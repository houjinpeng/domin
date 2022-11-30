import os
import sys
import time, datetime
import json
import threading, queue
from houhou.logger import Logger
from tool.get_beian import BeiAn
from tool.get_history import GetHistory
from tool.get_sogou import GetSougouRecord
from tool.get_baidu import BaiDu

from tool.get_360 import SoCom
import redis
from dbutils.pooled_db import PooledDB
from conf.config import *


db_pool = PooledDB(**mysql_pool_conf)

redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)
work_queue = queue.Queue()
history_obj = GetHistory()

mg_word = []
with open('./python_script/yikoujia/conf/敏感词.txt', 'r', encoding='utf-8') as fr:
    data = fr.readlines()
[mg_word.append(d.strip()) for d in data]





class FilterYm():
    def __init__(self, filter_id):
        # print(filter_id)
        self.filter_data = self.get_filter_data(filter_id)
        self.log = Logger(f'./python_script/yikoujia/logs/支线_{self.filter_data["title"]}.log').logger
        # self.log = Logger(f'./python_script/yikoujia/logs/主线程_{self.filter_data["title"]}').logger

        self.filter_data['place_2'] = 9999999999 if self.filter_data['place_2'] == 0 else self.filter_data['place_2']
        self.filter_dict = json.loads(self.filter_data['data'])
        self.ym_list= []

    #获取id的那条数据
    def get_filter_data(self,id):
        conn = db_pool.connection()
        cur = conn.cursor()
        select_sql = "select * from ym_yikoujia_buy_filter where  id=%s"%id
        cur.execute(select_sql)
        data = cur.fetchone()
        return data

    def get_history_token(self,data_list):
        ls = []
        new_data_list = []
        ym_dict = {}
        for data in list(data_list):
            ym_dict[data['ym']] = ''
            if len(ls) < 2000:
                ls.append(data['ym'])
                continue

            # 2000个获取一次token
            if len(ls) == 2000:
                token_list = history_obj.get_token(ls)
                ls = []
                # 放入域名列表中
                for d in token_list:
                    # data['token'] = d['token']
                    ym_dict[d['ym']] =  d['token']
                continue

        if len(ls) != 0:
            token_list = history_obj.get_token(ls)
            # 放入域名列表中
            for d in token_list:
                ym_dict[d['ym']] = d['token']

        for data in list(data_list):
            data['token'] = ym_dict[data['ym']]
            new_data_list.append(data)

        return new_data_list

    #h获取数据
    def get_work_data(self):
        while True:
            #redis去重
            all_data = redis_cli.sdiff(f'ym_data_{self.filter_data["main_filter_id"]}',f'out_ym_data_{self.filter_data["id"]}')
            if len(all_data) == 0:
                time.sleep(3)
                continue
            #内存去重
            new_data = []
            for data in all_data:
                data = json.loads(data)
                if data['ym'] not in self.ym_list:
                    self.ym_list.append(data['ym'])
                    new_data.append(data)

            #判断是否检测历史
            if self.filter_dict.get('history'):
                new_data = self.get_history_token(new_data)

            #存入任务队列
            for data in new_data:
                work_queue.put(data)
            time.sleep(3)

    #修改爬虫状态
    def update_spider_status(self,table, spider_id, update_status):
        conn = db_pool.connection()
        cur = conn.cursor()
        up_date_sql = "update %s set spider_status= %s ,pid=%s where id=%s" % (table, update_status, os.getpid(),spider_id)
        cur.execute(up_date_sql)
        conn.commit()
        conn.close()
        cur.close()

    #保存过滤完毕的数据
    def save_out_data(self,domain_data):
        if domain_data.get('token'):
            del domain_data['token']
        redis_cli.sadd(f'out_ym_data_{self.filter_data["id"]}', json.dumps(domain_data))

    #保存需要购买的域名
    def save_buy_ym(self,domain_data):
        conn = db_pool.connection()
        cur = conn.cursor()
        save_sql ="insert into ym_yikoujia_buy (buy_filter_id,ym) values ('%s','%s')"%(self.filter_data['id'],domain_data['ym'])
        cur.execute(save_sql)
        conn.commit()
        conn.close()
        cur.close()

    # 购买域名的线程
    def buy_ym(self):
      pass

    # 备案对比
    def comp_beian(self, domain, beian):

        beian_info = beian.beian_info(domain['ym'])
        if beian_info == None:
            # domain['cause'] = '没有备案'
            # out_ym_quque.put(domain)
            # log.logger.debug(f'查询备案失败，重新放入列表重新查询  {domain["domain"]}')
            # work_queue.put(domain)
            return self.comp_beian(domain, beian)

        try:
            # 没有备案的过滤
            if len(beian_info['params']['list']) == 0:
                domain['cause'] = '没有备案'
                self.log.debug(f' 域名为：{domain["ym"]} 备案 {domain["cause"]}')
                # out_ym_quque.put(domain)
                return domain['cause']
            xingzhi = beian_info['params']['list'][0]['natureName']
            if xingzhi in self.filter_dict['beian']['beian_xz'].split(','):
                domain['cause'] = f'备案 域名性质为：{xingzhi}'
                self.log.debug(f'域名为：{domain["ym"]} 备案 域名性质为：{xingzhi}')
                # out_ym_quque.put(domain)
                return domain['cause']

            beiai_num = beian_info['params']['list'][0]['serviceLicence']
            # 判断备案号 大于自定义号码的过滤
            if int(self.filter_dict['beian']['beian_suffix']) < int(beiai_num.split('-')[1]):
                domain['cause'] = f"备案号为：{beiai_num.split('-')[1]} 您设置的备案号为：{self.filter_dict['beian']['beian_suffix']}"
                # self.log.logger.debug(f'域名为：{domain["ym"]} 备案 {domain["cause"]}')
                # out_ym_quque.put(domain)
                return domain['cause']

            # 判断历史审核时间
            up_time = beian_info['params']['list'][0]['updateRecordTime']
            day = (datetime.datetime.now() - datetime.datetime.strptime(up_time, '%Y-%m-%d %H:%M:%S')).days
            if day <= int(self.filter_dict['beian']['beian_pcts']):
                domain['cause'] = f"备案历史审核时间为：{day}天  您设置的审核时间为：{self.filter_dict['beian']['beian_pcts']}天"
                self.log.logger.debug(f'域名为：{domain["ym"]} 备案 {domain["cause"]}')
                # out_ym_quque.put(domain)
                return domain['cause']

            return True
        except Exception as e:
            domain['cause'] = str(e)
            self.log.error(e)
            return False


    # 对比敏感词
    def check_mingan(self, history_data):
        try:
            if history_data['data'] == None:
                self.log.info(f'剩余任务：{work_queue.qsize()}   历史对比完毕 没有历史')
                return True
        except Exception as e:
            return '没有历史'
        title_list = [title['bt'] for title in history_data['data']]

        for word in mg_word:
            for title in title_list:
                if word in title:
                    return word
        return True


    # 获取历史数据进行对比
    def get_history_comp(self, domain):
        # 保存到完成域名中
        history_data = history_obj.get_history({'ym': domain['ym'], 'token': domain['token']})
        # 判断是否对比关键词
        if self.filter_dict['history']['history_is_com_word'] == '1':
            is_mingan = self.check_mingan(history_data)
            if is_mingan != True:
                # out_ym_quque.put({'cause':is_mingan,'ym':domain['domain'],'id':domain['id']})
                return is_mingan

        # 判断是否对比年龄
        if self.filter_dict['history']['history_age_1'] == '0' and self.filter_dict['history']['history_age_2'] =='0':
            return True

        else :
            try:
                #如果最大值为0 赋值99999999
                self.filter_dict['history']['history_age_2'] = 99999999 if int(self.filter_dict['history']['history_age_2']) == 0 else int(self.filter_dict['history']['history_age_2'])
                age = history_obj.get_age(domain)
                if int(self.filter_dict['history']['history_age_1']) <= int(age['data']['nl']) <= self.filter_dict['history']['history_age_2']:
                    return True
                else:
                    return f"历史小于设置年龄 历史年龄：{age['data']['nl']}"
            except:
                return f"历史小于设置年龄"


    # 注册商对比
    def ckeck_zhuceshang(self, domain):
        #注册商
        zcs = domain['zcs']
        bao_list = self.filter_dict['zcs']['zcs_include'].split(',')
        for bao in bao_list:
            if bao in zcs:
                return True
        return '没有包含的注册商'



    def work(self, beian=None, baidu=None,sogou=None,so=None):
        global work_queue
        while True:
            if work_queue.empty():
                time.sleep(3)
                continue

            # 获取域名
            domain_data = work_queue.get()
            #先判断价格是否合适


            if self.filter_data['place_1'] > int(domain_data['jg']) or int(domain_data['jg']) > self.filter_data['place_2'] :
                self.log.info(f'购买金额不付 域名价格{domain_data["jg"]}')
                self.save_out_data(domain_data)
                continue

            self.log.info(f'剩余任务:{work_queue.qsize()}  域名开始对比：{domain_data["ym"]}')

            # 对比历史
            if self.filter_dict.get('history'):
                is_ok = self.get_history_comp(domain_data)  # 返回失败信息
                if is_ok != True:
                    self.log.info({'ym': domain_data['ym'], 'id': domain_data['id'], 'cause': is_ok})
                    self.save_out_data(domain_data)
                    continue

            # 备案
            if self.filter_dict.get('beian'):
                is_ok = self.comp_beian(domain_data, beian)
                if is_ok != True:
                    self.save_out_data(domain_data)
                    self.log.info({'ym': domain_data['ym'], 'id': domain_data['id'], 'cause': is_ok})
                    continue

            # 搜狗
            if self.filter_dict.get('sogou'):
                is_ok = sogou.check_sogou(domain_data['ym'], [self.filter_dict['sogou']['sogou_sl_1'],self.filter_dict['sogou']['sogou_sl_2']],self.filter_dict['sogou']['sogou_kz'])
                if is_ok != True:
                    self.log.info({'ym': domain_data['ym'], 'id': domain_data['id'], 'cause': is_ok})
                    continue

            # 注册商
            if self.filter_dict.get('zcs'):
                if self.ckeck_zhuceshang(domain_data) != True:
                    self.save_out_data(domain_data)
                    self.log.info({'ym': domain_data['ym'], 'id': domain_data['id'], 'cause': '注册商包含非法字符串'})
                    continue

            # # 360
            if self.filter_dict.get('so'):
                is_ok = so.check_360(domain_data['ym'])
                if is_ok == '请求失败':
                    work_queue.put(domain_data)
                    continue
                if is_ok != True:
                    self.save_out_data(domain_data)
                    self.log.info({'ym': domain_data['ym'], 'id': domain_data['id'], 'cause': is_ok})
                    continue

            # 百度
            if self.filter_dict.get('baidu'):
                if domain_data['ym'] == None:
                    continue
                baidu_info_resp = baidu.get_info(domain_data['ym'])
                is_ok = baidu.check_baidu(baidu_info_resp,domain_data['ym'])
                if is_ok == '请求失败':
                    work_queue.put(domain_data)
                    continue
                if is_ok != True:
                    self.save_out_data(domain_data)
                    self.log.info({'ym': domain_data['ym'], 'id': domain_data['id'], 'cause': is_ok})
                    continue

            self.log.info({'ym': domain_data['ym'], 'id': domain_data['id'], 'cause': '需要购买'})
            self.save_out_data(domain_data)

            #最后判断是否被墙 如果被墙不买

            self.save_buy_ym(domain_data)


    def index(self):
        # 启动获取数据线程
        self.log.info(f'任务进程号：{os.getpid()}')
        # 修改状态 进行中
        self.update_spider_status('ym_yikoujia_buy_filter', self.filter_data['id'], 1)
        threading.Thread(target=self.get_work_data).start()
        for i in range(200):
            baidu=None
            beian=None
            sogou = None
            so = None
            if self.filter_dict.get('beian'):
                beian = BeiAn()
            if self.filter_dict.get('baidu'):
                baidu_record = [self.filter_dict['baidu']['baidu_sl_1'],self.filter_dict['baidu']['baidu_sl_2']]
                kuaizhao_time = self.filter_dict['baidu']['baidu_jg']
                lang_chinese = self.filter_dict['baidu']['baidu_is_com_chinese']
                min_gan_word = self.filter_dict['baidu']['baidu_is_com_word']

                baidu = BaiDu(baidu_record, kuaizhao_time,lang_chinese, min_gan_word)

            if self.filter_dict.get('sogou'):

                sogou = GetSougouRecord()

            if self.filter_dict.get('so'):
                so_record1 = self.filter_dict['so']['so_sl_1']
                so_record2 = self.filter_dict['so']['so_sl_2']
                fengxian = self.filter_dict['so']['so_fxts']
                kuaizhao_time = self.filter_dict['so']['so_jg']
                so = SoCom([so_record1,so_record2],fengxian,kuaizhao_time)


            # 启动任务线程程
            threading.Thread(target=self.work, args=(beian,baidu,sogou,so)).start()


if __name__ == '__main__':
    jkt_id = sys.argv[1]
    print(jkt_id)
    filter = FilterYm(jkt_id)
    filter.index()
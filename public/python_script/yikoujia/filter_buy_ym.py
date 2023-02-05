import os
import sys
import time, datetime
import json
import threading, queue
from datetime import date, timedelta

import pymongo
from houhou.logger import Logger
from tool.get_beian import BeiAn
from tool.get_history import GetHistory
from tool.get_sogou import GetSougouRecord
from tool.get_baidu import BaiDu
from tool.check_qiang import Qiang
from tool.get_360 import SoCom
from tool.get_aizhan import AiZhan
from tool.get_min_gan_word import get_mingan_word
from tool.JvZi import JvZi
from dbutils.pooled_db import PooledDB
from conf.config import *
from tool.jmApi import JmApi
from pymysql.converters import escape_string
import socket

jm_api = JmApi()
db_pool = PooledDB(**mysql_pool_conf)

history_obj = GetHistory()

# 获取敏感词
mg_word = get_mingan_word()


class Client():
    def __init__(self,filter_id,filter_dict,queue,log_queue,get_history_token):
        self.client = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        self.queue = queue
        self.log_queue = log_queue
        self.filter_dict = filter_dict
        self.filter_id = filter_id
        self.get_history_token = get_history_token

    def connect(self, server_ip, server_port):
        self.client.connect((server_ip, server_port))

    def run(self):

        self.client.sendall(str(self.filter_id).encode())
        error_data = ''
        while True:
            response = self.client.recv(102400000)
            try:
                data = response.decode()
                if data == '':time.sleep(0.2) ;continue
                data_list = data.split('||||||')
                new_data_list = []
                for data in data_list:
                    if error_data != '':
                        data = error_data + data
                        error_data = ''

                    if data.strip() == '':continue
                    try:
                        data = json.loads(data.replace('|',''))
                    except Exception :
                        error_data = data
                        continue

                    new_data_list.append(data)
                if new_data_list == []:continue
                # # 判断是否检测历史
                if self.filter_dict.get('history'):
                    if new_data_list[0].get('history') == None:
                        new_data_list = self.get_history_token(new_data_list)

                for d in new_data_list:
                    self.queue.put(d)
                    self.log_queue.put(f'本次插入队列数据:{d["ym"]}')
            except Exception as e:
                print(f'获取数据错误：{e}')
                self.log_queue.put(f'获取数据错误：{e}')

#启动插入日志队列

class FilterYm():
    def __init__(self, filter_id):
        self.filter_id = filter_id
        self.start_step = 0

    # 获取id的那条数据
    def get_filter_data(self, id):
        conn = db_pool.connection()
        cur = conn.cursor()
        select_sql = "select * from ym_yikoujia_buy_filter where  id=%s" % id
        cur.execute(select_sql)
        data = cur.fetchone()
        #主线的设置   用线程数
        main_filter = "select * from ym_yikoujia_jkt where id='%s'"%(data['main_filter_id'])
        cur.execute(main_filter)
        self.main_filter = cur.fetchone()
        cur.close()
        conn.close()
        return data

        # 定时清除任务线程

    def clear_data(self):
        start_time = str(self.main_filter['start_time'])[:19]
        if self.main_filter['clear_time_str'] != '':
            is_delete = False
            while True:
                if str(datetime.datetime.now())[11:16] == self.main_filter['clear_time_str']:
                    if is_delete == False:
                        self.ym_set.clear()
                    is_delete = True
                    time.sleep(3)
                    continue
                time.sleep(3)
                is_delete = False
        else:
            while True:
                t = datetime.datetime.strptime(start_time, '%Y-%m-%d %H:%M:%S')
                t = t + datetime.timedelta(hours=self.main_filter['clear_time'])
                if datetime.datetime.now() > t:
                    # 删除数据
                    start_time = str(datetime.datetime.now())[:19]
                    self.ym_set.clear()

                time.sleep(3)


    def get_history_token(self, data_list):
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
                    ym_dict[d['ym']] = d['token']
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

    # h获取数据
    def get_work_data(self):
        try:
            client = Client(self.filter_id,self.filter_dict,self.work_queue,self.log_queue,self.get_history_token)
            client.connect('127.0.0.1', self.main_filter['port'])
            # client.run()
            threading.Thread(target=client.run).start()
        except Exception as e:
            time.sleep(3)
            #重新获取port
            print(f'websocket 主线id:{self.main_filter["id"]} 支线id：{self.filter_id} 主线端口：{self.main_filter["port"]} 连接错误 {e} ')
            self.get_filter_data(self.filter_id)
            return self.get_work_data()

    # # h获取数据
    # def get_work_data(self):
    #     while True:
    #         # redis去重
    #         all_data = self.mycol.find().limit(1000).skip(self.start_step)
    #         self.start_step += 1000
    #         new_data = []
    #         is_have = False
    #         for data in all_data:
    #             is_have =True
    #             old_len = len(self.ym_set)
    #             self.ym_set.add(data['ym'])
    #             if old_len != len(self.ym_set):
    #                 new_data.append(data)
    #         if is_have == False:
    #             self.start_step -= 2000
    #             if self.start_step < 0:
    #                 self.start_step = 0
    #         if new_data == []:
    #             time.sleep(1)
    #             continue
    #         # 判断是否检测历史
    #         if self.filter_dict.get('history'):
    #             if new_data != []:
    #                 if new_data[0].get('history') == None:
    #                     new_data = self.get_history_token(new_data)
    #
    #         # 存入任务队列
    #         for data in new_data:
    #             self.work_queue.put(data)
    #         self.log_queue.put(f'本次插入队列数据:{len(new_data)}')
    #         time.sleep(1)

    # 修改爬虫状态
    def update_spider_status(self, table, spider_id, update_status):
        conn = db_pool.connection()
        cur = conn.cursor()
        update_sql = "update %s set spider_status= %s ,pid=%s where id=%s" % (table, update_status, os.getpid(), spider_id)
        cur.execute(update_sql)
        conn.commit()
        conn.close()
        cur.close()

    # 保存过滤完毕的数据
    def save_out_data(self, domain_data):
        pass
        # if domain_data.get('token'):
        #     del domain_data['token']
        # redis_cli.sadd(f'out_ym_data_{self.filter_data["id"]}', json.dumps(domain_data))

    # 保存需要购买的域名
    def save_buy_ym(self, domain_data,is_buy=0,main = '',zhi='',price='',):
        conn = db_pool.connection()
        cur = conn.cursor()

        if is_buy == 1:
            save_sql1 = "insert into all_buy_ym (main_name,zhi_name,ym,price) values ('%s','%s','%s','%s')" % (main,zhi,domain_data['ym'],price)
            cur.execute(save_sql1)
            conn.commit()

        if '失败' not in main:
            save_sql = "insert into ym_yikoujia_buy (buy_filter_id,ym,is_buy) values ('%s','%s','%s')" % (self.filter_data['id'], domain_data['ym'],is_buy)
            cur.execute(save_sql)
            conn.commit()
        conn.close()
        cur.close()


    def save_logs(self):
        while True:
            last_date = date.today().strftime('%Y%m%d')
            dir_path = f'./logs/logs_{last_date}'
            try:
                if os.path.exists(dir_path) == False:
                    os.mkdir(dir_path)
                if os.path.exists(f'{dir_path}/zhi_log') == False:
                    os.mkdir(f"{dir_path}/zhi_log")
                with open(f'{dir_path}/zhi_log/zhi_{self.filter_id}.log', 'a', encoding='utf-8') as fw:
                    while True:
                        today = date.today().strftime('%Y%m%d')
                        if last_date != today:
                            break
                        if self.log_queue.empty():
                            time.sleep(2)
                            continue
                        msg = self.log_queue.get()
                        fw.write(f'{str(datetime.datetime.now())[:19]} {str(msg).strip()}\n')
                        fw.flush()

            except Exception :
                pass



        # conn = db_pool.connection()
        # cur = conn.cursor()
        # while True:
        #     if self.log_queue.empty():
        #         time.sleep(2)
        #         continue
        #     msg = self.log_queue.get()
        #     insert_sql = "insert into ym_jkt_logs (`type`,filter_id,`msg`) values ('%s','%s','%s')"%(2,self.filter_id,escape_string(str(msg)))
        #     cur.execute(insert_sql)
        #     conn.commit()


    # 购买域名的线程
    def buy_ym(self, domain_data):

        resp = jm_api.buy_ykj(domain_data['ym'],domain_data['jg'])
        self.log_queue.put(resp)
        if '系统繁忙，请重试' in str(resp):
            self.log_queue.put(str(resp))
            return self.buy_ym(domain_data)


        if resp['code'] == 1:
            self.log_queue.put('购买成功')
            self.save_buy_ym(domain_data,is_buy=1,zhi=self.filter_data['title'],main=self.main_filter['title'],price=domain_data['jg'])

        elif resp['code'] == -11:
            if resp['msg'] == '该域名已被GFW(国家防火墙)拦截,是否确认购买？' or resp['msg'] == '该域名购买后无法解析，需将域名续费或转出至其他注册商才能解析，比较麻烦，是否确认购买？':
                self.log_queue.put('域名被拦截 或者无法解析 不购买')
            else:
                # 判断是否购买可赎回域名
                if self.filter_data['is_buy_sh'] == 1:
                    resp = jm_api.buy_ykj(domain_data['ym'], domain_data['jg'],ty=3)
                    if resp['code'] == 1:
                        self.log_queue.put(f'{domain_data["ym"]} 可赎回域名 购买成功')
                        self.save_buy_ym(domain_data, is_buy=1, zhi=self.filter_data['title'], main=self.main_filter['title'], price=domain_data['jg'])
                    else:
                        self.log_queue.put(f'购买失败 {resp}')
                        self.save_buy_ym(domain_data, is_buy=1, zhi=self.filter_data['title'] + ' 失败',main=self.main_filter['title'] + ' 失败', price=domain_data['jg'])

                else:
                    self.log_queue.put(f'{domain_data["ym"]} 可赎回域名不购买')

        else:
            self.save_buy_ym(domain_data, is_buy=1, zhi=self.filter_data['title']+' 失败', main=self.main_filter['title']+' 失败', price=domain_data['jg'])
            self.log_queue.put(f'购买失败 {resp}')

    # 备案对比
    def comp_beian(self, domain, beian):
        if domain.get('beian'):
            beian_info = domain.get('beian')
        else:
            beian_info = beian.beian_info(domain['ym'])
        if beian_info == None:
            # domain['cause'] = '没有备案'
            # out_ym_quque.put(domain)
            self.log_queue.put(f'查询备案失败，重新放入列表重新查询  {domain["domain"]}')
            # self.work_queue.put(domain)
            return self.comp_beian(domain, beian)

        try:
            # 没有备案的过滤
            if len(beian_info['params']['list']) == 0:
                domain['cause'] = '没有备案'
                # self.log_queue.put(f' 域名为：{domain["ym"]} 备案 {domain["cause"]}')
                # out_ym_quque.put(domain)
                return domain['cause']
            xingzhi = beian_info['params']['list'][0]['natureName']
            if xingzhi in self.filter_dict['beian']['beian_xz'].split(','):
                domain['cause'] = f'备案 域名性质为：{xingzhi}'
                # self.log_queue.put(f'域名为：{domain["ym"]} 备案 域名性质为：{xingzhi}')
                # out_ym_quque.put(domain)
                return domain['cause']

            beiai_num = beian_info['params']['list'][0]['serviceLicence']
            # 判断备案号 大于自定义号码的过滤
            if int(self.filter_dict['beian']['beian_suffix']) < int(beiai_num.split('-')[1]):
                domain['cause'] = f"备案号为：{beiai_num.split('-')[1]} 您设置的备案号为：{self.filter_dict['beian']['beian_suffix']}"
                # self.log_queue.put(f'域名为：{domain["ym"]} 备案 {domain["cause"]}')
                # out_ym_quque.put(domain)
                return domain['cause']

            # 判断历史审核时间
            up_time = beian_info['params']['list'][0]['updateRecordTime']
            day = (datetime.datetime.now() - datetime.datetime.strptime(up_time, '%Y-%m-%d %H:%M:%S')).days
            if day <= int(self.filter_dict['beian']['beian_pcts']):
                domain['cause'] = f"备案历史审核时间为：{day}天  您设置的审核时间为：{self.filter_dict['beian']['beian_pcts']}天"
                # self.log_queue.put(f'域名为：{domain["ym"]} 备案 {domain["cause"]}')
                # out_ym_quque.put(domain)
                return domain['cause']
            return True
        except Exception as e:
            domain['cause'] = str(e)
            self.log_queue.put(f'过滤备案错误：{e}')
            return e

    # 对比敏感词
    def check_mingan(self, history_data):
        try:
            if history_data['data'] == None:
                self.log_queue.put(f'剩余任务：{self.work_queue.qsize()}   历史对比完毕 没有历史')
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
    def get_history_comp(self, domain,history_obj):
        # 保存到完成域名中
        if domain.get('history'):
            history_data = domain.get('history')
        else:
            history_data = history_obj.get_history({'ym': domain['ym'], 'token': domain['token']})
            if history_data == None:
                return f'历史查询  域名有问题 {domain["ym"]}'
            if history_data == False:
                self.work_queue.put(domain)
                return f"历史查询  重新对比 {domain['ym']}"

        # 判断是否对比关键词
        if self.filter_dict['history']['history_is_com_word'] == '1':
            is_mingan = self.check_mingan(history_data)
            if is_mingan != True:
                return is_mingan

        age = None
        # 判断是否对比年龄
        if self.filter_dict['history']['history_age_1'] != '0' or self.filter_dict['history']['history_age_2'] != '0':
            try:
                # 如果最大值为0 赋值99999999
                self.filter_dict['history']['history_age_2'] = 99999999 if int(self.filter_dict['history']['history_age_2']) == 0 else int(self.filter_dict['history']['history_age_2'])
                age = history_obj.get_age(domain)
                if age == None:
                    return f"历史   域名：{domain['ym']} 没有年龄"
                if int(self.filter_dict['history']['history_age_1']) > int(age['data']['nl']) or self.filter_dict['history']['history_age_2'] < int(age['data']['nl']):
                    return f"历史设置年龄不符 历史年龄：{age['data']['nl']}"
            except Exception as error:
                return f"历史小于设置年龄 域名：{domain['ym']} 错误{error}"

        #对比评分
        if self.filter_dict['history']['history_score_1'] !='0' or self.filter_dict['history']['history_score_1'] != '0':
            #为None 重新获取
            if age == None:
                age = history_obj.get_age(domain)

            score_1 = 0 if self.filter_dict['history']['history_score_1'] == '0' else int(self.filter_dict['history']['history_score_1'])
            score_2 = 9999999 if self.filter_dict['history']['history_score_2'] == '0' else int(self.filter_dict['history']['history_score_2'])
            if score_1 > int(age['data']['pf']) or score_2 < int(age['data']['pf']):
                return f"历史 评分不符  评分为：{age['data']['pf']}"

        # 判断中文条数
        if self.filter_dict['history']['history_chinese_1'] != '0' or self.filter_dict['history']['history_chinese_2'] != '0':
            chinese_1 = 0 if self.filter_dict['history']['history_chinese_1'] == '0' else int(self.filter_dict['history']['history_chinese_1'])
            chinese_2 = 9999999 if self.filter_dict['history']['history_chinese_2'] == '0' else int(self.filter_dict['history']['history_chinese_2'])
            chinese_tiaoshu = history_obj.get_zh_title_num(history_data)
            if chinese_1 > chinese_tiaoshu or chinese_2 < chinese_tiaoshu:
                return f"历史 中文条数不符  中文条数为：{chinese_tiaoshu}"

        # 判断最长连续时间
        if self.filter_dict['history']['history_lianxu_1'] != '0' or self.filter_dict['history']['history_lianxu_2'] != '0':
            history_lianxu_1 = 0 if self.filter_dict['history']['history_lianxu_1'] == '0' else int(self.filter_dict['history']['history_lianxu_1'])
            history_lianxu_2 = 9999999 if self.filter_dict['history']['history_lianxu_2'] == '0' else int(self.filter_dict['history']['history_lianxu_2'])

            lianxu_num = history_obj.get_lianxu_cundang_time(history_data)
            if history_lianxu_1 > lianxu_num or history_lianxu_2 < lianxu_num:
                return f"历史 最长连续时间不符  连续时间为：{lianxu_num}"

        # 判断5年连续
        if self.filter_dict['history']['history_five_lianxu_1'] != '0' or self.filter_dict['history']['history_five_lianxu_2'] != '0':
            history_lianxu_1 = 0 if self.filter_dict['history']['history_five_lianxu_1'] == '0' else int(self.filter_dict['history']['history_five_lianxu_1'])
            history_lianxu_2 = 9999999 if self.filter_dict['history']['history_five_lianxu_2'] == '0' else int(self.filter_dict['history']['history_five_lianxu_2'])

            lianxu_num = history_obj.get_lianxu_cundang_time(history_data,5)
            if history_lianxu_1 > lianxu_num or history_lianxu_2 < lianxu_num:
                return f"历史 5年连续时间不符  连续时间为：{lianxu_num}"


        #判断近5年历史
        if self.filter_dict['history']['history_five_1'] != '0' or self.filter_dict['history']['history_five_2'] != '0':
            history_lianxu_1 = 0 if self.filter_dict['history']['history_five_1'] == '0' else int(self.filter_dict['history']['history_five_1'])
            history_lianxu_2 = 9999999 if self.filter_dict['history']['history_five_2'] == '0' else int(self.filter_dict['history']['history_five_2'])

            lianxu_num = history_obj.get_five_year_num(history_data)
            if history_lianxu_1 > lianxu_num or history_lianxu_2 < lianxu_num:
                return f"历史 5年历史不符  历史年数为：{lianxu_num}"

        #判断统一度
        if self.filter_dict['history']['history_tongyidu_1'] != '0' or self.filter_dict['history']['history_tongyidu_2'] != '0':
            tongyidu_num1 = 0 if self.filter_dict['history']['history_tongyidu_1'] == '0' else int(self.filter_dict['history']['history_tongyidu_1'])
            tongyidu_num2 = 9999999 if self.filter_dict['history']['history_tongyidu_2'] == '0' else int(self.filter_dict['history']['history_tongyidu_2'])

            tongyidu_num = history_obj.get_tongyidu(history_data)
            if tongyidu_num1 > tongyidu_num or tongyidu_num2 < tongyidu_num:
                return f"历史 统一度不符  统一度为：{tongyidu_num}"
        return True

    # 注册商对比
    def ckeck_zhuceshang(self, domain):
        # 注册商
        zcs = domain['zcs']
        bao_list = self.filter_dict['zcs']['zcs_include'].split(',')
        for bao in bao_list:
            if bao in zcs:
                return True
        return '没有包含的注册商'

    # 对比worker
    def work(self, beian=None, baidu=None, sogou=None, so=None,aizhan_obj=None):
        qiang = Qiang()
        history_obj = GetHistory()

        while True:
            if self.work_queue.empty():
                time.sleep(0.5)
                continue

            # 获取域名
            domain_data = self.work_queue.get()
            self.log_queue.put(f'剩余任务:{self.work_queue.qsize()}  域名开始对比：{domain_data["ym"]}')
            #如果是一口先判断是否合适
            if self.main_filter['cate'] == '一口价':
                try:
                    self.out_ym.insert_one({'ym': domain_data['ym'], 'type': 'zhi', 'filter_id': self.filter_id})
                    if self.filter_data['place_1'] > int(domain_data['jg']) or int(domain_data['jg']) > self.filter_data['place_2']:
                        self.log_queue.put(f'购买金额不符 域名：{domain_data["ym"]}价格：{domain_data["jg"]}')
                        self.save_out_data(domain_data)
                        continue
                except Exception as error:
                    self.log_queue.put(f'对比金额错误： {error}')
                    continue


            try:
                # 对比历史
                if self.filter_dict.get('history'):
                    is_ok = self.get_history_comp(domain_data,history_obj)  # 返回失败信息
                    if is_ok != True:
                        self.log_queue.put({'ym': domain_data['ym'], 'cause': is_ok})
                        self.save_out_data(domain_data)
                        continue
            except Exception as error:
                self.log_queue.put(f'对比历史错误： {error}')
                continue
            try:
                # 备案
                if self.filter_dict.get('beian'):
                    is_ok = self.comp_beian(domain_data, beian)
                    if is_ok != True:
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'], 'cause': is_ok})
                        continue
            except Exception as error:
                self.log_queue.put(f'对比备案错误： {error}')
                continue
            try:
                # 搜狗
                if self.filter_dict.get('sogou'):
                    if domain_data.get('sogou'):
                        data = domain_data.get('sogou')
                    else:
                        data = sogou.get_info(domain_data['ym'])
                    if data == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'搜狗获取错误重新获取')
                        continue
                    is_ok = sogou.check_sogou(data['html'], [self.filter_dict['sogou']['sogou_sl_1'],self.filter_dict['sogou']['sogou_sl_2']],self.filter_dict['sogou']['sogou_kz'],domain=domain_data['ym'],sogou_is_com_word=self.filter_dict['sogou']['sogou_is_com_word'],jg=self.filter_dict['sogou']['sogou_jg'])
                    if is_ok != True:
                        self.log_queue.put({'ym': domain_data['ym'],  'cause': is_ok})
                        continue
            except Exception as error:
                self.log_queue.put(f'对比搜狗错误： {error}')
                continue
            try:
                # 注册商
                if self.filter_dict.get('zcs'):
                    if self.ckeck_zhuceshang(domain_data) != True:
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'], 'cause': '注册商包含非法字符串'})
                        continue
            except Exception as error:
                self.log_queue.put(f'对比注册商错误： {error}')
                continue
            try:
                # # 360
                if self.filter_dict.get('so'):
                    if domain_data.get('so'):
                        data = domain_data.get('so')
                    else:
                        data = so.get_info(domain_data['ym'])
                    if data == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'360获取错误重新获取')
                        continue
                    is_ok = so.check_360(data['html'],domain_data['ym'])
                    if is_ok == '请求失败':
                        self.work_queue.put(domain_data)
                        continue
                    if is_ok != True:
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'],  'cause': is_ok})
                        continue
            except Exception as error:
                self.log_queue.put(f'对比360错误： {error}')
                continue
            try:
                # 百度
                if self.filter_dict.get('baidu'):
                    if domain_data.get('baidu'):
                        data = domain_data.get('baidu')
                    else:
                        data = baidu.get_info(domain_data['ym'])
                    if data == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'百度获取错误重新获取')
                        continue
                    is_ok = baidu.check_baidu(data, domain_data['ym'])
                    if is_ok == '请求失败':
                        self.work_queue.put(domain_data)
                        continue
                    if is_ok != True:
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'],  'cause': is_ok})
                        continue
            except Exception as error:
                self.log_queue.put(f'对比百度错误： {error}')
                continue
            try:
                # 爱站
                if self.filter_dict.get('aizhan'):
                    if domain_data.get('aizhan'):
                        data = domain_data.get('aizhan')
                    else:
                        data = aizhan_obj.get_info(domain_data['ym'])
                    if data == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'爱站获取错误重新获取')
                        continue
                    is_ok = aizhan_obj.check_aizhan(data)

                    if is_ok != True:
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'], 'cause': is_ok})
                        continue
            except Exception as error:
                self.log_queue.put(f'对比爱站错误： {error}')
                continue
            try:
                # 最后判断是否被墙 如果被墙不买
                if self.filter_data['is_buy_qiang'] == 0:
                    r = qiang.get_qiang_data(domain_data['ym'])
                    if r == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'获取墙错误重新获取')
                        # self.save_out_data(domain_data)
                        continue
                    if r['msg'] == '被墙':
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'],  'cause': '域名被墙'})
                        continue

                if self.filter_data['is_buy_wx'] == 0:
                    self.log_queue.put(f"检测微信是否被墙 检测域名:{domain_data['ym']}")
                    r = qiang.get_wx_data(domain_data['ym'])
                    if r == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'检测微信是否被墙错误重新获取')

                        # self.save_out_data(domain_data)
                        continue
                    if r['msg'] == '微信拦截':
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'],  'cause': '微信拦截'})
                        continue

                if self.filter_data['is_buy_qq'] == 0:
                    r = qiang.get_qq_data(domain_data['ym'])
                    if r == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'检测QQ是否被墙错误重新获取')
                        # self.save_out_data(domain_data)
                        continue
                    if '拦截' in r['msg'] :
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'], 'cause': 'QQ拦截'})
                        continue

                if self.filter_data['is_buy_beian'] == 0:
                    r = qiang.get_qiang_data(domain_data['ym'])
                    if r == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'检测备案黑名单错误重新获取')
                        # self.save_out_data(domain_data)
                        continue
                    if r['msg'] != '正常':
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'],  'cause': '备案'+r['msg']})
                        continue

                if self.filter_data['is_buy_history'] == 0:
                    r = qiang.get_beian_data(domain_data['ym'])
                    if r == None:
                        self.work_queue.put(domain_data)
                        self.log_queue.put(f'检测备案建站记录 错误重新获取')
                        # self.save_out_data(domain_data)
                        continue
                    if r['data'].get('icp') == '':
                        self.save_out_data(domain_data)
                        self.log_queue.put({'ym': domain_data['ym'], 'cause': '建站记录:' + r['msg']})
                        continue

            except Exception as error:
                self.log_queue.put(f'判断被墙错误：{error} 失败域名:{domain_data["ym"]}')

            try:
                #判断桔子数据
                if self.filter_dict.get('jvzi') != None:
                    #保存到待查询数据中  等待桔子程序判断是否购买
                    self.insert_jv_ym(domain_data['ym'],domain_data['jg'])
                    continue

            except Exception as e:
                pass

            # 判断是否是过期域名还是一口价域名   一口价域名直接保存到数据库
            if self.main_filter['cate'] == '一口价':
                self.log_queue.put({'ym': domain_data['ym'], 'cause': '需要购买'})
                # 判断是否真的购买 真的购买直接下单 不购买直接保存到数据库里
                if self.filter_data['is_buy'] == 1:
                    self.buy_ym(domain_data)
                else:
                    self.save_buy_ym(domain_data)
            else:
                self.save_buy_ym(domain_data)

    #修改mysql中桔子查询域名
    def update_is_search(self,id):
        # 修改域名已查询
        conn = db_pool.connection()
        cur = conn.cursor()
        update_sql = "update search_jvzi_data set is_search=2 where id=%s " % (id)
        cur.execute(update_sql)
        conn.commit()
        cur.close()
        conn.close()

    #保存进查找桔子数据队列中
    def insert_jv_ym(self,ym,jg):
        conn = db_pool.connection()
        cur = conn.cursor()
        insert_sql = "insert into search_jvzi_data (ym,zhi_filter_id,jg) value('%s',%s,%s)"%(ym,self.filter_id,jg)
        cur.execute(insert_sql)
        conn.commit()
        cur.close()
        conn.close()

    #获取桔子数据  然后开启线程检测
    def get_jv_data(self):

        while True:
            conn = db_pool.connection()
            cur = conn.cursor()
            select_sql = "select * from search_jvzi_data where zhi_filter_id=%s and is_search=1 " % (self.filter_id)
            cur.execute(select_sql)
            all_data = cur.fetchall()
            conn.close()
            cur.close()
            for data in all_data:
                self.juzi_queue.put(data)
            if all_data == []:
                time.sleep(1)
                continue
            t = []
            for i in range(20):
                t.append(threading.Thread(target=self.check_jvzi))
            for j in t:
                j.start()
            for j in t:
                j.join()


    def check_jvzi(self):
        jvzi_obj = JvZi()
        while not self.juzi_queue.empty():

            data = self.juzi_queue.get()
            resp = jvzi_obj.get_detail_html(data['ym'],data['ym_url'])
            if resp == None:
                continue
            try:
                # 建站历史  近五年建站 近五年连续 最长连续 统一度 (标题  收录 内容敏感)
                d = self.filter_dict.get('jvzi')
                is_comp_title_mingan = 0 if d.get('jvzi_title_mingan') == None else d.get('jvzi_title_mingan')
                is_comp_neirong_mingan = 0 if d.get('jvzi_neirong_mingan') == None else d.get('jvzi_neirong_mingan')
                is_comp_soulu_mingan = 0 if d.get('jvzi_soulu_mingan') == None else d.get('jvzi_soulu_mingan')
                is_ok = jvzi_obj.check(resp, age=[d['jvzi_age_1'], d['jvzi_age_2']],
                                       five_create_store=[d['jvzi_five_1'], d['jvzi_five_2']],
                                       lianxu=[d['jvzi_lianxu_1'], d['jvzi_lianxu_2']],
                                       five_lianxu=[d['jvzi_five_lianxu_1'], d['jvzi_five_lianxu_2']],
                                       tongyidu=[d['jvzi_tongyidu_1'], d['jvzi_tongyidu_2']],
                                       is_comp_title_mingan=is_comp_title_mingan,
                                       is_comp_neirong_mingan=is_comp_neirong_mingan,
                                       is_comp_soulu_mingan=is_comp_soulu_mingan)
                if is_ok != True:
                    self.log_queue.put({'ym': data['ym'], 'cause': is_ok})
                    #修改域名已查询
                    self.update_is_search(data['id'])

                    continue
            except Exception as error:
                self.log_queue.put(f'桔子对比错误 ：{data} {error}')
                continue

            #判断是否购买 如果购买直接购买
            # 判断是否是过期域名还是一口价域名   一口价域名直接保存到数据库
            if self.main_filter['cate'] == '一口价':
                self.log_queue.put({'ym': data['ym'], 'cause': '需要购买'})
                # 判断是否真的购买 真的购买直接下单 不购买直接保存到数据库里
                if self.filter_data['is_buy'] == 1:
                    self.buy_ym(data)
                    self.update_is_search(data['id'])
                else:
                    self.update_is_search(data['id'])
                    self.save_buy_ym(data)
            else:
                self.update_is_search(data['id'])
                self.save_buy_ym(data)

    def index(self):
        #初始化
        self.myclient = pymongo.MongoClient("mongodb://localhost:27017/")
        self.mydb = self.myclient["domain"]

        self.work_queue = queue.Queue()  # 工作队列
        self.log_queue = queue.Queue()  # 日志队列
        self.juzi_queue = queue.Queue()  # 桔子队列

        self.main_filter = None

        self.filter_data = self.get_filter_data(self.filter_id)
        # 主线的mongo库
        self.mycol = self.mydb[f"ym_data_{self.filter_data['main_filter_id']}"]
        # self.log = Logger(f'/logs/支线_{self.filter_data["title"]}.log').logger
        self.filter_data['place_2'] = 9999999999 if self.filter_data['place_2'] == 0 else self.filter_data['place_2']
        self.filter_dict = json.loads(self.filter_data['data'])
        if self.filter_dict == []:
            self.filter_dict = {}
        self.ym_set = set()

        #判断是否是过期域名还是一口价域名
        if self.main_filter['cate'] == '一口价':
            # 启动清除内存数据
            threading.Thread(target=self.clear_data).start()


            # 保存查询过的数据
            self.out_ym = self.mydb['out_ym']

            all_out_data = self.out_ym.find({'filter_id': self.filter_id, 'type': 'zhi'})
            for out_ym in all_out_data:
                self.ym_set.add(out_ym['ym'])


        #判断是否使用桔子的筛选条件
        if self.filter_dict.get('jvzi') != None:
            #开启一个线程监控桔子数据
            threading.Thread(target=self.get_jv_data).start()

        # 启动日志队列
        threading.Thread(target=self.save_logs).start()

        self.log_queue.put(f'任务进程号：{os.getpid()}')
        # 修改状态 进行中
        self.update_spider_status('ym_yikoujia_buy_filter', self.filter_data['id'], 1)

        #启动获取数据线程
        threading.Thread(target=self.get_work_data).start()
        thread_list = []

        baidu = None
        beian = None
        sogou = None
        so = None
        aizhan_obj = None
        if self.filter_dict.get('beian'):
            beian = BeiAn()
        if self.filter_dict.get('aizhan'):
            baidu_pr = [self.filter_dict['aizhan']['aizhan_baidu_pr_1'], self.filter_dict['aizhan']['aizhan_baidu_pr_2']]
            yidong_pr = [self.filter_dict['aizhan']['aizhan_yidong_pr_1'], self.filter_dict['aizhan']['aizhan_yidong_pr_2']]
            sm_pr = [self.filter_dict['aizhan']['aizhan_sm_pr_1'], self.filter_dict['aizhan']['aizhan_sm_pr_2']]
            so_pr = [self.filter_dict['aizhan']['aizhan_so_pr_1'], self.filter_dict['aizhan']['aizhan_so_pr_2']]
            sogou_pr = [self.filter_dict['aizhan']['aizhan_sogou_pr_1'], self.filter_dict['aizhan']['aizhan_sogou_pr_2']]
            aizhan_obj = AiZhan(baidu_pr=baidu_pr,yidong_pr=yidong_pr,sm_pr=sm_pr,so_pr=so_pr,sogou_pr=sogou_pr)


        if self.filter_dict.get('baidu'):
            baidu_record = [self.filter_dict['baidu']['baidu_sl_1'], self.filter_dict['baidu']['baidu_sl_2']]
            kuaizhao_time = self.filter_dict['baidu']['baidu_jg']
            lang_chinese = self.filter_dict['baidu']['baidu_is_com_chinese']
            min_gan_word = self.filter_dict['baidu']['baidu_is_com_word']

            baidu = BaiDu(baidu_record, kuaizhao_time, lang_chinese, min_gan_word,)

        if self.filter_dict.get('sogou'):
            sogou = GetSougouRecord()

        if self.filter_dict.get('so'):
            so_record1 = self.filter_dict['so']['so_sl_1']
            so_record2 = self.filter_dict['so']['so_sl_2']
            fengxian = self.filter_dict['so']['so_fxts']
            kuaizhao_time = self.filter_dict['so']['so_jg']
            so_is_com_word = self.filter_dict['so']['so_is_com_word']
            so = SoCom([so_record1, so_record2], fengxian, kuaizhao_time,so_is_com_word)

        # for i in range(self.main_filter['task_num']):
        print('开始程序')
        for i in range(100):
            # 启动任务线程程
            thread_list.append(threading.Thread(target=self.work, args=(beian, baidu, sogou, so,aizhan_obj)))

        for t in thread_list:
            t.start()


        # 结束进程
        # os.system(f'taskkill -f -pid {os.getpid()}')



if __name__ == '__main__':
    # jkt_id = sys.argv[1]
    # jkt_id = 66 #测试过期域名
    jkt_id = 64 #测试桔子
    filter = FilterYm(jkt_id).index()

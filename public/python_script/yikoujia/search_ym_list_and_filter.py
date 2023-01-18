'''
按条件搜索域名列表 并筛选数据
'''
from datetime import date, timedelta
import os
import datetime
import time
from dbutils.pooled_db import PooledDB
from conf.config import *
import threading,queue
from tool.get_beian import BeiAn
from tool.jmApi import JmApi
from tool.get_baidu import BaiDu
from tool.get_sogou import GetSougouRecord
from tool.get_360 import SoCom
from tool.get_history import GetHistory
from tool.get_aizhan import AiZhan
import pymongo

class SearchYmAndFilter():
    def __init__(self,filter_id):
        self.filter_id = filter_id
        self.ym_set = set()

    #定时清除任务线程
    def clear_data(self):
        start_time = str(self.filter['start_time'])[:19]
        if self.filter['clear_time_str'] != '':
            is_delete = False
            while True:
                if str(datetime.datetime.now())[11:16] == self.filter['clear_time_str']:
                    if is_delete == False:
                        self.ym_set.clear()
                        self.mycol.delete_many({})
                    is_delete = True
                    time.sleep(3)
                    continue
                time.sleep(3)
                is_delete = False
        else:
            while True:
                t = datetime.datetime.strptime(start_time, '%Y-%m-%d %H:%M:%S')
                t = t + datetime.timedelta(hours=self.filter['clear_time'])
                if datetime.datetime.now() > t:
                    #删除数据
                    start_time = str(datetime.datetime.now())[:19]
                    self.ym_set.clear()
                    self.mycol.delete_many({})
                    self.update_spider_status('ym_yikoujia_jkt', self.filter['id'], 1)

                time.sleep(3)


    #日志任务
    def save_logs(self):

        while True:

            last_date = date.today().strftime('%Y%m%d')
            dir_path = f'./logs/logs_{last_date}'

            if os.path.exists(dir_path) == False:
                os.mkdir(dir_path)
            if os.path.exists(f'{dir_path}/main_log') == False:
                os.mkdir(f'{dir_path}/main_log')

            with open(f'{dir_path}/main_log/main_{self.filter_id}.log','a',encoding='utf-8') as fw:
                while True:
                    today = date.today().strftime('%Y%m%d')

                    if last_date != today:
                        break
                    if self.log_queue.empty():
                        time.sleep(2)
                        continue
                    msg = self.log_queue.get()
                    fw.write(f'{str(datetime.datetime.now())[:19]} {str(msg)}\n')
                    fw.flush()
                    # insert_sql = "insert into ym_jkt_logs (`type`,filter_id,`msg`) values ('%s','%s','%s')" % (1, self.filter_id, escape_string(str(msg)))
                    # cur.execute(insert_sql)
                    # conn.commit()

    #获取id的那条数据
    def get_filter_data(self,id):
        conn =self.db_pool.connection()
        cur = conn.cursor()
        select_sql = "select * from ym_yikoujia_jkt where  id=%s"%id
        cur.execute(select_sql)
        data = cur.fetchone()
        return data


    def update_spider_status(self,table, spider_id, update_status):
        time_str = str(datetime.datetime.now())[:19]
        conn = self.db_pool.connection()
        cur = conn.cursor()
        up_date_sql = "update %s set spider_status= %s ,p_id=%s,start_time='%s' where id=%s" % (table, update_status, self.p_id,time_str,spider_id)
        cur.execute(up_date_sql)
        conn.commit()
        cur.close()
        conn.close()


    #构建查询参数
    def build_search_data(self):
        data = {}
        data['psize'] = 1000
        for k,v in self.filter.items():
            if 'jm' in k and v != 0 and v != '' and v != '0':
                if k == 'jm_sfba_1':
                    if '&amp;' in v:
                        data['sfba_1'] = v.split('&amp;')[0]
                        data['baxz'] =  v.split('&amp;')[1].replace('baxz=','')
                    else:
                        data['sfba_1'] = v
                else:
                    data[k.replace('jm_','')] = v
        if data.get('jznl_2') or data.get('jznl_1') or data.get('jzjl_1') or data.get('jzjl_2'):
            data['jzls'] = 1
        if data.get('jgpx') == None:
            data['jgpx'] = 5
        return data

    def get_history_token(self, data_list):
        ls = []
        new_data_list = []
        ym_dict = {}
        history_obj = GetHistory()
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

    #解析数据
    def parse_info(self,resp):

        all_data = resp['data']
        # 内存去重 set
        new_data = []
        for data in all_data:
            lens = len(self.ym_set)
            self.ym_set.add(data['ym'])
            if len(self.ym_set) != lens:
                new_data.append(data)

        # 判断是否检测历史
        if self.filter['main_filter'] == '历史':
            new_data = self.get_history_token(new_data)


        # 解析列表
        for data in new_data:
            self.log_queue.put(f"插入域名：{data['ym']}-{data['jg']}")
            self.task_queue.put(data)


    #线程获取列表
    def get_list(self):
        onece = True
        conn = self.db_pool.connection()
        cur = conn.cursor()
        while True:
            if onece:
                onece = False
                for i in range(1, 1000000):
                    self.log_queue.put(f'开始查询列表第{i}页')
                    self.data['page'] = i
                    info = self.jm_api.get_ykj_list(self.data)
                    # 修改总数
                    update_sql = "update ym_yikoujia_jkt set filter_count= %s  where id=%s" % (info['count'],self.filter['id'])
                    cur.execute(update_sql)
                    conn.commit()
                    # self.log_queue.put(f'查询总数：{info["count"]}')
                    self.parse_info(info)
                    if info['data'] == []:
                        break
            else:
                self.data['page'] = 1
                info = self.jm_api.get_ykj_list(self.data)

                # 修改总数
                update_sql = "update ym_yikoujia_jkt set filter_count= %s  where id=%s" % (
                info['count'], self.filter['id'])
                cur.execute(update_sql)
                conn.commit()


                self.parse_info(info)

            # self.log_queue.put('本次查询任务结束')
            time.sleep(3)


    def save_mysql(self, ym_data, key, value):
        # save_conn = self.db_pool.connection()
        # save_cur = save_conn.cursor()
        try:
            data = {
                'ym': ym_data['ym'],
                'jg': ym_data['jg'],
                'zcs': ym_data['zcs'],
                'token': ym_data.get('token'),
                key: value,
                'create_time':str(datetime.datetime.now())[:19]
            }

            self.mycol.insert_one(data)
            # sql = "insert into ym_domain_detail (ym,`data`,`filter_type`,filter_id) VALUES ('%s','%s','%s','%s')"%(data['ym'],escape_string(json.dumps(data)),0,self.filter_id)
            # save_cur.execute(sql)
            # save_conn.commit()

        except Exception as error:
            print(f'保存数据库错误:{error}')


    #过滤备案
    def beian_worker(self):
        beian = BeiAn()
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            # print(f'备案剩余任务：{self.task_queue.qsize()}')
            info = beian.beian_info(ym_data['ym'])

            # 查询库中是否存在 不存在插入 存在更新
            if info == None:
                self.task_queue.put(ym_data)
                continue
            try:
                # 判断是否有备案  如果有备案放入redis数据库中
                if info['params']['total'] != 0:
                    self.save_mysql(ym_data,'beian',info)
                    # print(f'备案查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data}')
                    self.log_queue.put(f'备案查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data}')
                else:
                    # print(f'备案查询剩余任务：{self.task_queue.qsize()} 备案 过滤 {ym_data["ym"]}')
                    self.log_queue.put(f'备案查询剩余任务：{self.task_queue.qsize()} 备案 过滤 {ym_data["ym"]}')
            except Exception as error:
                print(f'备案错误：{error}')
                self.log_queue.put(f'备案查询剩余任务：{self.task_queue.qsize()}  错误：{error} ')

    #过滤百度
    def baidu_worker(self):
        baidu = BaiDu()
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            # print(f'百度剩余任务：{self.task_queue.qsize()}')
            info = baidu.get_info(ym_data['ym'])
            # 查询库中是否存在 不存在插入 存在更新
            if info == None:
                self.task_queue.put(ym_data)
                continue

            if int(info['sl']) > 0:
                # 有直接放入redis 没有过滤
                self.save_mysql(ym_data, 'baidu', info)
                self.log_queue.put(f'百度 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'百度 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')


    # 过滤搜狗
    def sogou_worker(self):
        sogou_obj = GetSougouRecord()
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            # print(f'搜狗剩余任务：{self.task_queue.qsize()}')

            info = sogou_obj.get_info(ym_data['ym'])
            # 查询库中是否存在 不存在插入 存在更新
            if info == None:
                self.task_queue.put(ym_data)
                continue

            if info['sl'] > 0:
                # 有直接放入redis 没有过滤
                self.save_mysql(ym_data, 'sogou', info)
                self.log_queue.put(f'搜狗 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'搜狗 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')

    # 过滤360
    def so_worker(self):
        so_obj = SoCom([1,2],'是','泛','')
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            # print(f'360剩余任务：{self.task_queue.qsize()}')
            info = so_obj.get_info(ym_data['ym'])
            # 查询库中是否存在 不存在插入 存在更新
            if info == None:
                self.task_queue.put(ym_data)
                continue
            if info['sl'] > 0:
                # 有直接放入redis 没有过滤
                self.save_mysql(ym_data, 'so', info)
                self.log_queue.put(f'360 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'360 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')

    # 过滤历史
    def history_worker(self):
        history_obj = GetHistory()
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            self.log_queue.put(f'历史 查询剩余任务：{self.task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            history_info = history_obj.get_history(ym_data)
            if history_info == None:
                self.log_queue.put(f'历史 查询失败  域名有问题 {ym_data["ym"]} {ym_data["token"]}')
                continue
            if history_info == False:
                self.task_queue.put(ym_data)
                self.log_queue.put(f'历史 查询失败  重新查询 {ym_data["ym"]} {ym_data["token"]}')
                continue
            try:
                if history_info['count'] == None:
                    self.log_queue.put(f'历史 查询剩余任务：{self.task_queue.qsize()}  过滤域名 {ym_data["ym"]}')
                    continue
                if int(history_info['count']) > 0 :
                    # 有直接放入redis 没有过滤
                    self.save_mysql(ym_data, 'history', history_info)
                    self.log_queue.put(f'历史 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
                else:
                    # 有直接放入redis 没有过滤
                    self.log_queue.put(f'历史 查询剩余任务：{self.task_queue.qsize()}  过滤域名 {ym_data["ym"]}')
            except Exception as e:
                # 有直接放入redis 没有过滤
                self.log_queue.put(f'历史 查询剩余任务：{self.task_queue.qsize()}  过滤域名 {ym_data["ym"]} 错误:{e}')

    #直接放入查询队列中
    def wu_work(self):
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            self.log_queue.put(f'查询剩余任务：{self.task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            self.save_mysql(ym_data, 'wu', 'wu')
            # self.log_queue.put(f'插入购买查询队列中 {ym_data}')

    #注册商
    def zcs_worker(self):
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            if ym_data['zcs'] != '':
                self.save_mysql(ym_data, 'zcs', ym_data['zcs'])
                self.log_queue.put(f'注册商 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data}')
            else:
                self.log_queue.put(f'注册商 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')

    # 爱站
    def aizhan_worker(self):
        aizhan_obj = AiZhan(['0','0'],['0','0'],['0','0'],['0','0'],['0','0'])
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            info = aizhan_obj.get_info(ym_data['ym'])
            if info == None:
                self.task_queue.put(ym_data)
                continue
            is_have = False
            for k,v in info.items():

                if k =='html':continue
                if v != '0' and v != 'n':
                    is_have = True
            if is_have == True:
                self.save_mysql(ym_data, 'aizhan', info)
                self.log_queue.put(f'爱站 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'爱站 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')

    #主程序
    def index(self):
        self.db_pool = PooledDB(**mysql_pool_conf)
        self.myclient = pymongo.MongoClient("mongodb://localhost:27017/")
        self.mydb = self.myclient["domain"]
        self.jm_api = JmApi()
        self.filter = self.get_filter_data(self.filter_id)
        self.p_id = None
        self.data = self.build_search_data()
        self.log_queue = queue.Queue()  # 日志队列
        self.task_queue = queue.Queue()
        self.mycol = self.mydb[f"ym_data_{self.filter_id}"]

        all_data  = self.mycol.find()
        for data in all_data:
            self.ym_set.add(data['ym'])
        del all_data


        # 启动日志队列
        threading.Thread(target=self.save_logs).start()
        #启动删除数据任务
        threading.Thread(target=self.clear_data).start()
        self.p_id = os.getpid()
        self.log_queue.put(f'任务进程号：{self.p_id}')
        self.log_queue.put(f'查询表单：{self.data}')

        #修改状态 进行中
        self.update_spider_status('ym_yikoujia_jkt',self.filter['id'],1)
        ############################################################################
        #抓取数据
        get_list_thread_list =[]
        for i in range(1):
            get_list_thread_list.append(threading.Thread(target=self.get_list))
        for t in get_list_thread_list:
            t.start()
        ############################################################################
        thread_list = []
        # for i in range(self.filter['task_num']):
        for i in range(100):
            if self.filter['main_filter'] == '备案':
                thread_list.append(threading.Thread(target=self.beian_worker))

            elif self.filter['main_filter'] == '百度':
                thread_list.append(threading.Thread(target=self.baidu_worker))

            elif self.filter['main_filter'] == '搜狗':
                thread_list.append(threading.Thread(target=self.sogou_worker))

            elif self.filter['main_filter'] == '360':
                thread_list.append(threading.Thread(target=self.so_worker))

            elif self.filter['main_filter'] == '注册商':
                thread_list.append(threading.Thread(target=self.zcs_worker))

            elif self.filter['main_filter'] == '历史':
                thread_list.append(threading.Thread(target=self.history_worker))
            elif self.filter['main_filter'] == '爱站':
                thread_list.append(threading.Thread(target=self.aizhan_worker))

            elif self.filter['main_filter'] == '无':
                thread_list.append(threading.Thread(target=self.wu_work))

        for t in thread_list:
            t.start()
        for t in thread_list:
            t.join()


if __name__ == '__main__':
    # jkt_id = sys.argv[1]
    jkt_id = 53
    filter = SearchYmAndFilter(jkt_id).index()
    # filter = SearchYmAndFilter(40).index()

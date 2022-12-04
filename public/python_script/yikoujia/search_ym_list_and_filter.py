'''
按条件搜索域名列表 并筛选数据
'''
import os
import time
from dbutils.pooled_db import PooledDB
from conf.config import *
from houhou.logger import Logger
import sys
import threading,queue
from tool.get_beian import BeiAn
from houhou.sql_handler import *
import redis
from tool.jmApi import JmApi

jm_api = JmApi()
db_pool = PooledDB(**mysql_pool_conf)

redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)
task_queue = queue.Queue()

class SearchYmAndFilter():
    def __init__(self,filter_id):
        self.filter = self.get_filter_data(filter_id)
        self.p_id = None
        self.data = self.build_search_data()
        self.ym_list = []
        self.log = Logger(f'/logs/主线_{self.filter["title"]}.log').logger

    #获取id的那条数据
    def get_filter_data(self,id):
        conn = db_pool.connection()
        cur = conn.cursor()
        select_sql = "select * from ym_yikoujia_jkt where  id=%s"%id
        cur.execute(select_sql)
        data = cur.fetchone()
        return data


    def update_spider_status(self,table, spider_id, update_status):
        conn = db_pool.connection()
        cur = conn.cursor()
        up_date_sql = "update %s set spider_status= %s ,p_id=%s where id=%s" % (table, update_status, self.p_id,spider_id)
        cur.execute(up_date_sql)
        conn.commit()
        cur.close()
        conn.close()

    #查询域名是否存在
    def check_domain_is_in_databases(self,ym):
        conn = db_pool.connection()
        cur = conn.cursor()
        sql = 'select * from ym_domain_detail where ym="%s"'%(ym)
        cur.execute(sql)
        data = cur.fetchone()
        cur.close()
        conn.close()
        if data == None:
            return False
        else:
            return data

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
        return data

    #解析数据
    def parse_info(self,resp):

        all_data = resp['data']
        # 解析列表
        for data in all_data:
            try:
                if data['ym'] not in self.ym_list:
                    task_queue.put(data)
                    self.ym_list.append(data['ym'])
            except Exception as e:
                continue

    #线程获取列表
    def get_list(self):
        ggg = False
        while True:
            for i in range(1, 1000000):
                self.log.info(f'开始过滤列表第{i}页')
                self.data['page'] = i
                info = jm_api.get_ykj_list(self.data)
                #修改总数
                if ggg == False:
                    conn = db_pool.connection()
                    cur = conn.cursor()
                    update_sql = "update ym_yikoujia_jkt set filter_count= %s  where id=%s" % (info['count'],self.filter['id'])
                    cur.execute(update_sql)
                    conn.commit()
                    cur.close()
                    conn.close()
                    ggg = True
                    self.log.info(f'查询总数：{info["count"]}')

                self.parse_info(info)
                if info['data'] == []:
                    break
            time.sleep(3)

    #过滤备案
    def beian_worker(self):
        beian = BeiAn()
        conn = db_pool.connection()
        cur = conn.cursor()
        while True:
            if task_queue.empty():
                time.sleep(1)
                continue
            ym_data = task_queue.get()
            self.log.info(f'备案查询剩余任务：{task_queue.qsize()} 当前数据:{ym_data["ym"]}')

            info = beian.beian_info(ym_data['ym'])
            # 查询库中是否存在 不存在插入 存在更新
            if info == None:
                task_queue.put(ym_data)
                continue

            # 判断是否有备案  如果有备案放入redis数据库中
            if info['params']['total'] != 0:
                redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
                self.log.info(f'插入购买查询队列中 {ym_data}')
            # update_sql = "update ym_domain_detail set beian='%s' where id=%s" % (json.dumps(info), is_have['id'])
            # cur.execute(update_sql)
            # conn.commit()

            # is_have = self.check_domain_is_in_databases(ym_data['ym'])
            #判断域名是否有备案   有备案直接放入redis 没有过滤
            # if is_have != False and is_have['beian'] != None:
            #     pass
            # if is_have == False:
            #     info = beian.beian_info(ym_data['ym'])
            #
            #     #查询库中是否存在 不存在插入 存在更新
            #     if info == None:
            #         task_queue.put(ym_data)
            #         continue
            #
            #     #判断是否有备案  如果有备案放入redis数据库中
            #     if info['params']['total'] != 0:
            #         # 有备案直接放入redis 没有过滤
            #         redis_cli.sadd(f'ym_data_{self.filter["id"]}',json.dumps(ym_data))
            #         log.info(f'插入购买查询队列中 {ym_data}')
            #         pass
            #     ym_dict = {'beian': info, 'ym': ym_data['ym']}
            #     sql = build_insert_sql(ym_dict,'ym_domain_detail')
            #     cur.execute(sql)
            #     conn.commit()
            #
            # else:
            #     if is_have['beian'] == None:
            #         info = beian.beian_info(ym_data['ym'])
            #         # 查询库中是否存在 不存在插入 存在更新
            #         if info == None:
            #             task_queue.put(ym_data)
            #             continue
            #
            #         # 判断是否有备案  如果有备案放入redis数据库中
            #         if info['params']['total'] != 0:
            #             redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
            #             log.info(f'插入购买查询队列中 {ym_data}')
            #         update_sql = "update ym_domain_detail set beian='%s' where id=%s"%(json.dumps(info),is_have['id'])
            #         cur.execute(update_sql)
            #         conn.commit()


                # elif is_have['beian']['params']['total']!=0:
                #     redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))

    #过滤百度
    def baidu_worker(self):
        while True:
            if task_queue.empty():
                time.sleep(1)
                continue
            ym_data = task_queue.get()
            self.log.info(f'百度 查询剩余任务：{task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            if ym_data['bdsl'] > 0:
                # 有直接放入redis 没有过滤
                redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
                # self.log.info(f'插入购买查询队列中 {ym_data}')

    # 过滤搜狗
    def sogou_worker(self):
        while True:
            if task_queue.empty():
                time.sleep(1)
                continue
            ym_data = task_queue.get()
            self.log.info(f'搜狗 查询剩余任务：{task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            if ym_data['sgsl'] > 0:
                # 有备案直接放入redis 没有过滤
                redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
                # self.log.info(f'插入购买查询队列中 {ym_data["ym"]}')

    # 过滤360
    def so_worker(self):
        while True:
            if task_queue.empty():
                time.sleep(1)
                continue
            ym_data = task_queue.get()
            self.log.info(f'360 查询剩余任务：{task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            if ym_data['sosl'] > 0:
                # 有备案直接放入redis 没有过滤
                redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
                # self.log.info(f'插入购买查询队列中 {ym_data}')

    # 过滤历史
    def history_worker(self):
        while True:
            if task_queue.empty():
                time.sleep(1)
                continue
            ym_data = task_queue.get()
            self.log.info(f'历史 查询剩余任务：{task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            if ym_data['jzls'] > 0:
                redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
                # self.log.info(f'插入购买查询队列中 {ym_data}')

    #直接放入查询队列中
    def wu_work(self):
        while True:
            if task_queue.empty():
                time.sleep(1)
                continue
            ym_data = task_queue.get()
            self.log.info(f'查询剩余任务：{task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
            # self.log.info(f'插入购买查询队列中 {ym_data}')

    #注册商
    def zcs_worker(self):
        while True:
            if task_queue.empty():
                time.sleep(1)
                continue
            ym_data = task_queue.get()
            if ym_data['jj'] != '':
                redis_cli.sadd(f'ym_data_{self.filter["id"]}', json.dumps(ym_data))
                # self.log.info(f'插入购买查询队列中 {ym_data}')

    #主程序
    def index(self):
        self.p_id = os.getpid()
        self.log.info(f'任务进程号：{self.p_id}')
        self.log.info(f'查询表单：{self.data}')

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
        for i in range(self.filter['task_num']):
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

            elif self.filter['main_filter'] == '无':
                thread_list.append(threading.Thread(target=self.baidu_worker))


        for t in thread_list:
            t.start()
        for t in thread_list:
            t.join()


if __name__ == '__main__':
    # jkt_id = sys.argv[1]
    jkt_id = 34
    filter = SearchYmAndFilter(jkt_id).index()
    # filter = SearchYmAndFilter(40).index()

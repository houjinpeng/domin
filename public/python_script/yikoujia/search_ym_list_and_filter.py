'''
按条件搜索域名列表 并筛选数据
'''
import json
import random
from datetime import date
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
from socketserver import BaseRequestHandler, ThreadingTCPServer
from functools import partial

#websoket
class Handler(BaseRequestHandler):


    def __init__(self,save_ym, *args, **kwargs):
        self.save_ym = save_ym   #所有的域名结合
        self.zhi_id_list = []  #支线列表
        self.createVar = locals()  #动态创建参数


        # super(BaseRequestHandler, self).__init__( **kwargs)
        super().__init__(*args, **kwargs)

    def handle(self) -> None:
        address, pid = self.client_address
        data = self.request.recv(1024)
        zhi_id = data.decode()

        #判断是否是重启的
        if zhi_id not in self.zhi_id_list:
            #接收支线的id 创建一个 发送过的集合
            self.createVar[f'send_set_{zhi_id}'] = set()

        print(f'{address} connected!')

        while True:
            #对比发送的
            qvchong_data = self.save_ym.difference(self.createVar[f'send_set_{zhi_id}'])
            # qvchong_data = self.createVar[f'send_set_{zhi_id}'].difference(self.ym_set)
            # print(f'添加数据{len(qvchong_data)}')
            for d in qvchong_data:
                send_data = d+'||||||'
                # self.request.sendall(d.encode())
                self.request.send(send_data.encode())
                self.createVar[f'send_set_{zhi_id}'].add(d)
            # print(f'receive data: {str(i).decode()}')

            time.sleep(0.3)



class SearchYmAndFilter():
    def __init__(self,filter_id):
        self.filter_id = filter_id


    def create_socket(self):
        handler = partial(Handler, self.save_ym)
        while True:
            self.port = random.randint(10000, 65530)
            try:
                server = ThreadingTCPServer(('127.0.0.1', self.port), handler)
                print(f"Listening 端口号：{self.port}")
                # server.serve_forever()
                threading.Thread(target=server.serve_forever).start()
                break
            except Exception as e:
                print(f'主线81行错误：{e}')

    #定时清除任务线程
    def clear_data(self):
        if self.filter['start_time'] == None:
            start_time =str(datetime.datetime.now())[:19]
        else:
            start_time = str(self.filter['start_time'])[:19]
        if self.filter['clear_time_str'] != '':
            is_delete = False
            while True:
                if str(datetime.datetime.now())[11:16] == self.filter['clear_time_str']:
                    if is_delete == False:
                        self.ym_set.clear()
                        self.save_ym.clear()
                        self.mycol.delete_many({})
                        self.task_queue.queue.clear()
                        self.out_ym.delete_many({'type': 'main', 'filter_id':self.filter_id})
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
                    self.save_ym.clear()
                    self.mycol.delete_many({})
                    self.out_ym.delete_many({'type':'main','filter_id':self.filter_id})
                    self.update_spider_status('ym_yikoujia_jkt', self.filter['id'], 1)

                time.sleep(3)


    #日志任务
    def save_logs(self):

        while True:

            last_date = date.today().strftime('%Y%m%d')
            dir_path = f'./logs/logs_{last_date}'
            try:
                if os.path.exists(dir_path) == False:
                    os.mkdir(dir_path)
                if os.path.exists(f'{dir_path}/main_log') == False:
                    os.mkdir(f'{dir_path}/main_log')
                if self.filter['cate'] == '一口价':
                    with open(f'{dir_path}/main_log/main_{self.filter_id}.log', 'a', encoding='utf-8') as fw:
                        while True:
                            today = date.today().strftime('%Y%m%d')

                            if last_date != today:
                                break
                            if self.log_queue.empty():
                                time.sleep(2)
                                continue
                            msg = self.log_queue.get()
                            fw.write(f'{str(msg).strip()}\n')
                            fw.flush()
                elif self.filter['cate'] == '过期域名':
                    with open(f'{dir_path}/main_log/main_{self.filter_id}.log', 'a', encoding='utf-8') as fw:
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
            except Exception as e:
                pass


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
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 开始查询列表第{i}页')
                    self.data['page'] = i
                    info = self.jm_api.get_ykj_list(self.data)
                    # 修改总数
                    update_sql = "update ym_yikoujia_jkt set filter_count= %s  where id=%s" % (info['count'],self.filter['id'])
                    cur.execute(update_sql)
                    conn.commit()
                    # self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 查询总数：{info["count"]}')
                    self.parse_info(info)
                    if info['data'] == []:
                        break
            else:
                self.data['page'] = 1
                info = self.jm_api.get_ykj_list(self.data)

                # 修改总数
                update_sql = "update ym_yikoujia_jkt set filter_count= %s  where id=%s" % (info['count'], self.filter['id'])
                cur.execute(update_sql)
                conn.commit()
                self.parse_info(info)

            # self.log_queue.put('本次查询任务结束')
            time.sleep(1)


    def save_mysql(self, ym_data, key, value):
        # save_conn = self.db_pool.connection()
        # save_cur = save_conn.cursor()
        try:
            data = {
                'ym': ym_data['ym'],
                'jg': ym_data.get('jg'),
                'zcs': ym_data.get('zcs'),
                'token': ym_data.get('token'),
                key: value,
                'create_time': str(datetime.datetime.now())[:19]
            }
            self.save_ym.add(json.dumps(data))
            self.mycol.insert_one(data)

        except Exception as error:
            self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 保存数据库错误:{error}')
            print(f'保存数据库错误:{error}')


    #过滤备案
    def beian_worker(self):
        beian = BeiAn()
        if self.filter['cate'] == '过期域名':
            while not self.task_queue.empty():
                ym_data = self.task_queue.get()

                start_time = int(time.time())
                # print(f'备案剩余任务：{self.task_queue.qsize()}')
                info = beian.beian_info(ym_data['ym'])

                # 查询库中是否存在 不存在插入 存在更新
                if info == None:
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 备案查询失败 重新插入队列等待查询： {ym_data["ym"]}   本次运行时间：{int(time.time()-start_time)}秒')
                    self.task_queue.put(ym_data)
                    continue
                try:
                    self.out_ym.insert_one({'ym': ym_data['ym'], 'type': 'main', 'filter_id': self.filter_id})

                    # 判断是否有备案  如果有备案放入redis数据库中
                    if info['params']['total'] != 0:
                        self.save_mysql(ym_data, 'beian', info)
                        # print(f'备案查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data}')
                        self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 备案查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}  本次运行时间：{int(time.time()-start_time)}秒')
                    else:
                        # print(f'备案查询剩余任务：{self.task_queue.qsize()} 备案 过滤 {ym_data["ym"]}')
                        self.log_queue.put(f'{str(datetime.datetime.now())[:19]} 备案查询剩余任务：{self.task_queue.qsize()} 备案 过滤 {ym_data["ym"]}  本次运行时间：{int(time.time()-start_time)}秒')
                except Exception as error:
                    print(f'备案错误：{error}')
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} 备案查询剩余任务：{self.task_queue.qsize()}  错误：{error}  本次运行时间：{int(time.time()-start_time)}秒 ')

        else:
            while True:
                if self.task_queue.empty():
                    time.sleep(1)
                    continue


                ym_data = self.task_queue.get()
                start_time = int(time.time())
                # print(f'备案剩余任务：{self.task_queue.qsize()}')
                info = beian.beian_info(ym_data['ym'])

                # 查询库中是否存在 不存在插入 存在更新
                if info == None:
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} 备案查询失败 重新插入队列等待查询： {ym_data["ym"]}   本次运行时间：{int(time.time() - start_time)}秒')
                    self.task_queue.put(ym_data)
                    continue
                try:
                    self.out_ym.insert_one({'ym':ym_data['ym'],'type':'main','filter_id':self.filter_id})

                    # 判断是否有备案  如果有备案放入redis数据库中
                    if info['params']['total'] != 0:
                        self.save_mysql(ym_data,'beian',info)
                        # print(f'备案查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data}')
                        self.log_queue.put(f'{str(datetime.datetime.now())[:19]} 备案查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}  本次运行时间：{int(time.time()-start_time)}秒')
                    else:
                        # print(f'备案查询剩余任务：{self.task_queue.qsize()} 备案 过滤 {ym_data["ym"]}')
                        self.log_queue.put(f'{str(datetime.datetime.now())[:19]} 备案查询剩余任务：{self.task_queue.qsize()} 备案 过滤 {ym_data["ym"]}  本次运行时间：{int(time.time()-start_time)}秒')
                except Exception as error:
                    print(f'备案错误：{error}')
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} 备案查询剩余任务：{self.task_queue.qsize()}  错误：{error} ')

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
            self.out_ym.insert_one({'ym': ym_data['ym'], 'type': 'main', 'filter_id': self.filter_id})

            if int(info['sl']) > 0:
                # 有直接放入redis 没有过滤
                self.save_mysql(ym_data, 'baidu', info)
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 百度 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 百度 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')


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
            self.out_ym.insert_one({'ym': ym_data['ym'], 'type': 'main', 'filter_id': self.filter_id})

            if info['sl'] > 0:
                # 有直接放入redis 没有过滤
                self.save_mysql(ym_data, 'sogou', info)
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 搜狗 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 搜狗 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')


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
            self.out_ym.insert_one({'ym': ym_data['ym'], 'type': 'main', 'filter_id': self.filter_id})

            if info['sl'] > 0:
                # 有直接放入redis 没有过滤
                self.save_mysql(ym_data, 'so', info)
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 360 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 360 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')



    # 过滤历史
    def history_worker(self):
        history_obj = GetHistory()

        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()

            self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 历史 查询剩余任务：{self.task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            history_info = history_obj.get_history(ym_data)
            if history_info == None:
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 历史 查询失败  域名有问题 {ym_data["ym"]} {ym_data["token"]}')
                continue

            if history_info == False:
                self.task_queue.put(ym_data)
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 历史 查询失败  重新查询 {ym_data["ym"]} {ym_data["token"]}')
                continue
            self.out_ym.insert_one({'ym': ym_data['ym'], 'type': 'main', 'filter_id':self.filter_id})

            try:
                if history_info['count'] == None:
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 历史 查询剩余任务：{self.task_queue.qsize()}  过滤域名 {ym_data["ym"]}')
                    continue
                if int(history_info['count']) > 0 :
                    # 有直接放入redis 没有过滤
                    self.save_mysql(ym_data, 'history', history_info)
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 历史 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
                else:
                    # 有直接放入redis 没有过滤
                    self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 历史 查询剩余任务：{self.task_queue.qsize()}  过滤域名 {ym_data["ym"]}')
            except Exception as e:
                # 有直接放入redis 没有过滤
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 历史 查询剩余任务：{self.task_queue.qsize()}  过滤域名 {ym_data["ym"]} 错误:{e}')

    #直接放入查询队列中
    def wu_work(self):

        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 查询剩余任务：{self.task_queue.qsize()} 当前数据:{ym_data["ym"]}')
            self.save_mysql(ym_data, 'wu', 'wu')
            # self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 插入购买查询队列中 {ym_data}')

    #注册商
    def zcs_worker(self):
        while True:
            if self.task_queue.empty():
                time.sleep(1)
                continue
            ym_data = self.task_queue.get()
            self.out_ym.insert_one({'ym': ym_data['ym'], 'type': 'main', 'filter_id':self.filter_id})

            if ym_data['zcs'] != '':
                self.save_mysql(ym_data, 'zcs', ym_data['zcs'])
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 注册商 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 注册商 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')

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
            self.out_ym.insert_one({'ym': ym_data['ym'], 'type': 'main', 'filter_id': self.filter_id})
            is_have = False
            for k, v in info.items():

                if k == 'html': continue
                if v != '0' and v != 'n':
                    is_have = True
            if is_have == True:
                self.save_mysql(ym_data, 'aizhan', info)
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 爱站 查询剩余任务：{self.task_queue.qsize()}  插入购买查询队列中 {ym_data["ym"]}')
            else:
                self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 爱站 查询剩余任务：{self.task_queue.qsize()} 过滤当前数据:{ym_data["ym"]}')

    #主程序
    def index(self):
        self.ym_set = set()
        self.db_pool = PooledDB(**mysql_pool_conf)
        self.myclient = pymongo.MongoClient("mongodb://localhost:27017/")
        # self.myclient = pymongo.MongoClient('mongodb://myspider:maiyuan123@127.0.0.1:27017/')

        self.mydb = self.myclient["domain"]
        self.jm_api = JmApi()
        self.filter = self.get_filter_data(self.filter_id)
        self.p_id = None
        self.data = self.build_search_data()
        self.log_queue = queue.Queue()  # 日志队列
        self.task_queue = queue.Queue()


        #保存查询过的数据
        self.out_ym = self.mydb['out_ym']
        #需要发送的域名
        self.save_ym = set()



        all_out_data = self.out_ym.find({'filter_id':self.filter_id,'type':'main'})
        for out_ym in all_out_data:
            self.ym_set.add(out_ym['ym'])

        del all_out_data

        self.p_id = os.getpid()
        self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 任务进程号：{self.p_id}')
        self.log_queue.put(f'{str(datetime.datetime.now())[:19]} {str(datetime.datetime.now())[:19]} 查询表单：{self.data}')
        # 修改状态 进行中
        self.update_spider_status('ym_yikoujia_jkt', self.filter['id'], 1)

        # 启动日志队列
        threading.Thread(target=self.save_logs).start()
        self.mycol = self.mydb[f"ym_data_{self.filter_id}"]
        all_data = self.mycol.find()
        for data in all_data:
            self.ym_set.add(data['ym'])
        del all_data

        if self.filter['cate'] == '一口价':

            # 启动删除数据任务
            threading.Thread(target=self.clear_data).start()
            ############################################################################
            #抓取列表数据
            threading.Thread(target=self.get_list).start()
            ############################################################################
        else:

            #查询所有域名数据
            select_all_data_sql = "select * from ym_guoqi_main_ym"
            conn = self.db_pool.connection()
            cur = conn.cursor()
            cur.execute(select_all_data_sql)
            all_data = cur.fetchall()

            # 判断是否检测历史
            if self.filter['main_filter'] == '历史':
                all_data = self.get_history_token(all_data)

            for data in all_data:
                self.task_queue.put(data)

        # 启动webscoket
        threading.Thread(target=self.create_socket).start()
        time.sleep(3)
        # 修改端口号
        conn = self.db_pool.connection()
        cur = conn.cursor()
        up_date_sql = "update ym_yikoujia_jkt set port= %s where id=%s" % (self.port, self.filter_id)
        cur.execute(up_date_sql)
        conn.commit()
        cur.close()
        conn.close()



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

        time.sleep(60 * 10)
        self.update_spider_status('ym_yikoujia_jkt', self.filter_id, 2)


if __name__ == '__main__':
    # jkt_id = sys.argv[1]
    jkt_id = 47 #测试桔子
    # jkt_id = 77 #测试过期域名
    # jkt_id = 45
    filter = SearchYmAndFilter(jkt_id).index()
    # filter = SearchYmAndFilter(40).index()

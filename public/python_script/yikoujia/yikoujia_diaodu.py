'''

p = Process(target=函数名，args=参数， name=进程名)
#创建进程对象，执行任务 a
p.start()
#启动多进程
os.getpid()
#获取当前的进程id号
os.getppid()
#获取进程的父进程id号
multiprocessing.current_process().name
#获取当前进程名称

'''
import threading

from dbutils.pooled_db import PooledDB
from conf.config import *
from multiprocessing import Process
import time
from filter_buy_ym import FilterYm
from search_ym_list_and_filter import SearchYmAndFilter
from houhou.logger import Logger
from tool.save_jvzi import JvZi
import os
db_pool = PooledDB(**mysql_pool_conf)

log = Logger().logger


def check():

    conn = db_pool.connection()
    cur = conn.cursor()

    # 查找启动了确找不到id的进程 重新启动
    main_sql = "select * from ym_yikoujia_jkt where spider_status=1"
    cur.execute(main_sql)
    all_main_data = cur.fetchall()

    for dd in all_main_data:
        # 判断进程是否存在 不存在重新启动
        result = os.system(f'tasklist | findstr {dd["p_id"]}')
        if result == 1:
            # 重启
            search_obj = SearchYmAndFilter(dd['id'])
            process_task = Process(target=search_obj.index)
            # 设置安全进程   主线退出后 子线程也退出
            # process_task.daemon = True
            print(f'重启主线  {dd["title"]} 开始运行')
            process_task.start()

    ###############################################################################
    zhi_sql = 'select * from ym_yikoujia_buy_filter where spider_status=1'
    cur.execute(zhi_sql)
    all_zhi = cur.fetchall()
    cur.close()
    conn.close()
    for z in all_zhi:
        # 判断进程是否存在 不存在重新启动
        result = os.system(f'tasklist | findstr {z["pid"]}')
        if result == 1:
            # 重启z
            filter_obj = FilterYm(z['id'])
            process_task = Process(target=filter_obj.index)
            # 设置安全进程   主线退出后 子线程也退出
            # process_task.daemon = True
            process_task.start()
            print(f'重启支线  {z["title"]} 开始运行')




#监控程序
def scheduler():
    # check()

    #启动查询桔子线程
    threading.Thread(target=JvZi().index).start()
    time.sleep(60*60*24)

    while True:

        conn = db_pool.connection()
        cur = conn.cursor()


        ###############################################################################

        # 监控主条件和一口价条件  只负责列表的搜索和主条件过滤
        sql = 'select * from ym_yikoujia_jkt where spider_status=0'
        cur.execute(sql)
        all_filter = cur.fetchall()
        #启动进程
        for filter in all_filter:
            #修改为进行中
            search_obj = SearchYmAndFilter(filter['id'])
            process_task = Process(target=search_obj.index)
            #设置安全进程   主线退出后 子线程也退出
            # process_task.daemon = True
            print(f'主线  {filter["title"]} 开始运行')
            process_task.start()

        #监控子条件 并购买
        zhi_sql = 'select * from ym_yikoujia_buy_filter where spider_status=0'
        cur.execute(zhi_sql)
        all_zhi = cur.fetchall()
        for zhi in all_zhi:
            filter_obj = FilterYm(zhi['id'])
            process_task = Process(target=filter_obj.index)
            # 设置安全进程   主线退出后 子线程也退出
            # process_task.daemon = True
            process_task.start()
            print(f'支线  {zhi["title"]} 开始运行')

        time.sleep(10)


if __name__ == '__main__':
    scheduler()
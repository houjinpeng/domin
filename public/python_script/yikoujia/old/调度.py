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
from dbutils.pooled_db import PooledDB
from conf.config import *
from multiprocessing import Process
import time
from filter_buy_ym import FilterYm
from search_ym_list_and_filter import SearchYmAndFilter
from houhou.logger import Logger

db_pool = PooledDB(**mysql_pool_conf)

log = Logger().logger

#监控程序
def scheduler():
    while True:
        sql = 'select * from ym_yikoujia_jkt where spider_status=0'
        #监控主条件和一口价条件  只负责列表的搜索和主条件过滤
        conn = db_pool.connection()
        cur = conn.cursor()
        cur.execute(sql)
        all_filter = cur.fetchall()
        #启动进程
        for filter in all_filter:
            #修改为进行中
            search_obj = SearchYmAndFilter(filter)
            process_task = Process(target=search_obj.index,args=())
            #设置安全进程   主线退出后 子线程也退出
            process_task.daemon = True
            process_task.start()

        #监控子条件 并购买
        zhi_sql = 'select * from ym_yikoujia_buy_filter where spider_status=0'
        cur.execute(zhi_sql)
        all_zhi = cur.fetchall()
        for zhi in all_zhi:
            filter_obj = FilterYm(zhi)
            process_task = Process(target=filter_obj.index)
            # 设置安全进程   主线退出后 子线程也退出
            process_task.daemon = True
            process_task.start()


        time.sleep(3)


if __name__ == '__main__':
    scheduler()
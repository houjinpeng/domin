
from dbutils.pooled_db import PooledDB
from conf.config import *
db_pool = PooledDB(**mysql_pool_conf)

def get_mingan_word():
    # 获取敏感词
    mg_word = []
    c = db_pool.connection()
    cur = c.cursor()
    sql = "select `value` from ym_system_config where name='min_gan_word'"
    cur.execute(sql)
    data = cur.fetchone()
    cur.close()
    c.close()
    [mg_word.append(d.strip()) for d in data['value'].split('\n')]
    return mg_word
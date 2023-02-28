
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

def get_exclude_store_id():
    store_id_list = []
    c = db_pool.connection()
    cur = c.cursor()
    sql = "select `value` from ym_system_config where name='hmd'"
    cur.execute(sql)
    data = cur.fetchone()
    cur.close()
    c.close()
    if data == None:
        return []
    s = data['value'].split('\n')
    for ss in s:
        try:
            store_id_list.append(int(ss))
        except:
            pass
    return store_id_list

if __name__ == '__main__':
    print(get_exclude_store_id())
import threading,queue
import sys
import pymysql
import requests
from lxml import etree
import json
from pymysql.converters import escape_string
from tool.jmApi import JmApi
MYSQL_CONF = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '123456',
    'db': 'domain',
}

jm_api = JmApi()


#获取库存和销量
def get_store_kc_sales(store_id):
    #获取店铺详情
    store_info = jm_api.get_store_info(store_id)
    # 获取库存
    search_data = {
        'ymbhfs': 2,
        'gjz_cha': store_id
    }
    kuncun = jm_api.get_ykj_list(data=search_data)
    try:
        sales = store_info['data']['m_xinyong']
        kuncun = kuncun['count']
    except Exception as error:
        # print(error)
        sales = 0
        kuncun = 0



    return sales,kuncun


#构建数据库
def build_sql(table,data_dict):
        insert_sql = f'insert into {table} ('
        for key in data_dict.keys():
            if data_dict[key] == None:
                continue
            insert_sql += f"`{key}`" + ','
        insert_sql = insert_sql[:-1]
        insert_sql += ') values ('

        for val in data_dict.values():
            if val == None:
                continue
            if isinstance(val, str) == True:
                val = escape_string(val)
            elif isinstance(val, dict) == True or isinstance(val, list) == True:
                val = escape_string(json.dumps(val))
            elif isinstance(val, int) == True:
                val = str(val)

            insert_sql += f"'{str(val)}'" + ','
        insert_sql = insert_sql[:-1]
        insert_sql += ')'
        return insert_sql


#保存数据
def save_data(sql,cur,conn):
        try:
            cur.execute(sql)
            conn.commit()
            return True
        except Exception as e:
            return 'error'


#检查域名是否存在
def check_exits(ym_dict,cur):
    select_sql = "select `id` from ym_domain_sales where ym='%s' and fixture_date='%s' and store_id_hide='%s'" % (
        escape_string(ym_dict['ym']), escape_string(ym_dict['fixture_date']), escape_string(ym_dict['store_id_hide']))
    cur.execute(select_sql)
    data = cur.fetchone()
    if data == None:
        return False
    return True

#获取店铺今日销量
def get_store_today_sales(store_id):
    resp_data = f'zt=1&cjsj=1&ymbhfs=2&gjz_cha={store_id}&psize=500&page=1'
    search_data = {
        'zt': 1,
        'cjsj': 7,  # 今本月数据
        'psize': 1000,
        'page': 1,
        'gjz_cha': store_id,
        'ymbhfs': 2
    }

    #获取今日成交数据
    all_data = jm_api.get_ykj_cj_list(search_data)

    if len(all_data) == 0:
        return True
    conn = pymysql.connect(**MYSQL_CONF)
    cur = conn.cursor()
    for index, data in enumerate(all_data):
        try:
            e = etree.HTML(data['jj'])
            ym_dict = {
                'ym': data['ym'],
                'len': data['cd'],
                'store_id_hide': data['sid'],
                'store_id': data['sid'],
                'fixture_date': data['cjsj'],
                'price': data['jg'],
                'jj': ''.join(e.xpath('.//text()')),
                'mj_jj': data['ms'],
            }
            # 检查是否存在
            if check_exits(ym_dict,cur) == True:
                continue

            ym_dict['store_id'] = store_id

            if '*' in ym_dict['ym']:
                continue
            insert_sql = build_sql('ym_domain_sales', ym_dict)
            save_data(insert_sql,cur,conn)

        except Exception as error:
            pass
            # log.logger.error(f'{error}')

    cur.close()
    conn.close()
    #需要插入
    return True





def update_store():
    conn = pymysql.connect(**MYSQL_CONF)
    cur = conn.cursor()
    while not task_queue.empty():

        store_id = task_queue.get()
        #获取总销量和库存
        sales,kucun = get_store_kc_sales(store_id)
        sql = "update ym_domain_store set sales=%s,repertory=%s where store_id=%s"%(sales,kucun,store_id)
        print(sql)
        cur.execute(sql)
        conn.commit()
        ###################################
        #获取今日销量
        get_store_today_sales(store_id)

    cur.close()
    conn.close()




def start():
    for i in range(10):
        t.append(threading.Thread(target=update_store))
    for j in t:
        j.start()
    for j in t:
        j.join()
    print('success')


if __name__ == '__main__':
    store_ids = sys.argv[1].split(',')
    # store_ids = '41000'.split(',')
    task_queue = queue.Queue()
    t = []
    for store_id in store_ids:
        if store_id.strip() == '' :continue
        task_queue.put(store_id.strip())

    start()


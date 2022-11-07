import threading,queue
import sys
import pymysql
import requests
from lxml import etree
import json
from pymysql.converters import escape_string
MYSQL_CONF = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '123456',
    'db': 'domain',
}


def request_handler(url,method='get',data=''):
    try:
        conn = pymysql.connect(**MYSQL_CONF)
        cur = conn.cursor()
        sql = 'select cookie from ym_domain_config'
        cur.execute(sql)
        cookie = cur.fetchone()
        cur.close()
        conn.close()
        cookie = cookie[0]



        if method == 'get':
            headers = {
                "accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                "accept-encoding": "gzip, deflate, br",
                "accept-language": "zh-CN,zh;q=0.9",
                "cache-control": "no-cache",
                "cookie": cookie,
                "pragma": "no-cache",
                "referer": "http://domain.test/",
                "sec-ch-ua-mobile": "?0",
                "sec-fetch-dest": "document",
                "sec-fetch-mode": "navigate",
                "sec-fetch-site": "cross-site",
                "sec-fetch-user": "?1",
                "upgrade-insecure-requests": "1",
                "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
            }
            resp = requests.get(url,headers=headers,timeout=10)
            return resp.text
        else:
            headers = {
                'Accept': 'application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding': 'gzip, deflate',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Cache-Control': 'no-cache',
                'Connection': 'keep-alive',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Cookie': cookie,
                'Pragma': 'no-cache',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36',
                'X-Requested-With': 'XMLHttpRequest'
            }
            resp = requests.post(url, headers=headers, data=data, timeout=10).json()
            if resp['code'] == -401:
                return None
            if resp['code'] != 1:
                return None
            return resp
    except Exception as e:
        return request_handler(url)

#获取库存和销量
def get_store_kc_sales(store_id):
    url = f'http://{store_id}.jm.cn'
    #         url = f'https://www.juming.com/{store_id}/'
    html = request_handler(url)
    e = etree.HTML(html)
    try:
        sales = e.xpath('//p[@class="mai-xy"]/a/text()')[0]
    except Exception as error:
        # print(error)
        sales = 0
    try:  # 库存
        kucun = e.xpath('//div[@class="cha-list-title"]/strong/text()')[0]
    except Exception:
        kucun = 0
    return sales,kucun


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

    url = 'http://7a08c112cda6a063.juming.com:9696/ykj/get_list'

    resp = request_handler(url,method='post',data=resp_data)
    if resp == None:
        return None

    e = etree.HTML(resp['html'])
    all_data = e.xpath('//form[@id="listform"]/table//tr')

    if len(all_data) <= 2:
        return True
    conn = pymysql.connect(**MYSQL_CONF)
    cur = conn.cursor()
    for index, data in enumerate(all_data[::-1]):
        try:
            ym_dict = {}
            ym_dict['ym'] = data.xpath('.//a//text()')[0]
            ym_dict['len'] = len(ym_dict['ym'].split('.')[0])
            ym_dict['jj'] = ''.join(data.xpath('.//span[@class="xtjj"]//text()'))
            ym_dict['mj_jj'] = ''.join(data.xpath('.//span[@class="mrjj gray"]//text()'))
            ym_dict['store_id_hide'] = ''.join(data.xpath('.//td[@class="gray"]//text()'))
            ym_dict['fixture_date'] = ''.join(data.xpath('.//td')[4].xpath('.//text()'))
            ym_dict['price'] = data.xpath('.//td')[5].xpath('.//text()')[0]
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


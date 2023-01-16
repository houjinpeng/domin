import datetime
import time
import difflib
import requests
import json

from dbutils.pooled_db import PooledDB
from conf.config import *

db_pool = PooledDB(**mysql_pool_conf)
conn = db_pool.connection()
cur = conn.cursor()

cur.execute("select * from ym_system_config where `name`='ip'")
ip_data = cur.fetchone()
ip = ip_data['value']
cur.close()
conn.close()



class GetHistory():
    def get_token(self, domain_list):
        domain_token = []
        url = f'http://127.0.0.1:5001/get_token'
        r = requests.get(url)
        token = json.loads(r.text)
        headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Cache-Control': 'no-cache',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Host': '47.56.160.68:81',
            'Pragma': 'no-cache',
            'Proxy-Connection': 'keep-alive',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
            'Origin': 'http://47.56.160.68:81',
            'Referer': 'http://47.56.160.68:81/piliang/',
            'X-Requested-With': 'XMLHttpRequest',
        }

        api = "http://47.56.160.68:81/api.php?sckey=y"
        data = {"ym": "\n".join(domain_list),
                "authenticate": token["auth"],
                "token": token['token'],
                "sessionid": token['session']
                }
        try:
            res = requests.post(url=api, data=data, headers=headers)
            res = res.json()
            if res['code'] != 1:

                print("错误: ", res['msg'])

                return self.get_token(domain_list)

            for item in res['data']:
                domain_token.append(item)
            return domain_token
        except Exception as e:
            print("[209]", e)
            return self.get_token(domain_list)

    def get_history(self,domain,count=0):
        try:

            headers = {
                'Accept': '*/*',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Proxy-Connection': 'keep-alive',
                'Pragma': 'no-cache',
                'Cache-Control': 'no-cache',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Origin': 'http://47.56.160.68:81',
                'Host': '47.56.160.68:81',
            }

            data = {
                'ym': domain['ym'],
                'xq': 'y',
                'page': '1',
                'limit': '20',
                'token':domain['token'],
                'group': '1',
                'nian': ''
            }
            response_detail = requests.post('http://47.56.160.68:10247/api.php', data=data, verify=False,headers=headers, timeout=10)
            r = response_detail.json()

            results = {
                "count": r.get('count'),
                "data":r.get('data'),
                "code": r.get('code'),
                "msg": r.get('msg'),
            }

            # print(results)
            return results
        except Exception as e:
            print(e)
            if count >5:
                return False
            return self.get_history(domain,count+1)

    def get_age(self,domain):
        headers = {
            'Accept': '*/*',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Proxy-Connection': 'keep-alive',
            'Pragma': 'no-cache',
            'Cache-Control': 'no-cache',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin': 'http://47.56.160.68:81',
            'Host': '47.56.160.68:81',
        }
        data = {
            'ym': domain['ym'],
            'token': domain['token'],
            'qg': ''
        }
        try:
            response_detail = requests.post('http://47.56.160.68:10247/api.php', data=data, verify=False,
                                            headers=headers, timeout=3)
            results = response_detail.json()

            return results
        except Exception as e:
            return self.get_age(domain)

    #获取中文标题数量
    def get_zh_title_num(self, history_data):
        '''
        :param history_data: json 历史
        :return: 返回中文标题数量
        '''
        num = 0
        try:
            for data in history_data['data']:
                if data['yy'] == '中文':
                    num += 1
            return num
        except Exception as e:
            return 0

    #获取五年内建站次数
    def get_five_year_num(self, history_data):
        '''
        :param history_data: 历史json
        :return:  返回五年内建站次数
        '''
        try:
            now_year = datetime.datetime.now().year
            num = 0
            for data in history_data['data']:
                year = int(data['timestamp'][:4])
                if now_year - 5 <= year:
                    # if data['yy'] != '中文':
                    #     continue
                    num += 1
            return num
        except Exception as e:
            return 0

    #获取最长连续存档时间
    def get_lianxu_cundang_time(self, history_data, year_num=0):
        '''
        获取连续年份时间
        :param history_data: 历史json
        :param year_num: 区间num
        :return: 最大连续时间
        '''
        num = 0
        old_year = 0
        max_lianxu_years = 0
        now_year = datetime.datetime.now().year
        try:
            for data in history_data['data']:
                year = int(data['timestamp'][:4])
                if year_num != 0:
                    if now_year - year_num > year:
                        continue

                if old_year == 0:
                    # if is_comp_english == '否':
                    # if data['yy'] != '中文' or data['bt'] == '':
                    #     num = 0
                    #     continue
                    num += 1
                    max_lianxu_years += 1

                if year + 1 == old_year:
                    # if is_comp_english == '否':
                    # if data['yy'] != '中文' or data['bt'] == '':
                    #     num = 0
                    #     continue

                    num += 1
                    if num > max_lianxu_years:
                        max_lianxu_years += 1

                else:
                    # if is_comp_english == '否':
                    # if data['yy'] != '中文' or data['bt'] == '':
                    #     num = 0
                    #     continue
                    num = 1

                old_year = year
            return max_lianxu_years
        except Exception as e:
            return 0

    #获取统一度
    def get_tongyidu(self, history_data):
        num = 1
        try:
            if history_data['data'] == None:
                return 0
            xiangsidu = 0
            for i in range(len(history_data['data'])):
                for j in range(i + 1, len(history_data['data'])):
                    num += 1
                    xiangsidu += difflib.SequenceMatcher(None, history_data['data'][i],
                                                         history_data['data'][j]).quick_ratio()

            xiangsidu = int(xiangsidu * 100 / num)
            return int(xiangsidu)
        except Exception as e:
            return 0

'''
ym: baidu.com
xq: y
page: 1
limit: 20
token: cd837
group: 1
nian: 
'''

if __name__ == '__main__':
    ds = ['77ck.com','shining-stars.org']
    h = GetHistory()
    for domain in ds:
        ls = h.get_token(ds)
        for do in ls:
            result = h.get_history({'ym':do['ym'],'token':do['token']})

            five = h.get_five_year_num(result)
            tongyidu = h.get_tongyidu(result)

            print(five,tongyidu)

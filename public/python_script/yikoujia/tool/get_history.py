import time

import requests
import json
import redis
redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)
class GetHistory():
    def get_token(self, domain_list):
        domain_token = []
        url = 'http://127.0.0.1:5001/get_token'
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

    def get_history(self,domain):
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
            response_detail = requests.post('http://47.56.160.68:10015/api.php', data=data, verify=False,
                                                headers=headers, timeout=3)
            r = response_detail.json()

            results = {
                "count": r.get('count'),
                "data":r.get('data'),
                "code": r.get('code'),
                "msg": r.get('msg'),
            }

            # print(results)
            return results
        except:
            return False

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
            response_detail = requests.post('http://47.56.160.68:10280/api.php', data=data, verify=False,
                                            headers=headers, timeout=3)
            results = response_detail.json()

            return results
        except Exception as e:
            return self.get_age(domain)


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
    ds = ['baidu1.com', 'baidu2.com', 'baidu3.com', 'baidu4.com']
    # for domain in ['baidu.com','baidu.com','baidu.com','baidu.com']:
    # get_history(ds)

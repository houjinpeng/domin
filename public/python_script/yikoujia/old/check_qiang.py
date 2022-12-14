import time
import re
import requests
from dbutils.pooled_db import PooledDB
import pymysql
from tool.longin import Login
from copy import deepcopy

mysql_pool_conf = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '123456',
    'db': 'domain',
    'creator': pymysql,
    'cursorclass': pymysql.cursors.DictCursor,
}

db_pool = PooledDB(**mysql_pool_conf)
conn = db_pool.connection()
cur = conn.cursor()
cur.execute("select * from ym_domain_config")
data = cur.fetchone()
username = data['username']
password = data['password']
cur.close()
conn.close()
session, msg, cookie_str = Login().login(username, password)

class Qiang():
    def __init__(self):
        self.url = 'https://www.juming.com/hao/'
        self.key = ''
        self.s = deepcopy(session)

    def get_cookie(self):
        conn = db_pool.connection()
        cur = conn.cursor()
        cur.execute("select * from ym_domain_config")
        data = cur.fetchone()
        username = data['username']
        password = data['password']
        cookie = data['cookie']
        cur.close()
        conn.close()
        #如果cookie为空 重新登陆
        if cookie == '':
            session,msg,cookie = Login().login(username,password)
            self.set_cookie(cookie)
            self.s = session
        return cookie

    def set_cookie(self,cookie):
        conn = db_pool.connection()
        cur = conn.cursor()
        cur.execute("update ym_domain_config set cookie='%s'"%(cookie) )
        conn.commit()
        cur.close()
        conn.close()


    def request_handler(self, url,count=0):
        try:

            headers = {
                # "cookie":self.get_cookie() ,
                "accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                "accept-encoding": "gzip, deflate, br",
                "accept-language": "zh-CN,zh;q=0.9",
                "cache-control": "no-cache",
                "pragma": "no-cache",
                "sec-ch-ua": "\"Chromium\";v=\"106\", \"Google Chrome\";v=\"106\", \"Not;A=Brand\";v=\"99\"",
                "sec-ch-ua-mobile": "?0",
                "sec-ch-ua-platform": "\"Windows\"",
                "sec-fetch-dest": "document",
                "sec-fetch-mode": "navigate",
                "sec-fetch-site": "same-origin",
                "sec-fetch-user": "?1",
                "upgrade-insecure-requests": "1",
                "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
            }
            # resp = requests.get(url,headers=headers,timeout=7,proxies=dt_proxies)
            # resp = requests.get(url,headers=headers,timeout=7)
            resp = self.s.get(url,headers=headers,timeout=7)
            return resp
        except Exception as e:
            if count >5:
                return None
            print(f'获取被墙信息失败 {e}')
            time.sleep(2)
            return self.request_handler(url,count+1)


    def verify_code(self,domain):
        try:
            url = 'https://www.juming.com/hao/'+domain

            token = requests.get(f'http://127.0.0.1:5001/get_token').json()

            data = {
                'token':token['token'],
                'sid':token['session'],
                'sig':token["auth"],
            }
            # cookie = self.get_cookie()
            headers = {
                # "cookie": cookie,
                "accept": "application/json, text/javascript, */*; q=0.01",
                "accept-encoding": "gzip, deflate, br",
                "accept-language": "zh-CN,zh;q=0.9",
                "cache-control": "no-cache",
                "pragma": "no-cache",
                "sec-ch-ua": "\"Chromium\";v=\"106\", \"Google Chrome\";v=\"106\", \"Not;A=Brand\";v=\"99\"",
                "sec-ch-ua-mobile": "?0",
                "sec-ch-ua-platform": "\"Windows\"",
                "sec-fetch-dest": "document",
                "sec-fetch-mode": "navigate",
                "sec-fetch-site": "same-origin",
                "sec-fetch-user": "?1",
                "upgrade-insecure-requests": "1",
                "origin":'https://www.juming.com',
                "referer":url,
                "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
            }
            # r = requests.post(url,data=data,headers=headers,timeout=3,proxies=dt_proxies)
            r = self.s.post(url,data=data,headers=headers,timeout=3)
            if r.json()['code'] == 1:
                return True
                # ct = r.cookies._cookies['www.juming.com']['/']['ct'].value
                # cookie = f'{cookie.split(";")[0]};ct={ct}'
                # self.set_cookie(cookie)
            else:
                return self.verify_code(domain)

        except Exception as e:
            time.sleep(2)
            print(f'解除验证码失败：{e}')
            return self.verify_code(domain)


    def get_token(self,domain):
        url = 'https://www.juming.com/hao/' + domain
        resp = self.request_handler(url)

        if '抱歉，此次操作需要完成下方验证后方可继续' in resp.text:
            r = self.verify_code(domain)
            return self.get_token(domain)
        self.key = re.findall("key='(.*?)'", resp.text)[0]

    #检查被墙
    def get_qiang_data(self, domain):
        if self.key == '':
            self.get_token(domain)
            return self.get_qiang_data(domain)
        qiang_url = f'https://www.juming.com/hao/cha_d?do=qiang&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_qiang_data(domain)

        return resp_data.json()

    #微信检测
    def get_wx_data(self, domain):
        if self.key == '':
            self.get_token(domain)
            return self.get_wx_data(domain)
        qiang_url = f'https://www.juming.com/hao/cha_d?do=weixin&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_wx_data(domain)

        return resp_data.json()

    #qq检查
    def get_qq_data(self, domain):
        if self.key == '':
            self.get_token(domain)
            return self.get_qq_data(domain)
        qiang_url = f'https://www.juming.com/hao/cha_d?do=qqjc&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_qq_data(domain)

        return resp_data.json()

    #备案黑名单
    def get_beian_hmd_data(self, domain):
        if self.key =='':
            self.get_token(domain)
            return self.get_beian_hmd_data(domain)
        qiang_url = f'https://www.juming.com/hao/cha_d?do=beian_hmd&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_beian_hmd_data(domain)

        return resp_data.json()

    #检测是否有建站记录
    def get_beian_data(self,domain):
        if self.key == '':
            self.get_token(domain)
            return self.get_beian_data(domain)
        qiang_url = f'https://www.juming.com/hao/cha_d?do=beian&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None

        #请求异常 重新登陆
        elif resp_data.json()['code'] == -1:
            self.set_cookie('')
            self.get_token(domain)
            return self.get_beian_data(domain)

        return resp_data.json()

if __name__ == '__main__':
    q = Qiang()

    ym_list = ['nihao.com','baidu.com','maiyuan.com','jding.com','haha.com']
    for ym in ym_list:
        # print(ym)
        print(q.get_qiang_data(ym))
        print(q.get_wx_data(ym))
        print(q.get_qq_data(ym))
        print(q.get_beian_hmd_data(ym))
        print(q.get_beian_data(ym))

        print('=='*20)


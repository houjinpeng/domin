# @Time : 2021/5/15 16:18
# @Author : HH
# @File : longin.py
# @Software: PyCharm
# @explain:
import hashlib
import os
import time
import re
import requests
import json
import configparser

config = configparser.ConfigParser()
logFile = r"./conf/setting.cfg"
config.read(logFile, encoding="utf-8")


class Login():
    def __init__(self):
        self.login_url = 'https://www.juming.com/user_zh/p_login'
        self.s = requests.session()

        self.headers = {
            'accept': 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding': 'gzip, deflate, br',
            'accept-language': 'zh-CN,zh;q=0.9',
            'cache-control': 'no-cache',
            'content-type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'origin': 'https://www.juming.com',
            'pragma': 'no-cache',
            'referer': 'https://www.juming.com/',
            'sec-ch-ua': '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Windows"',
            'sec-fetch-dest': 'empty',
            'sec-fetch-mode': 'cors',
            'sec-fetch-site': 'same-origin',
            'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'x-requested-with': 'XMLHttpRequest',
            # 'Cookie': 'PHPSESSID=5uq2p1ei8p83alf2kldor1ap1v'
        }
        self.headers1 = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Encoding': "gzip, deflate",
            'Accept-Language': "zh-CN,zh;q=0.9",
            'Connection': "keep-alive",
            'Content-Type': "application/x-www-form-urlencoded; charset=UTF-8",
            'Upgrade-Insecure-Requests': '1',
            'Host': 'old.juming.com',
            'Pragma': 'no-cache',
            'User-Agent': "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.90 Safari/537.36",
        }

    def login(self, username, password):
        try:
            m = hashlib.md5()
            m1 = hashlib.md5()
            m.update(f'[jiami{password}mima]'.encode())
            m1.update((m.hexdigest()[0:19]).encode())
            password_md5 = m1.hexdigest()[0:19]
            try:
                token_dict = requests.get(f'http://127.0.0.1:5001/get_token').json()
                token = token_dict['token']
                sid = token_dict['session']
                sig = token_dict['auth']

            except Exception as error:
                time.sleep(2)
                print('请更新token池')
                return self.login(username, password)

            data = f'token={token}&sid={sid}&sig={sig}&re_mm={password_md5}&re_yx={username}&fs=tl'
            r = self.s.post(self.login_url, data=data, headers=self.headers).json()

            print(r)
            if '登陆成功' in r['msg']:
                print('登录成功 获取cookie中')
                cookie = 'PHPSESSID=' + self.s.cookies._cookies['www.juming.com']['/']['PHPSESSID'].value
                return self.s, r['msg'], cookie
            else:
                time.sleep(1)
                return self.s, r['msg'], ''
        except Exception as error:
            print(f'登陆错误  重新登陆 {error}')
            return self.login(username,password)
if __name__ == '__main__':
    Login().login('104038','qq123123')
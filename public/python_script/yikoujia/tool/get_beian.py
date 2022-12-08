import faulthandler
faulthandler.enable()
import time
import base64
from io import BytesIO
import cv2
import requests
import json
import numpy as np
import hashlib
import threading, queue
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)
import redis

redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)


class BeiAn():
    def __init__(self):
        self.url = 'https://hlwicpfwc.miit.gov.cn/icpproject_query/api/icpAbbreviateInfo/queryByCondition'
        self.set_proxies()
        self.token = ''


    def set_proxies(self):

        ip = redis_cli.rpop('beian_ip')
        if ip == None:
            print('备案没有ip可用啦 快快ip安排~~~~~')
            time.sleep(5)
            return self.set_proxies()

        self.proxies = {
            'http': f'http://{ip.decode()}',
            'https': f'http://{ip.decode()}',
        }
        #print(f'域名：{self.domain}更换代理 {self.proxies}')

    def request_handler(self,url,data,headers,type='data'):
        try:
            if type == 'data':
                r = self.s.post(url, headers=headers, verify=False, data=data, proxies=self.proxies,timeout=8)
            else:
                r = self.s.post(url, headers=headers, verify=False, json=data, proxies=self.proxies,timeout=8)
            try:
                data = json.loads(r.text)
            except Exception as e:
                return None
            if data['success'] == False:
                return None
            return r
        except Exception as e:
            return None

    def get_distance(self, fg, bg):
        """
        计算滑动距离
        """
        target = cv2.imdecode(np.asarray(bytearray(fg.read()), dtype=np.uint8), 0)
        template = cv2.imdecode(np.asarray(bytearray(bg.read()), dtype=np.uint8), 0)
        result = cv2.matchTemplate(target, template, cv2.TM_CCORR_NORMED)
        _, distance = np.unravel_index(result.argmax(), result.shape)
        return distance

    def get_cookie(self):
        url = "https://beian.miit.gov.cn/"
        headers = {
            # 'user-agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36'
        }
        try:
            response = self.s.get(url, proxies=self.proxies,headers=headers,timeout=8)
        except Exception as e:
            self.set_proxies()
            return self.get_cookie()
        return response

    def get_token(self):
        m = hashlib.md5()
        m.update(f'testtest{int(time.time() * 1000)}'.encode('utf-8'))
        auth_url = 'https://hlwicpfwc.miit.gov.cn/icpproject_query/api/auth'
        headers = {
            'Accept': "*/*",
            'Accept-Encoding': "gzip, deflate, br",
            'Accept-Language': "zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6",
            'Connection': "keep-alive",
            'Content-Length': "64",
            'cookie':'__jsluid_s=06ee83aa108ede7f9ba961531738304e',
            'Content-Type': "application/x-www-form-urlencoded; charset=UTF-8",
            'Host': "hlwicpfwc.miit.gov.cn",
            'Origin': "https://beian.miit.gov.cn",
            'Referer': "https://beian.miit.gov.cn/",
            'sec-ch-ua-mobile': "?0",
            'Sec-Fetch-Dest': "empty",
            'Sec-Fetch-Mode': "cors",
            'Sec-Fetch-Site': "same-site",
            'User-Agent': "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36 Edg/92.0.902.55",
            # 'User-Agent': UserAgent().chrome,
        }
        data = f'authKey={m.hexdigest()}&timeStamp={int((time.time() * 1000))}'
        r = self.request_handler(auth_url,data,headers)
        if r == None:
            self.set_proxies()
            # return self.beian_info(self.domain)
            return self.get_token()
        # logger.info('获取token成功  获取验证码图片 ')
        return r

    def get_img(self, token):
        # logger.info('获取验证码')
        img_url = 'https://hlwicpfwc.miit.gov.cn/icpproject_query/api/image/getCheckImage'
        headers = {
            'Accept': "application/json, text/plain, */*",
            'Accept-Encoding': "gzip, deflate, br",
            'Accept-Language': "zh-CN,zh;q=0.9",
            'Connection': "keep-alive",
            'Content-Length': "0",
            'Cookie': "__jsluid_s=5a0a7ae4dcb6eea5a1621a0fb51d8efe",
            'Host': "hlwicpfwc.miit.gov.cn",
            'Origin': "https://beian.miit.gov.cn",
            'Referer': "https://beian.miit.gov.cn/",
            'sec-ch-ua-mobile': "?0",
            'Sec-Fetch-Dest': "empty",
            'Sec-Fetch-Mode': "cors",
            'Sec-Fetch-Site': "same-site",
            'token': f"{token}",
            # 'User-Agent': UserAgent().chrome,
            'User-Agent': "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36",
        }
        # 获取验证码图片 并返回
        img_resp = self.request_handler(img_url,data='',headers=headers)
        if img_resp == None:
            self.set_proxies()
            return self.get_img(token)
        # logger.info("验证码获取成功  破解中···")
        img_data = json.loads(img_resp.text)
        big_img = img_data['params']['bigImage']
        fg = BytesIO(base64.b64decode(big_img))
        small_img = img_data['params']['smallImage']
        bg = BytesIO(base64.b64decode(small_img))
        uuid = img_data['params']['uuid']
        return fg, bg, uuid

    def check_img(self, token, uuid, distance):
        # 验证滑动验证码
        headers = {
            'Accept': "application/json, text/plain, */*",
            'Accept-Encoding': "gzip, deflate, br",
            'Accept-Language': "zh-CN,zh;q=0.9",
            'Connection': "keep-alive",
            'Content-Length': "60",
            'Content-Type': "application/json",
            'Cookie': "__jsluid_s=5a0a7ae4dcb6eea5a1621a0fb51d8efe",
            'Host': "hlwicpfwc.miit.gov.cn",
            'Origin': "https://beian.miit.gov.cn",
            'Referer': "https://beian.miit.gov.cn/",
            'sec-ch-ua-mobile': "?0",
            'Sec-Fetch-Dest': "empty",
            'Sec-Fetch-Mode': "cors",
            'Sec-Fetch-Site': "same-site",
            'token': token,
            'User-Agent': "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36",
        }
        check_url = 'https://hlwicpfwc.miit.gov.cn/icpproject_query/api/image/checkImage'

        data = {"key": uuid, "value": f"{distance}"}
        r = self.request_handler(check_url,data,headers,type='json')
        if r == None:
            return None
        # logger.info('破解成功')
        result = json.loads(r.text)
        if result['success'] == True:
            return result['params']
        else:
            return None

    def get_detail_data(self, param, token, uuid,domain):
        detail_url = 'https://hlwicpfwc.miit.gov.cn/icpproject_query/api/icpAbbreviateInfo/queryByCondition'
        headers = {
            'Accept': "application/json, text/plain, */*",
            'Accept-Encoding': "gzip, deflate, br",
            'Accept-Language': "zh-CN,zh;q=0.9",
            'Connection': "keep-alive",
            'Content-Length': "51",
            'Content-Type': "application/json",
            'Cookie': "__jsluid_s=5a0a7ae4dcb6eea5a1621a0fb51d8efe",
            'Host': "hlwicpfwc.miit.gov.cn",
            'Origin': "https://beian.miit.gov.cn",
            'Referer': "https://beian.miit.gov.cn/",
            'sec-ch-ua-mobile': "?0",
            'Sec-Fetch-Dest': "empty",
            'Sec-Fetch-Mode': "cors",
            'Sec-Fetch-Site': "same-site",
            'sign': param,
            'token': token,
            'User-Agent': "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.107 Safari/537.36",
            'uuid': uuid,
        }
        data_parm = {"pageNum": "", "pageSize": "", "unitName": domain}
        r = self.request_handler(detail_url,data_parm,headers,type='json')
        if r == None:
            return None
        data = json.loads(r.text)
        return data

    def beian_info(self,domain):
        self.s = requests.session()
        self.get_cookie()
        r = self.get_token()
        token = json.loads(r.text)['params']['bussiness']
        fg, bg, uuid = self.get_img(token)
        distance = self.get_distance(fg, bg)
        param = self.check_img(token, uuid, distance)
        if param == None:
            return None
        result = self.get_detail_data(param, token, uuid,domain)
        return result


if __name__ == '__main__':
    data = BeiAn().beian_info('sdfs123adssdsda.com')
    print(data)


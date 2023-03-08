import time
import base64
from io import BytesIO
import cv2
import requests
import numpy as np
import hashlib
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)
import sys
from tool import *

class BeiAn():
    '''
    获取icp备案   定时获取token 5分钟的过期时间  4分钟更新一次token
    '''
    def __init__(self):
        self.url = 'https://hlwicpfwc.miit.gov.cn/icpproject_query/api/icpAbbreviateInfo/queryByCondition'
        self.set_proxies()
        self.can = False
        self.token = ''
        self.param = ''
        self.uuid = ''
        #启动定时任务获取token 4分钟执行一次


    def set_proxies(self):
        url = 'http://222.186.42.15:7772/SML.aspx?action=GetIPAPI&OrderNumber=a2b676c40f8428c7de191c831cbcda44&poolIndex=1676099678&Split=&Address=&Whitelist=&isp=&qty=1'
        try:
            r = requests.get(url, timeout=1)
            if '尝试修改提取筛选参数' in r.text or '用户异常' in r.text:
                time.sleep(2)
                return self.set_proxies()
            ip_list = r.text.split('\r\n')
            for ip in ip_list:
                if ip.strip() == '': continue
                self.proxies = {
                    'http': f'http://{ip}',
                    'https': f'http://{ip}',
                }
                break
        except Exception as e:
            time.sleep(1)

            return self.set_proxies()


    def request_handler(self,url,data,headers,type='data',is_user_proxies = True):
        try:
            if type == 'data':
                r = self.s.post(url, headers=headers, verify=False, data=data, proxies=self.proxies,timeout=5)
            else:
                r = self.s.post(url, headers=headers, verify=False, json=data, proxies=self.proxies,timeout=5)

            if ('攻击行为' in r.text and '如果您是网站管理员' in r.text) or '您访问频率太高，请稍候再试。' in r.text:
                is_user_proxies = True
                self.set_proxies()
                return self.request_handler(url, data, headers, type, is_user_proxies=is_user_proxies)
            try:
                data = json.loads(r.text)
            except Exception as e:
                return None


            if data['success'] == False and '频次过高' in data['msg']:
                if is_user_proxies == False:
                    is_user_proxies = True
                self.set_proxies()
                return self.request_handler(url, data, headers, type,is_user_proxies=is_user_proxies)
            elif data['success'] == False :
                return None
            return r
        except Exception as e:
            if 'timeout' in str(e):
                self.set_proxies()
                is_user_proxies = True
                return self. request_handler(url,data,headers,type,is_user_proxies=is_user_proxies)
            self.set_proxies()
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

    def get_cookie(self,count=0):
        url = "https://beian.miit.gov.cn/"
        headers = {
            # 'user-agent':'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36'
        }
        try:
            response = self.s.get(url, proxies=self.proxies,headers=headers,timeout=4)
        except Exception as e:
            if count >10:
                return None
            self.set_proxies()
            return self.get_cookie(count+1)
        return response

    def get_token(self,count=0):
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
            if count>10:
                return None
            self.set_proxies()
            # return self.beian_info(self.domain)
            return self.get_token(count+1)
        # logger.info('获取token成功  获取验证码图片 ')
        return r

    def get_img(self, token,count=0):
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
        img_resp = self.request_handler(img_url,data='',headers=headers,is_user_proxies=False)
        if img_resp == None:
            if count>10:
                return None,None,None
            # self.set_proxies()
            return self.get_img(token,count+1)
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
        r = self.request_handler(check_url,data,headers,type='json',is_user_proxies=False)
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
        if self.get_cookie() == None:
            self.set_proxies()
            return self.beian_info(domain)

        r = self.get_token()
        if r == None:
            self.set_proxies()
            return self.beian_info(domain)
        token = json.loads(r.text)['params']['bussiness']


        fg, bg, uuid = self.get_img(token)
        if fg == None:
            return None
        distance = self.get_distance(fg, bg)
        param = self.check_img(token, uuid, distance)
        if param == None:
            return None
        result = self.get_detail_data(param, token, uuid,domain)



        return result




if __name__ == '__main__':
    ym = sys.argv[1]
    c = BeiAn()
    res = c.beian_info(ym)
    print(json.dumps(res))


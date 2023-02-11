import threading
import random
import requests,queue
import time
import re
from urllib.parse import urlparse
from lxml import etree
proxy_queue = queue.Queue()

def get_proxy():
    while True:
        if proxy_queue.qsize()> 100:
            time.sleep(2)
            continue
        url = 'http://222.186.42.15:7772/SML.aspx?action=GetIPAPI&OrderNumber=a2b676c40f8428c7de191c831cbcda44&poolIndex=1676099678&Split=&Address=&Whitelist=&isp=&qty=20'
        try:
            r = requests.get(url, timeout=3)
            if '尝试修改提取筛选参数' in r.text or '用户异常' in r.text:
                print('尝试修改提取筛选参数')
                time.sleep(20)
                continue
            ip_list = r.text.split('\r\n')
            for ip in ip_list:
                if ip.strip() == '': continue
                proxy_queue.put(ip)
        except Exception as e:
            time.sleep(1)
            print(e)
            continue

threading.Thread(target=get_proxy).start()





class AiZhan():
    def __init__(self,baidu_pr,yidong_pr,sm_pr,so_pr,sogou_pr):

        self.s = requests.session()
        self.baidu_pr = baidu_pr
        self.yidong_pr = yidong_pr
        self.sm_pr = sm_pr
        self.so_pr = so_pr
        self.sogou_pr = sogou_pr
        if self.baidu_pr[1] == '0':
            self.baidu_pr[1] = 999999
        if self.yidong_pr[1] == '0':
            self.yidong_pr[1] = 999999
        if self.sm_pr[1] == '0':
            self.sm_pr[1] = 999999
        if self.so_pr[1] == '0':
            self.so_pr[1] = 999999
        if self.sogou_pr[1] == '0':
            self.sogou_pr[1] = 999999


    #设置代理
    def get_proxy(self):
        try:
            ip = proxy_queue.get()

            proxies = {
                'http': f'http://{ip}',
                'https': f'http://{ip}'
            }
            self.s = requests.session()
            self.proxies = proxies
            self.s.proxies.update(proxies)

            return proxies
        except Exception as e:
            time.sleep(2)
            return None

    #发送请求
    def requests_handler(self, ym,count=0):
        url = f"https://www.aizhan.com/cha/{ym}/"

        headers = {
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Sec-Fetch-Site': 'none',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-User': '?1',
            'Sec-Fetch-Dest': 'document',
            'Accept-Language': 'zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6',
        }

        try:
            response = self.s.get(url, headers=headers, timeout=10)
            response.encoding = 'utf-8'
            # # 判断验证码
            if response.status_code > 200:
                if count > 10:
                    return None
                proxies = self.get_proxy()
                return self.requests_handler(ym, count=count + 1)
            return response
        except Exception as e:
            if count > 10 :
                return None
            proxies = self.get_proxy()
            return self.requests_handler(ym,count=count+1)

    def get_info(self,domain):

        r = self.requests_handler(domain)
        if r == None:
            return None

        html = etree.HTML(r.text)
        data ={}
        try:
           data['baidu_pr'] = str(html.xpath('//a[@id="baidurank_br"]/img/@alt')[0])
           if data['baidu_pr'] == 'n':
               data['baidu_pr'] = '0'
        except Exception:
            data['baidu_pr'] = '0'
        try:
            # 移动权重
            data['yidong_pr'] = str(html.xpath('//a[@id="baidurank_mbr"]/img/@alt')[0])
            if data['yidong_pr'] == 'n':
                data['yidong_pr'] = '0'
        except Exception:
            data['yidong_pr'] = '0'
        try:
            # 360权重
            data['so_pr'] = str(html.xpath('//a[@id="360_pr"]/img/@alt')[0])
            if data['so_pr'] == 'n':
                data['so_pr'] = '0'
        except Exception:
            data['so_pr'] = '0'
        try:
            # 神马权重
            data['shenma_pr'] = str(html.xpath('//a[@id="sm_pr"]/img/@alt')[0])
            if data['shenma_pr'] == 'n':
                data['shenma_pr'] = '0'
        except Exception:
           data['shenma_pr'] = '0'
        try:
            # 搜狗权重
            data['sogou_pr'] =str( html.xpath('//a[@id="sogou_pr"]/img/@alt')[0])
            if data['sogou_pr'] == 'n':
                data['sogou_pr'] = '0'
        except Exception:
           data['sogou_pr']  = '0'
        # data['html'] = r.text
        return data

    def check_aizhan(self,data):
        if self.baidu_pr != ['0','0']:
            if int(self.baidu_pr[0]) > int(data['baidu_pr']) or int(self.baidu_pr[1]) < int(data['baidu_pr']) :
                return f'爱站百度权重不符合要求 百度权重为:{data["baidu_pr"]}'

        if self.yidong_pr != ['0', '0']:
            if int(self.yidong_pr[0]) > int(data['yidong_pr']) or int(self.yidong_pr[1]) < int(data['baidu_pr']):
                return f'爱站百度权重不符合要求 移动权重为:{data["yidong_pr"]}'
        if self.sm_pr != ['0', '0']:
            if int(self.sm_pr[0]) > int(data['shenma_pr']) or int(self.sm_pr[1]) < int(data['baidu_pr']):
                return f'爱站百度权重不符合要求 神马权重为:{data["shenma_pr"]}'
        if self.so_pr != ['0', '0']:
            if int(self.so_pr[0]) > int(data['so_pr']) or int(self.so_pr[1]) < int(data['baidu_pr']):
                return f'爱站百度权重不符合要求 360权重为:{data["so_pr"]}'
        if self.sogou_pr != ['0', '0']:
            if int(self.sogou_pr[0]) > int(data['sogou_pr']) or int(self.sogou_pr[1]) < int(data['baidu_pr']):
                return f'爱站百度权重不符合要求 搜狗权重为:{data["sogou_pr"]}'
        return True


if __name__ == '__main__':
    o = AiZhan(['1','0'],['1','0'],['1','0'],['1','0'],['1','0'])
    res = o.get_info('baidu.com')
    print(res)
    print(o.check_aizhan(res))
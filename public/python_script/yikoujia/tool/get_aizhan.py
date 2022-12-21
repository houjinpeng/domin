import threading
import random
import requests,queue
import time
import re
from urllib.parse import urlparse
from lxml import etree
proxy_queue = queue.Queue()

import redis

redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)


class AiZhan():
    def __init__(self):
        '''
        :param record_num:[0,0]
        :param fengxian: 是 否
        :param kuaizhao_time: 泛 首页
        '''
        self.s = requests.session()
        pass
        # self.record_num_min = int(record_num[0])
        # self.record_num_max = 999999999 if int(record_num[1]) == 0 else int(record_num[1])
        # self.fengxian = fengxian
        # self.kuaizhao_time = kuaizhao_time
        # self.s = requests.session()

    #获取url连接
    def get_domain_url(self, so_url, count=0):
        try:
            headers = {
                'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
            }
            if 'www.so.com' in so_url:
                # r = requests.get(baidu_url,headers=headers,verify=False)
                r = requests.get(so_url, headers=headers, verify=False, allow_redirects=False, timeout=10)
                url = re.findall('URL=\'(.*?)"', r.text)[0][:-1]
            else:
                return so_url
            return url
        except Exception as e:
            if count < 5:
                return self.get_domain_url(so_url, count + 1)
            return ''

    #设置代理
    def get_proxy(self):
        try:
            ip = redis_cli.rpop('baidu_ip')
            if ip == None:
                print('爱站 没有ip可用啦 快快ip安排~~~~~')
                time.sleep(5)
                return self.get_proxy()
            proxies = {
                'http': f'http://{ip.decode()}',
                'https': f'http://{ip.decode()}'
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
        except Exception:
            data['baidu_pr'] = '0'
        try:
            # 移动权重
            data['yidong_pr'] = str(html.xpath('//a[@id="baidurank_mbr"]/img/@alt')[0])
        except Exception:
            data['yidong_pr'] = '0'
        try:
            # 360权重
            data['so_pr'] = str(html.xpath('//a[@id="360_pr"]/img/@alt')[0])
        except Exception:
            data['so_pr'] = '0'
        try:
            # 神马权重
            data['shenma_pr'] = str(html.xpath('//a[@id="sm_pr"]/img/@alt')[0])
        except Exception:
           data['shenma_pr'] = '0'
        try:
            # 搜狗权重
            data['sogou_pr'] =str( html.xpath('//a[@id="sogou_pr"]/img/@alt')[0])
        except Exception:
           data['sogou_pr']  = '0'
        # data['html'] = r.text
        return data

    def check_aizhan(self,html,domain):

        html = etree.HTML(html)
        try:
            count = re.findall('找到相关结果约(.*?)个', html)[0].replace(',', '')
        except Exception:
            count = '0'
        try:
            #判断收录数 最小值小于 实际过滤
            if self.record_num_min > int(count) or int(count) > self.record_num_max:
                return f'360 收录数不符合要求: 收录数为{count}'
        except Exception as e:
            pass
        #判断是否有风险
        if self.fengxian == '0':
            if '因部分结果可能无法正常访问或被恶意篡改、存在虚假诈骗等原因，已隐藏' in html:
                return '360 因部分结果可能无法正常访问或被恶意篡改、存在虚假诈骗等原因，已隐藏'

        # 判断url结构   1首页     2泛   3内页 0不判断
        all_result = html.xpath('//ul[@class="result"]/li')
        if self.kuaizhao_time == '0':
            return True

        elif self.kuaizhao_time == '1':
            for result in all_result:
                href = result.xpath('//p[@class="g-linkinfo"]/cite/a/@href')
                domain_url = self.get_domain_url(href[0])
                domain_1 = urlparse(domain_url).hostname
                if domain_1 == None:
                    continue

                if domain_1.split('.') == 0:
                    continue
                elif domain_1.split('.') == 2:
                    return True
                elif domain_1.split('.')[0] == 'www':
                    return True
            return '360 首页判断未通过'

        elif str(self.kuaizhao_time) == '2':
            for result in all_result:
                href = result.xpath('//p[@class="g-linkinfo"]/cite/a/@href')
                domain_url = self.get_domain_url(href[0])
                domain_1 = urlparse(domain_url).hostname
                if domain_1 == None:
                    continue
                if len(domain_1.split('.')) == 0:
                    continue
                elif domain_1.split('.') == 3 and domain_1.split('.')[0] != 'www':
                    return True
                elif domain in domain_1 and 'www' not in domain_1 and len(domain_1.split('.')) != 2 and 'm.' not in domain_1:
                    return True
            return '360 泛判断未通过'

        elif self.kuaizhao_time == '3':
            for result in all_result:
                href = result.xpath('//p[@class="g-linkinfo"]/cite/a/@href')
                domain_url = self.get_domain_url(href[0])
                domain = urlparse(domain_url)
                if domain.path != '':
                    return True
            return '360 内页判断未通过'

if __name__ == '__main__':

    res = AiZhan().get_info('mukherjeeudyog.com')
    print(res)
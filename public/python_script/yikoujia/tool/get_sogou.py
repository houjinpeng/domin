import requests
import re
from lxml import etree
import threading, queue
import time

import redis

redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)


class GetSougouRecord():

    def __init__(self):
        pass
        # ip = self.set_proxies()
        # self.proxies = {
        #     'http': f'http://{ip}',
        #     'https': f'http://{ip}'
        # }

    def set_proxies(self):
        ip = redis_cli.rpop('sogou_ip')
        if ip == None:
            print('备案没有ip可用啦 快快ip安排~~~~~')
            time.sleep(5)
            return self.set_proxies()

        self.proxies = {
            'http': f'http://{ip.decode()}',
            'https': f'http://{ip.decode()}'
        }
        return ip

    def request_hearders(self, url):
        try:

            proxies = {
                "http": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
                "https": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
            }
            headers = {
                'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.88 Safari/537.36'}
            # r = requests.get(url,headers=headers,timeout=5,proxies=self.proxies)
            r = requests.get(url, headers=headers, timeout=5, proxies=proxies)
            if '需要您协助验证' in r.text:
                # self.set_proxies()
                return self.request_hearders(url)
            return r
        except Exception as e:
            # self.set_proxies()
            return self.request_hearders(url)

    def check_sogou(self, domain, record_count, time_str):
        '''
        :param domain: 域名
        :param record_count: 收录数 [min,max]
        :param time_str: 快照时间
        :return:
        '''
        url = f'https://www.sogou.com/web?query=site%3A{domain}'
        r = self.request_hearders(url)
        e = etree.HTML(r.text)
        if time_str == '':
            is_kuaizhao = True
        else:
            is_kuaizhao = False
        try:

            # 查询收录数
            record = re.findall('搜狗已为您找到约(.*?)条相关结果', r.text)[0].replace(',', '')
            if record == '0':
                return '搜狗没有收录'
            # 查询
            all_domain = e.xpath('//div[contains(@class,"citeurl")]')
            fuhe_count = 0
            for domain_obj in all_domain:
                if domain in ''.join(domain_obj.xpath('.//text()')):
                    fuhe_count += 1
                    # 判断是否包好快照字符串
                    if time_str == '':
                        is_kuaizhao = True
                    else:
                        for t in time_str.split(','):
                            if t in ''.join(domain_obj.xpath('.//text()')):
                                is_kuaizhao = True

            if is_kuaizhao == False:
                return '搜狗快照不符合'

            if int(record_count[1]) == 0:
                record_count[1] = 99999999

            if int(record) < int(record_count[0]) or int(record) > int(record_count[1]):
                return f'搜狗 收录不符合 实际收录 {record}'

            return True
        except:
            return '搜狗检测错误'


if __name__ == '__main__':
    s = '>=50'
    tim_str = '小时,1天,2天'
    r = GetSougouRecord().check_sogou('aksqamu.com', s, tim_str)
    print(r)
    r = GetSougouRecord().check_sogou('worrywater.com', s, tim_str)
    print(r)

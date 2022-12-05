import threading
import random
import requests,queue
import time
import re
from urllib.parse import urlparse
from lxml import etree
proxy_queue = queue.Queue()
headersPool = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.103 Safari/537.36",
    "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36",
    "Mozilla/5.0 (Windows; U; MSIE 9.0; Windows NT 9.0; en-US)",
    "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 2.0.50727; Media Center PC 6.0)",
    "Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; .NET CLR 1.0.3705; .NET CLR 1.1.4322)",
    "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 5.2; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.2; .NET CLR 3.0.04506.30)",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN) AppleWebKit/523.15 (KHTML, like Gecko, Safari/419.3) Arora/0.3 (Change: 287 c9dfb30)",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2pre) Gecko/20070215 K-Ninja/2.1.1",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1; zh-CN; rv:1.9) Gecko/20080705 Firefox/3.0 Kapiko/3.0",
    "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.8) Gecko Fedora/1.9.0.8-1.fc10 Kazehakase/0.5.6",
    "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
    "Mozilla/5.0 (Windows; U; Windows NT 5.2) Gecko/2008070208 Firefox/3.0.1",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1) Gecko/20070309 Firefox/2.0.0.3",
    "Mozilla/5.0 (Windows; U; Windows NT 5.1) Gecko/20070803 Firefox/1.5.0.12",
    "Opera/9.27 (Windows NT 5.2; U; zh-cn)",
    "Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Version/3.1 Safari/525.13",
    "Mozilla/5.0 (Windows; U; Windows NT 5.2) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.2.149.27 ",
    "Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_6; en-US) AppleWebKit/530.9 (KHTML, like Gecko) Chrome/ Safari/530.9 ",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_0) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11",
    "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; 360SE)",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/27.0.1453.93 Chrome/27.0.1453.93 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36",
    "Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.94 Safari/537.36"]

import redis

redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)


class SoCom():
    def __init__(self,record_num,fengxian,kuaizhao_time):
        '''
        :param record_num:[0,0]
        :param fengxian: 是 否
        :param kuaizhao_time: 泛 首页
        '''
        self.record_num_min = int(record_num[0])
        self.record_num_max = 999999999 if int(record_num[1]) == 0 else int(record_num[1])
        self.fengxian = fengxian
        self.kuaizhao_time = kuaizhao_time
        self.s = requests.session()

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
            ip = redis_cli.rpop('so_ip')
            if ip == None:
                print('360 没有ip可用啦 快快ip安排~~~~~')
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
    def requests_handler(self, url1,count=0):
        url = f"https://www.so.com/s?q=site%3A{url1}"

        headers = {
            'Connection': 'keep-alive',
            'Upgrade-Insecure-Requests': '1',
            'User-Agent': random.choice(headersPool),
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
            if "请输入验证码以便正常访问" in response.text:
                # log.logger.info("有验证码...")
                if count > 10:
                    return None
                proxies = self.get_proxy()
                return self.requests_handler(url1, count=count + 1)
            elif response.status_code > 200:
                if count > 10:
                    return None
                proxies = self.get_proxy()
                return self.requests_handler(url1, count=count + 1)
            return response
        except Exception as e:
            if count > 10 :
                return None
            proxies = self.get_proxy()
            return self.requests_handler(url1,count=count+1)

    def get_info(self,domain):
        try:
            r = self.requests_handler(domain)
            html = etree.HTML(r.text)
            try:
                count = re.findall('找到相关结果约(.*?)个', r.text)[0].replace(',', '')
            except Exception:
                count = '0'
            return {'sl':int(count),'html':html}
        except Exception as error:
            return self.get_info(domain)

    def check_360(self,html,domain):

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
        if self.fengxian == '否':
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

    res = SoCom('>=2','否','2').check_360('azov-citi.com')
    print(res)
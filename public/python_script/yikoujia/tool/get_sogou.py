import requests
import re
from lxml import etree
import threading, queue
import time
from tool.get_min_gan_word import get_mingan_word
import redis
from urllib.parse import urlparse

redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)
words = get_mingan_word()


class GetSougouRecord():

    def __init__(self):
        self.set_proxies()
    #获取域名
    def extract_domain(self,ym_str):
        if '-' in ''.join(ym_str).lower().strip()[:10]:
            if ''.join(ym_str).lower().find('-')+1 == '':
                snapshot = ''.join(ym_str).lower().split('-')[1]
            else:
                snapshot = ''.join(ym_str).lower().split('/')[0].strip()

            if 'htt' in snapshot:
                snapshot = snapshot.split('/')[2].strip()
            else:
                snapshot = snapshot.split('/')[0].strip()
        else:
            snapshot = ''.join(ym_str).lower().split('/')[0].strip()

        return snapshot

    def set_proxies(self):
        ip = redis_cli.rpop('sogou_ip')
        if ip == None:
            print('搜狗没有ip可用啦 快快ip安排~~~~~')
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
            r = requests.get(url,headers=headers,timeout=5,proxies=self.proxies)
            # r = requests.get(url, headers=headers, timeout=5, proxies=proxies)
            if '需要您协助验证' in r.text:
                self.set_proxies()
                return self.request_hearders(url)
            return r
        except Exception as e:
            self.set_proxies()
            return self.request_hearders(url)

    def check_sogou(self, html, record_count, time_str,domain,sogou_is_com_word,jg='0'):
        '''
        :param html: 网页html
        :param record_count: 收录数 [min,max]
        :param time_str: 快照时间
        :param time_str: 域名
        :param sogou_is_com_word: 是否对比敏感词
        :return:
        '''
        url_list = []
        e = etree.HTML(html)
        if time_str == '':
            is_kuaizhao = True
        else:
            is_kuaizhao = False
        try:
            # 查询收录数
            record = re.findall('搜狗已为您找到约(.*?)条相关结果',html)[0].replace(',', '')
            if record == '0':
                return '搜狗没有收录'
            # 查询
            all_domain = e.xpath('//div[contains(@class,"citeurl")]')
            fuhe_count = 0

            url_list_obj = e.xpath('//div[contains(@class,"r-sech")]/@data-url')
            for url in url_list_obj:
                if domain in url:
                    url_list.append(url)

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

            #如果小于5 使用页面出现的收录
            if fuhe_count < 5:
                record = fuhe_count

            if is_kuaizhao == False:
                return '搜狗快照不符合'

            if int(record_count[1]) == 0:
                record_count[1] = 9999999999

            if int(record) < int(record_count[0]) or int(record) > int(record_count[1]):
                return f'搜狗 收录不符合 实际收录 {record}'

            #判断是否对比敏感词
            if sogou_is_com_word == '1':
                title_list = []
                all_div = e.xpath('//div[contains(@class,"results")]/div')
                for d in all_div:
                    try:
                        title_list.append(''.join(d.xpath('.///text()')))
                    except Exception as error:
                        continue
                if title_list == []: return '搜狗 没有找到标题 无法判断是否包含敏感词'
                for t in title_list:
                    for w in words:
                        if w in t:
                            return f'搜狗 包含敏感词：{w}'

            # 判断url结构   1首页     2泛   3内页 0不判断
            if jg == '1':
                for url in url_list:
                    domain = urlparse(url).hostname
                    if domain == None:
                        continue
                    if domain.split('.') == 0:
                        continue
                    elif domain.split('.') == 2:
                        return True
                    elif domain.split('.')[0] == 'www':
                        return True
                return '搜狗 首页判断未通过'

            elif jg == '2':
                for url in url_list:
                    domain_1 = urlparse(url).hostname
                    if domain_1 == None:
                        continue
                    if len(domain_1.split('.')) == 0:
                        continue
                    elif domain_1.split('.') == 3 and domain_1.split('.')[0] != 'www':
                        return True
                    elif domain in domain_1 and 'www' not in domain_1 and len(domain_1.split('.')) != 2 and 'm.' not in domain_1:
                        return True
                return '搜狗 泛判断未通过'

            elif jg == '3':
                for url in url_list:
                    domain = urlparse(url)
                    if domain.path != '':
                        return True
                return '搜狗 内页判断未通过'



            return True
        except Exception as error:
            return f'搜狗检测错误 {error}'

    def get_info(self,domain):
        url = f'https://www.sogou.com/web?query=site%3A{domain}'
        r = self.request_hearders(url)
        try:
            # 查询收录数
            try:
                record = re.findall('搜狗已为您找到约(.*?)条相关结果', r.text)[0].replace(',', '')
            except Exception as error:
                record = 0
            # 查询
            e = etree.HTML(r.text)
            all_domain = e.xpath('//div[contains(@class,"citeurl")]')
            fuhe_count = 0
            for domain_obj in all_domain:
                if domain in ''.join(domain_obj.xpath('.//text()')):
                    fuhe_count += 1

            if fuhe_count < 5:
                record = fuhe_count

            return {'sl':int(record),'html':r.text}
        except Exception as error:
            print(error)
            return self.get_info(domain)

if __name__ == '__main__':
    s = [0,0]
    tim_str = ''
    o = GetSougouRecord()
    # y = o.extract_domain('aaa.www.baidu.com')
    # print(y)
    data = o.get_info('baidu.com')
    r = o.check_sogou(data['html'],s,tim_str,domain='baidu.com',sogou_is_com_word='1',jg='3')
    # print(r)


import requests
import re
from lxml import etree
import threading, queue
import time
from tool.get_min_gan_word import get_mingan_word
from urllib.parse import urlparse

words = get_mingan_word()
proxy_queue = queue.Queue()
def get_proxy():
    while True:
        if proxy_queue.qsize()> 200:
            time.sleep(2)
            continue
        url = 'http://39.104.96.30:8888/SML.aspx?action=GetIPAPI&OrderNumber=98b90a0ef0fd11e6d054dcf38e343fe927999888&poolIndex=1628048006&poolnumber=0&cache=1&ExpectedIPtime=&Address=&cachetimems=0&Whitelist=&isp=&qty=20'
        try:
            r = requests.get(url, timeout=3)
            if '尝试修改提取筛选参数' in r.text or '用户异常' in r.text:
                print('尝试修改提取筛选参数')
                continue
            ip_list = r.text.split('\r\n')
            for ip in ip_list:
                if ip.strip() == '': continue
                proxy_queue.put(ip)
        except Exception as e:
            time.sleep(1)
            print(e)
            continue

# threading.Thread(target=get_proxy).start()

class GetSougouRecord():

    def __init__(self):
        pass
        # self.set_proxies()
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
        ip = proxy_queue.get()
        self.proxies = {
            'http': f'http://{ip}',
            'https': f'http://{ip}'
        }
        return ip

    def request_hearders(self, url):
        try:

            proxies = {
                "http": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
                "https": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
            }
            headers = {
                'Connection': 'keep-alive',
                'Pragma': 'no-cache',
                'Cache-Control': 'no-cache',
                'Upgrade-Insecure-Requests': '1',
                'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Host': 'www.sogou.com',
            }
            # r = requests.get(url,headers=headers,timeout=5,proxies=self.proxies)
            r = requests.get(url, headers=headers, timeout=5, proxies=proxies)
            if '需要您协助验证' in r.text:
                self.set_proxies()
                return self.request_hearders(url)
            return r
        except Exception as e:
            # self.set_proxies()
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
        domain = domain.lower()
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
            is_guo = False
            if jg == '1':
                for url in url_list:
                    host = urlparse(url).hostname
                    if host == None:
                        continue

                    if (host.split('.')[0] == 'www' or host.count('.') == 1) and (urlparse(url).path == '/' or urlparse(url).path == ''):
                        is_guo = True
                        break
                if is_guo == False:
                    return '搜狗 首页判断未通过'

            elif jg == '2':
                for url in url_list:
                    domain_1 = urlparse(url).hostname
                    if domain_1 == None:
                        continue
                    if domain_1.count('.') >= 2 and domain_1.split('.')[0] != 'www' and 'm.' not in domain_1:
                        is_guo = True
                        break
                if is_guo == False:
                    return '搜狗 泛判断未通过'

            elif jg == '3':
                for url in url_list:
                    domain = urlparse(url)
                    if domain.path != '/' and  domain.path != '':
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
    domain = 'chinactzj.com'
    data = o.get_info(domain)
    r = o.check_sogou(data['html'],s,tim_str,domain=domain,sogou_is_com_word='0',jg='1')
    print(r)


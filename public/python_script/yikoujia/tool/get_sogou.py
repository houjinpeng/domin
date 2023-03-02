from datetime import datetime
import json
import os

import requests
import re
from lxml import etree
import threading, queue
import time
from tool.get_min_gan_word import get_mingan_word
from urllib.parse import urlparse
import ddddocr





words = get_mingan_word()
proxy_queue = queue.Queue()
def get_proxy():
    while True:
        if proxy_queue.qsize()> 10:
            time.sleep(2)
            continue
        url = 'http://222.186.42.15:7772/SML.aspx?action=GetIPAPI&OrderNumber=a2b676c40f8428c7de191c831cbcda44&poolIndex=1676099678&Split=&Address=&Whitelist=&isp=&qty=20'
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
            print(f'搜狗 36行错误： {e}')
            continue

threading.Thread(target=get_proxy).start()

class GetSougouRecord():

    def __init__(self):
        self.s = requests.session()
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
        self.s = requests.session()
        self.proxies = {
            'http': f'http://{ip}',
            'https': f'http://{ip}'
        }
        self.s.proxies = self.proxies
        return self.proxies

    def check_verify(self,resp,domain,count=0):
        try:
            headers = {
                "Accept": "application/json, text/javascript, */*; q=0.01",
                "Accept-Encoding": "gzip, deflate",
                "Accept-Language": "zh-CN,zh;q=0.9",
                "Cache-Control": "no-cache",
                "Connection": "keep-alive",
                "Content-Length": "151",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "Host": "www.sogou.com",
                "Origin": "http://www.sogou.com",
                "Pragma": "no-cache",
                "Referer": resp.url,
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36",
                "X-Requested-With": "XMLHttpRequest",
            }

            img_headers = {
                "Accept": "image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8",
                "Accept-Encoding": "gzip, deflate",
                "Accept-Language": "zh-CN,zh;q=0.9",
                "Cache-Control": "no-cache",
                "Connection": "keep-alive",
                "Host": "www.sogou.com",
                "Pragma": "no-cache",
                "Referer": resp.url,
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
            }

            #下载图片
            e = etree.HTML(resp.text)
            #http://www.sogou.com/antispider/util/seccode.php?tc=1674024271814
            img_url = 'http://www.sogou.com/antispider/'+e.xpath('//img[@id="seccodeImage"]/@src')[0]

            img_resp = self.s.get(img_url,headers=img_headers,timeout=10)
            suv_url = f'http://pb.sogou.com/pv.gif?uigs_productid=search_anti&type=antispider&subtype=seccodeFocus&domain=sogou&suv=&snuid=&t={int(time.time() * 1000)}'
            self.s.get(suv_url, timeout=10)
            with open(f'code_{domain}.png','wb') as fw:
                fw.write(img_resp.content)

            ocr = ddddocr.DdddOcr(show_ad=False)
            with open(f'code_{domain}.png', 'rb') as f:
                img_bytes = f.read()
            code = ocr.classification(img_bytes)


            #获取auuid  suuid
            suuid = re.findall('suuid = "(.*?)"',resp.text)[0]
            auuid = re.findall('auuid = "(.*?)"',resp.text)[0]
            data = {
                "c": code,
                "r": f"%2Fweb%3Fquery%3Dsite%3{domain}",
                "p": "web_hb",
                "v": "5",
                "suuid": suuid,
                "auuid": auuid
            }
            os.remove(f'code_{domain}.png')

            result_resp = self.s.post('http://www.sogou.com/antispider/thank.php', data=data, timeout=10, headers=headers)
            # result_resp = requests.post('http://www.sogou.com/antispider/thank.php', data=data, timeout=10, headers=headers)
            result = json.loads(result_resp.text)
            # print(result_resp.text)
            if '跳转' in result_resp.text:
                c = requests.cookies.RequestsCookieJar()
                c.set('SNUID', result['id'], path='/', domain='.sogou.com')
                self.s.cookies.update(c)


                url = f'https://sogou.com/web?query=site%3A{domain}&_asf=www.sogou.com&_ast=&w=01015002&p=40040108&ie=utf8&from=index-nologin&s_from=index&oq=&ri=0&sourceid=sugg&suguuid=&sut=0&sst0=1674814022234&lkt=0%2C0%2C0&sugsuv=00C3E8BF76FA00FB63D38A0E70ABA902&sugtime=1674814022234'
                headers = {
                    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                    "Accept-Encoding": "gzip, deflate, br",
                    "Accept-Language": "zh-CN,zh;q=0.9",
                    "Cache-Control": "no-cache",
                    "Connection": "keep-alive",
                    "Host": "www.sogou.com",
                    "Pragma": "no-cache",
                    "sec-ch-ua": "\"Chromium\";v=\"106\", \"Google Chrome\";v=\"106\", \"Not;A=Brand\";v=\"99\"",
                    "sec-ch-ua-mobile": "?0",
                    "sec-ch-ua-platform": "\"Windows\"",
                    "Sec-Fetch-Dest": "document",
                    "Sec-Fetch-Mode": "navigate",
                    "Sec-Fetch-Site": "none",
                    "Sec-Fetch-User": "?1",
                    "Upgrade-Insecure-Requests": "1",
                    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
                }

                response = self.s.get(url, headers=headers, timeout=5)

                if "seccodeImage" in response.text and "请输入图中的验证码" in response.text:
                    # print('验证码')
                    if count >10:
                        return False
                    return self.check_verify(resp,domain,count+1)
                else:
                    return response

        except Exception as e:
            return False

    def get_jv_now_day(self,domain,html):
        try:
            e = etree.HTML(html)
            url_list_obj = e.xpath('//div[contains(@class,"r-sech")]/@data-url')
            all_domain = e.xpath('//div[contains(@class,"citeurl")]')
            time_list = []

            for url,domain_obj in zip(url_list_obj,all_domain):
                kuaizhao = ''.join(domain_obj.xpath('.//text()'))
                if domain.lower() in url:
                    if re.findall('\d+小时', kuaizhao) != []:
                        time_list.append(1)
                    elif re.findall('(\d+)天', kuaizhao) != []:
                        time_list.append(int(re.findall('(\d+)天', kuaizhao)[0]))
                    else:
                        date = domain_obj.xpath('.//text()')[-1][2:]
                        t = datetime.strptime(date, '%Y-%m-%d')
                        time_list.append((datetime.now()-t).days)
            if time_list == []:
                return '无法计算'

            time_list.sort()
            return int(time_list[0])

        except Exception as e:
            return '计算失败'

    def request_hearders(self, url,count=0):
        try:
            headers = {
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                "Accept-Encoding": "gzip, deflate, br",
                "Accept-Language": "zh-CN,zh;q=0.9",
                "Cache-Control": "no-cache",
                "Connection": "keep-alive",
                "Host": "www.sogou.com",
                "Pragma": "no-cache",
                "sec-ch-ua": "\"Chromium\";v=\"106\", \"Google Chrome\";v=\"106\", \"Not;A=Brand\";v=\"99\"",
                "sec-ch-ua-mobile": "?0",
                "sec-ch-ua-platform": "\"Windows\"",
                "Sec-Fetch-Dest": "document",
                "Sec-Fetch-Mode": "navigate",
                "Sec-Fetch-Site": "none",
                "Sec-Fetch-User": "?1",
                # "Referer": referer,
                "Upgrade-Insecure-Requests": "1",
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
            }
            # r = requests.get(url,headers=headers,timeout=5,proxies=proxies)
            r = self.s.get(url,headers=headers,timeout=5)
            # r = requests.get(url,headers=headers,timeout=5,proxies=proxies)
            # cookie_str = ''
            # for k,v in r.cookies.get_dict('www.sogou.com').items():
            #     cookie_str +=k+"="+v+';'
            # for k, v in r.cookies.get_dict('.sogou.com').items():
            #     cookie_str += k + "=" + v + ';'

            # r = requests.get(url, headers=headers, timeout=5, proxies=proxies)
            if r.status_code != 200:
                # print(r.status_code)
                if count > 20:
                    return None
                self.set_proxies()
                return self.request_hearders(url,count+1)

            if '请输入图中的验证码' in r.text:
                if count > 20:
                    return None
                # print('请输入图中的验证码')
                self.set_proxies()
                # self.s = requests.session()
                # resp = self.check_verify(r,domain)
                # if resp == False:
                #     return None
                # return resp
                return self.request_hearders(url,count+1)
            return r
        except Exception as e:
            if count >= 20:
                return None
            self.set_proxies()
            return self.request_hearders(url,count+1)

    def check_sogou(self, html, record_count, time_str, domain, sogou_is_com_word, jg='0', jv_now_day=None):
        '''
        :param html: 网页html
        :param record_count: 收录数 [min,max]
        :param time_str: 快照时间
        :param time_str: 域名
        :param sogou_is_com_word: 是否对比敏感词
        :param jg: 结构
        :param jv_now_day: 快照距离当前时间
        :return:
        '''
        if jv_now_day is None:
            jv_now_day = ['0', '0']
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
                        title_list.append(''.join(d.xpath('.//text()')).replace(domain," "))
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

                    if (host.split('.')[0] == 'www' or host.count('.') == 1) and (len(urlparse(url).path) <= 1):
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

            #判断快照距离当前时间天数
            if jv_now_day != ['0','0']:
                j1 = int(jv_now_day[0])
                j2 =  9999999 if jv_now_day[1] == '0' else int(jv_now_day[1])
                day = self.get_jv_now_day(domain,html)
                if isinstance(day,str):
                    return f'搜狗无法计算出 快照距离当前时间天数：{day}'
                if j1 > day or j2 < day:
                    return f'搜狗快照距离当前时间天数为：{day}  您设置的是:{j1,j2}'

            return True
        except Exception as error:
            return f'搜狗检测错误 {error}'

    def get_info(self,domain):
        url = f'https://www.sogou.com/web?query=site%3A{domain}'
        # url = f'https://www.sogou.com/web?query=site:{domain}&_ast=1674051198&_asf=www.sogou.com&w=01029901&cid=&s_from=result_up'
        # url = f'https://sogou.com/web?query=site%3A{domain}&_asf=www.sogou.com&_ast=&w=01015002&p=40040108&ie=utf8&from=index-nologin&s_from=index&oq=&ri=0&sourceid=sugg&suguuid=&sut=0&sst0=1674814022234&lkt=0%2C0%2C0&sugsuv=00C3E8BF76FA00FB63D38A0E70ABA902&sugtime=1674814022234'

        r = self.request_hearders(url)
        if r == None:
            return self.get_info(domain)
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
            print(f'搜索查询错误：{error}')
            return self.get_info(domain)

if __name__ == '__main__':
    s = [0,0]
    tim_str = ''
    o = GetSougouRecord()

    # y = o.extract_domain('aaa.www.baidu.com')
    # print(y)
    for i in range(1000):
        domain = 'maiyuan.com'
        data = o.get_info(domain)
        # print(data)
        r = o.check_sogou(data['html'],s,tim_str,domain=domain,sogou_is_com_word='1',jg='1',jv_now_day=[0,1234])
        print(r)


import datetime
import logging
import time
import requests
import json
from lxml import etree
import re
import threading,queue

proxy_queue = queue.Queue()
proxies = {
            "http": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
            "https": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
        }
def get_proxy():
    while True:
        if proxy_queue.qsize()> 1:
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
            print(f'桔子获取代理 33行错误：{e}')
            continue

# threading.Thread(target=get_proxy).start()

cookie = '__bid_n=185bf0f7c047f2324b4207; juz_user_login=04U%2BBdgSdC%2FPZtYVPHlA%2BHJhH60QPYPs%2BoraqAJpwo8eo5L0YxMTSZSAUbKNJRNJekGsNOSNM4KoAfmETC%2BUlk%2Bn14xDbOiAUmWPUkz1LCqBh68uFQe6VX6yU%2BW1QURaHyzaLpIwbzGfXa4kyRgnbw%3D%3D; FPTOKEN=QfMYduhF9mFvFqC4i2Bz9bUbrNic3KQ94OSwRay9VV+5eejF81GABeN0UUu7pUxhrWpgGk14YQPbnfs9G3yKggXKApJZAOpjpJpbdNxENgZroNmH5tb7atojsOAWkDdqeOgUZAT6WaaDsAhA64lIGFppf9YIXIWLj0/ZjgmogYePaFEA7g5awisFnkWHiNvU9sjAJgYbsNuC9B1GsAgh4GoM9qvekWXIvAkHzZGLmptBCP8L0t+2Zme7O9/nSkMq+FMrHqCac3NYwrhQH8dWvokMz+hk7g9ZzlUyIpPFKVIkCyy+bPYhUjrWiKLWlReX3wFpSCn/gKF/qFtEC2/1arUETUbF1bXH1/QHu/CBTYzDoQWjwGOhnpAtLBjF1KHjCx0qYuZsIBu3kq1yMXKW6g==|ITDu1li0jhOkWCZLWgRy32XBJIC+MClOKAPlVYqaMmc=|10|ce5c6844c8cbc6ba5603554459be7beb; juz_Session=ggfvuj593c8in4s747nidtte38; Hm_lvt_f87ce311d1eb4334ea957f57640e9d15=1674041808,1675303304,1675416437,1675422292; Hm_lpvt_f87ce311d1eb4334ea957f57640e9d15=1675435590'
class JvZi():

    def __init__(self):

        self.set_proxy()

    def set_proxy(self):
        try:
            # ip = proxy_queue.get()
            # proxies = {
            #     'http': f'http://{ip}',
            #     'https': f'http://{ip}'
            # }
            self.proxies = proxies
            return proxies
        except Exception as e:
            time.sleep(2)
            return None

    def save(self, domain,count=0):
        url = 'https://seo.juziseo.com/snapshot/save/'
        domain = ['yinxunkeji.com', 'qzycwsgc.com', 'dianyuanzulin.com']
        # data = f'qrtypeindex=1&domains={domain}&_post_type=ajax'
        data = f'post_hash=c6178ebec1d62d911fbfab0f34ceeede&domains={"%0A".join(domain)}&is_ajax=1&mark_title=&_post_type=ajax'
        headers = {
            "accept": "application/json, text/javascript, */*; q=0.01",
            "accept-encoding": "gzip, deflate, br",
            "accept-language": "zh-CN,zh;q=0.9",
            "cache-control": "no-cache",
            "content-length": "48",
            "content-type": "application/x-www-form-urlencoded; charset=UTF-8",
            "cookie": cookie,
            "origin": "https://seo.juziseo.com",
            "pragma": "no-cache",
            "referer": "https://seo.juziseo.com/",
            "sec-ch-ua": "\"Chromium\";v=\"106\", \"Google Chrome\";v=\"106\", \"Not;A=Brand\";v=\"99\"",
            "sec-ch-ua-mobile": "?0",
            "sec-ch-ua-platform": "\"Windows\"",
            "sec-fetch-dest": "empty",
            "sec-fetch-mode": "cors",
            "sec-fetch-site": "same-origin",
            "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36",
            "x-requested-with": "XMLHttpRequest"
        }
        try:
            result = requests.post(url, data=data, headers=headers, timeout=10,proxies=self.proxies).json()
            url = result['rsm']['url']

            return url
        except Exception as e:
            if count > 10:
                return None
            print(f'桔子 域名：{domain} 提交错误 {e}')
            time.sleep(2)
            self.set_proxy()
            return self.save(domain,count+1)


    #获取总建站年龄
    def get_age(self, html):
        try:
            e = etree.HTML(html)
            all_td = e.xpath('//table[@class="table table-condensed text-center"]//tr[1]/td')
            age = all_td[3].xpath('.//span[@class="v svg_num"]/text()')[0].strip()
            # age = re.findall('(\d+)<span class="text-color-999"> ', html, re.S)[2]
            return int(age)
        except Exception as e:
            return 0

    #统一度
    def get_tongyidu(self, html):
        try:
            e = etree.HTML(html)
            all_td = e.xpath('//table[@class="table table-condensed text-center"]//tr[1]/td')
            tongyidu = all_td[2].xpath('.//span[@class="v svg_num"]/text()')[0].strip()
            # tongyidu = re.findall('(\d+)<span class="text-color-999"> ', html, re.S)[1]
            return int(tongyidu)
        except Exception as e:
            return 0

    #连续存档时间
    def get_lianxu_cundang_time(self, html):
        try:
            e = etree.HTML(html)
            all_td = e.xpath('//table[@class="table table-condensed text-center"]//tr[1]/td')
            max_lianxu = all_td[4].xpath('.//span[@class="v svg_num"]/text()')[0].strip()
            # max_lianxu = re.findall('(\d+)<span class="text-color-999"> ', html, re.S)[3]
            return int(max_lianxu)
        except Exception as e:
            return 0

    # 近五年建站
    def get_five_year_num(self, html):
        try:
            five_year_num = re.findall('(\d+)<span class="text-color-999">/5 年 </span>', html, re.S)[0]
            return int(five_year_num)
        except Exception as e:
            return 0

    # 连续5年建站
    def get_lianxu_five_year_num(self, html):

        try:
            lianxu_five_year_num = re.findall('(\d+)<span class="text-color-999">/5 年 </span>', html, re.S)[1]
            return int(lianxu_five_year_num)
        except Exception as e:
            return 0

    def get_zh_title_num(self, html):
        e = etree.HTML(html)
        all_tr = e.xpath('//table[@class="table table-bordered table-condensed table-striped table-hover"]')
        num = 0
        old_year = 0
        try:
            all_tr = all_tr[0].xpath('.//tr')
            for tr in all_tr[1:]:
                year = ''.join(tr.xpath('.//td[2]/text()'))[:-4]
                if old_year != year:
                    old_year = year
                    w = ''.join(tr.xpath('.//span[@class="label label-success"]/text()'))
                    if w == '中文':
                        num += 1

            return num
        except Exception as error:
            return 0

        # 桔子自检敏感词

    def get_zijian_word(self, html_str):
        try:
            e = etree.HTML(html_str)
            span_all = e.xpath('/html/body/div[2]/div/div/div/div/div[2]/div[2]/div[3]/table/tbody/tr[3]/td/span/text()')
            word = '|'.join(span_all[1:])
            # word = re.findall('敏感词\(去重后\)：(.*?)"', html_str,re.S)[0]
            # word = html.unescape(word).replace(',', '|')
            return word
        except Exception as e:
            return '无'

    def get_domain_url(self, domain,count=0):

        url = "https://seo.juziseo.com/snapshot/history/"
        payload = f"post_hash=b16521f2b2262cfadf583c5ecbc00b79&qr={domain}&qrtype=1&input_time=lastquery&start_time=&end_time=&fav=&history_score=0&lang=&age=0&title_precent=0&site_age=0&stable_count=0&stable_start_year_eq=&stable_start_year=&last_year_eq=&last_year=&site_5_age=0&site_5_stable_count=0&blocked=&gray=&gray_in_html=&site_gray=&baidu_site=0&gword=&has_snap=&per_page="
        headers = {
            'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'accept-encoding': 'gzip, deflate, br',
            'accept-language': 'zh-CN,zh;q=0.9',
            'cache-control': 'no-cache',
            'content-length': str(len(payload)),
            'content-type': 'application/x-www-form-urlencoded',
            'cookie': cookie,
            'origin': 'https://seo.juziseo.com',
            'pragma': 'no-cache',
            'referer': 'https://seo.juziseo.com/snapshot/history/id-__qr-eJzLKk3MS0vNS6%2FKSMxL10vOzwUAPrwG1A%3D%3D__qrtype-1__input_time-lastquery.html',
            'sec-ch-ua': '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Windows"',
            'sec-fetch-dest': 'document',
            'sec-fetch-mode': 'navigate',
            'sec-fetch-site': 'same-origin',
            'sec-fetch-user': '?1',
            'upgrade-insecure-requests': '1',
            'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36'
        }
        try:
            response = requests.request("POST", url, headers=headers, data=payload, allow_redirects=False,timeout=10)
            # response = requests.request("POST", url, headers=headers, data=payload, allow_redirects=False,timeout=4)

            url = response.headers._store['location'][1]

            headers = {
                'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'accept-encoding': 'gzip, deflate, br',
                'accept-language': 'zh-CN,zh;q=0.9',
                'cache-control': 'no-cache',
                'cookie': 'juzufr=eJzLKCkpsNLXLy8v18sqrcosTs3XS87P1QcAZ9gIkQ%3D%3D; juzsnapshot=N; juz_Session=ir3e1ikeidpji7ftgs7gdbbf50; Hm_lvt_f87ce311d1eb4334ea957f57640e9d15=1651458445,1651670031,1651720455,1652694580; juz_user_login=iwp4%2FflbRB3RuwTFbF9U6CMbAIJiqYS2ohcrffAZOqkxVcZbLUgx9UD%2FjNRmVkvmZ1GqChT1moYNuNvgK1uVKS%2BhPyqSmgTROjdIi%2BMYacbwgG3oMEJ1vUcr1RhKvku5T02iLuIXaqEELxUvG5FZyQ%3D%3D; Hm_lpvt_f87ce311d1eb4334ea957f57640e9d15=1652701139',
                'pragma': 'no-cache',
                'referer': 'https://seo.juziseo.com/snapshot/history/id-__qr-eJzLKk3MS0vNS6%2FKSMxL10vOzwUAPrwG1A%3D%3D__qrtype-1__input_time-lastquery.html',
                'sec-ch-ua': '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
                'sec-ch-ua-mobile': '?0',
                'sec-ch-ua-platform': '"Windows"',
                'sec-fetch-dest': 'document',
                'sec-fetch-mode': 'navigate',
                'sec-fetch-site': 'same-origin',
                'sec-fetch-user': '?1',
                'upgrade-insecure-requests': '1',
                'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36',
                'Content-Type': 'text/plain'
            }

            r = requests.get(url, headers=headers,timeout=10)
            # r = requests.get(url, headers=headers,timeout=4)
            e = etree.HTML(r.text)
            try:
                url = e.xpath('//td[@class="text-color-999 anchors_area"]/a/@href')[0]
            except Exception as e:
                return ''
            return 'https://seo.juziseo.com'+url
        except Exception as error:
            if count > 10:
                return None
            return self.get_domain_url(domain,count+1)

    def get_detail_html(self,domain,url,count=0):
        try:

            headers = {
                'accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'accept-encoding': 'gzip, deflate, br',
                'accept-language': 'zh-CN,zh;q=0.9',
                'cache-control': 'no-cache',
                'cookie': cookie,
                'pragma': 'no-cache',
                'referer': 'https://seo.juziseo.com/snapshot/list/id-Y21WeVVFMXlXRUpCU0UxRE0waFZRekUzTHpCdmRFczVUMUU5UFE9PQ==.html',
                'sec-ch-ua': '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
                'sec-ch-ua-mobile': '?0',
                'sec-ch-ua-platform': '"Windows"',
                'sec-fetch-dest': 'document',
                'sec-fetch-mode': 'navigate',
                'sec-fetch-site': 'same-origin',
                'sec-fetch-user': '?1',
                'upgrade-insecure-requests': '1',
                'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36'
            }
            # self.check_proxy()
            # self.proxies = random.choice(self.proxy_list)

            response = requests.get(url, headers=headers, timeout=5,proxies=self.proxies)
            # response = requests.get(url, headers=headers, timeout=10)
            return response
        except Exception as e:
            if count > 10:
                return None

            # self.set_proxy()
            return self.get_detail_html(url,domain,count+1)


    def check(self,resp,age,five_create_store,lianxu,five_lianxu,tongyidu,is_comp_title_mingan,is_comp_neirong_mingan,is_comp_soulu_mingan):
        if age != ['0','0']:
            age[0] = int(age[0])
            age[1] = 99999 if age[1] == '0' else int(age[1])
            age_num = self.get_age(resp.text)
            if age_num < age[0] or age_num> age[1]:
                return f'桔子历史年龄不符 年龄为：{age_num} 设置区间为：{age[0],age[1]}'
        #获取自检词
        zijian = self.get_zijian_word(resp.text)
        if int(is_comp_title_mingan) == 1:
            if '标题敏感词' in zijian:
                return f'桔子标题有敏感词 ：{zijian}'


        if int(is_comp_soulu_mingan) == 1:
            if '收录敏感' in zijian or '百度敏感' in zijian:
                return f'桔子收录有敏感词 ：{zijian}'
        if int(is_comp_neirong_mingan) == 1:
            if '内容敏感词' in zijian:
                return f'桔子内容有敏感词 ：{zijian}'


        if five_create_store != ['0', '0']:
            five_create_store[0] = int(five_create_store[0])
            five_create_store[1] = 99999 if five_create_store[1] == '0' else int(five_create_store[1])
            five_create_store_num = self.get_five_year_num(resp.text)
            if five_create_store_num < five_create_store[0] or five_create_store_num > five_create_store[1]:
                return f'桔子五年建站不符 年龄为：{five_create_store_num}  设置区间为：{five_create_store[0],five_create_store[1]}'

        if lianxu != ['0', '0']:
            lianxu[0] = int(lianxu[0])
            lianxu[1] = 99999 if lianxu[1] == '0' else int(lianxu[1])
            lianxu_num = self.get_lianxu_cundang_time(resp.text)
            if lianxu_num < lianxu[0] or lianxu_num > lianxu[1]:
                return f'桔子最长连续时长不符 为：{lianxu_num} 设置区间为：{lianxu[0],lianxu[1]}'
        if five_lianxu != ['0', '0']:
            five_lianxu[0] = int(five_lianxu[0])
            five_lianxu[1] = 99999 if five_lianxu[1] == '0' else int(five_lianxu[1])
            lianxu_num = self.get_lianxu_five_year_num(resp.text)
            if lianxu_num < five_lianxu[0] or lianxu_num > five_lianxu[1]:
                return f'桔子五年连续时长不符 为：{lianxu_num} 设置区间为：{five_lianxu[0],five_lianxu[1]}'

        if tongyidu != ['0', '0']:
            tongyidu[0] = int(tongyidu[0])
            tongyidu[1] = 99999 if tongyidu[1] == '0' else int(tongyidu[1])
            tongyidu_num = self.get_tongyidu(resp.text)
            if tongyidu_num < tongyidu[0] or tongyidu_num > tongyidu[1]:
                return f'桔子统一度不符 为：{tongyidu_num} 设置区间为：{tongyidu[0],tongyidu[1]}'
        return True

    def test(self):
        ds = ["bjzry.com"]
        # resp = self.get_token(ds)

        for d in ds:
            resp = self.get_detail_html(d,'https://seo.juziseo.com/snapshot/list/id-SzA5cVEwOTFLMU5XU0ZGT01USlpTakl2UkRVME9YYzk=')

            '''
            1.域名用桔子查询历史并抓取历史中的标题 对比词库是否含有词库。
            2.提取桔子标题中 是中文的标题数量。注意每年只算一条。
            3.提取桔子中的总建站年数参数作为输出总建站年龄。
            4.提取桔子中的内容统一度参数作为统一度输出。
            5.提取桔子中的近5年历史参数作为近5年历史输出。
            6.提取桔子中的最长连续时间参数作为最长连续时间（年）输出。
            7.提取桔子中的近5年连续参数作为近5年连续输出。
            8.投射桔子当前域名的当前网址
            
            '''


            #   2.提取桔子标题中 是中文的标题数量。注意每年只算一条。
            print(f'开始查询：{d} ')
            zh_num = self.get_zh_title_num(resp.text)
            print('中文标题数量：', zh_num)
            #自检敏感词
            zijian = self.get_zijian_word(resp.text)
            print('中文自检敏感词：',zijian)
            # 3.提取桔子中的总建站年数参数作为输出总建站年龄。
            age = self.get_age(resp.text)
            print('建站中年龄:',age)
            # 4.提取桔子中的内容统一度参数作为统一度输出。
            tongyidu = self.get_tongyidu(resp.text)
            print('统一度：',tongyidu)
            #5.提取桔子中的近5年历史参数作为近5年历史输出。
            five_year_num = self.get_five_year_num(resp.text)
            print('近五年历史输出数：',five_year_num)
            # 6.提取桔子中的最长连续时间参数作为最长连续时间（年）输出。
            lianxu_cundang_time = self.get_lianxu_cundang_time(resp.text)
            print('最长连续时间（年）输出:',lianxu_cundang_time)
            # 7.提取桔子中的近5年连续参数作为近5年连续输出。
            lianxu_five_year_num = self.get_lianxu_five_year_num(resp.text)
            print('5年连续输出:',lianxu_five_year_num)
            # 8.投射桔子当前域名的当前网址
            url = resp.url
            print('详情网址：',url)
            reslut = self.check(resp, age=[1,100], five_create_store=[2,100], lianxu=[2,50], five_lianxu=[2,60], tongyidu=[90,100],is_comp_soulu_mingan=1,is_comp_title_mingan=1,is_comp_neirong_mingan=1)
            print(reslut)
            print('=='*10)

if __name__ == '__main__':


    JvZi().test()


import datetime
import logging
import time
import requests
import json
import threading
from lxml import etree
import re
import html
import random
proxies = {
    "http": "http://127.0.0.1:7890",
    "https": "http://127.0.0.1:7890",
}

cookie = '__bid_n=185bf0f7c047f2324b4207; FPTOKEN=RVYA2RIjazLHJY160EtVPdxdI0ncFm5Meppkm9C1IyyM6BEvgnFuH26xFO97aXa1UjGmE065clUu+Yv2PFpDOfUFXe3cdQdgnj6Y6fUYDHBw47tEOAN+fXngCpX6Lg9DBcnnrZazYgBI7YV/OnaiygVkhJWRnxUK8x/yAylawlTWvj1W1se2UxGnoP2LscMtefeODIs9Ox43mhUb7AXVVe9S2dS2907kmNLI2MmInCP417VDUIY7My9OEfG8MgrfU9KYs5bOpMXv8o+CoybqZFmgFGUHWKu8ZqIKy1CbhLRpnYPrcqHJAw3ryMBCAR+Bkpda4pNNiGePH6ow4Bs/RF5xR9jroGMKEpNvwXktH5vJ7nEBUEHaHNmdbU36lyBOO7b0rRGgRmpSuFlXzwTzHg==|d0VSeWdv05KEYOIQI58bpn6V9quEusT6HD+OF/oJkH8=|10|3bd55de002844f2dc9da2e61a9144c2a; juz_Session=mg3li0t643t84qum5ui20u4t5r; Hm_lvt_f87ce311d1eb4334ea957f57640e9d15=1673947741,1673955092; juz_user_login=04U%2BBdgSdC%2FPZtYVPHlA%2BHJhH60QPYPs%2BoraqAJpwo8eo5L0YxMTSZSAUbKNJRNJekGsNOSNM4KoAfmETC%2BUlk%2Bn14xDbOiAUmWPUkz1LCqBh68uFQe6VX6yU%2BW1QURaHyzaLpIwbzGfXa4kyRgnbw%3D%3D; Hm_lpvt_f87ce311d1eb4334ea957f57640e9d15=1673955164'
class JvZi():

    def __init__(self):
        pass

    #获取总建站年龄
    def get_age(self, html):
        try:
            e = etree.HTML(html)
            all_td = e.xpath('//table[@class="table table-condensed text-center"]//tr[1]/td')
            age = all_td[3].xpath('.//span[@class="v svg_num"]/text()')[0].strip()
            # age = re.findall('(\d+)<span class="text-color-999"> ', html, re.S)[2]
            return age
        except Exception as e:
            return 0

    #统一度
    def get_tongyidu(self, html):
        try:
            e = etree.HTML(html)
            all_td = e.xpath('//table[@class="table table-condensed text-center"]//tr[1]/td')
            tongyidu = all_td[2].xpath('.//span[@class="v svg_num"]/text()')[0].strip()
            # tongyidu = re.findall('(\d+)<span class="text-color-999"> ', html, re.S)[1]
            return tongyidu+'%'
        except Exception as e:
            return '0%'

    #连续存档时间
    def get_lianxu_cundang_time(self, html):
        try:
            e = etree.HTML(html)
            all_td = e.xpath('//table[@class="table table-condensed text-center"]//tr[1]/td')
            max_lianxu = all_td[4].xpath('.//span[@class="v svg_num"]/text()')[0].strip()
            # max_lianxu = re.findall('(\d+)<span class="text-color-999"> ', html, re.S)[3]
            return max_lianxu
        except Exception as e:
            return 0



    # 近五年建站
    def get_five_year_num(self, html):
        try:
            five_year_num = re.findall('(\d+)<span class="text-color-999">/5 年 </span>', html, re.S)[0]
            return five_year_num
        except Exception as e:
            return 0

    # 连续5年建站
    def get_lianxu_five_year_num(self, html):

        try:
            lianxu_five_year_num = re.findall('(\d+)<span class="text-color-999">/5 年 </span>', html, re.S)[1]
            return lianxu_five_year_num
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

    def get_detail_html(self,domain,count=0):
        try:
            url = self.get_domain_url(domain)
            if url == '':
                return ''
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

            response = requests.get(url, headers=headers, timeout=3)
            # response = requests.get(url, headers=headers, timeout=10)
            return response
        except Exception as e:
            if count > 5:
                return None


            return self.get_detail_html(domain,count+1)


    def test(self):
        ds = ['zxopfm.com', 'baidu1.com', 'baidu2.com', 'baidu3.com', 'baidu4.com']
        # resp = self.get_token(ds)


        resp = self.get_detail_html(ds[0])

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
        zh_num = self.get_zh_title_num(resp.text)
        #自检敏感词
        zijian = self.get_zijian_word(resp.text)
        print('中文标题数量：',zh_num)
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



if __name__ == '__main__':
    ds = ['zxopfm.com', 'baidu1.com', 'baidu2.com', 'baidu3.com', 'baidu4.com']

    JvZi().test()
    # for domain in ['baidu.com','baidu.com','baidu.com','baidu.com']:
    # get_history(ds)

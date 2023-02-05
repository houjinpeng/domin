import datetime
import logging
import time
import requests
import json
from conf.config import *

from dbutils.pooled_db import PooledDB
from lxml import etree
import re
import threading, queue

cookie = '__bid_n=185bf0f7c047f2324b4207; juz_user_login=04U%2BBdgSdC%2FPZtYVPHlA%2BHJhH60QPYPs%2BoraqAJpwo8eo5L0YxMTSZSAUbKNJRNJekGsNOSNM4KoAfmETC%2BUlk%2Bn14xDbOiAUmWPUkz1LCqBh68uFQe6VX6yU%2BW1QURaHyzaLpIwbzGfXa4kyRgnbw%3D%3D; FPTOKEN=QfMYduhF9mFvFqC4i2Bz9bUbrNic3KQ94OSwRay9VV+5eejF81GABeN0UUu7pUxhrWpgGk14YQPbnfs9G3yKggXKApJZAOpjpJpbdNxENgZroNmH5tb7atojsOAWkDdqeOgUZAT6WaaDsAhA64lIGFppf9YIXIWLj0/ZjgmogYePaFEA7g5awisFnkWHiNvU9sjAJgYbsNuC9B1GsAgh4GoM9qvekWXIvAkHzZGLmptBCP8L0t+2Zme7O9/nSkMq+FMrHqCac3NYwrhQH8dWvokMz+hk7g9ZzlUyIpPFKVIkCyy+bPYhUjrWiKLWlReX3wFpSCn/gKF/qFtEC2/1arUETUbF1bXH1/QHu/CBTYzDoQWjwGOhnpAtLBjF1KHjCx0qYuZsIBu3kq1yMXKW6g==|ITDu1li0jhOkWCZLWgRy32XBJIC+MClOKAPlVYqaMmc=|10|ce5c6844c8cbc6ba5603554459be7beb; juz_Session=ggfvuj593c8in4s747nidtte38; Hm_lvt_f87ce311d1eb4334ea957f57640e9d15=1674041808,1675303304,1675416437,1675422292; Hm_lpvt_f87ce311d1eb4334ea957f57640e9d15=1675435590'
db_pool = PooledDB(**mysql_pool_conf)


class JvZi():

    def __init__(self):
        self.headers = {
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

    def save(self, ym_list, count=0):
        url = 'https://seo.juziseo.com/snapshot/save/'
        # domain = ['yinxunkeji.com', 'qzycwsgc.com', 'dianyuanzulin.com']
        # data = f'qrtypeindex=1&domains={domain}&_post_type=ajax'
        data = f'post_hash=c6178ebec1d62d911fbfab0f34ceeede&domains={"%0A".join(ym_list)}&is_ajax=1&mark_title=&_post_type=ajax'
        try:
            result = requests.post(url, data=data, headers=self.headers, timeout=10).json()
            # url = result['rsm']['url']
            #
            # return url
        except Exception as e:
            if count > 10:
                return None
            print(f'桔子 域名：{ym_list} 提交错误 {e}')
            time.sleep(2)
            return self.save(ym_list, count + 1)

    def save_ym_url(self,ym,url):
        conn = db_pool.connection()
        cur = conn.cursor()
        update_sql = "update search_jvzi_data set ym_url='%s',is_search=1 where ym='%s'"%(url,ym)
        cur.execute(update_sql)
        conn.commit()
        cur.close()
        conn.close()

    def get_histroy(self, ym):

        ym_list = []
        for y in ym:
            ym_list.append(y['ym'].lower())



        # 查询
        # data = f'post_hash=c6178ebec1d62d911fbfab0f34ceeede&stype=domain&qr={ym}&qrtype=1&input_time=lastquery&start_time=&end_time=&mark_title=&fav=&history_score=0&lang=&age=0&title_precent=0&site_age=0&stable_count=0&stable_start_year_eq=&stable_start_year=&last_year_eq=&last_year=&site_5_age=0&site_5_stable_count=0&blocked=&gray=&gray_in_html=&site_gray=&baidu_site=0&gword=&has_snap=&per_page='
        data = f'post_hash=c6178ebec1d62d911fbfab0f34ceeede&stype=domain&qr={"+".join(ym_list)}&qrtype=1&input_time=lastquery&start_time=&end_time=&mark_title=&fav=&history_score=0&lang=&age=0&title_precent=0&site_age=0&stable_count=0&stable_start_year_eq=&stable_start_year=&last_year_eq=&last_year=&site_5_age=0&site_5_stable_count=0&blocked=&gray=&gray_in_html=&site_gray=&baidu_site=0&gword=&has_snap=&per_page=1000'

        try:
            headers = {
                "accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                "accept-encoding": "gzip, deflate, br",
                "accept-language": "zh-CN,zh;q=0.9",
                "cache-control": "no-cache",
                "content-type": "application/x-www-form-urlencoded",
                "cookie": cookie,
                "origin": "https://seo.juziseo.com",
                "pragma": "no-cache",
                "referer": "https://seo.juziseo.com/snapshot/history/id-__stype-domain__qr-eJzLyC%2FNyswrSM1L10vOzwUAK2kFpQ%3D%3D__qrtype-1__input_time-lastquery.html",
                "sec-ch-ua": "\"Chromium\";v=\"106\", \"Google Chrome\";v=\"106\", \"Not;A=Brand\";v=\"99\"",
                "sec-ch-ua-mobile": "?0",
                "sec-ch-ua-platform": "\"Windows\"",
                "sec-fetch-dest": "document",
                "sec-fetch-mode": "navigate",
                "sec-fetch-site": "same-origin",
                "sec-fetch-user": "?1",
                "upgrade-insecure-requests": "1",
                "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
            }

            url = 'https://seo.juziseo.com/snapshot/history/'
            r = requests.post(url, data=data, timeout=10, headers=headers)
            # r = requests.get(url, data=data, timeout=10, headers=headers)
            #搜索url
            search_url = r.url
            resp_data = requests.get(search_url,headers=headers,timeout=10)

            #查找域名url 保存到数据库
            e = etree.HTML(resp_data.text)
            a_list = e.xpath('//tr[@class="a_row"]')
            # 保存数据
            conn = db_pool.connection()
            cur = conn.cursor()
            for a in a_list:

                ym_str = a.xpath('.//a[@class="openUrl"]//text()')[0].lower()

                if ym_str in ym_list:
                    try:

                        data_obj = a.xpath('.//td')

                        ym_dict = {}

                        try:
                            #评分
                            ym_dict['score'] = int(data_obj[0].xpath('.//b/text()')[-1])
                        except:
                            ym_dict['score'] = 0
                        try:
                            # 自检
                            ym_dict['zijian'] = '|'.join(data_obj[0].xpath('.//span[@class="label label-warning show-tips"]//text()'))
                        except:
                            ym_dict['zijian'] = ''
                        try:
                            # 建站年龄
                            ym_dict['history_age'] = int(''.join(data_obj[1].xpath('.//span[@class="v svg_num"]/text()')).strip())
                        except:
                            ym_dict['history_age'] = 0
                        try:
                            # 统一度
                            ym_dict['tongyidu'] = int(''.join(data_obj[2].xpath('.//span[@class="v svg_num"]/text()')).strip())
                        except:
                            ym_dict['tongyidu'] = 0
                        try:
                            #建站总年数
                            ym_dict['create_site_total_year'] = int(''.join(data_obj[3].xpath('.//span[@class="v svg_num"]/text()')).strip())
                        except:
                            ym_dict['create_site_total_year'] = 0

                        try:
                            # 最长连续时长
                            ym_dict['zuizhanglianxu'] = int(''.join(data_obj[4].xpath('.//span[@class="v svg_num"]/text()')).strip())
                        except:
                            ym_dict['zuizhanglianxu'] = 0
                        try:
                            # 近五年历史输出
                            ym_dict['five_create_site'] = int(''.join(data_obj[5].xpath('.//span[@class="v svg_num"]/text()')).strip())
                        except:
                            ym_dict['five_create_site'] = 0
                        try:
                            # 5年连续
                            ym_dict['five_lianxu'] = int(''.join(data_obj[6].xpath('.//span[@class="v svg_num"]/text()')).strip())
                        except:
                            ym_dict['five_lianxu'] = 0

                        ym_dict['ym_url'] = 'https://seo.juziseo.com'+a.xpath('.//@href')[0]


                        update_sql = "update search_jvzi_data set ym_url='%s',is_search=1,detail='%s' where ym='%s'" % (ym_dict['ym_url'], json.dumps(ym_dict),ym_str)
                        cur.execute(update_sql)
                        conn.commit()


                    except Exception as error:
                        print(f'桔子获取详情时错误：{error}')


            cur.close()
            conn.close()
        except Exception as e:
            print(f'桔子获取域名url失败 错误信息 ：{e}')
            time.sleep(2)
            return self.get_histroy(ym)


    #开启线程一直监控桔子保存过的数据
    def get_url(self):
        while True:
            try:
                conn = db_pool.connection()
                cur = conn.cursor()
                select_sql = "select * from search_jvzi_data where is_search=3 limit 100"
                cur.execute(select_sql)
                all_data = cur.fetchall()
                cur.close()
                conn.close()
                self.get_histroy(all_data)
                time.sleep(1)
            except Exception as error:
                print(f'桔子线程获取域名url错误：{error}')

    def index(self):
        # 开启线程
        threading.Thread(target=self.get_url).start()

        # 查询数据库 是否有要查询的任务 1.5秒一次
        while True:
            conn = db_pool.connection()
            cur = conn.cursor()
            select_sql = "select * from search_jvzi_data where is_search=0 limit 10000"
            cur.execute(select_sql)
            all_data = cur.fetchall()

            ym_list = [data['ym'] for data in all_data]
            if ym_list == []:
                time.sleep(1)
                continue
            self.save(ym_list)

            #修改域名信息为3 已保存 未找到url
            update_sql ="update search_jvzi_data set is_search=3 where ym in (%s)"%(str(ym_list)[1:-1])
            cur.execute(update_sql)
            conn.commit()
            cur.close()
            conn.close()

            # for ym in ym_list:
            #     print(f'桔子 查询域名：{ym}')
            #     self.get_histroy(ym)

            # 直接查询结果  放到数据库中

            time.sleep(1)


if __name__ == '__main__':
    # JvZi().get_histroy('houjinpeng.com')
    JvZi().get_url()

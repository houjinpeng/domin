import copy
import queue
import re
import sys
import time
import requests
from lxml import etree
import pymysql
import threading
from urllib.parse import urlparse
import os
from config import *
from tool import *


# 初始化敏感词
def sensitive():
    try:
        words = []
        with open("敏感词.txt", encoding="UTF-8") as f:
            rows = f.readlines()
            for row in rows:
                row = row.replace("\n", "")
                words.append(row)

        words = [x for x in words if x]
        return words
    except Exception:
        return []


words = sensitive()

conn = pymysql.connect(**MYSQL_CONF)
cur = conn.cursor()


# 检测是否是中文
def check_contain_chinese1(check_str):
    for ch in check_str:
        if u'\u4e00' <= ch <= u'\u9fff':
            return '中文'

    return '其他'


proxy_queue = queue.Queue()


class BaiDu():
    def __init__(self):
        self.proxies = self.get_proxy()
        self.s = requests.session()

    # 检测是否是中文
    def check_contain_chinese(self, url_list):
        for url in url_list:
            if '.' not in url:
                return True
        return False

    def get_proxy(self):
        try:

            if proxy_queue.qsize() == 0:
                url = 'http://222.186.42.15:7772/SML.aspx?action=GetIPAPI&OrderNumber=a2b676c40f8428c7de191c831cbcda44&poolIndex=1676099678&Split=&Address=&Whitelist=&isp=&qty=1'
                try:
                    r = requests.get(url, timeout=3)
                    if '尝试修改提取筛选参数' in r.text or '用户异常' in r.text:
                        print('尝试修改提取筛选参数')
                        time.sleep(20)
                    ip_list = r.text.split('\r\n')
                    for ip in ip_list:
                        if ip.strip() == '': continue
                        proxy_queue.put(ip)
                except Exception as e:
                    time.sleep(1)
                    return self.get_proxy()

            ip = proxy_queue.get()
            proxies = {
                'http': f'http://{ip}',
                'https': f'http://{ip}'
            }
            self.proxies = proxies
            return proxies
        except Exception as e:
            time.sleep(2)
            return None

    # 获取结构
    def get_jg(self, domain, url_list):
        try:
            jg = []

            for url in url_list:
                if domain not in url: continue
                host = urlparse(url).hostname
                if host == None:
                    continue
                if (host.split('.')[0] == 'www' or host.count('.') == 1) and (len(urlparse(url).path) <= 1):
                    jg.append('首页')
                    break

            for url in url_list:
                if domain not in url: continue
                host = urlparse(url).hostname
                if host == None:
                    continue
                if host.count('.') >= 2 and host.split('.')[0] != 'www' and 'm.' not in host:
                    jg.append('泛')
                    break

            for url in url_list:
                if domain not in url: continue
                domain_1 = urlparse(url)
                if domain_1.path != '/' and domain_1.path != '':
                    jg.append('内页')
                    break

            return '|'.join(jg)

        except Exception as e:
            return ''

    # 查找关键词
    def find_word(self, seg_list):
        global words
        """
        # 匹配敏感侧
        :param seg_list:
        :return:
        """
        title_sensi = ""

        for w in words:
            if str(w) in seg_list:
                title_sensi += w + "|"
        if title_sensi != "":
            return title_sensi
        else:
            return "无结果"

    def requests_handler(self, url1, is_yz=False):
        url = f"https://www.baidu.com/s?f=8&rsv_bp=1&wd=site%3A{url1}"

        headers = {
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive',
            'Host': 'www.baidu.com',
            'Pragma': 'no-cache',
            'Referer': 'https://www.baidu.com/',
            'sec-ch-ua': '" Not A;Brand";v="99", "Chromium";v="101", "Google Chrome";v="101"',
            'sec-ch-ua-mobile': '?0',
            'sec-ch-ua-platform': '"Windows"',
            'Sec-Fetch-Dest': 'document',
            'Sec-Fetch-Mode': 'navigate',
            'Sec-Fetch-Site': 'same-origin',
            'Sec-Fetch-User': '?1',
            'Upgrade-Insecure-Requests': '1',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/101.0.4951.54 Safari/537.36',
            'Cookie': 'BIDUPSID=7E8FF5B8B6A04F730D326E096D797311; PSTM=1631605740; BAIDUID=7E8FF5B8B6A04F73916DB66CF6DA889A:FG=1; __yjs_duid=1_ac59271c36da10cdc87f4dc99a62b19b1631605755856; BD_UPN=12314753; BDUSS=HRGaEZLblB1QVN-cW9zUS1ER0o0SFNmOWo3Z3lnS2RWc1luYVpRNk51SndwSWhpRVFBQUFBJCQAAAAAAAAAAAEAAAAAJZjRYmF5dWxpYW4xMDQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHAXYWJwF2FiV; BDUSS_BFESS=HRGaEZLblB1QVN-cW9zUS1ER0o0SFNmOWo3Z3lnS2RWc1luYVpRNk51SndwSWhpRVFBQUFBJCQAAAAAAAAAAAEAAAAAJZjRYmF5dWxpYW4xMDQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAHAXYWJwF2FiV; H_WISE_SIDS=107314_110085_114550_127969_174441_179350_180636_184716_188746_189755_190623_191527_194085_194519_194530_196426_197241_197711_197956_199574_201193_203517_206906_207236_207574_207729_208608_208721_209455_209568_210091_210306_210440_210470_210642_210664_210757_210851_211288_211435_211558_211732_212416_212699_212778_212797_213031_213060_213094_213125_213353_213416_213596_213645_214005_214025_214115_214129_214138_214142_214396_214535_214655_214793_214883_215175_215280_215457_215484_215727_215806_215829_215859_216049; BDSFRCVID_BFESS=3WCOJeC62rTEVBJDlYXsMFIMlmPlqOOTH6aoTq4RcGLzFvWtAe3mEG0P5x8g0KuM3ulxogKKLmOTHpKF_2uxOjjg8UtVJeC6EG0Ptf8g0f5; H_BDCLCKID_SF_BFESS=tR4joID5tC-3fP36q4bs5t_HbUR0-I62aKDs-PDMBhcqEIL4jhjkM5-pQMcH0t33Bn7dhIojthjzHxbSj4QoQbt_jb_j35ci5CoMaKjYah5nhMJEb67JDMP0qfbe3hoy523i2n6vQpn2OpQ3DRoWXPIqbN7P-p5Z5mAqKl0MLPbtbb0xb6_0j5OBDG_eJ60s-C5KWJnaHt3qjRTphR6s-t6H-UnLq5oaX2OZ0l8KttK2eK3mXUOCXn8WM-keQhvMtjueXDOmWIQthn6wQJ6kjp5-DMt80JTz-nR4KKJxLlCWeIJo5fFhKxuehUJiBM7LBan7QpvIXKohJh7FM4tW3J0ZyxomtfQxtNRJ0DnjtnLhbC89jj-MDT5LepJq-J-XM6vH0RcXHJO_bIO5LUnkbfJBDl5n0l5GWenQhxnbBlQz8JRz0-vaXU47yajK2h3DJNr2KhRL3tQRfhjNDPcpQT8rjqAOK5OibmDeLRRyab3vOpRzXpO1KMPzBN5thURB2DkO-4bCWJ5TMl5jDh3Mb6ksDMDtqtJHKbDeoKLMfU5; BDORZ=B490B5EBF6F3CD402E515D22BCDA1598; BAIDUID_BFESS=7E8FF5B8B6A04F73916DB66CF6DA889A:FG=1; BA_HECTOR=85a12lah242ka58l802mdcnb1hd74nc17; ZFY=ba75SbVoKLf:AJxKUdLtbSEwnx:BPnQN7SAJHpxFCIMZk:C; BD_HOME=1; H_PS_PSSID=36558_36625_36820_36454_36413_36611_36692_36165_36816_36775_36745_36762_36771_36764_26350; delPer=0; BD_CK_SAM=1; PSINO=6; H_PS_645EC=a3c72gk8irtDfWDIrQiWofEHgqpQmJfOoP7TmNwXu7uwiQR6MINMvLZ45eA; channel=baidusearch; baikeVisitId=6ac61418-a9ce-47a5-ac60-b1c7abd4b0b6'
            # 'Cookie': 'BAIDUID=FE5D3C7F6CAAF5A80701894E937B66C2:FG=1; BIDUPSID=B2CE0F7F4FACBD5E54D7A2EF967F7D5D; BD_UPN=12314753; H_PS_PSSID=36543_37352_36885_34813_37486_37402_37396_36569_36786_37071_26350_37344_37372; BDORZ=B490B5EBF6F3CD402E515D22BCDA1598; BAIDUID_BFESS=FE5D3C7F6CAAF5A80701894E937B66C2:FG=1; BD_HOME=1; delPer=0; BA_HECTOR=218l2k8hal0k012h802lfnpa1hj7acd1a; ZFY=cMpoHD8KA4cRAxz88vZGKrU0Pz:AA6UjS9TTTXS:AyI6Y:C; BD_CK_SAM=1; B64_BOT=1; COOKIE_SESSION=159677_3_8_6_5_13_1_0_3_7_0_1_159742_89_3_0_1664335776_1664005747_1664335773%7C9%2333_4_1664005672%7C2; PSINO=7; H_PS_645EC=82b5rVoBLL0BGMQjI4j2q9mrsheDDXZ3dHodpCt8T1im4lzVAVR3clFJuKE; BDSVRTM=0; WWW_ST=1664345254413'
        }

        try:
            if is_yz == False:
                response = requests.get(url, headers=headers, timeout=3)
            else:
                h = copy.deepcopy(headers)
                del h['Cookie']
                response = requests.get(url, headers=headers, proxies=self.proxies, timeout=10)

            response.encoding = 'utf-8'
            if '百度安全验证' in response.text:
                self.get_proxy()
                print(f'出现百度安全验证 更换代理 {self.proxies}')
                return self.requests_handler(url1, True)
            elif response.text.strip() == '':
                return None
            elif '<div id="__status">' in response.text:
                return None
            return response
        except Exception as e:
            # self.get_proxy()
            # log.logger.error(f'更换代理 {e}')
            # return self.requests_handler(url1)
            return None

    def get_domain_url(self, baidu_url, count=0):
        try:
            headers = {
                'User-Agent': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
            }
            # r = requests.get(baidu_url,headers=headers,verify=False)
            r = requests.get(baidu_url, headers=headers, verify=False, allow_redirects=False)
            url = r.headers._store['location'][1]

            return url
        except Exception as e:
            if count < 5:
                return self.get_domain_url(baidu_url, count + 1)
            return ''

    def get_info(self,domain):

        # while not task_queue.empty():
        # domain = task_queue.get()
        data = {
            'ym': domain,
            'sl': 0,
            'mgc': '',  # 敏感词
            'jg': 'baidu',  # 结构
            'is_chinese': '',  # URL语言
        }

        r = self.requests_handler(domain)
        if r == None:
            return self.get_info(domain)
        html = etree.HTML(r.text)
        # 数据量
        h3_list = html.xpath('//div[@class="result c-container xpath-log new-pmd"]')
        # 完整的url域名
        full_url_list = []
        # 显示的url
        show_url_list = []
        findword = ''
        # 获取url list
        for d in h3_list:
            try:
                url = self.get_domain_url(d.xpath('.//h3[@class="c-title t t tts-title"]//a/@href')[0])
                if domain.lower() in url.lower():
                    full_url = self.get_domain_url(d.xpath('.//h3[@class="c-title t t tts-title"]//a/@href')[0])
                    full_url_list.append(full_url)
                    source_url = d.xpath('.//span[@class="c-color-gray"]//text()')[0]
                    show_url_list.append(source_url)
                    title = str(d.xpath('.//h3[@class="c-title t t tts-title"]//a/text()')[0]).replace(","," ").replace("\n", "")
                    findword += title
            except Exception as error:
                pass
        try:
            record_count = int(re.findall('找到相关结果数约(.*?)个', r.text)[0].replace(',', ''))
        except Exception as error:
            record_count = 0
        data['jg'] = self.get_jg(domain, full_url_list)
        data['mgc'] = self.find_word(findword)
        data['sl'] = record_count
        data['is_chinese'] = self.check_contain_chinese(show_url_list)

        sql = build_sql('ym_search_result', {'ym': domain, 'data': data, 'type': 'baidu'})
        save_data(sql, cur, conn)

if __name__ == '__main__':
    ym = sys.argv[1]
    # ym = 'loohool.cn'
    BaiDu().get_info(ym)


    # print(yms,type(yms))
    # print(os.getcwd())
    #
    # task_queue = queue.Queue()
    # for ym in yms:
    #     print(ym,12312331)
    #     if ym.strip() == '': continue
    #     task_queue.put(ym.strip())
    #
    #
    # def start():
    #     t = []
    #     for i in range(10):
    #         t.append(threading.Thread(target=BaiDu().get_info))
    #     for j in t:
    #         j.start()
    #     for j in t:
    #         j.join()
    #     cur.close()
    #     conn.close()
    #     print('success',len(yms))


    # start()

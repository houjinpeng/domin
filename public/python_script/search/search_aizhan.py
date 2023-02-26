import json
import sys
import requests
import time
import re
from lxml import etree


class AiZhan():
    def __init__(self):
        self.s = requests.session()


    #设置代理
    def get_proxy(self):
        try:
            url = 'http://222.186.42.15:7772/SML.aspx?action=GetIPAPI&OrderNumber=a2b676c40f8428c7de191c831cbcda44&poolIndex=1676099678&Split=&Address=&Whitelist=&isp=&qty=1'
            r = requests.get(url,timeout=4)
            if '尝试修改提取筛选参数' in r.text or '用户异常' in r.text:
                print('尝试修改提取筛选参数')
                time.sleep(20)
                return self.get_proxy()
            ip_list = r.text.split('\r\n')
            for ip in ip_list:
                if ip.strip() == '': continue
                proxies = {
                    'http': f'http://{ip}',
                    'https': f'http://{ip}'
                }

                self.s = requests.session()
                self.proxies = proxies
                self.s.proxies.update(proxies)
                return proxies
        except Exception as e:
            time.sleep(2)
            return None

    def get_pr(self,domain,ssyx,rn,cc):
        url = f'https://rest.aizhan.com/pr/{ssyx}?callback=jQuery19109297032291130505_1677395960164&domain={domain}&rn={rn}&cc={cc}&_={int(time.time()*1000)}'
        try:
            r = requests.get(url,timeout=3).text
            pr = re.findall('\{"pr":"(\d+)"',r)[0]
            return pr
        except Exception as e:
            return '0'



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
        rn = re.findall('rn = (.*?),',r.text)[0]
        cc = re.findall('cc = "(.*?)",',r.text)[0]
        try:
           data['baidu_pr'] = str(html.xpath('//a[@id="baidurank_br"]/img/@alt')[0])
           if data['baidu_pr'] == 'n':
               data['baidu_pr'] = self.get_pr(domain,'baidu',rn,cc)
               # data['baidu_pr'] = '0'
        except Exception:
            data['baidu_pr'] = '0'
        try:
            # 移动权重
            data['yidong_pr'] = str(html.xpath('//a[@id="baidurank_mbr"]/img/@alt')[0])
            if data['yidong_pr'] == 'n':
                data['yidong_pr'] =  self.get_pr(domain, 'yd', rn, cc)
        except Exception:
            data['yidong_pr'] = '0'
        try:
            # 360权重
            data['so_pr'] = str(html.xpath('//a[@id="360_pr"]/img/@alt')[0])
            if data['so_pr'] == 'n':
                data['so_pr'] = self.get_pr(domain, 'so', rn, cc)
        except Exception:
            data['so_pr'] = '0'
        try:
            # 神马权重
            data['shenma_pr'] = str(html.xpath('//a[@id="sm_pr"]/img/@alt')[0])
            if data['shenma_pr'] == 'n':
                data['shenma_pr'] = self.get_pr(domain, 'sm', rn, cc)
        except Exception:
           data['shenma_pr'] = '0'
        try:
            # 搜狗权重
            data['sogou_pr'] =str( html.xpath('//a[@id="sogou_pr"]/img/@alt')[0])
            if data['sogou_pr'] == 'n':
                data['sogou_pr'] = self.get_pr(domain, 'sogou', rn, cc)
        except Exception:
           data['sogou_pr']  = '0'
        # data['html'] = r.text
        return data



if __name__ == '__main__':
    ym = sys.argv[1]
    o = AiZhan()
    res = o.get_info(ym)
    print(json.dumps(res))

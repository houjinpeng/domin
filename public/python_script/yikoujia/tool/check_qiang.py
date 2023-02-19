import time
import re
import threading,queue
import redis
import requests
from dbutils.pooled_db import PooledDB
import pymysql
from tool.longin import Login
from tool.get_min_gan_word import get_mingan_word

mysql_pool_conf = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '123456',
    'db': 'domain',
    'creator': pymysql,
    'cursorclass': pymysql.cursors.DictCursor,
}

db_pool = PooledDB(**mysql_pool_conf)
# proxies = {
#     "http": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
#     "https": "http://user-sp68470966:maiyuan312@gate.dc.visitxiangtan.com:20000",
# }
# 连接redis
redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)

# 初始化敏感词
words = get_mingan_word()

proxy_queue = queue.Queue()
def get_proxy():
    while True:
        if proxy_queue.qsize()> 20:
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
            print(f'获取墙错误51行：{e}')
            continue

threading.Thread(target=get_proxy).start()
class Qiang():
    def __init__(self):
        self.url = 'https://www.juming.com/hao/'
        self.key = ''
        self.ct = ''
        self.s = requests.session()
        self.chinac_s = requests.session()
        self.get_proxy()
        self.token = ''
        self.auth = ''
        self.session = ''


    # 设置代理
    def get_proxy(self):
        try:
            ip =proxy_queue.get()
            if ip == None:
                print('查找墙 没有ip可用啦 快快ip安排~~~~~')
                time.sleep(5)
                return self.get_proxy()
            proxies = {
                'http': f'http://{ip}',
                'https': f'http://{ip}'
            }
            self.s = requests.session()
            self.proxies = proxies

            # self.s.proxies.update(proxies)

            return proxies
        except Exception as e:
            time.sleep(2)
            return None

    def request_handler(self, url,count=0):
        try:

            headers = {
                "cookie":self.ct ,
                "accept": "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9",
                "accept-encoding": "gzip, deflate, br",
                "accept-language": "zh-CN,zh;q=0.9",
                "cache-control": "no-cache",
                "pragma": "no-cache",
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
            # resp = requests.get(url,headers=headers,timeout=7,proxies=dt_proxies)
            # resp = requests.get(url,headers=headers,timeout=7)
            resp = self.s.get(url,headers=headers,timeout=7)

            return resp
        except Exception as e:
            self.get_proxy()
            # if count > 5:
            #     return None
            print(f'获取被墙信息失败 {e}')
            # time.sleep(2)
            return self.request_handler(url,count+1)

    def verify_code(self,domain):
        try:
            url = 'https://www.juming.com/hao/'+domain

            token = requests.get(f'http://127.0.0.1:5001/get_token').json()

            data = {
                'token':token['token'],
                'sid':token['session'],
                'sig':token["auth"],
            }
            headers = {
                "accept": "application/json, text/javascript, */*; q=0.01",
                "accept-encoding": "gzip, deflate, br",
                "accept-language": "zh-CN,zh;q=0.9",
                "cache-control": "no-cache",
                "pragma": "no-cache",
                "sec-ch-ua": "\"Chromium\";v=\"106\", \"Google Chrome\";v=\"106\", \"Not;A=Brand\";v=\"99\"",
                "sec-ch-ua-mobile": "?0",
                "sec-ch-ua-platform": "\"Windows\"",
                "sec-fetch-dest": "document",
                "sec-fetch-mode": "navigate",
                "sec-fetch-site": "same-origin",
                "sec-fetch-user": "?1",
                "upgrade-insecure-requests": "1",
                "origin":'https://www.juming.com',
                "referer":url,
                "user-agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36"
            }
            # r = requests.post(url,data=data,headers=headers,timeout=3,proxies=dt_proxies)
            r = self.s.post(url,data=data,headers=headers,timeout=3)
            if r.json()['code'] == 1:
                # pass
                self.ct = 'ct='+r.cookies._cookies['www.juming.com']['/']['ct'].value
                # cookie = f'{cookie.split(";")[0]};ct={ct}'
                # self.set_cookie(cookie)
            else:
                print('重新验证滑动验证码~')
                return self.verify_code(domain)

        except Exception as e:
            time.sleep(2)
            print(f'解除验证码失败：{e}')
            return self.verify_code(domain)

    def get_token(self,domain):
        try:
            url = 'https://www.juming.com/hao/' + domain
            resp = self.request_handler(url)

            if '抱歉，此次操作需要完成下方验证后方可继续' in resp.text:
                r = self.verify_code(domain)
                return self.get_token(domain)
            self.key = re.findall("key='(.*?)'", resp.text)[0]
        except Exception as e:
            print(f'检测墙错误 180行：{e}')
            self.get_proxy()
            return self.get_token(domain)

    #检查被墙
    def get_qiang_data(self, domain):
        if self.key == '':
            self.get_token(domain)
            return self.get_qiang_data(domain)
        domain = domain.replace(".","_").lower()
        qiang_url = f'https://www.juming.com/hao/cha_d?do=qiang&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_qiang_data(domain)
        print(resp_data.json())
        return resp_data.json()

    #微信检测
    def get_wx_data(self, domain):
        if self.key == '':
            self.get_token(domain)
            return self.get_wx_data(domain)
        domain = domain.replace(".","_").lower()
        qiang_url = f'https://www.juming.com/hao/cha_d?do=weixin&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_wx_data(domain)

        return resp_data.json()

    #qq检查
    def get_qq_data(self, domain):
        if self.key == '':
            self.get_token(domain)
            return self.get_qq_data(domain)
        domain = domain.replace(".","_").lower()
        qiang_url = f'https://www.juming.com/hao/cha_d?do=qqjc&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_qq_data(domain)

        return resp_data.json()

    #备案黑名单
    def get_beian_hmd_data(self, domain):
        if self.key =='':
            self.get_token(domain)
            return self.get_beian_hmd_data(domain)
        domain = domain.replace(".","_").lower()
        qiang_url = f'https://www.juming.com/hao/cha_d?do=beian_hmd&ym={domain}&key={self.key}'
        resp_data = self.request_handler(qiang_url)

        if resp_data == None:
            return None
        elif resp_data.json()['code'] == -1:
            self.get_token(domain)
            return self.get_beian_hmd_data(domain)

        return resp_data.json()

    #检测是否有建站记录
    def get_beian_data(self,domain):
        csrf = self.get_csrf(domain)
        data = self.get_icp(domain, '', csrf, '', '')
        if data == None:
            return self.get_beian_data(domain)
        return data

    def get_csrf(self,domain):
        try:
            url = "http://www.chaicp.com/frontend_tools/getCsrf"

            payload = f"ym={domain}\r\n"
            headers = {
                'Accept': 'application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding': 'gzip, deflate',
                'Cache-Control': 'no-cache',
                'Connection': 'keep-alive',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Host': 'www.chaicp.com',
                'Origin': 'http://www.chaicp.com',
                'Pragma': 'no-cache',
                'Referer': f'http://www.chaicp.com/icp/{domain}',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
                'X-Requested-With': 'XMLHttpRequest'
            }

            response = self.chinac_s.post(url, headers=headers, data=payload,timeout=10,proxies=self.proxies).json()
            if response['code'] == -1:
                print(f'重新请求 csrf {response["msg"]} {self.proxies}')
                # self.s = requests.session()
                self.get_proxy()
                self.chinac_s = requests.session()
                return self.get_csrf(domain)

            return response
        except Exception as e:
            self.get_proxy()
            self.chinac_s = requests.session()
            return self.get_csrf(domain)


    def get_icp(self, domain, token, response, authenticate, sessionid):
        try:
            url = "http://www.chaicp.com/frontend_tools/getIcp"
            if (response['code'] == -1):
                return None
            payload = {'url': domain,
                       'token': token,
                       'csrf': response['data'],
                       'authenticate': authenticate,
                       'sessionid': sessionid}

            headers = {
                'Accept': '*/*',
                'Accept-Encoding': 'gzip, deflate',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Cache-Control': 'no-cache',
                'Connection': 'keep-alive',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Host': 'www.chaicp.com',
                'Origin': 'http://www.chaicp.com',
                'Pragma': 'no-cache',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
                'X-Requested-With': 'XMLHttpRequest'
            }

            result = self.chinac_s.request("POST", url, headers=headers, data=payload,timeout=4,proxies=self.proxies).json()
            if result['code'] == 2001:
                self.chinac_s = requests.session()
                return self.get_icp(domain, self.token, response, self.auth, self.session)

            if result['code'] == 1:
                return result

            url = 'http://127.0.0.1:5001/get_token'
            token = requests.get(url).json()
            self.token, self.auth, self.session = token['token'], token['auth'], token['session']
            if token == '请更新token池':
                return None
            time.sleep(1)
            csrf = self.get_csrf(domain)
            return self.get_icp(domain, self.token, csrf, self.auth, self.session)

        except Exception as e:
            print(f'检测建站历史335行错误：{e}')
            return self.get_icp(domain, self.token, response, self.auth, self.session)

if __name__ == '__main__':
    q = Qiang()


    ym_list = ["iseeyouopticaL.com",
"acooLcustomer.com",
"jmbie.com",
"Lyc002.com",
"78Lhj.com",
"ca-creation.com",
"9103game.com",
"goto-mech.com",
"pc975.com",
"aLiyunfenqi.com",
"Lc137.com",
"ft2345.com",
"5515cp.com",
"pc976.com",
"ttL87.com",
"372game.com",
"shhchuangmu.com",
"pc736.com",
"ahhczdhyb.com",
"zccp7.com",
"ome-toho.com",
"hgcp666.com",
"yeezy-beLuga.com",
"8888Lf.com",
"ho678.com",
"Lc9931.com",
"carLosgandara.com",
"xiaoweixindai.com",
"pencereuzmani.com",
"yuanma518.com",
"94beauty.com",
"zxy521.com",
"24soLarterms.com",
"htp4.com",
"dongshan520.com",
"25zhan.com",
"knight66.com",
"hjx77.com",
"92youhuiquan.com",
"ntn5.com",
"ptscratch.com",
"nbtaide.com",
"cb7788.com",
"f7654.com",
"boyaai.com",
"shangpinhome.com",
"hongmu007.com",
"xiexie8.com",
"seanzhao.com",
"yifucon.com",
"0p0b.com",
"shou1quan.com",
"Lekuaiyun.com",
"cqyyit.com",
"mifenhome.com",
"myd-tech.com",
"minyinbank.com",
"shop10010.com",
"hxq001.com",
"aoruizhi.com",
"zeigao.com",
"aixiangchuan.com",
"jhske.com",
"sjmpf.com",
"iweixinqun.com",
"baidao100.com",
"ynjhjy.com",
"zhangjianjin.com",
"matrixdk.com",
"huijiamao.com",
"qqsy2.com",
"66yhj.com",
"tea-food.com",
"cnbcnet.com",
"hfdent.com",
"gouzhengpin.com",
"seeyou520.com",
"qqsy1.com",
"nk2019.com",
"jhrbkj.com",
"5gdog.com",
"rvwtp.com",
"zgtwpsc.com",
"wx9898.com",
"2026sf.com",
"gy09.com",
"go-123.com",]
    cunzai = 0
    bucunzai = 0
    for ym in ym_list:
        print(ym)
        print(q.get_qiang_data(ym))
        # print(q.get_wx_data(ym))
        # print(q.get_qq_data(ym))
        # print(q.get_beian_hmd_data(ym))
        # j = q.get_beian_data(ym)
        # print(j)
        # if j == None:
        #     print(f'ym :{ym} none')
        #     continue
        # elif j['data']['icp'] != '':
        #     cunzai+=1
        #     print(f'ym:{ym} 存在 ：{j}')
        # else:
        #     bucunzai+=1
        #     print(f'ym:{ym} 不存在 ：{j}')

        print('=='*10)


    print('=='*20)
    print(f'存在：{cunzai}')
    print(f'存在：{bucunzai}')


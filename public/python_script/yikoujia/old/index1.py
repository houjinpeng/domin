import re
import time, datetime
import configparser
import requests
from lxml import etree
import html
import json
import threading, queue
from tool.logger import Logger
from tool.longin import Login
from tool.get_beian import BeiAn
from tool.get_history import GetHistory
from tool.get_sogou import GetSougouRecord
from tool.get_beian import get_proxies
from old.verif_code import verif_api
from old.move_mouse import move

log = Logger('../conf/ym.log', level='debug')

config = configparser.ConfigParser()
logFile = r"./conf/setting.cfg"
config.read(logFile, encoding="utf-8")

class Juming():
    def __init__(self):
        self.screen_url = 'http://x.juming.com:9696/ykj/get_list'

        '''读配置文件'''
        self.config = configparser.ConfigParser()
        # 文件路径
        logFile = r"./conf/setting.cfg"
        self.config.read(logFile, encoding="utf-8")
        jm_setting = self.config.items('JMSEARCH')

        self.username = self.config.get('ACCOUNT', 'username')
        self.password = self.config.get('ACCOUNT', 'password')

        data_str = ''
        for jm in jm_setting:
            if jm[1] == '0':
                continue

            data_str = data_str + jm[0] + '=' + jm[1] + '&'
        self.data_str = data_str
        self.data_str = self.data_str.replace(',', '%2C')
        log.logger.debug(f'参数为：{self.data_str}')
        self.headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding': 'gzip, deflate',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Cookie': cookie,
            'Host': 'x.juming.com:9696',
            'Origin': 'http://x.juming.com:9696',
            'Pragma': 'no-cache',
            'Referer': 'http://x.juming.com:9696/ykj/',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36',
            'X-Requested-With': 'XMLHttpRequest'
        }
        threading.Thread(target=move).start()
        if self.config.get('soGou', 'is_comp_sogou') == '是':
            self.record = GetSougouRecord()

    # 解除保护
    def close_baohu(self, token, count=1):
        # 请求验证码
        url = 'http://x.juming.com:9696/user_baohu/close_baohu'

        # r = self.s.get('http://x.juming.com:9696/xcode?1648645870411')
        r = requests.get(f'http://x.juming.com:9696/xcode?{int(time.time()) * 1000}',
                         headers={'Cookie': cookie})
        with open('code.png', 'wb') as fw:
            fw.write(r.content)

        code = verif_api(uname='15211731111', pwd='esb104', img='code.png', typeid=3)
        data = {'csrf_token': token,
                'mmbhda': self.config.get("ACCOUNT", "baohu_name"),
                'pass': self.password,
                're_yzm': code[0]}

        try:
            # r = self.s.post(url,headers=self.headers,data=data,timeout=5)
            headers = {
                'Accept': 'application/json, text/javascript, */*; q=0.01',
                'Accept-Encoding': 'gzip, deflate',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Cache-Control': 'no-cache',
                'Connection': 'keep-alive',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Cookie': cookie,
                'Host': 'x.juming.com:9696',
                'Origin': 'http://x.juming.com:9696',
                'Pragma': 'no-cache',
                'Referer': 'http://x.juming.com:9696/ykj/shopcar',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36',
                'X-Requested-With': 'XMLHttpReques'
            }
            r = requests.post(url, headers=headers, data=data, timeout=5)
            data = json.loads(r.text)
            log.logger.debug(data)
        except Exception as e:
            if count > 5:
                return None
            return self.close_baohu(count + 1)

    # 判断是否退出登陆 退出登陆重新登陆 10秒一次
    def is_login(self):
        while True:
            try:
                url = 'http://x.juming.com:9696/'
                r = requests.get(url, headers=self.headers, timeout=3)
                if self.username not in r.text:
                    s, msg,a = Login().login(self.username, self.password)
                    global cookie
                    cookie = 'PHPSESSID=' + s.cookies._cookies['old.juming.com']['/']['PHPSESSID'].value
                    log.logger.debug('重新登陆')
                    time.sleep(100)
                time.sleep(100)
            except Exception as e:
                pass
            time.sleep(100)

    # 下单
    def palce(self, domain, token=None):
        token_url = 'http://x.juming.com:9696/ykj/shopcar'

        try:
            data_parm = f"id%5B%5D={domain['id']}"
            headers = {
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'Accept-Encoding': 'gzip, deflate',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Cache-Control': 'no-cache',
                'Connection': 'keep-alive',
                'Content-Length': '17',
                'Content-Type': 'application/x-www-form-urlencoded',
                'Cookie': cookie,
                'Host': 'x.juming.com:9696',
                'Origin': 'http://x.juming.com:9696',
                'Pragma': 'no-cache',
                'Referer': 'http://x.juming.com:9696/ykj/39963828/',
                'Upgrade-Insecure-Requests': '1',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.3'
            }
            resp = requests.post(token_url, headers=headers, data=data_parm)
            # resp = self.s.post(token_url,headers=self.headers,data=data_parm)
            token = re.findall("var token='(.*?)'", resp.text)[0]
            jq = re.findall('"qian":"(\d+?)"', resp.text)[0]
        except Exception as e:
            return self.palce(domain)

        if_url = f'http://x.juming.com:9696/main/if_mmbh?r=1&_={int(time.time()) * 1000}'
        if_headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding': 'gzip, deflate',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive',
            'Cookie': cookie,
            'Host': 'x.juming.com:9696',
            'Pragma': 'no-cache',
            'Referer': 'http://x.juming.com:9696/ykj/shopcar',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36',
            'X-Requested-With': 'XMLHttpReques'
        }

        try:
            resp = requests.get(if_url, headers=if_headers)
            data_if = json.loads(resp.text)
            if '对不起，由于您帐户正在保护中' in data_if['msg']:
                self.close_baohu(data_if['token'])
        except Exception as e:
            return self.palce(domain)

        url = "http://x.juming.com:9696/ykj/buy"

        data =  f"id={domain['id']}&jg={jq}&hdbj=&t=&csrf_token={token}"
        headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Encoding': 'gzip, deflate',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Cache-Control': 'no-cache',
            'Connection': 'keep-alive',
            'Content-Length': str(len(data)),
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Cookie': cookie,
            'Host': 'x.juming.com:9696',
            'Origin': 'http://x.juming.com:9696',
            'Pragma': 'no-cache',
            'Referer': 'http://x.juming.com:9696/ykj/shopcar',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/99.0.4844.84 Safari/537.36',
            'X-Requested-With': 'XMLHttpRequest'
        }

        try:
            resp_buy = requests.post(url, headers=headers, data=data)
            # resp_buy = self.s.post(url,headers=headers,data=data)
            data = json.loads(resp_buy.text)
            log.logger.debug(data)
            if '对不起，由于您帐户正在保护中' in data['msg']:
                self.close_baohu(data['token'])
                return self.palce(domain, token=data['token'])
            elif '该域名购买后' in data['msg']:
                log.logger.debug(f'域名{domain["ym"]}出现赎回现象 判断是否购买')

                if self.config.get('OTHER','is_buy') == '是':
                    log.logger.debug(f'域名{domain["ym"]} 购买中')
                    tongyi_data = f"id={domain['id']}&jg={jq}&hdbj=&t=&csrf_token={token}&tongyi=3"
                    resp_buy = requests.post(url, headers=headers, data=tongyi_data)
                    data = json.loads(resp_buy.text)
                    log.logger.debug(data)
                else:
                    log.logger.debug(f'域名{domain["ym"]}不买')

            pass
        except Exception as e:
            pass

    def save_out_ym(self):
        while True:
            if out_ym_quque.empty():
                time.sleep(5)
            out_ym = out_ym_quque.get()
            ym = out_ym['ym']
            id = out_ym['id']
            cause = out_ym['cause']
            try:
                with open('../conf/out_ym.txt', 'a', encoding='utf-8') as fw:
                    fw.write(ym + ',' + id +','+cause + '\n')
            except Exception:
                pass

    #购买域名的线程
    def buy_ym(self):
        is_buy = self.config.get('OTHER','buy')

        while True:
            if buy_ym_quque.empty():
                time.sleep(1)
                continue
            domain = buy_ym_quque.get()
            if is_buy == '是':
                self.palce(domain)
            else:
                log.logger.info(f'{domain["ym"]} 需要购买')
                with open('../palce.txt', 'a', encoding='utf-8') as fw:
                    fw.write(domain['ym'] + '\n')

    def request_heandler(self, url, headers, method='get', page=1,count=0):
        if method == 'post':
            parm_data = self.data_str + f'page={page}'
            try:

                r = requests.post(url, data=parm_data, headers=headers, timeout=10)
                if r.status_code != 200:
                    if count > 5:
                        return None
                    return self.request_heandler(url, headers, method, page,count+1)
                elif '验证' in json.loads(r.text)['msg']:
                    self.huadong_chcek()
                    return self.request_heandler(url, headers, method, page)

                elif '请求过于频繁' in json.loads(r.text)['html']:
                    return self.request_heandler(url, headers, method, page)
                else:
                    return r
            except Exception as e:
                if count > 5:
                    return None
                return self.request_heandler(url, headers, method, page,count+1)

        else:
            try:
                r = requests.get(url, headers=headers, timeout=3)
                return r
            except Exception:
                if count > 5:
                    return None
                return self.request_heandler(url, headers, method, page,count+1)

    def parse_page(self, resp):
        data = json.loads(resp.text)
        all_ym = data['count']
        all_page = int(int(all_ym) / int(self.config.get('JMSEARCH', 'psize')))
        if all_page == 0:
            all_page = 1
        log.logger.debug(f'本次共查询出{all_ym}条记录  共{all_page}页')
        for i in range(1, int(all_page) + 1):
            page_q.put(i)

    #获取域名
    def get_domain(self):
        while not page_q.empty():
            i = page_q.get()
            # 发送请求
            # print(f'请求第{i}页数据')
            r = self.request_heandler(self.screen_url, self.headers, method='post', page=i)
            if r == None:
                continue
            if '对不起' in json.loads(r.text)['msg']:
                break

            html_str = json.loads(r.text)['html']
            text = html.unescape(html_str)
            e = etree.HTML(text)
            all_data = e.xpath("//tr[contains(@id,'ymlist')]")
            # 解析列表
            for data in all_data:
                try:
                    ym = data.xpath(".//a[contains(@class,'a_ym')]/text()")[0]
                    id = re.findall('/(\d+?)/', data.xpath(".//a[contains(@class,'a_ym')]/@href")[0])[0]
                    data = {'ym': ym, 'id': id}
                except Exception as e:
                    log.logger.debug(1)
                    continue

                if ym_dict.get(data['ym']) == None and '*' not in ym:
                    log.logger.debug(data['ym'])
                    ym_queue.put(data)
                    ym_dict[ym] = {}
                    ym_dict[ym] = {'id':id,'domain':ym}

    # 备案对比
    def comp_beian(self,domain,beian):
        domain['ym'] = domain['domain']
        beian_info = beian.beian_info(domain['domain'])
        if beian_info == None:
            # domain['cause'] = '没有备案'
            # out_ym_quque.put(domain)
            # log.logger.debug(f'查询备案失败，重新放入列表重新查询  {domain["domain"]}')
            # work_queue.put(domain)
            return self.comp_beian(domain,beian)

        try:
            # 没有备案的过滤
            if len(beian_info['params']['list']) == 0:
                domain['cause'] = '没有备案'
                log.logger.debug(f' 域名为：{domain["ym"]} 备案 {domain["cause"]}')
                out_ym_quque.put(domain)
                return False
            xingzhi = beian_info['params']['list'][0]['natureName']
            if xingzhi in self.config.get('beian','beian_xingzhi').split(','):
                domain['cause'] = f'域名性质为：{xingzhi}'
                log.logger.debug(f'域名为：{domain["ym"]} 备案 域名性质为：{xingzhi}')
                out_ym_quque.put(domain)
                return False

            beiai_num = beian_info['params']['list'][0]['serviceLicence']
            # 判断备案号 大于自定义号码的过滤
            if int(self.config.get('beian', 'beian_num')) < int(beiai_num.split('-')[1]):
                domain['cause'] = f"备案号为：{beiai_num.split('-')[1]} 您设置的备案号为：{self.config.get('beian', 'beian_num')}"
                log.logger.debug(f'域名为：{domain["ym"]} 备案 {domain["cause"]}')
                out_ym_quque.put(domain)
                return False

            # 判断历史审核时间
            up_time = beian_info['params']['list'][0]['updateRecordTime']
            day = (datetime.datetime.now() - datetime.datetime.strptime(up_time, '%Y-%m-%d %H:%M:%S')).days
            if day <= int(self.config.get('beian','out_day')):
                domain['cause'] = f'备案历史审核时间为：{day}天  您设置的审核时间为：{self.config.get("beian","out_day")}天'
                log.logger.debug(f'域名为：{domain["ym"]} 备案 {domain["cause"]}')
                out_ym_quque.put(domain)
                return False

        except Exception as e:
            domain['cause'] = str(e)
            out_ym_quque.put(domain)
            log.logger.error(e)
            return False

    #对比敏感词
    def check_mingan(self,history_data):
        try:
            if history_data['data'] == None:
                log.logger.debug(f'剩余任务：{token_queue.qsize()}   历史对比完毕 没有历史')
                return True
        except Exception as e:
            return '没有历史'
        title_list = [title['bt'] for title in history_data['data']]

        for word in mg_word:
            for title in title_list:
                if word in title:
                    return word
        return True

    # 获取历史数据进行对比
    def get_history_comp(self,domain):
        # 保存到完成域名中
        history_data = history.get_history({'ym':domain['domain'],'token':domain['token']})
        #判断是否对比关键词
        if self.config.get('history','is_comp_word') == '是':
            is_mingan = self.check_mingan(history_data)
            if is_mingan != True:
                out_ym_quque.put({'cause':is_mingan,'ym':domain['domain'],'id':domain['id']})
                return False
        #判断是否对比年龄
        if self.config.get('history','is_comp_age') == '是':
            try:
                age = history.get_age(domain)
                if int(age['data']['nl']) < int(self.config.get('history','age')):
                    out_ym_quque.put({'cause': '历史小于设置年龄', 'ym': domain['domain'], 'id': domain['id']})
                    return False
            except :
                out_ym_quque.put({'cause': '历史小于设置年龄', 'ym': domain['domain'], 'id': domain['id']})
                return False
        log.logger.info(f'通过域名：{domain["domain"]}')
        return True

    #注册商对比
    def ckeck_zhuceshang(self,domain):
        try:
            url = f'http://x.juming.com:9696/ykj/{domain["id"]}/'
            r = requests.get(url)
            e = etree.HTML(r.text)
            all_str = ' '.join(e.xpath('//div[@class="buy detail"]//li//text()'))
            bao_list = self.config.get('zhuceshang','baohan').split(',')
            for bao in bao_list:
                if bao in all_str:
                    return True
            return False
        except :
            return self.ckeck_zhuceshang(domain)

    def huadong_chcek(self):
        try:
            get_token_url = 'http://127.0.0.1:5001/get_token'
            r = requests.get(get_token_url).json()
            if '请' in r:
                time.sleep(10)
                return self.huadong_chcek()

            url = 'http://x.juming.com:9696/ykj/get_list'
            data = {
                'token': r['token'],
                'sid': r['session'],
                'sig': r['auth'],
            }
            resp = requests.post(url,data=data,headers=self.headers,timeout=5)
            # cookie = a+ re
            r_json = resp.json()
            if r_json['msg'] == 'ok':
                return True
            else:
                print(r_json)
                time.sleep(10)
                return self.huadong_chcek() 
        except :
            pass

    #监控域名数量
    def jiankong_ym_count(self):
        global ym_count
        while True:
            r = self.request_heandler(self.screen_url, self.headers, method='post')
            if r == None:
                log.logger.error('域名没有变化')
                time.sleep(int(self.config.get('OTHER', 'refresh')))
                continue
            data = json.loads(r.text)
            all_ym = data['count']
            if int(all_ym) == ym_count:
                log.logger.debug(f'域名没有变化  继续监听 当前总数量：{all_ym}')
                time.sleep(int(self.config.get('OTHER','refresh')))
                continue
            else:
                ym_count = int(all_ym)
                log.logger.debug(f'本次共查询出{ym_count}条记录')
                # 解析页数  开启线程抓取页数内的域名
                self.parse_page(r)
                t = []
                for i in range(20):
                    t.append(threading.Thread(target=self.get_domain))
                for j in t:
                    j.start()
                for j in t:
                    j.join()


            if self.config.get('history', 'is_comp_history') == '是':
                # 先获取token
                ls = []
                while not ym_queue.empty():
                    dom = ym_queue.get()
                    ls.append(dom['ym'])
                    if len(ls) >= 2000:
                        token_list = history.get_token(ls)
                        # 放入域名列表中
                        for d in token_list:
                            ym_dict.get(d['ym'])['token'] = d['token']
                            ym_dict.get(d['ym'])['ym'] = d['ym']
                            work_queue.put(ym_dict.get(d['ym']))
                        ls.clear()
                if len(ls) < 2000 and len(ls) != 0:
                    token_list = history.get_token(ls)
                    # 放入域名列表中
                    for d in token_list:
                        ym_dict.get(d['ym'])['token'] = d['token']
                        ym_dict.get(d['ym'])['ym'] = d['ym']
                        work_queue.put(ym_dict.get(d['ym']))
                    ls.clear()

            else:
                while not ym_queue.empty():
                    domain = ym_queue.get()
                    work_queue.put(domain)

    def work(self,beian):
        while True:
            if work_queue.empty():
                time.sleep(3)
                continue
            #获取域名
            domain_data = work_queue.get()
            log.logger.info(f'剩余任务:{work_queue.qsize()}  域名开始对比：{domain_data["ym"]}')
            try:
                domain_data['domain'] = domain_data['ym']
                if ym_dict.get(domain_data['domain']) == None:
                    continue
            except :
                continue
            #对比历史 备案 搜狗
            if self.config.get('history', 'is_comp_history') == '是':
                if self.get_history_comp(domain_data) == False:
                    continue

            if self.config.get('beian', 'is_comp_beian') == '是':
                if self.comp_beian(domain_data,beian) == False:
                    continue
            if self.config.get('soGou', 'is_comp_sogou') == '是':
                if self.record.check_sogou(domain_data['domain'],self.config.get('soGou','sogou_record'),self.config.get('soGou','kuaizhao_time')) == False:
                    out_ym_quque.put({'ym':domain_data['domain'],'id':domain_data['id'],'cause':'搜狗判断未通过'})
                    continue
            if self.config.get('zhuceshang', 'is_comp_zhuceshang') == '是':
                if self.ckeck_zhuceshang(domain_data) == False:
                    out_ym_quque.put({'ym': domain_data['domain'], 'id': domain_data['id'], 'cause': '注册商包含非法字符串'})
                    continue

            buy_ym_quque.put(domain_data)
            out_ym_quque.put({'ym': domain_data['domain'], 'id': domain_data['id'], 'cause': '需要购买'})

    def index(self):
        # 启动保存过滤域名线程
        threading.Thread(target=self.save_out_ym).start()
        #启动买入域名线程
        threading.Thread(target=self.buy_ym).start()
        # 启动监控是否退出线程
        threading.Thread(target=self.is_login).start()
        # 启动监控 域名数量是否变化线程
        threading.Thread(target=self.jiankong_ym_count).start()
        for i in range(int(self.config.get('OTHER','task_num'))):
            beian = BeiAn()
            #启动任务进程
            threading.Thread(target=self.work,args=(beian,)).start()



if __name__ == '__main__':
    log.logger.debug('启动')
    #登陆 获取cookie
    s, msg,a = Login().login(config.get('ACCOUNT', 'username'),config.get('ACCOUNT', 'password'))
    cookie = 'PHPSESSID=' + s.cookies._cookies['old.juming.com']['/']['PHPSESSID'].value
    # 所有域名总数
    ym_count = 0
    # 初始化敏感词
    mg_word = []
    with open('../conf/敏感词.txt', 'r', encoding='utf-8') as fr:
        data = fr.readlines()
    [mg_word.append(d.strip()) for d in data]

    ym_dict = {}
    # 初始化已经抓取过的域名


    with open('../conf/out_ym.txt', 'r', encoding='utf-8') as fr:
        data = fr.readlines()
    for d in data:
        try:
            ym_dict[d.split(',')[0]] = {'id':d.split(',')[1].strip(),'domain':d.split(',')[0]}
        except Exception as e:
            pass

    # token 队列  保存domain
    token_queue = queue.Queue()
    # 需要保存的域名队列
    ym_queue = queue.Queue()

    #任务队列
    work_queue = queue.Queue()

    # 页数队列
    page_q = queue.Queue()
    # 备案队列
    beian_queue = queue.Queue()
    # 完成队列
    out_ym_quque = queue.Queue()

    #需要购买的队列
    buy_ym_quque = queue.Queue()

    # new 得到历史数据的class
    history = GetHistory()


    # 启动代理
    threading.Thread(target=get_proxies).start()




    # # 启动一个线程滑动验证码
    # threading.Thread(target=move).start()
    log.logger.debug('开始任务')
    #启动程序
    Juming().index()

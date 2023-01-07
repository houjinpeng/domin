import datetime
import time
import difflib
import requests
import json
import redis
redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)

from dbutils.pooled_db import PooledDB
from conf.config import *

db_pool = PooledDB(**mysql_pool_conf)
conn = db_pool.connection()
cur = conn.cursor()

cur.execute("select * from ym_system_config where `name`='ip'")
ip_data = cur.fetchone()
ip = ip_data['value']
cur.close()
conn.close()



class GetHistory():
    def get_token(self, domain_list):
        domain_token = []
        url = f'http://127.0.0.1:5001/get_token'
        r = requests.get(url)
        token = json.loads(r.text)
        headers = {
            'Accept': 'application/json, text/javascript, */*; q=0.01',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Cache-Control': 'no-cache',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Host': '47.56.160.68:81',
            'Pragma': 'no-cache',
            'Proxy-Connection': 'keep-alive',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
            'Origin': 'http://47.56.160.68:81',
            'Referer': 'http://47.56.160.68:81/piliang/',
            'X-Requested-With': 'XMLHttpRequest',
        }

        api = "http://47.56.160.68:81/api.php?sckey=y"
        data = {"ym": "\n".join(domain_list),
                "authenticate": token["auth"],
                "token": token['token'],
                "sessionid": token['session']
                }
        try:
            res = requests.post(url=api, data=data, headers=headers)
            res = res.json()
            if res['code'] != 1:

                print("错误: ", res['msg'])

                return self.get_token(domain_list)

            for item in res['data']:
                domain_token.append(item)
            return domain_token
        except Exception as e:
            print("[209]", e)
            return self.get_token(domain_list)

    def get_history(self,domain):
        try:

            headers = {
                'Accept': '*/*',
                'Accept-Language': 'zh-CN,zh;q=0.9',
                'Proxy-Connection': 'keep-alive',
                'Pragma': 'no-cache',
                'Cache-Control': 'no-cache',
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Origin': 'http://47.56.160.68:81',
                'Host': '47.56.160.68:81',
            }

            data = {
                'ym': domain['ym'],
                'xq': 'y',
                'page': '1',
                'limit': '20',
                'token':domain['token'],
                'group': '1',
                'nian': ''
            }
            response_detail = requests.post('http://47.56.160.68:10247/api.php', data=data, verify=False,headers=headers, timeout=3)
            r = response_detail.json()

            results = {
                "count": r.get('count'),
                "data":r.get('data'),
                "code": r.get('code'),
                "msg": r.get('msg'),
            }

            # print(results)
            return results
        except:
            return False

    def get_age(self,domain):
        headers = {
            'Accept': '*/*',
            'Accept-Language': 'zh-CN,zh;q=0.9',
            'Proxy-Connection': 'keep-alive',
            'Pragma': 'no-cache',
            'Cache-Control': 'no-cache',
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'Origin': 'http://47.56.160.68:81',
            'Host': '47.56.160.68:81',
        }
        data = {
            'ym': domain['ym'],
            'token': domain['token'],
            'qg': ''
        }
        try:
            response_detail = requests.post('http://47.56.160.68:10247/api.php', data=data, verify=False,
                                            headers=headers, timeout=3)
            results = response_detail.json()

            return results
        except Exception as e:
            return self.get_age(domain)

    #获取中文标题数量
    def get_zh_title_num(self, history_data):
        '''
        :param history_data: json 历史
        :return: 返回中文标题数量
        '''
        num = 0
        try:
            for data in history_data['data']:
                if data['yy'] == '中文':
                    num += 1
            return num
        except Exception as e:
            return 0

    #获取五年内建站次数
    def get_five_year_num(self, history_data):
        '''
        :param history_data: 历史json
        :return:  返回五年内建站次数
        '''
        try:
            now_year = datetime.datetime.now().year
            num = 0
            for data in history_data['data']:
                year = int(data['timestamp'][:4])
                if now_year - 5 <= year:
                    if data['yy'] != '中文':
                        continue
                    num += 1
            return num
        except Exception as e:
            return 0

    #获取最长连续存档时间
    def get_lianxu_cundang_time(self, history_data, year_num=0):
        '''
        获取连续年份时间
        :param history_data: 历史json
        :param year_num: 区间num
        :return: 最大连续时间
        '''
        num = 0
        old_year = 0
        max_lianxu_years = 0
        now_year = datetime.datetime.now().year
        try:
            for data in history_data['data']:
                year = int(data['timestamp'][:4])
                if year_num != 0:
                    if now_year - year_num > year:
                        continue

                if old_year == 0:
                    # if is_comp_english == '否':
                    # if data['yy'] != '中文' or data['bt'] == '':
                    #     num = 0
                    #     continue
                    num += 1
                    max_lianxu_years += 1

                if year + 1 == old_year:
                    # if is_comp_english == '否':
                    # if data['yy'] != '中文' or data['bt'] == '':
                    #     num = 0
                    #     continue

                    num += 1
                    if num > max_lianxu_years:
                        max_lianxu_years += 1

                else:
                    # if is_comp_english == '否':
                    # if data['yy'] != '中文' or data['bt'] == '':
                    #     num = 0
                    #     continue
                    num = 1

                old_year = year
            return max_lianxu_years
        except Exception as e:
            return 0

    #获取统一度
    def get_tongyidu(self, history_data):
        num = 1
        try:
            if history_data['data'] == None:
                return 0
            xiangsidu = 0
            for i in range(len(history_data['data'])):
                for j in range(i + 1, len(history_data['data'])):
                    num += 1
                    xiangsidu += difflib.SequenceMatcher(None, history_data['data'][i],
                                                         history_data['data'][j]).quick_ratio()

            xiangsidu = int(xiangsidu * 100 / num)
            return int(xiangsidu)
        except Exception as e:
            return 0

'''
ym: baidu.com
xq: y
page: 1
limit: 20
token: cd837
group: 1
nian: 
'''

if __name__ == '__main__':
    ds = ["Lefincf.com",
"kuyougo.com",
"haiyudb.com",
"bqiapp.com",
"aimazhijia.com",
"6qianmi.com",
"hzbpg.com",
"ccLc18.com",
"iseeyouopticaL.com",
"Lypdyy.com",
"Ledruanjian.com",
"sxmaiLisen.com",
"tLhsm.com",
"kamiduihuan.com",
"dkdzshg.com",
"qixianw.com",
"yunduanoffice.com",
"jtpjhcmak.com",
"zhaoruanwang.com",
"houyishe.com",
"5515cp.com",
"ttL87.com",
"shhchuangmu.com",
"pc736.com",
"zccp7.com",
"hannatu.com",
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
"semvads.com",
"25zhan.com",
"knight66.com",
"hjx77.com",
"92youhuiquan.com",
"jinLiyu2016.com",
"ntn5.com",
"dajuguan123.com",
"haokaishi365.com",
"beiLizeng.com",
"juyiyou365.com",
"cdkangrun.com",
"Liushuiyi.com",
"fjnLLy.com",
"anzhi56.com",
"ddongLai.com",
"yyjbgjj.com",
"nkw8.com",
"73111222.com",
"dajiangcad.com",
"wjctgc.com",
"54jiujun.com",
"zishinong.com",
"diaobuLi.com",
"ptscratch.com",
"tj-xajh.com",
"nbtaide.com",
"cb7788.com",
"f7654.com",
"boyaai.com",
"wui520.com",
"shangpinhome.com",
"hongmu007.com",
"xiexie8.com",
"a9325.com",
"seanzhao.com",
"jinseLuoxuan.com",
"yifucon.com",
"0p0b.com",
"gzshengbangzc.com",
"shou1quan.com",
"Lekuaiyun.com",
"cqyyit.com",
"cts9999.com",
"mifenhome.com",
"myd-tech.com",
"Leyutiyanguan.com",
"chinaszm.com",
"minyinbank.com",
"fs0351.com",
"shop10010.com",
"hxq001.com",
"aoruizhi.com",
"tangsanwang.com",
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
"jsgw365.com",
"fLz588.com",
"qqsy1.com",
"yuta520.com",
"nk2019.com",
"jhrbkj.com",
"5gdog.com",
"rvwtp.com",
"ruifaposuiji.com",
"zgtwpsc.com",
"san55555.com",
"iLovebabyup.com",
"wx9898.com",
"sdo-ent.com",
"2026sf.com",
"gy09.com",
"go-123.com",
"mu-chiLd.com",
"yjyLighting.com",
"vipsue.com",
"czjsdfw.com",
"horuida.com",
"nyxdyy.com",
"dongjiafruits.com",
"chaoxing123.com",
"szjiyuecheng.com",
"gzxwdmy.com",
"jiediLia168.com",
"muLticam3.com",
"fensishop.com",
"myLmchina.com",
"pinweijianshe.com",
"cshsksgs.com",
"chengzhifenqi.com",
"jhhw8888.com",
"aifang100.com",
"vanyunart.com",
"exwebapp.com",
"chinajianjia.com",
"shengLiangyuan.com",
"Liujiajixie.com",
"pianyixiezhen.com",
"qichegongyuan.com",
"ttqnykj.com",
"LiveweLLchina.com",
"juhuahuizhong.com",
"Lbsrh.com",
"hotmetaL0769.com",
"shenqixiazi.com",
"tanggurisheng.com",
"nbgic.com",
"xmjx520.com",
"tzxzmcc.com",
"cqapct.com",
"shanxihongLu.com",
"Langfangphoto.com",
"aomingzhanshi.com",
"duoshengdoors.com",
"591jianbao.com",
"wmzcz.com",
"apmjy.com",
"kaipan6.com",
"yxgs888.com",
"ziyuhengyuan.com",
"01hrs.com",
"xcswcLub.com",
"hcyyxm.com",
"shcw666.com",
"shijihf.com",
"jhhuadan.com",
"scsbwL.com",
"scjxydL.com",
"epdoo.com",
"sjbdbg.com",
"sunrockhoteLs.com",
"wwwszco.com",
"kaiheibanLv.com",
"rizerbeer.com",
"etLLatex.com",
"cqbestcake.com",
"zzqiying.com",
"yutojt.com",
"haiyuetouch.com",
"yiyanzhongqing.com",
"xiaomaomimi.com",
"chengshibianjie.com",
"beijinghengyuan.com",
"ddg365.com",
"Lionshowcp.com",
"gkj1.com",
"aiadmob.com",
"dtLakj.com",
"uzhengtong.com",
"sybwdLsb.com",
"chengrunhb.com",
"xiaoxiongyoupin.com",
"gyjianxing.com",
"yfkxs.com",
"bcssjz.com",
"cqyyzzy.com",
"moiLngca.com",
"gkdanbao.com",
"mayouyouoiL.com",
"mountscm.com",
"naichakafeidian.com",
"goushij.com",
"smc-km.com",
"shenbiqm.com",
"yaLanzhu.com",
"hbxLzL.com",
"huiteng9999.com",
"shibaing.com",
"zhao-xiaoying.com",
"schuanda.com",
"9Liannet.com",
"best-nvr.com",
"xcgy-industriaL.com",
"niushangtong.com",
"Lekangganquan.com",
"kwzking.com",
"Laifusuye.com",
"junkangyiyuan.com",
"aLiyunfenqi.com",
"pintuocanyin.com",
"rijiehuo.com",
"easirhr.com",
"365xintouw.com",
"372game.com",
"ra5168.com",
"senjc.com",
"xmcsjz.com",
"aikaix.com",
"tianyigouwu.com",
"weixinLw.com",
"xuguangchem.com",
"huaishangpin.com",
"dingdongquanyue.com",
"myjzs777.com",
"ca-creation.com",
"syt-jk.com",
"js-kaipu.com",
"shoujio2o.com",
"fengdianchina.com",
"zgzhhc.com",
"zdxy66.com",
"Lzfcmy.com",
"twranchu.com",
"tongfu520.com",
"zhmrxxw.com",
"haorentouzi.com",
"guangrunpay.com",
"gz-eLan.com",
"sczqip.com",
"ypscansi.com",
"shanshanxieye.com",
"handeen.com",
"qmydsy.com",
"mefshow.com",
"qhzykjsb.com",
"pbhbzg.com",
"qiangtuogc.com",
"nniksw.com",
"zytxsd.com",
"zmchangjia.com",
"xmmoyan.com",
"bL0813.com",
"zkynf.com",
"schxky.com",
"zyhtms.com",
"keduart.com",
"engineconnector.com",
"yuanfavip.com",
"jmyunfa.com",
"Ldxkcy.com",
"zsc118.com",
"zjywsst.com",
"wuLiuzuche.com",
"anyangjingxuan.com",
"fengdiwjj.com",
"zhencaizx.com",
"souLcooLvip.com",
"yufaLL.com",
"guangmangcaifu.com",
"tacdjc.com",
"9103game.com",
"cqesLy.com",
"ft2345.com",
"znyfsy.com",
"boaicheng.com",
"baiLirz.com",
"Lyc002.com",
"xintengbz.com",
"23k3.com",
"haoyuanjm.com",
"qxjtsc.com",
"zzyhcy.com",
"chunchiLiuxiang.com",
"ddqiubite.com",
"auki-Led.com",
"jianxingt.com",
"ok0903.com",
"gz-refined.com",
"ahhczdhyb.com",
"tjcjbjgs.com",
"cqxsdcf.com",
"Lygbdznh.com",
"xiaoerdz.com",
"pc975.com",
"sh-ouxun.com",
"yftianxia.com",
"q66676.com",
"pc976.com",
"kaiyicn.com",
"paibaner.com",
"jzhk120.com",
"fuzhou-gupiao.com",
"chinese120.com",
"580665.com",
"nbyctx.com",
"hetongfk.com",
"Lishishikong.com",
"uniquenourishment.com",
"Lc137.com",
"aiLLnet.com",
"dongxufund.com",
"csqianbaidu.com",
"xincai-vip.com",
"yangfanLv.com",
"chaoyuerencai.com",
"wujunhong.com",
"zhengwenfang.com",
"yckpdz.com",
"bjneisheng.com",
"LcLc365.com",
"hkyueyan.com",
"gzytzs168.com",
"adbrjc.com",
"Lwban.com",
"dazhixx.com",
"zx147.com",
"49ksd.com",
"fdyLfw.com",
"fsxycj.com",
"jndouboshi.com",
"zwc580.com",
"oupinLvyehrz.com",
"yangchebaifen.com",
"itkebao.com",
"ynznfs.com",
"1994c.com",
"szyLkjzs.com",
"shqhjzgc.com",
"bbfeifan.com",
"sjzhtedu.com",
"fxddxf.com",
"92jiahao.com",
"scstzw.com",
"sxkdbps.com",
"xxpumps.com",
"jxgjggs.com",
"de-yin.com",
"cdhcdp.com",
"zzyysh.com",
"mcrunhai.com",
"jxesquire.com",
"ff-boxing.com",
"guanyunzb.com",
"hyLhmm.com",
"tuLitong.com",
"jmbie.com",
"jiamanrui.com",
"zuiyouLai.com",
"acooLcustomer.com",
"78Lhj.com",
"ccjLdd.com",
"goto-mech.com",
"",]
    h = GetHistory()
    for domain in ['baidu.com','baidu.com','baidu.com','baidu.com']:
        ls = h.get_token(ds)
        for do in ls:
            ls = h.get_history(do['ym'])



            print(do)

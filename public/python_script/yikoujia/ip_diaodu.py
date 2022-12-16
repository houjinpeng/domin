import time
import requests
import redis
from houhou.logger import Logger

log = Logger().logger


def set_proxy():
    # 连接redis
    redis_cli = redis.Redis(host="127.0.0.1", port=6379, db=15)

    # 代理
    while True:
        url = 'http://39.104.96.30:8888/SML.aspx?action=GetIPAPI&OrderNumber=98b90a0ef0fd11e6d054dcf38e343fe927999888&poolIndex=1628048006&poolnumber=0&cache=1&ExpectedIPtime=&Address=&cachetimems=0&Whitelist=&isp=&qty=20'
        need_proxy = False
        if redis_cli.llen('beian_ip') <= 200:
            need_proxy = True
        if redis_cli.llen('baidu_ip') <= 200:
            need_proxy = True

        if redis_cli.llen('so_ip') <= 200:
            need_proxy = True

        if redis_cli.llen('sogou_ip') <= 200:
            need_proxy = True

        if need_proxy == True:
            try:
                r = requests.get(url, timeout=3)
                if '尝试修改提取筛选参数' in r.text or '用户异常' in r.text:
                    log.info('自己ip用户异常 ')

                ip_list = r.text.split('\r\n')
                for ip in ip_list:
                    if ip.strip() == '': continue
                    log.info(ip)
                    redis_cli.lpush("beian_ip", ip)
                    redis_cli.lpush("baidu_ip", ip)
                    redis_cli.lpush("so_ip", ip)
                    redis_cli.lpush("sogou_ip", ip)
            except Exception as e:
                time.sleep(1)
                log.info(e)
                continue

            try:
                r = requests.get('http://dev.qydailiip.com/api/?apikey=1515052b1349c02721701f386ade2dae92ee0d26&num=30&type=text&line=win&proxy_type=putong&sort=rand&model=all&protocol=http&address=&kill_address=&port=&kill_port=&today=false&abroad=1&isp=&anonymity=', timeout=3)
                if '尝试修改提取筛选参数' in r.text or '用户异常' in r.text:
                    log.info('用户异常 等60秒')
                    # time.sleep(60)
                    continue
                ip_list = r.text.split('\r\n')
                for ip in ip_list:
                    if ip.strip() == '': continue
                    log.info(ip)
                    redis_cli.lpush("beian_ip", ip)
                    redis_cli.lpush("baidu_ip", ip)
                    redis_cli.lpush("so_ip", ip)
                    redis_cli.lpush("sogou_ip", ip)

            except Exception as e:
                time.sleep(1)
                log.info(e)
                continue
            finally:
                pass
        else:
            time.sleep(3)



if __name__ == '__main__':
    set_proxy()

import json
import time

import requests

from dbutils.pooled_db import PooledDB
from conf.config import *
db_pool = PooledDB(**mysql_pool_conf)



def select_data():
    while True:
        try:
            select_sql = "select * from all_buy_ym where is_send =0"
            conn = db_pool.connection()
            cur = conn.cursor()
            cur.execute(select_sql)
            all_data = cur.fetchall()
            for data in all_data:
                ym = data['ym']
                t = data['place_time']
                price = data['price']
                main = data['main_name']
                zhi = data['zhi_name']
                if '失败' in zhi:
                    continue
                msg = f"用户: 3198\n购买域名：{ym} \n金额：{price}\n主线：{main}\n支线：{zhi}\n时间：{t}\n"

                #修改
                cur.execute("update all_buy_ym set is_send=1 where id=%s"%data['id'])
                conn.commit()

                print(send_msg(msg))

            cur.close()
            conn.close()
            time.sleep(10)
        except Exception as e:
            print(e)
            time.sleep(2)


def send_msg(msg):
    url = 'https://oapi.dingtalk.com/robot/send?access_token=5b1a5ce97ed3d82a7356c1e80e8d8b8fefbe0cd2622dbb234f83a501ed6cd130'

    headers = {'Content-Type': 'application/json;charset=utf-8'}
    data = {
        "msgtype": "text",  # 发送消息类型为文本
        "at": {
            # "atMobiles": reminders,
            "isAtAll": False,  # 不@所有人，如果要@所有人写True并且将上面atMobiles注释掉
        },
        "text": {
            "content": msg,  # 消息正文
        }
    }
    try:
        r = requests.post(url, data=json.dumps(data), headers=headers)
        return r.text
    except Exception as e:
        return send_msg(msg)


if __name__ == '__main__':
    select_data()
    # ym = 'ceshi'
    # t = '2022-03-31'
    # price = 10
    # main = '测试主'
    # zhi = '测试支'
    # msg = f"用户: 3198\n购买域名：{ym} \n金额：{price}\n主线：{main}\n支线：{zhi}\n时间：{t}\n"
    # print(send_msg(msg))

import hashlib
import time
import urllib.parse

import requests


class JmApi():
    def __init__(self):
        self.domain = 'http://newp.juming.com:9696'

    def build_data(self, data):
        time_str = int(time.time())
        common_data = {
            'appid': '3198',
            'time': time_str,
            'key': hashlib.md5(f'YeJSrpSwf&{time_str}'.encode('utf-8')).hexdigest(),
        }
        data.update(common_data)
        return data

    # 获取一口价列表
    def get_ykj_list(self, data):
        '''
        :param data: 搜索数据
        :return:
        '''
        try:
            # 增加公共参数
            data = self.build_data(data)
            response = requests.post(f'{self.domain}/newapi/ykj_get_list', data=data, timeout=10).json()
            if response['code'] != 1:
                time.sleep(2)
                return self.get_ykj_list(data)

            return response
        except Exception as e:
            time.sleep(2)
            print(e)
            return self.get_ykj_list(data)

    # 获取一口价成交数据
    def get_ykj_cj_list(self, data):
        '''
        :param data: 搜索数据
        :return:
        '''
        try:
            # 增加公共参数
            data = self.build_data(data)
            response = requests.post(f'{self.domain}/newapi/ykj_cj', data=data, timeout=4).json()
            if response['code'] != 1:
                time.sleep(2)
                print(response)
                return self.get_ykj_cj_list(data)

            return response
        except Exception as e:
            time.sleep(2)
            print(e)
            return self.get_ykj_cj_list(data)

    # 获取店铺数据
    def get_store_info(self, store_id):
        try:
            # 增加公共参数
            data = self.build_data({'id': store_id})
            response = requests.post(f'{self.domain}/newapi/ykj_dp', data=data, timeout=4).json()

            if response['code'] != 1:
                time.sleep(2)
                print(response)
                return self.get_store_info(data)

            return response
        except Exception as e:
            time.sleep(2)
            print(e)
            return self.get_store_info(store_id)

    #一口价下单
    def buy_ykj(self,ym,jg,ty=None,yz=None):
        try:
            # 增加公共参数
            data = {
                'ym':ym,
                'jg':jg,
                'ty':ty,
                'yz':yz,
            }
            data = self.build_data(data)
            response = requests.post(f'{self.domain}/newapi/ykj_buy', data=data, timeout=4).json()
            return response
        except Exception as e:
            time.sleep(2)
            print(e)
            return self.buy_ykj(ym,jg,ty=ty,yz=yz)


if __name__ == '__main__':
    jm_api = JmApi()
    data = {'psize': 1000, 'bdqz_1': 1, 'qiangjc': 1, 'jgpx': 5,
            'gjz_cha': 'lfgylongli.cn',
            'page':1}
    # data = {
    #     'psize': '50',
    #     # 'tao':'1299,77379'
    #         # 'bqjc': 99,#被墙检测
    #         'jgpx': 5,#排序结果
    #         'gjz_cha':'handofman.com'
    #         }
    data_info = jm_api.get_ykj_list(data)
    for data in data_info['data']:
        print(data)
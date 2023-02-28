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

    def get_all(self,type='all'):
        url = self.domain+'/newapi/ykj_list'
        # 增加公共参数
        data = self.build_data({'type':type})
        resp = requests.post(url,data=data).json()
        print(f'全部保存完毕{resp}')

    # 获取一口价列表
    def get_ykj_list(self, data):
        '''
        :param data: 搜索数据
        :return:
        '''
        try:
            # 增加公共参数
            data = self.build_data(data)
            response = requests.post(f'{self.domain}/newapi/ykj_get_list', data=data, timeout=1).json()
            if response['code'] != 1:
                # print(f'重新请求  一口价获取列表返回数据错误：{response}')
                time.sleep(2)
                return self.get_ykj_list(data)

            return response
        except Exception as e:
            # print(f'重新请求  一口价获取列表错误：{e}')
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
                print(response)
                return self.get_ykj_cj_list(data)

            return response
        except Exception as e:
            print(f'一口价获取成交数据错误：{e}')
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
            print(f'聚名api 获取店铺数据85行错误：{e}')
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
            response = requests.post(f'{self.domain}/newapi/ykj_buy', data=data, timeout=2).json()
            return response
        except Exception as e:
            # print(e)
            return self.buy_ykj(ym,jg,ty=ty,yz=yz)


if __name__ == '__main__':
    jm_api = JmApi()
    jm_api.get_all()

    # data = {'psize': 1000, 'bdqz_1': 1, 'qiangjc': 1, 'jgpx': 5,
    #         'gjz_cha': 'js-kaipu.com',
    #         'page':1}
    data = {
        'psize': '50',
        # 'tao':'1299,77379'
            # 'bqjc': 99,#被墙检测
            'jgpx': 5,#排序结果
            'gjz_cha':'trtfund.com'
            }
    data_info = jm_api.get_ykj_cj_list(data)
    for data in data_info['data']:
        print(data)
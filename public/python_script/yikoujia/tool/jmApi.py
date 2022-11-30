import hashlib
import requests
import time

import requests

headers = {
    'User-Agent': 'Apipost client Runtime/+https://www.apipost.cn/',
}


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
            response = requests.post(f'{self.domain}/newapi/ykj_cj', data=data, timeout=10).json()
            if response['code'] != 1:
                time.sleep(2)
                print(response)
                return self.get_ykj_cj_list(data)

            return response
        except Exception as e:
            time.sleep(2)
            return self.get_ykj_cj_list(data)

    # 获取店铺数据
    def get_store_info(self, store_id):
        try:
            # 增加公共参数
            data = self.build_data({'id': store_id})
            response = requests.post(f'{self.domain}/newapi/ykj_dp', data=data, timeout=10).json()
            if response['code'] != 1:
                time.sleep(2)
                print(response)
                return self.get_store_info(data)

            return response
        except Exception as e:
            time.sleep(2)
            return self.get_store_info(store_id)


if __name__ == '__main__':
    # appid = '3198'
    # time_str = int(time.time())
    # key = hashlib.md5(f'YeJSrpSwf&{time_str}'.encode('utf-8')).hexdigest()
    #
    # data = {
    #     'appid': appid,
    #     'time': time_str,
    #     'key': key,
    #     'psize': '50',
    #     # 'gjz_cha': 'qhdxinbei.com'
    #
    # }
    #
    # # response = requests.post('http://newp.juming.com:9696/newapi/ykj_get_list', headers=headers, data=data)
    #
    # print(data)

    jm_api = JmApi()

    # store_info = jm_api.get_store_info('41000')
    data = {
            # 'bqjc': 99,#被墙检测
            # 'jgpx': 41,#排序结果
            'gjz_cha':'thecircLeofit.com'
            }
    data_info = jm_api.get_ykj_list(data)

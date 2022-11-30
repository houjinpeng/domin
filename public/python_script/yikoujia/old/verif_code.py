import base64
import json
import requests
# 一、图片文字类型(默认 3 数英混合)：
# 1 : 纯数字
# 1001：纯数字2
# 2 : 纯英文
# 1002：纯英文2
# 3 : 数英混合
# 1003：数英混合2
#  4 : 闪动GIF
# 7 : 无感学习(独家)
# 11 : 计算题
# 1005:  快速计算题
# 16 : 汉字
# 32 : 通用文字识别(证件、单据)
# 66:  问答题
# 49 :recaptcha图片识别 参考 https://shimo.im/docs/RPGcTpxdVgkkdQdY
# 二、图片旋转角度类型：
# 29 :  旋转类型
#
# 三、图片坐标点选类型：
# 19 :  1个坐标
# 20 :  3个坐标
# 21 :  3 ~ 5个坐标
# 22 :  5 ~ 8个坐标
# 27 :  1 ~ 4个坐标
# 48 : 轨迹类型
#
# 四、缺口识别
# 18：缺口识别
# 五、拼图识别
# 53：拼图识别
def verif_api(uname, pwd,  img,typeid):
    try:
        with open(img, 'rb') as f:
            base64_data = base64.b64encode(f.read())
            b64 = base64_data.decode()
        data = {"username": uname, "password": pwd,"typeid":typeid, "image": b64}
        result = json.loads(requests.post("http://api.ttshitu.com/predict", json=data).text)
        if result['success']:
            return result["data"]["result"],result["data"]["id"]
        else:
            return result["message"],0
    except Exception as e:
        print(f'验证出错 {e}')
        return ""

def reportError(id):
    try:
        data = {"id": id}
        result = json.loads(requests.post("http://api.ttshitu.com/reporterror.json", json=data).text)
        if result['success']:
            print("报错成功")
            return "报错成功"
        else:
            return result["message"]
    except Exception as e:
        print(e)
        return ""

if __name__ == "__main__":
    # img_path = "./verif/verifCode_0.png"
    img_path = "code.jpg"
    # result = verif_api(uname='15211731111', pwd='esb104', img=img_path,typeid=53)
    result = verif_api(uname='15211731111', pwd='esb104', img=img_path,typeid=3)
    print(result)
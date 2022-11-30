import requests
import time
url = f"https://cf.aliyun.com/nocaptcha/analyze.jsonp?a=FFFF0N000000000087DE&t=CF_APP_1%3A{int(time.time()*1000)}%3A0.016923508999659775&n=140%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&p=%7B%22key1%22%3A%22code0%22%2C%22ncSessionID%22%3A%225e701e784328%22%2C%22umidToken%22%3A%22T2gAx_ic18so4g3JxC-gr0xy7X4_FX3BP6bE8hIapWSFkClpYigKfKVMLvXrUPzyW7I%3D%22%7D&scene=register&asyn=0&lang=cn&v=1083&callback=jsonp_16450243387268313"

payload={}
headers = {
  'accept': '*/*',
  'accept-encoding': 'gzip, deflate, br',
  'accept-language': 'zh-CN,zh;q=0.9',
  'cache-control': 'no-cache',
  'pragma': 'no-cache',
  'referer': 'http://www.chaicp.com/',
  'sec-ch-ua': '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
  'sec-ch-ua-mobile': '?0',
  'sec-ch-ua-platform': '"Windows"',
  'sec-fetch-dest': 'script',
  'sec-fetch-mode': 'no-cors',
  'sec-fetch-site': 'cross-site',
  'user-agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.127 Safari/537.36'
}

response = requests.request("GET", url, headers=headers, data=payload)

print(response.text)

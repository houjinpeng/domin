import execjs

import js2py


#
# with open('e.js','r',encoding='utf-8') as fr:
#     js = fr.read()

with open('ceshi.js','r',encoding='utf-8') as fr:
    cs_js = fr.read()

obj ={
    "SendInterval": 5,
    "SendMethod": 8,
    "isSendError": 1,
    "MaxMCLog": 12,
    "MaxKSLog": 14,
    "MaxMPLog": 5,
    "MaxGPLog": 1,
    "MaxTCLog": 12,
    "GPInterval": 50,
    "MPInterval": 4,
    "MaxFocusLog": 6,
    "Flag": 2980046,
    "OnlyHost": 1,
    "MaxMTLog": 500,
    "MinMTDwnLog": 30,
    "MaxNGPLog": 1,
    "sIDs": [
        "_n1t|_n1z|nocaptcha|-stage-1"
    ],
    "mIDs": [
        "nc-canvas",
        "click2slide-btn"
    ],
    "hook": 1,
    "font": 1,
    "api": 1
}
# result = execjs.compile(js,cwd='node_modules').call('e', (1,obj))
# result = execjs.compile(js).call('e', (1,obj))
result1 = execjs.compile(cs_js).call('aaa', (1,2))
# print(result)
print(result1)

context = js2py.EvalJs()
#执行js
c = context.execute(cs_js)
print(c.aaa(1,2))
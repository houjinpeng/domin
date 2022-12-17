import time
import os
# print(1)
# while True:
#     print(1)
#     with open('aa.txt','a',encoding='utf-8') as fw:
#         fw.write(str(os.getpid()))
#         fw.write('\n')
#     time.sleep(2)

a = os.system('tasklist | findstr 2444444442')
print(a)
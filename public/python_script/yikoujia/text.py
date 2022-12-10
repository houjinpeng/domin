import time
while True:
    print(1)
    with open('aa.txt','a',encoding='utf-8') as fw:
        fw.write('aa')
    time.sleep(2)
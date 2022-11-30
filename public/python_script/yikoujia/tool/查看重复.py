with open('../conf/out_ym.txt', 'r', encoding='utf-8') as fr:
    data = fr.readlines()

ls = [d.split(',')[0]for d in data]
len(ls)
print(f'总数量 {len(ls)}')
print(f'去重总数量 {len(set(ls))}')

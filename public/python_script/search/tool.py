
from pymysql.converters import escape_string
import json

#构建数据库
def build_sql(table,data_dict):
    insert_sql = f'insert into {table} ('
    for key in data_dict.keys():
        if data_dict[key] == None:
            continue
        insert_sql += f"`{key}`" + ','
    insert_sql = insert_sql[:-1]
    insert_sql += ') values ('

    for val in data_dict.values():
        if val == None:
            continue
        if isinstance(val, str) == True:
            val = escape_string(val)
        elif isinstance(val, dict) == True or isinstance(val, list) == True:
            val = escape_string(json.dumps(val))
        elif isinstance(val, int) == True:
            val = str(val)

        insert_sql += f"'{str(val)}'" + ','
    insert_sql = insert_sql[:-1]
    insert_sql += ')'
    return insert_sql


#保存数据
def save_data(sql,cur,conn):
    try:
        cur.execute(sql)
        conn.commit()
        return True
    except Exception as e:
        return 'error'

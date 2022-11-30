import pymysql

#配置文件

#mysql 配置文件
mysql_pool_conf = {
    'host': 'localhost',
    'port': 3306,
    'user': 'root',
    'password': '123456',
    'db': 'domain',
    'creator': pymysql,
    'cursorclass': pymysql.cursors.DictCursor,
}


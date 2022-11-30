import win32com.client
import subprocess

#判断进程是否存在
def proc_exist(process_name):
    is_exist = False
    wmi = win32com.client.GetObject('winmgmts:')
    processCodeCov = wmi.ExecQuery('select * from Win32_Process where name=\"%s\"' %(process_name))
    if len(processCodeCov) > 0:
        is_exist = True
    return is_exist

# if proc_exist('chrome.exe'):
#     print('chrome.exe is running')
# else:
#     print('no such process...')
import sys
#
import psutil


# pl = psutil.pids()
# for pid in pl:
#     if psutil.Process(pid).name() == 'notepad++.exe':
#         print(pid)


def checkprocess(processname):
    pl = psutil.pids()
    for pid in pl:
        if psutil.Process(pid).name() == processname:
            return pid


# print(isinstance(checkprocess("notepad++.exe"),int))
print(checkprocess("8fa15235-0b94-4ccb-a702-6047c8c2db84"))






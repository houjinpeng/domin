import time
from socketserver import BaseRequestHandler, ThreadingTCPServer
import threading

class Handler(BaseRequestHandler):

    def handle(self) -> None:
        address, pid = self.client_address
        print(f'{address} connected!')
        i = 0
        while True:
            # data = self.request.recv(1024)
            # if len(data) <= 0:
            #     print("close!")
            #     break
            i += 1
            # print(f'receive data: {str(i).decode()}')
            self.request.sendall(f'send {i}'.encode())
            time.sleep(1)

if __name__ == '__main__':

    server = ThreadingTCPServer(('127.0.0.1', 8999), Handler)
    print("Listening")
    # server.serve_forever()
    threading.Thread(target=server.serve_forever).start()
    print(123)
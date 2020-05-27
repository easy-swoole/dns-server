# dns-server
## 相关知识
- DNS是53端口的udp协议
## 测试
### 服务端
### 客户端
```
$ dig @127.0.0.1 test.com A +short
111.111.111.111

$ dig @127.0.0.1 test.com TXT +short
"Some text."

$ dig @127.0.0.1 test2.com A +short
111.111.111.111
112.112.112.112
```
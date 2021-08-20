# Ecloud
> Ecloud是一款基于http/1.1协议传输TCP流量的工具，适用于内网不出网时通过web代理脚本转发tcp流量，以达到socket5隧道、内网cs等程序上线、反弹虚拟终端等功能

## 初衷
> 在进行红蓝对抗时，经常会遇到外网打点拿下的服务器不出网，只有靠已有的工具进行web代理出网，例如：
- reGeorg/Neo-reGeorg: 通过web脚本搭建socket5隧道
- ABPTTS: 和reGeorg类似，基于web脚本的就不一一介绍了
- 毒刺(pystinger): 通过client->web脚本流量转发->server进行tcp连接

> 那为什么重新造轮子写一款新的工具呢？主要是实战中碰到的问题：
- 过waf：这是最核心也是最重要的，分为数据包特征和http请求速率等（以上工具都已被waf拦截）
- 需要的功能：socket5隧道、内网程序(如cs等)上线、虚拟终端(webshell的命令执行只是exec执行，不支持交互式)
- 程序稳定性、跨平台性等
- 发送和返回的数据太大
- ......更多细节

## 项目设计
> 要实现以上所需功能，还是得采用 client端->web脚本转发流量->server端，优点是：
- 除能实现socket5隧道外，还能实现内网程序上线、虚拟终端等功能，有了TCP流量就能做更多事
- web脚本代码简单，只是一个http请求转发，这样就能支持大部分web环境
- TCP连接放在server端，可更容易管理和维护以及自定义流量加密等
- 更少的http请求
- 数据压缩，使用gzip压缩，越大数据压缩比例越好，减少数据体积

> 当然，也有缺点：
- 需要上传server端并且执行

## Socket5隧道
> 为了跨平台，采用golang语言开发，从零开始，不得不研究socket5协议、http协议转tcp优化、数据压缩等

> 要实现基于http/1.1协议搭建socket5隧道，因为TCP是字节流传输，并不是像http协议request->response这样回合制；故设计为：

### client端
- TCP监听某端口，解析socket5协议，获取目标主机和端口；发送connect命令让sever端建立tcp连接并保存；
- 读取客户端输入，并将读取到的数据发送到server端进行tcp.write()
- 读取server端tcp.read()结果，写入客户端中
- 客户端断开，发送命令让server端相应的tcp连接断开，防止tcp打开过多，server端tcp连接池自身也进行自检，断开长时间未读写的连接

### server端
- 启一个web并监听某端口(监听本机地址和大数字端口只需普通用户权限即可)，接收web代理脚本转发的post数据
- 内置tcp连接池，client端socket5每监听到一个客户端分配唯一标识，且connect时server tcp连接池分配

### web脚本
> 只是一个http请求转发，可以省略

### 过waf
- 采用golang gob序列化+gzip数据压缩+base64替换编码，使其正常不能解码
- 数据采用分段key-value形式，数据越大分段越多

## 项目演示

### 流量特征
> 内置ua协议头，每次发送不一致，内置1000+常用api变量名，可指定分段大小，如分段长度为20，则100大小的数据分为5个key-value形式；返回数据特征暂未处理

![index](https://raw.githubusercontent.com/CTF-MissFeng/Ecloud/main/img/1.png)

### 优化
- 配置了只代理局域网功能，这样浏览器使用当前socket5打开外网地址不影响
- 配置了域名过滤，浏览器会默认发送垃圾流量，配置此域名则进行过滤，最大程序减少http请求发送量

```toml
[client.socket5]
        enable = true # 是否开启socket5代理功能
        listen = "127.0.0.1" # 本地Socket5监听地址
        listenPort = 1080 # 本地Socket5监听端口
        readTime = 500 # 读取TCP响应延迟(太快容易被安全设备拦截)
        writeTime = 200 # 发送TCP数据延迟
        sectionLength = 100 # 发送数据分段长度
        localRoute = false # 只代理局域网地址
        domain = ["baidu.com", "google.com", "googleapis.com"]  # 当未开启只代理局域网地址功能 则过滤以下域名，防止浏览器默认垃圾流量
```

### 使用教程

#### client端
> 配置好client.toml文件，直接运行即可，注意代理默认http://127.0.0.1:8080，测试时打开burp，或置为空

#### server端
> 配置好server.json，直接运行（webshell命令执行需要从后台运行：nohup ./server &），如果文件体积过大，可使用upx进行压缩后上传

#### web脚本端
> 如果是测试可以省略web脚本，url直接填server http监听地址，若需要使用web脚本，则将web脚本上传到web目录中，url填写web脚本地址

#### 视频演示
<iframe src="//player.bilibili.com/player.html?bvid=BV1bq4y1S7iC&page=1" scrolling="no" border="0" frameborder="no" framespacing="0" allowfullscreen="true"> </iframe>

### 去做
> 该项目正在开发中

- 内网程序出网
- 虚拟终端
- 更好想法

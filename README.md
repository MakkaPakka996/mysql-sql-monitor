好，这次给你**标准 Markdown 文件内容（可直接复制到 README.md）**，且避免嵌套代码块问题👇



------





# **🛰 MySQL SQL Monitor（Web版）**





一个用于 PHP 代码审计 / SQL 注入分析的轻量级工具，通过浏览器实时查看 MySQL 执行的 SQL。



------





## **✨ 功能**





- 实时查看 SQL 执行
- 浏览器可视化（自动刷新）
- SQL 关键字高亮
- 无需修改目标代码





------





## **🚀 使用**







### **1. 开启 MySQL 日志**



```
SET GLOBAL log_output = 'TABLE';
SET GLOBAL general_log = 'ON';
```



------





### **2. 部署文件**





放入网站目录：

```
monitor.php
index.html
```



------





### **3. 浏览器访问**



```
http://127.0.0.1/index.html
```



------





## **📌 注意**





必须使用：

```
FROM mysql.general_log
```

需要权限：

```
GRANT SELECT ON mysql.general_log TO 'user'@'localhost';
```

使用完关闭日志：

```
SET GLOBAL general_log = 'OFF';
```



------





## **🎯 适用场景**





- SQL 注入调试
- PHP 代码审计
- 漏洞复现





------





## **⚠️ 免责声明**





仅用于学习与授权测试。

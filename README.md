# MySQL SQL Monitor

一个用 PHP + HTML 做的轻量级 MySQL 日志查看页，适合在浏览器里快速观察 `mysql.general_log`，排查 SQL 注入、调试业务查询、复现漏洞链路。

## 功能

- 页面直接填写数据库连接配置，不需要手改 PHP
- 自动轮询刷新日志
- 支持搜索 SQL 内容
- 支持设置日志条数
- 支持显示序号
- SQL 关键字高亮
- 一键开启日志
- 一键清空日志
- 自动检查 `general_log` 和 `log_output` 状态

## 文件

- `index.html`：前端页面
- `monitor.php`：接口和日志控制逻辑

## 使用方式

### 1. 部署文件

把下面两个文件放到站点目录：

```text
index.html
monitor.php
```

### 2. 浏览器访问

```text
http://127.0.0.1/index.html
```

页面里可以直接填写：

- Host
- Port
- User
- Password
- Database
- 日志条数

点击 `Connect` 后会自动开始轮询刷新。

### 3. 页面操作

- `Connect`：按当前配置读取日志
- `开启日志`：执行 `SET GLOBAL log_output='TABLE'` 和 `SET GLOBAL general_log='ON'`
- `清空日志`：清空 `mysql.general_log` 并重新开启日志

## MySQL 要求

这个工具读取的是：

```sql
mysql.general_log
```

为了正常工作，MySQL 需要满足：

- `general_log = ON`
- `log_output` 包含 `TABLE`

如果你不想手动执行 SQL，可以直接用页面里的 `开启日志` 按钮。

## 权限要求

至少需要这些权限：

```sql
GRANT SELECT ON mysql.general_log TO 'user'@'localhost';
```

如果要使用页面里的“一键开启日志”和“清空日志”，还需要足够的全局管理权限，例如能执行：

```sql
SET GLOBAL log_output = 'TABLE';
SET GLOBAL general_log = 'ON';
TRUNCATE TABLE mysql.general_log;
```

如果账号权限不够，页面会直接显示错误信息。

## 注意事项

- `general_log` 会带来额外性能开销，不建议长期在线上开启
- MySQL 重启后，`general_log` 和 `log_output` 可能恢复默认值
- 页面会过滤一部分监控器自己的维护 SQL，尽量只展示业务查询
- 当前项目已经兼容较老的 PHP 环境，线上实测为 PHP 5.6

## 适用场景

- SQL 注入调试
- PHP 代码审计
- 漏洞复现
- 临时排查业务 SQL

## 免责声明

仅用于学习、调试和授权测试。

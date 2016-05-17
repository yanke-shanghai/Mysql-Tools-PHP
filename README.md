Mysql-Tools-PHP
===================
LINUX环境下用php去调用mysql在开发中经常会遇到，每次重写调用mysql的方法会很麻烦，这里实现Mysql工具类，将调用过程封装，用户只需要调用需要的接口即可
***
###　　　　　　　　　　Author:DMINER
###　　　　　　E-mail:yanke_shanghai@126.com
　
===================
使用说明
--------
###一、文件解释
* mysql_pdo_util.php    ：mysql工具类
* check_config.php      ：数据库配置信息
* check_value_util.php  ：示例Demo
###二、接口解释
```php
####1.初始化
public static function init()
####2.设置服务器
public static function sethost($host)
####3.设置数据库
public static function setdb($db)
####4.设置用户,密码
public static function setuser($user,$pwd,$port=3306)
####5.获取DSN
public static function getdsn()
####6.获取DB_USER
public static function getuser()
####7.获取DB_PSWD
public static function getpswd()
####8.建立连接
private function getconnection($host, $db, $user, $pwd)
####9.插入操作
public function exeinsert($host, $db, $user, $pwd, $sql)
####10.查询操作
private function exequery($host, $db, $user, $pwd, $sql,$parameters = null)
####11.返回多条记录
public function queryrows($host, $db, $user, $pwd, $sql)
####12.返回单条记录
public function queryrow($host, $db, $user, $pwd, $sql)
####13.返回单条记录的一个字段
public function queryfield($host, $db, $user, $pwd, $sql)
```

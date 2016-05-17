<?php
//数据库配置类
class dbconfig{
	private static $DB_HOST = '';
	private static $DB_PORT = '';
	private static $DB_USER = '';
	private static $DB_PSWD = '';
	private static $DB_NAME = '';
	private static $dsn = '';
	//初始化
	public static function init(){
		self::$DB_HOST = '';
		self::$DB_NAME = '';
		self::$dsn = '';
	}
	//设置服务器
	public static function sethost($host){
		if(isset($host) && strlen($host) > 0){
			self::$DB_HOST = trim($host);
		}
	}
	//设置数据库
	public static function setdb($db){
		if(isset($db) && strlen($db) > 0){
			self::$DB_NAME = trim($db);
		}
	}
	//设置用户,密码
	public static function setuser($user,$pwd,$port=3306){
		if(isset($user) && strlen($user) > 0){
			self::$DB_USER = trim($user);
		}
		if(isset($pwd) && strlen($pwd) > 0){
			self::$DB_PSWD = trim($pwd);
		}
		if(isset($pwd) && strlen($pwd) > 0){
			self::$DB_PORT = trim($port);
		}
	}
	//获取DSN
	public static function getdsn(){
		self::$dsn = 'mysql:dbname='.self::$DB_NAME;
		self::$dsn.= ';host='.self::$DB_HOST;
		self::$dsn.= ';port='.self::$DB_PORT;
		self::$dsn.= ':charset=utf8';
		return self::$dsn;
	}
	//获取DB_USER
	public static function getuser(){
		if(isset(self::$DB_USER) && strlen(self::$DB_USER) > 0){
			return self::$DB_USER;
		}
	}
	//获取DB_PSWD
	public static function getpswd(){
		if(isset(self::$DB_PSWD) && strlen(self::$DB_PSWD) > 0){
			return self::$DB_PSWD;
		}
	}
}

//数据库操作类
class dbtemplate{
	//建立连接
	private function getconnection($host, $db, $user, $pwd){
		$dbConfig = new dbconfig();
		$dbConfig->init();
		$dbConfig->sethost($host);
		$dbConfig->setdb($db);
		$dbConfig->setuser($user,$pwd);
		$conn = new pdo($dbConfig->getdsn(),$dbConfig->getuser(),$dbConfig->getpswd(),array(
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
	));
		unset($dbConfig);
		return $conn;
	}
	//插入操作
	public function exeinsert($host, $db, $user, $pwd, $sql){
		$conn = $this->getconnection($host, $db, $user, $pwd);
		$conn->query($sql);
		$conn = null;
	}
	//查询操作
	private function exequery($host, $db, $user, $pwd, $sql,$parameters = null){
		$conn = $this->getconnection($host, $db, $user, $pwd);
		$stmt = $conn->prepare($sql);
		$stmt->execute($parameters);
		$rs = $stmt->fetchall();
		$stmt = null;
		$conn = null;
		return $rs;
	}
	//返回多条记录
	public function queryrows($host, $db, $user, $pwd, $sql){
		return $this->exequery($host, $db, $user, $pwd, $sql);
	}
	//返回单条记录
	public function queryrow($host, $db, $user, $pwd, $sql){
		$rs = $this->exequery($host, $db, $user, $pwd, $sql);
		if(count($rs) > 0){
			return $rs[0];
		}else{
			return null;
		}
	}
	//返回单条记录的一个字段
	public function queryfield($host, $db, $user, $pwd, $sql){
		$rs = $this->exequery($host, $db, $user, $pwd, $sql);
		if(count($rs) > 0){
			return $rs[0][0];
		}else{
			return null;
		}
	}
}	

?>

<?php
require_once('mysql_pdo_util.php');
require_once('check_config.php');


class checkvalue extends dbtemplate{
	//错误信息
	public $ERROR_INFO = '';
	public $CONFIG_HOST = '';
	public $CONFIG_DB = '';
	public $CONFIG_USER = '';
	public $CONFIG_PWD = '';
	//初始化
	public function _construct($host, $db, $user, $pwd){
		$this->CONFIG_HOST = $host;
		$this->CONFIG_DB = $db;
		$this->CONFIG_USER = $user;
		$this->CONFIG_PWD = $pwd;
		$this->ERROR_INFO = 'OK';
	}
	//获得report_id
	private function getreportId($gid, $stid, $sstid, $op_fields, $op_type){
		GLOBAL $CONFIG_HOST,$CONFIG_DB;
		$sql = "SELECT report_id FROM t_report_info WHERE game_id = '$gid' and stid = '$stid' and sstid = '$sstid' and op_fields = '$op_fields' and op_type = '$op_type'";
		$reportId = dbtemplate::queryfield($CONFIG_HOST,$CONFIG_DB,$CONFIG_USER,$CONFIG_PWD,$sql);
		echo "reportId = $reportId\n";
		return $reportId;
	}
	//获得result_id
	private function getresultId($gid, $taskid){
		GLOBAL $CONFIG_HOST,$CONFIG_DB;
		$sql = "SELECT result_id FROM t_common_result WHERE game_id = '$gid' and task_id = '$taskid'";
		$resultId = dbtemplate::queryfield($CONFIG_HOST,$CONFIG_DB,$CONFIG_USER,$CONFIG_PWD,$sql);
		echo "resultId = $resultId\n";
		return $resultId;
	}
	//获得data_id,sthash
	private function getdataId($r_id, $type='report', $range=''){
		GLOBAL $CONFIG_HOST,$CONFIG_DB;
		$sql = "SELECT data_id,sthash FROM t_data_info WHERE r_id = '$r_id' and type = '$type' and range = '$range'";
		$dataInfo = dbtemplate::queryrow($CONFIG_HOST,$CONFIG_DB,$CONFIG_USER,$CONFIG_PWD,$sql);
		return $dataInfo;
	}
	//根据sthash获得数据存储库
	private function getDb($sthash){
		$db_tb = $sthash % 10000;
		$db = (int)($db_tb / 100);
		return $db;
	}
	//根据sthash获得数据存储表
	private function getTb($sthash){
		$db_tb = $sthash % 10000;
		$tb = $db_tb % 100;
		return $tb;
	}
	//根据db查表，获取服务器信息
	private function getHostInfo($db){
		GLOBAL $CONFIG_HOST,$CONFIG_DB;
		$sql = "SELECT db_user,db_pwd,db_host,db_port FROM t_db_info WHERE db_name = 'db_td_data_$db'";
		$hostInfo = dbtemplate::queryrow($CONFIG_HOST,$CONFIG_DB,$CONFIG_USER,$CONFIG_PWD,$sql);
		return $hostInfo;
	}
	//根据gid查表，获取gpzsid
	private function getGpzsId($gid){
		GLOBAL $CONFIG_HOST,$CONFIG_DB;
		$sql = "SELECT gpzs_id FROM t_gpzs_info WHERE game_id = '$gid' and platform_id = '-1' and zone_id = '-1' and server_id = '-1' and status = '0'";
		$gpzsId = dbtemplate::queryfield($CONFIG_HOST,$CONFIG_DB,$CONFIG_USER,$CONFIG_PWD,$sql);
		return $gpzsId;
	}
	//对比老统计数据，检测按条付费数据
	private function checkItem($gid, $time, $value_tongji, $type){
		GLOBAL $errorInfo,$ridItem;
		$s = $time;
		$e = $s + 86400 - 1;
		$resultId = $ridItem[$type][$gid];

		echo "s=$s e=$e resultId=$resultId\n";

		$url = "http://192.168.71.57/lock-db/api/api.php?action=getMaxData&ids=rs_{$resultId}&start_time={$s}&end_time={$e}&interval=1440";
		$ret = json_decode(file_get_contents($url), true);
		if(!isset($ret[0]["value"])) {
			$this->ERROR_INFO = $errorInfo[5];
			return;
		}
		$ret = $ret[0]["value"];
		if(!isset($ret[0]["value"])) {
			$this->ERROR_INFO = $errorInfo[5];
			return;
		}
		$value_stat = $ret[0]["value"];
		echo "老统计按条付费数据($type) ：$value_stat\n";
		echo "新统计按条付费数据($type) ：$value_tongji\n";
		if($type == "sum"){
		    $value_stat = (int)($value_stat/100);
		    $value_tongji = (int)($value_tongji/100);
		    echo "老统计按条付费数据int($type) ：$value_stat\n";
		    echo "新统计按条付费数据int($type) ：$value_tongji\n";
		}
		if($value_stat != $value_tongji){
			$this->ERROR_INFO = $errorInfo[6];
		}else{
			$this->ERROR_INFO = $errorInfo[0];
		}
	}
	
	//处理datainfo
	public function datainfoProc($gid, $dataInfo, $time){
		GLOBAL $errorInfo,$didItem;
		$dataId = $dataInfo['data_id'];
		$stHash = $dataInfo['sthash'];
		$db = $this->getDb($stHash);
		$tb = $this->getTb($stHash);
		echo "dataId = $dataId sthash = $stHash db = $db tb = $tb\n";
		//获取host信息
		$hostInfo = $this->getHostInfo($db);
		$dbUser = $hostInfo['db_user'];
		$dbPwd = $hostInfo['db_pwd'];
		$dbHost = $hostInfo['db_host'];
		$dbPort = $hostInfo['db_port'];
		//获取gpzsid
		$gpzsId = $this->getGpzsId($gid);
		$sql = "SELECT value FROM t_db_data_day_$tb WHERE gpzs_id = $gpzsId AND data_id = $dataId AND time = $time";
		$value = dbtemplate::queryfield("$dbHost", "db_td_data_$db", $dbUser, $dbPwd, $sql);
		echo "value = $value\n";
		if(strlen($value) > 0 && $value > 0){
			//和老统计对比，检测按条付费数据
			if($dataId == $didItem['sum'][$gid]){
				$this->checkItem($gid, $time, $value, 'sum');
			}else if($dataId == $didItem['ucount'][$gid]){
				$this->checkItem($gid, $time, $value, 'ucount');
			}else{
				$this->ERROR_INFO = $errorInfo[0];
			}
		}else{
			$this->ERROR_INFO = $errorInfo[4];
		}		
	}
	//获得report数据检测结果
	public function checkReport($gid, $stid, $sstid, $op_fields, $op_type, $time){
		GLOBAL $errorInfo;
		$reportId = $this->getreportId($gid, $stid, $sstid, $op_fields, $op_type);
                if($reportId != ''){
			$dataInfo = $this->getdataId($reportId);
			if($dataInfo != ''){
                        	$this->datainfoProc($gid, $dataInfo, $time);
			}else{
				$this->ERROR_INFO = $errorInfo[3];
				echo "Can not find data_id: gid=$gid stid=$stid sstid=$sstid\n";
			}
		}else{
			$this->ERROR_INFO = $errorInfo[1];
                        echo "Can not find report_id: gid=$gid stid=$stid sstid=$sstid\n";
		}
		return $this->ERROR_INFO;
	}
	//获得result数据检测结果
	public function checkResult($gid, $taskid, $range, $time){
		GLOBAL $errorInfo;
		$resultId = $this->getresultId($gid, $taskid);
                if($resultId != ''){
			$dataInfo = $this->getdataId($resultId, "result", $range);
			if($dataInfo != ''){
                                $this->datainfoProc($gid, $dataInfo, $time);
			}else{
				$this->ERROR_INFO = $errorInfo[3];
                                echo "Can not find data_id: gid=$gid taskid=$taskid\n";
                        }
                }else{
			$this->ERROR_INFO = $errorInfo[2];
                        echo "Can not find result_id: gid=$gid taskid=$taskid\n";
		}
		return $this->ERROR_INFO;
	}
}

?>

<?php
/**
 * @Author   liuxiaodong
 * @DateTime 2018-04-09
 */

namespace App;
use Lib;
use model\Device as DbDevice;
use Lib\Monitor as Mon;
use Lib\Tasks;
class Control {

	/**
	 * worker回调中心服 任务执行状态
	 * @param $tasks
	 * @return array
	 */

	public static function notify($tasks) {
		echo "APP ------ Device ----------notify" . PHP_EOL;
		if (empty($tasks) || count($tasks) <= 0) {
			return Lib\Util::errCodeMsg(101, "The tasks can't be empty");
		}
		$header = Lib\LoadTasks::getTasks();
		foreach ($tasks as $task) {
			if ($task["code"] == 0) {
				$runStatus = Lib\LoadTasks::RunStatusSuccess;
			} else {
				$runStatus = Lib\LoadTasks::RunStatusFailed;
				Lib\Report::taskFailed($task["taskId"], $task["runid"], $task["code"]);
			}
			$header->set($task["taskId"], ["runStatus" => $runStatus, "runUpdateTime" => time()]);
			$header->decr($task["taskId"], 'execNum'); //减少当前执行数量
			if (Lib\Tasks::$table->exist($task["runid"])) {
				Lib\Tasks::$table->set($task["runid"], ["runStatus" => $runStatus]);
			}
			Lib\TermLog::log($task["runid"], $task["taskId"], "任务已经执行完成", $task);
		}
		return Lib\Util::errCodeMsg(0, "ok");
	}



	/**
	 * 获取代理服务器
	 * @return array
	 */
	public static function getControls($gets = [], $page = 1, $pagesize = 10) {
		// $list = DbDevice::getAllDevices();
		echo '----------------monitor table'.PHP_EOL;
		$list = DbDevice::getOneColumns([],['c_deviceid','c_devicesn','c_status','c_type']);
		$res = [];
		foreach ($list as $k => $task) {
			$tmp = Lib\Robot::$table->get($task["c_devicesn"]);
			$monitor = Lib\Monitor::$table->get($task['c_devicesn']);
			$res[$k]['c_devicesn'] = $task['c_devicesn'];
			$res[$k]['c_deviceid'] = $task['c_deviceid'];
			$res[$k]['c_type'] = $task['c_type'];
			if (!empty($tmp)) {
				$res[$k]["lasttime"] = $tmp["lasttime"];
				$res[$k]["isconnect"] = 1;
				$res[$k]['c_relay'] = $monitor['c_relay'];
			} else {
				$res[$k]["isconnect"] = 0;
			}
		}
		return $res;
	}

	/**
	 *  修改任务
	 * @param $id
	 * @param $Device
	 * @return array
	 */
	public static function update($data) {
		echo "APP ------ Device ----------updateDevice" . PHP_EOL;
		if (empty($data['c_deviceid']) && empty($data)) {
			return false;
		}
		$ret = Tasks::updateRelay($data);
		if(!$ret){
			return false;
		}
		return true;
		// $status = db('Device')->where(['c_deviceid' => $id])->value('c_status');
		// if ($status == 0) {
		// 	$data['c_status'] = 1;
		// 	$res = Lib\Robot::stopAgent($id);
		// } else {
		// 	$data['c_status'] = 0;
		// 	$res = Lib\Robot::startAgent($id);
		// }
		// $data['c_deviceid'] = $id;
		// $res1 = DbDevice::updateDevice($data);
		// if ($res && $res1) {
		// 	return true;
		// }
		// return false;
	}
	/**
	 *del the device
	 * @param    [type]      $id [deviceid]
	 * @return   [type]          [boolean]
	 */
	public static function delDevice($id) {
		echo "APP ------ Device ----------delDevice" . PHP_EOL;
		if (empty($id)) {
			return false;
		}
		$res = Lib\Robot::delAgent($id);
		$res1 = db('Device')->delete($id);
		if ($res && $res1) {
			return true;
		}
		return false;
	}

	/**
	 * 删除任务代理
	 * @param $id
	 * @return array
	 */
	public static function deleteDevice($id) {
		echo "APP ------ Device ----------deleteDevice" . PHP_EOL;
		if (empty($id)) {
			return Lib\Util::errCodeMsg(101, "参数为空");
		}
		if (!table("Devices")->del($id)) {
			return Lib\Util::errCodeMsg(102, "删除失败");
		}
		table("Device_group")->dels(["aid" => $id]);
		self::reload($id);
		return Lib\Util::errCodeMsg(0, "删除成功");
	}

	private static function reload($aid) {
		echo "APP ------ Device ----------reload" . PHP_EOL;
		$Devices = table("Devices");
		$info = $Devices->get($aid);
		if (empty($info) && $info["status"] == 1) {
			Lib\Robot::$aTable->del($aid);
		}
	}

}
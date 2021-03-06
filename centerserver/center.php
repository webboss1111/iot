<?php
/**
 * Created by PhpStorm.
 * User: liuzhiming
 * Date: 16-8-18
 * Time: 下午2:30
 */

require_once __DIR__ . '/_init.php';

Swoole\Network\Server::setPidFile(getRunPath() . '/logs/center.pid');

Swoole\Network\Server::start(function ($opt) {
	$logger = new Swoole\Log\FileLog(['file' => getRunPath() . '/logs/center.log']);
	$AppSvr = new Lib\CenterServer;
	$AppSvr->setLogger($logger);

	$setting = array(
		'worker_num' => WORKER_NUM,
		'task_worker_num' => TASK_NUM,
		'max_request' => 1000,
		'dispatch_mode' => 4, //IP分配，根据客户端IP进行取模hash，分配给一个固定的worker进程
		'log_file' => getRunPath() . '/logs/swoole.log',
		'open_length_check' => 1,
		'package_max_length' => $AppSvr->packet_maxlen,
		'socket_buffer_size' => 9000000000,
        'heartbeat_idle_time' => 120,
        'heartbeat_check_interval' => 20,
		'package_length_type' => 'N',
		'package_body_offset' => \Swoole\Protocol\SOAServer::HEADER_SIZE,
		'package_length_offset' => 0,
	);
	//重定向PHP错误日志到logs目录
	ini_set('error_log', getRunPath() . '/logs/php_errors.log');

	\Lib\Donkeyid::init(); //初始化donkeyid对象
	\Lib\Tasks::init(); //创建task表
	\Lib\Monitor::init(); //创建监控状态表
	\Lib\Robot::init(); //创建任务处理服务表
    \Table\SafeLimit::init();//创建安全上下限表
    \Table\Warning::init();//创建警报内存表
	Swoole::$php->db->close();
	$host = CENTER_HOST;
	$port = CENTRE_PORT;
	if (isset($opt['host'])) {
		$host = $opt['host'];
	}
	if (isset($opt['port'])) {
		$port = $opt['port'];
	}

	$server = Swoole\Network\Server::autoCreate($host, $port);
	$AppSvr::$_server = $server;
	$server->setProtocol($AppSvr);
	$server->setProcessName("CenterServer");
	$server->on("PipeMessage", array($AppSvr, 'onPipeMessage'));
	$server->run($setting);
});

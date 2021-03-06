<?php
namespace model;
/**
 * @Author   liuxiaodong
 * @DateTime 2018-04-09
 * @return   [type]      [封装的monitor数据库类]
 */

class Monitor
{
    public $table;
    /**
     * @var null
     * 单例模式
     */
    public static $_instance = null;
    public static function getInstance()
    {
        if(empty(self::$_instance))
        {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct()
    {
        $this->table = table('t_monitor');
    }
    /**
     * 获取所有监控数据
	 * @param    [type]      $where [where condition]
	 * @return   [type]             [return all list]
	 */
	public  function getAllMonitors($where = [],$order = 'c_id desc')
    {
        $data['where'] = $where;
        $data['order'] = $order;
		return $this->table->gets($data);
	}
	/**
	 * @param    [type]      $where [condition]
	 * @return   [type]             [return one res]
	 */
	public function getOneMonitor($id,$where)
    {
		return $this->table->get($id,$where);
	}
	public function updateMonitor($id,$data,$where='c_devicesn')
    {
		return $this->table->set($id,$data,$where);
	}
	public function insertMonitor($data){
//	    echo "insert model monitor  ----------".PHP_EOL;
	    $data['create_time'] = time();
	    try{
            $this->table->put($data);;
        }catch (\Exception $e){
	        echo "monitor data chongfu l ";
        }
	}
}
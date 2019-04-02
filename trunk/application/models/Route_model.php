<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Route_Model extends BASE_Model {
    private $_cache_prefix = 'ad_route_';
    private $_redis;
    public $table = 'table_route';
    public $split_rule = array(
        'tongji' => 32
    );
    public function __construct()
    {
        parent::__construct();
        $this->load->library('lib_redis');
        $this->_redis = $this->lib_redis;
    }
    /**
     * 获取路由信息
     * @author  zhangxin
     * @param   string  $type   业务类型
     * @param   int     $target 分表维度ID
     * @return  array
     */ 
    public function get_info($type,$target)
    {
        $cache_key = $this->_cache_prefix.$type."_".$target;
        $cache_value = $this->_redis->get($cache_key);
        if(!empty($cache_value))
        {
            return unserialize($cache_value);
        }
        else
        {
            $res = $this->fetch_master_row(array('type'=>$type,'target'=>$target));
            if(empty($res))
            {
                $t_data = array(
                    'tongji'=>$type.'_tongji_'.(int)fmod($target,$this->split_rule['tongji'])
                );
                $r_data = array(
                    'type' => $type,
                    'target' => $target,
                    'data' => serialize($t_data)
                );
                $insert = $this->insert($r_data);
                if($insert)
                {
                    $this->_redis->set($cache_key,serialize($t_data));
                    return $t_data;
                }
            }
            else
            {
                return unserialize($res['data']);
            }
        }
        return false;
    }
}

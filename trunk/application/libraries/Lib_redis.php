<?php
/**
 * Predis基类
 */
class Lib_redis
{
    public $client = null;

    public function __construct($params = array())
    {
        //获取数据库配置文件
       // $redis_config = $this->get_redis_config();
       // if (empty($redis_config))
       // {
       //     throw new Exception('redis数据库配置错误！');
       // }
       // if(is_array($redis_config))
       // {
       //     $servers = array();
       //     foreach ($redis_config as $key => $value)
       //     {
       //         $servers[] = 'tcp://' . $value;
       //     }
       //     $options = array('cluster' => 'redis');
       //     $this->client = new \Predis\Client($servers, $options);
       // }
       // elseif(is_string($redis_config))
       // {
       //     $this->client = new \Predis\Client("tcp://".$redis_config);
       // }
    }
    /**
     * 获取数据库配置
     * @param string $type
     * @return mixed
     */
    private function get_redis_config($type = 'cluster')
    {
        $CI =& get_instance();
        //加载redis的配置文件
        $CI->config->load('redis', TRUE, TRUE);
        $config = $CI->config->item('redis');
        $config = $config[$type];
        return $config;
    }
    /**
     * redis方法执行
     */ 
    function __call($name,$arguments)
    {
        $method_lower = strtolower($name);
        //被禁用的方法
        switch ($method_lower) {
            case 'flushdb':
            case 'flushall':
                exit($name . '方法已被禁用,如果要使用请联系运维人员!');
                break;
            default:
                break;
        }

        return call_user_func_array(array($this->client, $name), $arguments);
    }
}

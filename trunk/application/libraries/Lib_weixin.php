<?php
/**
 * 微信接口
 * @author zhangxin
 * @example
 * 
    //初始化微信认证类
    $this->load->library('lib_weixin');
    
    //授权代码
    $oauth_res = $this->lib_weixin-->oauth ( array (
            'cur_url' => 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
    ) );
    if(!$oauth_res)
    {
        exit('auth fail!');
    }
    print_r($oauth_res);
    
    //获取用户信息
    $open_id = $oauth_res['open_id'];
    $result = $this->lib_weixin-->get_user_info($open_id);
    print_r($result);
 */
class Lib_weixin {
    private $_options;
    private $_appkey = 'BGDhtCB7]8L3nU)!'; //给视频直播分配的appkey
    private $_wx_id;
    private $_auth_token = ')dsaMs230:s{d@sa';
    private $_api_token = '26214078';
    private $_api_host = array();
    private $_lib_http;
    public function __construct($options = array()){
        
        $this->_options = $options ? $options : $this->get_weixin_config();
       
        $this->_wx_id = $this->_options['wx_id'];
        if(ENVIRONMENT == 'development')
        {
            $this->_api_host = array(
                'oauth'=>'http://m.bch.leju.com/',  //授权域名
                'weixin'=>'http://weixin.bch.leju.com/',  //接口域名
            );
        }
        elseif(ENVIRONMENT == 'production')
        {
            $this->_api_host = array(
                'oauth'=>'http://m.leju.com/',  //授权域名
                'weixin'=>'http://weixin.leju.com/',  //接口域名
            );
        }
        $this->_ci = & get_instance();
        $this->_ci->load->library('lib_http',array(),'lib_api_weixin');
        $this->_lib_http = $this->_ci->lib_api_weixin;
    }
    /**
     * 授权代码
     * @param   array   $data
     * $data 数据说明：
     *        weixin_house_id:微信公众号
     *        cur_url:授权后回跳地址
     */ 
    public function oauth($data)
    {
        //解析参数
        $wx_id = isset($_GET['wx_id']) ? $_GET['wx_id'] : 0;
        $open_id = isset($_GET['openid']) ? $_GET['openid'] : 0;
        $url_sign = isset($_GET['sign']) ? $_GET['sign'] : 0;
        $auth_sign = isset($_GET['wx_auth_sign']) ? $_GET['wx_auth_sign'] : 0;
        $auth_time = isset($_GET['wx_auth_time']) ? $_GET['wx_auth_time'] : 0;
        if(!$open_id)
        {
            $weixin_house_id=$this->_wx_id;
            $cur_url=$data['cur_url'];
            $urlid=isset($data["urlid"])?$data['urlid']:'u103';
            $coopre=isset($data["coopre"])?$data['coopre']:'';
            //解析链接地址
            $url_info=parse_url($cur_url);
            //重新构造参数
            $output = array();
            if(isset($url_info['query']))
            {
                parse_str($url_info['query'], $output);
                unset($output['wx_id']);
            }
            $params='';
            foreach($output as $k=>$v)
            {
                if(stripos($v,'http://')!==false || stripos($v,'https://')!==false)
                {
                    $params.="&param[$k]=".urlencode($v);
                }
                else
                {
                    $params.="&param[$k]=$v";                            
                }
            }
            $appkey=$this->_get_auth_appkey($weixin_house_id);
            $plus=ltrim($url_info["path"],'/');
            if(!empty($cur_url))
            {
                $jump_uri = $this->_api_host['oauth']."?site=api&ctl=weixin_oauth&act=redirect&appkey=$appkey&coopre={$coopre}&urlid={$urlid}&returl=".urlencode($cur_url);
            }
            else
            {
                $jump_uri = $this->_api_host['oauth']."?site=api&ctl=weixin_oauth&act=redirect&appkey=$appkey&coopre={$coopre}&urlid={$urlid}&plus={$plus}{$params}";
            }
            
            header('Location: ' . $jump_uri);
            exit;
        }
        if($this->_check_auth_sign($wx_id,$open_id,$auth_time,$auth_sign))
        {
            return array(
                'open_id'=>$open_id
            );
        }
        return false;
    }
    /**
     * 新版授权签名验证
     * @param   int     $wx_id      公众号ID
     * @param   string  $openid     用户open_id
     * @param   string  $url_sing   签名字符串
     * @return  boolean
     */ 
    private function _check_auth_sign($wx_id,$openid,$auth_time,$url_sign)
    {
        $token=$this->_auth_token;
        $sign = md5($wx_id . $openid . $token .$auth_time);
        if($sign==$url_sign && time() - $auth_time < 30)
        {
            return true;
        }
        return false;
    }
    /**
     * 获取授权的appkey
     * @param   string  $wx_id  公众号ID
     * @return  string
     */ 
    private function _get_auth_appkey($wx_id)
    {
        $app_code=array('0'=>'G','1'=>'N','2'=>'A','3'=> 'Q', '4'=> 'B', '5'=> '2', '6'=> '9', '7'=>'a', '8'=> 'f', '9'=> 'F');
        $wx_id=strval($wx_id);
        $wx_code=str_split($wx_id);
        $result='';
        foreach($wx_code as $v)
        {
            $result.=$app_code[$v];
        }
        return $result;
    }
    /**
     * 获取用户信息
     * @param   string  $open_id            用户open_id
     * @return  array(错误：{"error_code":"错误编号","error":"错误信息"},正确：{entry:用户信息数组})
     */ 
    public function get_user_info($open_id)
    {
        $url = $this->_api_host['weixin'].'api/user/get_weixin_oauth_info.json';
        $data['weixin_house_id'] = $this->_wx_id;
        $data['open_id'] = $open_id;
        $p_data              = $data;
        $p_data['timestamp'] = time();
        $p_data['sign']      = $this->create_sign($this->_api_token, $p_data);
        $res = $this->_lib_http->post($url,$p_data);
        return $res;
    }
    /**
    * 生成sign
    * @param string $token  签名token
    * @param array  $data   签名数据
    * @return boolean|string
    */
    public function create_sign($token, $data)
    {
        if(empty($data) || empty($token) || empty($data['timestamp']))
        {
            return false;
        }
        ksort($data);
        $tmpstr = http_build_query($data);
        $sign = md5($tmpstr.$token);
        return $sign;
    }
    /**
     * 获取配置
     * @param string $type
     * @return mixed
     */
    private function get_weixin_config()
    {
        $CI =& get_instance();
        //加载redis的配置文件
        $CI->config->load('weixin', TRUE, TRUE);
        $config = $CI->config->item('weixin');
        $config = $config['options'];
        return $config;
    }
}
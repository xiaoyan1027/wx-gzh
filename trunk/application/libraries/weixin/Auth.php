<?php
/**
 * 微信授权代码
 * @author zhangxin
 *
 */
class Auth{
    private $_authorizer_appid;
    private $_component_appid;
    private $_component_appsecret;
    private $_component_token;
    private $encodingAesKey;
    private $_lib_redis;
    private $_wx_errlog;
    private $_lib_http;
    private $_api_host = 'https://api.weixin.qq.com/';
    private $_authorizer_refresh_token;
    private $_ci;
    private $_receive;
    public function __construct($config = array()){
        $this->_ci = & get_instance();

        if ($this->_ci->config->load('weixin', TRUE, TRUE))
    	{
    		$global_config = $this->_ci->config->item('weixin');
    	}

        $this->_component_appid = isset($config['component_appid']) ? $config['component_appid'] : $global_config['options']['component_appid'];
        $this->_component_appsecret = isset($config['component_appsecret']) ? $config['component_appsecret'] : $global_config['options']['component_appsecret'];
        $this->_component_token = isset($config['component_token']) ? $config['component_token'] : $global_config['options']['component_token'];
        $this->encodingAesKey = isset($config['component_aeskey']) ? $config['component_aeskey'] : $global_config['options']['component_aeskey'];
        $this->_template_authorizer_appid = isset($global_config['options']['template_authorizer_appid']) ? $global_config['options']['template_authorizer_appid'] : '';
        $this->_authorizer_appid = isset($config['authorizer_appid']) ? $config['authorizer_appid'] : '';
        $this->_lib_redis = $this->_ci->lib_redis;

        $this->_ci->load->model('service/component_authorize_model');
        $this->_ci->load->library('lib_http',array('host'=>$this->_api_host),'lib_http_auth');
        $this->_lib_http = $this->_ci->lib_http_auth;
		$this->_lib_http->ssl_verifypeer = 'CURL_SSLVERSION_TLSv1';
        $this->_ci->load->model('api/logger_model');
        $this->_logger_model = $this->_ci->logger_model;
    }
    /**
     * 获取第三方授权平台APPID
     */
    public function get_component_appid()
    {
        return $this->_component_appid;
    }
    /**
	 * 跳转至微信页面，获取code
	 */
	public function get_code($redirect_uri = '', $state='', $scope='snsapi_base')
	{
	    $redirect_uri = empty($redirect_uri) ? 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] : $redirect_uri;
        $redirect_uri=urlencode($redirect_uri);
		$oauth_jump_url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->_authorizer_appid}&redirect_uri={$redirect_uri}&response_type=code&scope={$scope}&state={$state}&component_appid={$this->_component_appid}#wechat_redirect";
		header('Location: ' . $oauth_jump_url);
		exit;
	}
    /**
     * 调整至第三方授权页面，获取auth_code
     */
    public function get_auth_code($redirect_uri='')
    {
        $auth_code = $this->_ci->input->get('auth_code');
	    $expires_in = $this->_ci->input->get('expires_in');
        if($auth_code && $expires_in)
        {
            return $auth_code;
        }
        $pre_auth_code=$this->get_pre_auth_code();
        $redirect_uri = empty($redirect_uri) ? 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'] : $redirect_uri;
        $redirect_uri=urlencode($redirect_uri);
        $oauth_jump_url="https://mp.weixin.qq.com/cgi-bin/componentloginpage?component_appid={$this->_component_appid}&pre_auth_code={$pre_auth_code}&redirect_uri={$redirect_uri}&auth_type=2";
    
        header('Location: ' . $oauth_jump_url);
		exit;
    }
    /**
     * 使用授权码换取公众号的授权信息
     */
    public function get_auth_info($authorization_code='')
    {
        //获取公众号的授权信息
        $component_access_token=$this->get_component_access_token();
        if(!$component_access_token)
        {
            return false;
        }
        $authorization_code=$authorization_code?$authorization_code:$this->get_auth_code();
		$url = "cgi-bin/component/api_query_auth?component_access_token={$component_access_token}";
        $params=array(
                'component_appid'=>$this->_component_appid,
                'authorization_code'=>$authorization_code
        );
		$res = $this->_lib_http->post($url,json_encode($params));
		if($res)
		{
    		if(isset($res['errcode']))
    		{
                $this->_logger_model->fail($res,$params,'POST',$this->_api_host.$url);
    			return false;
    		}

            //缓存授权方access_token
    		$cache_key = 'weixin_authorizer_access_token_' . $this->_component_appid . "_" . $res['authorization_info']['authorizer_appid'];
            $expire = $res['authorization_info']['expires_in'] - 600;
    		$cache_data = array(
    		    'access_token' => $res['authorization_info']['authorizer_access_token'],
    		);
    		$cache_data = serialize($cache_data);
    		$this->_lib_redis->set($cache_key, $cache_data);
    		$this->_lib_redis->expire($cache_key, $expire);
            //存储到数据库
            $data = array(
                'component_appid' => $this->_component_appid,
                'authorizer_appid' => $res['authorization_info']['authorizer_appid'],
                'authorizer_access_token' => $res['authorization_info']['authorizer_access_token'],
                'authorizer_refresh_token' => $res['authorization_info']['authorizer_refresh_token'],
                'func_info' => json_encode($res['authorization_info']['func_info']),
                'expires_in' => time() + $res['authorization_info']['expires_in'] - 600
            );
            $this->_ci->component_authorize_model->add($data);

            $this->_logger_model->success($res,$params,'POST',$this->_api_host.$url);
            //返回公众号的授权信息
    		return $res;
		}
		return false;
    }

    /**
     * 使用授权码换取公众号的授权信息
     */
    public function get_authorizer_info($authorizer_appid)
    {
        $component_access_token=$this->get_component_access_token();
        if(!$component_access_token)
        {
            return false;
        }
		$url = "cgi-bin/component/api_get_authorizer_info?component_access_token={$component_access_token}";
        $params=array(
                'component_appid'=>$this->_component_appid,
                'authorizer_appid'=>$authorizer_appid
        );
		$res = $this->_lib_http->post($url,json_encode($params));
		if($res)
		{
    		if(isset($res['errcode']))
    		{
    		    $this->_logger_model->fail($res,$params,'POST',$this->_api_host.$url);

    			return false;
    		}
            $this->_logger_model->success($res,$params,'POST',$this->_api_host.$url);
    		return $res;
		}
		return false;
    }

    /**
     * 获取授权方令牌
     */
    public function get_authorizer_access_token($authorizer_appid = '')
    {
        $authorizer_appid = $authorizer_appid ? $authorizer_appid : $this->_authorizer_appid;
        //缓存提取公众号的授权信息
        $cache_key = 'weixin_authorizer_access_token_' . $this->_component_appid . "_" . $authorizer_appid;
	    $cache_value = $this->_lib_redis->get($cache_key);
	    if($cache_value)
	    {
	        $cache_value = unserialize($cache_value);
	        return $cache_value['access_token'];
	    }
        //数据库读取授权信息
        $authorize_info = $this->_ci->component_authorize_model->get_info(array('component_appid' => $this->_component_appid,'authorizer_appid' => $authorizer_appid));
        if(empty($authorize_info))
        {
            return false;
        }
        if($authorize_info['expires_in'] > time())
        {
            return $authorize_info['authorizer_access_token'];
        }
        //获取公众号的授权信息
        $component_access_token=$this->get_component_access_token();
        if(!$component_access_token)
        {
            return false;
        }
		$url = "cgi-bin/component/api_authorizer_token?component_access_token={$component_access_token}";
        $params=array(
                'component_appid'=>$this->_component_appid,
                'authorizer_appid'=>$authorizer_appid,
                'authorizer_refresh_token'=>$authorize_info['authorizer_refresh_token']
        );
		$res = $this->_lib_http->post($url,json_encode($params));
		if($res)
		{
    		if(isset($res['errcode']))
    		{
    		    $this->_logger_model->fail($res,$params,'POST',$this->_api_host.$url);
    			return false;
    		}

            //缓存授权方access_token
            $expire = $res['expires_in'] - 600;
    		$cache_data = array(
    		    'access_token' => $res['authorizer_access_token'],
    		);
    		$cache_data = serialize($cache_data);
    		$this->_lib_redis->set($cache_key, $cache_data);
    		$this->_lib_redis->expire($cache_key, $expire);
            //存储到数据库
            $data = array(
                'component_appid' => $this->_component_appid,
                'authorizer_appid' => $authorizer_appid,
                'authorizer_access_token' => $res['authorizer_access_token'],
                'authorizer_refresh_token' => $res['authorizer_refresh_token'],
                'expires_in' => time() + $res['expires_in'] - 600
            );
            $this->_ci->component_authorize_model->add($data);
            $this->_logger_model->success($res,$params,'POST',$this->_api_host.$url);
            //返回公众号的授权信息
    		return $res['authorizer_access_token'];
		}
		return false;
    }
    public function get_component_verify_ticket($component_appid)
    {
        $cache_ticket = 'weixin_component_verify_ticket_' . $component_appid;
	    $component_verify_ticket = $this->_lib_redis->get($cache_ticket);
        if(false && !empty($component_verify_ticket))
        {
            return $component_verify_ticket;
        }
        $data_component_ticket = Table_model::get_instance('weixin_component_ticket');
        $data=$data_component_ticket->fetch_row(array('appid'=>$component_appid));
        if(!empty($data))
        {
            return $data['ticket'];
        }
        return false;
    }
    /**
     * 获取第三方平台access_token
     * @return  string|boolean
     */
    public function get_component_access_token()
    {

        $cache_key = 'weixin_component_access_token_' . $this->_component_appid;
        //$ttl = $this->_lib_redis->ttl($cache_key);
	    $cache_value = $this->_lib_redis->get($cache_key);
	    if($cache_value)
	    {
	        $cache_value = unserialize($cache_value);
	        return $cache_value['access_token'];
	    }
	    $this->_component_verify_ticket = $this->get_component_verify_ticket($this->_component_appid);
        if(empty($this->_component_verify_ticket))
        {
            return false;
        }
        //获取第三方平台access_token
		$url = "cgi-bin/component/api_component_token";
        $params=array(
                'component_appid'=>$this->_component_appid,
                'component_appsecret'=>$this->_component_appsecret,
                'component_verify_ticket'=>$this->_component_verify_ticket
        );
		$res = $this->_lib_http->post($url,json_encode($params));
		if($res)
		{
        		if(isset($res['errcode']))
        		{
        		    $this->_logger_model->fail($res,$params,'POST',$this->_api_host.$url);
    
        			return false;
        		}
            //缓存第三方平台access_token
        		$expire = $res['expires_in'] ? $res['expires_in'] - 600 : 3600;
        		$cache_data = array(
        		    'access_token' => $res['component_access_token']
        		);
        		$cache_data = serialize($cache_data);
        		$this->_lib_redis->set($cache_key, $cache_data);
        		$this->_lib_redis->expire($cache_key, $expire);
        		// 调试微信access_token有效性问题
        		$ttl = $this->_lib_redis->ttl($cache_key);
            $debug_str = json_encode($res).'ttl:'.$ttl.'cache_key:'.$cache_key;
            $this->_lib_redis->set('access_token_debug_key',$debug_str);
            $this->_logger_model->success($res,$params,'POST',$this->_api_host.$url);
            //返回第三方平台access_token
    		    return $res['component_access_token'];
		}
		return false;
    }
    /**
     * 获取第三方平台pre_auth_code
     * @return  string|boolean
     */
    public function get_pre_auth_code()
    {
        //获取第三方平台pre_auth_code
        $component_access_token=$this->get_component_access_token();
		$url = "cgi-bin/component/api_create_preauthcode?component_access_token={$component_access_token}";
        $params=array(
                'component_appid'=>$this->_component_appid
        );
		$res = $this->_lib_http->post($url,json_encode($params));
		if($res)
		{
    		if(isset($res['errcode']))
    		{
    		    $this->_logger_model->fail($res,$params,'POST',$this->_api_host.$url);

    			return false;
    		}

            $this->_logger_model->success($res,$params,'POST',$this->_api_host.$url);
            //返回第三方平台pre_auth_code
    		return $res['pre_auth_code'];
		}
		return false;
    }
    /**
     * 通过code获取Access Token
     * @return array {access_token,expires_in,refresh_token,openid,scope}
     */
    public function get_user_access_token($code)
    {
        if (!$code) return false;
        $component_access_token=$this->get_component_access_token();
        $url = "sns/oauth2/component/access_token?appid={$this->_authorizer_appid}&code={$code}&grant_type=authorization_code&component_appid={$this->_component_appid}&component_access_token={$component_access_token}";
        $res = $this->_lib_http->get($url);
        if($res)
		{
    		if(isset($res['errcode']) && $res['errcode'] != 0)
    		{
    		    $this->_logger_model->fail($res,array(),'GET',$this->_api_host.$url);

    			return false;
    		}
            $this->_logger_model->success($res,array(),'GET',$this->_api_host.$url);
            return $res;
		}
		return false;
    }
    /**
     * 获取授权后的用户资料,拉取用户信息
     * @param string $access_token
     * @param string $openid
     * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege}
     */
    public function get_oauth_userinfo($access_token,$openid){
        $url = 'sns/userinfo?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
        $res = $this->_lib_http->get($url);
        if ($res)
        {
            if(isset($res['errcode']) && $res['errcode'] != 0)
            {
                $this->_logger_model->fail($res,array(),'GET',$this->_api_host.$url);
                return false;
            }
            $this->_logger_model->success($res,array(),'GET',$this->_api_host.$url);
            return $res;
        }
        return false;
    }
    /**
	 * 用户授权
	 * @param string $show_oauth_page 是否显示授权页面，显示则能获取用户信息，不显示则获取open_id
	 * @param string $redirect_uri 跳转地址
	 */
	public function user_auth($show_oauth_page = false, $redirect_uri = '')
	{
	    $code = lib_context::get('code', lib_context::T_STRING);
	    $state = lib_context::get('state', lib_context::T_STRING);
        $appid = lib_context::get('appid', lib_context::T_STRING);
	    $scope = $show_oauth_page ? 'snsapi_userinfo' : 'snsapi_base';
	    if(!$code && !$state && !$appid)
	    {
	    	$this->get_code($redirect_uri, 'leju_oauth', $scope);
	    }
	    if($code && $state == 'leju_oauth')
	    {
	        //用户拒绝授权
	    	if($code == 'authdeny')
	    	{
	    		return false;
	    	}
	    	//获取用户access_token
	    	$res = $this->get_user_access_token($code);
	    	if(!$res)
	    	{
	    		return false;
	    	}
	    	$this->open_id = $res['openid'];
	    	if($show_oauth_page)
	    	{
	    		//获取用户信息
	    	    $userinfo = $this->get_oauth_userinfo($res['access_token'],$this->open_id);
	    	    if ($userinfo && !empty($userinfo['nickname']))
	    	    {
	    	        return $userinfo;
	    	    }
	    	    else
	    	    {
	    	        return false;
	    	    }
	    	}
	    	else
	    	{
	    	    //获取用户信息
	    	    $userinfo = $this->get_oauth_userinfo($res['access_token'],$this->open_id);
	    	    if ($userinfo && !empty($userinfo['nickname']))
	    	    {
	    	        return $userinfo;
	    	    }
	    	    else
	    	    {
	    	        return array('openid' => $this->open_id);
	    	    }
	    	}
	    }
	    return false;
	}

    /**
     * 第三平台接收消息
     * @return string
     */
    public function get_message() {
        include_once(APPPATH.'libraries/weixin/wxBizMsgCrypt.php');
        $signature = $this->_ci->input->get('msg_signature');
        $timestamp = $this->_ci->input->get('timestamp');
        $nonce = $this->_ci->input->get('nonce');
        $postdata = isset($GLOBALS["HTTP_RAW_POST_DATA"]) ? $GLOBALS["HTTP_RAW_POST_DATA"] : '';
        $pc = new WXBizMsgCrypt($this->_component_token, $this->encodingAesKey, $this->_component_appid);
        $errCode = $pc->decryptMsg($signature, $timestamp, $nonce, $postdata, $msg);
        if ($errCode == 0) {
            $this->_receive = (array) simplexml_load_string($msg, 'SimpleXMLElement', LIBXML_NOCDATA);
        }
        return $this->_receive;
    }
    /**
     * 加密信息
     */ 
    public function encrypt_message($text,$timeStamp,$nonce)
    {
        include_once(APPPATH.'libraries/weixin/wxBizMsgCrypt.php');
        $pc = new WXBizMsgCrypt($this->_component_token, $this->encodingAesKey, $this->_component_appid);
        $encryptMsg = '';
        $errCode = $pc->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);
        if ($errCode == 0) {
        	return $encryptMsg;
        }
        return false;
    }
    /**
     * 获取小程序代码模板
     */
    public function get_code_template_list()
    {
        $component_access_token=$this->get_component_access_token();
        if(!$component_access_token)
        {
            return false;
        }
		$url = "wxa/gettemplatelist?access_token={$component_access_token}";
		$res = $this->_lib_http->get($url);

		if($res)
		{
    		if(isset($res['errcode']) && $res['errcode'] != 0)
    		{
    		    $this->_logger_model->fail($res,array(),'GET',$this->_api_host.$url);

    			return false;
    		}
            $this->_logger_model->success($res,array(),'GET',$this->_api_host.$url);
    		return $res;
		}
		return false;
    }


    /**
     * 获取帐号下已存在的模板列表
     * @link https://developers.weixin.qq.com/miniprogram/dev/api/notice.html#%E6%A8%A1%E7%89%88%E6%B6%88%E6%81%AF%E7%AE%A1%E7%90%86
     * @param array $params
     * @example $params['offset'] = 0;
     * @example $params['count'] = 20; 最大值 20
     * @example $params['authorizer_appid'] = 'wx54226153924b95cd'
     * @author zhangxin
     * @return array
     */
    public function get_code_msg_list($params)
    {
        
       // $component_access_token=$this->get_component_access_token();
       $authorizer_access_token=$this->get_authorizer_access_token($params['authorizer_appid']);
       unset($params['authorizer_appid']);
       if(!$authorizer_access_token)
           return false;
        
        $url = "cgi-bin/wxopen/template/list?access_token={$authorizer_access_token}";
        $res = $this->_lib_http->post($url, json_encode($params));
        if($res)
        {
        	if(isset($res['errcode']) && $res['errcode'] != 0)
        	{
        	    $this->_logger_model->fail($res,$params,'POST',$this->_api_host.$url);
                                                                                      
        		return false;
        	}
            $this->_logger_model->success($res, $params,'POST',$this->_api_host.$url);
        	return $res;
        }
        return false;
    }




     /**
     * 验证是否有效
     * @return boolean
     */
    public function valid() {
        $check_res = $this->check_signature();
        if (!$check_res) {
            die('no access');
        }

        //第一次验证接入
        $echostr = $this->_ci->input->get('echostr');
        if (!empty($echostr)) {
            die($echostr);
        }

        return true;
    }

    /**
     * 验证
     * @return boolean
     */
    public function check_signature() {
        $signature = $this->_ci->input->get('signature');
        $timestamp = $this->_ci->input->get('timestamp');
        $nonce = $this->_ci->input->get('nonce');

        $tmpArr = array($this->_token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);

        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        }

        return false;
    }

    /**
     * 微信登录code 换取 session_key
     */
    public function jscode2session($data)
    {
        $component_access_token=$this->get_component_access_token();
        if(!$component_access_token)
        {
            return false;
        }
		$url = "sns/component/jscode2session?appid={$data["authorizer_appid"]}&js_code={$data['js_code']}&grant_type=authorization_code&component_appid={$this->_component_appid}&component_access_token={$component_access_token}";

        $res = $this->_lib_http->get($url);
       /* echo time()."<br/>";
        echo date('Y-m-d H:i:s')."<br/>";
        echo $url."</br>";
        dump($res);die;*/
		if($res)
		{
    		if(isset($res['errcode']))
    		{
    		    $this->_logger_model->fail($res,array(),'GET',$this->_api_host.$url);

    			return false;
    		}
            $this->_logger_model->success($res,array(),'GET',$this->_api_host.$url);
    		return $res;
		}
		return false;
    }

    /**
     * 返回发送方
     * @return boolean
     */
    public function get_rev_form() {
        return isset($this->_receive['FromUserName']) ? $this->_receive['FromUserName'] : false;
    }

    /**
     * 返回接收方
     * @return boolean
     */
    public function get_rev_to() {
        return isset($this->_receive['ToUserName']) ? $this->_receive['ToUserName'] : false;
    }

    /**
     * 设置回复消息
     * @param type $string
     * @return string
     */
    public function reply_text($text = '') {
        $msg = array(
            'ToUserName' => $this->get_rev_form(),
            'FromUserName' => $this->get_rev_to(),
            'MsgType' => 'text',
            'Content' => $text,
            'CreateTime' => time(),
        );
        return $this->reply($msg);
    }
    
    /**
     * 设置回复消息
     * @param type $string
     * @return string
     */
    public function reply_widget_data($text = '') {
        $msg = array(
            'ToUserName' => $this->get_rev_form(),
            'FromUserName' => $this->get_rev_to(),
            'MsgType' => 'widget_data',
            'Content' => $text,
            'CreateTime' => time(),
        );
        return $this->reply($msg);
    }

    
    /**
     * 设置回复图文消息
     * @param array $newsData
     * @return string
     */
    public function reply_news($newsData = array()) {
        $count = count($newsData);

        $msg = array(
            'ToUserName' => $this->get_rev_form(),
            'FromUserName' => $this->get_rev_to(),
            'MsgType' => 'news',
            'CreateTime' => time(),
            'ArticleCount' => $count,
            'Articles' => $newsData,
        );
        return $this->reply($msg);
    }

    /**
     * 回复消息
     * @param array $msg
     * @param boolean $return
     * @return type
     */
    public function reply($msg = array(), $return = false) {
        include_once(APPPATH.'libraries/weixin/wxBizMsgCrypt.php');
        $pc = new WXBizMsgCrypt($this->token, $this->encodingAesKey, $this->_component_appid);
        $timestamp = time();
        $nonce = mt_rand(10000, 99999);

        $xmldata = $this->xml_encode($msg);

        $errCode = $pc->encryptMsg($xmldata, $timestamp, $nonce, $encryptMsg);
        if ($return)
            if ($errCode == 0) {
                return $encryptMsg;
            } else {
                return '';
            } else
            echo $encryptMsg;
    }
    /**
	 * 数据XML编码
	 * @param mixed $data 数据
	 * @return string
	 */
	public static function data_to_xml($data) {
	    $xml = '';
	    foreach ($data as $key => $val) {
	        is_numeric($key) && $key = "item id=\"$key\"";
	        $xml    .=  "<$key>";
	        $xml    .=  ( is_array($val) || is_object($val)) ? self::data_to_xml($val)  : self::xmlSafeStr($val);
	        list($key, ) = explode(' ', $key);
	        $xml    .=  "</$key>";
	    }
	    return $xml;
	}
    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $root 根节点名
     * @param string $item 数字索引的子节点名
     * @param string $attr 根节点属性
     * @param string $id   数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    public function xml_encode($data, $root = 'xml', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8') {
        if (is_array($attr)) {
            $_attr = array();
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml = "<{$root}{$attr}>";
        $xml .= self::data_to_xml($data, $item, $id);
        $xml .= "</{$root}>";
        return $xml;
    }
    /**
     * 发送客服消息
     * @link https://developers.weixin.qq.com/miniprogram/dev/api/custommsg/conversation.html
     * @param array $params
     */
    public function send_custom_message($data)
    {
        $authorizer_access_token=$this->get_authorizer_access_token($data['authorizer_appid']);
        if(!$authorizer_access_token)
        {
            return false;
        }
		$url = "cgi-bin/message/custom/send?access_token=".$authorizer_access_token;
        $params = array(
            "touser" => $data['touser'],
            'msgtype' => $data['msgtype'],
            'text' => array('content' => $data['content']),
        );
		$res = $this->_lib_http->post($url,json_encode($params));
		if($res)
		{
		    $params['authorizer_appid'] = $data['authorizer_appid'];
    		if(isset($res['errcode']) && $res['errcode'] != 0)
    		{
    		    $this->_logger_model->fail($res,$params,'POST',$this->_api_host.$url);

    			return false;
    		}
            $this->_logger_model->success($res,$params,'POST',$this->_api_host.$url);
    		return $res;
		}
		return false;
    }

    /**
     * 获取所有需要使用JS-SDK的页面配置信息
     * @author  zhangxin
     */
    public function get_js_sign_package($authorizer_appid)
    {
        $jsapi_ticket = $this->get_js_api_ticket($authorizer_appid);
        $timestamp = time();
        $nonce_str = $this->create_nonce_str();

        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket=$jsapi_ticket&noncestr=$nonce_str&timestamp=$timestamp&url=".CUR_URL;
        $signature = sha1($string);
        $sign_package = array(
          "noncestr"  => $nonce_str,
          "timestamp" => $timestamp,
          "signature" => $signature,
        );
        return $sign_package;
      }
      /**
       * 生成签名的随机串
       * @author    zhangxin
       */
      public function create_nonce_str($length = 16)
      {
            $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $str = "";
            for ($i = 0; $i < $length; $i++) {
              $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
            }
            return $str;
      }
      /**
       * 获取jsapi_ticket
       * @author    zhangxin
       */
      public function get_js_api_ticket($authorizer_appid)
      {
            $authorizer_access_token=$this->get_authorizer_access_token($authorizer_appid);
            if(!$authorizer_access_token)
            {
                return false;
            }
            $cache_key = 'weixin_jsapi_ticket_' . $authorizer_appid;
            $cache_value = $this->_lib_redis->get($cache_key);
    	    if($cache_value)
    	    {
    	        $cache_value = unserialize($cache_value);
    	        return $cache_value['jsapi_ticket'];
    	    }
    		$url = "cgi-bin/ticket/getticket?access_token=$authorizer_access_token&type=jsapi";
    		$res = $this->_lib_http->get($url);
    		if($res)
    		{
        		if($res['errcode']!=0)
        		{
                    $this->_logger_model->fail($res,array('authorizer_appid' => $authorizer_appid),'GET',$this->_api_host.$url);

        			return false;
        		}
        		$expire = $res['expires_in'] ? $res['expires_in'] - 600 : 3600;
        		$cache_data = array(
        		    'jsapi_ticket' => $res['ticket'],
        		);
        		$cache_data = serialize($cache_data);
        		$this->_lib_redis->set($cache_key, $cache_data);
        		$this->_lib_redis->expire($cache_key, $expire);
                $this->_logger_model->success($res,array('authorizer_appid' => $authorizer_appid),'GET',$this->_api_host.$url);
        		return $res['ticket'];
    		}
            return false;
      }

      /**
     * 获取微信卡券api_ticket
     * @author zhangxin
     */
    public function get_jscardticket($authorizer_appid) {
        if($authorizer_appid == 'wxb7f63d61476ef1a3')
        {
            $authorizer_access_token = '8_Bh2_ia3tI-5g1Jymz4moLgL32KJnOObIXFa0E7toKiGIG2-ikslIwRZFlS82fMdbbZf4HEFHQueQzbqL6zRBl9h5ZvasljfBj-_2Z8yNyZYXskMpFTeTxmqjim6_SGJuyuMcaM5yJl0KEIAtICIaAAAZTZ';
        }
        else
        {
            $authorizer_access_token = $this->get_authorizer_access_token($authorizer_appid);
        }


        if(!$authorizer_access_token)
        {
            return false;
        }
        $cache_key = 'weixin_card_api_ticket_' . $authorizer_appid;
        $cache_value = $this->_lib_redis->get($cache_key);
        if ($cache_value) {
            $cache_value = unserialize($cache_value);
            return $cache_value['ticket'];
        }
        $url = "cgi-bin/ticket/getticket?access_token={$authorizer_access_token}&type=wx_card";
        $res = $this->_lib_http->get($url);
        if ($res) {
            if($res['errcode']!=0)
    		{
                $this->_logger_model->fail($res,array('authorizer_appid' => $authorizer_appid),'GET',$this->_api_host.$url);
    			return false;
    		}
    		$expire = $res['expires_in'] ? $res['expires_in'] - 600 : 3600;
    		$cache_data = array(
    		    'ticket' => $res['ticket'],
    		);
    		$cache_data = serialize($cache_data);
    		$this->_lib_redis->set($cache_key, $cache_data);
    		$this->_lib_redis->expire($cache_key, $expire);
            $this->_logger_model->success($res,array('authorizer_appid' => $authorizer_appid),'GET',$this->_api_host.$url);
    		return $res['ticket'];
        }
        return false;
    }


}


<?php
/***************************************************
*
*   Filename: application/libraries/weixin/Basic_setting.php
*
*   Author: zhangxin
*   Description: 小程序基础信息设置，昵称 头像 描述 类目
*   Create: 2018-07-19 15:50:00
****************************************************/

class Basic_setting
{
    
    private $input;
    private $_api_host = 'https://api.weixin.qq.com/';
    private $_lib_http;
    private $_ci;
    private $_api_url;

    /**
     * 初始化
     * @author zhangxin
     */
    public function __construct()
    {
    
        $this->_ci = & get_instance();
        $this->input = $this->_ci->input;
        $this->_ci->load->library('lib_http',array('host'=>$this->_api_host),'lib_http_auth');
        $this->_lib_http = $this->_ci->lib_http_auth;
		$this->_lib_http->ssl_verifypeer = 'CURL_SSLVERSION_TLSv1';
    }

    /**
     * 修改小程序昵称设置
     * @author zhangxin
     */
    public function set_nickname($params)
    {
    
        $url = 'wxa/setnickname?access_token=' . $params['access_token'];
        unset($params['access_token']);
		$res = $this->_lib_http->post($url, json_encode($params));

        return $res;
    }
}

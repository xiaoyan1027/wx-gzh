<?php

/***************************************************
 *   Filename: Test.php
 *   Author: zhangxin
 *   Description: php项目文件描述
 *   Create: 2019-03-27 16:59
 ****************************************************/
require_once(APPPATH.'controllers/'.$RTR->directory.'Base.php');
class Test extends Base
{
    public function __construct() {
        parent::__construct();
        //连接数据库示例
        $this->load->model('test_model');
        //连接类库示例
        $this->load->library("lib_signature");

    }

    public function index(){

        if(isset($_GET['type'])) {
            $this->_api_fail(1001,'测试接口返回错误');
        }
        $this->_api_succ('测试接口返回正确');

        //连接数据库示例
        $this->test_model->get_data();
        //连接类库示例
        $sign = $this->lib_signature->encrypt( $str,'ceshi');



    }



}
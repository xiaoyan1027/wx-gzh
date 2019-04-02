<?php

/***************************************************
 *   Filename: Test_model.php
 *   Author: zhangxin11@leju.com
 *   Description: php项目文件描述
 *   Create: 2019-03-27 18:00
 ****************************************************/
class Test_model extends BASE_Model
{

    public $product_table = 'pet_admin_users';
    public $admin_start;
    public function __construct()
    {
        parent::__construct();
        $this->set_table($this->product_table);
    }

    /*
     * 添加数据
     * */
    public function get_data()
    {
        return $this->fetch_all();
    }
}
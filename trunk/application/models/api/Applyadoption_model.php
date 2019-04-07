<?php
/**
 * Created by PhpStorm.
 * User: zhangxin
 * Date: 2019/4/3
 * Time: 下午11:17
 */

class Applyadoption_model extends  BASE_Model
{

    public $product_table = 'pet_Apply_adoption';
    public $admin_start;
    public function __construct()
    {
        parent::__construct();
        $this->set_table($this->product_table);
    }

    /*
     * 获取数据
     * */
    public function get_data($params = array(),$order= 'id DESC',$offset= 0,$limit= 20,$group= '',$fileds= '*')
    {
        return $this->fetch_all($params, $order , $offset , $limit , $group , $fileds );
    }
}
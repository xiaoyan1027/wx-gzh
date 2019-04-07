<?php
/**
 * Created by PhpStorm.
 * User: zhangxin
 * Date: 2019/4/3
 * Time: 下午11:01
 */

class Relationadopt_model extends BASE_Model
{

    public $product_table = 'pet_relation_apply_adopt';
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

    public function get_count($where = array()) {

        return $this->fetch_count($where);

    }


}
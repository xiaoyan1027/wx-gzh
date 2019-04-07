<?php
/**
 * Created by PhpStorm.
 * User: zhangxin
 * Date: 2019/4/3
 * Time: 下午10:39
 */

class User_model extends BASE_Model
{

    public $product_table = 'pet_user';
    public $admin_start;
    public function __construct()
    {
        parent::__construct();
        $this->set_table($this->product_table);
    }

    /**
     * 单条数据
     */
    public function get_info($where = array(), $fileds = '*', $orderBy = 'id DESC', $groupBy = '', $offset = 0, $limit = 1){

        return $this->fetch_row($where,$fileds,$orderBy,$groupBy,$offset,$limit);

    }

    public function update_data($data = array(),$where = array()) {
        return $this->update($data,$where);
    }
}
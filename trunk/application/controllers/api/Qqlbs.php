<?php
/**
 * QQ地图
 * @author zhangxin
 */

require_once(APPPATH.'controllers/'.$RTR->directory.'Base.php');

class Qqlbs extends Base {

    public function __construct() {
        parent::__construct();
        $this->load->library('lib_api_qqlbs');
        $this->_open_log = TRUE;
    }
     
    public function search()
    {
        $keyword = $this->input->get('keyword');
        $boundary = $this->input->get('boundary');
        $filter = $this->input->get('filter');
        $orderby = $this->input->get('orderby') ? $this->input->get('orderby') : '_distance';
        $page_size = $this->input->get('page_size') ? $this->input->get('page_size') : 10;
        $page_index = $this->input->get('page_index') ? $this->input->get('page_index') : 1;
        if(empty($keyword))
        {
            $this->_api_fail(30001,'keyword不可以为空！');
        }
        elseif(empty($boundary))
        {
            $this->_api_fail(30002,'boundary码不可以空！');
        }
        $data = array(
            'keyword' => $keyword,
            'boundary' => $boundary,
            'filter' => $filter,
            'orderby' => $orderby,
            'page_size' => $page_size,
            'page_index' => $page_index
        );
        $res = $this->lib_api_qqlbs->search($data);
        if(isset($res['status']) && $res['status'] == 0)
        {
            $this->_api_succ($res);
        }
        else
        {
            $this->_api_fail(30003,$res['message']);
        }
    }
}
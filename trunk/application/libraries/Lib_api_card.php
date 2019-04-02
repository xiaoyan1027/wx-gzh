<?php
/***************************************************
*
*   Filename: .//application/libraries/Lib_api_card.php
*
*   Author: renfeng1@leju.com
*   Description: 心愿券
*   Create: 2018-07-04 16:23:01
****************************************************/


class Lib_api_card 
{

    private $_lib_http;
    private $_api_host;
    private $_logger_model;

    public function __construct() 
    {
        $this->_ci = & get_instance();

        if (ENVIRONMENT == 'development') {
            $this->_api_host = 'http://weixin.bch.leju.com/';
            //$this->_api_host = 'http://weixin.leju.com/';
        } else {
            $this->_api_host = 'http://weixin.leju.com/';
        }

        $this->_ci->load->library('lib_http', array('host' => $this->_api_host), 'lib_http_card');
        $this->_lib_http = $this->_ci->lib_http_card;
        $this->_ci->load->model('api/logger_model');
        $this->_logger_model = $this->_ci->logger_model;
    }


    /**
     * 获取卡券信息
     * @author:xionghui2@leju.com
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=95&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @param array $params
     * @return mixed
     */
    public function get_quan_info($params = array()) 
    {
        $result = $this->_lib_http->post('api/miniprogram/quan_info.html', $params, false);
        if ( isset($result['status']) && $result['status'] == 'succ' )
            $this->_logger_model->success($result, $params,'POST', $this->_api_host . '/api/miniprogram/quan_info.html');
        else
            $this->_logger_model->fail($result, $params,'POST', $this->_api_host . '/api/miniprogram/quan_info.html'); 

        return $result;
    }


    /**
     * 创建订单
     * @author:xionghui2@leju.com
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=96&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @param array $params
     * @return mixed
     */
    public function create_order_new($params = array()) 
    {
        $result = $this->_lib_http->post('api/miniprogram/create_order_new.html', $params, false);
        // 日志
        if ( isset($result['status']) && $result['status'] == 'succ' )
            $this->_logger_model->success($result, $params,'POST', $this->_api_host . '/api/miniprogram/create_order_new.html');
        else
            $this->_logger_model->fail($result, $params,'POST', $this->_api_host . '/api/miniprogram/create_order_new.html'); 

        return $result;
    }

    /**
     * 获取订单详情
     * @author:xionghui2@leju.com
     *
     * @param array $params
     * @return mixed
     */
    public function order_info($params = array()) 
    {
        $result = $this->_lib_http->post('api/miniprogram/order_info.html', $params, false);
        return $result;
    }

    /**
     * 创建支付订单
     * @author:xionghui2@leju.com
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=99&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @param array $params
     * @return mixed
     */
    public function create_pay_order($params = array()) 
    {
        $result = $this->_lib_http->post('api/miniprogram/create_pay_order.html', $params, false);

        // 日志
        if ( isset($result['status']) && $result['status'] == 'succ' )
            $this->_logger_model->success($result, $params,'POST', $this->_api_host . '/api/miniprogram/create_pay_order.html');
        else
            $this->_logger_model->fail($result, $params,'POST', $this->_api_host . '/api/miniprogram/create_pay_order.html'); 


        return $result;
    }


    /**
     * 收集用户信息
     * @author:xionghui2@leju.com
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=98&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @param array $params
     * @return mixed
     */
    public function user_info_collect($params = array()) 
    {

        $result = $this->_lib_http->post('api/miniprogram/user_info_collect.html', $params, false);

        // log
        if ( isset($result['status']) && $result['status'] == 'succ' )
            $this->_logger_model->success($result, $params,'POST', $this->_api_host . '/api/miniprogram/user_info_collect.html');
        else
            $this->_logger_model->fail($result, $params,'POST', $this->_api_host . '/api/miniprogram/user_info_collect.html'); 
        

        return $result;
    }

    /**
     * 获取适用楼盘
     * @author:xionghui2@leju.com
     *
     * @param array $params
     * @return mixed
     */
    public function city_change($params = array()) 
    {
        $result = $this->_lib_http->get('api/miniljqorder/city_change.html', $params, false);
        return $result;
    }

    /*
     * 订单详情
     * */
    public function get_all_order_info($params)
    {
        $result = $this->_lib_http->get('api/miniljqorder/get_all_order_info.html',$params,false);
        return $result;
    }

    /**
     * 退款
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=151&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     */
    public function apply_refund($params)
    {
        $result = $this->_lib_http->get('api/miniljqorder/apply_refund.html',$params,false);

        // 日志
        if ( isset($result['entry']) && $result['entry'] )
            $this->_logger_model->success($result, $params,'POST', $this->_api_host . '/api/miniprogram/apply_refund.html');
        else
            $this->_logger_model->fail($result, $params,'POST', $this->_api_host . '/api/miniprogram/apply_refund.html'); 

        return $result;
    }

    /**
     * 用户取消订单
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=144&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     */
    public function cancel_order($params)
    {
        $result = $this->_lib_http->get('api/miniljqorder/cancel_order.html',$params,false);
        // 日志
        if ( isset($result['entry']) && $result['entry'] )
            $this->_logger_model->success($result, $params,'POST', $this->_api_host . '/api/miniprogram/cancel_order.html');
        else
            $this->_logger_model->fail($result, $params,'POST', $this->_api_host . '/api/miniprogram/cancel_order.html'); 


        return $result;
    }

    /*
     * 心愿券订单详情
     * */
    public function quan_detail($params)
    {
        $result = $this->_lib_http->get('api/miniljqorder/quan_detail.html',$params,false);
        return $result;
    }

    /*
     * 卡包
     * */
    public function get_info($params)
    {
        $result = $this->_lib_http->get('api/miniljqcard/get_info.html',$params,false);
        return $result;
    }

    /*
     * 查看进度
     * */
    public function get_refund_detail($params)
    {
        $result = $this->_lib_http->get('api/miniljqorder/get_refund_detail.html',$params,false);
        return $result;
    }

    /**
     * 根据城市获取优惠券列表
     * @params array $params
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=128&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @return array 二维数组
     * @author renfeng1@leju.com
     */
    public function get_quan_list_by_city($params)
    {
    
        $list = $this->_lib_http->post('api/cooperate/get_quan_list_by_city.html',$params,false);

        // 日志记录
        if (isset($list['status']) && $list['status'] == 'succ')
             $this->_logger_model->success($list, $params,'POST', $this->_api_host . '/api/cooperate/get_quan_list_by_city.html');
        else
            $this->_logger_model->fail($list, $params,'POST', $this->_api_host . '/api/cooperate/get_quan_list_by_city.html');


        return $list;
    }

    /**
     * 获取心愿券的拼购及点赞设置
     * @params array $params
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=129&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @return array
     * @author renfeng1@leju.com
     */
    public function cooperate_quan_set($params)
    {

        $info = $this->_lib_http->post('/api/cooperate/cooperate_quan_set.html',$params,false);

        return $info;
    }

    /**
     * 订单点赞
     * @params array $params 
     * @link   http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=131&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @return  array
     * @author  renfeng1@leju.com
     */
    public function order_vote($params)
    {

        $res = $this->_lib_http->post('/api/cooperate/order_vote.html',$params,false);

        // 日志记录
        if (isset($res['status']) && $res['status'] == 'succ')
             $this->_logger_model->success($res, $params,'POST', $this->_api_host . '/api/cooperate/order_vote.html');
        else
            $this->_logger_model->fail($res, $params,'POST', $this->_api_host . '/api/cooperate/order_vote.html');

        return $res;
    }

    /**
     * 获取订单点赞数
     * @params  $params array
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=133&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @return  array
     * @author  guojun5@leju.com
     */
    public function order_vote_num($params)
    {
        $res_order_vote_num = $this->_lib_http->post('/api/cooperate/order_vote_num.html',$params,false);
        return $res_order_vote_num;
    }

    /**
     * 获取点赞记录
     * @params  $params array
     * @link http://api.mp.leju.com/#/home/project/inside/api/detail?groupID=-1&apiID=132&projectName=%E5%BF%83%E6%84%BF%E5%88%B8%E6%8E%A5%E5%8F%A3&projectID=23
     * @return  array
     * @author  guojun5@leju.com
     */
    public function get_order_vote_log($params)
    {
    
        $res = $this->_lib_http->post('/api/cooperate/get_order_vote_log.html',$params,false);
        return $res;
    }


}

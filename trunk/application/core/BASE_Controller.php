<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BASE_Controller extends CI_Controller {

    protected $_succ = 'succ';
    protected $_fail = 'fail';

    public $CI;
    public $user_info;
    public $account_type;
    public $lib_smarty;
    public function __construct() {
        parent::__construct();
        $this->_init();
        $this->_check_router();
        
    }
    /**
     * 初始化
     */ 
    protected function _init()
    {
        $this->CI = & get_instance();

    }
    
    /**
     * 路由权限限制
     */
    public function _check_router() {

        $filter_route = array(
            'index/index/index',
            'index/login/index',
            'index/login/logout',
            'index/login/send_code',
        );
        $path = $this->router->directory . $this->router->class . "/" . $this->router->method;
        return true;
        if(in_array($path, $filter_route)) {
            return true;
        }

    }

    /**
     * 信息提示
     * @param $message 提示内容
     * @author zhangxin
     */
    public function show_message($message = '', $url = '') {
        $this->assign('message', $message);
        $this->assign('url', $url, 'NO_DENY');
        $this->display_base('common/message');
        exit;
    }

    /**
     * 信息提示
     * @param $message 提示内容
     * @author zhangxin
     */
    public function show_alert_message($message = '', $url = '') {
        $this->assign('message', $message);
        $this->assign('url', $url, 'NO_DENY');
        $this->display_base('common/alert_message');
        exit;
    }

    /**
     * 解析基础模板
     * @param string $tmp_name
     */
    public function display_base($tmp_name) {
        $this->display($tmp_name);
        exit;
    }

    public function assign($key, $val , $xss_clean = TRUE) {
        if($xss_clean)
        {
            $val = xss_clean($val);
        }
        $this->lib_smarty->assign($key, $val);
    }

    public function display($html) {

        if (is_file($this->lib_smarty->template_dir .'/' . $html . '.html')) {

            $file = $html;

        } else {

            $path = $this->router->class;

            if ($this->router->directory) {
                $path = $this->router->directory . $path;
            }

            $file = $path . '/' . $html;
        }

        $this->assign('act', $this->router->method);
        $this->assign('con', $this->router->class);
        $this->assign('directory', $this->router->directory);

        $this->lib_smarty->display($file . '.html');
    }

    /**
     * AJAX Return
     * @param  string  $type       [类型(error, succ, info)]
     * @param  string  $reason     [错误原因]
     * @param  integer $error_code [错误码]
     * @return [type]              [description]
     */
    public function ajax_return($type = 'info', $code = 0, $reason = '',  $data = array())
    {
        $result           = array();
        $result['reason'] = $reason;
        $result['code']   = $code;
        $result['data']   = $data;

        switch (trim($type)) {
            case 'error':
                $result['status'] = 'fail';
                break;
            case 'succ':
                $result['status'] = 'succ';
                break;
            default:
                $result['status'] = 'info';
                break;
        }

        $callback = $this->input->get('callback');
        $return   = empty($callback) ? json_encode($result) : ' ' . $callback . "(" . json_encode($result) . ");";
        echo $return;
        exit;
    }
}

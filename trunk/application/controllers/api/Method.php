<?php
/**
 * Created by PhpStorm.
 * User: fernando
 * Date: 16/11/8
 * Time: 下午1:41
 */
require_once(APPPATH.'controllers/'.$RTR->directory.'Base.php');
class Method extends Base{

    public function __construct() {
        $this->_open_log = true;
        parent::__construct();
    }

    /**
     * 获取方法列表
     */
    public function get_method_list() {
        $sign = $this->input->post('sign');
        if (empty($sign)) {
            $this->_api_fail(10001, '签名不能为空!');
        }
        $params = $this->encrypt->decode($sign);
        $data = unserialize($params);

        if (empty($data['compose'])) {
            $this->_api_fail(10002, '组件名称不能为空!');
        }

        if (empty($data['controller'])) {
            $this->_api_fail(10003, '控制器名称不能为空!');
        }

        $controller = ucfirst($data['controller']);
        $file = $_SERVER['DOCUMENT_ROOT'] . '/application/controllers/'. $data['compose'] . '/' . $controller . '.php';

        if (!isset($file) && !file_exists($file)) {
            $this->_api_fail(10004, '文件不存在');
        }

        require_once $file;

        $method_list = get_class_methods($controller);
        if ($method_list) {
            $this->_api_succ($method_list);
        } else {
            $this->_api_fail(10005, '没有数据');
        }
    }
}
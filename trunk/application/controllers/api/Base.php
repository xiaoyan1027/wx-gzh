<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Base extends CI_Controller
{
    protected $_token='8wD^x2j3$X#*^Scb';
    protected $_succ = 'succ';
    protected $_fail = 'fail';
    protected $_open_log = FALSE;
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Lib_error_map');

    }
    /**
	 * 验证sign
	 * @param unknown $token
	 * @param unknown $data
	 * @return boolean
	 */
	protected function _check_sign($token,$method='post')
	{
	    if(is_array($method))
        {
            $data = $method;
        }
        elseif(is_string($method))
        {
            if($method=='post')
            {
                $data = $this->input->post(NULL, FALSE);
            }
    	    elseif($method=='get')
            {
                $data = $this->input->get(NULL, FALSE);
            }
        }

	    if(empty($data) || empty($token))
	    {
			return false;
		}
		if(!isset($data['timestamp']) || empty($data['timestamp']) || !isset($data['sign']) || empty($data['sign']))
		{
			return false;
		}
		$sign = $data['sign'];
		unset($data['sign']);
		$new_sign = $this->_create_sign($token, $data);
		if($sign == $new_sign)
		{
			return true;
		}
		return false;
	}
	/**
	 * 生成sign
	 * @param unknown $token
	 * @param unknown $data
	 * @return boolean|string
	 */
	protected function _create_sign($token, $data)
	{
	    if(empty($data) || empty($token) || empty($data['timestamp']))
	    {
			return false;
		}
		ksort($data);
		$tmpstr = http_build_query($data);
		$sign = md5($tmpstr.$token);
		return $sign;
	}

    /**
     * 检测key
     */
    protected function _check_key()
    {
        $appkey = $this->input->get('appkey');
        if(empty($appkey))
        {
            $appkey = $this->input->post('appkey');
        }

        $app_data = $this->ret_appkey();
        $appkeys = array();

        foreach ($app_data as $v)
        {
            $appkeys[] = $v['appkey'];
        }

        if(empty($appkey) || !in_array($appkey, $appkeys))
        {
             return false;
        }
        return true;
    }
    /**
     * api接口成功输出数据
     * @param int $total
     * @param array $data
     */
    protected function _api_succ($data,$callback='',$type=1)
    {
        header('Content-Type: application/json; charset=utf-8');
        if($type) {
            if(!empty($data))
            {
                $data = $this->null_to_empty($data);
            }
        }
        
        $return['entry'] = $data;
        $return['trace_id'] = $GLOBALS['_traceId'];
        $return['request_uri'] = parse_url(CUR_URL, PHP_URL_PATH);
        //记录日志
        if($this->_open_log)
        {
            $this->load->model('api/logger_model');
            $this->logger_model->success($return);
        }

        if (is_rpc()) {
            return $return;
        }

        $return_str = json_encode($return);
        // 如果从url中获取到了callback 并字符串合法
        $get_callback = isset($_GET['callback']) && php_safe_str($_GET['callback']) ? ($_GET['callback']) : '';
        $callback   = $callback ? $callback : $get_callback;

        echo $callback ? $callback."(".$return_str.")" : $return_str;
        exit;
    }
    /**
     * api接口失败输出数据
     * @param int $error_code
     * @param string $error
     */
    public function _api_fail($error_code = 0, $error = '', $callback='',$data='')
    {
        header('Content-Type: application/json; charset=utf-8');
        $return = array(
            'error_code' => $error_code,
            'error' => empty($error) ? $this->_ret_error_info($error_code) : $error,
            'data' => $data,
            'trace_id' => $GLOBALS['_traceId'],
            'request_uri' => parse_url(CUR_URL, PHP_URL_PATH)
        );
        //记录日志
        if($this->_open_log)
        {
            $this->load->model('api/logger_model');
            $this->logger_model->fail($return);
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if(is_rpc())
            {
                return $return;
            }
        }

        $return_str = json_encode($return);
        // 如果从url中获取到了callback 并字符串合法
        $get_callback = isset($_GET['callback']) && php_safe_str($_GET['callback']) ? ($_GET['callback']) : '';
        $callback   = $callback ? $callback : $get_callback;

        echo $callback ? $callback."(".$return_str.")" : $return_str;
        exit;
    }

    /**
     * api接口失败输出数据
     * @param array $error_info
     * @author zhangxin
     */
    public function _api_fail_new($error_info = array(), $callback='',$data='')
    {
        header('Content-Type: application/json; charset=utf-8');
        $return = array(
            'error_code' => $error_info['code'],
            'error'      => $error_info['error_msg'],
            'data'       => $data,
            'trace_id'   => $GLOBALS['_traceId']
        );
        //记录日志
        if($this->_open_log)
        {
            $this->load->model('api/logger_model');
            $this->logger_model->fail($return);
        }

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            if(is_rpc())
            {
                return $return;
            }
        }

        $return_str = json_encode($return);
        // 如果从url中获取到了callback 并字符串合法
        $get_callback = isset($_GET['callback']) && php_safe_str($_GET['callback']) ? ($_GET['callback']) : '';
        $callback   = $callback ? $callback : $get_callback;

        echo $callback ? $callback."(".$return_str.")" : $return_str;
        exit;
    }

    /**
     * 把输出接口的数据null转换为空串
     */
     public function null_to_empty($arr)
     {
     	if (is_array($arr))
        {
            foreach ($arr as $key => $val)
            {
                $arr[$key] = $this->null_to_empty($val);
            }
        }
        else
        {
            if($arr === null)
            {
            	$arr = '';
            }
            if($arr == 'null')
            {
            	$arr = '';
            }
        }
        return $arr;
     }

    private function _ret_error_info($error_code)
    {
        $error_info = array(
            20000 => '系统错误',
            20001 => '验证失败！',
            20002 => '参数错误！',
            20003 => '没有数据！',
            20004 => '数据重复！',
            20005 => '添加失败！',
            20006 => '数据不存在',
        );
        $error = array_key_exists($error_code,$error_info) ? $error_info[$error_code] : '';

        //为空获取配置参数
        if(empty($error))
        {
            $err_res = Lib_error_map::get_err_msg($error_code);
            $error = $err_res['error_msg'];
        }

    	return $error;
    }


}

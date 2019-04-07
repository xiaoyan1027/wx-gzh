<?php
/**
 * 转义数据
 * @param string|array $string
 * @param int $force
 */
function daddslashes($string, $force = 0)
{
	if (!get_magic_quotes_gpc() || $force)
	{
		if (is_array($string))
		{
			foreach ($string as $key => $val)
			{
				$string[$key] = daddslashes($val, $force);
			}
		}
		else
		{
			$string = addslashes($string);
		}
	}
	return $string;
}

/**
 * 转义字符编码
 * @param string $in_charset
 * @param string $out_charset
 * @param array $arr
 */
function iconv_array($in_charset, $out_charset, $arr)
{
	if (strtolower($in_charset) == "utf8")
	{
		$in_charset = "UTF-8";
	}
	if (strtolower($out_charset) == "utf8")
	{
		$out_charset = "UTF-8";
	}
	if (is_array($arr))
	{
		foreach ($arr as $key => $value)
		{
			$arr[$key] = iconv_array($in_charset, $out_charset, $value);
		}
	}
	else
	{
		$arr = @iconv($in_charset, $out_charset, $arr);
	}
	return $arr;
}


/**
 * 获取字符长度
 * 全角作为一个字符
 * @param string $str
 * @return number
 */
function get_length($str)
{
	$len = strlen($str);
	$strlen = $len;
	for($i = 0; $i < $len; $i++)
	{
		if(ord($str[$i])>128)
		{
			$strlen = $strlen-1;
			$i++;
		}
	}
	return $strlen;
}

/**
 * 获取客户端IP
 *
 * @return string
 */
function get_client_ip()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
	{
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
	{
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
	    $ip = $_SERVER['REMOTE_ADDR'];
	}

	$valid_res = filter_var($ip, FILTER_VALIDATE_IP);
	if ($valid_res)
	{
	    return $ip;
	}
	else
	{
	    return $_SERVER["REMOTE_ADDR"];
	}
}



/**
 * 去掉字符两端空格
 * @param unknown_type $string
 */
function trims($string)
{
    if (is_array($string))
    {
        foreach ($string as $key=>$val)
        {
            $string[$key] = trims($val);
        }
    }
    else
    {
        $string = trim($string);
    }

    return $string;
}

/**
 * 处理HTML输出字符
 * @param string $string
 * @param int $force
 * @return string
 */
function dhtmlspecialchars($string, $force = 0)
{
	!defined('MAGIC_QUOTES_GPC') && define('MAGIC_QUOTES_GPC', get_magic_quotes_gpc());
	if (!MAGIC_QUOTES_GPC || $force)
	{
		if (is_array($string))
		{
			foreach ($string as $key => $val)
			{
				$string[$key] = dhtmlspecialchars($val, $force);
			}
		}
		else
		{
			$string = htmlspecialchars($string);
		}
	}
	return $string;
}

/**
 * 循环创建目录
 *
 * @param string $dir
 * @param int $mode
 * @return boolean
 */
function mk_dir($dir, $mode = 0755)
{
    if (is_dir($dir) || @mkdir($dir,$mode))
    {
        return true;
    }
    if (!mk_dir(dirname($dir),$mode)) {
        return false;
    }
    return @mkdir($dir,$mode);
}


/**
 * 二维数组按指定字段排序
 * @param array $arr
 * @param string $shortKey
 * @param string $short
 * @param string $shortType
 * @return array
 */
function multi_array_sort($arr,$shortKey,$short=SORT_DESC,$shortType=SORT_REGULAR)
{
    foreach ($arr as $key => $data){
        $name[$key] = $data[$shortKey];
    }
    array_multisort($name,$shortType,$short,$arr);
    return $arr;
}
/**
 * 解决 unserialize出现Error at offset 错误
 * @param unknown $string
 */
function dunserialize($string)
{
    return unserialize(preg_replace('!s:(\d+):"(.*?)";!se', '"s:".strlen("$2").":\"$2\";"', $string));
}
/**
 * 获取文件后缀
 */
function get_file_suffix($file)
{
    $suffix	= strtolower(substr($file, (strrpos($file,".",1)+1)));
    return $suffix;
}

if (!function_exists('array_column')) {
    function array_column($input, $column_key, $index_key = '') {
        $result = array();
        foreach ($input as $k => $v) {
            if (empty($index_key)) {
                $result[] = $v[$column_key];
            } else {
                $result[$v[$index_key]] = $column_key ? $v[$column_key] : $v;
            }
        }
        return $result;
    }
}
function array_fields($input, $column_key, $index_key = '') {
    $result = array();
    foreach ($input as $k => $v) {
        if (empty($index_key)) {
            $result[] = $v[$column_key];
        } else {
            $result[$v[$index_key]] = $column_key ? $v[$column_key] : $v;
        }
    }
    return $result;
}
/**
 * 获取分页输出模板
 *
 * @param $count
 * @param $limit
 * @param $url
 * @param array $params
 */
function get_pager_html($count, $limit = 20, $url = '', $params = array()) {
    $CI = &get_instance();

    $query_arr = $_REQUEST;
    unset($query_arr['per_page']);
    $str = http_build_query($query_arr);
    $config['page_query_string'] = TRUE;
    $config['base_url'] = $url ?: './' . $CI->router->method . '?' . $str;
    $config['total_rows'] = $count;
    $config['per_page'] = $limit;

    $config['first_link'] = '首页';
    $config['last_link'] = '末页';
    $config['full_tag_open'] = '<ul class="pagination">';
    $config['full_tag_close'] = '<ul>';
    $config['prev_tag_open'] = '<li class="paginate_button previous"> ';
    $config['prev_tag_close'] = '</li>';
    $config['prev_link'] = '上一页';
    $config['next_tag_open'] = '<li class="paginate_button next"> ';
    $config['next_tag_close'] = '</li>';
    $config['next_link'] = '下一页';
    $config['num_tag_open'] = '<li class="paginate_button ">';
    $config['num_tag_close'] = '</li>';
    $config['cur_tag_open'] = '<li class="paginate_button active"><a>';
    $config['cur_tag_close'] = '</a></li>';

    $config['last_tag_open'] = '<li class="paginate_button"> ';
    $config['last_tag_close'] = '</li>';

    $config['first_tag_open'] = '<li class="paginate_button"> ';
    $config['first_tag_close'] = '</li>';


    $CI->pagination->initialize($config);
    $pagination = $CI->pagination->create_links();
    return $pagination;
}

/**
 * json错误输出
 *
 * @param string $message
 * @param string $code
 * @param array $data
 * @param string $url
 */
function output_error($code = '0', $message = '', $data = array(), $url = '') {
    $return = array(
        'status' => 'false',
        'code' => $code,
        'info' => $message,
        'data' => $data,
        'url' => $url,
    );

    header('Content-Type:application/json; charset=utf-8');
    echo json_encode($return);
    exit;
}


/**
 * json输出
 *
 * @param string $message
 * @param string $code
 * @param array $data
 * @param string $url
 */
function output_ok($message = '', $data = array(), $code = '200', $url = '') {
    $return = array(
        'status' => 'true',
        'code' => $code,
        'info' => $message,
        'data' => $data,
        'url' => $url,
    );

    header('Content-Type:application/json; charset=utf-8');
    echo json_encode($return);
    exit;
}


/**
 * 根据指定字段获取列表数据
 * @param array $source
 * @param array $keys
 * @param string $action intersect|diff
 * @return multitype:multitype:
 */
function get_list_by_keys($source, $keys, $action='intersect')
{
    if(!is_array($source) || !is_array($keys) || count($source) == 0) return false;
    $keys = array_fill_keys($keys, '');
    $result = array();
    foreach($source as $key => $value)
    {
        if($action == 'intersect')
        {
            $result[$key] = array_intersect_key($value, $keys);
        }
        else
        {
            $result[$key] = array_diff_key($value, $keys);
        }
    }
    return $result;
}


/**
* 缓存键名池
* @author  mingxing
* @param   string  $name   键名
* @param   array   $data   替换数据
* @return  string
*/
function get_cache_key($name,$data=array(),$type='redis')
{
    $cache_prefix = 'xcx_v1_';
    $CI =& get_instance();
    $cache_key = '';
    if($type == 'redis')
    {
        if ($CI->config->load('redis', TRUE, TRUE))
    	{
    		$config = $CI->config->item($type);
    	}
    }
    $key_set = $config['key_set'];

    if(isset($key_set[$name]))
    {
        $cache_key = $cache_prefix.$key_set[$name];
        if(empty($data)) return $cache_key;
        array_unshift($data,$cache_key);
        $cache_key = call_user_func_array("sprintf",$data);
    }
    return $cache_key;
}



/**
 * 手机号验证
 */
function is_mobile($mobile)
{
    if(preg_match("/^1[3456789]{1}\d{9}$/",$mobile))
    {
        return TRUE;
    }
    return FALSE;
}

function md5_16($str = '')
{
    return substr(md5($str ? $str : uniqid(mt_rand())),8,16);
}
/**
 * 构造url
 * @param   string  $url    url地址
 * @param   array   $params url参数
 * @return  string
 */
function build_url($url,$params='')
{
    if(stripos($url,"?")===false)
    {
        $url .= $params ? ("?".http_build_query($params)) : '';
    }
    else
    {
        $url .= $params ? "&".(http_build_query($params)) : '';
    }
    return $url;
}
/**
 * 打印调试
 * @param mixed
 */
function p($data)
{
    echo '<pre>';
    var_dump($data);
}

/**
 * 获取掩码电话号
 */
function mask_mobile($mobile,$num)
{
    if(empty($num)) return $mobile;
    return substr_replace($mobile,str_repeat("*",$num),floor((strlen($mobile) - $num)/2),$num);
}
/**
 * 判断是否是微信
 */
function is_weixin()
{
    if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false)
    {
        return TRUE;
    }
    return FALSE;
}
/**
 * 获取邮件配置
 * @param string $type
 * @return mixed
 */
function get_email_config($key = 'text')
{
    $CI =& get_instance();
    //加载redis的配置文件
    $CI->config->load('email', TRUE, TRUE);
    $config = $CI->config->item('email');
    if(!empty($key))
    {
        return $config[$key];
    }
    return $config;
}

/**
 * 删除html标签
 *
 * @param $str
 * @return mixed
 */
function remove_html($str) {
    $okstr = str_replace("\r\n", "", strip_tags($str));
    return $okstr;
}


function number_uppercase($number){
    $number=substr($number,0,2);
    $arr=array("零","一","二","三","四","五","六","七","八","九");
    if(strlen($number)==1){
        $result=$arr[$number];
    }else{
        if($number==10){
            $result="十";
        }else{
            if($number<20){
                $result="十";
            }else{
                $result=$arr[substr($number,0,1)]."十";
            }
            if(substr($number,1,1)!="0"){
                $result.=$arr[substr($number,1,1)];
            }
        }
    }
    return $result;
}


function get_fitment_name($fitment) {
    $fitment_list = array(
        '2' => '毛坯',
        '3' => '简装',
        '4' => '装修',
        '5' => '公共部分装修',
        '99' => '待定',
    );

    $type_arr = explode(',', $fitment);
    $new_arr = array();
    foreach ($type_arr as $item) {
        $new_arr[] = $fitment_list[$item];
    }
    return implode('/', $new_arr);
}


function get_archtype_name($archtype) {
    $archtype_list = array(
        "1" => "板楼",
        "2" => "塔楼",
        "3" => "板塔结合",
        "4" => "独栋别墅",
        "5" => "双拼",
        "6" => "联排",
        "7" => "叠拼",
        "8" => "低层",
        "9" => "多层",
        "10" => "小高层",
        "11" => "高层",
        "12" => "超高层",
    );

    $type_arr = explode(',', $archtype);
    $new_arr = array();
    foreach ($type_arr as $item) {
        $new_arr[] = $archtype_list[$item];
    }
    return implode('/', $new_arr);
}

function get_around_list() {
    $around_list = array(
        'traffic' => '交通',
        'education' => '教育',
        'med_care' => '医疗',
        'business' => '商业',
    );
    return $around_list;
}


function get_around_key_list() {
    $around_list = get_around_list();
    return array_keys($around_list);
}

function get_around_name($type) {
    $around_list = get_around_list();
    return $around_list[$type];
}


/**
 * 加密规则
 *
 * @param $str
 * @return string
 */
function encrypt($str) {
    return base64_encode(substr(md5($str),0,8).base64_encode($str).substr(md5($str),10,4));
}

/**
 * 生成sign
 * @param unknown $token
 * @param unknown $data
 * @return boolean|string
 */
function _create_sign($token, $data)
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
 * array_column函数 5.4
 * @author  zhangxin
 * @param string $columnKey
 * @param array $input
 * @param string $indexKey
 * @return mixed
 */
function i_array_column($input, $columnKey, $indexKey=null)
{

    if(!function_exists('array_column')){
        $columnKeyIsNumber  = (is_numeric($columnKey))?true:false;
        $indexKeyIsNull     = (is_null($indexKey))?true :false;
        $indexKeyIsNumber   = (is_numeric($indexKey))?true:false;
        $result                         = array();
        foreach((array)$input as $key=>$row){
            if($columnKeyIsNumber){
                $tmp = array_slice($row, $columnKey, 1);
                $tmp = (is_array($tmp) && !empty($tmp))?current($tmp):null;
            }else{
                $tmp = isset($row[$columnKey])?$row[$columnKey]:null;
            }
            if(!$indexKeyIsNull){
                if($indexKeyIsNumber){
                    $key = array_slice($row, $indexKey, 1);
                    $key = (is_array($key) && !empty($key))?current($key):null;
                    $key = is_null($key)?0:$key;
                }else{
                    $key = isset($row[$indexKey])?$row[$indexKey]:0;
                }
            }
            $result[$key] = $tmp;
        }
        return $result;
    }else{
        return array_column($input, $columnKey, $indexKey);
    }
}

/**
 * 获取小程序跳转外部链接
 * @param string $city
 * @param string $hid
 * @param array  $others
 * Author: zhangxin
 */
function get_jump_path($data = array(),$type = '',$data_type = 'url') {

    $CI =& get_instance();
    $CI->config->load('jump_path', TRUE, TRUE);

    $config = $CI->config->item("jump_path");

    if (ENVIRONMENT == 'development') {
        if($data_type == "url") {
            $url = $config['voucher_url']['test'];
        }else {
            $jump_app = $config['jump_app']['test'];
        }

    } else {
        if($data_type == "url") {
            $url = $config['voucher_url']['product'];
        }else {
            $jump_app = $config['jump_app']['product'];
        }
    }

    //拼接跳转H5路径
    if($data_type == "url") {
        if($type == "huodong") {
            $city_hid = $data['city']."-".$data['activity_id'];
            $url = $url['huodong'].$city_hid.".html?hdsource=33";
        }
    } else {
        //返回小程序appid
        $url = $jump_app[$type];
    }


    return $url;

}

/**
 * 限定字符串
 * @param mixed $str 需要限定的字符串
 * @param string $match 正则表达式
 * @author zhangxin
 * @return bool
 */
function php_safe_str($str, $match = '/^[0-9a-zA-Z_]+$/')
{

    $res = true;
    if (!preg_match($match, $str))
        $res = false;

    return $res;
}


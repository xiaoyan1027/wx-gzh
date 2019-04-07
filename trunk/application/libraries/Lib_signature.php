<?php
/**
 * 签名算法
 */
class Lib_signature 
{
	public function __construct()
    {
        
    }
    /**
     * 签名算法
     */
    function create_sign($token, $data)
	{
	    if(empty($data) || empty($token))
	    {
			return false;
		}
        unset($data['sign']);
		ksort($data);
		$tmpstr = http_build_query($data);
		$sign = md5($tmpstr.$token);
		return $sign;		
	}
    
    /**
     * 签名校验token方法
     * @param   array   $search_cond    查询条件
     * @param   string  $key    业务Key
     * @return  string  数据签名token
     */
    function get_check($search_cond, $key){
        $string = '';
        if(is_array($search_cond)){
            foreach ($search_cond as $v){
                $string .= $v;
            }
        }else{
            $string = $search_cond;
        }
        $token = md5($string.$key);
        return $token;
    }

    /*
     * 订阅生成签名
     * */
    function get_sign($params = array())
    {
        if (empty($params)) {
            return '';
        }
        $sKey = "+#~Cn8KN"; // 加密因子
        ksort($params); // 升序排序
        $str = '';
        foreach ($params as $val) {
            $str .= $val; // 参数拼接
        }
        return md5($str.$sKey);// 再拼接加密因子 MD5加密
    }

    /**
     * 获取签名
     * @author  zhangxin 2018-07-30
     * @param   string  $token    token值
     * @param   array   $params   加密数据
     * @return  string            返回结果
     */
    function get_sign1($token,$params)
    {

        unset($params['sign']);
        ksort($params);
        $sign = md5(http_build_query($params).$token);
        return $sign;
    }

    /**
     * 签名
     * @author zhangxin 2018-09-06
     *
     */
    protected function getPostString(&$post)
    {
        $string = '';
        if(is_array($post))
        {
            foreach($post as $item)
            {
                if(is_array($item))
                    $string .= $this->getPostString($item);
                else
                    $string .= $item;
            }
        }
        else
        {
            $string = $post;
        }
        return $string;
    }

    /**
     * 加密算法  zhangxin  2018-10-15
     * @param $data
     * @param $key
     * @return string
     */
    public function encrypt($data, $key) {
        $prep_code = serialize($data);
        $block = mcrypt_get_block_size('des', 'ecb');
        if (($pad = $block - (strlen($prep_code) % $block)) < $block) {
            $prep_code .= str_repeat(chr($pad), $pad);
        }
        $encrypt = @mcrypt_encrypt(MCRYPT_DES, $key, $prep_code, MCRYPT_MODE_ECB);
        return base64_encode($encrypt);
    }

}

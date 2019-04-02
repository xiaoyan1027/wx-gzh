<?php
/**
 * 登录管理
 * @author zhangxin
 */

require_once(APPPATH.'controllers/'.$RTR->directory.'Base.php');

class Login extends Base {

    public function __construct() {
        parent::__construct();
        $this->load->library('weixin/auth');
        $this->_open_log = TRUE;
    }
    
    /**
     * 小程序登录
     */ 
    public function jscode2session()
    {

        $wa_code = $this->input->get('wa_code');
        $js_code = $this->input->get('js_code');
        if(empty($wa_code))
        {
            $this->_api_fail(30001,'wa_code不可以为空！');
        }
        elseif(empty($js_code))
        {
            $this->_api_fail(30002,'code码不可以空！');
        }
        $account_info = $this->account_model->get_account_info(array('unique_code' => $wa_code));
        if(empty($account_info))
        {
            $this->_api_fail(30003,'账号不存在！');
        }
        $appid = $account_info['authorizer_appid'];
        $data = array(
            'authorizer_appid' => $appid,
            'js_code' => $js_code
        );
        $res = $this->auth->jscode2session($data);

        if (isset($_GET['powerby'])) {
            dump($res);
            exit;
        }

        if(!empty($res))
        {
            $res['wa_code'] = $wa_code;
            $login_res = $this->login_model->miniprogram_login($res);

            $this->_api_succ(array('access_token' => $login_res['access_token'],'open_id'=>$login_res['open_id'],'msg'=>'登录成功！'));
        }
        $this->_api_fail(30004,'登录失败！');
    }


    /**
     * 验证码
     */
    public function verifycode() {
        $weixin_token  = $this->input->get('weixin_token');
        if(empty($weixin_token))
        {
            $this->_api_fail(30001,'weixin_token不可以为空！');
        }
        $login_res = $this->login_model->get_miniprogram_login_info($weixin_token);
        if(empty($login_res))
        {
            $this->_api_fail(30002,'未授权登录！');
        }
        $this->load->library('lib_verifycode',array('code_key' => $weixin_token));
        $this->lib_verifycode->get_code();
    }


    /**
     * 校验登陆是否过期
     * @author zhangxin
     * @return string
     */
    public function check_login()
    {
    
        $weixin_token  = $this->input->get('weixin_token');
        $login_res = $this->login_model->get_miniprogram_login_info($weixin_token);
        if(empty($login_res))
        {
            $this->_api_fail(30002,'未授权登录！');
        }                                                                              

        $this->_api_succ('success');
    }


    /**
     * 微信授权手机号登录
     * Author: zhangxin
     */
    public function wx_authorized_login() {

        /*
         *     [openid] => oCBkJ4zsMWI12uHOc2HPbQKJ4ORk
               [session_key] => RPS0GH/vbE+ucrXT0tVHfA==
        */

        $this->load->library('lib_api_ucenter');
        $wa_code = $this->input->get_post('wa_code');//这个参数不需要
        $weixin_token = $this->input->get_post('weixin_token');
        $encrypted_data = $this->input->get_post('encrypted_data');
        $iv = $this->input->get_post('iv');
        $btype = $this->input->get_post('btype');
        $bcode = $this->input->get_post('bcode');

        if(empty($encrypted_data))
        {
            $this->_api_fail(30001,'授权数据为空！');
        }
        
        $wx_login_info = $this->login_model->get_miniprogram_login_info($weixin_token);
        if(empty($wx_login_info))
        {
            $this->_api_fail(30003,"微信登陆信息获取失败");
        }
        $session_key = $wx_login_info['session_key'];
        $wa_code = $wx_login_info['wa_code'];
        $openid = $wx_login_info['openid'];
        if(empty($session_key) || empty($wa_code) || empty($openid))
        {
            $this->_api_fail(30003,"微信登陆信息获取失败");
        }
        
        $account_info = $this->account_model->get_account_info(array('unique_code' => $wa_code));
        if(empty($account_info))
        {
            $this->_api_fail(30002,'账号不存在！');
        }
        $appid = $account_info['authorizer_appid'];
        
        include_once(APPPATH.'libraries/weixin/miniprogram/wxBizDataCrypt.php');
        
        $pc = new WXBizDataCrypt($appid, $session_key);
        $err_code = $pc->decryptData($encrypted_data, $iv, $data);
        if ($err_code == 0) {
            $u_data = json_decode($data,TRUE);
            //小程序用户登录
            if(!$u_data) {
                $this->_api_fail(30005,"微信授权信息解析失败",'',$wx_login_info);
            }
            $u_data['openid'] = $openid;
            $log_result = $this->lib_api_ucenter->wx_authorized_login($u_data);

            if(isset($log_result['code']) && $log_result['code'] == 0)
            {
                $data_where['token'] = $log_result['data']['token'];
                $data_where['mobile'] = $u_data['phoneNumber'];
                //待优化，TODO
                $res = $this->lib_api_ucenter->get_login_message($data_where);

                $res['data']['mobile'] = $u_data['phoneNumber'];
                $res['data']['token'] = $log_result['data']['token'];

                $login_res = $this->login_model->ucenter_login($res['data']);

                $data = array(
                    'uid' => $res['data']['uid'],
                    'nickname' => $res['data']['username'],
                    'mobile' => $res['data']['mobile'],
                    'avatar_url' => $res['data']['headurl'],
                    'gender' => $res['data']['gender'],
                );
                
                $ucenter_user = $this->user_model->add_ucenter_user($data);    
                
                //根据不同业务 做不同处理
                $login_info = $this->login_model->get_miniprogram_login_info($weixin_token);
                
                if(!empty($login_info) && !empty($account_info) && !empty($bcode) && !empty($btype))
                {
                    $oauthuser = array('open_id' => $login_info['openid'],'authorizer_appid' => $account_info['authorizer_appid']);
                    $oauthusers_info = $this->user_model->get_oauthusers_info($oauthuser);
                    
                    if(!empty($oauthusers_info))
                    {
                        //更新小程序账号授权信息
                        $oauthuser['mobile'] = $u_data['phoneNumber'];
                        $this->user_model->update_user_mobile($oauthuser);
                        
                        $b_data=array(
                            'uid' => $oauthusers_info['id'],
                            'mobile'=>$u_data['phoneNumber'],
                            'btype' => $btype,
                            'bcode' => $bcode,
                            'imid' => isset($ucenter_user['imid']) ? $ucenter_user['imid'] : 0,
                        );
                        $this->user_model->add_business_user($b_data);
                    }
                }

                $return = array(
                    'access_token' => $login_res['access_token'],
                    'username' => $res['data']['username'],
                    'avatar_url' => $res['data']['headurl'],
                    'phone' => $u_data['phoneNumber'],
                    'imid' => isset($ucenter_user['imid']) ? $ucenter_user['imid'] : 0,
                    'leju_uid' => $res['data']['uid'],
                    'msg' => '登录成功！',
                );
                
                $this->_api_succ($return);
            }

        } else {
            $this->_api_fail(30004,'登录失败('.$err_code.')！', '', $wx_login_info);
        }
    }


}

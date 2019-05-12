<?php

/***************************************************
 *   Filename: Index.php
 *   Author: zhangxin11@leju.com
 *   Description: php项目文件描述
 *   Create: 2019-04-03 18:32
 ****************************************************/

require_once(APPPATH.'controllers/'.$RTR->directory.'Base.php');
class Index extends Base
{

    public function __construct() {
        parent::__construct();
        $this->load->model('test_model');
        $this->load->model('api/Adoptmessage_model');//发布信息
        $this->load->model('api/user_model');
        $this->load->model('api/Relationadopt_model');//申请关联表
        $this->load->model('api/Applyadoption_model');//申请领养数据表
        //$this->load->library("lib_signature");
    }

    /**
     * 获取首页接口获取列表数据
     * Author: zhangxin11@leju.com
     */
    public function index() {
        //接受参数
        $type      = $this->input->post('type');
        $city_en      = $this->input->post('city_en');

        if(empty($type)) {
            $this->_api_fail(1001,'type不能为空');
        }
        // 发起领养审核通过且未被领养的宠物列表
        $params['verify_status'] = 2;
        $params['adopt_status']  = 1;
        // 获取首页数据
        if($type == 1) {
            //猫
           $params['type'] = 1;

        }elseif ($type == 2 ) {
            //狗
            $params['type'] = 2;
        }elseif($type == 3) {
            //其他
            $params['type'] = 3;

        }
        if(!empty($city__en)) {
            $params['city_en'] = $city_en;
         }

        // 获取宠物数据
        $res = $this->Adoptmessage_model->get_data($params);

        $this->_api_succ($res);

    }

    /**
     * 获取列表数据详情
     */
    public function get_pet_info()
    {

        $id      = $this->input->post('id');

        if(empty($id)) {
            $this->_api_fail('1001','id不能为空');
        }

        // 获取单条数据
        $info = $this->Adoptmessage_model->fetch_by_id($id);
        if(empty($info)) {
            $this->_api_fail(1001,'数据不存在');
        }
        $this->_api_succ($info);
    }


    /**
     * 发布领养信息
     */
    public function create_adopt(){
        $open_id = $this->input->post('open_id');
        $user_name = $this->input->post('user_name');
        $wx_number = $this->input->post('wx_number');
        $mobile = $this->input->post('mobile');
        $city_en = $this->input->post('city_en');
        $city_cn = $this->input->post('city_cn');
        $nick_name = $this->input->post('nick_name');
        $age = $this->input->post('age');
        $sex = $this->input->post('sex');
        $pet_type = $this->input->post('pet_type');
        $is_ster = $this->input->post('is_ster');
        $is_immune = $this->input->post('is_immune');
        $body_status_desc = $this->input->post('body_status_desc');

        //  传图片、视频的没整

        //数据验证
        if(empty($user_name)) {
            $this->_api_fail(1001,'申请人不能为空');
        }
        if(empty($wx_number)) {
            $this->_api_fail(1001,'微信号不能为空');
        }
        if(empty($mobile)) {
            $this->_api_fail(1001,'手机号不能为空');
        }
        if(!is_mobile($mobile)) {
            $this->_api_fail(1001,'手机号格式不正确');
        }
        if(empty($city_en)) {
            $this->_api_fail(1001,'城市不能为空');
        }
        if(empty($city_cn)) {
            $this->_api_fail(1001,'城市英文不能为空');
        }
        if(empty($nick_name)) {
            $this->_api_fail(1001,'宠物昵称不能为空');
        }
        if(empty($age)) {
            $this->_api_fail(1001,'年龄不能为空');
        }
        if(empty($sex)) {
            $this->_api_fail(1001,'性别不能为空');
        }
        if(empty($pet_type)) {
            $this->_api_fail(1001,'宠物类型不能为空');
        }
        if(empty($is_ster)) {
            $this->_api_fail(1001,'是否绝育未选择不能提交');
        }
        if(empty($is_immune)) {
            $this->_api_fail(1001,'是否免疫未选择不能提交');
        }
        if(empty($body_status_desc)) {
            $this->_api_fail(1001,'简介身体状况为空，不能提交');
        }

        // 拼接入库数据
        $data = array(
            'user_name' => $user_name,
            'wx_number' => $wx_number,
            'mobile' => $mobile,
            'city_en' => $city_en,
            'city_cn' => $city_cn,
            'nick_name' => $nick_name,
            'age' => $age,
            'sex' => $sex,
            'pet_type' => $pet_type,
            'is_ster' => $is_ster,
            'is_immnue' => $is_immune,
            'body_start_desc' => $body_status_desc,
            'verify_status' => 1,
            'adopt_status' => 1,
            'verify_desc' => '',
            'add_time' => time(),
            'update_time' => time(),

        );

        $up_data['mobile'] = $mobile;
        $up_where['open_id'] = $open_id;

        // 更新用户手机号
        $this->user_model->update_data($up_data,$up_where);


        $res = $this->Adoptmessage_model->insert($data);
        if(!$res) {
            $this->_api_fail(1001,'发布领养信息失败');
        }
        $this->_api_succ('发布成功');

    }

    /**
     * 提交领养信息
     */
    public function submit_apply()
    {
        $open_id = $this->input->post('open_id');
        $adopt_id = $this->input->post('adopt_id');
        $apply_name = $this->input->post('apply_name');
        $sex = $this->input->post('sex');
        $wx_size = $this->input->post('wx_size');
        $mobile = $this->input->post('mobile');
        $age = $this->input->post('age');
        $registered = $this->input->post('registered ');//户口
        $address = $this->input->post('address');//现住址
        $company = $this->input->post('company');//单位
        $duties = $this->input->post('duties');//职务
        $month_money = $this->input->post('month_money');//月薪
        $reason = $this->input->post('reason');//领养原因
        $is_agreement = $this->input->post('is_agreement');//是否愿意签订协议
        $is_idcard = $this->input->post('is_idcard');//是否愿意留身份证
        $houseing_type = $this->input->post('houseing_type');//住房情况
        $home_type = $this->input->post('home_type');//房屋情况
        $home_num = $this->input->post('home_num');//常住家庭人口
        $is_small_child = $this->input->post('is_small_child');//是否有小孩
        $is_raise_pet = $this->input->post('is_raise_pet');//是否养过宠物
        $is_have_pet = $this->input->post('is_have_pet');//是否有宠物
        $active_rang = $this->input->post('active_rang');//活动范围
        $is_ster = $this->input->post('is_ster');//考虑绝育
        $log_immune = $this->input->post('log_immune');//多久免疫一次
        $pet_money = $this->input->post('pet_money');//每月指出预算
        $illnes_money = $this->input->post('illnes_money');//看病预算
        $pet_food = $this->input->post('pet_food');//宠物食物
        $leav_pet = $this->input->post('leav_pet');//离开宠物怎么处理
        $pregnant_pet = $this->input->post('pregnant_pet');//媳妇怀孕怎么处理
        $moveing_house = $this->input->post('moveing_house');//搬家如何处理
        $is_visit = $this->input->post('is_visit');//是否允许回访
        $opinion = $this->input->post('opinion');//对北京领养日的建议//选填


        // 数据验证

        if(empty($adopt_id))
        {
            $this->_api_fail(1001,'领养信息不能为空，不能提交');
        }if(empty($apply_name))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($open_id))
        {
            $this->_api_fail(1001,'open_id为空，不能提交');
        }
        if(empty($sex))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($wx_size))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($mobile))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(!is_mobile($mobile))
        {
            $this->_api_fail(1001,'手机号格式不正确，不能提交');
        }
        if(empty($age))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($registered))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($address))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($company))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($duties))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($month_money))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($reason))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($is_agreement))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($is_idcard))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($houseing_type))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($home_type))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }

        if(empty($home_num))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($is_small_child))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($is_raise_pet))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }

        if(empty($is_have_pet))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($active_rang))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($is_ster))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($log_immune))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($pet_money))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($illnes_money))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($pet_food))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($leav_pet))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($pregnant_pet))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($moveing_house))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($is_visit))
        {
            $this->_api_fail(1001,'存在为空数据，不能提交');
        }
        if(empty($opinion))
        {
            $this->_api_fail(1001,' 存在为空数据，不能提交');
        }

        $where['open_id'] = $open_id;
        $user_info = $this->user_model->get_info->get_info($where);
        if(empty($user_info)){
            $this->_api_fail(1001,'用户数据拉取失败，不能添加');
        }

        //拼接入库数据
        $data = array(
            'user_id' => $user_info['id'],
            'adopt_id' => $adopt_id,
            'sex' => $sex,
            'wx_size' => $wx_size,
            'mobile' => $mobile,
            'age' => $age,
            'registered' => $registered,
            'address' => $address,
            'company' => $company,
            'duties' => $duties,
            'month_money' => $month_money,
            'reason' => $reason,
            'is_agreement' => $is_agreement,
            'is_idcard' => $is_idcard,
            'houseing_type' => $houseing_type,
            'home_type' => $home_type,
            'home_num' => $home_num,
            'is_small_child' => $is_small_child,
            'is_raise_pet' => $is_raise_pet,
            'is_have_pet' => $is_have_pet,
            'active_rang' => $active_rang,
            'is_ster' => $is_ster,
            'log_immune' => $log_immune,
            'pet_money' => $pet_money,
            'illnes_money' => $illnes_money,
            'pet_food' => $pet_food,
            'leav_pet' => $leav_pet,
            'pregnant_pet' => $pregnant_pet,
            'moveing_house' => $moveing_house,
            'is_visit' => $is_visit,
            'is_status' => 1,//审核状态 1 审核中  2 已通过 3 已驳回
            'reject_desc' => 1,//审核驳回原因
            'add_time' => time(),//添加时间
            'update_time' => time(),//修改时间

        );

        $res = $this->Applyadoption_model->insert($data);

        $up_data['mobile'] = $mobile;
        $up_where['open_id'] = $open_id;

        // 更新用户手机号
        $this->user_model->update_data($up_data,$up_where);


        // 添加申请记录
        $relation_data = array(
            "apply_id" => $res,
            "adopt_id" => $adopt_id,
            "add_time" => time(),
            "update_time" => time(),

        );
        $this->Relationadopt_model->insert($relation_data);


        if(!$res) {
            $this->_api_fail(1001,'提交领养信息失败');
        }
        $this->_api_succ('提交成功');

    }

    /**
     * 获取我的申请
     */
    public function get_apply(){
        $this->input->post('open_id');
        if(empty($open_id)) {
            $this->_api_fail(1001,'open_id不能为空');
        }

        $params['open_id'] = $open_id;
        $info = $this->Applyadoption_model->get_data($params);


        if(empty($info)) {
            $this->_api_fail(1001,'没有数据');
        }

        //查询申请的宠物数据
        foreach($info as $k=>&$v){

            $adopt_info = $this->Adoptmessage_model->fetch_by_id($v['adopt_id']);
            $v['adopt_info'] = $adopt_info;

        }

        $this->_api_succ($info);

    }


    // 获取我的发布
    public function get_release() {
        $open_id = $this->input->post('open_id');
        if(empty($open_id)) {
            $this->_api_fail(1001,'open_id不能为空');

        }

        $params['open_id'] = $open_id;
        $data = $this->Adoptmessage_model->get_data($params);
        // 查询每个发布信息中的申请人数
        foreach($data as $k=>&$v) {
            $where['adopt_id'] = $v['id'];
            $count = $this->Relationadopt_model->get_count($where);
            if(!$count) {
                $v['adopt_count'] = 0;
            }
            $v['adopt_count'] = $count;
        }

        $this->_api_succ($data);

    }



}
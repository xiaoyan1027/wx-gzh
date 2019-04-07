<?php

require_once(APPPATH.'controllers/'.$RTR->directory.'Base.php');

class Map extends Base {

    public function __construct() {
        parent::__construct();
        $this->load->model('service/common_model');
        $this->load->model('loupan_browse_model');
        $this->load->model('api/logger_model');
        $this->load->model('city_model');
        $this->load->service('house_service');
        $this->load->library('lib_api_house');
        $this->load->library('lib_api_qqlbs');
        $this->load->model('template_model');
        $this->_open_log = TRUE;
    }

    /**
     * 根据坐标获取城市信息
     * @author:zhangxin
     */
    public function get_city_info() {
        $location_x = $this->input->get('location_x');
        $location_y = $this->input->get('location_y');
        $wa_code = $this->input->get('wa_code');
        if (empty($wa_code)) {
            $this->_api_fail(10001, 'wa_code不能为空！');
        }

        $project_info = $this->common_model->get_project_info_by_wacode($wa_code);

        if (empty($project_info)) {
            $this->_api_fail(10002, '项目不存在');
        }

        $template_info = $this->template_model->fetch_row(array('id'=>$project_info['t_id']));
        $tem_type = $template_info['type'];

        if ($tem_type == 3 && !$location_x && !$location_y)  {
            $location_x = '39.92';
            $location_y = '116.46';
        }

        //如果用户拒绝授权则没有坐标
        $city_en = "";
        $city_cn = "";
        $location_city = "";

        if (!empty($location_x) && !empty($location_y))
        {

            $data['location'] = $location_x . ',' . $location_y;
            $res = $this->lib_api_qqlbs->geocoder($data);

            $ad_info = $res['result']['ad_info'];
            if(isset($ad_info['city']))
            {
                $city_cn = str_replace("市", "", $ad_info['city']);
                //获取城市的city_en
                $city_en = $this->city_model->get_city_en_by_city_cn($city_cn);
                $location_city = $city_en;
            }
        }

        //判断用户当前城市在项目配置里面是否存在,不存在的话则获取默认城市
        if (!empty($project_info)) {

            //拼购
            if($tem_type == 3) {
                //没有默认城市给个默认城市
                if (!isset($project_info['setting']['basic_setting']['city_en'])) {
                    $project_info['setting']['basic_setting']['city_en'] = "bj";
                    $city_info = $this->city_model->get_city_info_by_en($project_info['setting']['basic_setting']['city_en']);
                }
            }

            //品牌馆（常规版）
            if($tem_type == 1) {
                if (!isset($project_info['setting']['basic_setting']['city_hid'][$city_en]) || empty($location_x) || empty($location_y)) {

                    $city_info = $this->city_model->get_city_info_by_en($project_info['setting']['basic_setting']['city_en']);
                }

            }
            //优惠版
            if($tem_type == 4) {

                if (!isset($project_info['setting']['basic_setting']['city_hids'][$city_en]) || empty($location_x) || empty($location_y)) {
                    $default_city = $project_info['setting']['basic_setting']['city_en'];
                    //如果默认城市为空
                    if(empty($project_info['setting']['basic_setting']['city_en'])) {
                    $default_city = $project_info['setting']['basic_setting']['city_hid'][0]['site'];
                    }
                    $city_info = $this->city_model->get_city_info_by_en($default_city);
                }
            }

            // 来客名片
            if($tem_type == 6 || $tem_type == 8)
            {
                // 没有坐标或者坐标定位不到
                if(empty($location_x) || empty($location_y) || empty($city_en) || empty($city_cn))
                {
                    // 指定默认坐标为北京
                    $default_city = 'bj';
                    $city_info = $this->city_model->get_city_info_by_en($default_city);
                }
            }

            if(isset($city_info)) {
                $city_cn = $city_info['city_cn'];
                $city_en = $city_info['city_en'];
                $ad_info['location']['lat'] = $city_info['coord_center_y'];
                $ad_info['location']['lng'] = $city_info['coord_center_x'];
            }

        }

        $data = array(
            'lat' => $ad_info['location']['lat'] ? $ad_info['location']['lat'] : $location_x,
            'lng' => $ad_info['location']['lng']? $ad_info['location']['lng'] : $location_y,
            'city_en' => $city_en,
            'city_cn' => $city_cn,
            'in_city' => "on",
            'tmp_types' => $tem_type
        );

        //判断定位城市是否在项目绑定的城市中
        $in_city = explode(",",$project_info['city']);
        if(!in_array($location_city,$in_city)){
            $data['in_city'] = "off";
        }


        // 添加接口日志
        $this->logger_model->success($data, $_GET, 'get');
        $this->_api_succ($data); 
    }

    /**
     * 地图找房
     * @author:zhangxin
     */
    public function search() {
        $wa_code = $this->input->get('wa_code');
        $city_en = $this->input->get('city_en');
        if (empty($wa_code)) {
            $this->_api_fail(10001, 'wa_code不能为空！');
        }

        $project_info = $this->common_model->get_project_info_by_wacode($wa_code);
        if (empty($project_info)) {
            $this->_api_fail(10002, '项目不存在');
        }

        if(isset($project_info['setting']['basic_setting']['city_hid'])) {
            $project_info['setting']['basic_setting']['city_hid'] = $project_info['setting']['basic_setting']['city_hids'];
        }
        
        if (empty($project_info['setting']['basic_setting']['city_hid'][$city_en])) {
            $this->_api_fail(10003, '相关城市信息不存在！');
        }

        $count = count($project_info['setting']['basic_setting']['city_hid'][$city_en]);
        $hid = implode('|', $project_info['setting']['basic_setting']['city_hid'][$city_en]);

        $res = $this->lib_api_house->get_house_list($city_en, $hid, '', array('pcount' => $count));
        if (!isset($res['data'])) {
            $this->_api_fail(30003, $res['message']);
        }

        //转化成腾讯坐标
        $location = array();
        foreach ($res['data'] as $row) {
            $location[] = $row['coordy2'] . ',' . $row['coordx2'];
        }

        $data['locations'] = implode(';', $location);
        $data['type'] = 3;

        $translate_res = $this->lib_api_qqlbs->translate($data);
        $qq_location = $translate_res['locations'];


        $list = array();
        foreach ($res['data'] as $key => $row) {
            $list[] = array(
                'lat' => $qq_location[$key]['lat'],  //纬度
                'lng' => $qq_location[$key]['lng'],  //经度
                'name' => $row['name'],
                'hid' => $row['hid'],
                'city_en' => $row['site'],
                'price_display' => $row['price_display'],
                'tags_list' => $this->house_service->get_house_tag($row['tags_id']),
                'look_user_num' => $this->loupan_browse_model->get_browse_num_by_hid($project_info['id'], $row['hid']),
                'pic_thumb' => $row['pic_thumb'],
            );
        }

        $data = array(
            'list' => $list,
        );
        $this->_api_succ($data);
    }
}

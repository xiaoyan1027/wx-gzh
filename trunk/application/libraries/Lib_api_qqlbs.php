<?php

/**
 * 腾讯地图
 * @author zhangxin
 *
 */
class Lib_api_qqlbs {
    private $_config = array();
    private $_ci;
    private $_lib_http;
    private $_redis;
    private $_headers = array();
    private $_key = 'STGBZ-LAL63-TGN36-YHNTN-KCTOT-RFFSS';

    public function __construct() {
        $this->_ci = &get_instance();
        $this->_ci->load->library('lib_http', array('host' => 'https://apis.map.qq.com/'), 'lib_http_qqlbs');
        $this->_lib_http = $this->_ci->lib_http_qqlbs;
        $this->_redis = $this->_ci->lib_redis;
    }

    /**
     * 地点搜索（search接口），提供三类范围条件的搜索功能：
     * 指定城市的地点搜索：如在北京搜索餐馆；
     * 圆形区域的地点搜索：一般用于指定位置的周边（附近）地点搜索，如，搜索颐和园附近的酒店；
     * 矩形区域的地点搜索：在地图应用中，往往用于视野内搜索，因为显示地图的区域是个矩形。
     */
    public function search($data) {
        $data['key'] = $this->_key;
        $res = $this->_lib_http->get('ws/place/v1/search', $data);
        return $res;
    }


    public function geocoder($data) {
        $data['key'] = $this->_key;
        $res = $this->_lib_http->get('ws/geocoder/v1', $data);
        return $res;
    }

    /**
     * 实现从其它地图供应商坐标系或标准GPS坐标系，批量转换到腾讯地图坐标系。
     *
     * @param $data
     * @return mixed
     */
    public function translate($data) {
        $data['key'] = $this->_key;
        $res = $this->_lib_http->get('ws/coord/v1/translate', $data);
        return $res;
    }
}
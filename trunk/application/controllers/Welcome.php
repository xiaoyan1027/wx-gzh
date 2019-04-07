<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_controller{

	public function index()
	{
        $this->load->library('lib_upload');
        $url = 'http://mmbiz.qpic.cn/mmbiz_jpg/TYgKM6HhbjIgcsJNRbd4VKduia8j3rpVQcrJA2BtKTTRzEaI3y5icPk9GCAJ2E8OkIN3dmPDmQlNXeXjrdoDnouA/0';
        $res = $this->lib_upload->upload_url_pic($url);
        print_r($res);
        //echo "<img src='http://mmbiz.qpic.cn/mmbiz_jpg/TYgKM6HhbjIgcsJNRbd4VKduia8j3rpVQcrJA2BtKTTRzEaI3y5icPk9GCAJ2E8OkIN3dmPDmQlNXeXjrdoDnouA/0'/>";
	}
}

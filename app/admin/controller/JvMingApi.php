<?php


namespace app\admin\controller;
use app\admin\model\NodWarehouse;
use app\common\controller\AdminController;
use GuzzleHttp\Client;
use think\App;
use think\facade\Db;


class JvMingApi
{


    public function __construct()
    {
        $this->client = new Client(['timeout'=>10]);
        $this->domain = 'http://newp.juming.com:9696';
    }

    public function bulid_data($data): array
    {
        $time_str = time();
        $common_data =[
            'appid'=> '3198',
            'time'=> $time_str,
            'key'=>md5("YeJSrpSwf&{$time_str}")
        ];
        return array_merge($data, $common_data);
    }

    /**
     * 查询店铺信息
     * @param $store_id string 店铺id
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get_store_data($store_id){
        $post_data = $this->bulid_data(['id'=>$store_id]);
        return json_decode($this->client->post("$this->domain/newapi/ykj_dp",['form_params'=>$post_data])->getBody()->getContents(),true);

    }

    /**
     * 查询一口价信息
     */
    public function get_yikoujia_list($form_params){
        $post_data = $this->bulid_data($form_params);
        $result = json_decode($this->client->post("$this->domain/newapi/ykj_get_list",['form_params'=>$post_data])->getBody()->getContents(),true);

        if ($result['code'] != 1){
            return $this->get_yikoujia_list($form_params);
        }
        return $result;


    }



}
<?php


namespace app\admin\controller\website;
use think\App;
use think\db\Where;
use think\facade\Db;

//店铺统计功能    筛选然后在重新出入数据库
class StoreTask
{
    public function __construct()
    {
        $this->model = new \app\admin\model\WebsiteTask();
        $this->model_site = new \app\admin\model\WebsiteSite();
        $this->model_cate = new \app\admin\model\WebsiteCate();
    }

    public function select_mongo_data($table_name,$where){
        $data = Db::connect('mongo')
            ->table($table_name)
            ->order('创建时间')
            ->where($where)
            ->select()->toArray();
        return $data;
    }

    public function insert_mongo_data($data,$user_id){
        unset($data['_id']);
        //出入之前判断有没有此数据
        $where[] = ['创建时间','=',$data['创建时间']];
        $where[] = ['店铺','=',$data['店铺']];
        $where[] = ['user_id','=',$user_id];
        $data_flag = $this->select_mongo_data('store_statistics',$where);
        if ($data_flag == []){
            Db::connect('mongo')
                ->table('store_statistics')
                ->save($data);
        }

    }

    public function etsy_store(){
//       先查cate表的etsy的店铺id 在查找出需要site表中添加了多少等于cate_id的数据一个一个进行筛选
//       etsy的店铺的数据  是哪一个店铺的    然后去数据库查店铺当前日期的产品数  然后去查前七天的产品数
        $cate_data = $this->model_cate->where('remark','=','Etsy店铺')->find();
        $cate_id = $cate_data['id'];
        //查找site表中的数据  遍历查找数据
        $site_data = $this->model_site->where('cate_id','=',$cate_id)->select()->toArray();
        foreach ($site_data as $index=>$items){
            $store_name = $items['web_url'];
            $name_arry = explode('/',$store_name);
            $store_name = explode('?',$name_arry[4])[0];
            //去mongo中查询当天店铺的数据数据  在找出前七天数据\
            $where = [];
            $where[] = ['店铺','=',$store_name];
            $today_store_data = $this->select_mongo_data('etsy',$where);


            if ($today_store_data == []){
                continue;
            }

            $last_time = $today_store_data[0]['创建时间'];
            $where[] = ['创建时间','>=',substr($last_time,0,10)];
            $today_store_data = $this->select_mongo_data('etsy',$where);
            $history_store_data = [];
            for ($i=7;$i >1;$i--){
                $where = [];
                $date_begin = date('Y-m-d', strtotime('-'.$i.' days')); //保留年-月-日
                $date_end = date('Y-m-d', strtotime('-'.($i-1).' days')); //保留年-月-日
                $where[] = ['创建时间','between',[$date_begin,$date_end]];
                $where[] = ['店铺','=',$store_name];
                $history_store_data = $this->select_mongo_data('etsy',$where);
                if (count($history_store_data) <> 0){
                    break;
                }
            }

            //上架数量   店铺商品数量 存入数据库
            $today_store_data[0]['up_goods_count'] = count($today_store_data) - count($history_store_data);
            $today_store_data[0]['count'] = count($today_store_data);
            $today_store_data[0]['type'] = '1';
            $today_store_data[0]['user_id'] =$items['user_id'];
            $this->insert_mongo_data($today_store_data[0],$items['user_id']);
        }

    }

    public function amazon_store(){
        $cate_data = $this->model_cate->where('remark','=','亚马逊账户')->find();
        $cate_id = $cate_data['id'];
        //查找site表中的数据  遍历查找数据
        $site_data = $this->model_site->where('cate_id','=',$cate_id)->select()->toArray();
        foreach ($site_data as $index=>$items){
            $where = [];
            $where[] = ['task_id','=',$items['task_id']];
            $store_data = Db::connect('mongo')
                ->table('亚马逊')
                ->order('创建时间')
                ->where($where)
                ->find();
            if ($store_data == []){
                continue;
            }
            $store_name = $store_data['店铺'];
            //去mongo中查询当天店铺的数据数据  在找出前七天数据\
            $where = [];
            $where[] = ['店铺','=',$store_name];
            $today_store_data = $this->select_mongo_data('亚马逊',$where);
            $history_store_data = [];
            if ($today_store_data == []){
                continue;
            }
            $last_time = $today_store_data[0]['创建时间'];
            $where[] = ['创建时间','>=',substr($last_time,0,10)];
            $today_store_data = $this->select_mongo_data('亚马逊',$where);
            $history_store_data = [];

            for ($i=7;$i >1;$i--){
                $where = [];
                $date_begin = date('Y-m-d', strtotime('-'.$i.' days')); //保留年-月-日
                $date_end = date('Y-m-d', strtotime('-'.($i-1).' days')); //保留年-月-日
                print_r($date_begin,$date_end);
                $where[] = ['创建时间','between',[$date_begin,$date_end]];
                $where[] = ['店铺','=',$store_name];
                $history_store_data = $this->select_mongo_data('亚马逊',$where);
                if (count($history_store_data) <> 0){
                    break;
                }

            }
            //上架数量   店铺商品数量 存入数据库
            $today_store_data[0]['up_goods_count'] = count($today_store_data) - count($history_store_data);
            $today_store_data[0]['count'] = count($today_store_data);
            $today_store_data[0]['type'] = '2';
            $today_store_data[0]['user_id'] =$items['user_id'];
            $this->insert_mongo_data($today_store_data[0],$items['user_id']);

        }

    }

    public function index()
    {
//        $this->etsy_store();
        $this->amazon_store();
        return 'ok';
    }
}


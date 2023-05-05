<?php
// 应用公共文件

use app\admin\model\NodCustomerManagement;
use app\admin\model\NodSupplier;
use app\common\service\AuthService;
use think\facade\Cache;
use Ramsey\Uuid\Uuid;

if (!function_exists('uuid')) {
    /**
     * 生成UUID
     * @return string
     */
    function uuid(): string
    {
        return (string)Uuid::uuid4();
    }
}

if (!function_exists('now_time')) {
    /**
     * 生成当前时间 年月日时分秒毫秒
     * @return string
     */
    function now_time(): string
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);

        return date('YmdHis').substr($msectime, -3);


    }
}

if (!function_exists('kill_task')) {
    /**
     * 生成UUID
     * @return string
     */
    function kill_task($id): string
    {
        //liunx
        //exec('kill -9 '.$id);
        //        win
        exec('taskkill -f -pid ' . $id);
        return 1;
    }
}

if (!function_exists('start_task')) {
    /**
     * 生成UUID
     * @return string
     */
    function start_task($path,$id): string
    {
        //liunx
        $out = exec('nohup python3 '.$path.' '.$id.' > ./python_script/yikoujia/ccc.log 2>&1 &');
        return 1;
    }
}

if (!function_exists('__url')) {

    /**
     * 构建URL地址
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function __url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value)
    {
        $value = sha1('blog_') . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }

}

if (!function_exists('xdebug')) {

    /**
     * debug调试
     * @param string|array $data 打印信息
     * @param string $type 类型
     * @param string $suffix 文件后缀名
     * @param bool $force
     * @param null $file
     */
    function xdebug($data, $type = 'xdebug', $suffix = null, $force = false, $file = null)
    {
        !is_dir(runtime_path() . 'xdebug/') && mkdir(runtime_path() . 'xdebug/');
        if (is_null($file)) {
            $file = is_null($suffix) ? runtime_path() . 'xdebug/' . date('Ymd') . '.txt' : runtime_path() . 'xdebug/' . date('Ymd') . "_{$suffix}" . '.txt';
        }
        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . "========================= {$type} ===========================" . PHP_EOL, FILE_APPEND);
        $str = ((is_string($data) ? $data : (is_array($data) || is_object($data))) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}

if (!function_exists('sysconfig')) {

    /**
     * 获取系统配置信息
     * @param $group
     * @param null $name
     * @return array|mixed
     */
    function sysconfig($group, $name = null)
    {
        $where = ['group' => $group];
//        $value = empty($name) ? Cache::get("sysconfig_{$group}") : Cache::get("sysconfig_{$group}_{$name}");
        if (empty($value)) {
            if (!empty($name)) {
                $where['name'] = $name;
                $value = \app\admin\model\SystemConfig::where($where)->value('value');
                Cache::tag('sysconfig')->set("sysconfig_{$group}_{$name}", $value, 3600);
            } else {
                $value = \app\admin\model\SystemConfig::where($where)->column('value', 'name');
                Cache::tag('sysconfig')->set("sysconfig_{$group}", $value, 3600);
            }
        }
        return $value;
    }
}

if (!function_exists('array_format_key')) {

    /**
     * 二位数组重新组合数据
     * @param $array
     * @param $key
     * @return array
     */
    function array_format_key($array, $key)
    {
        $newArray = [];
        foreach ($array as $vo) {
            $newArray[$vo[$key]] = $vo;
        }
        return $newArray;
    }

}

if (!function_exists('auth')) {

    /**
     * auth权限验证
     * @param $node
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function auth($node = null)
    {
        $authService = new AuthService(session('admin.id'));
        $check = $authService->checkNode($node);
        return $check;
    }

}

if (!function_exists('array_remove')) {
    /**
     * @param $data
     * 数组
     * @param $key
     * 删除的key
     * @return mixed 返回新的数组
     */
    function array_remove($data, $key){
        if(!array_key_exists($key, $data)){
            return $data;
        }
        $keys = array_keys($data);
        $index = array_search($key, $keys);
        if($index !== FALSE){
            array_splice($data, $index, 1);
        }
        return $data;

    }

}
if (!function_exists('delete_where_filter')) {
    /**
     * @param $where
     * where 条件
     * @param $field
     * @return mixed 返回新的where
     */
    function delete_where_filter($where, $field){
        $new_where = [];
        foreach ($where as $item){
            if ($item[0] != $field) $new_where[] = $item;
        }
        return $new_where;

    }

}

if (!function_exists('delete_dict_key')) {
    /**
     * @param $data
     * 数组
     * @param $key
     * 删除的key
     * @return mixed 返回新的数组
     */
    function delete_dict_key($dict, $key){
        $new_dict= [];
        foreach ($dict as $k=>$v){
            if ($key ==$k) continue;
            $new_dict[$k] = $v;
        }
        return $new_dict;

    }

}

if (!function_exists('delete_unnecessary_order_info')) {
    /**
     * @param $pid
     * 单据id
     * @param $save_data
     * 需要保存的数据
     * @return mixed 返回新的数组
     */
    function delete_unnecessary_order_info($pid, $save_data){
        $all_id_list = [];
        $all_data = \app\admin\model\NodOrderInfo::where('pid','=',$pid)->select()->toArray();
        foreach ($all_data as $item){
            $all_id_list[] = $item['id'];
        }
        $save_id_list = [];
        foreach ($save_data as $item){
            $save_id_list[] = $item['id'];
        }
        $unnecessary_id = array_diff($all_id_list,$save_id_list);
        \app\admin\model\NodOrderInfo::where('id','in',$unnecessary_id)->delete();


    }

}

if (!function_exists('get_total_receivable_price')) {
    /**
     * @return mixed
     * 获取总应收款金额
     */
    function get_total_receivable_price(){
        $customer_price = NodCustomerManagement::sum('receivable_price');
        $supplier_price = NodSupplier::sum('receivable_price');
        return $supplier_price+$customer_price;


    }

}

if (!function_exists('get_total_account_price')) {
    /**
     * @return mixed
     * 获取总应收款金额
     */
    function get_total_account_price(){
        $price = \app\admin\model\NodAccount::sum('balance_price');
        return $price;


    }

}

if (!function_exists('get_inventory')) {
    /**
     * @return mixed
     * 获取总应收款金额
     */
    function get_inventory(){
        $all_data = \app\admin\model\NodInventory::select()->toArray();
        $list = [];
        foreach ($all_data as $item){
            $list[] = $item['good_name'];
        }


        return $list;


    }

}


if (!function_exists('check_practical_price')) {
    /**
     * @return mixed
     * 检查单据金额和订单金额是否一样
     */
    function check_practical_price($practical_price,$item){
        $price = 0;

        foreach ($item as $v){
            $price += floatval($v['unit_price']);
        }
        return floatval($practical_price) == $price;
    }
}



if (!function_exists('calculator_paid_price')) {
    /**
     * @return mixed
     * 计算单据金额
     */
    function calculator_paid_price($data){
        $price = 0;

        foreach ($data as $v){
            $price += intval($v);
        }
        return $price;
    }
}

if (!function_exists('get_warehouse_ym')) {
    /**
     * @return mixed
     * 根据仓库名字获取仓库下所有的域名
     */
    function get_warehouse_ym($warehouse_name){
        $list = [];
        $all = \app\admin\model\NodWarehouse::where('name','=',$warehouse_name)->select();

        foreach ($all as $item){
            $list[] = $item['good_name'];
        }


        return $list;
    }
}

if (!function_exists('save_jvming_order_log')) {
    /**
     * @return mixed
     * 保存聚名订单日志
     */
    function save_jvming_order_log($data,$cate,$account,$search_time){

        $insert_all_data = [];
        foreach ($data as $item){
            $insert_all_data[] = [
                'order_id'=>$item['id'],
                'cate'=>$cate,
                'account'=>$account,
                'search_time'=>$search_time,
                'order_data'=>json_encode($item),
            ];
        }
        if ($insert_all_data ==[]) return;
        try {
            \app\admin\model\NodJvMingOrderLog::insertAll($insert_all_data);
        }catch (\Exception $e){
            dd($e->getMessage(),$insert_all_data);
        }


    }
}

if (!function_exists('format_where_datetime')) {
    /**
     * @return mixed
     * 保存聚名订单日志
     */
    function format_where_datetime($where,$filed): mixed
    {
        $w = [];
        foreach ($where as $item){
            if ($item[0] == $filed){
                if ($item[1] == '<='){
                    $item[2] = date('Y-m-d',$item[2]).' 23:59:59';

                }else{
                    $item[2] = date('Y-m-d H:i:s',$item[2]);

                }
                $w[] = $item;
                continue;
            }
            $w[] = $item;
        }
        return $w;
    }
}

if (!function_exists('check_order_exist')) {
    /**
     * @return mixed
     * 检查次域名是否存在  存在不录入
     */
    function check_order_exist($ym,$time,$cate): mixed
    {
        //获取当前时间的所有订单
        $all_order = \app\admin\model\NodOrder::whereRaw('DATE_FORMAT(order_time,"%Y-%m-%d") = "'.$time.'"')->where('type','=',$cate)->select()->toArray();
        foreach ($all_order as $item){
            //如果存在 返回true
            if ($cate == 9){   //其他收入单
                $d = \app\admin\model\NodOrderInfo::where('pid','=',$item['id'])->where('remark','=','日期：'.$time.' 竞价活动 '.$ym)->find();
            }else{
                $d = \app\admin\model\NodOrderInfo::where('pid','=',$item['id'])->where('good_name','=',$ym)->find();
            }

            if (!empty($d)){
                return true;
            }

        }
        //最后直接返回flase
        return false;

    }
}

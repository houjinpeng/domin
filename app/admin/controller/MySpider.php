<?php


namespace app\admin\controller;
use MongoDB\BSON\ObjectId;
use think\facade\Db;
use function app\admin\controller\website\getProject;
use function app\admin\controller\website\scheduleHandler;

class MySpider
{

    public function getProject()
    {

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8080",
            CURLOPT_URL => "http://".sysconfig('spider','spider_domain')."/api/projects",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: ".sysconfig('spider','spider_token'),
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function scheduleHandler($name, $spider_id, $param,$corn='')
    {
        $param = htmlspecialchars_decode($param);
        $curl = curl_init();
        if ($corn == ''){
            $now_time= time()+2*60;
            $h= date('H',$now_time);
            $m= date('i',$now_time);
            $corn = '0 '.$m.' '.$h.' * * *';
        }
        $parm_str ="{\"node_ids\":[],\"name\":\"$name\",\"run_type\":\"random\",\"spider_id\":\"$spider_id\",\"cron\":\"$corn\",\"param\":\"\\\"$param\\\"\"}";
        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8080",
            CURLOPT_URL => "http://".sysconfig('spider','spider_domain')."/api/schedules",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => $parm_str,
            CURLOPT_HTTPHEADER => array(
                "Authorization: ".sysconfig('spider','spider_token'),
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    /**
     * @param $name   ?????????title
     * @param $cateName ???????????????????????????  ??????myspider????????????name
     * @param $param  ??????????????????  ??????
     * @param string $corn  corn?????????
     * @return string|void
     */
    public function addSchedule($name, $cateName, $param,$corn='')
    {
        $resp = $this->getProject();
        $resp = json_decode($resp,true);
        $spider_id = 0;
        $is_find = false;
        //?????????????????????id
        foreach ($resp['data'] as $index=>$items){
            if ($is_find == false){
                foreach ($items['spiders'] as $key => $value) {
                    if ($value['display_name'] == $cateName) {
                        $spider_id = $value['_id'];
                        $is_find = true;
                        break;
                    }
                }
            }
        }
        if ($spider_id == 0) {
            return '????????????';
        }
        if (strstr($cateName, '????????????')){
            $param =str_replace("\n",',',$param);
        }
        $reslut = $this->scheduleHandler($name, $spider_id, $param,$corn);
        return $this->selectMongo($name,$param);
    }

    public function selectMongo($name,$param){

        $where[] = ['name','=',$name];
        if (strstr($param,'"')==false){
            $param = '"'.$param.'"';
        }

        $where[] = ['param','=',$param];
        $mongo = Db::connect('mongo')
            ->table('schedules')
            ->where($where)
            ->order('create_ts')
            ->limit(1)
            ->select()
            ->toArray();

        try {
            $spider_id = $mongo[0]['_id'];
        }catch (\Exception $e){
            $spider_id = 0;
        }
        return $spider_id;

    }

    public function deleteSchedule($id){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_PORT => "8080",
            CURLOPT_URL => "http://".sysconfig('spider','spider_domain')."/api/schedules/$id",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_POSTFIELDS => "",
            CURLOPT_HTTPHEADER => array(
                "Authorization: ".sysconfig('spider','spider_token'),
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
//        echo $response;
            return $response;
        }
    }

    /**
     * @param $update ?????????????????????array ?????????name parm
     */
    public function updateSchedule($update,$schedule_id='',$name=''){
        if ($schedule_id == ''){
            $wherem[] = ['name','=',$update['title_old']];
            $wherem[] = ['param','=','"'.htmlspecialchars_decode($update['web_url']).'"'];
            $save_data = array('name'=>$update['title']);
            Db::connect('mongo')
                ->table('schedules')
                ->where($wherem)
                ->save($save_data);
            return true;
        }else{
            Db::connect('mongo')
                ->table('schedules')
                ->where('_id','=',$schedule_id)
                ->save(['name'=>$name]);
            return true;
        }

    }

    /**
     * @param $update ?????????????????????array ?????????name parm
     */
    public function updateScheduleName($schedule_id,$name){

        Db::connect('mongo')
            ->table('schedules')
            ->where('_id','=',$schedule_id)
            ->save(['name'=>$name]);
        return true;

    }

    /**
     * @param $param  ????????????
     */
    public function getTaskData($param){

        try {
//            $param = htmlspecialchars_decode($param);
//            $param = '"' . str_replace("\n", ',', $param) . '"';
            $url = htmlspecialchars_decode($param['web_url']);
            $url = '"' . str_replace("\n", ',', $url) . '"';
        } catch (\Exception $e) {
            $param = '';
            return 0;
        }
        if ($param['schedule_id']){
            $schedule_id = new ObjectId($param['schedule_id']);
            $mongo = \think\facade\Db::connect('mongo')
                ->table('tasks')
                ->where('param','=',$url)
                ->where('schedule_id','=',$schedule_id)
                ->order('create_ts', 'desc')
                ->select()->toArray();
        }else{
            $mongo = \think\facade\Db::connect('mongo')
                ->table('tasks')->where('param','=',$url)
                ->order('create_ts', 'desc')
                ->select()->toArray();
        }
        if (empty($mongo)){
            return 0;
        }else{
            return $mongo[0];
        }

    }

}
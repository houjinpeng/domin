<?php


namespace app\admin\controller;
use app\admin\model\NodWarehouse;
use app\common\controller\AdminController;
use GuzzleHttp\Client;
use think\App;
use think\facade\Db;


class JvMing  extends AdminController
{

    public function __construct($username,$password,$cookie)
    {
        $this->username = $username;
        $this->password = $password;
        $this->cookie = $cookie;
        $this->client = new Client();
    }

    public function request($url,$headers){
//        '登录超时'
        $headers['cookie'] = $this->cookie;
        try {
            $resp = $this->client->request('GET',$url,[
                'headers'=>$headers
            ])->getBody()->getContents();

            $resp = json_decode($resp,true);

            if (strstr($resp['msg'],'登录超时')){
                $this->cookie = $this->login();
                if ($this->cookie =='帐户或密码错误!'){
                    $data = ['code'=>999,'msg'=>'账号【'.$this->username.'】帐户或密码错误!'];
                    return $data;
                }



                return $this->request($url,$headers);
            }

            return $resp;

        }catch (\Exception $e){
            dd($e);
            return $this->request($url,$headers);
        }


    }


    /**
     * 登陆聚名 获取cookie
     */
    public function login(){

        $login_url = 'https://www.juming.com/user_zh/p_login';
//        $token_data = $this->client->request('GET','http://192.168.11.190:5001/get_token')->getBody()->getContents();
//        $token_data = $this->client->request('GET','http://192.168.1.109:5001/get_token')->getBody()->getContents();
        $token_data =  $this->client->request('GET','http://127.0.0.1:5001/get_token')->getBody()->getContents();
        $token_data = json_decode($token_data,true);
        $token = $token_data['token'];
        $sid = $token_data['session'];
        $sig = $token_data['auth'];

        //生成加密密码
        $pws = md5('[jiami'.$this->password.'mima]');
        //取19位
        $password_md5 = md5(substr($pws,0,19));
        $password_md5 = substr($password_md5,0,19);
        $headers = [
            'accept'=> 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding'=> 'gzip, deflate, br',
            'accept-language'=> 'zh-CN,zh;q=0.9',
            'cache-control'=> 'no-cache',
            'content-type'=> 'application/x-www-form-urlencoded; charset=UTF-8',
            'origin'=> 'https://www.juming.com',
            'pragma'=> 'no-cache',
            'referer'=> 'https://www.juming.com/',
            'sec-ch-ua'=> '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile'=> '?0',
            'sec-ch-ua-platform'=> '"Windows"',
            'sec-fetch-dest'=> 'empty',
            'sec-fetch-mode'=> 'cors',
            'sec-fetch-site'=> 'same-origin',
            'user-agent'=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'x-requested-with'=> 'XMLHttpRequest',
        ];

        $resp = $this->client->request('POST',$login_url,[
            'form_params'=>[
                'token'=>$token,
                'sid'=>$sid,
                'sig'=>$sig,
                're_mm'=>$password_md5,
                're_yx'=>$this->username,
                'fs'=>'tl',
            ],
            'headers'=>$headers
        ]);
        $result = json_decode($resp->getBody()->getContents(),true);

        if (strstr($result['msg'],'登陆成功')) {
            $cookie = explode(';',$resp->getHeaders()['Set-Cookie'][0])[0];
            NodWarehouse::where('account','=',$this->username)->where('password','=',$this->password)->update(['cookie'=>$cookie]);
            return $cookie;

        }
        if (strstr($result['msg'],'帐户或密码错误!')){
            return '帐户或密码错误!';
        }
        return  $this->login();

    }


    //下载数据
    public function download_sales_ym(){
        $url = 'https://www.juming.com/user_ym/ym_list_dc?dcfs=88&gjz_cha=&ymlx=&ymzt=&zcsj_1=&zcsj_2=&gjz_cha2=&ymhz=&ymzcs=&dqsj_1=&dqsj_2=&dnsbh=&ymmb=&jgpx=&ymcd_1=&ymcd_2=&uid=';
        $headers = [
            'accept'=> 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'accept-encoding'=> 'gzip, deflate, br',
            'accept-language'=> 'zh-CN,zh;q=0.9',
            'cache-control'=> 'no-cache',
            'cookie'=>$this->cookie,
            'content-type'=> 'application/x-www-form-urlencoded; charset=UTF-8',
            'pragma'=> 'no-cache',
            'sec-ch-ua'=> '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile'=> '?0',
            'sec-ch-ua-platform'=> '"Windows"',
            'sec-fetch-dest'=> 'empty',
            'sec-fetch-mode'=> 'cors',
            'sec-fetch-site'=> 'same-origin',
            'user-agent'=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
        ];
        $resp =  $this->client->request('GET',$url,['headers'=>$headers])->getBody()->getContents();
        if (strstr($resp,'登录超时,请重新登录!')){
            $this->cookie = $this->login($this->username,$this->password);
            if ($this->cookie == '帐户或密码错误!'){
                return '帐户或密码错误!';
            }
            return $this->download_sales_ym();

        }

        $all_data = explode("\r\n",$resp);
        $list = [];
        foreach ($all_data as $index=>$item){
            if ($index ==0 || $item=='')continue;

            $item = explode(',',$item);
            $list[$item[0]] = ['ym'=>$item[0],'zc_time'=>$item[1],'dq_time'=>$item[2]];

        }

        return $list;


    }


    //查询资金明细
    public function get_financial_details($search_list){
        $sou = join('%0A',$search_list);
        $headers = [
            'accept'=> 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding'=> 'gzip, deflate, br',
            'accept-language'=> 'zh-CN,zh;q=0.9',
            'cache-control'=> 'no-cache',
            'content-type'=> 'application/x-www-form-urlencoded; charset=UTF-8',
            'origin'=> 'https://www.juming.com',
            'pragma'=> 'no-cache',
            'referer'=> 'https://www.juming.com/',
            'sec-ch-ua'=> '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile'=> '?0',
            'sec-ch-ua-platform'=> '"Windows"',
            'sec-fetch-dest'=> 'empty',
            'sec-fetch-mode'=> 'cors',
            'sec-fetch-site'=> 'same-origin',
            'user-agent'=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'x-requested-with'=> 'XMLHttpRequest',
        ];

        $all_data = [];
        for ($i=1; $i<=100; $i++)
        {
            $url = 'https://www.juming.com/user_main/zjmx_list?page='.$i.'&limit=500&sou='.$sou.'&sj=';
            $data = $this->request($url,$headers);
            if ($data['code'] == 999){
                return $data;
            }

            $total_page = $data['count']/500;
            foreach ($data['data'] as $item){
                isset($all_data[$item['ym']])?$all_data[$item['ym']][] = $item:$all_data[$item['ym']] = [$item];
            }
            if ($i > $total_page)   break;
        }





        return $all_data;

    }


    public function get_sale_ym($start_time,$end_time){
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/ykj_list?page=1&limit=500&fs=1&gjz_cha=&ymhz=&wtqian_1=&wtqian_2=&cbqian_1=&cbqian_2=&gjz_cha2=&ifjj=&dqsj_1=&dqsj_2=&mjsj_1='.$start_time.'&mjsj_2='.$end_time;
        $headers = [
               'accept'=> 'application/json, text/javascript, */*; q=0.01',
               'accept-encoding'=> 'gzip, deflate, br',
               'accept-language'=> 'zh-CN,zh;q=0.9',
               'cache-control'=> 'no-cache',
               'content-type'=> 'application/x-www-form-urlencoded; charset=UTF-8',
               'origin'=> 'https://www.juming.com',
               'pragma'=> 'no-cache',
               'referer'=> 'https://www.juming.com/',
               'sec-ch-ua'=> '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
               'sec-ch-ua-mobile'=> '?0',
               'sec-ch-ua-platform'=> '"Windows"',
               'sec-fetch-dest'=> 'empty',
               'sec-fetch-mode'=> 'cors',
               'sec-fetch-site'=> 'same-origin',
               'user-agent'=> 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
               'x-requested-with'=> 'XMLHttpRequest',
           ];

        $result = $this->request($url,$headers);



        return $result;




    }


}
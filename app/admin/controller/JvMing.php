<?php


namespace app\admin\controller;

use app\admin\model\NodWarehouse;
use app\common\controller\AdminController;
use GuzzleHttp\Client;
use http\Params;
use think\App;
use think\facade\Db;


class JvMing extends AdminController
{

    public function __construct($username='', $password='', $cookie='')
    {
        $this->redis = new \Redis();
        $this->username = $username;
        $this->password = $password;
        $this->cookie = $cookie;
//        $this->client = new Client(['cookies' => true, 'allow_redirects' => true,]);
        $this->client = new Client(['cookies' => false, 'allow_redirects' => true,]);
        $this->headers = [
            'accept' => 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'pragma' => 'no-cache',
            'sec-ch-ua' => '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest',
            'origin' => 'http://7a08c112cda6a063.juming.com:9696',
            'referer' => 'http://7a08c112cda6a063.juming.com:9696',
        ];

    }

    public function request($url, $headers,$query='')
    {
//        '登录超时'
//        $headers['cookie'] = 'PHPSESSID=h6ahrbvj7orpic74hf37ta8577';
        $headers['cookie'] = $this->cookie;
        try {
            $opt = [
                'headers' => $headers
            ];
            empty($query)||$opt['query'] = $query;
            $result = $this->client->request('GET', $url, $opt)->getBody()->getContents();

            $resp = json_decode($result, true);
            if ($resp == null){
                if (strstr($result, '登录超时')) {
                    $this->cookie = $this->login();
                    if ($this->cookie == '帐户或密码错误!') {
                        $data = ['code' => 999, 'msg' => '账号【' . $this->username . '】帐户或密码错误!'];
                        return $data;
                    }
                    return $this->request($url, $headers);
                }
            }else{
                if (strstr($resp['msg'], '登录超时') || strstr($resp['msg'], '请先登录')) {
                    $this->cookie = $this->login();
                    if ($this->cookie == '帐户或密码错误!') {
                        $data = ['code' => 999, 'msg' => '账号【' . $this->username . '】帐户或密码错误!'];
                        return $data;
                    }


                    return $this->request($url, $headers);
                }
            }




            return $resp;

        } catch (\Exception $e) {
            $data = ['code' => 999, 'msg' => '账号【' . $this->username . '】 错误：' . $e->getMessage()];
            return $data;
//            return $this->request($url,$headers);
        }


    }


    /**
     * 登陆聚名 获取cookie
     */
    public function login()
    {
        $client = new Client(['cookies' => true, 'allow_redirects' => true,]);

        $uuid = str_replace('-', '', uuid());
//        $login_url = 'https://www.juming.com/user_zh/p_login';
        $login_url = 'http://7a08c112cda6a063.juming.com:9696/user_zh/p_login';
        // 设置 Redis 服务器地址和端口号
        $host = '127.0.0.1';
        $port = 6379;
        $this->redis->connect($host, $port);
        $this->redis->select(13);
        $randomKey = $this->redis->randomKey();

        if (!empty($randomKey)) {
            // 根据随机 key 获取对应的 value
            $token_data = $this->redis->get($randomKey);
        } else {
            return false;
        }

        $token_data = json_decode($token_data, true);
        $token = $token_data['token'];
        $sid = $token_data['session'];
        $sig = $token_data['auth'];
        //生成加密密码
        $pws = md5('[jiami' . $this->password . 'mima]');
        //取19位
        $password_md5 = md5(substr($pws, 0, 19));
        $password_md5 = substr($password_md5, 0, 19);
        $headers = [
            'accept' => 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'pragma' => 'no-cache',
            'sec-ch-ua' => '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest',
            'cookie' => 'PHPSESSID=' . $uuid
        ];
        $headers_index = [
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'cookie' => 'PHPSESSID=' . $uuid
        ];
        $jar = new \GuzzleHttp\Cookie\CookieJar;
        $resp1 = $client->request('POST', 'http://7a08c112cda6a063.juming.com:9696/user_zh/wxdl_ewm', ['headers' => $headers]);
        $resp = $client->request('POST', $login_url, [
//            'cookies'=>$jar,
            'form_params' => [
                'token' => $token,
                'sid' => $sid,
                'sig' => $sig,
                're_mm' => $password_md5,
                're_yx' => $this->username,
                'fs' => 'tl',
            ],
            'headers' => $headers
        ]);

//        dd($resp->getHeaders()['Set-Cookie'],$resp1->getHeaders()['Set-Cookie']);
        $cookie = explode(';', $resp1->getHeaders()['Set-Cookie'][0])[0];

        $result = json_decode($resp->getBody()->getContents(), true);
        //生成加密密码
        $pws = md5('[jiami' . $this->password . 'mima]');
        //取19位
        $password_md5 = md5($result['token'] . substr($pws, 0, 19));
        $password_md5 = substr($password_md5, 0, 19);

        $form_params = [
            'token' => $token,
            'sid' => $sid,
            'sig' => $sig,
            're_mm' => $password_md5,
            're_yx' => $this->username,
            'fs' => 'tl',
            'dltoken' => $result['token']
        ];
        $resp = $client->request('POST', $login_url, [
//            'cookies'=>$jar,
            'form_params' => $form_params,
            'headers' => $headers
        ]);
        $result = json_decode($resp->getBody()->getContents(), true);
        if (strstr($result['msg'], '登陆成功')) {
            foreach ($client->getConfig('cookies') as $item) {
                if ($item->getName() == 'PHPSESSID') {
                    $cookie = 'PHPSESSID=' . $item->getValue();
                    NodWarehouse::where('account', '=', $this->username)->where('password', '=', $this->password)->update(['cookie' => $cookie]);

                }
            }
            return $cookie;

        }
        if (strstr($result['msg'], '帐户或密码错误!')) {
            return '帐户或密码错误!';
        }
        return $this->login();

    }


    //下载数据
    public function download_sales_ym()
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/ym_list_dc?dcfs=88&gjz_cha=&ymlx=&ymzt=&zcsj_1=&zcsj_2=&gjz_cha2=&ymhz=&ymzcs=&dqsj_1=&dqsj_2=&dnsbh=&ymmb=&jgpx=&ymcd_1=&ymcd_2=&uid=';
        $headers = [
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'cache-control' => 'no-cache',
            'cookie' => $this->cookie,
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'pragma' => 'no-cache',
            'sec-ch-ua' => '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
        ];
        $resp = $this->client->request('GET', $url, ['headers' => $headers])->getBody()->getContents();
        if (strstr($resp, '登录超时,请重新登录!')) {
            $this->cookie = $this->login($this->username, $this->password);
            if ($this->cookie == '帐户或密码错误!') {
                return '帐户或密码错误!';
            }elseif ($this->cookie == false){
                return 'token 不足';
            }
            return $this->download_sales_ym();

        }

        $all_data = explode("\r\n", $resp);
        $list = [];
        foreach ($all_data as $index => $item) {
            if ($index == 0 || $item == '') continue;

            $item = explode(',', $item);
            $list[$item[0]] = ['ym' => $item[0], 'zc_time' => $item[1], 'dq_time' => $item[2]];

        }

        return $list;


    }


    //查询资金明细
    public function get_financial_details($search_list)
    {
        $sou = join('%0A', $search_list);
        $headers = [
            'accept' => 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'pragma' => 'no-cache',
            'sec-ch-ua' => '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest',
        ];

        $all_data = [];
        for ($i = 1; $i <= 100; $i++) {
            $url = 'http://7a08c112cda6a063.juming.com:9696/user_main/zjmx_list?page=' . $i . '&limit=500&sou=' . $sou . '&sj=';
            $data = $this->request($url, $headers);
            if ($data['code'] == 999) {
                return $data;
            }

            $total_page = $data['count'] / 500;
            foreach ($data['data'] as $item) {
                isset($all_data[$item['ym']]) ? $all_data[$item['ym']][] = $item : $all_data[$item['ym']] = [$item];
            }
            if ($i > $total_page) break;
        }


        return $all_data;

    }

    //获取指定时间出售域名
    public function get_sale_ym($start_time, $end_time)
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/ykj_list?page=1&limit=500&fs=1&gjz_cha=&ymhz=&wtqian_1=&wtqian_2=&cbqian_1=&cbqian_2=&gjz_cha2=&ifjj=&dqsj_1=&dqsj_2=&mjsj_1=' . $start_time . '&mjsj_2=' . $end_time;
        $headers = [
            'accept' => 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
//            'origin' => 'https://www.juming.com',
            'pragma' => 'no-cache',
//            'referer' => 'https://www.juming.com/',
            'sec-ch-ua' => '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/104.0.0.0 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest',
        ];

        $result = $this->request($url, $headers);


        return $result;


    }

    //查询资金明细
    public function get_financial_detailss($search_list = [], $start_time = '', $end_time = '')
    {
        $sou = join('%0A', $search_list);
        $headers = [
            'accept' => 'application/json, text/javascript, */*; q=0.01',
            'accept-encoding' => 'gzip, deflate, br',
            'accept-language' => 'zh-CN,zh;q=0.9',
            'cache-control' => 'no-cache',
            'content-type' => 'application/x-www-form-urlencoded; charset=UTF-8',
//            'origin' => 'https://www.juming.com',
            'pragma' => 'no-cache',
//            'referer' => 'https://www.juming.com/',
            'sec-ch-ua' => '"Chromium";v="104", " Not A;Brand";v="99", "Google Chrome";v="104"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Windows"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'x-requested-with' => 'XMLHttpRequest',
        ];
        $sj = '';
        if ($start_time != '') {
            $sj = $start_time . '_' . $end_time;
        }

        $all_data = [];
        for ($i = 1; $i <= 100; $i++) {
            $url = 'http://7a08c112cda6a063.juming.com:9696/user_main/zjmx_list?page=' . $i . '&limit=500&sou=' . $sou . '&sj=' . $sj;
            $data = $this->request($url, $headers);
            if ($data['code'] == 999) {
                return $data;
            }

            $total_page = $data['count'] / 500;
            foreach ($data['data'] as $item) {
                $all_data[] = $item;
            }
            if ($i > $total_page) break;
        }


        return $all_data;

    }

    //获取外部入库列表 同行push列表
    public function get_ruku_list($start_time = '', $end_time = '')
    {
        $headers =[
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language' => 'zh-CN,zh;q=0.9,en;q=0.8',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Pragma' => 'no-cache',
            'Upgrade-Insecure-Requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ];

        $url = 'http://7a08c112cda6a063.juming.com:9696/user_rk/ruku_list?page=1&limit=500&field=tjsj&order=desc';
        $result = $this->request($url, $headers);
        //判断时间范围
        $data = [];
        foreach ($result['data'] as $item) {
            $t = explode(' ', $item['tjsj'])[0];

            if ($start_time == '' || $end_time == '') {
                $data[] = $item;
                continue;
            }
            if ($start_time == $t) {
                $data[] = $item;
            }

        }
        return $data;


    }

    //获取券的最后价格
    public function get_quan_price()
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_main/zjmx_list?page=1&limit=500&lx=&zu=%E4%BC%98%E6%83%A0%E5%88%B8';
        $result = $this->request($url, $this->headers);
        foreach ($result['data'] as $item) {
            //判断是否是购买券
            if (strstr($item['sm'], '购买')) {
                return intval(explode('/元', explode(',', $item['sm'])[1])[0]);
            }
        }
        return 0;


    }


    public function get_push_detail($id)
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/push_xq';

        $result = $this->client->post($url, [
            'headers' => $this->headers, 'form_params' => ['id' => $id]
        ])->getBody()->getContents();
        return json_decode($result, true);


    }

    //获取发送域名 push域名
    public function get_push_list($start_time = '', $end_time = '')
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/push_list?page=1&limit=500';
        $result = $this->request($url,$this->headers);
        if ($result['code'] == 999) {
            return $result;
        }
        //获取每个订单中的所有信息  ['目标账户'=>['域名1','域名2']]
        $data = [];
        foreach ($result['data'] as $item) {
            $t = explode(' ', $item['sj'])[0];
            //对比时间
            if ($start_time == '' || $end_time == '') {
                $data[] = $item;
                continue;
            }
            if ($start_time == $t) {
                $data[] = $item;
            }


        }
        $result['data'] = $data;
        return $result;

    }


    //获取接收域名
    public function get_pull_list($start_time = '', $end_time = '')
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/pushin_list?page=1&limit=500';
        $result = $this->request($url, $this->headers);
        if ($result['code'] == 999) {
            return $result;
        }

        //获取每个订单中的所有信息  ['目标账户'=>['域名1','域名2']]
        $data = [];
        foreach ($result['data'] as $item) {
            $t = explode(' ', $item['sj'])[0];
            //对比时间
            if ($start_time == '' || $end_time == '') {
                $data[] = $item;
                continue;
            }
            if ($start_time == $t) {
                $data[] = $item;
            }


        }
        $result['data'] = $data;
        return $result;

    }

    //获取转出域名列表
    public function get_zhuanchu_list($start_time = '', $end_time = '')
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/zhuanchu_list?page=1&limit=500';
        $result = $this->request($url, $this->headers);
        if ($result['code'] == 999) {
            return $result;
        }
        //获取每个订单中的所有信息  ['目标账户'=>['域名1','域名2']]
        $data = [];
        foreach ($result['data'] as $item) {
            $t = explode(' ', $item['sj'])[0];
            //对比时间
            if ($start_time == '' || $end_time == '') {
                $data[] = $item;
                continue;
            }
            if ($start_time == $t) {
                $data[] = $item;
            }


        }
        $result['data'] = $data;
        return $result;
    }

    //获取账户余额等信息
    public function get_account_info()
    {
        $url = 'http://7a08c112cda6a063.juming.com:9696/user_zh/islogin?_=1681644180532';

        $result = $this->request($url, $this->headers);


        return $result;

    }

    //获取库存
    public function get_inventory()
    {

        $all_inventory = [];
        for ($i = 1; $i <= 100; $i++) {
            $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/ym_list?page=' . $i . '&limit=500&gjz_cha=&ymlx=&ymzt=&zcsj_1=&zcsj_2=&gjz_cha2=&ymhz=&ymzcs=&dqsj_1=&dqsj_2=&dnsbh=&ymmb=&jgpx=&ymcd_1=&ymcd_2=';
            $data = $this->request($url, $this->headers);
            if ($data['code'] == 999) {
                return $data;
            }

            $total_page = $data['count'] / 500;
            foreach ($data['data'] as $item) {
                $all_inventory[] = $item['ym'];
            }
            if ($i > $total_page) break;
        }

        return $all_inventory;

    }

    //获取关注列表
    public function get_gzlist()
    {

        $list = [];
        for ($i = 1; $i <= 100; $i++) {
            $url = 'http://7a08c112cda6a063.juming.com:9696/user_ym/ykj_gzlist?page=' . $i . '&limit=500&sou=';

            $data = $this->request($url, $this->headers);
            if ($data['code'] == 999) {
                return $data;
            }
            $total_page = $data['count'] / 500;
            foreach ($data['data'] as $item) {
                $item['account'] = $this->username;
                $list[$item['ym']] = $item;
            }
            if ($i > $total_page) break;

        }
        return $list;

    }


    //取消关注
    public function qx_gz($ym_id){
        $headers['cookie'] = $this->cookie;
        $url = 'http://7a08c112cda6a063.juming.com:9696/ykj/qx_gz';

        $data = [
            'id'=>$ym_id,
            'csrf_token'=>'PLQi1SAK7ZwQkQjI1'
        ];
        $result =json_decode( $this->client->post($url,['form_params'=>$data,'headers'=>$headers])->getBody()->getContents(),true);
        if ($result['msg'] == '请先登录!'){
            $this->login();
            return $this->qx_gz($ym_id);
        }

        return $result;


    }

    //关注
    public function add_gz($ym_id){
        $headers['cookie'] = $this->cookie;
        $url = 'http://7a08c112cda6a063.juming.com:9696/ykj/add_gz?id='.$ym_id.'&_t='.time()*1000;

        $result = $this->request($url,$headers);
        if ($result['msg'] == '请先登录!'){
            $this->login();
            return $this->add_gz($ym_id);
        }
        return $result;


    }

    //获取竞价列表
    public function get_jj_list($ymList){
        $url = "http://7a08c112cda6a063.juming.com:9696/user_main/jj_list";
        $query = [
            "page"=> 1,
            "limit"=> 500,
            "zt"=> "1",
            "gjz_cha"=> join("\n",$ymList),
            "ymgc"=> "",
            "sfbax"=> "",
            "sclx"=> "",
            "gjz_cha2"=> "",
            "ymhz"=> "",
            "jssj"=> "",
            "ymcd_1"=> "",
            "ymcd_2"=> ""
        ];
        return $this->request($url,$this->headers,$query);



    }


    //查询域名
    public function get_ym_list($ym){
        $url = "http://7a08c112cda6a063.juming.com:9696/user_ym/ym_list";
        $params = [
            "page"=> "1",
            "limit"=> "500",
            "gjz_cha"=> $ym,
            "ymlx"=> "",
            "ymzt"=> "",
            "zcsj_1"=> "",
            "zcsj_2"=> "",
            "gjz_cha2"=> "",
            "ymhz"=> "",
            "ymzcs"=> "",
            "dqsj_1"=> "",
            "dqsj_2"=> "",
            "dnsbh"=> "",
            "ymmb"=> "",
            "jgpx"=> "",
            "ymcd_1"=> "",
            "ymcd_2"=> ""
        ];
        $headers = [
            'cookie'=> $this->cookie,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ];
        $result = $this->request($url,$headers,$params);
        return $result;



    }

    //添加抢注域名

    /***
     * @param $ymlb string  域名多个,分割
     * @param $ydfs string  通道
     * @param string $bzsm   备注
     * @param string $ys    预算
     */
    public function add_ym(string $ymlb, $ydfs, string $bzsm='', string $ys=''){
        $url = "http://7a08c112cda6a063.juming.com:9696/qiang/add";
        $data = [
            'chkagreement'=>1,
            'bzsm'=>$bzsm,
            'ydfs'=>$ydfs,
            'fenzu'=>'',
            'ymlb'=>$ymlb,
            'ys'=>$ys
        ];

        $headers = [
            'cookie'=> $this->cookie,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
        ];

        $result = $this->client->post($url,['form_params'=>$data,'headers'=>$headers])->getBody()->getContents();
        $result = json_decode($result,true);
        if ($result['msg'] == '请先登录!'){
            $this->cookie = $this->login();
            if ($this->cookie == '帐户或密码错误!') {
                $data = ['code' => 999, 'msg' => '账号【' . $this->username . '】帐户或密码错误!'];
                return $data;
            }
            return $this->add_ym($ymlb, $ydfs,$bzsm,$ys);
        }

        return $result;

    }

}
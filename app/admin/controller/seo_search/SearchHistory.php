<?php


namespace app\admin\controller\seo_search;

use app\admin\model\SearchResult;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use GuzzleHttp\Client;

/**
 * @ControllerAnnotation(title="SEO查询历史查询")
 */
class SearchHistory extends AdminController
{

    use \app\admin\traits\Curd;


    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new SearchResult();
        $this->client = new Client();

    }

    /**
     * @NodeAnotation(title="页面")
     */
    public function index()
    {
        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="查询历史")
     */
    public function search()
    {

        $post = $this->request->post();

        $headers = [
            'Accept' => '*/*',
            'Accept-Language' => 'zh-CN,zh;q=0.9',
            'Proxy-Connection' => 'keep-alive',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'no-cache',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
        ];

//        $data = [
//            'ym' => $post['ym'],
//            'xq' => 'y',
//            'page' => '1',
//            'limit' => '20',
//            'token' => $post['token'],
//            'group' => '1',
//            'nian' => ''
//        ];

        $data = [
            'ym' => $post['ym'],
            'qg' => '',
            'token' => $post['token'],
        ];
        $url = 'http://47.56.160.68:10172/api.php';

        $result = json_decode($this->client->request('post',$url,['headers'=>$headers,'form_params'=>$data])->getBody()->getContents(),true);

        return json($result);


    }


    /**
     * @NodeAnotation(title="获取token")
     */
    public function get_token()
    {
        $post = $this->request->post();
        $url = 'http://192.168.1.105:5001/get_token';
        $token = json_decode($this->client->request('get', $url)->getBody()->getContents(), true);
        $headers = [
            'Accept' => 'application/json, text/javascript, */*; q=0.01',
            'Accept-Language' => 'zh-CN,zh;q=0.9',
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8',
            'Host' => '47.56.160.68:81',
            'Pragma' => 'no-cache',
            'Proxy-Connection' => 'keep-alive',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.132 Safari/537.36',
            'Origin' => 'http://47.56.160.68:81',
            'Referer' => 'http://47.56.160.68:81/piliang/',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        $api_url = "http://47.56.160.68:81/api.php?sckey=y";
        $data = [
            "ym" => join("\n", $post['data']),
            "authenticate" => $token["auth"],
            "token" => $token['token'],
            "sessionid" => $token['session']
        ];
        $result = $this->client->request('post', $api_url, [
            'headers' => $headers, 'form_params' => $data,
        ])->getBody()->getContents();
        $r = json_decode($result, true);
        return json($r);

    }


}

<?php


namespace app\admin\controller;

use app\admin\model\SystemAuth;
use PhpOffice\PhpSpreadsheet\IOFactory as PHPExcel_IOFactory;
use app\admin\model\WebsiteCustomCategory;
use think\db\Where;
use GuzzleHttp\Client;

class Tool
{
    public function __construct()
    {
        $this->client = new Client();
        $this->model_admin = new \app\admin\model\SystemAdmin();
    }

    public function info()
    {
        phpinfo();
    }

    //获取user_id
    public function get_user_id()
    {
        $this->model = new \app\admin\model\WebsiteSite();
//        如果其他用户 auth表belong不为0 就查询当前用户的id
        $where = [];
        if (session("admin")['id'] != 1) {
            $list = $this->model
                ->createuser()
                ->where('id', '=', session("admin")['id'])
                ->select()->toArray();
            if ($list[0]['belong_id'] == null || $list[0]['belong_id'] == 1) {
                $user_id = $list[0]['id'];
                $list1 = $this->model
                    ->createuser()
                    ->field('id')
                    ->where('belong_id', '=', $user_id)
                    ->select()->toArray();
                foreach ($list1 as $key => $v) {
                    $user_list[] = $v['id'];
                }
                $user_list [] = $list[0]['id'];
                $where[] = ['user_id', 'in', $user_list];

            } else {
                $where[] = ['user_id', '=', session("admin")['id']];
            }

        }


        return $where;
    }

    //获取user_id
    public function build_user_id()
    {

        $this->model_admin = new \app\admin\model\SystemAdmin();
        //查询当前用户可见组
        $user = $this->model_admin->find(session('admin.id'));
        if (session('admin.id') == 1) return [];
        //查询所有相同组的用户名
        $all_user = $this->model_admin->where('group_id', '=', $user['group_id'])->select()->toArray();
        $user_list = [];
        foreach ($all_user as $index => $item) {
            $user_list[] = $item['id'];
        }
        if (count($user_list) == 0) {
            return [];
        }
        return ['user_id', 'in', $user_list];

    }

    //获取当前组的id
    public function get_current_group_id($child_id = [], $search_id = 18, $group_id_list = [])
    {
        //根据查找此分组下的所有子组
        echo '查找id:' . $search_id . "<br>";

        if (empty($child_id) && $search_id != 0) {
            $child_id = $this->model_group->field(['id', 'title'])
                ->where('pid', '=', $search_id)->select()->toArray();
        }

        foreach ($child_id as $index => $item) {
            $group_id_list[] = $item['id'];
            echo '找到id:' . $item['id'] . $item['title'] . $search_id . '找到：' . count($child_id) . "个<br>";
            return $this->get_current_group_id($item, $item['id'], $group_id_list);
        }
        return $group_id_list;
    }

    //根据组查找出可以查看的user_id列表
    public function get_group_user_id()
    {
        if (session('admin.id') == 1) return [];

        //查看用户当前组
        $user = $this->model_admin->find(session('admin.id'));
        //查询当前用户可见组
        $group_id_list = [$user['group_id']];
        $group_id = $user['group_id'];
        for ($i = 1; $i <= 10; $i++) {
            //根据查找此分组下的所有子组
            $parent_id = $this->model_group->where('pid', '=', $group_id)->select()->toArray();
            if (empty($parent_id)) break;
            foreach ($parent_id as $index => $item) {
                $group_id = $item['id'];
                $group_id_list[] = $item['id'];
            }

        }
        //查询所有相同组的用户名
        $all_user = $this->model_admin->where('group_id', 'in', $group_id_list)->select()->toArray();
        $user_list = [];
        foreach ($all_user as $index => $item) {
            $user_list[] = $item['id'];
        }
        if (count($user_list) == 0) {
            return [];
        }
        return $user_list;

    }

    //构建where条件  是min max类型的
    public function build_select_where($where)
    {
        //先把where弄成array
        $ar = [];
        foreach ($where as $index => $item) {
            if (strstr($item[0], 'max')) {
                $k = str_replace("_max", "", $item[0]);
                $ar[] = [$k, '<=', $item[2]];
                continue;
            } elseif (strstr($item[0], 'min')) {
                $k = str_replace("_min", "", $item[0]);
                $ar[] = [$k, '>=', $item[2]];
                continue;
            } elseif ($item[0] == 'user_id' && $item[2] == '0') {
                $ar[] = [$item[0], '<>', 'null'];
            } else {
                $ar[] = $item;
            }

        };

        return $ar;

//        $where_array = array();
//        foreach ($where as $index => $value) {
//            $where_array[$value[0]] = $value;
//            if (strstr($value[0], 'max') || strstr($value[0], 'min')) {
//            }
//        };
//        $ar = array();
//        foreach ($where_array as $key => $value) {
//            if (strstr($value[0], 'max') || strstr($value[0], 'min')) {
//                if (strstr($value[0], 'max')) {
//                    $k = str_replace("_max", "", $key);
//                    if (array_key_exists($k, $ar)) {
//                        $ar[$k] = [$k, 'between', [intval($ar[$k][2][0]), intval($value[2])]];
//
//                    } else {
//                        $ar[$k] = [$k, 'between', [0, intval($value[2])]];
//                    }
//
//                } else {
//                    $k = str_replace("_min", "", $key);
//                    $ar[$k] = [$k, 'between', [intval($value[2]), 100000000]];
//                }
//            }
//            else {
//                if ($value[0]=='user_id' && $value[2]=='0'){
//                    $ar[$key] = ['user_id', '<>', 'null'];
//                    continue;
//                }
//                $ar[$key] = $value;
//            }
//        };
//        dd($where);
//        $where = [];
//        foreach ($ar as $k => $v) {
//            $where[] = $v;
//        }
        return $where;
    }

    public function check_user_id($where)
    {
        $is_have_user_id = false;
        foreach ($where as $index => $value) {
            if ($value[0] == 'user_id') {
                $is_have_user_id = true;
                if ($value[2] == '0') {
                    unset($where[$index]);
                }
                continue;
            }
        }
        if ($is_have_user_id == false) {
            $where[] = ['user_id', '=', session('admin.id')];
        }
        $ar = [];
        foreach ($where as $index => $value) {
            $ar[] = $value;
        }

        return $ar;

    }

    // 请求对接接口
//    public function request_connect($url = 'https://zhusun.maiyuan.online/api/v1/pull_notify')
    public function request_connect($url = 'http://zhusun.test/api/v1/pull_notify')
    {
        try {
//            phpinfo();exit;
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $headers = array();
            $headers[] = 'token: *8*s*s*a*s*1*d*d*d*2';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            return $result;
        } catch (\Exception $e) {
            return $this->request_connect($url);
        }


    }


    // 对接竹笋接口
    public function connect_data($data)
    {
        try {
            //本地链接
            //$url = 'http://zhusun.test/api/v1/connect_data';
            //线上链接
            $url = 'https://zhusun.maiyuan.online/api/v1/connect_data';
            $this->client = new Client();
            $headers = [
                'token' => '*8*s*s*a*s*1*d*d*d*2',
            ];
            $response = $this->client->request('POST', $url, [
                'json' => $data, 'headers' => $headers, 'verify' => false
            ]);
            return $response->getBody()->getContents();

        } catch (\Exception $e) {
            return false;
        }


    }


    /**
     * http请求
     * @param $url
     * @param $data
     * @param string $method
     * @param string $contentType
     * @param bool $sslCheck
     * @param string $token
     * @param bool $needContentType
     * @return false|Response
     */
    public function curlHttp($url, $data, $method = 'POST', $contentType = 'Content-Type: application/json', $sslCheck = false, $token = '', $needContentType = true)
    {
        $method = strtoupper($method);
        // 设置header(json)
        if ($needContentType) {
            $contentType = $contentType == 'application/json' ? 'Content-Type: application/json' : $contentType;
            $headers = [$contentType];
        } else {
            $headers = [];
        }

        if ($token) {
            $headers[] = $token;
        }
        // 验证apikey是否通过
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // 验证ssl证书
        if ($sslCheck) {
            // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        }
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $resultInfo = json_decode($result, true);
        curl_close($curl);
        return !empty($resultInfo) ? $resultInfo : false;
    }

    /**
     * @return array 返回柱状数据
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTreeData()
    {
        $cate = new WebsiteCustomCategory();
        $all_data_parent = $cate->where('parent_id', '=', null)->select()->toArray();
        $all_data_childern = $cate->where('parent_id', '<>', null)->select()->toArray();
        $tree_array = [];
        foreach ($all_data_parent as $index => $item) {
            $tree_array[] = ['name' => $item['name'], 'value' => $item['id'], 'children' => []];
            foreach ($all_data_childern as $i => $childer_item) {
                if ($childer_item['parent_id'] == $item['id']) {
                    $tree_array[$index]['children'][] = ['name' => $childer_item['name'], 'value' => $childer_item['id'], 'parent_id' => $childer_item['parent_id']];
                }
            }
        }
        return $tree_array;

    }


    public function readExcelFile($filePath)
    {
        $fileParts = pathinfo($filePath);
        $filetype = strtolower($fileParts['extension']);
        if (strtolower($filetype) == 'xls') {
            $objReader = PHPExcel_IOFactory::createReader('Xls');
        } elseif (strtolower($filetype) == 'xlsx') {
            $objReader = PHPExcel_IOFactory::createReader('Xlsx');
        } elseif (strtolower($filetype) == 'csv') {
            $objReader = PHPExcel_IOFactory::createReader('Csv')
                ->setDelimiter(',')
                ->setInputEncoding('GBK') //处理csv读取中文异常问题
                ->setEnclosure('"');
        }

        $objReader->setReadDataOnly(true);
        $objPHPExcel = $objReader->load($filePath);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $highestRow = $objWorksheet->getHighestRow(); // 获取总行数
        $highestColumn = $objWorksheet->getHighestColumn();// 获取最大列号
        $excelResult = [];
        // 从第2行开始读取
        $startRow = 2;
        for ($j = $startRow; $j <= $highestRow; $j++) {
            // 从A列读取数据
            for ($k = 'A'; $k <= $highestColumn; $k++) {
                // 读取单元格
                $excelResult[$j][$k] = (string)$objWorksheet->getCell("$k$j")->getValue();
            }
        }
        return $excelResult;

    }


    /**
     * 飞书机器人卡片消息
     * @param string $user_id 飞书 id
     * @param string $username 用户名
     * @param int $num 数量
     * @param int $msg 发送的消息
     */
    public function flybook_send_card($user_id = '9f5e9c9e', $username = "张三", $msg = '')
    {
        $card_msg = [
            "config" => [
                "wide_screen_mode" => true
            ],
            "header" => [
                "title" => [
                    "tag" => "plain_text",
                    "content" => "🔊 转移标记人"
                ],
                "template" => "turquoise"//卡片标题的主题色
            ],
            "elements" => [
                [
                    "tag" => "div",
                    "fields" => [
                        [
                            "is_short" => false,
                            "text" => [
                                "tag" => "lark_md",
                                "content" => "**👨‍🚀 操作人：** {$username}"
                            ],
                        ],
                        [
                            "is_short" => false,
                            "text" => [
                                "tag" => "lark_md",
                                "content" => $msg
                            ]
                        ],
                    ]

                ]
                ,
                [
                    "tag" => "hr"
                ],
                [
                    "tag" => "div",
                    "fields" => [
                        [
                            "is_short" => true,
                            "text" => [
                                "tag" => "lark_md",
                                "content" => " **蜘蛛侠** - [SpiderMan](https://myspider-manager.maiyuan.online/admin/) 通知"

                            ]
                        ],
                    ]
                ],

            ]
        ];
        //消息   interactive     post   text
        app('feishu')->im->sendMessage($user_id, $card_msg, 'interactive');
//        $app->feishu->im->sendMessage($user_id,$card_msg,'interactive');
    }

    /**
     * @return false|mixed 获取领取人user
     */
    public function getUserList()
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://pms.maiyuan.online/api/user/list');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');

        $headers = array();
        $headers[] = 'User-Agent:Apipost client Runtime/+https://www.apipost.cn/';
        $headers[] = 'Accept:application/json';
        $headers[] = 'Api-Key:40d64b53-10b7-4a9a-afd0-7b92776360b5';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            return false;
        }
        curl_close($ch);
        $data = json_decode($result, true);
        return $data['data'];
    }

    /**
     * @return false|mixed 获取采集分类
     */
    public function get_gather_cate()
    {
        try {

            $url = 'https://pms.maiyuan.online/api/mdc/list';
            $headers = [
                'api-key' => '17db70ee-0aec-4985-ad5a-168748c03904',
                'Accept' => 'application/json',
            ];
            $response = $this->client->request('POST', $url, [
                'headers' => $headers, 'verify' => false
            ]);
            return $response->getBody()->getContents();

        } catch (\Exception $e) {
            return false;
        }


    }

    /**
     * @return false|mixed 获取采集分类name
     */
    public function get_gather_cate_name($ids)
    {
        try {

            $url = 'https://pms.maiyuan.online/api/mdc/getMdcNameList';
            $headers = [
                'api-key' => '17db70ee-0aec-4985-ad5a-168748c03904',
                'Accept' => 'application/json',
            ];
            $response = $this->client->request('POST', $url, [
                'json' => ['id' => $ids], 'headers' => $headers, 'verify' => false
            ]);
            return $response->getBody()->getContents();

        } catch (\Exception $e) {
            return false;
        }


    }


    /**
     * @return
     * 以post获取的数据生成where查询条件
     */
    public function build_search_where($post)
    {
        $where = [];
        foreach ($post as $key => $value) {
            if ($key == 'user_id') {
                $user_id_list = [];
                foreach (explode(',', $value) as $i => $v) {
                    if (strstr($v, 'user')) {
                        preg_match('/\d+/', $v, $arr);
                        $user_id_list[] = $arr[0];
                    }
                }
                if (!empty($user_id_list)) {
                    $where[] = ['user_id', 'in', $user_id_list];
                }
            } elseif ($key == 'time' || $key == 'jz_time' || $key == 'top_num' || $key == 'page' || strstr($value, '~')) {
                continue;
            } elseif (strstr($key, 'min')) {
                $k = str_replace("_min", "", $key);
                $where[] = [$k, '>=', $value];
            } elseif (strstr($key, 'max')) {
                $k = str_replace("_max", "", $key);
                $where[] = [$k, '<=', $value];
            } elseif (strstr($value, ',')) {
                $where[] = [$key, 'in', explode(',', $value)];
            } else {
                $where[] = [$key, '=', $value];
            }
        }
        return $where;
    }


    /**
     * @return
     * 生成tree
     */
    public function get_tree_data($data, $pid): array
    {
        $result = [];
        $auth_id = SystemAuth::where('title', '=', '组长')->find();
        foreach ($data as $k => $v) {
            if ($v['pid'] == $pid) {
                $value = 'group' . $v['id'];
                $name = $v['title'];
                $children = $this->get_tree_data($data, $v['id']);
                //查询分组下的user
                //只查不是最末级分组的user
                $gropu_id = $this->model_group->where('pid', '=', $v['id'])->find();
                if (empty($gropu_id)) {
                    $user_id_list = $this->model_admin->where('group_id', '=', $v['id'])->select()->toArray();
                    if (!empty($user_id_list)) {
                        foreach ($user_id_list as $index => $item) {
                            if (in_array($auth_id['id'], explode(',', $item['auth_ids']))) {
                                $children[] = ['value' => 'user' . $item['id'], 'name' => $item['username'] . '<i class="layui-icon layui-icon-friends" style="font-size: 4px; color: #1E9FFF;">负责人</i>'];
                            } else {
                                $children[] = ['value' => 'user' . $item['id'], 'name' => $item['username']];
                            }
                        }
                    }
                }
                if ($children == []) {
                    $result[] = ['name' => $name, 'value' => $value];
                } else {
                    $result[] = ['name' => $name, 'value' => $value, 'children' => $children];
                }
            }
        }
        return $result;
    }


    /**
     * @return
     * 生成tree添加一个无操作人
     */
    public function get_tree_data_add($data, $pid): array
    {
        $result = $this->get_tree_data($data, $pid);
        $result[] = ['name' => '无操作人', 'value' => 'user0'];
        return $result;
    }

    /**
     * @return
     * 构建where搜索条件  根据当前用户的where条件
     * 查询搜索的user_id 列表
     */
    public function get_group_user_list($where)
    {
        //如果查找到筛选user 返回user_id
        foreach ($where as $index => &$item) {
            if ($item[0] == 'user_id') {
                $user_id_list = [];
                foreach (explode(',', $item[2]) as $i => $v) {
                    if (strstr($v, 'user')) {
                        preg_match('/\d+/', $v, $arr);
                        $user_id_list[] = $arr[0];
                    }
                }
                return $user_id_list;
            }
        }

        //是组长查看自己组下所有用户信息
        if (auth('system.admin/index')) {
            $user_id = $this->get_group_user_id();
            if ($user_id) {
                return $user_id;
            }
        } else { //不是组长  查看自己信息
            $w[] = ['user_id', '=', session('admin.id')];
            return [session('admin.id')];
        }

    }

    /**
     * @param $where
     * @param $filter
     * @param $change_data
     * @return array
     * 替换where中的某个字段的搜索条件
     */
    public function change_where($where, $filter, $change_data)
    {
        $w = [];
        $is_t = false;
        foreach ($where as $index => &$item) {
            if ($item[0] == $filter) {
                $w[] = $change_data;
                $is_t = true;
                continue;
            }
            $w[] = $item;
        }
        $is_t ==false && $w[] = $change_data;
        return $w;
    }

}
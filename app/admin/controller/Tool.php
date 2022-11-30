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
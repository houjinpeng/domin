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
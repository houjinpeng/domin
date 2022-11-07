<?php

namespace app\admin\controller\website;

use app\admin\model\WebsiteSite;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use think\App;
use think\Exception;
use think\facade\Db;

//亚马逊
//排名上升最快前十产品          3
//评论上升最快前十产品          4
//特别关注排名上升最快前十产品   5

//etsy
//销量上升最快前十店铺          1
//评论上升最快前十商品          2
//收藏上升最快前十商品          6

//评论上升最快前十店铺          7
//店铺收藏上升最快前十店铺       8

//销量最多前十店铺              9
//评论最多的十个店铺            10
//收藏最多的十个店铺            11


class ResultWatchBoard
{
    public function __construct()
    {

        $this->model = new \app\admin\model\WebsiteSite();
        $task_id = $this->model->where('is_privacy', '=', '1')->field('task_id')->select()->toArray();
        $task_id_array = [];
        foreach ($task_id as $index => $item) {
            array_push($task_id_array, $item['task_id']);
        }
        $where[] = ['task_id', 'in', $task_id_array];
        $privacy_data = Db::connect('mongo')
            ->table('etsy')
            ->field(['标题', '店铺'])
            ->where($where)->select()->toArray();
        $this->privacy_data_array = [];
        foreach ($privacy_data as $index => $items) {
            array_push($this->privacy_data_array, $items['标题'] . '|' . $items['店铺']);
        }

        dd($this->privacy_data_array);
    }

    public function tool($table_name)
    {
//        链接mongo数据库 查找用户的task_id下的数据 进行去重
        $mongo = Db::connect('mongo')
            ->table($table_name)
            ->multiAggregate(['sum' => 1], ['店铺']);
        //找出出现次数大于1的数据 之后进行条件筛选 在程序中进行处理
        $title_list = [];
        foreach ($mongo as $index => $items) {
            if ($items['1_sum'] <> 1) {
                array_push($title_list, $items['店铺']);
            }
        }
        $where = [['店铺', 'in', $title_list], ['创建时间', '>=', date('Y-m-d', strtotime('-7 days'))]];
        $mongo_all_data = Db::connect('mongo')
            ->table($table_name)
            ->where($where)
            ->select();
        $result = array();
        foreach ($mongo_all_data as $data) {
            isset($result[$data['店铺']]) || $result[$data['店铺']] = array();
            $result[$data['店铺']][] = $data;
        }
        return $result;
    }

    //销量上升最快前十店铺   1
    public function etsy_sales($result)
    {
        $up_rank_list = [];
        //etsy店铺销量排行
        foreach ($result as $key => $items) {
            foreach ($items as $k => $v) {
                try {
                    if ($v['销量'] <> 0) {
                        $start_num = $v['销量'];
                        $up_rank_list[$key] = [end($items)['销量'] - $start_num, $items[0]['链接'], end($items)];
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }

            }
        }

        arsort($up_rank_list);
        $count = 1;
        foreach ($up_rank_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '1', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo $e . '<br>';
            }
        }
    }

    //etsy产品评论排行上升最快前十  2
    public function etsy_product_comment($result)
    {
        $up_rank_list = [];

        //etsy产品评论排行
        foreach ($result as $key => $items) {
            try {
                foreach ($items as $k => $v) {
                    if ($v['评论数'] <> 0) {
                        $start_num = $v['评论数'];
                        $up_comment_list[$key] = [end($items)['评论数'] - $start_num, $items[0]['链接'], end($items)];
                        break;
                    }
                }
            } catch (Exception $e) {
                continue;
            }

        }
        arsort($up_comment_list);
        $count = 1;
        foreach ($up_comment_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '2', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo 'etsy产品评论排行' . $e . ' <br>';
            }
        }


    }

    //etsy产品收藏上升最快前十   6
    public function etsy_product_collect($result)
    {
        $up_rank_list = [];

        //etsy产品收藏上升最快前十
        foreach ($result as $key => $items) {
            try {
                foreach ($items as $k => $v) {
                    if ($v['collect'] <> 0) {
                        $start_num = $v['collect'];
                        $up_collect_list[$key] = [end($items)['collect'] - $start_num, $items[0]['链接'], end($items)];
                        break;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }

        }
        arsort($up_collect_list);
        $count = 1;
        foreach ($up_collect_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '6', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo 'etsy产品收藏上升最快前十' . $e . ' <br>';
            }
        }


    }

    //etsy店铺评论上升最快前十   7
    public function etsy_store_comment($result)
    {

        //etsy店铺评论上升最快前十
        foreach ($result as $key => $items) {
            foreach ($items as $k => $v) {
                if (!array_key_exists('store_comments', $v)) {
                    unset($items[$k]);
                    continue;
                }
                try {
                    if ($v['store_comments'] <> 0) {
                        $start_num = $v['store_comments'];
                        $up_store_comment_list[$key] = [end($items)['store_comments'] - $start_num, end($items)['链接'], end($items)];
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }

            }
        }
        arsort($up_store_comment_list);

        $count = 1;
        foreach ($up_store_comment_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '7', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo $e;
            }
        }
        echo '数据储存完毕<br>';

    }

    //etsy店铺收藏最快前十店铺   8
    public function etsy_store_collect($result)
    {

        //店铺收藏最快前十店铺
        foreach ($result as $key => $items) {
            foreach ($items as $k => $v) {
                try {
                    if ($v['store_comments'] <> 0) {
                        $start_num = $v['store_comments'];
                        $up_store_comment_list[$key] = [end($items)['store_comments'] - $start_num, $items[0]['链接'], end($items)];
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

        }
        arsort($up_store_comment_list);
        echo '正在存储数据<br>';
        $count = 1;
        foreach ($up_store_comment_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '8', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo $e;
            }

        }
        echo '数据储存完毕<br>';

    }

    //etsy销量最多的十个店铺     9
    public function etsy_sales_stroe_more($result)
    {
        //销量最多的十个店铺
        foreach ($result as $key => $items) {
            try {
                $up_store_sales_list[$key] = [end($items)['销量'], end($items)['链接'], end($items)];
            } catch (\Exception $e) {
                continue;
            }
        }

        arsort($up_store_sales_list);
        echo '正在存储数据<br>';
        $count = 1;
        foreach ($up_store_sales_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '9', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo $e;
            }

        }
        echo '数据储存完毕<br>';


    }


    //etsy评论最多的十个店铺  10
    public function etsy_comment_stroe_more($result)
    {
        //销量最多的十个店铺
        foreach ($result as $key => $items) {
            try {
                $store_comment_list[$key] = [end($items)['store_comments'], end($items)['链接'], end($items)];
            } catch (\Exception $e) {
                continue;
            }
        }

        arsort($store_comment_list);
        $count = 1;
        foreach ($store_comment_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '10', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo $e;
            }

        }
        echo '数据储存完毕<br>';


    }

    //etsy收藏最多的十个店铺  11
    public function etsy_collect_stroe_more($result)
    {
        //销量最多的十个店铺
        foreach ($result as $key => $items) {
            try {
                $store_collect_list[$key] = [end($items)['nofollow'], end($items)['链接'], end($items)];
            } catch (\Exception $e) {
                continue;
            }
        }

        arsort($store_collect_list);
        echo '正在存储数据<br>';
        $count = 1;
        foreach ($store_collect_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('etsy_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '11', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            } catch (\Exception $e) {
                echo $e;
            }

        }
        echo '数据储存完毕<br>';


    }


    //亚马逊排名       3
    public function amazon_sale($result)
    {
        //amazon店铺销量排行
        foreach ($result as $key => $items) {
            foreach ($items as $k => $v) {
                try {
                    if ($v['销量'] <> 0) {
                        $start_num = $v['销量'];
                        $up_rank_list[$key] = [end($items)['销量'] - $start_num, $items[0]['链接'], end($items)];
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
        arsort($up_rank_list);
        $count = 1;
        foreach ($up_rank_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            $mongo_all_data = Db::connect('mongo')
                ->table('amazon_rank')
                ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '3', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
        }
    }

    //亚马逊产品评论    4
    public function amazon_product_comment($result)
    {
        //amazon产品评论排行
        foreach ($result as $key => $items) {
            foreach ($items as $k => $v) {
                try {
                    if ($v['评论数'] <> 0) {
                        $start_num = $v['评论数'];
                        $up_comment_list[$key] = [end($items)['评论数'] - $start_num, $items[0]['链接'], end($items)];
                        break;
                    }
                } catch (\Exception $e) {
                    continue;
                }

            }
        }
        arsort($up_comment_list);
        $count = 1;
        foreach ($up_comment_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            $mongo_all_data = Db::connect('mongo')
                ->table('amazon_rank')
                ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '4', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
        }
    }

    //亚马逊特别关注    5
    public function amazon_special()
    {

//        查询所有c_id为3的task_id
        $model = new \app\admin\model\WebsiteSite();
        $task_id_array = $model->where('cate_id', '=', 16)
            ->field('task_id')
            ->select()->toArray();
        $task_id_list = [];
        foreach ($task_id_array as $k => $item) {
            array_push($task_id_list, $item['task_id']);
        }
//        链接mongo数据库 查找用户的task_id下的数据 进行去重
        $mongo = Db::connect('mongo')
            ->table('亚马逊')
            ->where('task_id', 'in', $task_id_list)
            ->multiAggregate(['sum' => 1], ['标题']);
        //找出出现次数大于1的数据 之后进行条件筛选 在程序中进行处理
        $title_list = [];
        foreach ($mongo as $index => $items) {
            array_push($title_list, $items['标题']);
        }
        $where = [['标题', 'in', $title_list]];
        $mongo_all_data = Db::connect('mongo')
            ->table('亚马逊')
            ->where($where)
            ->select();
        $result = array();
        foreach ($mongo_all_data as $data) {
            isset($result[$data['标题']]) || $result[$data['标题']] = array();
            $result[$data['标题']][] = $data;
        }
        $up_rank_list = [];
        $up_comment_list = [];
        //amazon店铺销量排行
        foreach ($result as $key => $items) {
            foreach ($items as $k => $v) {
                if ($v['销量'] <> 0) {
                    $start_num = $v['销量'];
                    $up_rank_list[$key] = [end($items)['销量'] - $start_num, $items[0]['链接'], end($items)];
                    break;
                }
            }
        }
        arsort($up_rank_list);
        $count = 1;
        foreach ($up_rank_list as $k => $items) {
            if ($count > 15) {
                break;
            }
            if (in_array($items['标题'] . '|' . $items['店铺'], $this->privacy_data_array)) {
                continue;
            }
            $count += 1;
            try {
                $mongo_all_data = Db::connect('mongo')
                    ->table('amazon_rank')
                    ->save(array('title' => $k, 'rank' => $items[0], 'link' => $items[1], 'data' => $items[2], 'type' => '5', 'create_time' => date('Y-m-d H:i:s', strtotime('now'))));
            }catch (\Exception $e){
                continue;
            }
        }
    }


    //亚马逊 主入口
    public function amazon_index()
    {
        $result = $this->tool('亚马逊');
        $up_rank_list = [];
        $up_comment_list = [];
        //先清空表
        $mongo = Db::connect('mongo')
            ->table('amazon_rank')
            ->delete(true);
        $this->amazon_special();
        $this->amazon_sale($result);
        $this->amazon_product_comment($result);

        return 'ok';

    }

    //etsy 主入口
    public function etsy_index()
    {
        $result = $this->tool('etsy');
        echo '查询完毕<br>';

        //先清空表
        $mongo = Db::connect('mongo')
            ->table('etsy_rank')
            ->delete(true);

        $this->etsy_sales($result);
        $this->etsy_product_comment($result);
        $this->etsy_product_collect($result);
        $this->etsy_store_comment($result);
        $this->etsy_store_collect($result);
        $this->etsy_sales_stroe_more($result);
        $this->etsy_comment_stroe_more($result);
        $this->etsy_collect_stroe_more($result);

        return 'ok';

    }


    public function index()
    {
//        $this->amazon_index();
//        $this->etsy_index();
    }

}


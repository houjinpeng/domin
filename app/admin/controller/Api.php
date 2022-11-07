<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use think\App;

class Api extends AdminController
{
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new \app\admin\model\WebsiteDomainRecord();
        $this->model_google = new \app\admin\model\WebsiteGoogleRank();

    }

    public function set_store_status($store_id = null, $status = null)
    {
        if ($store_id == null) return 'error';
        if ($status == null) return 'error';
        if (!in_array($status, [1, 0])) return 'error';
        //http://spider.test/admin/api/close_store?store_id=1232131sadsadasda
        $this->model_google->where('store_id', '=', $store_id)->update(['status' => $status]);
        $this->model->where('store_id', '=', $store_id)->update(['status' => $status]);

        return 'success';


    }

    /**
     *给lz的关键词排名
     * @return string
     */
    public function getKeywordRank()
    {

        $keyword_data = [];
        $all_data = $this->model_google->where('parent_id', '=', null)->select()->toArray();
        foreach ($all_data as $index => $item) {
            $keyword_data[] = [
                'storeId' => $item['store_id'],
                'keyword' => $item['keyword'],
                'Google' => $item['google_rank_num'] == 1000000 ? 0 : $item['google_rank_num'],
                'Bing' => $item['bing_rank_num'] == 1000000 ? 0 : $item['bing_rank_num'],
                'Yahoo' => $item['yahoo_rank_num'] == 1000000 ? 0 : $item['yahoo_rank_num'],
                'Duckduckgo' => $item['duckduckgo_rank_num'] == 1000000 ? 0 : $item['duckduckgo_rank_num'],
            ];
        }
        $data = [
            'code' => 1,
            'keywordList' => $keyword_data,
            'msg' => ''
        ];
        return json($data);
    }

}
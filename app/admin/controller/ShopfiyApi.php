<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use think\App;

class ShopfiyApi extends AdminController
{


    function __construct($accessToken, $shop_url, $api_version = '2021-10'){
        $this->accessToken = $accessToken;
        $this->graphqlEndpoint = 'https://'.$shop_url.'/admin/api/'.$api_version.'/graphql.json';
    }

    //搜索产品
    public function getSearchProducts($query){
        $graphQL = <<<Query
                query {
                    products(first: 50, query: "title:*$query* OR sku:*$query*") {
                      edges {
                        node {
                          id
                          title
                          handle
                          onlineStoreUrl
                          featuredImage {
                              src
                          }
                        }
                      }
                    }
                }
Query;

        $data = $this->doRequest($graphQL);
        if($data){
            return $data['data']['products'];
        }
        return false;
    }



    public function doRequest($query){
        $client = new \GuzzleHttp\Client(['verify'=>false]);
        $response = $client->request('POST', $this->graphqlEndpoint, [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $this->accessToken,
            ],
            'json' => [
                'query' => $query
            ]
        ]);
        return json_decode($response->getBody()->getContents(), true);
    }



    public function save_comment($data){
        $client = new \GuzzleHttp\Client(['verify'=>false]);
        $response = $client->request('POST', "https://pg.easyapps.pro/api/Comment/process", [
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $this->accessToken,
            ],
            'json' => [
                'content' => "'".$data."'"
            ]
        ]);
        return json_decode($response->getBody()->getContents(), true);

    }

}
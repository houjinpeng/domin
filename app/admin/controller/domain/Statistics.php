<?php



namespace app\admin\controller\domain;


use app\admin\model\DomainSalse;
use app\admin\model\DomainStore;
use app\admin\model\SystemAdmin;
use app\admin\service\TriggerService;
use app\common\constants\AdminConstant;
use app\common\controller\AdminController;
use EasyAdmin\annotation\ControllerAnnotation;
use EasyAdmin\annotation\NodeAnotation;
use EasyAdmin\tool\CommonTool;
use jianyan\excel\Excel;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use think\App;
use think\db\Where;
use think\facade\Db;

/**
 * @ControllerAnnotation(title="统计")
 */
class Statistics extends AdminController
{

    use \app\admin\traits\Curd;

    protected $sort = [
        'id'   => 'asc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->model = new DomainSalse();

    }

    /**
     * @NodeAnotation(title="列表")
     */
    public function index()
    {
        if ($this->request->isAjax()) {

        }
        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="获取id排名")
     */
    public function get_sales_rank(){
        if ($this->request->isAjax()){


            $get = $this->request->get('fixture_date');
            $t = explode(' ~ ',$get);
            $where[] = ['fixture_date','>=',$t[0]];
            $where[] = ['fixture_date','<=',$t[1]];

            $list = $this->model
                ->where($where)
                ->field(['count(*) as count','store_id'])
                ->group('store_id')
                ->having('count>10')
                ->order('count','desc')
                ->select()->toArray();

            $data = ['code'=>1,
                'data'=>$list
                ];
            return json($data);
        }


        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="获取id销售金额排名")
     */
    public function get_sales_price_rank(){
        if ($this->request->isAjax()){


            $get = $this->request->get('fixture_date');
            $t = explode(' ~ ',$get);
            $where[] = ['fixture_date','>=',$t[0]];
            $where[] = ['fixture_date','<=',$t[1]];

            $list = $this->model
                ->where($where)
                ->field(['sum(price) as price','store_id'])
                ->group('store_id')
                ->having('price>10')
                ->order('price','desc')
                ->select()->toArray();

            $data = ['code'=>1,
                'data'=>$list
            ];
            return json($data);
        }


        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="获取每日销量走势图")
     */
    public function get_sales_zs(){
        if ($this->request->isAjax()){


            $get = $this->request->get('fixture_date');
            $t = explode(' ~ ',$get);
            $where[] = ['fixture_date','>=',$t[0]];
            $where[] = ['fixture_date','<=',$t[1]];

            $list = $this->model
                ->where($where)
                ->field(['count(*) as count','fixture_date'])
                ->group('fixture_date')
                ->order('fixture_date','asc')
                ->select()->toArray();

            $data = ['code'=>1,
                'data'=>$list
            ];
            return json($data);
        }


        return $this->fetch();
    }

    /**
     * @NodeAnotation(title="获取价格段位销量排名")
     */
    public function get_sales_price(){
        if ($this->request->isAjax()){

            $post = $this->request->post();
            $chart_data = [];
            $jg_list= explode("\n",$post['jg']);
            //默认区间
            if ($jg_list[0] == ''){
                $jg_list = ['0-100','101-200','201-300','301-400','401-500','501-600','601-700','701-800','801-900','901-1000','1000-1000000'];
            }


            foreach ($jg_list as $index=>$item){
                $where = [];
                $t = explode(' ~ ',$post['fixture_date']);
                $where[] = ['fixture_date','>=',$t[0]];
                $where[] = ['fixture_date','<=',$t[1]];

                $chart_data[$item] = 0;
                $m = explode('-',$item);
                $where[] = ['price','BETWEEN',$m];
                $chart_data[$item] = $this->model
                    ->where($where)
                    ->count();
            }


            $data = ['code'=>1,
                'data'=>$chart_data
            ];
            return json($data);
        }


        return $this->fetch();
    }


    /**
     * @NodeAnotation(title="获取属性占比")
     */
    public function attr_proportion(){
        if ($this->request->isAjax()){
            $post = $this->request->post();
            $t = explode(' ~ ',$post['fixture_date']);
            $where[] = ['fixture_date','>=',$t[0]];
            $where[] = ['fixture_date','<=',$t[1]];

            $attr_list = explode(',',$post['attr']);
            $data_array = [];
            foreach ($attr_list as $index=>$item){
                //SELECT * from ym_domain_sales where jj LIKE "%Bd%" and jj LIKE "%Sg%"
                $where1 = [];
                foreach (explode('+',$item) as $idx=>$vv){
                    $where1[] = ['jj','like','%'.$vv.'%'];

                }

                $count = $this->model->where($where)
                    ->where('jj','like','%%')
                    ->where($where1)->count();
                $data_array[] =['name'=>$item,'value'=>$count];
            }

            $data = ['code'=>1,
                'data'=>$data_array
            ];
            return json($data);


        }

    }


    /**
     * @NodeAnotation(title="获取域名排行")
     */
    public function get_ym_rank(){

        $get = $this->request->get();
        $page = isset($get['page'])?$get['page']:1;
        $limit = isset($get['limit'])?$get['limit']:20;
        $get = $this->request->get();
        list($page, $limit, $where) = $this->buildTableParames();
        $t = explode(' ~ ',$get['fixture_date']);
        $where[] = ['fixture_date','>=',$t[0]];
        $where[] = ['fixture_date','<=',$t[1]];
        $list = $this->model->field('ym,count(*) as count,price')
            ->where($where)
            ->order('count','desc')
            ->group('ym')
            ->having('count>1')
            ->page($page,$limit)
            ->select()->toArray();
        foreach ($list as &$item){
            $price_list = [];
            if ($item['count']>=2){
                $d = $this->model->where($where)->field('price')->where('ym','=',$item['ym'])
                    ->order('id','asc')
                    ->select()->toArray();
                foreach ($d as $vv){
                    $price_list[] = $vv['price'];
                }
                $item['price'] =$price_list;
            }else{
                $item['price'] = [$item['price']];
            }
        }

        $count = $this->model->field('ym,count(*) as count,price')
            ->where($where)
            ->group('ym')
            ->having('count>1')
            ->count();


        $data = ['code'=>0,
            'count'=>$count,
            'data'=>$list,
        ];
        return json($data);
    }


}

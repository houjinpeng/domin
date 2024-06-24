<?php
declare (strict_types = 1);

namespace app\command;

use app\admin\controller\JvMing;
use app\admin\model\DomainReserveBatch;
use app\admin\model\DomainReserveDomain;
use app\admin\model\NodWarehouse;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\helper\Str;

class YudingYm extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('yuding_ym')
            ->setDescription('the yuding_ym command');
    }

    protected function execute(Input $input, Output $output)
    {
        $domain_model = new DomainReserveDomain();
        while (1){

            sleep(1);
            $now_time = date('Y-m-d H:i:s');
            print ("{$now_time}\n");
            //批次列表
            $batch_list = DomainReserveBatch::where('status','=',0)->select()->toArray();
            foreach ($batch_list as $item){
                //判断要运行时间是否是大于现在的时间

                if ($item['start_script_time'] > $now_time) continue;

                //修改为运行中
                DomainReserveBatch::where('id','=',$item['id'])->update(['status'=>1]);
                print ("开始运行\n");
                //所有域名信息
                $domain_list = $domain_model->where('batch_id','=',$item['id'])->where('status','=',0)
                    ->select()->column('ym');
                if (empty($domain_list)){
                    DomainReserveBatch::where('id','=',$item['id'])->update(['status'=>2]);
                    continue;
                }

                //获取账户
                $warehouse_row = NodWarehouse::where('id','=',$item['warehouse_id'])->find();
                if (empty($warehouse_row)){
                    DomainReserveBatch::where('id','=',$item['id'])->update(['status'=>3]);
                    continue;
                }
                $jm_api = new JvMing($warehouse_row['name'],$warehouse_row['password'],$warehouse_row['cookie']);


                $result = $jm_api->add_ym(ymlb: join(',',$domain_list),ydfs:$item['fs'],bzsm: $item['remark']);

                if (isset($result['ok'])){
                    //修改成功
                    foreach ($result['ok'] as $r){
                        print("域名：".$r['ym']."  通道id：".$item['fs'] ." 添加成功\n");
                        $domain_model->where('ym','=',$r['ym'])->where('batch_id','=',$item['id'])
                            ->update(['status'=>2]);

                    }
                }


                $again_add_list = [];
                if (isset($result['err'])){
                    //修改成功
                    foreach ($result['err'] as $r){
                        $again_add_list[] = $r['ym'];
                        print("域名：".$r['ym']."  通道id：".$item['fs'] ."  添加失败:".$r['msg']."\n");

                        $domain_model->where('ym','=',$r['ym'])->where('batch_id','=',$item['id'])
                            ->update(['status'=>3,'error_msg'=>$r['msg']]);

                    }
                }

                $fs_v = [
                    '8'=>'通道1',
                    '13'=>'通道2',
                    '6'=>'通道3',
                    '9'=>'通道4',
                    '5'=>'通道5',
                    '12'=>'通道6',
                    '4'=>'通道7',
                    '3'=>'通道8',
                    '14'=>'通道9',
                    '16'=>'通道10',
                    '17'=>'通道11',
                    '18'=>'通道12',
                ];
                $fs_list = [18,17,16,14,3,4,12,5,9,6,13,8];
                //查询当前的通道下标
                //遍历数据  直到全部完事或者通道全部完事  如果没有了 直接返回
                $custom_index = array_search($item['fs'],$fs_list);
                if ($custom_index === false){
                    //修改为运行中2
                    DomainReserveBatch::where('id','=',$item['id'])->update(['status'=>2]);
                    dd(array_search($item['fs'],$fs_list),$item['fs'],$fs_list);
                    continue;
                }
                //如果没有再次添加的  直接跳过下发方法
                if ($again_add_list == []) {
                    //修改为运行中2
                    DomainReserveBatch::where('id','=',$item['id'])->update(['status'=>2]);
                    continue;
                }
                //一直提交  直到没有
                while (1){
                    $custom_index += 1;
                    //判断总长度和下标是否相等 相等就返回
                    if (count($fs_list) == $custom_index) break;

                    $result = $jm_api->add_ym(ymlb: join(',',$again_add_list),ydfs:$fs_list[$custom_index],bzsm: $item['remark']);
                    if (isset($result['ok'])){
                        //修改成功
                        foreach ($result['ok'] as $r){
                            print("域名：".$r['ym']."  通道id：".$fs_list[$custom_index] ."  添加成功\n");
                            $domain_model->where('ym','=',$r['ym'])->where('batch_id','=',$item['id'])
                                ->update(['status'=>2,'fs'=>$fs_list[$custom_index],'error_msg'=>'']);


                        }
                    }
                    $again_add_list = [];
                    if (isset($result['err'])){
                        //修改成功
                        foreach ($result['err'] as $r){
                            print("域名：".$r['ym']."  通道id：".$fs_list[$custom_index] ."  添加失败:".$r['msg']."\n");
                            $domain_model->where('ym','=',$r['ym'])->where('batch_id','=',$item['id'])
                                ->update(['status'=>3,'error_msg'=>$r['msg']]);
                            $again_add_list[] = $r['ym'];
                        }
                    }


                    print ("===============================\n");
                }

                //修改为运行中2
                DomainReserveBatch::where('id','=',$item['id'])->update(['status'=>2]);
            }




        }


    }
}

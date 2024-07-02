<?php
declare (strict_types = 1);

namespace app\command;
use AlibabaCloud\SDK\Domain\V20180208\Domain;
use Darabonba\OpenApi\Models\Config;
use AlibabaCloud\SDK\Domain\V20180208\Models\ReserveDomainRequest;


use app\admin\model\DomainReserveBatch;
use app\admin\model\DomainReserveDomain;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\helper\Str;

class YudingYmAliyun extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('yuding_ym_aliyun')
            ->addArgument('batch_id', Argument::OPTIONAL, "batch_id")
            ->setDescription('the yuding_ym_aliyun command');
    }


    public function get_client(){
        $config = new Config([
            // 必填，请确保代码运行环境设置了环境变量 ALIBABA_CLOUD_ACCESS_KEY_ID。
            "accessKeyId" => '',
            // 必填，请确保代码运行环境设置了环境变量 ALIBABA_CLOUD_ACCESS_KEY_SECRET。
            "accessKeySecret" => ''
        ]);
        // Endpoint 请参考 https://api.aliyun.com/product/Domain
        $config->endpoint = "domain.aliyuncs.com";
        return new Domain($config);

    }


    public function do_request($client,$ym,$count=0){
        try {
            $request = new ReserveDomainRequest(['channels'=>'','domainName'=>$ym]);
            $result = $client->reserveDomain($request);
        }catch (\Exception $e){
            if ($count > 5){
                return [];
            }
            return  $this->do_request($client,$ym,$count+1);
        }
        return $result;

    }

    protected function execute(Input $input, Output $output)
    {
        $batch_id = trim($input->getArgument('batch_id'));
        //如果没有批次id 过滤
        if (!$batch_id){
            print("没有批次id\n");
            return '';
        }
        $client = $this->get_client();
        $domain_model = new DomainReserveDomain();

        //修改没有备案的数据状态为3
        $domain_model->where('batch_id','=',$batch_id)
            ->where('status','=',0)
            ->where('is_have_beian','=',0)
            ->update(['status'=>3,'error_msg'=>'没有备案']);
        print($batch_id." 提交批次\n");
        //所有域名信息
        $domain_list = $domain_model->where('batch_id','=',$batch_id)->where('status','=',0)
            ->where('is_have_beian','=',1)
            ->select()->column('ym');
        if (empty($domain_list)){
            DomainReserveBatch::where('id','=',$batch_id)->update(['status'=>2]);
            print("没有要提交的域名\n");
            return true;
        }

        foreach ($domain_list as $domain){
            $result = $this->do_request($client,$domain);
            print('域名：'.$domain.' '.json_encode($result)."\n");
            if ($result){
                $domain_model->where('ym','=',$domain)->where('batch_id','=',$batch_id)
                    ->update(['status'=>2]);
            }else{
                $domain_model->where('ym','=',$domain)->where('batch_id','=',$batch_id)
                    ->update(['status'=>3,'error_msg'=>'抢注失败']);
            }

        }


        //修改为完成2
        DomainReserveBatch::where('id','=',$batch_id)->update(['status'=>2]);

    }
}

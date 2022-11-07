<?php

// +----------------------------------------------------------------------
// | EasyAdmin
// +----------------------------------------------------------------------
// | PHP交流群: 763822524
// +----------------------------------------------------------------------
// | 开源协议  https://mit-license.org 
// +----------------------------------------------------------------------
// | github开源项目：https://github.com/zhongshaofa/EasyAdmin
// +----------------------------------------------------------------------

namespace app\admin\controller;


use app\admin\model\SystemAdmin;
use app\common\controller\AdminController;
use think\App;
use think\captcha\facade\Captcha;
use think\facade\Env;
use app\admin\controller\Tool;

/**
 * Class Login
 * @package app\admin\controller
 */
class Login extends AdminController
{

    private $f_appid='cli_a2972ac6d6b8d00e';
    private $f_appSecret='2MWH42bJcMMcP6IoUOeWWd4tTqmFf1gp';
//    private $f_redirect_uri='http://spider.test/admin/login/index';
    private $f_redirect_uri='https://myspider-manager.maiyuan.online/admin/login/index';


    /**
     * 初始化方法
     */
    public function initialize()
    {
        parent::initialize();
        $action = $this->request->action();
        if (!empty(session('admin')) && !in_array($action, ['out'])) {
            $adminModuleName = config('app.admin_alias_name');
            $this->success('已登录，无需再次登录', [], __url("@{$adminModuleName}"));
        }
    }


    //飞书授权登录  start
    public function index()
    {
        //$this->error('蜘蛛侠将在中秋佳节乘坐SpiderMan-Line号航班飞往日本，飞行期间禁止登陆，落地后机长会广播通知！',wait: 1000000);
        $captcha = Env::get('easyadmin.captcha', 1);
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $rule = [
                'username|用户名'      => 'require',
                'password|密码'       => 'require',
                'keep_login|是否保持登录' => 'require',
            ];
            $captcha == 1 && $rule['captcha|验证码'] = 'require|captcha';
            $this->validate($post, $rule);
            $admin = SystemAdmin::where(['username' => $post['username']])->find();
            if (empty($admin)) {
                $this->error('用户不存在');
            }
            if (password($post['password']) != $admin->password) {
                $this->error('密码输入有误');
            }
            if ($admin->status == 0) {
                $this->error('账号已被禁用');
            }
            $admin->login_num += 1;
            $admin->save();
            $admin = $admin->toArray();
            unset($admin['password']);
            $admin['expire_time'] = $post['keep_login'] == 1 ? true : time() + 7200;
            session('admin', $admin);
            $this->success('登录成功');
        }
        $param=input('param.');
        if(isset($param['code']) && isset($param['state'])){
             //获取access_token
            $access_token=$this->access_token($param['code']);
            if(!empty($access_token['msg'])){
                $this->error($access_token['msg']);
            }
             //获取用户信息
            $data=$this->fgetuser($access_token);
            if(empty($data['sub'])){
                $this->error('获取用户信息失败');
            }
            if($data){
                $user = $this->fvloglogin($data);
                switch ($user['code']){
                    case 0:
                        $this->error($user['data']);
                        break;
                    case 2: //新用户
//                        dd($user);
                        $this->assign(['bind'=>'bind','fs_user_id'=>$user['data']['user_id']]);
                        break;
                    case 3: //老用户
                        $user['data']['expire_time'] =  time() + 7200;
                        session('admin', $user['data']);
                        $this->success('登录成功');
                        break;
                    default:

                        break;
                }
            }
        }
        $this->assign('captcha', $captcha);
        $this->assign('appid', $this->f_appid);
        $this->assign('appSecret', $this->f_appSecret);
        $this->assign('redirect_uri', $this->f_redirect_uri);
        return $this->fetch();
    }



    /**
     * 获取飞书用户信息
     * @return $userid
     *
     */
    protected function fgetuser($access_token){
        $url = "https://passport.feishu.cn/suite/passport/oauth/userinfo";

        $header  = array(
            "Authorization:Bearer ".$access_token,
        );

        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $tmpInfo = curl_exec($curl);
        //关闭URL请求
        curl_close($curl);
        return   json_decode($tmpInfo,true);
    }


    /**
     * access_token
     * @return $access_token
     */
    protected function access_token($code) {
        $postBody = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->f_appid,
            'client_secret' => $this->f_appSecret,
            'code' => $code,
            'redirect_uri' => $this->f_redirect_uri,
        ];

        $tool = new Tool();
        $data = $tool->curlHttp(
            'https://passport.feishu.cn/suite/passport/oauth/token',
            $postBody,
            'POST',
            "application: x-www-form-urlencoded",
            true
        );
        if (!$data['access_token']){
            return '';
        }
        return $data['access_token'];
    }

    /**
     * 扫码后登录
     * @return $data
     */
    private function fvloglogin($info) {
        $user_id = $info['user_id'];
        $datas =  SystemAdmin::where(['fs_user_id' => $user_id])->find();
        if(!empty($datas)){
            //老用户,登陆成功
            return ['code'=>3,'data'=>$datas];
        }else{
            //新用户,去绑定吧
            return ['code'=>2,'data'=>$info];
        }
    }

    /**
     * 飞书用户绑定
     * @return mixed
     */
    public function bind()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();

            if(empty($post['username'])){
                $this->error('输入用户名');
            }
            if(empty($post['password'])){
                $this->error('输入密码');
            }
            $admin = SystemAdmin::where(['username' => $post['username']])->find();
            if(!empty($admin['fs_user_id'])){
                $this->error('该账号已被绑定');
            }
            if (empty($admin)) {
                $this->error('用户不存在');
            }
            if (password($post['password']) != $admin->password) {
                $this->error('密码输入有误');
            }
            if ($admin->status == 0) {
                $this->error('账号已被禁用');
            }

            try{
                SystemAdmin::where(['username' => $post['username']])->update(['fs_user_id'=>$post['fs_user_id']]);
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }
            $admin['expire_time'] =  time() + 7200;
            session('admin', $admin);
            $this->redirect('admin');
            $this->success('绑定成功');
        }
    }

    //飞书授权 end



    /**
     * 返回当前的毫秒时间戳
     * @return $msectime
     */
    protected function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }



    /**
     * gettoken
     * @return $access_token
     */
    protected function gettoken($appkey = 'dingheltlq6mj16h6mvs', $appsecret = '_19iT_8T2V5QBLr1vTYtUP0BPKniGmkEb49CZSbVtfEQnVjLP5YWKFLNtyUVcpTb') {
        if(empty(cache('ding_access_token'))){
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, "https://oapi.dingtalk.com/gettoken?appkey={$appkey}&appsecret={$appsecret}");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $data = curl_exec($curl);
            $data = json_decode($data, true);
            if($data['errmsg'] == 'ok'){
                $access_token = $data['access_token'];
                cache('ding_access_token',$access_token,7100);
                return cache('ding_access_token');
            }
        }else{
            return cache('ding_access_token');
        }

    }


    /**
     * 根据unionid获取用户userid
     * @return $userid
     */
    protected function getbyunionid($info) {
        $unionid = $info['user_info']['unionid'];
        $postBody    = ['unionid'=>$unionid];
        $token = $this->gettoken();
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://oapi.dingtalk.com/topapi/user/getbyunionid?access_token={$token}");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postBody);//设置请求体
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        $data = curl_exec($curl);
        $data = json_decode($data, true);
        if($data['errmsg'] == 'ok'){
            return ['code'=>1,'data'=>$data['result']['userid']];
        }else{
            return ['code'=>0,'data'=>$info['user_info']['nick']. '获取unionid失败:'.$data['errmsg']];
        }
        return ['code'=>0,'data'=>'获取unionid失败'];
    }

    /**
     * 根据userid获取用户详情
     * @return $data
     */
    protected function getUser($userid) {
        $token = $this->gettoken();
        $postBody = [
            "language" => "zh_CN",
            "userid" => $userid
        ];
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://oapi.dingtalk.com/topapi/v2/user/get?access_token={$token}");
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postBody);//设置请求体
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');//使用一个自定义的请求信息来代替"GET"或"HEAD"作为HTTP请求。(这个加不加没啥影响)
        $data = curl_exec($curl);
        $data = json_decode($data, true);
        return $data;
    }

    /**
     * 扫码后登录
     * @return $data
     */
    private function vloglogin($info) {
        $unionid = $info['user_info']['unionid'];
        $datas =  SystemAdmin::where(['unionid' => $unionid])->find();
        if(!empty($datas)){
            //老用户,登陆成功
            return ['code'=>3,'data'=>$datas];
        }else{
            $userinfo = $this->getbyunionid($info);
            if(!empty($userinfo['code'])){
                $userData = $this->getUser($userinfo['data']);
            }else{
                return ['code'=>0,'data'=>$userinfo['data']];
            }
            if($userData['errmsg'] == 'ok'){
                $user_name = $userData['result']['name'];
                $user_title = $userData['result']['title'];
                $user_unionid = $userData['result']['unionid'];
                $user_userid = $userData['result']['userid'];
                    //新用户,去绑定吧
                    return ['code'=>2,'data'=>$userData['result']];
            }else{
                return ['code'=>0,'data'=>'获取用户详情失败:'.$userData['errmsg']];
            }
        }
    }

    /**
     * 用户绑定
     * @return mixed
     */
    public function dingdingbind()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if(empty($post['username'])){
                $this->error('输入用户名');
            }
            if(empty($post['password'])){
                $this->error('输入密码');
            }
            if(empty($post['unid'])){
                $this->error('凭证不存在');
            }else{
                if(empty(cache($post['unid']))){
                    $this->error('凭证失效,授权后请在4分钟内绑定');
                }
            }
            $admin = SystemAdmin::where(['username' => $post['username']])->find();
            if(!empty($admin['unionid'])){
                $this->error('该账号已被绑定');
            }
            if (empty($admin)) {
                $this->error('用户不存在');
            }
            if (password($post['password']) != $admin->password) {
                $this->error('密码输入有误');
            }
            if ($admin->status == 0) {
                $this->error('账号已被禁用');
            }
            try{
                SystemAdmin::where(['username' => $post['username']])->update(['unionid'=>$post['unid']]);
            }catch (\Exception $e){
                $this->error($e->getMessage());
            }
            $admin['expire_time'] =  time() + 7200;
            session('admin', $admin);
            $this->success('绑定成功');
        }
    }
    //dingding  end

    /**
     * 用户退出
     * @return mixed
     */
    public function out()
    {
        session('admin', null);
        if (session('admin')==null){
            $s = '啦';
        }
        $this->success('退出登录成功'.$s);
    }

    /**
     * 验证码
     * @return \think\Response
     */
    public function captcha()
    {
        return Captcha::create();
    }
}

<?php
namespace app\admin\controller;
use app\admin\model\UserType;
use think\Controller;
use think\Db;
use org\Verify;

class Index extends Common
{
    public function index()
    {   
        $version = include_once(ROOT_PATH.'version.php');
        config($version);
        $db = DB::name('browse');
        $browse['d'] = $db->where("date_format(from_unixtime(time),'%Y-%m-%d') = date_format(CURDATE(),'%Y-%m-%d')")->count();//今日
        $browse['w'] = $db->where("YEARWEEK(date_format(from_unixtime(time),'%Y-%m-%d')) = YEARWEEK(now())")->count();//本周
        $browse['m'] = $db->where("date_format(from_unixtime(time),'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m' )")->count();//本月
        $browse['a'] = $db->count();//全部

        $sys_mysql = db()->query('SELECT VERSION();');
        $sys_mysql = is_array($sys_mysql) ? $sys_mysql[0]['VERSION()'] : '';

	    $this->cloud = new \com\Cloud(config('cloud.identifier'));
	    $html_status = @file_get_contents("http://cms.api.yunucms.com/check");
        $html_status = $html_status == "SUCCESS" ? 1 : 0;
        $cloudstr = "<font style='color:#000;'>通信异常</font>";
        $rankurl = "";
        if ($html_status) {
        	$issq = false;
            if (config('cloud.identifier')) {
                $issq = config('cloud.grant') ? true : false;
            }
            if (!$issq) {
            	$cloudstr = "<br>温馨提示：您当前域名 <font style='color:#000;'>未认证</font> ，如果已购买授权，请前往 <a href='".url('upgrade/index')."' style='color:#000'>绑定</a>，如果未购买授权，请前往 <a href='http://www.yunucms.com/buy/index.html' target='_blank' style='color:#000'>购买</a>";
            }else{
            	$cloudstr = "<font style='color:#000;'>已认证</font>";
            }
            $this->cloud->record(config('sys.site_title'), config('yunucms.version'))->api('recordapi');


            $rankurldata = send_post($this->cloud->yunapiUrl().'/getrankurl', ['domain'=>config('sys.site_levelurl')]);
            $rankurldata = $rankurldata ? json_decode($rankurldata, true) : null;
            if ($rankurldata) {
                $rankurl = $rankurldata['state'] == 1 ? $rankurldata['data'] : $rankurl;
            }
        }

        $this->assign([
            'diyform_count' => DB::name('formcon')->where(['look'=>0])->count(),
            'sys_os' => PHP_OS,//操作系统
            'sys_ser' => $_SERVER["SERVER_SOFTWARE"],//服务器软件
            'sys_mysql' => $sys_mysql,//mysql版本
            'sys_upfile' => ini_get('file_uploads') ? ini_get('upload_max_filesize') : '不支持',//上传文件大小
            'cloudstr' => $cloudstr,
            'rankurl' => $rankurl,
            'browse' => $browse
        ]);
        return $this->fetch();
    }

    public function login()
    {
        if (!isset($_COOKIE["isloginurl"]) || $_COOKIE["isloginurl"] !== 'yunucms') {                   
            abort(404);
        }
        return $this->fetch('login');
    }

    public function autologin()
    {
        header('Content-Type: text/html; charset=utf-8');
        if (!config('sys.api_autologin')) {
            echo "ERRPR：请在CMS后台开启一键登录"; exit();
        }
        $encrypt = input('encrypt');
        $redirecturl = input('redirecturl') ? input('redirecturl') : 'index/index';
        $html = send_post("http://control.yunucms.com/Index/Api/cms_login_check", ['encrypt'=>$encrypt,'host'=>config('sys.site_url')]);
        if ($html) {
            $html = json_decode($html, true);

            if ($html['result'] == 1) {

                $db = Db::name('admin');

                if (input('username')) {
                    $hasUser = $db->where(['username'=>input('username')])->find();
                }else{
                    $hasUser = $db->where(['groupid'=>1])->find();
                }
                if(empty($hasUser)){
                    return json(['code' => -1, 'data' => '', 'msg' => 'No account exists']);
                }

                if(1 != $hasUser['status']){
                    return json(['code' => -6, 'data' => '', 'msg' => 'Account Banned']);
                }
                $user = new UserType();
                $info = $user->getRoleInfo($hasUser['groupid']);
                session('admin_username', $hasUser['username']);
                session('admin_uid', $hasUser['id']);
                session('groupid', $hasUser['groupid']);
                session('rolename', $info['title']);
                session('rule', $info['rules']);
                session('name', $info['name']);

                session('last_login_ip', $hasUser['last_login_ip']);
                session('last_login_time', date('Y-m-d H:i:s', $hasUser['last_login_time']));
        
                //更新管理员状态
                $param = [
                    'loginnum' => $hasUser['loginnum'] + 1,
                    'last_login_ip' => request()->ip(),
                    'last_login_time' => time()
                ];

                $db->where(['id'=>$hasUser['id']])->update($param);
                writelog($hasUser['id'], session('admin_username'), '用户【'.session('admin_username').'】自动登录成功', 1);
                $this->redirect(url($redirecturl));
            }
        }else{
            echo "ERRPR：关联请求错误，请联系管理员"; exit();
        }
    }
    //清除缓存
    public function cache()
    {
        $cachetype = input("param.cachetype");
        if ($cachetype == 'html') {
            @unlink("./index.html");
            if(is_dir(RUNTIME_PATH.'list'.DS)){
                dir_del(RUNTIME_PATH.'list'.DS);
            }
            if(is_dir(RUNTIME_PATH.'content'.DS)){
                dir_del(RUNTIME_PATH.'content'.DS);
            }
            if(is_dir(RUNTIME_PATH.'area'.DS)){
                dir_del(RUNTIME_PATH.'area'.DS);
            }
        }else{
            if(is_dir(RUNTIME_PATH.'cache'.DS)){
                dir_del(RUNTIME_PATH.'cache'.DS);
            }
            if(is_dir(RUNTIME_PATH.'log'.DS)){
                dir_del(RUNTIME_PATH.'log'.DS);
            }
            if(is_dir(RUNTIME_PATH.'temp'.DS)){
                dir_del(RUNTIME_PATH.'temp'.DS);
            }
        }
        return json(['msg' => '清除缓存成功']);
    }
    //缓存体积
    public function cachesize()
    {
        $size = 0;
        $size += dirsize(RUNTIME_PATH.'list'.DS);
        $size += dirsize(RUNTIME_PATH.'content'.DS);
        $size += dirsize(RUNTIME_PATH.'area'.DS);
        return json(['msg' => get_byte($size)]);
    }

    //登录操作
    public function doLogin()
    {
        if (request()->isAjax()) {
            $username = input("param.username");
            $password = input("param.password");

            if (config('verify_type') == 1) {
                $code = input("param.code");
            }
            $db = Db::name('admin');
            $result = $this->validate(compact('username', 'password'), 'AdminValidate');
            if(true !== $result){
                return json(['code' => -5, 'data' => '', 'msg' => $result]);
            }

            $hasUser = $db->where(['username'=>$username])->find();
            if(empty($hasUser)){
                return json(['code' => -1, 'data' => '', 'msg' => '管理员不存在']);
            }

            if(md5(md5($password).config('auth_key')) != $hasUser['password']){
                writelog($hasUser['id'],$username,'用户【'.$username.'】登录失败：密码错误',2);
                return json(['code' => -2, 'data' => '', 'msg' => '账号或密码错误']);
            }

            if(1 != $hasUser['status']){
                writelog($hasUser['id'],$username,'用户【'.$username.'】登录失败：该账号被禁用',2);
                return json(['code' => -6, 'data' => '', 'msg' => '该账号被禁用']);
            }

            //获取该管理员的角色信息
            $user = new UserType();
            $info = $user->getRoleInfo($hasUser['groupid']);
            session('admin_username', $username);
            session('admin_uid', $hasUser['id']);
            session('groupid', $hasUser['groupid']);
            session('rolename', $info['title']);
            session('rule', $info['rules']);
            session('name', $info['name']);

            session('last_login_ip', $hasUser['last_login_ip']);
            session('last_login_time', date('Y-m-d H:i:s', $hasUser['last_login_time']));
      
            //更新管理员状态
            $param = [
                'loginnum' => $hasUser['loginnum'] + 1,
                'last_login_ip' => request()->ip(),
                'last_login_time' => time()
            ];
            setcookie("isloginurl", "");
            $db->where(['id'=>$hasUser['id']])->update($param);
            writelog($hasUser['id'], session('admin_username'), '用户【'.session('admin_username').'】登录成功', 1);
            return json(['code' => 1, 'data' => url('index/index'), 'msg' => '登录成功！']);
        }
    }
    public function dodellog()
    {
        $logtime = input('param.logtime') ? input('param.logtime') : 2;
        if ($logtime == 1) {
            $where = ['time'=>['LT', strtotime("- 6 month")]];
        }
        if ($logtime == 2) {
            $where = ['time'=>['LT', strtotime("- 3 month")]];
        }
        if ($logtime == 3) {
            $where = ['time'=>['LT', strtotime("- 1 month")]];
        }
        if ($logtime == 4) {
            $where = ['time'=>['GT', 0]];
        }
        $flag = Db::name('browse')->where($where)->delete();
        return json(['code' => 1, 'data' => $flag['data'], 'msg' => '已处理完成']);
    }
    //退出操作
    public function loginOut()
    {
        session(null);
        return json(array('code'=>1));
    }
}

<?php
namespace app\admin\controller;
use think\Controller;
use app\admin\model\Node;
use app\admin\model\UserType;
use app\admin\model\UserModel;

class Common extends Controller{

 	public function _initialize()
    {
    	error_reporting(-1);
        
        $module     = strtolower(request()->module());
        $controller = strtolower(request()->controller());
        $action     = strtolower(request()->action());
        $url        = $module."/".$controller."/".$action;

        $noauthurl = [
            'admin/index/autologin',
            'admin/index/login',
            'admin/index/dologin',
            'admin/index/loginout',
            'admin/index/cache',  
        ];
        $version = include_once(ROOT_PATH.'version.php');
        config($version);
        $assign = [];
        if(!in_array($url, $noauthurl)){
            if(!session('admin_uid')){
                $this->redirect('/'.config('sys.login_url'));
            }
            $loginnoauthurl = [
                'admin/upload/upload',
                'admin/upload/uploadfile',
                'admin/upload/browsefile',
                'admin/content/sortcontent',
                'admin/content/movecategory',
                'admin/category/sortcategory',
                'admin/category/etitlecategory',
                'admin/content/baidu',
                'admin/content/xzh',
                'admin/content/media',
                'admin/content/stateall',
                'admin/content/mainurl',
                'admin/content/getarea',
                'admin/content/baiduqc',
                'admin/content/wxcaiji',
                'admin/content/statecontent',
                'admin/content/edittitle',
                'admin/content/copycontent',
                'admin/area/showurl',
                'admin/area/showkey',
                'admin/area/doopen',
                'admin/area/dozdseo',
                'admin/area/dodelseo',
                'admin/area/doseo',
                'admin/area/stateopen',
                'admin/area/stateurl',
                'admin/area/statecon',
                'admin/area/statetop',
                'admin/area/statearea',
                'admin/area/openbqs',
                'admin/area/sortarea',
                'admin/system/copyright',
                'admin/role/staterole',
                'admin/tagurl/allcontag',
                'admin/tpl/editfile',
                'admin/tpl/delfile',
                'admin/tpl/deldir',
                'admin/tpl/adddir',
                'admin/tpl/uploadfile',
                'admin/tpl/drop_upload',
                'admin/tpl/rename',
                'admin/tpl/paste',
                'admin/tpl/delete',
                'admin/tpl/copy_dir',
                'admin/tpl/create_crumbs',
                'admin/url/statichtmlcount',
                'admin/url/statichtml',
                'admin/url/maincontent',
            ];
            if(!in_array($url, $loginnoauthurl)){
                $auth = new \com\Auth();
                if(session('groupid') != 1){
                    if(!in_array($url, ['admin/index/index'])){
                        if(!$auth->check($url,session('admin_uid'))){
                            $this->error('抱歉，您没有操作权限');
                        }
                    }
                }

                $usermod = new UserModel();
                $hasUser = $usermod->getOneUser(session('admin_uid'));
                $user = new UserType();
                $roleinfo = $user->getRoleInfo($hasUser['groupid']);
                $node = new Node();
                $menu_list = $node->getMenu($roleinfo['rules']);
                $menu_child = $node->getMenuchild($url, $roleinfo['rules']);
                $position = $node->getPosition($url);
                $position['name'] = $position['name'] ? $position['name'] : "管理控制台";

                $assign = [
                    'username' => $hasUser['username'],
                    'menu' => $menu_list,
                    'menu_child' => $menu_child,
                    'rolename' => $roleinfo['title'],
                    'position' => $position,
                    'version' => config('yunucms.version'),
                ];
            }else{
                $assign = [
                    'position' => ['name'=>'管理员登陆'],
                    'version' => config('yunucms.version')
                ];
            }
        }else{
            $assign = [
                'position' => ['name'=>'管理员登陆'],
                'version' => config('yunucms.version')
            ];
        }
        
        $assign['isagent'] = config('cloud.agent') != 'cfcd208495d565ef66e7dff9f98764da' && config('cloud.agent') ? true : false;
        $assign['isagent'] = config('cloud.pid') ? true : $assign['isagent'];
        $strarr = reeturnsitecopy();
        $assign['copy_sysname'] = config('sys.copy_sysname') ? config('sys.copy_sysname') : $strarr['copy_sysname'];
        $assign['copy_name'] = config('sys.copy_name') ? config('sys.copy_name') : $strarr['copy_name'];
        $assign['copy_url'] = config('sys.copy_url') ? config('sys.copy_url') : $strarr['copy_url'];

        $sitehtml = "";
        if (session('authorization')) {
            $sitehtml .="<div class='fix_box'>";
            $sitehtml .="<div class='fixbg'></div>";
            $sitehtml .="<div class='lrbox'>";
            $sitehtml .="<div class='lrtext'>";
            $sitehtml .="<h4>侵 权 告 知 函</h4>";
            $sitehtml .="<p>尊敬的用户您好，贵公司网站（<span>".config('sys.site_levelurl')."</span>）正在使用本公司拥有合法著作权的软件<span>".$strarr['sitestr']."</span>（简称“<span>".$strarr['copy_sysname']."</span>”".$strarr['copy_num']."，但本公司的正版用户数据库中并未有贵公司的购买记录。    </p>";
            $sitehtml .="<p>本公司《使用协议》已载明，企业用户（泛指非自然人的团体，如企业、协会等组织机构）必须购买软件授权后方可正式建站使用，在未获得商业授权之前，企业用户<span>不得将本软件用于正式建站</span>。根据上述内容，贵公司作为企业用户，如使用本公司开发的<span>".$strarr['sitestr']."</span>，则应通过购买方式获得本公司商业授权后方可使用。但截至本通知发出之日，贵公司尚未向本公司购买<span>".$strarr['sitestr']."</span>。故，贵公司的上述非法使用行为<span>已严重侵犯本公司的计算机软件著作权，并给本公司造成了严重的经济损失</span>。</p>";
            $sitehtml .="<div class='lrmore'>";
            $sitehtml .="<a href='".$strarr['payurl']."' target='_blank'>前往购买</a><a href='javascript:;' class='cloudBind'>立即授权</a>";
            $sitehtml .="</div></div></div></div>";
            $sitehtml .="<style>";
            $sitehtml .=".fix_box {position: fixed;left: 0;top: 0;width: 100%;height: 100%;z-index: 9999;display: show;}";
            $sitehtml .=".fix_box .fixbg {position: absolute;left: 0;top: 0;width: 100%;height: 100%;background: rgba(0,0,0,.6);z-index: 9;}";
            $sitehtml .=".fix_box .lrbox {position: absolute;width: 100%;height: 100%;top: 0;left: 0;z-index: 10;-ms-flex-align: center;align-items: center;-ms-flex-line-pack: center;align-content: center;display: -ms-flexbox;display: flex;-ms-flex-pack: center;}";
            $sitehtml .=".fix_box .lrtext {background: #fff;position: relative;z-index: 11;max-width: 630px;margin: 0 auto;padding-bottom: 40px;}";
            $sitehtml .=".fix_box .lrtext h4 {font-size: 16px;color: #333;display: block;font-weight: normal;line-height: 40px;background: #eee;margin: 0;padding: 0 20px;margin-bottom: 30px;}";
            $sitehtml .=".fix_box .lrtext p {font-size: 15px;color: #666;display: block;margin-bottom: 20px;padding: 0 20px;line-height: 22px;}";
            $sitehtml .=".fix_box .lrtext p span {color: red;}";
            $sitehtml .=".fix_box .lrmore {padding: 0 20px;text-align: center;padding-top: 20px;}";
            $sitehtml .=".fix_box .lrmore a {display: inline-block;width: auto;padding: 0 17px;height: 32px;line-height: 32px;text-align: center;color: #fff;font-size: 12px;background: #28b5d6;border: 0;margin: 0 10px;}";
            $sitehtml .=".fix_box .lrmore a:hover {background: #0099cc;color: #fff;}";
            $sitehtml .="</style>";
        }
        $assign['sitehtml'] = $sitehtml;
        $this->assign($assign);
        foreach (config('sys') as $k => $v) {
            config('sys.'.$k, strip_slashes_recursive($v));
        }
    }
    public function checkrecordapi(){
        $update_path = ROOT_PATH.'data'.DS.'uppack'.DS;
        $cloud = new \com\Cloud(config('cloud.identifier'), $update_path);
        $result = $cloud->data(['version' => config("yunucms.version"), 'identifier' => config('cloud.identifier'), 'app_grant' => config('cloud.grant')])->api('checkupgradeapi');
        
        if ($result && $result['code'] == 2) {
            if ($result['msg'] != "本月版本更新次数已达上限！") {
                $result = $cloud->record(config('sys.site_title'), config('yunucms.version'))->api('recordapi');
                if ($result) {
                    session('authorization', $result['authorization']);
                }
                return false;
            }
        }
        return true;
    }
}
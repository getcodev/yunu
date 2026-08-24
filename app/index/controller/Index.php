<?php
namespace app\index\controller;
use think\Db;

class Index extends Common
{
    public function index()
    {   
    	db('browse')->insert(['ip' => request()->ip(), 'time' => time(), 'type' => is_mobile() ? 2 : 1]);
    	if(config('sys.site_guide') == 1 && request()->url() == "/") {
			return $this->fetch($this->tpl_file.'index_default.html');
		}
		runhook('sys_index_index');
		$strarr = reeturnsitecopy();
        $sitehtml = "";
        if (cache('front_authorization') === false) {
            $version = include_once(ROOT_PATH.'version.php');
            config($version);
            $this->cloud = new \com\Cloud(config('cloud.identifier'));
            $result = $this->cloud->record(config('sys.site_title'), config('yunucms.version'))->api('recordapi');
            $front_authorization = 0;
            if ($result) {
                $front_authorization = $result['front_authorization'];
            }
            cache('front_authorization', $front_authorization, 86400);
        }else{
            $front_authorization = cache('front_authorization');
        }
        if ($front_authorization) {
            $sitehtml .="<div class='fix_box'>";
            $sitehtml .="<div class='fixbg'></div>";
            $sitehtml .="<div class='lrbox'>";
            $sitehtml .="<div class='lrtext'>";
            $sitehtml .="<h4>侵 权 告 知 函</h4>";
            $sitehtml .="<p>尊敬的用户您好，贵公司网站（<span>".config('sys.site_levelurl')."</span>）正在使用本公司拥有合法著作权的软件<span>".$strarr['sitestr']."</span>（简称“<span>".$strarr['copy_sysname']."</span>”".$strarr['copy_num']."，但本公司的正版用户数据库中并未有贵公司的购买记录。    </p>";
            $sitehtml .="<p>本公司《使用协议》已载明，企业用户（泛指非自然人的团体，如企业、协会等组织机构）必须购买软件授权后方可正式建站使用，在未获得商业授权之前，企业用户<span>不得将本软件用于正式建站</span>。根据上述内容，贵公司作为企业用户，如使用本公司开发的<span>".$strarr['sitestr']."</span>，则应通过购买方式获得本公司商业授权后方可使用。但截至本通知发出之日，贵公司尚未向本公司购买<span>".$strarr['sitestr']."</span>。故，贵公司的上述非法使用行为<span>已严重侵犯本公司的计算机软件著作权，并给本公司造成了严重的经济损失</span>。</p>";
            $sitehtml .="<div class='lrmore_ds'>";
            $sitehtml .="<a href='".$strarr['payurl']."' target='_blank'>前往购买</a><a href='/index.php?s=index/index/cache'>我已购买</a>";
            $sitehtml .="</div></div></div></div>";
            $sitehtml .="<style>";
            $sitehtml .=".fix_box {position: fixed;left: 0;top: 0;width: 100%;height: 100%;z-index: 9999;display: show;}";
            $sitehtml .=".fix_box .fixbg {position: absolute;left: 0;top: 0;width: 100%;height: 100%;background: rgba(0,0,0,.6);z-index: 9;}";
            $sitehtml .=".fix_box .lrbox {position: absolute;width: 100%;height: 100%;top: 0;left: 0;z-index: 10;-ms-flex-align: center;align-items: center;-ms-flex-line-pack: center;align-content: center;display: -ms-flexbox;display: flex;-ms-flex-pack: center;}";
            $sitehtml .=".fix_box .lrtext {background: #fff;position: relative;z-index: 11;max-width: 630px;margin: 0 auto;padding-bottom: 40px;}";
            $sitehtml .=".fix_box .lrtext h4 {font-size: 16px;color: #333;display: block;font-weight: normal;line-height: 40px;background: #eee;margin: 0;padding: 0 20px;margin-bottom: 30px;}";
            $sitehtml .=".fix_box .lrtext p {font-size: 15px;color: #666;display: block;margin-bottom: 20px;padding: 0 20px;line-height: 22px;}";
            $sitehtml .=".fix_box .lrtext p span {color: red;}";
            $sitehtml .=".fix_box .lrmore_ds {padding: 0 20px;text-align: center;padding-top: 20px;}";
            $sitehtml .=".fix_box .lrmore_ds a {display: inline-block;width: auto;padding: 0 17px;height: 32px;line-height: 32px;text-align: center;color: #fff;font-size: 12px;background: #28b5d6;border: 0;margin: 0 10px;}";
            $sitehtml .=".fix_box .lrmore_ds a:hover {background: #0099cc;color: #fff;}";
            $sitehtml .="</style>";
        }
        $this->sitehtml = $sitehtml;

		if(input('publichtml', 0, 'intval') == 1){
            $tpl_file = './template/'.config('sys.theme_style').'/index/';
            $info = $this->buildHtml('index', 'html/', $tpl_file.'index_index.html');
        }

        if (config('sys.indexhtml')) {
			if (!session('sys_areainfo')) {
	            $htmlfilestr = "./index.html";
				$dir = "./";
				$filename = "index";
			}else{
				$htmlfilestr = "./caches/area/".session('sys_areainfo')['etitle'].".html";
		        $dir = "./caches/area/";
		        if(!is_dir($dir)){
		            mkdir($dir, 0777, true);
		        }
		        $filename = session('sys_areainfo')['etitle'];
			}
			if (file_exists($htmlfilestr)) {
	            if ((date('d',filemtime($htmlfilestr)) != date('d', time())) && (config('sys.indexhtml_time') < date('H:i:s',time()))) {
	                unlink($htmlfilestr);
	            }else{
	                return $this->fetch($htmlfilestr).$this->sitehtml; exit();
	            }
		    }
		    $tpl_file = './template/'.config('sys.theme_style').'/index/';
	       	$info = $this->buildHtml($filename, $dir, $tpl_file.'index_index.html');
		}
        return $this->fetch().$this->sitehtml;
    }
    private function buildHtml($htmlfile='', $htmlpath='', $templateFile='') {
        $content = $this->fetch($templateFile);
        $htmlpath = !empty($htmlpath) ? $htmlpath : APP_PATH.'/html';
        $htmlfile = $htmlpath.$htmlfile.'.html';
        if(file_put_contents($htmlfile, $content) === false) {
            return false;
        } else {
            return true;
        }
    }
    public function cache(){
    	dir_del(RUNTIME_PATH.'cache'.DS);
    	$this->redirect('/');
    }
    public function empty404(){
        return $this->fetch("./404.htm");
    }
}

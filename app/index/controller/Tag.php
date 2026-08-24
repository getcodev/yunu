<?php
namespace app\index\controller;
use think\Db;

class Tag extends Common
{
	public function index(){
		$input = input();
		if (!isset($input['title'])) {
			abort(404); exit();
		}
		$info['title'] = htmlspecialchars($input['title']);
		$info['title'] = str_replace(".html", "", $info['title']);
		$taginfo = db('tagurl')->where(["tagurl"=>$info['title']])->find();
		$coninfo = db('content')->where(["tag"=>['like','%'.$info['title'].'%']])->find();
		if (empty($taginfo) && empty($coninfo)) {
			abort(404); exit();
		}
		if ($taginfo) {
			$info['title'] = $taginfo['tagname'];
			db('tagurl')->where(['id'=>$taginfo['id']])->setInc('click');
		}
    	$taginfo = update_str_dq($taginfo, session('sys_areainfo'));

    	$this->assign($info);
		$this->assign(['tag'=>$taginfo]);
    	$page = input("param.page/d", 0);
    	if($taginfo && input('publichtml', 0, 'intval') == 1){
			$htmldir = "html/tag/".$taginfo['tagurl']."/";
			if ($page) {
				$htmldir = $htmldir."page/".$page."/";
			}
			@mkdir($htmldir, 0777, true); 
            $tpl_file = './template/'.config('sys.theme_style').'/index/';
            $info = $this->buildHtml('index', $htmldir, $tpl_file."tag_index.html");
        }
		return $this->fetch();
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
}
?>
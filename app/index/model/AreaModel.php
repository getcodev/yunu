<?php
namespace app\index\model;
use think\Model;
use think\Db;
use app\admin\model\Plugins as PluginsModel;
class AreaModel extends Model
{
    protected $name='area';
    
    public function getAreaUrl($area) {
	    $url = '';
	    switch (config('sys.url_model')) {
	    	case '1'://动态
	    		if ($area['isurl']) {
	    			$url = config('sys.site_protocol')."://".$area['etitle'].'.'.config('sys.site_levelurl');
	    		}else{
	    			$url = config('sys.site_protocol')."://".config('sys.site_url')."/index.php?area=".$area['etitle'];
	    		}
	    		break;
	    	case '3'://伪静态
	    		if ($area['isurl']) {
	    			$url = config('sys.site_protocol')."://".$area['etitle'].'.'.config('sys.site_levelurl');
	    		}else{
	    			$url = config('sys.site_protocol')."://".config('sys.site_url').'/'.$area['etitle'].".html";
	    		}
	    		break;
	    }
	    return $url;
	}

	public function getDefArea(){
		if (!cache('defareaetitle')) {
			$etitle = $this->where(['id'=>config('sys.seo_default_area')])->value('etitle');
			cache('defareaetitle', $etitle, 7200);
		}else{
			$etitle = cache('defareaetitle');
		}
		return $etitle;
	}

	public function getInfo($where=[], $field='*'){
        return $this->where($where)->field($field)->find();
    }

    public function getOneArea($where=[]){
        $info1 = $this->where($where)->find()->toArray();
        $info2 = [];
        if (PluginsModel::where(['name'=>'areaextend'])->find()) {
            $plugClass = get_plugins_class('areaextend');
            if (class_exists($plugClass)) {
                $plugObj = new $plugClass;
                $info2 = $plugObj->getdata($info1['id']);
            }
        }
        if ($info2) {
            return array_merge($info1, $info2);
        }else{
            return $info1;
        }
    }
}
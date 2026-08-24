<?php
namespace app\admin\controller;
use think\Db;

setlocale(LC_ALL, 'zh_CN.UTF-8');

class Tpl extends Common{
    public function _initialize(){
        parent::_initialize();
        if (!config('sys.site_tplfun')) {
            if(request()->isAjax()){
                echo json(['code' => 2, 'msg' => '模板文件管理功能已关闭，如需使用请点击确定，前往基础设置-模板文件管理开启功能！']);
            }else{
                echo("<script type='text/javascript'>alert('模板文件管理功能已关闭，如需使用请点击确定，前往基础设置-模板文件管理开启功能！');window.location.href='/index.php?s=/admin/system/basic';</script>");
            }
            exit();
        }
    }
    public function index(){
        if (input('filedir')) {
            $filedir = input('filedir');
        }else{
            $filedir = "./template";
        }
        if ($this->checkfile($filedir)) {
            $filedir = "./template";
        }

        $key = input('key');

        $dirflags = empty($key) ? '*' : "*$key*";
        $dirlist = getFileFolderList($filedir , 1, $dirflags);
        if($dirlist){
            foreach ($dirlist as $k => $v) {
                $dirdata = [
                    'dirname' => $v,
                    'dirurl' => $filedir."/".$v
                ];
                $dirlist[$k] = $dirdata;
            }
        }

        $fileflags = empty($key) ? '*.*' : "*$key*";
        $filelist = getFileFolderList($filedir , 2, $fileflags);
        $tpllistinfo = [];
        if ($filelist) {
            foreach ($filelist as $k => $v) {
                $ext = pathinfo($v, PATHINFO_EXTENSION);
                $tplfile = [
                    'filename' => $v,
                    'filesize' => format_bytes(filesize($filedir."/".$v)),
                    'filetime' => date('Y-m-d H:i:s', filemtime($filedir."/".$v)),
                    'ysfilename' => str_replace(".", "*-*", $v),
                    'isedit' => in_array($ext, ['html','js','css']) ? true : false,
                    'isshow' => in_array($ext, ['bmp','jpg','jpeg','png','gif']) ? true : false,
                    'icon' => $this->ext2icon($ext),
                ];
                $tpllistinfo[] = $tplfile;
            }
        }
        $template = input('template', 'index');
        $this->assign([
            'tpllistinfo'=>$tpllistinfo,
            'filedir' => $filedir,
            'dirlist' => $dirlist,
            'crumbs' => $this->create_crumbs($filedir),
            'template' => $template,
        ]);
        return $this->fetch($template);
    }
    public function editfile(){
        $filename = input('filename');
        $filename = str_replace("*-*", ".", $filename);

        $allfilename = $filename;
        if (!file_exists($allfilename)) {
            return json(['code' => 2, 'msg' => "可编辑文件不存在"]);
        }
        $ext = pathinfo($allfilename, PATHINFO_EXTENSION);
        if (!in_array($ext, ['html', 'css', 'js'])) {
            return json(['code' => 2, 'msg' => '可编辑文件后缀异常']);
        }
        if(request()->isAjax()){
            $filecontent = input('filecontent');
            $mgc = ['eval','exec','scandir','shell_exec','ini_set','ini_restore','popen'];
            foreach ($mgc as $k => $v) {
                if (strpos($filecontent, $v) !== false) {
                    return json(['code' => 2, 'msg' => '编辑内容存在危险关键词：'.$v.'，请检查后重新提交']);
                }
            }
            file_put_contents($allfilename, $filecontent);
            return json(['code' => 1, 'msg' => '已保存']);
        }
        $filecontent = file_get_contents($allfilename);
        $this->assign([
            'filecontent'=>htmlspecialchars($filecontent),
            'filename'=>str_replace(".", "*-*", $filename),
            'ext' => $ext,
        ]);
        return $this->fetch();
    }
    public function delfile(){
        $filename = input('filename');
        $filename = str_replace("*-*", ".", $filename);
        if (!file_exists($filename)) {
            return json(['code' => 2, 'msg' => "删除文件不存在"]);
        }
        if ($this->checkfile($filename, false)) {
            return json(['code' => 2, 'msg' => "文件路径异常，请勿恶意操作！"]);
        }
        @unlink($filename);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    public function deldir(){
        $dirname = input('dirname');
        if (!is_dir($dirname)) {
            return json(['code' => 2, 'msg' => "删除文件夹不存在"]);
        }
        if ($this->checkfile($dirname)) {
            return json(['code' => 2, 'msg' => "文件夹路径异常，请勿恶意操作！"]);
        }
        dir_del($dirname);
        @rmdir($dirname);
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    public function adddir(){
        $filedir = input('filedir');
        $dirname = input('dirname');

        if (strpbrk($dirname, "\\/?%*:|\"<>") !== FALSE) {
            return json(['code' => 2, 'msg' => "文件夹名称不符合规范！"]);
        }
        $newdirname = $filedir."/".$dirname;
        if (is_dir($newdirname)) {
            return json(['code' => 2, 'msg' => "文件夹已存在！"]);
        }
        if ($this->checkfile($newdirname, false)) {
            return json(['code' => 2, 'msg' => "文件夹路径异常，请勿恶意操作！"]);
        }
        @mkdir($newdirname, 0777, true);
        return json(['code' => 1, 'msg' => '创建成功']);
    }
    //文件上传
    public function uploadfile(){
        $filedir = urldecode(input('filedir'));

        if ($this->checkfile($filedir)) {
            return json(['status' => 0, 'msg' => "文件夹路径异常，请勿恶意操作！"]);
        }
        $file = request()->file(input('file'));
        $name = $file->getInfo()["name"];
        $info = $file->validate(['ext'=>"js,css,jpg,jpeg,png,gif,html",'size'=>20480*1024])->move($filedir, $name);

        if($info){
            $res['status'] = 1;
            $res['file_name'] = $info->getFilename();
            $res['file_path'] = $filedir."/".str_replace("\\", "/", $info->getSaveName());
            $res['msg'] = "上传成功";
        }else{
            $res['status'] = 0;
            $res['msg'] = $file->getError();
        }
        return json_encode($res);
    }

    //文件上传
    public function drop_upload(){
        $filedir = input('filedir');

        if ($this->checkfile($filedir)) {
            return json(['status' => 0, 'msg' => "文件夹路径异常，请勿恶意操作！"]);
        }
        $file = request()->file()['file'];
        $name = $file->getInfo()["name"];
        $info = $file->validate(['ext'=>"js,css,jpg,jpeg,png,gif,html",'size'=>20480*1024])->move($filedir, $name);

        if($info){
            $res['status'] = 1;
            $res['file_name'] = $info->getFilename();
            $res['file_path'] = $filedir."/".str_replace("\\", "/", $info->getSaveName());
            $res['msg'] = "上传成功";
        }else{
            $res['status'] = 0;
            $res['msg'] = $file->getError();
        }
        return json($res);
    }
    // 重命名
    public function rename(){
        $filedir = input('filedir');
        $filename = input('filename');
        $oldname = $filedir.'/'.input('oldname');

        $fileinfo = pathinfo($filename);
        if (!in_array($fileinfo['extension'],['js','css','jpg','jpeg','png','gif','html'])) {
            return json(['code' => 2, 'msg' => "名称不符合规范！"]);
        }

        if (strpbrk($filename, "\\/?%*:|\"<>") !== FALSE) {
            return json(['code' => 2, 'msg' => "名称不符合规范！"]);
        }
        $newname = $filedir."/".$filename;
        if (file_exists($newname)) {
            return json(['code' => 2, 'msg' => "存在同名文件！"]);
        }
        if ($this->checkfile($newname, is_dir($newname))) {
            return json(['code' => 2, 'msg' => "路径异常，请勿恶意操作！"]);
        }
        rename($oldname, $newname);
        return json(['code' => 1, 'msg' => '成功']);
    }
    // 粘贴
    public function paste(){
        $filedir = input('filedir');
        $iscut = input('iscut');
        $select_file = input('select_file');
        $select_file = json_decode($select_file, true);
        if (empty($select_file)) {
            return json(['code' => 2, 'msg' => "请选择文件"]);
        }

        foreach ($select_file as $key => $value) {
            if (strpbrk($value['filename'], "\\/?%*:|\"<>") !== FALSE) {
                return json(['code' => 2, 'msg' => "名称不符合规范！"]);
            }
            if (empty($iscut)) {
                $extension = pathinfo($value['filename'], PATHINFO_EXTENSION);
                $filename = pathinfo($value['filename'], PATHINFO_FILENAME);
                $newname = $filedir."/".$filename.'copy.'.$extension;
            } else {
                $newname = $filedir."/".$value['filename'];
            }

            if (file_exists($newname)) {
                return json(['code' => 2, 'msg' => "存在同名文件！"]);
            }
            if ($this->checkfile($newname, is_dir($newname))) {
                return json(['code' => 2, 'msg' => "路径异常，请勿恶意操作！"]);
            }
        }
        foreach ($select_file as $key => $value) {
            $oldname = $value['filedir']."/".$value['filename'];
            if (empty($iscut)) {
                $extension = pathinfo($value['filename'], PATHINFO_EXTENSION);
                $filename = pathinfo($value['filename'], PATHINFO_FILENAME);
                $newname = $filedir."/".$filename.'copy.'.$extension;
                $this->copy_dir($oldname, $newname);
            } else {
                $newname = $filedir."/".$value['filename'];
                rename($oldname, $newname);
            }
        }
        return json(['code' => 1, 'msg' => '成功']);
    }
    // 删除
    public function delete(){
        $select_file = input('select_file');
        $select_file = json_decode($select_file, true);
        if (empty($select_file)) {
            return json(['code' => 2, 'msg' => "请选择文件"]);
        }
        foreach ($select_file as $key => $value) {
            $file_path = $value['filedir']."/".$value['filename'];
            if (!file_exists($file_path)) {
                return json(['code' => 2, 'msg' => "删除文件不存在"]);
            }
            if ($this->checkfile($file_path, is_dir($file_path))) {
                return json(['code' => 2, 'msg' => "文件路径异常，请勿恶意操作！"]);
            }
            if (is_dir($file_path)) {
                dir_del($file_path);
                @rmdir($file_path);
            } else {
                @unlink($file_path);
            }
        }
        return json(['code' => 1, 'msg' => '成功']);
    }

    private function checkfile($filestr,$isdir = true){
        $filestatus = false;
        if (substr($filestr, 0, strlen("./template")) !== "./template") {
            $filestatus = true;
        }
        if (strstr($filestr, '..')) {
            $filestatus = true;
        }
        if ($isdir) {
            if (!is_dir($filestr)) {
                $filestatus = true;
            }
        }
        return $filestatus;
    }

    private function copy_dir($src, $des) {
        if (is_file($src)) {
            copy($src, $des);
        } else {
            $dir = opendir($src);
            @mkdir($des);
            while (($file = readdir($dir)) !== false) {
                if (($file != '.') && ($file != '..')) {
                    $source = $src.'/'.$file;
                    $destination = $des.'/'.$file;
                    if (is_dir($source)) {
                        $this->copy_dir($source, $destination);
                    } else {
                        copy($source, $destination);
                    }
                }
            }
            closedir($dir);
        }
    }

    private function create_crumbs($filedir){
        $filedirstr = str_replace('./', '', $filedir);
        $filedirarr = explode('/', $filedirstr);
        $crumbs = [];
        foreach ($filedirarr as $k => $v) {
            $prev = array_slice($filedirarr, 0, $k+1);
            $crumbs[] = [
                'filename'=>$v,
                'filedir'=>'./'.implode('/', $prev),
            ];
        }
        return $crumbs;
    }
    private function ext2icon($ext) {
        $config = [
            'html' => 'icon-bianzu3',
            'jpg' => 'icon-bianzu18',
            'png' => 'icon-bianzu20',
        ];
        if (isset($config[$ext])) {
            return $config[$ext];
        }
        return 'icon-wenjian1';
    }
}

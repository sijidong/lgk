<?php

namespace app\api\controller;

use app\common\controller\Api;
use fast\Random;
use think\Config;

class Clouddisk extends Api{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    public function index()
    {
        $path = $this->request->post('base_path');

        $file_path = $this->request->post('file_path');

        $user_id = $this->auth->getUser()->id;
        $url = ROOT_PATH . 'public' . DS .'clouddisk'. DS. $user_id;

        if (!is_dir($url)) {
            mkdir($url,0777,true);
        }

        $real_path = ROOT_PATH . 'public' . DS .'clouddisk'. DS. $user_id .$path;
        if (!empty($file_path)) {
            $real_path = ROOT_PATH . 'public' . DS .'clouddisk'. DS. $user_id . $path . $file_path;
        }
        $real_path = realpath($real_path);
        if ($real_path === false || !is_dir($real_path)) {
            $this->error('文件错误！');
        }
        $arr = explode('/', $real_path);
        if (empty($arr[6]) || $arr[6] != $this->auth->getUser()->id) {
            $this->error('文件路径错误！');
        }

        $data = scandir($real_path);
        $file_data = [];
        foreach ($data as $value) {
            if($value != '.' && $value != '..'){
                $file_path = $real_path. DS .$value;
                $tmp['name'] = $value;
                $tmp['is_dir'] = is_dir($file_path);
                $tmp['filesize'] = $this->getSize(filesize($file_path));
                $tmp['time'] = date('Y-m-d H:i:s',filemtime($file_path));
                $file_data[] = $tmp;
            }
        }

        $this->success('ok',$file_data);
    }

    /**
     * 上传
     */
    function upload()
    {
        $this->loadlang('common');
        $user_id = $this->auth->getUser()->id;

        $file = $this->request->file('file');
        $path = $this->request->post('base_path');

        $real_path = ROOT_PATH . 'public' . DS .'clouddisk'. DS. $user_id .$path;
        $real_path = realpath($real_path);
        if ($real_path === false || !is_dir($real_path)) {
            $this->error('文件错误！');
        }
        $arr = explode('/', $real_path);
        if (empty($arr[6]) || $arr[6] != $this->auth->getUser()->id) {
            $this->error('文件路径错误！');
        }

        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }

        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');
        $upload['mimetype'] = '*';
        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
//        print_r($upload);
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
//        print_r($fileInfo);
        $suffix = $suffix && preg_match("/^[a-zA-Z0-9]+$/", $suffix) ? $suffix : 'file';

        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);
//        print_r($typeArr);exit;
        //禁止上传PHP和HTML文件
        if (in_array($fileInfo['type'], ['text/x-php', 'text/html']) || in_array($suffix, ['php', 'html', 'htm'])) {
            $this->error(__('Uploaded file format is limited'));
        }
        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            $this->error(__('Uploaded file format is limited'));
        }
        //验证是否为图片文件
        $imagewidth = $imageheight = 0;
        if (in_array($fileInfo['type'], ['image/gif', 'image/jpg', 'image/jpeg', 'image/bmp', 'image/png', 'image/webp']) || in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'webp'])) {
            $imgInfo = getimagesize($fileInfo['tmp_name']);
            if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1])) {
                $this->error(__('Uploaded file is not a valid image'));
            }
            $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
            $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
        }
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            '{hour}'     => date("H"),
            '{min}'      => date("i"),
            '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //
        $splInfo = $file->validate(['size' => $size])->move($real_path, $fileName);
        if ($splInfo) {
            $params = array(
                'admin_id'    => 0,
                'user_id'     => (int)$this->auth->id,
                'filesize'    => $fileInfo['size'],
                'imagewidth'  => $imagewidth,
                'imageheight' => $imageheight,
                'imagetype'   => $suffix,
                'imageframes' => 0,
                'mimetype'    => $fileInfo['type'],
                'url'         => $uploadDir . $splInfo->getSaveName(),
                'uploadtime'  => time(),
                'storage'     => 'local',
                'sha1'        => $sha1,
            );
            $attachment = model("attachment");
            $attachment->data(array_filter($params));
            $attachment->save();
            \think\Hook::listen("upload_after", $attachment);
            $this->success(__('Upload successful'), [
                'url' => $uploadDir . $splInfo->getSaveName()
            ]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }

    /**
     * 下载
     */
    function download()
    {
        $user_id = $this->auth->getUser()->id;
        $base_path = $this->request->post('base_path');
        $path = $this->request->post('file_path');

        $url = ROOT_PATH . 'public' . DS .'clouddisk'. DS. $user_id . $base_path . $path;
        $url = realpath($url);

        $arr = explode('/', $url);
        if (empty($arr[6]) || $arr[6] != $this->auth->getUser()->id) {
            $this->error('文件路径错误！');
        }

        if (!file_exists($url)) {
            $this->error('数据错误！');
        }

        return '/clouddisk'. DS. $user_id . $base_path . $path;
//        header("Content-type: application/octet-stream");
//        header('Content-Disposition: attachment; filename="'. basename($url) .'"');
//        header("Content-Length: ". filesize($url));
//        readfile($url);
    }

    function getSize($filesize) {

        if($filesize >= 1073741824) {

         $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';

        } elseif($filesize >= 1048576) {

         $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';

        } elseif($filesize >= 1024) {

         $filesize = round($filesize / 1024 * 100) / 100 . ' KB';

        } else {

             $filesize = $filesize . ' 字节';

        }

    return $filesize;

  }
}
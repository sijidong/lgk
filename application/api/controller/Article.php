<?php
/**
 * Created by PhpStorm.
 * User: Progress
 * Date: 2019/10/28
 * Time: 17:19
 */
namespace app\api\controller;

use app\admin\model\Announcement;
use app\admin\model\News;
use app\common\controller\Api;

/**
 * 系统文章
 */
class Article extends Api
{
    protected $noNeedLogin = ['getRollMessage','getAnnouncementList','getAnnouncementDetail'];
    protected $noNeedRight = ['*'];

    /**
     * 滚动消息
     */
    public function getRollMessage()
    {
        //TODO
        $data = Announcement::field('id,title')->where('status',1)->order('id','desc')->select();
        $this->success('ok',$data);
    }

    /**
     * 公告列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAnnouncementList()
    {
        $data = Announcement::field('id,title,introduce,update_time')->where('status',1)->order('update_time','asc')->select();
        $tmp = [];
//        foreach ($data as $value) {
//            $value->time = date('Y年m月d日',strtotime($value->update_time));
//            $tmp[] = $value;
////            $tmp[date('Ym',strtotime($value->update_time))]['str'] =  date('Y年m月',strtotime($value->update_time));
////            $tmp[date('Ym',strtotime($value->update_time))]['data'][] = $value;
//        }
        foreach ($data as $value) {
            $tmp[date('Ym',strtotime($value->update_time))]['str'] =  date('Y年m月',strtotime($value->update_time));
            $tmp[date('Ym',strtotime($value->update_time))]['data'][] = $value;
        }
        $this->success('ok',$tmp);
    }

    /**
     * 公告详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAnnouncementDetail()
    {
        $id = $this->request->param('id');

        $data = Announcement::where('id',$id)->where('status',1)->find();

        $this->success('ok',$data);
    }

    /**
     * 首页资讯列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNewsList()
    {
        $data = News::field('id,image,title,update_time')->where('status',1)->order('update_time','desc')->select();

        $this->success('ok',$data);
    }

    /**
     * 公告详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNewsDetail()
    {
        $id = $this->request->param('id');

        $data = News::where('id',$id)->where('status',1)->find();

        $this->success('ok',$data);
    }
}
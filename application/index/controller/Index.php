<?php

namespace app\index\controller;

use app\admin\model\MiningType;
use app\admin\model\MiningUserWallet;
use app\admin\model\Order;
use app\admin\model\OrderStock;
use app\admin\model\WaterOrigin;
use app\admin\model\WaterStock;
use app\common\controller\Api;
use app\common\controller\Frontend;
//use Faker\Factory;
//use Faker\Factory;
use app\common\model\CountryMobilePrefix;
use app\common\model\Detail;
use Faker\Factory;
use Faker\Guesser\Name;
use Faker\Provider\Address;
use Faker\Provider\Uuid;
use fast\Http;
use think\Db;


class Index extends Api
{
    use \app\api\traits\CountryMobilePrefix;
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

//    public function index()
//    {
//        echo 123;
//        echo phpinfo();exit;
//    }

//    public function index()
//    {
//        $data = [
//            'coinid' => 1,
//            'userid' => 2
//        ];
//        $result = $this->http_post_json('127.0.0.1:10603/v1/lgk/generateaddr',json_encode($data));
//
////        $result = Http::post('127.0.0.1:10603/v1/lgk/generateaddr',$data);
//        var_dump($result);
//    }


//    public function dec()
//    {
//        $data = WaterStock::whereTime('create_time','today')->where('type',20)->limit(10)->select();
//        foreach ($data as $value) {
//            try {
//                \app\common\model\User::where('id',$value->user_id)->setDec('origin_static',$value->money);
//                \app\common\model\User::where('id',$value->user_id)->setInc('stock',$value->money);
//            } catch (\Exception $e) {
//                var_dump($value->user_id);
//                continue;
//            }
////            \app\common\model\User::where('id',$value->user_id)->setDec('origin_static',$value->money);
////            \app\common\model\User::where('id',$value->user_id)->setInc('stock',$value->money);
//        }
//        WaterStock::whereTime('create_time','today')->whereIn('type',[3,20])->delete();
//    }
//
//    public function add()
//    {
//        $data = OrderStock::where('balance','<>',0)->select();
//        foreach ($data as $value) {
//            $balance = $value->balance / 0.99;
//            $money = $value->money - ($balance - $value->balance);
//            OrderStock::where('id',$value->id)->update(['balance' => $balance, 'money' => $money]);
//        }
//
//    }
////    public function news()
////    {
////        $newslist = [];
////        return jsonp(['newslist' => $newslist, 'new' => count($newslist), 'url' => 'https://www.fastadmin.net?ref=news']);
////    }
//
//    public function yang(){
//        $id = $this->auth->getUser()->id;
//        $user_data = \app\common\model\User::field('id,pid,rule_user_level_id')->select();
//        $result = [];
//        $user = getChildId($user_data,$id,$result, - 1);
////        foreach ($user as $value) {
////            $value->stock =
////        }
//    }
}

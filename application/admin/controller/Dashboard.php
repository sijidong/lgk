<?php

namespace app\admin\controller;

use app\admin\model\Order;
use app\admin\model\OrderBase;
use app\admin\model\OrderMarket;
use app\admin\model\OrderOrigin;
use app\admin\model\OrderShop;
use app\admin\model\OrderStock;
use app\admin\model\User;
use app\admin\model\WaterOrigin;
use app\admin\model\WaterStock;
use app\common\controller\Backend;
use think\Config;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{

    /**
     * 查看
     */
    public function index()
    {
        $seventtime = \fast\Date::unixtime('day', -7);
        $paylist = $createlist = [];
        for ($i = 0; $i < 7; $i++)
        {
            $day = date("Y-m-d", $seventtime + ($i * 86400));
            $createlist[$day] = mt_rand(20, 200);
            $paylist[$day] = mt_rand(1, mt_rand(1, $createlist[$day]));
        }
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');

        $totaluser = User::count();
        $totalorder = 0;
        $totalorderamount = OrderBase::where('status',4)->sum('number');

        $todayuserlogin = User::whereTime('logintime','today')->count();
        $todayusersignup = User::whereTime('jointime','today')->count();
        $todayorder = 0;

        $sven_time = strtotime(date('Y-m-d',time())) - (3600 * 24 * 7);
        $sevendnu = User::whereTime('jointime','>=',$sven_time)->count();
        $sevendnu = round($sevendnu / $totaluser * 100, 1);
        $sevendau = User::whereTime('logintime','>=',$sven_time)->count();
        $sevendau = round($sevendau / $totaluser * 100, 1);

        $base_order = OrderBase::whereTime('create_time','today')->count();
        $origin_order = OrderOrigin::whereTime('create_time','today')->count();
        $stock_order = OrderStock::whereTime('create_time','today')->count();
        $market_order = OrderMarket::whereTime('create_time','today')->count();
        $shop_order = OrderShop::whereTime('create_time','today')->count();
        $un_base_order = OrderBase::where('status',1)->count();
        $stock_total = \app\common\model\User::where('status','normal')->sum('stock');

        $origin_rechagre = WaterOrigin::where('type',MINING_WATER_RECHARGE)->sum('money');     //总共充值
        $origin_recharge_today = WaterOrigin::where('type',MINING_WATER_RECHARGE)->where('create_time','today')->sum('money');     //总共充值

        $origin_withdraw = \app\admin\model\MiningWithdraw::where('status',1)->sum('number');   //总共提币
        $origin_withdraw_today = \app\admin\model\MiningWithdraw::where('status',1)->where('create_time','today')->sum('number');

        $origin_withdraw_undeal = \app\admin\model\MiningWithdraw::where('status',0)->count();      //未处理提币
        $static = WaterStock::where('type',WATER_STOCK_RELEASE_PROFIT)->sum('money');         //静态总产出
        $dynamic = WaterOrigin::whereIn('type',[WATER_ORIGIN_DYNAMIC,WATER_ORIGIN_ONE,WATER_ORIGIN_TWO,WATER_ORIGIN_THREE,WATER_ORIGIN_FOUR])
            ->sum('money');         //动态总产出
        $market_money = OrderOrigin::whereTime('create_time','today')->where('status',3)->sum('money');
        $this->view->assign([
            'totaluser'        => $totaluser,
            'totalviews'       => $totaluser,
            'totalorder'       => $totalorder,
            'totalorderamount' => $totalorderamount,
            'todayuserlogin'   => $todayuserlogin,
            'todayusersignup'  => $todayusersignup,
            'todayorder'       => $todayorder,
            'unsettleorder'    => 132,
            'sevendnu'         => $sevendnu.'%',            //7日新增
            'sevendau'         => $sevendau.'%',            //7日活跃
            'paylist'          => $paylist,
            'createlist'       => $createlist,
            'addonversion'       => $addonVersion,
            'uploadmode'       => $uploadmode,
            'base_order'       => $base_order,
            'origin_order'       => $origin_order,
            'stock_order'       => $stock_order,
            'market_order'       => $market_order,
            'shop_order'       => $shop_order,
            'un_base_order'       => $un_base_order,
            'stock_total'       => $stock_total,
            'market_money'      => $market_money,
            'origin_withdraw_today'      => $origin_withdraw_today,
            'origin_recharge_today'      => $origin_recharge_today,
            'origin_withdraw_undeal'      => $origin_withdraw_undeal,
            'origin_recharge_today'      => $origin_recharge_today,
//            'market_money'      => $market_money,

        ]);

        return $this->view->fetch();
    }

}

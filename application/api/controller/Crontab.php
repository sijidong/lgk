<?php

namespace app\api\controller;

use app\admin\model\Banner;
use app\admin\model\BonusWater;
use app\admin\model\Car;
use app\admin\model\CarRule;
use app\admin\model\CarUser;
use app\admin\model\ManageRule;
use app\admin\model\MineUser;
use app\admin\model\MiningCalculation;
use app\admin\model\MiningStatistics;
use app\admin\model\MiningType;
use app\admin\model\MiningUserWallet;
use app\admin\model\MiningWater;
use app\admin\model\MiningWithdraw;
use app\admin\model\OrderMarket;
use app\admin\model\OrderOrigin;
use app\admin\model\OrderStock;
use app\admin\model\RuleDynamic;
use app\admin\model\RuleStatic;
use app\admin\model\RuleUserLevel;
use app\admin\model\UserLevelRule;
use app\admin\model\UserWater;
use app\admin\model\WaterCoin;
use app\admin\model\WaterOrigin;
use app\admin\model\WaterOriginDetail;
use app\admin\model\WaterShop;
use app\admin\model\WaterStock;
use app\common\controller\Api;
use app\common\model\Area;
use app\common\model\Config;
use app\common\model\CrontabControl;
use app\common\model\Detail;
use app\common\model\Intoken;
use app\common\model\Outtoken;
use app\common\model\StatementProperty;
use app\common\model\User;
use app\common\model\WalletTransferIn;
use app\common\model\WalletTransferOut;
use think\Db;
use think\Log;

/**
 * 示例接口
 */
class Crontab extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
    protected $noNeedRight = ['*'];

//    public function index()
//    {
////        $data = WaterStock::field('create_time,user_id, money')
////            ->whereTime('create_time','>=','2019-12-18 00:00:00')
////            ->whereTime('create_time','<','2019-12-20 00:00:00')
////            ->where('type',1)->select();
////        $data = Detail::whereNotNull('money1')->select();
////        foreach ($data as $value) {
////            $add = $value->money2 ?? 0;
////            $balance = $value->balance1 + $add;
////            Detail::where('id', $value->id)->update(['money2' => $balance]);
////        }
//////        exit;
////        $rule = RuleStatic::select();
////        $data = Detail::whereNotNull('money2')->select();
////        foreach ($data as $value) {
////            $profit_rate = 0;
////            foreach ($rule as $val) {
////                if ($value->money2 >= $val->from && $value->money2 < $val->to) {
////                    $profit_rate = $val->profit;
////                    break;
////                }
////            }
////            if (!empty($profit_rate)) {
////                $profit = $profit_rate * $value->money2 / 100;
////                Detail::where('id', $value->id)->update(['bonus2' => $profit]);
////            }
////        }
//////
////        $data = Detail::whereNotNull('money2')->select();
////
////        foreach ($data as $value)
////        {
////            $profit = 5 * $value->bonus1 / 100;
////            $balance = $value->money2 + $value->bonus2 - $profit;
////            Detail::where('id', $value->id)->update(['transfer2' => $profit,'balance2'=> $balance]);
////        }
//    }

//    public function results()
//    {
//        $data = Detail::where('id','<>',0)->select();
//        $user_data = User::field('id,pid')->whereTime('createtime','<','2019-12-23 00:00:00')->select();
//        $rule = RuleDynamic::select();
//        $max = RuleDynamic::order('to','desc')->value('to');
//        $min_num = RuleStatic::order('to','asc')->value('to');
//        $user_num = [];
//        foreach ($data as $value)
//        {
//            $result = [];
//            getParents($user_data, $value->user_id,$result, 9999999);
//            $reduce_level = 0;
//            foreach ($result as $user) {
//                $pid_num = User::where('pid',$user->id)->whereTime('createtime','<','2019-12-23 00:00:00')->count();
//                $can_level = ($pid_num * 2) > $max ? $max :($pid_num * 2);
//                $user->level = $user->level - $reduce_level;
//
//                if ($user->level > $can_level) {
//                    $reduce_level += 1;
//                    continue;
//                }
//                $count_stock = Detail::where('user_id',$user->id)->value('money2') ?? 0;
//
//                if ($count_stock < $min_num) {
//                    $reduce_level += 1;
//                    continue;
//                }
//
//                $profit_rate = 0;
//                foreach ($rule as $val)
//                {
//                    if ($user->level >= $val->from && $user->level <= $val->to) {
//                        $profit_rate = $val->profit;
//                        break;
//                    }
//                }
//
//                if (!empty($profit_rate)) {
//                    $user_num[$user->id] = $user_num[$user->id] ?? 0;
//                    $money = $profit_rate * $value->bonus2 / 100;
//                    $user_num[$user->id] += $money;
//                }
//            }
//        }
//        foreach ($user_num as $user_id => $profit)
//        {
//            Detail::where('user_id',$user_id)->update(['dynamic2' => $profit]);
//        }
//    }
//
//    public function updateUser()
//    {
//        $data = Detail::whereNotNull('money2')->select();
//        $user_data = User::field('id,pid')->whereTime('createtime','<','2019-12-23 00:00:00')->select();
//        $next_data = RuleUserLevel::where('id',1)->find();
//        foreach ($data as $value) {
//            if ($value->money2 >= $next_data->produce) {
//                $result = [];
//                getChildId($user_data,$value->user_id,$result, -1);
//                $result[] = $value->user_id;
//                $total_stock = Detail::whereIn('user_id',$result)->sum('money2');
//                if ($total_stock > $next_data->team_produce && count($result) > $next_data->team_num) {
//                    \app\common\model\User::where('id',$value->user_id)->update(['rule_user_level_id' => 1]);
//                }
//            }
//        }
////        $user_data = User::field('id,pid')->whereTime('createtime','<','2019-12-18 00:00:00')->select();
//    }
//
//    public function teamss()
//    {
//        $rule_user_level = RuleUserLevel::select();
//        $rule_user_level_data = [];
//        foreach ($rule_user_level as $value) {
//            $rule_user_level_data[$value->id] = $value;
//        }
//
//        $all_user_data = \app\admin\model\User::field('id,pid,rule_user_level_id')
//            ->whereTime('createtime','<','2019-12-23 00:00:00')
//            ->select();
//        $user_num = [];
//        foreach ($all_user_data as $value)
//        {
//            $profit = Detail::where('user_id',$value->id)->value('bonus2');
//            if (!empty($profit)) {
//                $level = $value->rule_user_level_id;
//
//                $profit_rate = $rule_user_level_data[$value->rule_user_level_id]['profit'] ?? 0;
//                $result = [];
//                getParents($all_user_data, $value->id,$result, 999999999);
//                foreach ($result as $val)
//                {
//                    if ($val->rule_user_level_id != 0 && $val->rule_user_level_id > $level)
//                    {
//
//                        $profit_rate = $rule_user_level_data[$val->rule_user_level_id]['profit'] - $profit_rate;
//                        if ($profit_rate > 0) {
//                            $user_num[$val->id] = $user_num[$val->id] ?? 0;
//                            $profit_money = $profit_rate * $profit / 100;
//                            $user_num[$val->id] += $profit_money;
//                        }
//
//                        $level = $val->level;
//                    }
//                }
//            }
//        }
//
//        foreach ($user_num as $user_id => $profit) {
//            Detail::where('user_id',$user_id)->update(['dynamic_team2' => $profit]);
////            incBalance($user_id, 'origin_dynamic',$profit);
//        }
//    }
//
//    public function resultTransfer()
//    {
//        $transfer = Detail::whereNotNull('dynamic2')->whereOr('dynamic_team2','<>','null')->select();
//        foreach ($transfer as $value) {
//            $profit = $value->dynamic2 + $value->dynamic_team2;
//            $ss = ceil($profit * 5 / 100);
//            $balance = $profit - $ss + $value->dynamic_balance1;
//            Detail::where('id',$value->id)->update(['dynamic_transfer2' =>$ss,'dynamic_balance2' =>$balance]);
//        }
//    }
//
//    public function restart()
//    {
//        $data = Detail::select();
//        foreach ($data as $value) {
//            //产量
//            try {
//                WaterStock::where('user_id',$value->user_id)
//                    ->whereTime('create_time','>=','2019-12-18 00:00:00')
//                    ->whereTime('create_time','<=','2019-12-19 00:00:00')
//                    ->where('type',WATER_STOCK_INTEREST)
//                    ->update(['money' => $value->bonus7]);
//
//                //静态转换
//                WaterStock::where('user_id',$value->user_id)
//                    ->whereTime('create_time','>=','2019-12-18 00:00:00')
//                    ->whereTime('create_time','<=','2019-12-19 00:00:00')
//                    ->where('type',WATER_STOCK_CONVERT_SHOP)
//                    ->update(['money' => '-'.$value->transfer7]);
//
//                //静态转换
//                WaterShop::where('user_id',$value->user_id)
//                    ->whereTime('create_time','>=','2019-12-18 00:00:00')
//                    ->whereTime('create_time','<=','2019-12-19 00:00:00')
//                    ->where('type',WATER_SHOP_STATIC)
//                    ->update(['money' => $value->transfer7]);
//
//                //原酒动态
//                WaterOrigin::where('user_id',$value->user_id)
//                    ->whereTime('create_time','>=','2019-12-18 00:00:00')
//                    ->whereTime('create_time','<=','2019-12-19 00:00:00')
//                    ->where('type',WATER_ORIGIN_DYNAMIC)
//                    ->update(['money' => $value->dynamic7]);
//
//                //原酒节点
//                WaterOrigin::where('user_id',$value->user_id)
//                    ->whereTime('create_time','>=','2019-12-18 00:00:00')
//                    ->whereTime('create_time','<=','2019-12-19 00:00:00')
//                    ->where('type',WATER_ORIGIN_ONE)
//                    ->update(['money' => $value->dynamic_team7]);
//
//                //原酒动态转换积分
//                WaterOrigin::where('user_id',$value->user_id)
//                    ->whereTime('create_time','>=','2019-12-18 00:00:00')
//                    ->whereTime('create_time','<=','2019-12-19 00:00:00')
//                    ->where('type',WATER_ORIGIN_CONVERT_SHOP)
//                    ->update(['money' => $value->dynamic_transfer7]);
//
//                //商城获取积分流水
//                WaterShop::where('user_id',$value->user_id)
//                    ->whereTime('create_time','>=','2019-12-18 00:00:00')
//                    ->whereTime('create_time','<=','2019-12-19 00:00:00')
//                    ->where('type',WATER_SHOP_DYNAMIC)
//                    ->update(['money' => $value->dynamic_transfer7]);
//            }catch (\Exception $e) {
//                echo $value->user_id;
//            }
//
//        }
//    }
//
//    public function sss()
//    {
//        $water = WaterShop::order('id','asc')->select();
//        foreach ($water as $value)
//        {
//            $balance = User::where('id',$value->user_id)->value('score') + $value->money;
//            WaterShop::where('id',$value->id)->update(['balance' => $balance]);
//            User::where('id',$value->user_id)->setInc('score',$value->money);
//        }
//    }
//
//    public function ssss()
//    {
//        $data = WaterOrigin::order('id','asc')
//            ->whereIn('type',[WATER_ORIGIN_DYNAMIC,WATER_ORIGIN_ONE,WATER_ORIGIN_CONVERT_SHOP])
////            ->whereTime('create_time','>=','2019-12-17 00:00:00')
//            ->select();
//        foreach ($data as $value)
//        {
//            $balance = User::where('id',$value->user_id)->value('origin_dynamic') + $value->money;
//            WaterOrigin::where('id',$value->id)->update(['balance' => $balance]);
//            if ($value->money < 0) {
//                User::where('id',$value->user_id)->setDec('origin_dynamic',abs($value->money));
//            } else {
//                User::where('id',$value->user_id)->setInc('origin_dynamic',$value->money);
//            }
//
//        }
//    }
//
//    public function ssds()
//    {
//        $data = WaterOrigin::order('id','asc')->where('type',27)->select();
//        foreach ($data as $value)
//        {
//            if ($value->money > 0) {
//                WaterOrigin::where('id',$value->id)->update(['money' => '-'.$value->money]);
//            }
//        }
//    }
//
//    public function stockss()
//    {
//        $data = WaterStock::order('id','asc')->select();
//        foreach ($data as $value)
//        {
//            $balance = User::where('id',$value->user_id)->value('stock') + $value->money;
//            WaterStock::where('id',$value->id)->update(['balance' => $balance]);
//            if ($value->money < 0) {
//                    User::where('id',$value->user_id)->setDec('stock',abs($value->money));
//                } else {
//                    User::where('id',$value->user_id)->setInc('stock',$value->money);
//            }
//        }
//    }

//    public function duplicate()
//    {
//        $data = WaterStock::field('count(*) as count,user_id')->whereTime('create_time','>','2020-1-12 00:00:00')
//            ->whereTime('create_time','<','2020-1-13 00:00:00')
//            ->where('type',1)
//            ->group('user_id')
//            ->having('count > 1')
//            ->select();
//        print_r(collection($data)->toArray());
//    }


//    public function testss()
//    {
//        $data = User::field('id,pid')->order('id','desc')->select();
//        $i = 0;
//        foreach ($data as $value)
//        {
//            $i ++ ;
////            if ($i > 1) {
////                exit;
////            }
//            $result = [];
//            getParentsId($data,$value->id,$result,999999);
//
//            $haha = '-'.implode('-',$result);
//            User::where('id',$value->id)->update(['path' => $haha]);
//        }
//    }
//
//    public function test1()
//    {
//        $id = 3730;
//        $list = User::where('path','like','%-'.$id.'-%')->whereOr('path','like','%-'.$id)->field('id')->select();
//        print_r(collection($list)->toArray());
//    }
//
//    public function test(){
//        $data = User::field('id,pid')->order('id','desc')->select();
//        $result = [];
//        getParentsId($data,3730,$result,999999);
//
//        print_r($result);
//    }

    //用户指数余额与流水指数余额对比
    public function stockBalance()
    {
        $data = User::select();
        foreach ($data as $value) {
            $balance = WaterStock::where('user_id',$value->id)->order('id', 'desc')->value('balance');
            if (empty($balance)) {
                continue;
            }
            $stock = $value->stock;
            if (strval($stock) != $balance) {
                var_dump($stock);
                var_dump($balance);
                var_dump($value->id);
            }
        }
    }

    //动态余额与流水动态余额对比
    public function dynamicBalance()
    {
        $data = User::select();
        foreach ($data as $value) {

            $balance = WaterOrigin::whereIn('type',[
                WATER_ORIGIN_DYNAMIC,WATER_ORIGIN_ONE,WATER_ORIGIN_CONVERT_SHOP,WATER_ORIGIN_BUY_STOCK_DYNAMIC,
                WATER_ORIGIN_TRANSFER_IN,WATER_ORIGIN_TRANSFER_OUT,
            ])->where('user_id',$value->id)
                ->where('id','>',500)
                ->order('id', 'desc')->value('balance');
            if (empty($balance)) {
                continue;
            }
            $dynamic = $value->origin_dynamic + $value->origin_buy;
            if (strval($dynamic) != $balance) {
                var_dump($dynamic);
                var_dump($balance);
                var_dump($value->id);

            }
        }
    }

    public function dynamicDataCompare()
    {
        $data = WaterOrigin::order('id','asc');
        $data = User::select();
        foreach ($data as $value)
        {
            if($value->id == 15 || $value->id == 70) {
                continue;
            }
            $water_balance = WaterOrigin::where('id','>',195)->where('user_id',$value->id)->
            whereIn('type',[
                WATER_ORIGIN_DYNAMIC
                ,WATER_ORIGIN_ONE
                ,WATER_ORIGIN_CONVERT_SHOP
                ,WATER_ORIGIN_BUY_STOCK_DYNAMIC
                ,WATER_ORIGIN_TRANSFER_IN 
                ,WATER_ORIGIN_TRANSFER_OUT
            ])->select();
            $balance = 0;
            foreach ($water_balance as $value)
            {

                $balance = $balance + $value->money;
                if (strval($balance) != $value->balance ) {
                    var_dump($balance);
                    var_dump($value->balance);
                    var_dump($value->id);
//                    WaterOrigin::where('id',$value->id)->update(['balance' => $balance]);
//                    $sum = WaterOrigin::where('user_id',$value->user_id)->sum('money');
//                    User::where('id',$value->user_id)->update(['origin_dynamic' => $sum]);
//                    User::where('id',$value->user_id)->update(['stock'])
                }
            }
        }
//        $balance = 0;
//        foreach ($data as $value)
//            ->whereIn('type',[WATER_ORIGIN_DYNAMIC,WATER_ORIGIN_ONE,WATER_ORIGIN_CONVERT_SHOP])
////            ->whereTime('create_time','>=','2019-12-17 00:00:00')
//            ->select();
//        foreach ($data as $value)
//        {
//            $balance = User::where('id',$value->user_id)->value('origin_dynamic') + $value->money;
//            WaterOrigin::where('id',$value->id)->update(['balance' => $balance]);
//            if ($value->money < 0) {
//                User::where('id',$value->user_id)->setDec('origin_dynamic',abs($value->money));
//            } else {
//                User::where('id',$value->user_id)->setInc('origin_dynamic',$value->money);
//            }
//
//        }
    }

    public function stockDataCompare()
    {
        $data = User::select();
        $balance = 0;
        foreach ($data as $value)
        {
            $water_balance = WaterStock::where('user_id',$value->id)->where('type','<>',20)->select();
            $balance = 0;
            foreach ($water_balance as $value)
            {
                $balance = $balance + $value->money;
                if (strval($balance) != $value->balance) {
                    var_dump($balance);
                    var_dump($value->balance);
                    var_dump($value->id);
//                    WaterStock::where('id',$value->id)->update(['balance' => $balance]);
//                    $sum = WaterStock::where('user_id',$value->user_id)->sum('money');
//                    User::where('id',$value->user_id)->update(['stock' => $sum]);
//                    User::where('id',$value->user_id)->update(['stock'])
                }
            }
//            $water_balance = WaterStock::where('user_id',$value->id)->order('id','desc')->value('balance') ?? 0;
//            $balance += $water_balance;
        }
//        $user_sum = User::where('id','<>',0)->sum('stock');
//        $water_sum = WaterStock::where('id','<>',0)->sum('money');
//        var_dump($user_sum);
//        var_dump($water_sum);
//        var_dump($water_sum - $user_sum);
//        var_dump($balance);
    }

    /**
     * 产量
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bonusRelease()
    {
        $result = CrontabControl::where('create_time',date('Y-m-d'))->where('name','bonusRelease')->find();
        if (!empty($result)) {
            exit;
        }
        CrontabControl::create([
            'name' => 'bonusRelease',
            'create_time' => date('Y-m-d')
        ]);
        $data = OrderStock::where('status',10)->order('id','asc')->select();
        $rule = RuleStatic::select();
//        $rule_max_from = RuleStatic::order('id','desc')->value('from');
        $user_info = [];
//        $user_num = [];
        $stock_add = [];
//        $stock_cal = [];
        foreach ($data as $value)
        {
            $profit_rate = 0;
            if (empty($user_info[$value->user_id])) {
                $user_balance = User::field('origin_static,stock')->where('id',$value->user_id)->find();
                $user_info[$value->user_id] = $user_balance->origin_static + $user_balance->stock;
//                $user_info[$value->user_id] = OrderStock::where('user_id',$value->user_id)->sum('balance');
            }
            foreach ($rule as $val) {
                if ($user_info[$value->user_id] >= $val->from && $user_info[$value->user_id] < $val->to) {
                    $profit_rate = $val->profit;
                    break;
                }
            }

            if (!empty($profit_rate)) {
//                $profit = ceil($profit_rate * $value->balance / 100);
                //如果超过30000最高则减少收入
//                $stock_cal[$value->user_id] = $stock_cal[$value->user_id] ?? 0;
//                if ($stock_cal[$value->user_id] == $rule_max_from) {
//                    continue;
//                }
//                if ($stock_cal[$value->user_id] + $value->balance > $rule_max_from) {
//                    $value->balance = $rule_max_from - $stock_cal[$value->user_id];
//                }

                $profit = $profit_rate * $value->balance / 100;
                $stock_add[$value->id] = $stock_add[$value->id] ?? 0;
                $stock_add[$value->id] += $profit;

                $user_num[$value->user_id] = $user_num[$value->user_id] ?? 0;
                $user_num[$value->user_id] += $profit;

//                $stock_cal[$value->user_id] += $value->balance;
            }
        }

        foreach ($stock_add as $id => $profit)
        {
            $profit = round($profit,2);
            OrderStock::where('id',$id)->setInc('balance' , $profit);
        }

        $data = User::field('id,origin_static,stock')->where('origin_static','<>',0)->whereOr('stock','<>',0)->select();
        foreach ($data as $value)
        {
            $profit_rate = 0;
            $balance = round($value->origin_static + $value->stock,2);
            foreach ($rule as $val) {
                if ($balance >= $val->from && $balance < $val->to) {
                    $profit_rate = $val->profit;
                    break;
                }
            }
            if (!empty($profit_rate)){
                $profit = round($profit_rate * $balance / 100, 2);
                WaterStock::create([
                    'user_id' => $value->id,
                    'type' => WATER_STOCK_INTEREST,
                    'money' => $profit,
                    'balance' => $value->stock + $profit
                ]);
                User::where('id',$value->id)->setInc('stock',$profit);
            }
        }

//        foreach ($user_num as $user_id => $profit) {
//            $profit = round($profit, 2);
//            $balance = User::where('id',$user_id)->value('stock');
//            WaterStock::create([
//                'user_id' => $user_id,
//                'type' => WATER_STOCK_INTEREST,
//                'money' => $profit,
//                'balance' => $balance + $profit
//            ]);
//            User::where('id',$user_id)->setInc('stock',$profit);
//        }
    }

    /**
     * 产值释放
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function bonusStockRelease()
    {
        $result = CrontabControl::where('create_time',date('Y-m-d'))->where('name','bonusStockRelease')->find();
        if (!empty($result)) {
            exit;
        }
        CrontabControl::create([
            'name' => 'bonusStockRelease',
            'create_time' => date('Y-m-d')
        ]);
        $produce_profit = getConfigValue('produce_profit');

        $user_num = [];
        $data = OrderStock::where('status',10)->where('balance','<>',0)->select();
        foreach ($data as $value)
        {
//            $profit = ceil($value->balance * $produce_profit / 100);
            $profit = round($value->balance * $produce_profit / 100,2);
            if (!empty($profit)) {
                $user_num[$value->user_id] = $user_num[$value->user_id] ?? 0;
                $user_num[$value->user_id] += $profit;
                OrderStock::where('id',$value->id)->setDec('balance',$profit);
                OrderStock::where('id',$value->id)->setInc('money',$profit);
            }
        }

//        $user_data = User::where('stock','<>',0)->select();
//        foreach ($user_data as $value)
//        {
//            WaterStock::create([
//                'user_id' => $user_id,
//                'type' => WATER_STOCK_RELEASE,
//                'money' => '-'.$profit,
//                'balance' => $stock_balance - $profit
//            ]);
//
//            WaterStock::create([
////                'detail_id' => $order->id,
//                'user_id' => $user_id,
//                'type' => WATER_STOCK_RELEASE_PROFIT,
//                'money' => $profit,
//                'balance' => $origin_static + $profit
//            ]);
//
//            User::where('id',$user_id)->setInc('origin_static',$profit);
//            User::where('id',$user_id)->setDec('stock',$profit);
//        }

        foreach ($user_num as $user_id => $profit) {
            try {
                $stock_balance = User::where('id',$user_id)->value('stock');
                $origin_static = User::where('id',$user_id)->value('origin_static');
                WaterStock::create([
                    'user_id' => $user_id,
                    'type' => WATER_STOCK_RELEASE,
                    'money' => '-'.$profit,
                    'balance' => $stock_balance - $profit
                ]);

                WaterStock::create([
//                'detail_id' => $order->id,
                    'user_id' => $user_id,
                    'type' => WATER_STOCK_RELEASE_PROFIT,
                    'money' => $profit,
                    'balance' => $origin_static + $profit
                ]);

                User::where('id',$user_id)->setInc('origin_static',$profit);
                User::where('id',$user_id)->setDec('stock',$profit);
            }catch (\Exception $e) {
                Log::error('bonusStockRelease error:'.$e->getMessage().',user_id:'.$user_id,',profit:'.$profit);
            }

        }

    }

    /**
     * 分享收益
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function dynamicProfit()
    {
        $result = CrontabControl::where('create_time',date('Y-m-d'))->where('name','dynamicProfit')->find();
        if (!empty($result)) {
            exit;
        }
        CrontabControl::create([
            'name' => 'dynamicProfit',
            'create_time' => date('Y-m-d')
        ]);
        $data = WaterStock::whereTime('create_time','today')->where('type',WATER_STOCK_INTEREST)->select();
        $user_num = [];
        $rule = RuleDynamic::select();
        $user_data = User::field('id,pid')->select();
        $max = RuleDynamic::order('to','desc')->value('to');
        $min_num = RuleStatic::order('to','asc')->value('to');
        //特殊处理
        $spec = RuleStatic::where('id',2)->find();
        $spec_from = $spec->from;
        $spec_to = $spec->to;

        $user_info = [];
        foreach ($data as $value)
        {
            $result = [];
            getParents($user_data, $value->user_id,$result, 9999999);
            $reduce_level = 0;
            foreach ($result as $user) {
                $pid_num = User::where('pid',$user->id)->count();
                $can_level = ($pid_num * 2) > $max ? $max :($pid_num * 2);
                $user->level = $user->level - $reduce_level;

                if ($user->level > $can_level) {
                    $reduce_level += 1;
                    continue;
                }
                if (empty($user_info[$user->id])) {
                    $user_balance = User::field('origin_static,stock')->where('id',$user->id)->find();
                    $user_info[$user->id] = $user_balance->origin_static + $user_balance->stock;
                }
//                $count_stock = OrderStock::where('user_id',$user->id)->sum('balance');

                if ($user_info[$user->id] < $min_num) {
                    $reduce_level += 1;
                    continue;
                }
                //特别处理
                if ($user_info[$user->id] >= $spec_from && $user_info[$user->id] < $spec_to) {
                    if ($user->level > 3) {
                        $reduce_level += 1;
                        continue;
                    }
                }
                $profit_rate = 0;

                foreach ($rule as $val)
                {
                    if ($user->level >= $val->from && $user->level <= $val->to) {
                        $profit_rate = $val->profit;
                        break;
                    }
                }

                if (!empty($profit_rate)) {
                    $user_num[$user->id] = $user_num[$user->id] ?? 0;
//                    $money = ceil($profit_rate * $value->money / 100);
                    $money = $profit_rate * $value->money / 100;
                    $user_num[$user->id] += $money;
//                    WaterOriginDetail::create([
//                        'relate_user_id' =>$value->user_id,
//                        'user_id' => $user->id,
//                        'type' => WATER_ORIGIN_DYNAMIC,
//                        'money' => $money
//                    ]);
                }
            }
        }

        foreach ($user_num as $user_id => $profit)
        {
            $profit = round($profit,2);
            $balance = getDynamicBalance($user_id);
            WaterOrigin::create([
                'user_id' => $user_id,
                'type' => WATER_ORIGIN_DYNAMIC,
                'money' => $profit,
                'balance' => $balance + $profit
            ]);
            incBalance($user_id, 'origin_dynamic',$profit);
        }

    }

    /**
     * 社区收益
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function teamProfit()
    {
        $result = CrontabControl::where('create_time',date('Y-m-d'))->where('name','teamProfit')->find();
        if (!empty($result)) {
            exit;
        }
        CrontabControl::create([
            'name' => 'teamProfit',
            'create_time' => date('Y-m-d')
        ]);
        $rule_user_level = RuleUserLevel::select();
        $rule_user_level_data = [];
        foreach ($rule_user_level as $value) {
            $rule_user_level_data[$value->id] = $value;
        }

        $user_data = \app\admin\model\User::field('id,pid,rule_user_level_id')->select();
        $user_num = [];
        foreach ($user_data as $value)
        {
            $profit = WaterStock::whereTime('create_time','today')
                ->where('user_id',$value->id)
                ->where("type",WATER_STOCK_INTEREST)->value('money');
            if (!empty($profit)) {
                $level = $value->rule_user_level_id;

                $profit_rate = $rule_user_level_data[$value->rule_user_level_id]['profit'] ?? 0;
                $result = [];
                getParents($user_data, $value->id,$result, 999999999);
                foreach ($result as $val)
                {
                    if ($val->rule_user_level_id != 0 && $val->rule_user_level_id > $level)
                    {

                        $profit_rate = $rule_user_level_data[$val->rule_user_level_id]['profit'] - $profit_rate;
                        if ($profit_rate > 0) {
                            $user_num[$val->id] = $user_num[$val->id] ?? 0;
                            $profit_money = $profit_rate * $profit / 100;
                            $user_num[$val->id] += $profit_money;

                        }

                        $level = $val->level;
                    }
                }
            }
        }


        foreach ($user_num as $user_id => $profit) {
            $profit = round($profit,2);
            $level = User::where('id',$user_id)->value('rule_user_level_id');
            if ($level == 1) {
                $type = WATER_ORIGIN_ONE;
            } elseif ($level == 2) {
                $type = WATER_ORIGIN_TWO;
            } elseif ($level == 3) {
                $type = WATER_ORIGIN_THREE;
            } else{
                continue;
            }
            $balance = getDynamicBalance($user_id);
            WaterOrigin::create([
                'user_id' => $user_id,
                'type' => $type,
                'money' => $profit,
                'balance' => $balance + $profit
            ]);
            incBalance($user_id, 'origin_dynamic',$profit);
        }

        $user_data = \app\admin\model\User::field('id,pid,rule_user_level_id')->where('rule_user_level_id',4)->select();
        $total = WaterStock::whereTime('create_time','today')
            ->where("type",WATER_STOCK_INTEREST)->sum('money');

        $rate = $rule_user_level_data[4]['equal_profit'] ?? 0;

        if (!empty($user_data) && !empty($total)) {
//            $average_money = ceil($total / count($user_data) * $rate / 100);
            $average_money = round($total / count($user_data) * $rate / 100,2);

            foreach ($user_data as $value)
            {
                $balance = getDynamicBalance($value->id);
                WaterOrigin::create([
                    'user_id' => $value->id,
                    'type' => WATER_ORIGIN_FOUR,
                    'money' => $average_money,
                    'balance' => $balance + $average_money
                ]);
                incBalance($value->id, 'origin_dynamic',$average_money);
            }
        }
    }

    /**
     * 商城积分转换
     */
    public function transferShop()
    {
        $result = CrontabControl::where('create_time',date('Y-m-d'))->where('name','transferShop')->find();
        if (!empty($result)) {
            exit;
        }
        CrontabControl::create([
            'name' => 'transferShop',
            'create_time' => date('Y-m-d')
        ]);
        $static_shop_profit = getConfigValue('static_shop_profit');
        $dynamic_shop_profit = getConfigValue('dynamic_shop_profit');

        $water_origin = WaterStock::field('user_id,create_time,money,type')->whereTime('create_time','today')
            ->where('type',WATER_STOCK_INTEREST)->select();
        foreach ($water_origin as $value) {
            try {
                $score_balance = User::where('id',$value->user_id)->value('score');
                $stock = User::where('id',$value->user_id)->value('stock');
//                $profit = ceil($static_shop_profit * $value->money / 100);
                $profit = round($static_shop_profit * $value->money / 100,2);
                decBalance($value->user_id,'stock',$profit);
                WaterStock::create([
                    'user_id' => $value->user_id,
                    'type' => WATER_STOCK_CONVERT_SHOP,
                    'money' => '-'.$profit,
                    'balance' => $stock - $profit,
                ]);
                WaterShop::create([
                    'user_id' => $value->user_id,
                    'type' => WATER_SHOP_STATIC,
                    'money' => $profit,
                    'balance' => $score_balance + $profit
                ]);

                incBalance($value->user_id,'score',$profit);

            }catch (\Exception $e) {
                Log::error('Transfer Shop Static Error:'.$e->getMessage());
            }
        }

        $user_num = [];
        $water_origin = WaterOrigin::field('user_id,money,type')->whereTime('create_time','today')
            ->whereIn('type',[WATER_ORIGIN_DYNAMIC,WATER_ORIGIN_ONE,WATER_ORIGIN_TWO,WATER_ORIGIN_THREE,WATER_ORIGIN_FOUR])->select();
        foreach ($water_origin as $value) {
            $profit = $dynamic_shop_profit * $value->money / 100;

            $user_num[$value->user_id] = $user_num[$value->user_id] ?? 0;
            $user_num[$value->user_id] += $profit;
        }

        foreach ($user_num as $user_id =>$value) {
            try{
                $value = round($value,2);
                $score_balance = User::where('id',$user_id)->value('score');
                $origin_dynamic_balance = User::where('id',$user_id)->value('origin_dynamic');
//                $value = ceil($value);
                decBalance($user_id,'origin_dynamic',$value);
                WaterOrigin::create([
                    'user_id' => $user_id,
                    'type' => WATER_ORIGIN_CONVERT_SHOP,
                    'money' => '-'.$value,
                    'balance' => $origin_dynamic_balance - $value
                ]);
                WaterShop::create([
                    'user_id' => $user_id,
                    'type' => WATER_SHOP_DYNAMIC,
                    'money' => $value,
                    'balance' => $score_balance + $value
                ]);

                incBalance($user_id,'score',$value);

            }catch (\Exception $e) {
                Log::error('Transfer Shop Dynamic Error:'.$e->getMessage());
            }

        }

    }

    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        $data = OrderOrigin::field('order_market_id,status,create_time,id,number')->where('status',1)->select();
        $countdown = getConfigValue('pay_order_time');
        $time = time();
        foreach ($data as $value) {
            $pay_time = strtotime($value->create_time) + $countdown;
            if ($time > $pay_time) {
                OrderOrigin::where('id',$value->id)->update(['status' => 0]);
                OrderMarket::where('id',$value->order_market_id)->setInc('balance', $value->number);
            }
        }

        $countdown = getConfigValue('release_order_time');
        $deal_bonus_fee = getConfigValue('deal_bonus_fee');
        $data_list = OrderOrigin::field('order_market_id,user_id,status,pay_time,id,number')->where('status',2)->select();
        foreach ($data_list as $data)
        {
            $release_time = strtotime($data->pay_time) + $countdown;
            if ($time >= $release_time) {
                $result = OrderOrigin::where('id', $data->id)->update(['status' => 3]);
                if (empty($result)) {
                    continue;
                }
                try {
                    Db::transaction(function () use ($data,$deal_bonus_fee) {
                        $to_user_origin_buy_balance = getDynamicBalance($data->user_id);
                        \app\common\model\User::where('id', $data->user_id)->setInc('origin_buy', $data->number);
                        WaterOrigin::create([
                            'detail_id' => $data->id,
                            'user_id' => $data->user_id,
                            'type' => WATER_ORIGIN_BUY,
                            'money' => $data->number,
                            'balance' => $to_user_origin_buy_balance + $data->number
                        ]);
                        OrderOrigin::where('id', $data->id)->update(['status' => 3, 'finish_time' => datetime()]);

                        $market_data = OrderMarket::where('id', $data->order_market_id)->find();

                        $handling_rate = $market_data->handling_rate;
                        //成功扣除手续费
                        $handling = round($handling_rate * $data->number / 100, 2);
                        OrderMarket::where('id', $data->order_market_id)->setDec('handling_fee', $handling);

                        //奖励买家
                        $handling = round($deal_bonus_fee * $data->number / 100, 2);
                        if (!empty($handling) && !empty($this->auth->getUser()->trade_center)) {
                            $to_user_origin_dynamic_balance = getDynamicBalance($data->user_id);
                            //同时需要奖励玩家交易费用
                            WaterOrigin::create([
                                'detail_id' => $data->id,
                                'user_id' => $data->user_id,
                                'type' => WATER_ORIGIN_BUY_BONUS,
                                'money' => $handling,
                                'balance' => $to_user_origin_dynamic_balance + $handling
                            ]);
                            incBalance($data->user_id, 'origin_dynamic', $handling);
                        }

                        //如果市场单的余额为0，置为下架
                        $exist = OrderOrigin::where('order_market_id', $market_data->id)->whereIn('status', '1,2')->find();
                        if ($market_data->balance == 0 && empty($exist)) {
                            OrderMarket::where('id', $market_data->id)->update(['status' => 0, 'finish_time' => datetime()]);
                        }
                    });
                } catch (\Exception $e) {
                    Log::error('Release Error:' . $e->getMessage());
                    OrderOrigin::where('id', $data->id)->update(['status' => 2]);
                }
            }
        }
    }

    /**
     * 每日重制系统参数
     */
    public function resetSystem()
    {
        $result = CrontabControl::where('create_time',date('Y-m-d'))->where('name','resetSystem')->find();
        if (!empty($result)) {
            exit;
        }
        CrontabControl::create([
            'name' => 'resetSystem',
            'create_time' => date('Y-m-d')
        ]);
        $base = getConfigValue('publish_base');
        $stock = getConfigValue('publish_stock');
        MiningStatistics::create([
            'base' => $base,
            'stock' => $stock,
            'create_time' => date('Y-m-d')
        ]);
        CarRule::where('id','<>',0)->update(['status' => 0]);
    }

    /**
     * 计算用户算力,升级用户
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateCalculation()
    {
        $user_data = \app\admin\model\User::field('id,pid,level')->order('id','desc')->select();
        $level_num = UserLevelRule::order('id','desc')->column('next_num','id');
        foreach ($user_data as $value) {
//            $child =  \app\admin\model\User::join('mine_user m','m.user_id = user.id')
//                ->field('user.id,pid,level,count(*) as haha')->where('pid',$value->id)
//                ->order('level','desc')->group('level')->select();

            $child =  \app\admin\model\User::field('id,pid,level,count(*) as haha')->where('pid',$value->id)
                ->group('level')->select();

            $tmp = [];
            foreach ($child as $val) {
                $tmp[$val->level] = $val->haha;
            }

            foreach ($level_num as $key => $val)
            {
                $cal_level = $key + 1;
                if (isset($tmp[$key]) && $tmp[$key] >= $level_num[$cal_level])
                {
                    $child_id= User::where('pid',$value->id)->where('level',$key)->column('id');

                    $i = 0;
                    foreach ($child_id as $c_id) {
                        $exist = MineUser::where('user_id',$c_id)->where('status','<>',MINE_USER_WAITE)->value('id');
                        if (empty($exist)) {
                            $i ++;
                        }
                    }

                    if ($i >= $level_num[$cal_level]) {
                        User::where('id',$value->id)->update(['level' => $key + 1]);
                        break;
                    }
                }
            }

        }
    }


    public function statementProperty()
    {
        $origin_recharge = User::where('status','normal')->sum('origin_recharge');
        $origin_static = User::where('status','normal')->sum('origin_static');
        $origin_dynamic = User::where('status','normal')->sum('origin_dynamic');
        $origin_buy = User::where('status','normal')->sum('origin_buy');
        $base = User::where('status','normal')->sum('base');
        $stock = User::where('status','normal')->sum('stock');
        $score = User::where('status','normal')->sum('score');
        $create_time = date('Y-m-d');
        StatementProperty::create(compact('origin_dynamic','origin_recharge','origin_static','origin_buy','base','stock','score','create_time'));
    }

    /**
     * 发车管理
     */
    public function carPublish()
    {
        $data = CarRule::where('status',0)->where('start_time','<=',datetime(time(),'H:i:s'))->select();
        foreach ($data as $value) {
            try{
                for ($i = 0;$i < $value->car_num; $i++) {
                    Car::create([
                        'num' => $value->people_num,
                        'total' => $value->people_num,
                    ]);
                }
            }catch (\Exception $e) {
                echo $e->getMessage();
                Log::error($e->getMessage());
            }
            CarRule::where('id',$value->id)->update(['status'=>1]);
        }
    }
    /**
     * 车辆到站
     */
    public function carArrive()
    {
        $data = Car::where('status',2)->whereTime('finish_time','<',datetime())->select();
        foreach ($data as $value)
        {
            $car_id = $value->id;
            $car_user_data = CarUser::where('car_id',$car_id)->select();
            foreach ($car_user_data as $val){
                $user_balance = User::where('id',$val->user_id)->value('coin');
                $first_balance = $user_balance + $val->coin2;
                $second_balance = $first_balance + $val->gas;
                incBalance($val->user_id,'coin', ($val->coin2 +  $val->gas));
                WaterCoin::create([
                    'detail_id' => $car_id,
                    'user_id' => $val->user_id,
                    'type' => WATER_COIN_CAR_RECHARGE,
                    'money' => $val->coin2,
                    'balance' => $first_balance,
                ]);
                WaterCoin::create([
                    'detail_id' =>$car_id,
                    'user_id' => $val->user_id,
                    'type' => WATER_COIN_CAR_RETURN,
                    'money' => $val->gas,
                    'balance' => $second_balance,
                ]);
            }
            Car::where('id',$value->id)->update(['status' => 3]);

        }
    }

    /**
     * 充币旧版
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function recharge()
//    {
//        $data = Intoken::where('status',0)->select();
//        foreach ($data as $value) {
//            $balance = getUserValue($value->uid,'origin_recharge');
//            WaterOrigin::create([
//                    'user_id' => $value->uid,
//                    'type' => MINING_WATER_RECHARGE,
//                    'money' => $value->value,
//                    'balance' => $balance + $value->value
//            ]);
//            Intoken::where('id',$value->id)->update(['status' => 1]);
//            $result = User::where('id',$value->uid)->setInc('origin_recharge',$value->value);
//        }
//    }

    /**
     * 提币,旧版
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
//    public function withdraw()
//    {
//        $data = Outtoken::whereIn('status',[2,3])->select();
//        foreach ($data as $value) {
//            try {
//                if ($value->status == 2) {
//                    MiningWithdraw::where('hash',$value->txhash)->where('user_id',$value->uid)->update(['status' => 4]);
//                    Outtoken::where('id',$value->id)->update(['status' => 4]);
//                } else {
//                    $data = MiningWithdraw::where('hash',$value->txhash)->where('user_id',$value->uid)->find();
//                    MiningWithdraw::where('hash',$value->txhash)->where('user_id',$value->uid)->update(['status' => 5]);
//                    $balance = getUserValue($data->user_id,'origin_static');
//                    WaterStock::create([
//                        'detail_id' => $data->id,
//                        'user_id' => $data->user_id,
//                        'type' => WATER_STOCK_WITHDRAW_REJECT,
//                        //                'coin_type' => $coin_type,
//                        'money' => $data->number,
//                        'balance' => $balance + $data->number
//                    ]);
//                    incBalance($data->user_id,'origin_static',$data->number);
//                    Outtoken::where('id',$value->id)->update(['status' => 5]);
//                }
//            }catch (\Exception $e) {
//                Log::error('WithDraw Error:'.$e->getMessage());
//            }
//
//        }
//    }
    public function withdraw()
    {
        $data = MiningWithdraw::whereIn('status',1)->select();
        foreach ($data as $value) {
            try{
                $block_data = WalletTransferOut::where('id',$value->detail_id)->where('status','<>',5)->find();
                if (empty($block_data)) {
                    continue;
                }
                if ($block_data->status == 1) {
                    MiningWithdraw::where('id',$value->id)->update(['status' => 3]);
                } else if ($block_data->status == 2) {
                    MiningWithdraw::where('id',$value->id)->update(['status' => 4]);
                    WaterCoin::create([
                        'detail_id' => $value->id,
                        'user_id' => $value->user_id,
                        'type' => WATER_COIN_WITHDRAW_REJECT,
                        'money' => $value->number,
                        'balance' => getUserValue($value->user_id,'coin') + $value->number,
                        'mark' => $value->txid ?? ''
                    ]);
                    incBalance($value->user_id,'coin',$value->number);
                }
            }catch (\Exception $e){
                Log::error('Withdraw Error:'.$e->getMessage());
            }

        }
    }

    public function recharge()
    {
        $data = WalletTransferIn::where('sendstatus',0)->where('status',1)->select();
        foreach ($data as $value) {
            if ($value->coinid == 1) {
                $money_str = 'coin';
            }
            try{
                $balance = getUserValue($value->user_id,$money_str);
                WaterCoin::create([
                    'detail_id' => $value->id,
                    'user_id' => $value->user_id,
                    'type' => WATER_COIN_RECHARGE,
                    'money' => $value->num,
                    'balance' => $balance + $value->num,
                    'mark' => $value->txid
                ]);
                WalletTransferIn::where('id',$value->id)->update(['sendstatus' => 1]);
                incBalance($value->user_id,$money_str,$value->num);
            }catch (\Exception $e) {
                Log::error('Recharge Error:'.$e->getMessage());
            }

        }
    }
}

<?php

namespace app\api\controller;

use app\admin\model\OrderMarket;
use app\admin\model\OrderOrigin;
use app\admin\model\UserPayinfo;
use app\admin\model\WaterOrigin;
use app\common\controller\Api;
use app\common\model\Area;
use think\Db;
use think\Env;
use think\Log;

/**
 * 首页接口
 */
class Market extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];


    /**
     * 市场首页
     */
    public function index()
    {
        $type = $this->request->get('type') ?? 0;
        $user_id = $this->auth->getUser()->id;
        $publish_origin_rate = getConfigValue('publish_origin_rate');

        $balance = $this->auth->getUser()->origin_static;
        $enable_sell = $balance;
        if (!empty($publish_origin_rate)) {
            $buy_origin = OrderOrigin::where('user_id',$user_id)->where('status',3)->sum('number');

            $sell_origin_1 = OrderMarket::where('user_id',$user_id)->where('status',1)->sum('balance');
            $sell_origin_2 = OrderOrigin::where('sell_user_id',$user_id)->where('status','<>',0)->sum('number');
            $sell_origin = $sell_origin_1 + $sell_origin_2;

            $buy_origin = $buy_origin * $publish_origin_rate;

            $enable_sell = $buy_origin - $sell_origin;

            if ($balance < $enable_sell) {
                $enable_sell = $balance;
            }
        }

        if ($enable_sell < 0) {
            $enable_sell = 0;
        }
        $data['nickname'] = $this->auth->getUser()->nickname;
        $data['sell_enable'] = $enable_sell;
        $data['avatar'] = $this->auth->getUser()->avatar;
//        $data['my'] = OrderMarket::where('user_id',$this->auth->getUser()->id)->where('status',1)->count();

//        $user_data = \app\common\model\User::field('id,pid,trade_center')->where('trade_center',0)->select();
//        $result = [];
//        getChildId($user_data, $this->auth->getUser()->id, $result, -1);
//
//        $data['team'] = OrderMarket::whereIn('user_id',$result)->where('status',1)->count();;
        $data['trade_center'] = $this->auth->getUser()->trade_center;
        $data['real_auth'] = $this->auth->getUser()->real_auth;

        $this->success('ok',$data);
    }

    /**
     * 集市
     */
    public function market()
    {
        $page = $this->request->get('page') ?? 1;

        $data = OrderMarket::where('status',1)->where('balance','<>',0)
            ->order('price asc,create_time desc')->page($page, 10)->select();

        foreach ($data as $value) {
            $value->nicname = getUserValue($value->user_id, 'nickname');
            $value->avatar = getUserValue($value->user_id, 'avatar');
            $value->amount = $value->balance;
            $value->total_price = $value->balance * $value->price;
        }
        $this->success('ok',$data);
    }

    /**
     * 订单确定
     */
    public function orderConfirm()
    {
        if (empty($this->request->post())) {
            $id = $this->request->get('id');
            $order_data = OrderMarket::field('id,price,balance as amount')->where('id',$id)->where('status',1)->find();
            $this->success('ok',$order_data);;
        }
        if ($this->auth->getUser()->real_auth != 2) {
            $this->error('请先通过实名认证！');
        }

        $id = $this->request->post('id');
        $number = $this->request->post('number');

        if ($number % 100 != 0) {
            $this->error('购买必须是100的倍数！');
        }

        $order_data = OrderMarket::field('id,price,balance,user_id')->where('id',$id)->where('status',1)->findOrFail();

        if ($order_data->user_id == $this->auth->getUser()->id) {
            $this->error('自己不能购买自己挂卖的订单');
        }
        if ($number > $order_data->balance) {
            $this->error('挂卖订单原酒数量不足！');
        }
        if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
            $this->error('交易密码错误！');
        }
        $order = OrderOrigin::create([
            'order_number' => generate_order_number(),
            'order_market_id' => $order_data->id,
            'sell_user_id' => $order_data->user_id,
            'user_id' => $this->auth->getUser()->id,
            'price' => $order_data->price,
            'number' => $number,
            'money' => $number * $order_data->price,
        ]);

        OrderMarket::where('id',$id)->where('status',1)->setDec('balance', $number);
        $this->success('下单成功',$order->id);
    }

    /**
     * 订单详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function orderDetail()
    {
        $this->loadadminlang('order/order_origin');

        $id = $this->request->get('id');
        $data = OrderOrigin::where('id',$id)->where(function($query) {
//            $query->where('user_id', $this->auth->getUser()->id)->whereOr('sell_user_id', $this->auth->getUser()->id);
        })->findOrFail();


        $marker_order_data = OrderMarket::where('id',$data->order_market_id)
//            ->where('status',1)
            ->find();
        if (empty($marker_order_data)) {
            $this->error('订单可能已经下架！');
        }

        $countdown = getConfigValue('pay_order_time');
        $data->countdown = strtotime($data->create_time) + $countdown;
        if ($data->status == 2) {
            $data->countdown = strtotime($data->pay_time) + $countdown;
        }
        $sell_user_id = $marker_order_data->user_id;
        $data->nickname = getUserValue($sell_user_id,'nickname');
        $data->mobile = getUserValue($sell_user_id,'mobile');
        $data->buy_nickname = getUserValue($data->user_id,'nickname');
        $data->buy_mobile = getUserValue($data->user_id,'mobile');
        $payinfo = UserPayinfo::where('user_id',$sell_user_id)->where('status',1)->select();

        $tmp = [];
        $result = [];
        foreach ($payinfo as $value)
        {
            $tmp['id'] = $value->id;
            $tmp['bank'] = $value->bank;
            $tmp['name'] = $value->name;
            $tmp['account'] = $value->account;
            $tmp['image'] = $value->image;
            $tmp['type'] = $value->type;
            if ($value->type == 'bank') {
                $tmp['type_text'] = '银行卡';
            } else if ($value->type == 'alipay') {
                $tmp['type_text'] = '支付宝';
            } else if ($value->type == 'wechat') {
                $tmp['type_text'] = '微信';
            }
            $result[] = $tmp;
        }
        $data['payinfo'] = $result;
        $this->success('ok',$data);
    }

    /**
     * 支付订单
     */
    public function orderPay()
    {
        $id = $this->request->post('id');
        $data = OrderOrigin::where('id',$id)->where('status',1)->where('user_id',$this->auth->getUser()->id)->find();
        if (empty($data)) {
            $this->error('订单已经支付或是订单已取消');
        }

        $file = $this->request->file('image');
//        if (!empty($file)) {
//            $this->error('未上传文件');
//        }
        $image = '';
        if (!empty($file)) {
            $info = $file->validate(['size' => 4194204])->move(PAYMENT_PATH);
            if (!$info) {
                $this->error($file->getError());
            }
            $image = '/'.PAYMENT_PATH . $info->getSaveName();
        }

//        $countdown = getConfigValue('pay_order_time');
//        $pay_time = strtotime($data->create_time) + $countdown;
//        if (time() > $pay_time) {
//            $this->error('已过支付时间！');
//        }
        OrderOrigin::where('id',$id)->update(['status' => 2,'pay_time'=>datetime(),'payment'=> $image]);

        $this->success('支付成功！');
    }

    /**
     * 订单放行
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function releaseOrder()
    {
        $id = $this->request->post('id');

        $data = OrderOrigin::where('id',$id)->where('status',2)
            ->where('sell_user_id',$this->auth->getUser()->id)
            ->find();
        if (empty($data)) {
            $this->error('放行的订单不存在，请确定订单是否已支付或是已经放行！');
        }

        $deal_password = $this->request->post('deal_password');
        if ($this->auth->getUser()->deal_password != $deal_password) {
            $this->error('支付密码错误！');
        }
        $result = OrderOrigin::where('id',$id)->update(['status' => 3]);
        if (empty($result)) {
            $this->error('该订单可能已经放行！');
        }
        try {
            Db::transaction(function () use ($id,$data){
                $to_user_origin_buy_balance = getDynamicBalance($data->user_id);
                \app\common\model\User::where('id',$data->user_id)->setInc('origin_buy',$data->number);
                WaterOrigin::create([
                    'detail_id' => $id,
                    'user_id' => $data->user_id,
                    'type' => WATER_ORIGIN_BUY,
                    'money' => $data->number,
                    'balance'=> $to_user_origin_buy_balance + $data->number
                ]);
                OrderOrigin::where('id', $data->id)->update(['status' => 3, 'finish_time' => datetime()]);
                $market_data = OrderMarket::where('id',$data->order_market_id)->find();

                $handling_rate = $market_data->handling_rate;
                //成功扣除手续费
                $handling = round($handling_rate * $data->number / 100,2);
                OrderMarket::where('id',$data->order_market_id)->setDec('handling_fee',$handling);

                //奖励买家
                $deal_bonus_fee = getConfigValue('deal_bonus_fee');
                $handling = round($deal_bonus_fee * $data->number / 100,2);
                if (!empty($handling) && !empty($this->auth->getUser()->trade_center)) {
                    $to_user_origin_dynamic_balance = getDynamicBalance($data->user_id);
                    //同时需要奖励玩家交易费用
                    WaterOrigin::create([
                        'detail_id' => $id,
                        'user_id' => $data->user_id,
                        'type' => WATER_ORIGIN_BUY_BONUS,
                        'money' => $handling,
                        'balance' => $to_user_origin_dynamic_balance + $handling
                    ]);
                    incBalance($data->user_id,'origin_dynamic',$handling);
                }

                //如果市场单的余额为0，置为下架
                $exist = OrderOrigin::where('order_market_id',$market_data->id)->whereIn('status','1,2')->find();
                if ($market_data->balance == 0 && empty($exist)) {
                    OrderMarket::where('id',$market_data->id)->update(['status' => 2,'finish_time' => datetime()]);
                }
            });
        }catch (\Exception $e) {
            OrderOrigin::where('id',$id)->update(['status' => 2]);
            Log::error('Release Error:'.$e->getMessage());
            $this->error('放行失败！');
        }

        $this->success('放行成功！');
    }

    /**
     * 我要挂卖页面数据
     */
    public function publishOrder()
    {
        $user_id = $this->auth->getUser()->id;
        $handling_rate = getConfigValue('deal_handling_fee');
        $lowest = getConfigValue('publish_lowest_price');
        $highest = getConfigValue('publish_highest_price');

        if (empty($this->request->post())) {

//            $lowest = OrderMarket::where('status',1)->order('price','asd')->value('price') ?? 0;

            $user_origin_static = $this->auth->getUser()->origin_static;

            $data = [
                'lowest' => $lowest,
                'highest' => $highest,
                'enable_sell' => $user_origin_static,
                'handling_fee' => $handling_rate
            ];
            $this->success('ok',$data);;
        }

        $begin_time = getConfigValue('publish_trade_time_begin');
        $end_time = getConfigValue('publish_trade_time_end');

        if (time() < strtotime($begin_time) || time() > strtotime($end_time)) {
            $this->error('对不起，挂卖开放时间为:'.$begin_time.'至'.$end_time.'。');
        }

        $payinfo = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status','<>',2)->find();
        if (empty($payinfo)) {
            $this->error('未设置收款方式，请先设置收款方式。');
        }
        $exist = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status','<>',0)->find();
        if (empty($exist)) {
            $this->error('你的收款方式还在审核中，请稍后再尝试！');
        }
        if ($this->auth->getUser()->real_auth != 2) {
            $this->error('请先通过实名认证！');
        }
        $price = $this->request->post('price');
        $number = $this->request->post('number');

        if ($number % 100 != 0) {
            $this->error('挂卖必须是100的倍数！');
        }
        $exist = OrderMarket::where('user_id',$user_id)->where('status',1)->find();
        if (!empty($exist)) {
            $this->error('你有一笔挂卖单尚未完成，无法发布新的挂卖单');
        }
        $publish_origin_rate = getConfigValue('publish_origin_rate');


        $enable_sell = $this->auth->getUser()->origin_static;
        if (!empty($publish_origin_rate)) {
            $buy_origin = OrderOrigin::where('user_id',$user_id)->where('status',3)->sum('number');

            $sell_origin_1 = OrderMarket::where('user_id',$user_id)->where('status',1)->sum('balance');
            $sell_origin_2 = OrderOrigin::where('sell_user_id',$user_id)->where('status','<>',0)->sum('number');
            $sell_origin = $sell_origin_1 + $sell_origin_2;

            $buy_origin = $buy_origin * $publish_origin_rate;

            $enable_sell = $buy_origin - $sell_origin;
        }

        if ($enable_sell - $number < 0 && $this->auth->getUser()->id != 1) {
            if ($enable_sell < 0) {
                $enable_sell = 0;
            }
            $this->error('产酒指数可挂卖额度不足，请先购买原酒增加可卖额度。目前可售产酒指数额度:'.$enable_sell.'。');
        }

        $handling_fee = round($number * $handling_rate / 100,2);
        $total_fee = round($number + $handling_fee,2);

        if ($this->auth->getUser()->origin_static < $total_fee) {
            $this->error('释放的产酒指数不足，无法挂卖。');
        }

        if ($price < $lowest) {
            $this->error('价格无法低于平台最低挂卖价，最低挂卖价为:'.$lowest);
        }
        if ($price > $highest) {
            $this->error('价格无法高于平台最高挂卖价，最低挂卖价为:'.$highest);
        }
        $lowest = getConfigValue('publish_lowest_number');
        $highest = getConfigValue('publish_highest_number');
        if ($number < $lowest) {
            $this->error('对不起，每一单挂卖最低额度数量为:'.$lowest);
        }
        if ($number > $highest) {
            $this->error('对不起，每一单挂卖最高额度数量为:'.$highest);
        }

        Db::transaction(function() use ($user_id,$number,$total_fee,$price,$handling_fee,$handling_rate){
            $balance = \app\common\model\User::where('id',$user_id)->value('origin_static');
            $hand_balance = round($balance - $number,2);
            decBalance($user_id,'origin_static',$total_fee);
            $data = OrderMarket::create([
                'order_number' => generate_order_number(),
                'user_id' => $user_id,
                'price' => $price,
                'number' => $number,
                'balance' => $number,
                'handling_fee' => $handling_fee,
                'handling_rate' => $handling_rate,
                'status' => 1,
            ]);

            WaterOrigin::create([
                'detail_id' => $data->id,
                'user_id' => $data->user_id,
                'type' => WATER_ORIGIN_MARKET_SELL,
                'money' => '-'.$number,
                'balance' => $hand_balance
            ]);

            if (!empty($handling_fee)) {
                WaterOrigin::create([
                    'detail_id' => $data->id,
                    'user_id' => $data->user_id,
                    'type' => WATER_ORIGIN_MARKET_SELL_HANDLING_FEE,
                    'money' => '-'.$handling_fee,
                    'balance' => $hand_balance - $handling_fee
                ]);
            }
        });

        $this->success('挂卖成功！');
    }

    /**
     * 撤单
     */
    public function cancelMarketOrder()
    {
        $id = $this->request->post('id');

        $data = OrderMarket::where('id',$id)->where('status',1)
            ->where('user_id',$this->auth->getUser()->id)->find();

        if (empty($data)) {
            $this->error('挂单不存在！');
        }

        $exist = OrderOrigin::where('order_market_id',$data->id)->whereIn('status',[1,2])->find();

        if (!empty($exist)) {
            $this->error('撤单失败，此挂单还有交易未完成');
        }

        OrderMarket::where('id',$id)->update(['status' => 0]);


        if (!empty($data->balance)){
            Db::transaction(function () use ($data){
                $balance = \app\common\model\User::where('id',$this->auth->getUser()->id)->value('origin_static');
                $balance_two = $balance + $data->balance;
                incBalance($this->auth->getUser()->id,'origin_static',$data->balance);
                WaterOrigin::create([
                    'detail_id' => $data->id,
                    'user_id' => $data->user_id,
                    'type' => WATER_ORIGIN_MARKET_SELL_CANCEL,
                    'money' => $data->balance,
                    'balance' => $balance_two
                ]);
                if (!empty($data->handling_fee)) {
                    incBalance($this->auth->getUser()->id,'origin_static',$data->handling_fee);
                    WaterOrigin::create([
                        'detail_id' => $data->id,
                        'user_id' => $data->user_id,
                        'type' => WATER_ORIGIN_MARKET_SELL_CANCEL_HANDLING_FEE,
                        'money' => $data->handling_fee,
                        'balance' => $balance_two + $data->handling_fee
                    ]);
                }
            });
        }

        $this->success('撤单成功！');
    }

    /**
     * 重新上架
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function rePublishMarketOrder()
    {
        $user_id = $this->auth->getUser()->id;
        $handling_rate = getConfigValue('deal_handling_fee');
        $lowest = getConfigValue('publish_lowest_price');


        $begin_time = getConfigValue('publish_trade_time_begin');
        $end_time = getConfigValue('publish_trade_time_end');

        if (time() < strtotime($begin_time) || time() > strtotime($end_time)) {
            $this->error('对不起，挂卖开放时间为:'.$begin_time.'至'.$end_time.'。');
        }

        $payinfo = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status','<>',2)->find();
        if (empty($payinfo)) {
            $this->error('未设置收款方式，请先设置收款方式。');
        }
        $exist = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status','<>',0)->find();
        if (empty($exist)) {
            $this->error('你的收款方式还在审核中，请稍后再尝试！');
        }
        if ($this->auth->getUser()->real_auth != 2) {
            $this->error('请先通过实名认证！');
        }

        $id = $this->request->post('id');
        $data = OrderMarket::where('id',$id)->where('status',0)->where('user_id',$user_id)->find();
        if (empty($data)) {
            $this->error('找不到该挂单！');
        }
        if ($data->balance == 0) {
            $this->error('该单已经全部出售完毕，无法重新上架！');
        }

        $price = $data->price;
        $number = $data->balance;

//        if ($number % 100 != 0) {
//            $this->error('挂卖必须是100的倍数！');
//        }
        $exist = OrderMarket::where('user_id',$user_id)->where('status',1)->find();
        if (!empty($exist)) {
            $this->error('你有一笔挂卖单尚未完成，无法发布新的挂卖单');
        }
        $publish_origin_rate = getConfigValue('publish_origin_rate');


        $enable_sell = $this->auth->getUser()->origin_static;
        if (!empty($publish_origin_rate)) {
            $buy_origin = OrderOrigin::where('user_id',$user_id)->where('status',3)->sum('number');

            $sell_origin_1 = OrderMarket::where('user_id',$user_id)->where('status',1)->sum('balance');
            $sell_origin_2 = OrderOrigin::where('sell_user_id',$user_id)->where('status','<>',0)->sum('number');
            $sell_origin = $sell_origin_1 + $sell_origin_2;

            $buy_origin = $buy_origin * $publish_origin_rate;

            $enable_sell = $buy_origin - $sell_origin;
        }

        if ($enable_sell - $number < 0 && $this->auth->getUser()->id != 1) {
            if ($enable_sell < 0) {
                $enable_sell = 0;
            }
            $this->error('产酒指数可挂卖额度不足，请先购买原酒增加可卖额度。目前可售产酒指数额度:'.$enable_sell.'。');
        }

        $handling_fee = round($number * $handling_rate / 100,2);
        $total_fee = round($number + $handling_fee,2);

        if ($this->auth->getUser()->origin_static < $total_fee) {
            $this->error('释放的产酒指数不足，无法挂卖。');
        }

        if ($price < $lowest) {
            $this->error('价格无法低于平台最低挂卖价，最低挂卖价为:'.$lowest);
        }

//        $lowest = getConfigValue('publish_lowest_number');
//        $highest = getConfigValue('publish_highest_number');
//        if ($number < $lowest) {
//            $this->error('对不起，每一单挂卖最低额度数量为:'.$lowest);
//        }
//        if ($number > $highest) {
//            $this->error('对不起，每一单挂卖最高额度数量为:'.$highest);
//        }
        Db::transaction(function() use ($user_id,$id,$number,$data,$total_fee,$price,$handling_fee,$handling_rate){
            $balance = \app\common\model\User::where('id',$user_id)->value('origin_static');
            $hand_balance = round($balance - $number,2);
            decBalance($user_id,'origin_static',$total_fee);
            OrderMarket::where('id',$id)->where('status',0)->where('user_id',$user_id)->update(['status'=>1]);
//            $data = OrderMarket::create([
//                'order_number' => generate_order_number(),
//                'user_id' => $user_id,
//                'price' => $price,
//                'number' => $number,
//                'balance' => $number,
//                'handling_fee' => $handling_fee,
//                'handling_rate' => $handling_rate,
//                'status' => 1,
//            ]);

            WaterOrigin::create([
                'detail_id' => $data->id,
                'user_id' => $data->user_id,
                'type' => WATER_ORIGIN_MARKET_SELL,
                'money' => '-'.$number,
                'balance' => $hand_balance
            ]);

            if (!empty($handling_fee)) {
                WaterOrigin::create([
                    'detail_id' => $data->id,
                    'user_id' => $data->user_id,
                    'type' => WATER_ORIGIN_MARKET_SELL_HANDLING_FEE,
                    'money' => '-'.$handling_fee,
                    'balance' => $hand_balance - $handling_fee
                ]);
            }
        });

        $this->success('重新上架成功！');
    }

    /**
     * 获取支付状态
     */
    public function getBankStatus()
    {
        $data['bank'] = 0;
        $data['wechat'] = 0;
        $data['alipay'] = 0;
        $bank = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status',1)->where('type', 'bank')->find();
        if (!empty($bank)) {
            $data['bank'] = 1;
        }
        $wechat = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status',1)->where('type', 'wechat')->find();
        if (!empty($wechat)) {
            $data['wechat'] = 1;
        }
        $alipay = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status',1)->where('type', 'alipay')->find();
        if (!empty($alipay)) {
            $data['alipay'] = 1;
        }

        $this->success('ok',$data);
    }


    /**
     * 购买的市场订单
     */
    public function buyMarketOrder()
    {
        $page = $this->request->get('page') ?? 1;
        $state =  $this->request->get('state') ?? 0;

        $user_id = $this->auth->getUser()->id;
        $this->loadadminlang('order/order_origin');
        if ($state == 0) {
            $data = OrderOrigin::where('user_id',$user_id)->order('create_time','desc')->page($page, 20)->select();
        } else {
            $user_data = \app\common\model\User::field('id,pid')->select();
            $result = [];
            $result2 = [];
            getChildId($user_data, $this->auth->getUser()->id, $result, -1);
            getParentsId($user_data, $this->auth->getUser()->id, $result2, -1);
            $result = array_merge($result, $result2);
            $data = OrderOrigin::field('user_id,create_time,price,number')->whereIn('user_id',$result)
                ->order('create_time','desc')->page($page, 20)->select();
            foreach ($data as $value)
            {
                $value->avatar = getUserValue($value->user_id,'avatar');
                $value->nickname = getUserValue($value->user_id,'nickname');
            }

        }

        $this->success('ok',$data);
    }

    /**
     * 出售的市场订单
     */
    public function sellMarketOrder()
    {
        $page = $this->request->get('page') ?? 1;
        $state =  $this->request->get('state') ?? 0;

        $user_id = $this->auth->getUser()->id;
        $this->loadadminlang('order/order_origin');
        if ($state == 0) {
            $data = OrderOrigin::where('sell_user_id',$user_id)->order('create_time','desc')->page($page, 20)->select();
        } else {
            $user_data = \app\common\model\User::field('id,pid')->select();
            $result = [];
            $result2 = [];
            getChildId($user_data, $this->auth->getUser()->id, $result, -1);
            getParentsId($user_data, $this->auth->getUser()->id, $result2, -1);
            $result = array_merge($result, $result2);
            $data = OrderOrigin::field('user_id,create_time,price,number')->whereIn('sell_user_id',$result)->order('create_time','desc')->page($page, 20)->select();
            foreach ($data as $value)
            {
                $value->avatar = getUserValue($value->user_id,'avatar');
                $value->nickname = getUserValue($value->user_id,'nickname');
            }
        }

        $this->success('ok',$data);
    }

    /**
     * 团队挂单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function teamMarketOrder()
    {
        $page = $this->request->get('page') ?? 1;

        $user_data = \app\common\model\User::field('id,pid,trade_center')->where('trade_center',0)->select();
        $result = [];
        getChildId($user_data, $this->auth->getUser()->id, $result, -1);
        $result[] = $this->auth->getUser()->pid;
        $this->loadadminlang('order/order_market');
        $data = OrderMarket::field('user_id,create_time,price,number,balance')
            ->whereIn('user_id',$result)->where('status',1)->order('number','desc')->page($page,20)->select();

        foreach ($data as $value)
        {
            $value->avatar = getUserValue($value->user_id,'avatar');
            $value->nickname = getUserValue($value->user_id,'nickname');
            $value->deal_amount = $value->number - $value->balance;
        }

        $this->success('ok',$data);
    }


    /**
     * 挂单管理-自己上架，撤架的挂单
     */
    public function myMarketOrder()
    {
        $state = $this->request->get('state') ?? 1;

        $user_id = $this->auth->getUser()->id;

        $page = $this->request->get('page') ?? 1;
        $this->loadadminlang('order/order_market');

        $data = OrderMarket::where('user_id',$user_id)->where('status',$state)->order('create_time','desc')->page($page, 20)->select();
//        if ($state == 1) {
//            $data = OrderMarket::where('user_id',$user_id)->where('status',1)->order('create_time','desc')->page($page, 20)->select();
//        } else {
//            $data = OrderMarket::where('user_id',$user_id)->where('status',0)->order('create_time','desc')->page($page, 20)->select();
//        }

        foreach ($data as $value)
        {
            $value->deal_amount = $value->number - $value->balance;
        }

        $this->success('ok',$data);
    }

    /**
     * 自己的订单，卖卖
     */
    public function myOrder()
    {
        $state = $this->request->get('state');

        $user_id = $this->auth->getUser()->id;

        $page = $this->request->get('page') ?? 1;
        $this->loadadminlang('order/order_origin');
        if ($state == 1) {
            $data = OrderOrigin::where('user_id',$user_id)->order('create_time','desc')->page($page, 20)->select();
        } else {
            $data = OrderOrigin::where('sell_user_id',$user_id)->order('create_time','desc')->page($page, 20)->select();
        }

        $this->success('ok',$data);
    }

    /**
     * 我要挂卖页面数据
     */
    public function publishBuyOrder()
    {
        $user_id = $this->auth->getUser()->id;
        $handling_rate = getConfigValue('deal_handling_fee');
        $lowest = getConfigValue('publish_lowest_price');

        if (empty($this->request->post())) {

//            $lowest = OrderMarket::where('status',1)->order('price','asd')->value('price') ?? 0;

            $user_origin_static = $this->auth->getUser()->origin_static;

            $data = [
                'lowest' => $lowest,
                'enable_sell' => $user_origin_static,
                'handling_fee' => $handling_rate
            ];
            $this->success('ok',$data);;
        }

        $begin_time = getConfigValue('publish_trade_time_begin');
        $end_time = getConfigValue('publish_trade_time_end');

        if (time() < strtotime($begin_time) || time() > strtotime($end_time)) {
            $this->error('对不起，挂卖开放时间为:'.$begin_time.'至'.$end_time.'。');
        }

        $payinfo = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status','<>',2)->find();
        if (empty($payinfo)) {
            $this->error('未设置收款方式，请先设置收款方式。');
        }
        $exist = UserPayinfo::where('user_id',$this->auth->getUser()->id)->where('status','<>',0)->find();
        if (empty($exist)) {
            $this->error('你的收款方式还在审核中，请稍后再尝试！');
        }
        if ($this->auth->getUser()->real_auth != 2) {
            $this->error('请先通过实名认证！');
        }
        $price = $this->request->post('price');
        $number = $this->request->post('number');

        if ($number % 100 != 0) {
            $this->error('挂卖必须是100的倍数！');
        }

        $publish_origin_rate = getConfigValue('publish_origin_rate');


        $enable_sell = $this->auth->getUser()->origin_static;
        if (!empty($publish_origin_rate)) {
            $buy_origin = OrderOrigin::where('user_id',$user_id)->where('status',3)->sum('number');

            $sell_origin_1 = OrderMarket::where('user_id',$user_id)->where('status',1)->sum('balance');
            $sell_origin_2 = OrderOrigin::where('sell_user_id',$user_id)->where('status','<>',0)->sum('number');
            $sell_origin = $sell_origin_1 + $sell_origin_2;

            $buy_origin = $buy_origin * $publish_origin_rate;

            $enable_sell = $buy_origin - $sell_origin;
        }

        if ($enable_sell - $number < 0 && $this->auth->getUser()->id != 1) {
            if ($enable_sell < 0) {
                $enable_sell = 0;
            }
            $this->error('产酒指数可挂卖额度不足，请先购买原酒增加可卖额度。目前可售产酒指数额度:'.$enable_sell.'。');
        }

        $handling_fee = round($number * $handling_rate / 100,2);
        $total_fee = round($number + $handling_fee,2);

        if ($this->auth->getUser()->origin_static < $total_fee) {
            $this->error('释放的产酒指数不足，无法挂卖。');
        }

        if ($price < $lowest) {
            $this->error('价格无法低于平台最低挂卖价，最低挂卖价为:'.$lowest);
        }

        Db::transaction(function() use ($user_id,$number,$total_fee,$price,$handling_fee,$handling_rate){
            $balance = \app\common\model\User::where('id',$user_id)->value('origin_static');
            $hand_balance = round($balance - $number,2);
            decBalance($user_id,'origin_static',$total_fee);
            $data = OrderMarket::create([
                'order_number' => generate_order_number(),
                'user_id' => $user_id,
                'price' => $price,
                'number' => $number,
                'balance' => $number,
                'handling_fee' => $handling_fee,
                'handling_rate' => $handling_rate,
                'status' => 1,
            ]);

            WaterOrigin::create([
                'detail_id' => $data->id,
                'user_id' => $data->user_id,
                'type' => WATER_ORIGIN_MARKET_SELL,
                'money' => '-'.$number,
                'balance' => $hand_balance
            ]);

            if (!empty($handling_fee)) {
                WaterOrigin::create([
                    'detail_id' => $data->id,
                    'user_id' => $data->user_id,
                    'type' => WATER_ORIGIN_MARKET_SELL_HANDLING_FEE,
                    'money' => '-'.$handling_fee,
                    'balance' => $hand_balance - $handling_fee
                ]);
            }
        });

        $this->success('挂卖成功！');
    }

}
<?php

namespace app\api\controller;

use app\admin\model\Banner;
use app\admin\model\BonusWater;
use app\admin\model\Goods;
use app\admin\model\GoodsDetail;
use app\admin\model\ManageRule;
use app\admin\model\MineUser;
use app\admin\model\MiningStatistics;
use app\admin\model\MiningUserWallet;
use app\admin\model\MiningWater;
use app\admin\model\OrderBase;
use app\admin\model\OrderStock;
use app\admin\model\RuleUserLevel;
use app\admin\model\UserCashWater;
use app\admin\model\UserLeaderRule;
use app\admin\model\UserLevelRule;
use app\admin\model\UserWater;
use app\admin\model\WaterBase;
use app\admin\model\WaterOrigin;
use app\admin\model\WaterShop;
use app\admin\model\WaterStock;
use app\common\controller\Api;
use app\common\model\Config;
use app\common\model\Locks;
use fast\Random;
use think\Db;
use think\Lang;
use think\Log;

/**
 * 订单接口
 */
class Order extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];


    /**
     * 原酒余额流水
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function waterOrigin()
    {
        $this->loadadminlang('water/water_origin');
        $user_id = $this->auth->getUser()->id;

        $page = $this->request->get('page') ?? 1;
        $key = $this->request->get('key');
        $model = WaterOrigin::where('user_id',$user_id)->order('create_time','desc')->page($page,20);
        if (!empty($key) && $key != 'all') {
            $keys = explode(',', $key);
            $model = $model->whereIn('type',$keys);
        }
        $data['data'] = $model->select();
        foreach ($data['data'] as $value) {
            if ($value->type == WATER_ORIGIN_TRANSFER_IN || $value->type == WATER_ORIGIN_TRANSFER_OUT
                || $value->type == WATER_ORIGIN_RECHARGE_TRANSFER_IN  || $value->type == WATER_ORIGIN_RECHARGE_TRANSFER_OUT) {
                $user_info = \app\common\model\User::field('mobile,nickname')->where('id',$value->relate_user_id)->find();
                $value->mobile = $user_info->mobile ?? '';
                $value->nickname = $user_info->nickname ?? '';
            }
        }
        $ha = new WaterOrigin();
        $type = $ha->getTypeList();
        $tmp = [];
        $tmp['key'] = 'all';
        $tmp['name'] = '全部';
        $data['filter'][] = $tmp;
        foreach ($type as $key => $value) {
            if ($key != 0) {
                $tmp['key'] = $key;
                $tmp['name'] = $value;
                $data['filter'][] = $tmp;
            }

        }

        $this->success('ok',$data);
    }

    /**
     * 购买产酒指数
     */
    public function stock()
    {
        $base_exist = OrderBase::where('user_id',$this->auth->getUser()->id)->where('status',2)->find();
        if (empty($this->request->post())) {
            $data['stock'] = MiningStatistics::whereTime('create_time','today')->value('stock') ?? 0;
            $data['rate'] = getConfigValue('stock_need_origin');
            $data['user']['origin_static'] = $this->auth->getUser()->origin_static;
            $data['user']['origin_recharge'] = $this->auth->getUser()->origin_recharge;
            $data['user']['origin_dynamic'] = $this->auth->getUser()->origin_dynamic + $this->auth->getUser()->origin_buy;
            $data['user']['base'] = $this->auth->getUser()->base;
            $data['user']['total_total'] = nf($this->auth->getUser()->stock + $this->auth->getUser()->origin_static);
            $data['user']['stock'] = $this->auth->getUser()->stock;
            $data['base_exist'] = empty($base_exist) ? 0 : 1;
            $this->success('ok',$data);
        }
        $rules = [
            'pay_type|支付方式'  => 'require|in:recharge,dynamic',
            'number|数量'   => 'require|number|min:1',
        ];
        $validate = new \think\Validate($rules);
        if(!$validate->check($this->request->post())){
            $this->error($validate->getError());
        }

        if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
            $this->error('交易密码错误！');
        }
        $buy_min_stock = getConfigValue('buy_min_stock');

        $rate = getConfigValue('stock_need_origin');
        $number = $this->request->post('number');
        $pay_type = $this->request->post('pay_type');

        if ($number < $buy_min_stock) {
            $this->error('最小产酒指数购买额度为:'.$buy_min_stock);
        }

        if ($number % 10 != 0) {
            $this->error('购买必须是10的倍数！');
        }
        $need_origin = round($number * $rate / 100,2);
        $need_base = $number - $need_origin;

        $str = '';
        if ($pay_type == 'dynamic') {
            if ($this->auth->getUser()->origin_dynamic + $this->auth->getUser()->origin_buy < $need_origin) {
                $this->error('原酒余额不足！');
            }
        } else {
            $str = 'origin_'.$pay_type;
            if ($this->auth->getUser()->$str < $need_origin) {
                $this->error('原酒余额不足！');
            }
        }

        if ($this->auth->getUser()->base < $need_base) {
            $this->error('基酒余额不足！');
        }

        $stock_balance =  MiningStatistics::whereTime('create_time','today')->value('stock');
        if ($stock_balance < $number) {
            $this->error('今日产酒指数余额不足！');
        }

        if ($base_exist) {
            $this->error('您有待支付的基酒订单，无法下单，是否立即去完成订单');
        }
        $max_stock = getConfigValue('buy_max_stock');
//        $user_stock = OrderStock::where('user_id',$this->auth->getUser()->id)->where('status',10)->sum('number');
        if ($this->auth->getUser()->stock + $number > $max_stock) {
            $this->error('产酒指数最大能购买'.$max_stock.',现最多能购买'.($max_stock - $this->auth->getUser()->stock));
        }

        $exist = Locks::where('user_id',$this->auth->getUser()->id)->where('key','stock')->find();

        if (empty($exist)) {
            $result = Locks::create([
                'user_id' => $this->auth->getUser()->id,
                'key' => 'stock',
                'status' => 1
            ]);
        } else {
            $result = Locks::where('user_id',$this->auth->getUser()->id)->where('key','stock')->update(['status' => 1]);
        }
        if (empty($result)) {
            $this->error('正在购买过程中.....请稍后再尝试。');
        }

        try {
            Db::transaction(function () use ($pay_type,$need_origin,$number,$need_base,$str){
                $order = OrderStock::create([
                    'order_number' => generate_order_number(),
                    'user_id' => $this->auth->getUser()->id,
                    'number' => $number,
                    'balance' => $number,
                    'pay_type' => $pay_type,
                    'status' => 10,
                ]);

                $user_data = \app\admin\model\User::field('id,pid,rule_user_level_id')->select();
                //升级自己
                $next_data = RuleUserLevel::where('id',1)->find();
                if ($this->auth->getUser()->rule_user_level_id == 0) {

                    $stock = OrderStock::where('user_id',$this->auth->getUser()->id)->sum('number');
                    if ($stock >= $next_data->produce) {
                        $result = [];
                        getChildId($user_data,$this->auth->getUser()->id,$result, -1);
                        $result[] = $this->auth->getUser()->id;
                        $total_stock = OrderStock::whereIn('user_id',$result)->sum('number');
                        if ($total_stock > $next_data->team_produce && count($result) > $next_data->team_num) {
                            \app\common\model\User::where('id',$this->auth->getUser()->id)->update(['rule_user_level_id' => 1]);
                        }
                    }
                }


                //升级上级
                $rule_user_level = RuleUserLevel::where('id','<>',1)->select();
                $rule_user_level_data = [];
                foreach ($rule_user_level as $value) {
                    $rule_user_level_data[$value->id] = $value;
                }
                $result = [];
                //升级父类，在自己升级的时候触发
                getParents($user_data, $this->auth->getUser()->id,$result, 999999999);
                foreach ($result as $value) {
                    if ($value->rule_user_level_id == 0) {
                        $stock = OrderStock::where('user_id',$value->id)->sum('number');
                        if ($stock >= $next_data->produce) {
                            $user_data = \app\admin\model\User::field('id,pid,rule_user_level_id')->select();
                            $result = [];
                            getChildId($user_data, $value->id, $result, -1);
                            $result[] = $value->id;
                            $total_stock = OrderStock::whereIn('user_id', $result)->sum('number');
                            if ($total_stock > $next_data->team_produce && count($result) >= $next_data->team_num) {
                                \app\common\model\User::where('id', $value->id)->update(['rule_user_level_id' => 1]);
                            }
                        }
                    } else {
                        $child_count = \app\common\model\User::field('rule_user_level_id,count(*) as count')->where('pid',$value->id)
                            ->group('rule_user_level_id')->order('rule_user_level_id','desc')->find();

                        $next_level_id = $child_count->rule_user_level_id + 1;

                        if (isset($rule_user_level_data[$next_level_id])
                            && $child_count->count >= $rule_user_level_data[$next_level_id]['next_num']
                        ) {
                            \app\common\model\User::where('id',$value->id)->update(['rule_user_level_id' => $next_level_id]);
                        }
                    }
                }

                $base_balance = $this->auth->getUser()->base;
                $stock_balance = $this->auth->getUser()->stock;

                //如果是动态，先扣除动态的，再扣除购买的
                if ($pay_type == 'dynamic') {
                    $dynamic_balance = getDynamicBalance($this->auth->getUser()->id);
                    if ($this->auth->getUser()->origin_dynamic < $need_origin) {
                        decBalance($this->auth->getUser()->id,'origin_dynamic', $this->auth->getUser()->origin_dynamic);
                        $remain_origin = $need_origin - $this->auth->getUser()->origin_dynamic;
                        if (!empty($remain_origin)) {
                            decBalance($this->auth->getUser()->id,'origin_buy', $remain_origin);
                        }
                    } else {
                        decBalance($this->auth->getUser()->id,'origin_dynamic', $need_origin);
                    }
                    WaterOrigin::create([
                        'detail_id' => $order->id,
                        'user_id' => $this->auth->getUser()->id,
                        'type' => WATER_ORIGIN_BUY_STOCK_DYNAMIC,
                        'money' => '-'.$need_origin,
                        'balance' => $dynamic_balance - $need_origin
                    ]);
                } else {
                    $dynamic_balance = $this->auth->getUser()->$str;
                    decBalance($this->auth->getUser()->id,$str,$need_origin);
                    WaterOrigin::create([
                        'detail_id' => $order->id,
                        'user_id' => $this->auth->getUser()->id,
                        'type' => WATER_ORIGIN_BUY_STOCK,
                        'money' => '-'.$need_origin,
                        'balance' => $dynamic_balance - $need_origin
                    ]);
                }
                decBalance($this->auth->getUser()->id,'base',$need_base);
                incBalance($this->auth->getUser()->id,'stock',$number);


                WaterBase::create([
                    'detail_id' => $order->id,
                    'user_id' => $this->auth->getUser()->id,
                    'type' => WATER_BASE_BUY_STOCK,
                    'money' => '-'.$need_base,
                    'balance' => $base_balance - $need_base
                ]);

                WaterStock::create([
                    'detail_id' => $order->id,
                    'user_id' => $this->auth->getUser()->id,
                    'type' => WATER_STOCK_BUY_STOCK,
                    'money' => $number,
                    'balance' => $stock_balance + $number
                ]);

                $result = MiningStatistics::whereTime('create_time','today')->setDec('stock', $number);
                if (empty($result)) {
                    $this->error('申请失败！今日产酒指数余额不足！');
                }
            });
            Locks::where('user_id',$this->auth->getUser()->id)->where('key','stock')->update(['status' => 0]);
        }catch (\Exception $e) {
            Locks::where('user_id',$this->auth->getUser()->id)->where('key','stock')->update(['status' => 0]);
            $this->error('购买失败！');
        }
        $this->success('购买成功!');
    }

    /**
     * 产酒指数订单列表
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function stockOrderList()
    {
        $this->loadadminlang('order/order_stock');

        $page = $this->request->get('page') ?? 1;
        $order = OrderStock::field('id,order_number,create_time,number,money')
            ->where('user_id', $this->auth->getUser()->id)->page($page,20)->order('create_time', 'desc')->select();


        $data['order_list'] = $order;
        $data['stock'] = $this->auth->getUser()->stock;
        $this->success('ok', $data);
    }

        /**
         * 产酒指数明细
         */
    public function waterStock()
    {
        $this->loadadminlang('water/water_stock');
        $user_id = $this->auth->getUser()->id;
        $page = $this->request->get('page') ?? 1;
        $data = WaterStock::where('user_id',$user_id)->page($page, 20)->order('create_time','desc')->select();

        $this->success('ok',$data);

    }

    /**
     * 基酒流水
     */
    public function waterBase()
    {
        $this->loadadminlang('water/water_base');
        $user_id = $this->auth->getUser()->id;

        $page = $this->request->get('page') ?? 1;

        $data = WaterBase::where('user_id',$user_id)->page($page, 20)->order('create_time','desc')->select();
        foreach ($data as $value) {
            if ($value->type == WATER_BASE_TRANSFER_IN || $value->type == WATER_BASE_TRANSFER_OUT) {
                $user_info = \app\common\model\User::field('mobile,nickname')->where('id',$value->relate_user_id)->find();
                $value->mobile = $user_info->mobile ?? '';
                $value->nickname = $user_info->nickname ?? '';
            }
        }
        $this->success('ok',$data);
    }

    /**
     * 商城流水
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function waterShop()
    {
        $this->loadadminlang('water/water_shop');
        $user_id = $this->auth->getUser()->id;
        $page = $this->request->get('page') ?? 1;
        $data = WaterShop::where('user_id',$user_id)->page($page,20)->order('create_time','desc')->select();

        $this->success('ok',$data);
    }

    /**
     * 购买基酒
     */
    public function base()
    {
        $base_price = getConfigValue('base_price');
        $base_exist = OrderBase::where('user_id',$this->auth->getUser()->id)->where('status',2)->find();
        if (empty($this->request->post())) {
            $data['base'] = MiningStatistics::whereTime('create_time','today')->value('base') ?? 0;;
            $data['base_price'] = $base_price;

            $data['base_exist'] = empty($base_exist) ? 0 : 1;
//            $data['rate'] = 80;
//            $data['user']['origin'] = 123;
            $data['user']['base'] = $this->auth->getUser()->base;
            $this->success('ok',$data);
        }

        $rules = [
            'number|数量'   => 'require|number|min:1',
        ];
        $validate = new \think\Validate($rules);
        if(!$validate->check($this->request->post())){
            $this->error($validate->getError());
        }

        $number = $this->request->post('number');
        $money = $number * $base_price;

        $single_buy_max_base = getConfigValue('single_buy_max_base');
        if ($number > $single_buy_max_base) {
            $this->error('单次购买基酒最高额度为'.$single_buy_max_base);
        }
        if ($number % 100 != 0) {
            $this->error('购买必须是100的倍数！');
        }

        $balance = MiningStatistics::whereTime('create_time','today')->value('base');
        if ($balance < $number) {
            $this->error('今日基酒余额不足！');
        }

        if ($base_exist) {
            $this->error('您有待支付的基酒订单，无法下单，是否立即去完成订单');
        }
        if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
            $this->error('交易密码错误！');
        }
        try {
            $order = Db::transaction(function() use ($number,$money){
                $order = OrderBase::create([
                    'order_number' => generate_order_number(),
                    'user_id' => $this->auth->getUser()->id,
                    'number' => $number,
                    'money' => $money,
                    'status' => 2
                ]);

                $result = MiningStatistics::whereTime('create_time','today')->setDec('base',$number);
                if (empty($result)) {
                    $this->error('申请失败！今日基酒余额不足！');
                }
                
                return $order;
            });
        } catch (\Exception $e) {
            $this->error('申请失败！今日基酒余额不足！');
        }


        $this->success('申请成功！',['id' => $order->id]);
    }

    /**
     * Get 基酒订单列表
     */
    public function baseOrderList()
    {
        $this->loadadminlang('order/order_base');
        $page = $this->request->get('page') ?? 1;

        $order = OrderBase::field('id,order_number,create_time,number,money,status')
            ->where('user_id',$this->auth->getUser()->id)->page($page,20)->order('create_time','desc')->select();

        foreach ($order as $value) {
            $value->wait_num = OrderBase::where('id','<',$value->id)->whereIn('status',[1,2,3])->count();
        }

        $this->success('ok',$order);
    }

    /**
     * 取消基酒排单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelBaseOrder()
    {
        $id = $this->request->post('id');

        $this->loadadminlang('order/order_base');

        $order = OrderBase::where('user_id',$this->auth->getUser()->id)->where('id',$id)->where('status', 2)->find();

        if (empty($order)) {
            $this->error('挂单不存在，可能已经取消或排单成功！');
        }
        $create_time = date('Y-m-d',strtotime($order->create_time));

        MiningStatistics::where('create_time',$create_time)->setInc('base',$order->number);

        OrderBase::where('user_id',$this->auth->getUser()->id)->where('id',$id)->update(['status' => 0]);

        $this->success('取消成功！');
    }

    /**
     * 基酒订单详情
     */
    public function baseOrderDetail()
    {
        $this->loadadminlang('order/order_base');
        if (empty($this->request->post())) {
            $id = $this->request->get('id');
            $data = OrderBase::where('id',$id)->where('user_id',$this->auth->getUser()->id)->find();
            if (empty($data)){
                $this->error('找不到该订单！');
            }
            $payinfo[] = [
                'name' => getConfigValue('bank_name'),
                'bank' => getConfigValue('bank'),
                'sub_bank' => getConfigValue('sub_bank'),
                'card' => getConfigValue('bank_card'),
            ];
            $data['payinfo'] = $payinfo;
            $this->success('ok',$data);
        }
    }

    /**
     * 支付基酒
     */
    public function payBaseOrder()
    {
        $id = $this->request->post('id');

        $data = OrderBase::where('id',$id)->where('user_id',$this->auth->getUser()->id)
            ->where('status',2)->find();
        if (empty($data)) {
            $this->error('找不到该订单！');
        }

        $image = $this->request->file('image');

        if (empty($image)) {
            $this->error('未上传文件');
        }
        $info = $image->validate(['size' => 4194204])->move(PAYMENT_PATH);
        if (!$info) {
            $this->error($image->getError());
        }
        $payment = '/'.PAYMENT_PATH . $info->getSaveName();

        OrderBase::where('id',$id)->update(['status' => 3,'payment' => $payment]);

        $this->success('上传支付凭证成功！');
    }

    /**
     * 用户订单列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userOrder()
    {
        $state = $this->request->get('state');
        $page = $this->request->get('page') ?? 1;
        $data = \app\admin\model\OrderShop::field('id,order_number,goods_id,goods_name,money,number')
            ->where('status', $state)->where('user_id', $this->auth->getUser()->id)->page($page,20)->order('id','desc')->select();

        foreach ($data as $value) {
            $value->image = Goods::where('id',$value->goods_id)->value('image');
        }

        $this->success('ok', $data);
    }


    /**
     * 订单详情
     */
    public function orderDetail()
    {
        $this->loadadminlang('order/order_shop');
        $order_id = $this->request->param('order_id');
        $order_data = \app\admin\model\OrderShop::where('id',$order_id)->where('user_id',$this->auth->getUser()->id)->find();
        if (empty($order_data)) {
            $this->error('找不到该订单！');
        }
        $order_data->image = Goods::where('id',$order_data->goods_id)->value('image');
        $this->success('ok', $order_data);
    }

    /**
     * 确定收货
     */
    public function receiveOrder()
    {
        $order_id = $this->request->post('order_id');
        $order_data = \app\admin\model\Order::where('id',$order_id)
            ->where('status',ORDER_WAIT_RECEIVE)
            ->where('user_id',$this->auth->getUser()->id)->update(['status' => ORDER_FINISH]);

        $this->success('收货成功！');
    }

    /**
     * 资金流水
     */
    public function orderWaterDetail()
    {
        $this->loadadminlang('water/water_shop');
        $user_id = $this->auth->getUser()->id;

        $data = WaterShop::where('user_id',$user_id)->select();

        $this->success('ok',$data);
    }


}

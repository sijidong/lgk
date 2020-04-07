<?php
/**
 * Created by PhpStorm.
 * User: Progress
 * Date: 2019/10/28
 * Time: 17:19
 */
namespace app\api\controller;

use app\admin\model\Announcement;
use app\admin\model\Car;
use app\admin\model\CarUser;
use app\admin\model\Goods;
use app\admin\model\MineUser;
use app\admin\model\MiningType;
use app\admin\model\MiningUserWallet;
use app\admin\model\MiningWater;
use app\admin\model\MiningWithdraw;
use app\admin\model\News;
use app\admin\model\UserCashWater;
use app\admin\model\UserCashWithdraw;
use app\admin\model\WaterBase;
use app\admin\model\WaterCalculation;
use app\admin\model\WaterCoin;
use app\admin\model\WaterOrigin;
use app\admin\model\WaterStock;
use app\common\controller\Api;
use app\common\model\Config;
use app\common\model\Locks;
use app\common\model\MnemonicWord;
use app\common\model\Tcoininfo;
use app\common\model\UserAddress;
use app\common\model\WalletToken;
use Faker\Factory;
use think\Db;
use think\Lang;
use think\Validate;

/**
 * 系统文章
 */
class Mining extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];

    /**
     * 币余额
     */
    public function balance()
    {
        $data['coin'] = \app\common\model\User::where('id',$this->auth->getUser()->id)->value('coin');
        $this->success('ok',$data);
    }

    /**
     * 币流水
     */
    public function waterCoin()
    {
        $this->loadadminlang('water/water_coin');
        $user_id = $this->auth->getUser()->id;

        $page = $this->request->get('page') ?? 1;

        $data = WaterCoin::where('user_id',$user_id)->page($page, 20)->order('id','desc')->select();
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
     * 余额详情
     */
    public function balanceDetail()
    {
        $this->loadadminlang('water/mining_water');

        $page = $this->request->get('page') ?? 1;
        $coin_type = strtoupper($this->request->get('coin_type'));

        if (empty($coin_type)) {
            $this->error('参数错误!');
        }

        $data['data'] = MiningWater::field('detail_id,money,mark,type,update_time')->where('user_id',$this->auth->getUser()->id)
            ->where('coin_type',$coin_type)->page($page,50)->select();

        foreach ($data['data'] as $value) {
            if ($value->type == USER_WATER_TRANSFER_IN || $value->type == USER_WATER_TRANSFER_OUT) {
                $value->type_text = $value->type_text . '-'.$value->mark;
            }
        }

        $coin_type_id = MiningType::where('coin_type',$coin_type)->value('id');
        $data['balance'] = MiningUserWallet::where('mining_type_id',$coin_type_id)->where('user_id',$this->auth->getUser()->id)->value('balance') ?? 0;

        $this->success('ok',$data);
    }

    /**
     * 充币
     */
    public function onRecharge()
    {
        $coinid = Tcoininfo::where('cnamecn','LGK')->value('coinid');
        $data['address'] = UserAddress::where('user_id',$this->auth->getUser()->id)->where('coinid',$coinid)->value('address');
        if (empty($data['address'])) {
            $data = [
                'coinid' => $coinid,
                'userid' => $this->auth->getUser()->id
            ];
            $result = http_post_json('172.16.1.196:10603/v1/lgk/generateaddr',json_encode($data));
        }
        $data['address'] = UserAddress::where('user_id',$this->auth->getUser()->id)->where('coinid',$coinid)->value('address');
        $this->success('ok',$data);
    }

    /**
     * 提币
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function withdraw()
    {
        if (empty($this->request->post())) {
//            $coin_type = $this->request->get('coin_type');
//            $mining_type = MiningType::where('coin_type',$coin_type)->find();
            $data['withdraw_lowest'] = getConfigValue('withdraw_handling_fee_lowest');
            $data['handling_fee'] = getConfigValue('withdraw_handling_fee');
            $data['balance'] = $this->auth->getUser()->coin;

            $this->success('ok',$data);
        }

            $validate = \think\Validate::make([
                'amount|数量' => 'require|number|egt:1',
                'address|地址' => 'require',
            ]);
            if (!$validate->check($this->request->post()))
                $this->error($validate->getError());

            $user_id = $this->auth->getUser()->id;
            $amount = $this->request->post('amount');
            $address = $this->request->post('address');

            $handling_fee = Config::where('name','withdraw_handling_fee')->value('value');
            $handling_fee = round($handling_fee * $amount / 100, 6);

            $withdraw_handling_fee_lowest = getConfigValue('withdraw_handling_fee_lowest');
            if ($amount < $withdraw_handling_fee_lowest) {
                $this->error('最低提币额度为：'.$withdraw_handling_fee_lowest);
            }

//            if ($amount % 100 != 0) {
//                $this->error('提币额度必输为100的倍数！');
//            }

            $balance = $this->auth->getUser()->coin;

            if ($balance < $amount) {
                $this->error('余额不足,提币失败！');
            }
            if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
                $this->error('交易密码错误！');
            }


            decBalance($user_id,'coin',$amount);
            $id = MiningWithdraw::create([
                'user_id' => $user_id,
                'number' => $amount,
                'hand_fee' => $handling_fee,
                'amount' => $amount - $handling_fee,
                'address' => $address,
            ]);

            $id = WaterCoin::create([
                'detail_id' => $id->id,
                'user_id' => $user_id,
                'type' => WATER_COIN_WITHDRAW,
                'money' => '-'.$amount,
                'balance' => $balance - $amount,
                'mark' => $address
            ]);

            $this->success('提币申请成功！');
    }

    /**
     * 转账
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function transfer()
    {
        $user_id = $this->auth->getUser()->id;
//        $fee = Config::where('name','transfer_mining_handling_fee')->value('value');

        $handling_fee = Config::where('name','transfer_mining_handling_fee')->value('value');

        if (empty($this->request->post()))
        {
//            $data['origin_static'] = $this->auth->getUser()->origin_static;
//            $data['origin_buy'] = $this->auth->getUser()->origin_buy;
            $data['origin_dynamic'] = $this->auth->getUser()->origin_dynamic;
            $data['origin_recharge'] = $this->auth->getUser()->origin_recharge;
            $data['base'] = $this->auth->getUser()->base;
            $data['coin'] = $this->auth->getUser()->coin;
            $data['handling_fee'] = $handling_fee;
            $this->success('ok', $data);
        }
        $transfer = getConfigValue('transfer');
        if (empty($transfer)) {
            $this->error('转账功能暂时维护中，请稍后再尝试。');
        }
        $rules = [
            'coin_type|支付类型'  => 'in:origin_dynamic,origin_recharge,base,coin',
            'number|数量'   => 'require|number|min:1',
            'account|账户' => 'require'
        ];
        $validate = new \think\Validate($rules);
        if(!$validate->check($this->request->post())){
            $this->error($validate->getError());
        }


        $account = $this->request->post('account');
        $number = $this->request->post('number');
        $coin_type = $this->request->post('coin_type');

        if (Validate::is($account, "email")) {
            $to_user = \app\common\model\User::field('money,id,mobile,base')->where('email', $account)->find();

            if ($account == $this->auth->getUser()->email) {
                $this->error('转让人不能是自己');
            }
        } else {
            $to_user = \app\common\model\User::field('money,id,mobile,base')->where('mobile', $account)->find();

            if ($account == $this->auth->getUser()->mobile) {
                $this->error('转让人不能是自己');
            }
        }

        if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
            $this->error('交易密码错误！');
        }


        $balance = $this->auth->getUser()->$coin_type;

        if (empty($to_user)) {
            $this->error('转账用户不存在！');
        }
        if ($balance < $number) {
            $this->error('用户余额不足！');
        }

        $user_data = \app\admin\model\User::field('id,pid,rule_user_level_id')->order('id','desc')->select();
        $result = [];
        $resultt = [];
        getChildId($user_data,$user_id, $result, -1);
        getParentsId($user_data,$user_id,$resultt,99999);
        $result = array_merge($result, $resultt);
//
        if (!in_array($to_user->id,$result)) {
            $this->error('转账用户必须是自己的上下级！');
        }

        $number = round($number,2);
        //手续费
        $handling_fee = Config::where('name','transfer_mining_handling_fee')->value('value');
        $handling_fee = round($handling_fee * $number / 100,2);

        $increase_money = $number - $handling_fee;

        $user_balance = getUserValue($user_id,$coin_type);
        $to_user_balance = getUserValue($to_user->id,$coin_type);

        $user_account = empty($this->auth->getUser()->mobile) ? $this->auth->getUser()->email : $this->auth->getUser()->mobile;
        if ($coin_type == 'origin_dynamic' || $coin_type == 'origin_recharge') {
            if ($coin_type == 'origin_dynamic') {
                $user_balance = getDynamicBalance($user_id);
                $to_user_balance = getDynamicBalance($to_user->id);
                WaterOrigin::create([
                    'relate_user_id' =>$to_user->id,
                    'user_id' => $user_id,
                    'type' => WATER_ORIGIN_TRANSFER_OUT,
                    'money' => '-'.$number,
                    'mark' => $account,
                    'balance' => $user_balance - $number
                ]);

                WaterOrigin::create([
                    'relate_user_id' =>$user_id,
                    'user_id' => $to_user->id,
                    'type' => WATER_ORIGIN_TRANSFER_IN,
                    'money' => $increase_money,
                    'mark' => $user_account,
                    'balance' => $to_user_balance + $increase_money
                ]);
            } elseif($coin_type == 'origin_recharge') {
                WaterOrigin::create([
                    'relate_user_id' =>$to_user->id,
                    'user_id' => $user_id,
                    'type' => WATER_ORIGIN_RECHARGE_TRANSFER_OUT,
                    'money' => '-'.$number,
                    'mark' => $account,
                    'balance' => $user_balance - $number
                ]);

                WaterOrigin::create([
                    'relate_user_id' =>$user_id,
                    'user_id' => $to_user->id,
                    'type' => WATER_ORIGIN_RECHARGE_TRANSFER_IN,
                    'money' => $increase_money,
                    'mark' => $user_account,
                    'balance' => $to_user_balance + $increase_money
                ]);
            }

        } else if ($coin_type == 'base'){
            WaterBase::create([
//            'detail_id' => $id,
                'relate_user_id' =>$to_user->id,
                'user_id' => $user_id,
                'type' => WATER_BASE_TRANSFER_OUT,
                'money' => '-'.$number,
                'mark' => $account,
                'balance' => $user_balance - $number
            ]);
            WaterBase::create([
//            'detail_id' => $id,
                'relate_user_id' =>$user_id,
                'user_id' => $to_user->id,
                'type' => WATER_BASE_TRANSFER_IN,
                'money' => $increase_money,
                'mark' => $user_account,
                'balance' => $to_user_balance + $increase_money
            ]);
        } else if ($coin_type == 'coin') {
            WaterCoin::create([
//            'detail_id' => $id,
                'relate_user_id' =>$to_user->id,
                'user_id' => $user_id,
                'type' => WATER_COIN_TRANSFER_OUT,
                'money' => '-'.$number,
                'mark' => $account,
                'balance' => $user_balance - $number
            ]);
            WaterCoin::create([
//            'detail_id' => $id,
                'relate_user_id' =>$user_id,
                'user_id' => $to_user->id,
                'type' => WATER_COIN_TRANSFER_IN,
                'money' => $increase_money,
                'mark' => $user_account,
                'balance' => $to_user_balance + $increase_money
            ]);
        }

        incBalance($to_user->id, $coin_type,$increase_money);
        decBalance($user_id, $coin_type,$number);


        $this->success('转账成功！');
    }

    /**
     * 转换
     */
    public function convert()
    {
        $number = $this->request->post('number');

        $user_id = $this->auth->getUser()->id;
        $rules = [
            'number|数量'   => 'require|number|min:1',
        ];
        $validate = new \think\Validate($rules);
        if(!$validate->check($this->request->post())){
            $this->error($validate->getError());
        }

        if ($this->auth->getUser()->origin_static < $number) {
            $this->error('释放产酒指数不足！');
        }

        $number = round($number,2);
        $origin_static_balance = $this->auth->getUser()->origin_static;
        $origin_recharge_balance = $this->auth->getUser()->origin_recharge;
        \app\common\model\User::where('id',$user_id)->setDec('origin_static',$number);
        \app\common\model\User::where('id',$user_id)->setInc('origin_recharge',$number);
        WaterOrigin::create([
            'user_id' => $user_id,
            'type' => WATER_ORIGIN_RELEASE,
            'money' => $number,
            'balance' => $origin_recharge_balance + $number
        ]);

        WaterStock::create([
            'user_id' => $user_id,
            'type' => WATER_STOCK_CONVERT,
            'money' => '-'.$number,
            'balance' => $origin_static_balance - $number
        ]);

        $this->success('转换成功！');
    }

    /**
     * 上车
     */
    public function carCharge()
    {
        $user_id = $this->auth->getUser()->id;
        $user_origin_static = $this->auth->getUser()->origin_static;
        $user_coin = $this->auth->getUser()->coin;

        $coin_take_times = getConfigValue('coin_take_times');
        $lowest_take_num = getConfigValue('lowest_take_num');
        $highest_take_num = getConfigValue('highest_take_num');

        try {
            $coin_data = json_decode(file_get_contents('https://www.honghuipro.com/api/market/open/ticker?symbol=lgkusdt'),true);
            $coin2_price = nf($coin_data['data']['close'] * 6.9);

            //更新系统值
            $config_coin2_price = getConfigValue('coin2_price');
            if ($config_coin2_price != $coin_data['data']['close'])
            {
                Config::where('name','coin2_price')->update(['value' => $coin_data['data']['close']]);
            }
        }catch (\Exception $e) {
            $coin2_price = nf(getConfigValue('coin2_price') * 6.9);
        }

        $coin1_price=  getConfigValue('publish_highest_price');
        $car_data = Car::where('status',1)->order('id','asc')->find();
        $car_num = $car_data->num ?? null;
        if (empty($this->request->post())) {
            $data['coin_take_times'] = $coin_take_times;
            $data['lowest_take_num'] = $lowest_take_num;
            $data['highest_take_num'] = $highest_take_num;

            $data['user_coin1'] = $user_origin_static;
            $data['user_coin2'] = $user_coin;
            $data['coin1_price'] = $coin1_price;
            $data['coin2_price'] = $coin2_price;
            if ($car_num == null) {
                $car_num = '已没车';
            } else {
                $car_num = $car_data->total - $car_data->num;
            }
            $data['car_num'] = $car_num;      //TODO

            $this->success('ok',$data);
        }

        $number = $this->request->post('number');
        if ($number % 100 != 0) {
            $this->error('带出LGK的数量必须是100的倍数');
        }
        $need_coin1 = $coin2_price / $coin1_price * $number;
        $need_coin1_gas = $number * $coin_take_times;
        $coin1_str = 'origin_static';
        $coin2_str = 'coin';

        if(empty($car_data)) {
            $this->error('今日的车已发送完毕！');
        }

        if ($this->auth->getUser()->$coin1_str < $need_coin1) {
            $this->error('LGK1余额不足！');
        }
        if ($this->auth->getUser()->$coin2_str < $need_coin1_gas) {
            $this->error('LGK2余额不足！');
        }

        if ($number < $lowest_take_num) {
            $this->error('你已低于最低带出数量：'.$lowest_take_num);
        }
        if ($number > $highest_take_num) {
            $this->error('你已高于带出数量：'.$highest_take_num);
        }
        if ($this->auth->getUser()->deal_password != $this->request->post('deal_password')) {
            $this->error('交易密码错误！');
        }
        $user_car_id = CarUser::where('user_id',$this->auth->getUser()->id)->order('id','desc')->value('car_id');
        $car_user_status = Car::where('id',$user_car_id)->value('status');
        if (!empty($car_user_status) && $car_user_status != 3 ) {
            $this->error('对不起，你还有一辆未到站的车，无法上车！');
        }
        //锁
        $exist = Locks::where('user_id',$this->auth->getUser()->id)->where('key','car')->find();

        if (empty($exist)) {
            $result = Locks::create([
                'user_id' => $this->auth->getUser()->id,
                'key' => 'car',
                'status' => 1
            ]);
        } else {
            $result = Locks::where('user_id',$this->auth->getUser()->id)->where('key','car')->update(['status' => 1]);
        }
        if (empty($result)) {
            $this->error('正在上车过程中.....请稍后再尝试。');
        }
        try{
            Db::transaction(function () use ($car_data,$coin2_price,$user_id,$need_coin1_gas,$need_coin1,$number,$coin1_str,$coin2_str){
                $balance_coin1 = $this->auth->getUser()->origin_static;
                $balance_coin2 = $this->auth->getUser()->coin;

                $coin1_balance = $balance_coin1 - $need_coin1;
                $coin2_balance = $balance_coin2 - $need_coin1_gas;

                $car_user_data = CarUser::create([
                    'car_id' => $car_data->id,
                    'user_id' => $user_id,
                    'coin_price' => $coin2_price,
                    'coin1' => $need_coin1,
                    'coin1_balance' => $coin1_balance,
                    'coin2' => $number,
                    'coin2_balance' => $coin2_balance,
                    'gas' => $need_coin1_gas,
                ]);

                WaterStock::create([
                    'detail_id' => $car_user_data->id,
                    'user_id' => $user_id,
                    'type' => WATER_STOCK_CAR_COST,
                    'money' => '-'.$need_coin1,
                    'balance' => $balance_coin1 - $need_coin1,
                ]);

                WaterCoin::create([
                    'detail_id' => $car_user_data->id,
                    'user_id' => $user_id,
                    'type' => WATER_COIN_CAR_COST,
                    'money' => '-'.$need_coin1_gas,
                    'balance' => $balance_coin2 - $need_coin1_gas,
                ]);


                Car::where('status',1)->where('id',$car_data->id)->setDec('num',1);
                decBalance($user_id,'origin_static', $need_coin1);
                decBalance($user_id,'coin', $need_coin1_gas);
                $car_num = Car::where('id',$car_data->id)->value('num');
                if ($car_num == 0 ) {
                    //并发处理
                    $car_arrive_time = getConfigValue('car_arrive_time');
                    $finish_time = datetime(time() + ($car_arrive_time * 3600));
                    Car::where('id',$car_data->id)->update(['begin_time'=>datetime(),'finish_time'=>$finish_time]);
                    $result = Car::where('id',$car_data->id)->update(['status' => 2]);
                    if (empty($result)) {
                        $this->error('目前人员较多，上车失败，请稍后再尝试！');
                    }

                }
            });
            Locks::where('user_id',$this->auth->getUser()->id)->where('key','car')->update(['status' => 0]);
        }catch (\Exception $e) {
            Locks::where('user_id',$this->auth->getUser()->id)->where('key','car')->update(['status' => 0]);
            $this->error('目前上车人员较多，上车失败，请稍后再尝试！');
        }

        $this->success('上车成功！');
    }

    /**
     * 我的车单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function myCarList()
    {
        $page = $this->request->get('page') ?? 1;
        $this->loadadminlang('car/car');
        $user_id = $this->auth->getUser()->id;
        $data = CarUser::field('car_id,coin2,gas,coin_price,create_time')->where('user_id',$user_id)->page($page, 20)->order('create_time','desc')->select();

        foreach ($data as $value) {
            $value->take_num = number_format($value->coin2 + $value->gas,2,'.','');
            $car_data = Car::field('status,total,num,finish_time')->where('id',$value->car_id)->find();
            $car_data->num = $car_data->total - $car_data->num;
            $value->car_data = $car_data;
        }
        $this->success('ok',$data);
    }
}
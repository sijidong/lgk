<?php

namespace app\api\controller;

use app\admin\model\Announcement;
use app\admin\model\Banner;
use app\admin\model\ContactService;
use app\admin\model\Goods;
use app\admin\model\MineUser;
use app\admin\model\MiningStatistics;
use app\admin\model\MiningUserWallet;
use app\admin\model\MiningWater;
use app\admin\model\MiningWithdraw;
use app\admin\model\OrderBase;
use app\admin\model\PlatformReceiveInfo;
use app\admin\model\UserCashWater;
use app\admin\model\UserLevelRule;
use app\admin\model\UserWater;
use app\admin\model\WaterOrigin;
use app\admin\model\WaterStock;
use app\common\controller\Api;
use app\common\model\Config;
use app\common\model\WalletToken;
use fast\Http;
use think\Db;
use think\Env;

/**
 * 首页接口
 */
class Index extends Api
{
    protected $noNeedLogin = ['getBanner'];
    protected $noNeedRight = ['*'];

    /**
     * 得到banner
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getBanner()
    {
        $banner = Banner::field('image')->order('sort','desc')->select();

        $this->success('ok',$banner);
    }

    /**
     * 首页
     *
     */
    public function index()
    {
        $user_id = $this->auth->getUser()->id;

        $data['stock'] = MiningStatistics::whereTime('create_time','today')->value('stock') ?? 0;
        $data['base'] = MiningStatistics::whereTime('create_time','today')->value('base') ?? 0;
        $data['base_queue'] = OrderBase::whereIn('status',[1,2,3])->count();

        $this->success('请求成功', $data);
    }

    /**
     * 联系客服
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serviceList()
    {
        $list = ContactService::where('status',1)->select();

        $this->success('ok',$list);
    }

    /**
     * 收款方式列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function receiveInfoList()
    {
        $list = PlatformReceiveInfo::where('status',1)->select();

        $this->success('ok',$list);
    }


    /**
     * 兑换页面
     */
    public function onConvert()
    {
        $data['money'] = $this->auth->getUser()->money;
        $data['mining_money'] = $this->auth->getUser()->mining_money;

        $data['exchange_rate'] = Config::where('name', 'exchange_rate')->value('value');

        $this->success('', $data);
    }

    /**
     * 兑换
     */
    public function convert()
    {
        $type = $this->request->post('type');
        $money = $this->request->post('money');
        $exchange_rate = $this->request->post('exchange_rate');

        if ($type != 1 && $type != 2) {
            $this->error('类型错误！');
        }
        if ($money < 1) {
            $this->error('兑换金额不能小于1！');
        }

        $user_money = $this->auth->getUser()->money;
        $mining_money = $this->auth->getUser()->mining_money;
        //1 豆兑成币，2币兑成豆
        $user_id = $this->auth->getUser()->id;

        $config_exchange_rate = Config::where('name', 'exchange_rate')->value('value');
        if ($config_exchange_rate != $exchange_rate) {
            $this->error('汇率已变，请刷新页面再尝试！');
        }

        try {

            if ($type == 1) {
                if ($user_money < $money) {
                    $this->error('余额不足！');
                }
                $convert_money = $money * $config_exchange_rate;

                Db::transaction(function() use ($money, $user_money, $mining_money, $user_id, $convert_money,$config_exchange_rate){
                    \app\admin\model\User::where('id', $user_id)->setDec('money',$money);
                    \app\admin\model\User::where('id', $user_id)->setInc('mining_money',$convert_money);
                    UserWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => USER_WATER_MONEY_CONVERT_COIN,
                        'money' => '-'.$money,
                        'balance' => $user_money - $money,
                        'mark' => USER_WATER_MONEY_CONVERT_COIN_MARK . $config_exchange_rate
                    ]);
                    MiningWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => MINING_WATER_MONEY_TO_COIN,
                        'money' => $convert_money,
                        'balance' => $mining_money + $convert_money,
                        'mark' => USER_WATER_MONEY_CONVERT_COIN_MARK . $config_exchange_rate
                    ]);
                });
            } else {
                //币转钱
                if ($mining_money < $money) {
                    $this->error('币余额不足！');
                }

                $convert_money = round($money / $config_exchange_rate, 4);

                Db::transaction(function() use ($money, $user_money, $mining_money, $user_id, $convert_money,$config_exchange_rate){
                    \app\admin\model\User::where('id', $user_id)->setInc('money',$convert_money);
                    \app\admin\model\User::where('id', $user_id)->setDec('mining_money',$money);
                    //加钱减币
                    UserWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => USER_WATER_COIN_CONVERT_MONEY,
                        'money' => $convert_money,
                        'balance' => $convert_money + $user_money,
                        'mark' => USER_WATER_MONEY_CONVERT_COIN_MARK . $config_exchange_rate
                    ]);
                    MiningWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => MINING_WATER_COIN_TO_MONEY,
                        'money' => '-'.$money,
                        'balance' => $mining_money - $money,
                        'mark' => USER_WATER_MONEY_CONVERT_COIN_MARK . $config_exchange_rate
                    ]);
                });
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            $this->error('兑换失败！请重试兑换。');
        }

        $this->success('兑换成功');
    }

    /**
     * 豆兑换现金
     */
    public function onPeasConvert()
    {
        $data['money'] = $this->auth->getUser()->money;
        $data['cash_money'] = $this->auth->getUser()->cash_money;

//        $data['exchange_rate'] = Config::where('name', 'exchange_rate')->value('value');
        $data['exchange_rate'] = 1;
        $this->success('', $data);
    }

    /**
     * 现金券兑换豆
     */
    public function PeasConvert()
    {
        $type = $this->request->post('type');
        $money = $this->request->post('money');
        $exchange_rate = $this->request->post('exchange_rate');

        if ($type != 1 && $type != 2) {
            $this->error('类型错误！');
        }
        if ($money < 1) {
            $this->error('兑换金额不能小于1！');
        }

        $user_money = $this->auth->getUser()->cash_money;
        $peas_money = $this->auth->getUser()->money;
        //1 钱兑成豆，2豆兑成钱
        $user_id = $this->auth->getUser()->id;

//        $config_exchange_rate = Config::where('name', 'exchange_rate')->value('value');
        $config_exchange_rate = 1;
        if ($config_exchange_rate != $exchange_rate) {
            $this->error('汇率已变，请刷新页面再尝试！');
        }

        try {

            if ($type == 1) {
                $this->error('暂不开放！');
                if ($user_money < $money) {
                    $this->error('余额不足！');
                }
                $convert_money = $money * $config_exchange_rate;

                Db::transaction(function() use ($money, $user_money, $peas_money, $user_id, $convert_money,$config_exchange_rate){
                    \app\admin\model\User::where('id', $user_id)->setDec('cash_money',$money);
                    \app\admin\model\User::where('id', $user_id)->setInc('money',$convert_money);
                    UserCashWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => USER_WATER_MONEY_CONVERT_PEAS,
                        'money' => '-'.$money,
                        'balance' => $user_money - $money,
                        'mark' => USER_WATER__MONEY_CONVERT_PEAS_MARK . $config_exchange_rate
                    ]);
                    UserWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => USER_CASH_WATER_MONEY_CONVERT_PEAS,
                        'money' => $convert_money,
                        'balance' => $peas_money + $convert_money,
                        'mark' => USER_WATER__MONEY_CONVERT_PEAS_MARK . $config_exchange_rate
                    ]);
                });
            } else {

                //豆转钱
                if ($peas_money < $money) {
                    $this->error('币余额不足！');
                }

                $convert_money = $money * $config_exchange_rate;

                Db::transaction(function() use ($money, $user_money, $peas_money, $user_id, $convert_money,$config_exchange_rate){
                    \app\admin\model\User::where('id', $user_id)->setDec('money',$money);
                    \app\admin\model\User::where('id', $user_id)->setInc('cash_money',$convert_money);
                    //加钱减豆
                    UserWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => MINING_WATER_COIN_TO_MONEY,
                        'money' => '-'.$money,
                        'balance' => $peas_money - $money,
                        'mark' => USER_WATER_MONEY_CONVERT_COIN_MARK . $config_exchange_rate
                    ]);
                    UserCashWater::create([
                        'from_user_id' => $user_id,
                        'user_id' => $user_id,
                        'type' => USER_WATER_COIN_CONVERT_MONEY,
                        'money' => $convert_money,
                        'balance' => $convert_money + $user_money,
                        'mark' => USER_WATER_MONEY_CONVERT_COIN_MARK . $config_exchange_rate
                    ]);

                });
            }
        } catch (\Exception $e) {
            $this->error('兑换失败！请重试兑换。');
        }

        $this->success('兑换成功');
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
        $account = $this->request->post('account');
        $money = $this->request->post('money');
        $to_user = \app\common\model\User::field('money,id,mobile')->where('mobile', $account)->find();

        $captcha = $this->request->post('code');     //验证码
        $ret = \app\common\library\Sms::check($this->auth->getUser()->mobile, $captcha, 'transfer');
        if (!$ret) {
            $this->error('转账验证码不正确');
        }

        if (empty($to_user)) {
            $this->error('转账用户不存在！');
        }
        if ($this->auth->getUser()->money < $money) {
            $this->error('用户余额不足！');
        }
        if ($money < 1) {
            $this->error('最低转账金额为1！');
        }

        \app\common\model\User::where('id',$this->auth->getUser()->id)->setDec('money', $money);
        \app\common\model\User::where('id',$to_user->id)->setInc('money', $money);
        UserWater::create([
            'from_user_id' => $to_user->id,
            'user_id' => $this->auth->getUser()->id,
            'type' => USER_WATER_TRANSFER_OUT,
            'money' => '-'.$money,
            'balance' => $this->auth->getUser()->money - $money,
            'mark' => sprintf(USER_WATER_TRANSFER_OUT_MARK, $to_user->mobile)
        ]);

        UserWater::create([
            'from_user_id' => $this->auth->getUser()->id,
            'user_id' => $to_user->id,
            'type' => USER_WATER_TRANSFER_IN,
            'money' => $money,
            'balance' => $to_user->money + $money,
            'mark' => sprintf(USER_WATER_TRANSFER_IN_MARK, $this->auth->getUser()->mobile)
        ]);


        $this->success('转账成功！');
    }
    /**
     * 准备充值页面
     */
    public function onChargeMoney()
    {
        $data['exchange_rate'] = Config::where('name', 'exchange_cny_rate')->value('value');
        $data['money_block'] = array(500, 1000, 2000, 5000, 10000, 20000);

        $this->success('ok', $data);
    }

    /**
     * 准备提币页面
     */
    public function onWithdrawCoin()
    {
        $data['handling_fee'] = Config::where('name','withdraw_handling_fee')->value('value');
        $data['least_number'] = Config::where('name','withdraw_least_number')->value('value');

        $data['coin_balance'] = $this->auth->getUser()->mining_money;

        $this->success('ok', $data);
    }
    /**
     * 申请提币
     */
    public function withdrawCoin()
    {
        $coin_type = $this->request->post('coin_type');
        $address = $this->request->post('address');
        $number = $this->request->post('number');

        $captcha = $this->request->post('captcha');     //验证码
        $ret = \app\common\library\Sms::check($this->auth->getUser()->mobile, $captcha, 'withdraw');
        if (!$ret) {
            $this->error('支付验证码不正确');
        }
        $least_withdraw = Config::where('name', 'withdraw_least_number')->value('value');
        if ($least_withdraw > $number) {
            $this->error('提币数量小于最低提币数额！');
        }
        if ($this->auth->getUser()->mining_money < $number) {
            $this->error('账户币余额不足，申请提币失败！');
        }
        $hand_fee = Config::where('name', 'withdraw_handling_fee')->value('value');
        $handling_fee = round($number * $hand_fee / 100, 3);
        $mining_water = MiningWithdraw::create([
            'user_id' => $this->auth->getUser()->id,
            'number' => $number,
            'hand_fee' => $handling_fee,
            'amount' => $number - $handling_fee,
            'address' => $address,
            'type' => $coin_type
        ]);
        MiningWater::create([
            'detail_id' => $mining_water->id,
            'from_user_id' => $this->auth->getUser()->id,
            'user_id' => $this->auth->getUser()->id,
            'type' => MINING_WATER_WITHDRAW_APPEAL,
            'money' => $number,
            'balance' => $this->auth->getUser()->mining_money - $number,
//            'mark' => USER_WATER_MONEY_CONVERT_COIN_MARK . $config_exchange_rate
        ]);
        \app\admin\model\User::where('id',$this->auth->getUser()->id)->setDec('mining_money',$number);

        $this->success('申请成功！');
    }
}

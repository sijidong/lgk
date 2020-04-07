<?php

namespace app\api\controller;
use app\admin\model\MiningType;
use app\admin\model\MiningUserWallet;
use app\admin\model\MiningWater;
use app\common\controller\Api;
use app\common\model\UserWater;
use think\Lang;


/**
 * 会员接口
 */
class Wallet extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';



    /**
     * 余额
     */
    public function balance()
    {
        //TODO
        $type = $this->request->get('type');

        $data = [];
        if ($type == 'base') {
            $data['balance'] = 123;
        } elseif ($type == 'origin') {
            $data['balance'] = 123;
            $data['recharge'] = 123;
            $data['static'] = 123;
            $data['dynamic'] = 123;
        } elseif ($type == 'stock') {
            $data['balance'] = 123;
        }
        $this->success('ok',$data);
    }

    /**
     * 余额详情
     */
    public function balanceDetail()
    {
        //TODO
        Lang::load(APP_PATH  . 'admin/lang/zh-cn/water/mining_shop.php');
        $page = $this->request->get('page') ?? 1;
        $coin_type = strtoupper($this->request->get('coin_type'));

        if (empty($coin_type)) {
            $this->error('参数错误!');
        }

        $data['data'] = MiningWater::field('detail_id,money,mark,type,update_time')->where('user_id',$this->auth->getUser()->id)
            ->where('coin_type',$coin_type)->page($page,50)->select();

        $coin_type_id = MiningType::where('coin_type',$coin_type)->value('id');
        $data['balance'] = MiningUserWallet::where('mining_type_id',$coin_type_id)->where('user_id',$this->auth->getUser()->id)->value('balance') ?? 0;

        $this->success('ok',$data);
    }

    /**
     * 现金余额详情
     */
    public function getCashDetail()
    {
        Lang::load(APP_PATH  . 'admin/lang/zh-cn/water/user_cash_water.php');

        $data['balance'] = $this->auth->getUser()->money;

        $data['detail'] = UserCashWater::where('user_id',$this->auth->getUser()->id)->order('update_time','desc')->select();

        $this->success('ok',$data);
    }


    /**
     * 现金提现
     */
    public function cashWithdraw()
    {
        $money = $this->request->post('money');
        $bank = $this->request->post('bank');
        $subbank = $this->request->post('subbank');
        $bank_name = $this->request->post('bank_name');
        $bank_card = $this->request->post('bank_card');

        if ($money < 200) {
            $this->error('提现最低两百！');
        }


        if ($this->auth->getUser()->money < $money) {
            $this->error('现金余额不足,提币失败！');
        }

        $withdraw = UserCashWithdraw::create([
            'user_id' =>$this->auth->getUser()->id,
            'money' => $money,
            'bank' => $bank,
            'subbank' => $subbank,
            'bank_name' => $bank_name,
            'bank_card' => $bank_card
        ]);
        UserCashWater::create([
            'detail_id' => $withdraw->id,
            'user_id' => $this->auth->getUser()->id,
            'type' => USER_CASH_WATER_WITHDRAW_APPEAL,
            'money' => '-'.$money,
        ]);
        \app\common\model\User::where('id',$this->auth->getUser()->id)->setDec('money',$money);
        $this->error('提币申请成功！');
    }

}

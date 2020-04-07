<?php

namespace app\api\controller;

use app\admin\model\OrderShop;
use app\admin\model\WaterShop;
use app\common\controller\Api;

/**
 * 邮箱验证码接口
 */
class Goods extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = '*';

    /**
     * 用户信息
     */
    public function user()
    {

        $data['balance'] = $this->auth->getUser()->score;

        $data['nickname'] = $this->auth->getUser()->nickname;

        $data['avatar'] = $this->auth->getUser()->avatar;

        $this->success('ok',$data);
    }

    /**
     * 余额详情
     */
    public function balanceDetail()
    {
        //TODO
    }

    /**
     * 商品列表
     */
    public function goodsList()
    {
        $data = \app\admin\model\Goods::field('id,image,name,price,introduce')->where('status',1)
            ->order('sort','desc')->select();

        $this->success('ok',$data);
    }

    /**
     * 商品详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function goodsDetail()
    {
        $id = $this->request->param('id');
        if (empty($id)) {
            $this->error('商品信息错误！');
        }

        $data['goods'] = \app\admin\model\Goods::where('id', $id)->find();

//        $data['goods_detail'] = GoodsDetail::field('id,name,image,number,price')->where('goods_id', $id)->select();
//
//        $data['new_member'] = \app\admin\model\Order::where('user_id', $this->auth->getUser()->id)
//            ->where('status',ORDER_FINISH)->find() ? 0 : 1;
//
//        $data['discount'] = UserLevelRule::where('id', $this->auth->getUser()->level)->value('discount');

        $this->success('ok', $data);
    }

    public function orderConfirm()
    {
        $user_id = $this->auth->getUser()->id;
        if (empty($this->request->post())) {

            $address = \app\admin\model\Address::where('user_id',$user_id)->find();
            $data['receive']['name'] = $address->name ?? '';
            $data['receive']['mobile'] = $address->mobile ?? '';
            $data['receive']['address'] = $address->address ?? '';
            $data['score'] = $this->auth->getUser()->score;
            $this->success('ok',$data);
        }

        $rules = [
            'number|数量'   => 'require|number|min:1',
            'goods_id|商品' => 'require|number|min:1',
            'name|名字' => 'require',
            'mobile|电话' => 'require',
            'address|地址' => 'require',
        ];
        $validate = new \think\Validate($rules);
        if(!$validate->check($this->request->post())){
            $this->error($validate->getError());
        }

        $good_data = \app\admin\model\Goods::where('id',$this->request->post('goods_id'))->where('status',1)->find();
        if (empty($good_data)) {
            $this->error('商品已下架！');
        }

        $number = $this->request->post('number');
        $need_money = round($good_data->price * $number,2);
        if ($this->auth->getUser()->score < $need_money) {
            $this->error('用户积分不足！');
        }

        $deal_password = $this->request->post('deal_password');
        if ($this->auth->getUser()->deal_password != $deal_password) {
            $this->error('支付密码错误！');
        }


        $address = \app\admin\model\Address::where('user_id',$user_id)->find();
        if (empty($address)) {
            \app\admin\model\Address::create([
                'user_id' => $user_id,
                'name' => $this->request->post('name'),
                'address' => $this->request->post('address'),
                'mobile' => $this->request->post('mobile'),
            ]);
        } else {
            \app\admin\model\Address::where('user_id',$user_id)->update([
                'user_id' => $user_id,
                'name' => $this->request->post('name'),
                'address' => $this->request->post('address'),
                'mobile' => $this->request->post('mobile'),
            ]);
        }

        $order = OrderShop::create([
            'order_number' => generate_order_number(),
            'user_id' => $user_id,
            'goods_id' => $good_data->id,
            'goods_name' => $good_data->name,
            'number' => $number,
            'money' =>$need_money,
            'receive_name' => $this->request->post('name'),
            'receive_address' => $this->request->post('address'),
            'receive_mobile' => $this->request->post('mobile'),
            'pay_time' => datetime()
        ]);

        WaterShop::create([
            'detail_id' => $order->id,
            'user_id' => $user_id,
            'type' => WATER_SHOP_BUY,
            'money' => '-'.$need_money,
            'balance' => $this->auth->getUser()->score - $need_money
        ]);

        decBalance($user_id,'score',$need_money);
        $data['order_id'] = $order->id;

        $this->success('购买成功！',$data);
    }
}

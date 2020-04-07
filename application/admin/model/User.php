<?php

namespace app\admin\model;

use think\Model;


class User extends Model
{


    // 表名
    protected $table = 'user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'prevtime_text',
        'logintime_text',
        'jointime_text',
        'trade_center_text',
        'real_auth_text'
    ];

    public function getOriginData()
    {
        return $this->origin;
    }
    public function useraddress()
    {
        return $this->hasOne('user_address', 'user_id','',[],'LEFT')->field('address')->setEagerlyType(0);
    }

    protected static function init()
    {

        self::beforeUpdate(function ($row) {
            $changedata = $row->getChangedData();
            $origin = $row->getOriginData();

            //如果有修改密码
            if (isset($changedata['password'])) {
                if ($changedata['password']) {
                    $salt = \fast\Random::alnum();
                    $row->password = \app\common\library\Auth::instance()->getEncryptPassword($changedata['password'], $salt);
                    $row->salt = $salt;
                } else {
                    unset($row->password);
                }
            }

            if (isset($changedata['deal_password'])) {
                if ($changedata['deal_password']) {
                    $row->deal_password = $changedata['deal_password'];
                } else {
                    unset($row->deal_password);
                }
            }

            if(!empty($row->origin_recharge)) {
                WaterOrigin::create([
                        'user_id' => $row['id'],
                        'type' => WATER_ORIGIN_BACKEND,
                        'money' => $row->origin_recharge,
                        'balance' => getUserValue($row['id'],'origin_recharge') +  $row->origin_recharge
                    ]);

                $row->origin_recharge = ($changedata['origin_recharge']+$origin['origin_recharge']);
            }else{
                unset($row->origin_recharge);
            }

            if(!empty($row->origin_static)) {
                WaterStock::create([
                    'user_id' => $row['id'],
                    'type' => WATER_STOCK_BACKEND,
                    'money' => $row->origin_static,
                    'balance' => getUserValue($row['id'],'origin_static') +  $row->origin_static
                ]);
                $row->origin_static = ($row->origin_static+$origin['origin_static']);
            }else{
                unset($row->origin_static);
            }

            if(!empty($row->origin_buy)) {
                WaterOrigin::create([
                    'user_id' => $row['id'],
                    'type' => WATER_ORIGIN_BACKEND_BUY,
                    'money' => $row->origin_buy,
                    'balance' => getDynamicBalance($row['id']) +  $row->origin_buy
                ]);
                $row->origin_buy = ($row->origin_buy+$origin['origin_buy']);
            }else{
                unset($row->origin_buy);
            }

            if(!empty($row->origin_dynamic)) {
                WaterOrigin::create([
                    'user_id' => $row['id'],
                    'type' => WATER_ORIGIN_BACKEND_DYNAMIC,
                    'money' => $row->origin_dynamic,
                    'balance' => getDynamicBalance($row['id']) +  $row->origin_dynamic
                ]);
                $row->origin_dynamic = ($row->origin_dynamic+$origin['origin_dynamic']);
            }else{
                unset($row->origin_dynamic);
            }


            if(!empty($row->stock)) {
                WaterStock::create([
                    'user_id' => $row['id'],
                    'type' => WATER_STOCK_BACKEND,
                    'money' => $row->stock,
                    'balance' => getUserValue($row['id'],'stock') +  $row->stock
                ]);
                $row->stock = ($row->stock+$origin['stock']);
            }else{
                unset($row->stock);
            }

            if(!empty($row->base)) {
                WaterBase::create([
                    'user_id' => $row['id'],
                    'type' => WATER_BASE_BACKEND,
                    'money' => $row->base,
                    'balance' => getUserValue($row['id'],'base') +  $row->base
                ]);
                $row->base = ($row->base+$origin['base']);
            }else{
                unset($row->base);
            }

            if(!empty($row->score)) {
                WaterShop::create([
                    'user_id' => $row['id'],
                    'type' => WATER_SHOP_BACKEND,
                    'money' => $row->score,
                    'balance' => getUserValue($row['id'],'score') +  $row->score
                ]);
                $row->score = ($changedata['score']+$origin['score']);
            }else{
                unset($row->score);
            }

            if(!empty($row->coin)) {
                WaterCoin::create([
                    'user_id' => $row['id'],
                    'type' => WATER_COIN_BACKEND,
                    'money' => $row->coin,
                    'balance' => getUserValue($row['id'],'coin') +  $row->coin
                ]);
                $row->coin = ($changedata['coin']+$origin['coin']);
            }else{
                unset($row->coin);
            }
        });
    }

    public function getTradeCenterList()
    {
        return ['0' => __('Trade_center 0'), '1' => __('Trade_center 1')];
    }

    public function getRealAuthList()
    {
        return ['0' => __('Real_auth 0'), '1' => __('Real_auth 1'), '2' => __('Real_auth 2')];
    }


    public function getPrevtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['prevtime']) ? $data['prevtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getLogintimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['logintime']) ? $data['logintime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getJointimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['jointime']) ? $data['jointime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getTradeCenterTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['trade_center']) ? $data['trade_center'] : '');
        $list = $this->getTradeCenterList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getRealAuthTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['real_auth']) ? $data['real_auth'] : '');
        $list = $this->getRealAuthList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setPrevtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setLogintimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setJointimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}

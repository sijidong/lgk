<?php

namespace app\admin\model;

use think\Model;


class OrderStock extends Model
{

    

    

    // 表名
    protected $table = 'order_stock';
    
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'pay_type_text'
    ];

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }

    public function getStatusList()
    {
        return [
            '10' => __('Status 10'),
        ];
    }

    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function getPayTypeList()
    {
        return ['recharge' => __('Pay_type recharge'), 'static' => __('Pay_type static'), 'dynamic' => __('Pay_type dynamic')];
    }


    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

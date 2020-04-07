<?php

namespace app\admin\model;

use think\Model;


class OrderShop extends Model
{

    

    

    // 表名
    protected $table = 'order_shop';
    
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '2' => __('Status 2'), '3' => __('Status 3'), '10' => __('Status 10')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




}

<?php

namespace app\admin\model;

use think\Model;


class CarUser extends Model
{

    

    

    // 表名
    protected $table = 'car_user';
    
    // 自动写入时间戳字段
//    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
//    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    public function user()
    {
        return $this->belongsTo('user', 'user_id')->setEagerlyType(0);
    }
    







}

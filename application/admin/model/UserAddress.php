<?php

namespace app\admin\model;

use think\Model;

class UserAddress extends Model
{

    // 表名,不含前缀
    protected $name = 'user_address';
    
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}

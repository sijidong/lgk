<?php

namespace app\common\model;

use think\Model;

class Locks extends Model
{

    // 表名,不含前缀
    protected $name = 'locks';
    
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}

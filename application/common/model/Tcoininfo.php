<?php

namespace app\common\model;

use think\Model;

class Tcoininfo extends Model
{

    // 表名,不含前缀
    protected $name = 'tcoininfo';
    
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}

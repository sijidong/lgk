<?php

namespace app\admin\model;

use think\Model;

class WalletTransferOut extends Model
{

    // 表名,不含前缀
    protected $name = 'wallet_transfer_out';
    
    protected $autoWriteTimestamp = false;
    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
}

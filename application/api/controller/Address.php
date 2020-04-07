<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Area;

/**
 * 首页接口
 */
class Address extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 默认地址
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDefault()
    {
        $address['province'] = Area::field('name,id')->where('pid', 0)->select();
        $address['city'] = [];
        $address['district'] = [];
        if (!empty($address['province'][0]['id'])) {
            $address['city'] = Area::field('name,id')->where('pid', $address['province'][0]['id'])->select();
            if (!empty($address['city'][0]['id'])) {
                $address['district'] = Area::field('name,id')->where('pid', $address['city'][0]['id'])->select();
            }
        }
        $this->success('ok',$address);
    }

    /**
     * 根据省得到市
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCity()
    {
        $city_id = $this->request->param('city_id');

        $address['city'] = Area::field('name,id')->where('pid', $city_id)->select();
        if (!empty($address['city'][0]['id'])) {
            $address['district'] = Area::field('name,id')->where('pid', $address['city'][0]['id'])->select();
        }

        $this->success('ok', $address);
    }

    /**
     * 根据市得到区
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDistrict()
    {
        $district_id = $this->request->param('district_id');

        $data = Area::field('name,id')->where('pid', $district_id)->select();

        $this->success('ok', $data);
    }
}

<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Sms as Smslib;
use app\common\model\User;
use think\Env;

/**
 * 手机短信接口
 */
class Sms extends Api
{
    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    /**
     * 发送验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     */
    public function send()
    {
        $mobile = $this->request->request("mobile");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';

        $area = $this->request->post('area');
        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }
        $last = Smslib::get($mobile, $event);
        if ($last && time() - $last['createtime'] < 60) {
            $this->error(__('发送频繁'));
        }
        $ipSendTotal = \app\common\model\Sms::where(['ip' => $this->request->ip()])->whereTime('createtime', '-1 hours')->count();
        if ($ipSendTotal >= 15) {
            $this->error(__('发送频繁'));
        }
        if ($event) {
            $userinfo = User::getByMobile($mobile);
            if ($event == 'register' && $userinfo) {
                //已被注册
                $this->error(__('已被注册'));
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                //被占用
                $this->error(__('已被占用'));
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                //未注册
                $this->error(__('未注册'));
            }
        }
//        if ($area == 86) {
        $ret = true;
        if (!Env::get('app.debug')) {
            $ret = Smslib::send($mobile, null, $event);
        }
//        } else if ($area == 852) {
//            $ret = $this->xsend($mobile,$event);
//        } else {
//            $ret = false;
//        }
        if ($ret) {
            $this->success(__('发送成功'));
        } else {
            $this->error(__('发送失败'));
        }
    }

    /**
     * 检测验证码
     *
     * @param string $mobile 手机号
     * @param string $event 事件名称
     * @param string $captcha 验证码
     */
    public function check()
    {
        $mobile = $this->request->request("mobile");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';
        $captcha = $this->request->request("captcha");

        if (!$mobile || !\think\Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('手机号不正确'));
        }
        if ($event) {
            $userinfo = User::getByMobile($mobile);
            if ($event == 'register' && $userinfo) {
                //已被注册
                $this->error(__('已被注册'));
            } elseif (in_array($event, ['changemobile']) && $userinfo) {
                //被占用
                $this->error(__('已被占用'));
            } elseif (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo) {
                //未注册
                $this->error(__('未注册'));
            }
        }
        $ret = Smslib::check($mobile, $captcha, $event);
        if ($ret) {
            $this->success(__('成功'));
        } else {
            $this->error(__('验证码不正确'));
        }
    }

    public function xsend($mobile,$event)
    {
        $this->error = '';

        $code = mt_rand(100000, 999999);
        $time = time();
        $ip = request()->ip();
        $sms = \app\common\model\Sms::create(['event' => $event, 'mobile' => $mobile, 'code' => $code, 'ip' => $ip, 'createtime' => $time]);

        $params = array(
            'appid' => '61069',
            'content' => '【OBE】你的验证码为：'.$code,
            'to' => $mobile,
            'sign_type' => 'md5',
            'appkey' => '938943ac7caf02876db7bc7f0acbc492'
        );
        $postArr = array(
            'appid' => '61069',
            'content' => '【OBE】你的验证码为：'. $code,
            'to' => $mobile,
            'sign_type' => 'md5',
            'timestamp' => $this->getTimestamp()
        );
        ksort($postArr);
        reset($postArr);
        $str = '';
        $i = 1;
        $count = count($postArr);
        foreach ($postArr as $key => $value) {
            $str .= $key.'='.$value;
            if ($i < $count) {
                $str .= '&';
            }
            $i ++;
        }
        $str = $params['appid'].$params['appkey'].$str.$params['appid'].$params['appkey'];
        $signature = md5($str);
        $postArr['signature'] = $signature;
        $options = [
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json; charset=utf-8'
            )
        ];

        $result = \fast\Http::sendRequest('https://api.mysubmail.com/internationalsms/send.json', json_encode($postArr), 'POST', $options);
        if ($result['ret'])
        {
            $res = (array) json_decode($result['msg'], TRUE);
            if (isset($res['status']) && $res['status'] == 'success')
                return TRUE;
            $this->error = isset($res['Message']) ? $res['Message'] : 'InvalidResult';
        }
        else
        {
            $this->error = $result['msg'];
        }
        return FALSE;
    }
}

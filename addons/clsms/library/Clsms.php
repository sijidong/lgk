<?php

namespace addons\clsms\library;

/**
 * 创蓝SMS短信发送
 * 如有问题，请加微信  andiff424  QQ:165607361
 */
class Clsms
{

    private $base_url = 'http://api.mysubmail.com/';
    private $_params = [];
    protected $error = '';
    protected $config = [];

    public function __construct($options = [])
    {
        if ($config = get_addon_config('clsms'))
        {
            $this->config = array_merge($this->config, $config);
        }
        $this->config = array_merge($this->config, is_array($options) ? $options : []);
    }

    /**
     * 单例
     * @param array $options 参数
     * @return Clsms
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static($options);
        }
        return self::$instance;
    }

    /**
     * 立即发送短信
     *
     * @return boolean
     */
    public function send()
    {
        $this->error = '';
        $params = $this->_params();

        $postArr = array(
            'appid' => $params['appid'],
            'content' => $params['content'],
            'to' => $params['mobile'],
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

        $result = \fast\Http::sendRequest($this->base_url.'message/send.json', json_encode($postArr), 'POST', $options);
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

    private function _params()
    {
        return array_merge([
            'appid'  => $this->config['appid'],
            'appkey'  => $this->config['appkey'],
            'sign'     => $this->config['sign'],
        ], $this->_params);
    }

    /**
     * 获取错误信息
     * @return array
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 短信类型
     * @param   string    $st       0验证码1会员营销短信（会员营销短信不能测试）
     * @return Clsms
     */
    public function smstype($st = 0)
    {
        $this->_params['smstype'] = $st;
        return $this;
    }

    /**
     * 接收手机
     * @param   string  $mobile     手机号码
     * @return Clsms
     */
    public function mobile($mobile = '')
    {
        $this->_params['mobile'] = $mobile;
        return $this;
    }

    /**
     * 短信内容
     * @param   string  $msg        短信内容
     * @return Clsms
     */
    public function msg($msg = '')
    {
        $this->_params['content'] = $this->config['sign'].$msg;
        return $this;
    }

    protected function getTimestamp()
    {
        $api= $this->base_url.'service/timestamp.json';
        $ch = curl_init($api) ;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ;
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
        $output = curl_exec($ch) ;

        $timestamp=json_decode($output,true);
        return $timestamp['timestamp'];
    }
}

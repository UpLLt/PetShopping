<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/8/9
 * Time: 14:39
 */

namespace Notify\Controller;


use Common\Model\NotifyModel;
use Common\Model\RechargeModel;
use Think\Log;

/**
 * 充值回调
 * Class AlipayRechargeController
 * @package Notify\Controller
 */
class AlipayRechargeController extends RechargeBaseController
{
    protected $order_info;

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $this->order_info = I('post.');
        vendor('Alipay.RSAfunction');
        vendor('Alipay.Corefunction');
        vendor('Alipay.Md5function');
        vendor('Alipay.Notify');
        vendor('Alipay.Submit');


        $alipay_config = C('ALIPAY_CONFIG');
        $alipayNotify = new \AlipayNotify($alipay_config);

        $verify = $alipayNotify->verifyNotify();
        if (!$verify) {
            Log::record('验证失败:' . json_encode(I('')), Log::WARN);
            echo "sign fail";
            exit;
        }

        if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS') {
            //微信返回参数
            $out_trade_no = $this->order_info['out_trade_no'];
            $total_fee = $this->order_info['total_fee'];

            //订单状态和金额检查
            if (!$this->chechValidity($out_trade_no, $total_fee)) {
                //错误日志
                Log::record('充值检测异常,订单号为:' . $out_trade_no . '/n', Log::WARN);
                exit;
            }

            //钱包金额变动
            if (!$this->compute(RechargeModel::PAY_TYPE_ALIPAY, json_encode($this->order_info))) {
                Log::record('alipay充值:' . json_encode($this->order_info), Log::WARN);
                exit;
            }
            echo "success";        //请不要修改或删除
        } else {
            Log::record('alipay充值:' . json_encode($this->order_info), Log::WARN);
            echo "sign fail";
        }
    }
}
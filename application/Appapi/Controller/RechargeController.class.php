<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/8/2
 * Time: 15:55
 */

namespace Appapi\Controller;

use Appapi\Model\RechargeModel;
use Order\Model\OrderModel;


/**
 * 充值接口
 * Class RechargeController
 * @package Appapi\Controller
 */
class RechargeController extends ApibaseController
{
    protected $recharge_model;

    public function __construct()
    {
        parent::__construct();
        $this->recharge_model = new RechargeModel();
    }

    public function UnifiedRecharge()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $price = I('post.price');
            $paytype = I('post.paytype');

            $this->checkparam([$token, $mid, $price, $paytype]);
            if (!$this->checktoken($mid, $token)) {
                exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
            }

            //维护
//            exit($this->returnApiError(ApibaseController::FATAL_ERROR,'系统维护,充值已关闭'));

            $out_trade_no = 'SN' . date('Ymd', time()) . $this->get_code(6, 5);
            if ($paytype == 'wxpay') {
                $data = [
                    'mid'          => $mid,
                    'out_trade_no' => $out_trade_no,
                    'total_fee'    => $price,
                    'create_time'  => time(),
                    'paytype'      => OrderModel::PAY_TYPE_WXPAY,

                ];
            } else {
                $data = [
                    'mid'          => $mid,
                    'out_trade_no' => $out_trade_no,
                    'total_fee'    => $price,
                    'create_time'  => time(),
                    'paytype'      => OrderModel::PAY_TYPE_ALIPAY,
                ];
            }

            $result = $this->recharge_model->add($data);
            if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '创建订单失败'));

            if ($paytype == 'wxpay') {

                //微信支付
                vendor('WxPayPubHelper.WxPayPubHelper');
                $unifiedOrder = new \UnifiedOrder_pub();
                $unifiedOrder->setParameter("body", "聚联汇充值");//商品描述
                $unifiedOrder->setParameter("out_trade_no", "$out_trade_no");//商户订单号
                $price = $price * 100;
                $unifiedOrder->setParameter("total_fee", $price);//总金额 $money_all
                $unifiedOrder->setParameter("notify_url", 'http://yaan.cdth.cn/Notify/WxRecharge/index');//通知地址
                $unifiedOrder->setParameter("trade_type", "APP");//交易类型
                $unifiedOrder->setParameter("device_info", "WEB");//设备号
                $unifiedOrder->setParameter("body", "充值");//商品描述

                $appparam = $unifiedOrder->getResultAppApi();
                if ($appparam) {
                    $data['Appparam'] = $appparam;

                } else {
                    $data['error'] = $unifiedOrder->result;
                    $this->recharge_model->delete($insert_id);
                }

            } else if ($paytype == 'alipay') {
                //支付宝
                vendor('Alipay.Corefunction');
                vendor('Alipay.Md5function');
                vendor('Alipay.Notify');
                vendor('Alipay.Submit');
                vendor('Alipay.RSAfunction');
                $alipay_res = new \AlipaySubmit(C('ALIPAY_CONFIG'));

                $para_sort = [
                    'service'        => 'mobile.securitypay.pay',
                    'partner'        => C('ALIPAY_CONFIG.partner'),
                    'seller_id'      => C('ALIPAY_CONFIG.partner'),
                    '_input_charset' => 'utf-8',
                    'notify_url'     => "http://" . $_SERVER['HTTP_HOST'] . '/Notify/AlipayRecharge/index',
                    'out_trade_no'   => $out_trade_no,
                    'subject'        => '充值',
                    'body'           => '余额充值',
                    'payment_type'   => '1',
                    'total_fee'      => $price,
                    'it_b_pay'       => '30m',
                    'return_url'     => C('ALIPAY_CONFIG.return_url'),
                ];

                $pay_string = $alipay_res->buildRequestParaToString($para_sort);

                $data['Appparam'] = $para_sort;

                $data['pay_string'] = $pay_string;  //后端签名支付
                exit($this->returnApiSuccess($data));


            } else {
                //余额
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '充值类型错误'));
            }

            exit($this->returnApiSuccess($data));

        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }
}
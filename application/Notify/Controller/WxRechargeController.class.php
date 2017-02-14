<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/8/2
 * Time: 15:44
 */

namespace Notify\Controller;

use Common\Model\RechargeModel;
use Think\Log;

/**
 * 微信充值回调
 * Class WxRechargeController
 * @package Notify\Controller
 */
class WxRechargeController extends RechargeBaseController
{

    public function __construct()
    {
        parent::__construct();
        header("Content-type:text/html;charset=utf-8");
        ini_set('date.timezone', 'Asia/Shanghai');
        vendor('WxPayPubHelper.WxPayPubHelper');
    }

    public function index()
    {
        $notify = new \Notify_pub();
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        if (!$xml) return false;
        $notify->saveData($xml);


        //签名状态
        $checkSign = true;
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL");//返回状态码
            $notify->setReturnParameter("return_msg", "签名失败");//返回信息
            $checkSign = false;
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
        }
        $returnXml = $notify->returnXml();

        echo $returnXml;
        if (!$checkSign) exit;

        $back_data = $notify->getData();
        $out_trade_no = $back_data['out_trade_no']; //订单号
        $total_fee = $back_data['total_fee'] / 100; //微信返回的是分，换算成元




        if (!$this->chechValidity($out_trade_no, $total_fee, json_encode($back_data))) {
            Log::record(json_encode($back_data), Log::WARN);
            exit;
        }

        if (!$this->compute(RechargeModel::PAY_TYPE_WX, json_encode($back_data))) {
            Log::record(json_encode($back_data), Log::WARN);
            exit;
        }
    }

    public function pcindex(){
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //回调错误
        if (!$xml){
            Log::record('微信支付回调校验失败:' . json_encode(I('')), Log::WARN);
            return false;
        }

        $notify->saveData($xml);
        //签名状态
        $checkSign = true;
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL");//返回状态码
            $notify->setReturnParameter("return_msg", "签名失败");//返回信息
            $checkSign = false;
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS");//设置返回码
        }
        $returnXml = $notify->returnXml();


        if (!$checkSign) {
            Log::record('微信支付回调校验失败:' . json_encode(I('')), Log::WARN);
            exit;
        }

        //通知微信，成功获取到相应的异步通知
        echo $returnXml;


        //微信返回参数
        $back_data = $notify->getData();
        $out_trade_no = $back_data['out_trade_no']; //订单号
        $total_fee = $back_data['total_fee'] / 100; //微信返回的是分，换算成元


        if (!$this->chechValidity($out_trade_no, $total_fee, json_encode($back_data))) {
            Log::record(json_encode($back_data), Log::WARN);
            exit;
        }

        if (!$this->compute(RechargeModel::PAY_TYPE_WX, json_encode($back_data))) {
            Log::record(json_encode($back_data), Log::WARN);
            exit;
        }
    }

}
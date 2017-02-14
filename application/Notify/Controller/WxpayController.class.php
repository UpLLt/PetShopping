<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/25
 * Time: 14:05
 */

namespace Notify\Controller;

use Common\Model\OrderModel;
use Common\Model\SmslogModel;
use Think\Log;


/**
 * 微信支付
 * Class WxpayController
 * @package Notify\Controller
 */
class WxpayController extends NotifybaseController
{

    private $smslog_model;
    public function __construct()
    {
        parent::__construct();
        $this->smslog_model = new SmslogModel();
        header("Content-type:text/html;charset=utf-8");
        ini_set('date.timezone', 'Asia/Shanghai');
        vendor('WxPayPubHelper.WxPayPubHelper');
    }

    public function index()
    {
        //使用通用通知接口
        $notify = new \Notify_pub();
        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //回调错误
        if (!$xml) {
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



        $order_sn = $back_data['out_trade_no']; //订单号

        $order = $this->order_model->where(['order_sn' => $order_sn])->find();
        if (!$order) {
            Log::record($order_sn . '订单编号不存在', Log::WARN);
            echo 'fail';
        }

        $data = [
            'status' => OrderModel::STATUS_PAY_SUCCESS,
            'pay_type' => OrderModel::PAY_TYPE_WX,
            'pay_time' => time(),
        ];

        $this->order_model->where(['order_sn' => $order_sn])->save($data);

        //如果是商品，修改库存  TODO
        if ($order['order_type'] == OrderModel::ORDER_TYPE_GOODS) {
            $this->product_change($order['id']);
        }

        if( $order['order_type'] == OrderModel::ORDER_TYPE_PET ){

            $username = $this->order_model
                ->where(['ego_order.id'=>$order['id']])
                ->join('LEFT JOIN ego_member on ego_member.id = ego_order.mid')
                ->field('ego_order.*,ego_member.username')
                ->find();


            $content = C('BUYPET_CONTENT');
            $data_cms = [
                'content'     => $content,
                'mobile'      => $username['username'],
                'create_time' => time(),
                'end_time'    => time()
            ];
            vendor("Cxsms.Cxsms");
            $options = C('SMS_ACCOUNT');

            $Cxsms = new \Cxsms($options);
            $result = $Cxsms->send($username['username'], $content);
            if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                $this->smslog_model->add($data_cms);
            }
        }


        if($order['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {
            $info = $this->order_model
                ->alias('a')
                ->join('LEFT JOIN ego_hospital as b ON a.id = b.order_sid')
                ->join('LEFT JOIN ego_hospital_shop as c ON b.hid = c.id')
                ->where(array('a.id' => $order['id']))
                ->field('c.sid')
                ->find();
            $rst = M('member_shop')
                ->where(array('id' => $info['sid']))
                ->setInc('balance', $order['order_price']);
            $data_s = [
                'status' => OrderModel::STATUS_COMPLETE,
            ];
            $result = $this->order_model->where(['order_sn' => $order_sn])->save($data_s);
            /*if(!$rst) {
                $iscommit = false;
            }*/
        }
    }

    public function PCnotify()
    {


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
        $order_sn = $back_data['out_trade_no']; //订单号
        $order = $this->order_model->where(['order_sn' => $order_sn])->find();
        $data = [
            'status' => OrderModel::STATUS_PAY_SUCCESS,
            'pay_type' => OrderModel::PAY_TYPE_WX,
            'pay_time' => time(),
        ];
       $this->order_model->where(['order_sn' => $order_sn])->save($data);

        if($order['order_type'] == OrderModel::ORDER_TYPE_HOSPITAL) {
            $info = $this->order_model
                ->alias('a')
                ->join('LEFT JOIN ego_hospital as b ON a.id = b.order_sid')
                ->join('LEFT JOIN ego_hospital_shop as c ON b.hid = c.id')
                ->where(array('a.id' => $order['id']))
                ->field('c.sid')
                ->find();
            $rst = M('member_shop')
                ->where(array('id' => $info['sid']))
                ->setInc('balance', $order['order_price']);

            $data_s = [
                'status' => OrderModel::STATUS_COMPLETE,
            ];
            $result = $this->order_model->where(['order_sn' => $order_sn])->save($data_s);
            /*if(!$rst) {
                $iscommit = false;
            }*/
        }

        if( $order['order_type'] == OrderModel::ORDER_TYPE_PET ){

            $username = $this->order_model
                ->where(['ego_order.id'=>$order['id']])
                ->join('LEFT JOIN ego_member on ego_member.id = ego_order.mid')
                ->field('ego_order.*,ego_member.username')
                ->find();


            $content = C('BUYPET_CONTENT');
            $data_a = [
                'content'     => $content,
                'mobile'      => $username['username'],
                'create_time' => time(),
                'end_time'    => time()
            ];
            vendor("Cxsms.Cxsms");
            $options = C('SMS_ACCOUNT');

            $Cxsms = new \Cxsms($options);
            $result = $Cxsms->send($username['username'], $content);
            if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                $this->smslog_model->add($data_a);
            }
        }

        //如果是商品，修改库存  TODO
        if ($order['order_type'] == OrderModel::ORDER_TYPE_GOODS) {
            $this->product_change($order['id']);
        }

    }





}
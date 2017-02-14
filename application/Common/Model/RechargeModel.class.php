<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/15
 * Time: 下午5:54
 */

namespace Common\Model;


class RechargeModel extends CommonModel
{

    const NOTIFY_STATUS_DEFAULT = 0;//notify 未收到回调
    const NOTIFY_STATUS_SUCCESS = 1;//notify 已收到回调


    const STATUS_WAIT_FOR_PAY = 0;  //等待付款
    const STATUS_PAY_SUCCESS = 1;  //支付成功

    const PAY_TYPE_ALIPAY = 1;  //支付宝支付
    const PAY_TYPE_WX = 2;      //微信支付
    const PAY_ADMIN  = 3;       //后台充值


    /**
     * 获取订单编号
     * @return string
     */
    public function getOrderNumber()
    {
        $out_trade_no = get_code(16, 1);
        if ($this->where(['out_trade_no' => $out_trade_no])->count() > 0)
            return $this->getOrderNumber();
        return $out_trade_no;
    }


    /**
     * 支付方式
     *
     * @param $type
     *
     * @return string
     */
    public function payTypetoString($type)
    {
        switch ($type) {
            case self::PAY_TYPE_ALIPAY:
                return '支付宝';
                break;
            case self::PAY_TYPE_WX:
                return '微信';
                break;
            case self::PAY_ADMIN:
                return '后台';
                break;
            default :
                return '';
                break;
        }
    }
}
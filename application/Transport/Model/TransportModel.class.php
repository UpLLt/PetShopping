<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:38
 */

namespace Transport\Model;


use Common\Model\OrderModel;

/**
 * 运输
 * Class TransportModel
 * @package Transport\Model
 */
class TransportModel extends OrderModel
{
    /*const ORDER_STATUS_WAIT_PAY = 1;//待付款
    const ORDER_STATUS_END = 2; //已完结
    const ORDER_STATUS_WAIT = 3; //待分配
    const ORDER_STATUS_ALLOT = 4;//已分配
    const ORDER_STATUS_CANCEL = 5;//已取消*/


    const PICK_UP_SEND = 1; //上门取
    const PICK_UP_OWN = 2; //客户送货上门


    /**
     * 返回状态
     *
     * @param $status
     *
     * @return string
     */
    function getStatus($status)
    {
        switch ($status) {
            case self::STATUS_WAIT_FOR_PAY:
                return "待付款";
                break;
            case self::STATUS_COMPLETE:
                return "已完结";
                break;
            case self::STATUS_PAY_SUCCESS:
                return "待分配";
                break;
            case self::STATUS_SEND:
                return "已分配";
                break;
            case self::STATUS_CANCEL:
                return "用户取消";
                break;
            default:
                return "";
            break;
        }
    }

    function getPickup($status)
    {
        switch ($status) {
            case self::PICK_UP_SEND:
                return "上门取货";
                break;
            case self::PICK_UP_OWN:
                return "客户送达";
                break;
        }
    }

}
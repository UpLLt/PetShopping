<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/28
 * Time: 10:03
 */

namespace Funeral\Model;


use Common\Model\OrderModel;

/**
 * 殡葬
 * Class BuriedModel
 * @package Funeral\Model
 */
class BuriedModel extends OrderModel
{

    const PICK_UP_SEND = 1; //上门取
    const PICK_UP_OWN  = 2; //客户送货上门

    const BURIED_METHOD_1 = 1;
    const BURIED_METHOD_2 = 2;
    const BURIED_METHOD_3 = 3;
    const BURIED_METHOD_4 = 4;
    const BURIED_METHOD_5 = 5;

    /**
     * 返回状态
     * @param $status
     * @return string
     */
    public function getStatus($status){
        switch($status){
            case self::STATUS_WAIT_FOR_PAY:
                return "待付款";
                break;
            case self::STATUS_COMPLETE:
                return "已完成";
                break;
            case self::STATUS_PAY_SUCCESS:
                return "待分配";
                break;
            case self::STATUS_SEND:
                return "已分配";
            case self::STATUS_CANCEL:
                return "用户取消";
                break;
            default:
                return "";
                break;
        }
    }

    public function getPickup($status){
        switch($status) {
            case self::PICK_UP_SEND:
                return "上门取货";
                break;
            case self::PICK_UP_OWN:
                return "送货上门";
                break;
        }
    }

    public function getMethod($status) {
        switch($status) {
            case self::BURIED_METHOD_1:
                return '普通埋葬';
            case self::BURIED_METHOD_2:
                return '深树埋葬';
            case self::BURIED_METHOD_3:
                return '豪华埋葬';
            case self::BURIED_METHOD_4:
                return '普通西式';
            case self::BURIED_METHOD_5:
                return '豪华西式';
        }
    }

    public function getStr($status) {
        switch($status) {
            case self::BURIED_METHOD_1:
                return 'bu_normal';
            case self::BURIED_METHOD_2:
                return 'bu_tree';
            case self::BURIED_METHOD_3:
                return 'bu_luxury';
            case self::BURIED_METHOD_4:
                return 'bu_normal_west';
            case self::BURIED_METHOD_5:
                return 'bu_luxury_west';

        }
    }


}
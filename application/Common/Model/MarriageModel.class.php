<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/28
 * Time: 17:13
 */

namespace Common\Model;


/**
 * 婚介
 * Class MarriageModel
 * @package Common\Model
 */
class MarriageModel extends OrderModel
{
    function getStatus($status){
        switch($status){
            case self::STATUS_WAIT_FOR_PAY:
                return '待付款';
            case self::STATUS_COMPLETE:
                return "已完成";
                break;
            case self::STATUS_PAY_SUCCESS:
                return "待联系";
                break;
            case self::STATUS_SEND:
                return "已联系";
                break;
            case self::STATUS_CANCEL:
                return "用户取消";
                break;
            default:
                return "";
                break;
        }
    }
}
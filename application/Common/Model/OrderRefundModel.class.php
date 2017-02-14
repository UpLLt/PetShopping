<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/12
 * Time: 下午5:44
 */

namespace Common\Model;


class OrderRefundModel extends CommonModel
{
    const STATUS_APPLY = 1; //申请
    const STATUS_APPLY_OK = 2;  //同意
    const STATUS_APPLY_NO = -1; // 拒绝
    const STATUS_APPLY_SEND = 3; //已发货
    const STATUS_GET_GOODS = 4; //收货
    const STATUS_BACK_BALANCE = 5; //退款成功


    public function getStatusToString($status)
    {
        switch ($status) {
            case self::STATUS_APPLY:
                return '申请中';
                break;
            case self::STATUS_APPLY_OK:
                return '申请通过';
                break;
            case self::STATUS_APPLY_NO:
                return '拒绝';
                break;
            case self::STATUS_APPLY_SEND:
                return '已发货';
                break;
            case self::STATUS_GET_GOODS:
                return '收货';
                break;
            case self::STATUS_BACK_BALANCE:
                return '退款成功';
                break;
            default :
                return '';
                break;
        }
    }


    /**
     * 修改状态
     *
     * @param $id
     * @param $status
     *
     * @return bool
     */
    public function setStatus($id, $status)
    {
        return $this->where(['id' => $id])->save(['status' => $status]);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/5
 * Time: 14:01
 */

namespace Merchant\Model;


use Common\Model\OrderModel;

class HospitalModel extends OrderModel
{
    const ORDER_STATUS_WAIT_PAY  = 1;//待付款
    const ORDER_STATUS_END  = 2; //已完成

    /**
     * 添加回复
     * @param $id
     * @param $reply
     * @return bool
     */
    public function addReply($id, $reply) {
        return $this->where(array('id' => $id))->save(array('ho_reply' => $reply, 'ho_status' => 2));
    }


    /**
     * 获取订单状态 option
     *
     * @param string $status
     *
     * @return string
     */
    public function getStatusOption($status = '')
    {

        $data[] = self::STATUS_WAIT_FOR_PAY;
        $data[] = self::STATUS_COMPLETE;

        $str_option = '';
        foreach ($data as $k => $v) {
            $state = '';
            if ($status == $v) $state = 'selected="selected"';
            $str_option .= '<option ' . $state . ' value="' . $v . '">' . $this->getStatustoString($v) . '</option>';
        }
        return $str_option;
    }

}
<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/1
 * Time: 19:28
 */

namespace Merchant\Model;


use Common\Model\CommonModel;

class HospitalShopModel extends CommonModel
{
    const CHECK_STATUS_WAIT = 1;//待审核
    const CHECK_STATUS_PASS = 2;//审核通过
    const CHECK_STATUS_NO = 3;//审核拒绝
    const CHECK_STATUS_FREEZE = 4;//已冻结
    const CHECK_STATUS_ON = 5;//解冻


    public function getStatus($status) {
        switch ($status) {
            case self::CHECK_STATUS_WAIT:
                return '待审核';
                break;
            case self::CHECK_STATUS_PASS:
                return '审核通过';
            break;
            case self::CHECK_STATUS_NO:
                return '审核拒绝';
            break;
            case self::CHECK_STATUS_FREEZE:
                return '已冻结';
            break;
            case self::CHECK_STATUS_ON:
                return '解冻';
                break;
            default:
                return '';
        }
    }

    /**
     * @param $hid
     * @return mixed
     * 获取医院名称
     */
    public function getHospitalName($hid){
        return $this->where(['id'=>$hid])->getField('hos_name');
    }


}
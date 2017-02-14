<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/28
 * Time: 下午5:13
 */

namespace Purchase\Model;


use Common\Model\PetModel;

class SellPetModel extends PetModel
{
    const SELL_PET_WAIT = 1;//待审核
    const SELL_PET_OK = 2;//审核通过
    const SELL_PET_REFUSE = 3;//审核拒绝
    const SELL_PET_COMPLETE = 4;//已完成

    /**
     * 我要卖宠物：修改状态
     * @param $id
     * @param $status
     * @return bool
     */
    public function editstatus($id, $status) {
        $where['id'] = $id;
        $data['status'] = $status;
        return $this->where($where)->save($data);
    }


    public function getStatusStr($status){
        switch($status){
            case self::SELL_PET_WAIT:
                return '待审核';
                break;
            case self::SELL_PET_OK:
                return '审核通过';
                break;
            case self::SELL_PET_REFUSE:
                return '审核拒绝';
                break;
            case self::SELL_PET_COMPLETE:
                return '已完成';
                break;
        }
    }
}
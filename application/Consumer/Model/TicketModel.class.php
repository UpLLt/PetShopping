<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 上午11:29
 */

namespace Consumer\Model;


use Common\Model\CommonModel;

class TicketModel extends CommonModel
{
    const TYPE_CURRENT = 2; // 全场通用
    const TYPE_REGISTER = 1; // 注册即送

    public function getTypeStr($ttype)
    {
        switch ($ttype) {
            case self::TYPE_CURRENT:
                return "全场通用";
                break;
            case self::TYPE_REGISTER:
                return "注册即送";
                break;
            default :
                return '';
                break;
        }
    }

}
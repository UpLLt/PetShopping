<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:38
 */

namespace Transport\Model;
use Common\Model\CommonModel;

class TransportRulesModel extends CommonModel
{

    /**
     * 获取笼子名称
     * @param $mark
     * @return string
     */
    function getCagename($mark){
        switch ($mark) {
            case mini:
                return '微型笼';
                break;
            case small:
                return '小型笼';
                break;
            case middle:
                return '中型笼';
                break;
            case big:
                return '中大型笼';
                break;
            case large:
                return '特大型笼';
                break;
            default:
                return '自带';
                break;
        }
    }


}
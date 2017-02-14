<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/25
 * Time: 17:23
 */

namespace Marriage\Model;


use Common\Model\CommonModel;

class WeddingRulesModel extends CommonModel
{
    public function getValue() {
        return $this->select();
    }

}
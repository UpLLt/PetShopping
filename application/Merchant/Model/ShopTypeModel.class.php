<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/1
 * Time: 18:30
 */

namespace Merchant\Model;


use Common\Model\CommonModel;

class ShopTypeModel extends CommonModel
{
    public function getType($id)
    {
        return $this->where(array('st_id' => $id))->field('st_name')->find();

    }
}
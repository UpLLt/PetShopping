<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/28
 * Time: 17:13
 */

namespace Common\Model;


class ProductOptionModel extends CommonModel
{

    /**
     * @param $id
     *
     * @return mixed
     */
    public function getOption($id)
    {
        return $this->where(['product_id' => $id])->select();
    }
}
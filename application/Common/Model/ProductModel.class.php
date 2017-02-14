<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/22
 * Time: 下午4:30
 */

namespace Common\Model;


/**
 * 宠物基类
 * Class PetModel
 * @package Issue\Model
 */
class ProductModel extends CommonModel
{

    public function getHotProduct($ids, $size = 10)
    {
        $result = $this->where(['hot' => 1, 'status' => 1])
            ->where(['category_id' => ['in', $ids]])
            ->order('id desc')
            ->limit(0, $size)
            ->field('id,pro_name,sales_volume,smeta,pro_shop_type,pro_thirdparty_url')
            ->select();

        $product_option = new ProductOptionModel();

        foreach ($result as $k => $v) {
            $result[$k]['cate_sign'] = 'product';
            $result[$k]['smeta'] = json_decode($v['smeta'], true);
            $result[$k]['cover'] = '';
            $result[$k]['price'] = '';
            if ($result[$k]['smeta'])
                $result[$k]['cover'] = setUrl($result[$k]['smeta'][0]['url']);
            unset($result[$k]['smeta']);
            $result[$k]['price'] = $product_option->where(['product_id' => $v['id']])->Min('option_price');
        }


        return $result;
    }
}
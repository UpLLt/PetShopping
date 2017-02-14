<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/21
 * Time: 14:48
 */

namespace Advertisement\Model;

use Common\Model\CommonModel;

class BannerModel extends CommonModel
{
    const TYPE_APP  = 1; //轮播图 APP

    const TYPE_COM  = 3; //轮播图 社区
    const TYPE_SIDE = 4;//轮播图 侧栏


    /**
     * 获取 banner
     *
     * @param $sign
     *
     * @return mixed
     */
    public function getBanner($sign, $type = '1')
    {
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'banner_image as b on a.id = b.banner_id';
        return $this->alias('a')
            ->join($join)
            ->where(['sign_key' => $sign, 'status' => 1, 'a.type' => $type])
            ->field('image,link, b.type')
            ->order('sort_order asc')
            ->select();
    }

}
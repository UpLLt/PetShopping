<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/22
 * Time: 15:03
 */

namespace Community\Model;


use Common\Model\CommonModel;

class ComTouchModel extends CommonModel
{
    public function addOne($mid, $art_id) {
        $data = array(
            'tou_member_id' => $mid,
            'tou_art_id' => $art_id
        );
        return $this->add($data);
    }
    /*
     * 查是否点赞
     */
    public function isClick($mid, $art_id) {
        $data = array(
            'tou_member_id' => $mid,
            'tou_art_id' => $art_id
        );
        return $this->where($data)->find();
    }
}
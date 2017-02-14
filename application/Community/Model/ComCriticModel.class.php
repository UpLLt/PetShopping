<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/20
 * Time: 16:33
 */

namespace Community\Model;


use Common\Model\CommonModel;

class ComCriticModel extends CommonModel
{
    /*
     * 添加/回复评论
     */
    public function addOne($content, $memberid, $art_id, $parent_id = 0, $parent_member_id = 0) {
        $data = array(
            'cri_content' => $content,//内容
            'cri_time' => time(),//时间
            'cri_member_id' => $memberid,//评论人id
            'cri_art_id' => $art_id,//被评论帖子id
            'cri_parent_id' => $parent_id,//父级评论id
            'cri_parent_member_id' => $parent_member_id//被评论人id
        );
        return $this->add($data);
    }
}
<?php
namespace Community\Model;
use Common\Model\CommentsModel;

/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/17
 * Time: 15:41
 */
class ComArticleModel extends CommentsModel
{
    const TAG_N = 1;
    const TAG_J = 2;
    const TAG_C = 3;
    const TAG_M = 4;
    /*
     * 添加单个
     */
    public function addOne($title, $content, $image, $memberid, $time) {
        $data = array(
            'art_title' => $title,//标题
            'art_content' =>$content,//内容
            'art_image' => $image,//图片地址
            'art_member_id' => $memberid,//发帖人id
            'art_time' => $time,//发帖时间
        );
        return $this->add($data);
    }
    /*
     * 删除单个帖子
     */
    public function deleteOne($id) {
        return $this->where(array('art_id'=>$id))->delete();
    }
    /*
     * 更新帖子数据(点赞数，评论数）
     */
    public function updateArt($id, $gnum, $cnum) {
        $data = array(
            'art_gnum' => $gnum,
            'art_cnum' => $cnum
        );
        return $this->where(array('art_id' => $id))->save($data);
    }

    /**
     * 更新帖子标签
     * @param $id
     * @param $data
     */
    public function saveTag($id,$data)
    {
        return $this->where(array('art_id' => $id))->save($data);
    }

    /*
     * 查询单个帖子信息
     */
    public function oneDetail($id, $field = array()) {
        return $this->where(array('art_id' => $id))->field($field)->find();
    }

    public function gettype($type)
    {
        switch ($type) {
            case self:: TAG_N:
                return '最新帖子';
            case self:: TAG_J:
                return '精华帖';
            case self:: TAG_C:
                return '宠物小知识';
            case self:: TAG_M:
                return '我的话题';
        }
    }

}
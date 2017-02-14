<?php
namespace Community\Model;
use Common\Model\CommentsModel;

/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/17
 * Time: 15:41
 */
class ComKnowModel extends CommentsModel
{
    public function detail($id) {
        return $this->where(array('kno_id' => $id))->find();
    }
    /*
     * 删除单个
     */
    public function deleteOne($id) {
        return $this->where(array('kno_id'=>$id))->delete();
    }
    /*
     * 数据
     */
    public function updateKnow($id, $data) {
        return $this->where(array('kno_id' => $id))->save($data);
    }

    /*
     * 问答搜索
     */
    public function searchList($keyword, $num) {
        $where['kno_keyword']  = array('like', '%'.$keyword.'%');
        return $this->where($where)->limit(15)->select();
    }
}
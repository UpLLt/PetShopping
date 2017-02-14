<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/11/21
 * Time: 13:51
 */

namespace Community\Model;


use Common\Model\CommonModel;

/**
 * 积分记录
 * Class ComRecordModel
 * @package Community\Model
 */
class ComRecordModel extends CommonModel
{
    /**
     * 增加记录
     * @param     $num
     * @param     $path
     * @param     $mid
     * @param int $rec_ioe  1/2  收入/支出
     *
     * @return mixed
     */
    public function addOne($num, $path, $mid ,$rec_ioe = 1) {
        $data = array(
            'rec_num' =>$num,
            'rec_path' =>$path,
            'rec_time' => time(),
            'rec_member_id' => $mid,
            'rec_ioe' => $rec_ioe
        );
        return $this->add($data);
    }

    /*
     * 是否已签到
     */
    public function is_sign($id) {
        $starttime = strtotime(date('Y-m-d'));
        $endtime = strtotime(date('Y-m-d'.' 23:59:59'));
        $where['rec_path'] = '签到获取';
        $where['rec_time'] = array('between',array($starttime,$endtime));
        $where['rec_member_id'] = $id;
        return $this->where($where)->find();
    }

    public function getSum($mid) {
        $where['rec_member_id'] = $mid;
        $where['rec_time'] = array('gt', strtotime(date('Y-m-d')));
        $where['rec_path'] = array('in', '发帖获取,评论获取,签到获取');
        return $this->where($where)->sum('rec_num');
    }
}
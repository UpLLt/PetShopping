<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/14
 * Time: 11:44
 */

namespace Common\Model;


class PushmsgModel extends CommonModel
{

    const MSG_TYPE_VACCINE = 1;//疫苗
    const MSG_TYPE_INSECT = 2;//驱虫
    const MSG_TYPE_ALL = 3;//全场推送

    const READ_STATUS_NO = 1;//未读
    const REDA_STATUS_YES = 2;//已读

    const PUSH_STATUS_NO = 1;//未推送
    const PUSH_STATUS_YES = 2;//已推送

    public function editRead($id,$status = 2) {
        return $this->where(array('id' => $id))->save(array('push_read' => $status));
    }

}
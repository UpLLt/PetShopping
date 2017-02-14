<?php
/**
 * Created by PhpStorm.
 * User: yyq
 * Date: 2016/12/2
 * Time: 15:23
 */

namespace Merchant\Model;


use Common\Model\CommonModel;

class MemberShopModel extends CommonModel
{
    public function addMember($user_login, $password) {
        $data = array(
            'user_login' => $user_login,
            'user_pass' => sp_password($password),
            'create_time' => time(),
            'taking_pass' => sp_password(substr($user_login, -6)),
        );
        return $this->add($data);
    }

    /**
     * 修改用户状态，冻结、解冻
     * @param $id
     * @param $status
     * @return bool
     */
    public function editStatus($id, $status) {
        $where['id'] = $id;
        $data['status'] = $status;
        return $this->where($where)->save($data);
    }

    /**
     * 获取用户信息
     * @param $where
     * @return mixed
     */
    public function getInfo($where) {
        return $this->where($where)->find();
    }

    /**
     * 修改登录密码
     * @param $id
     * @param $password
     * @return bool
     */
    public function editPass($id, $password) {
        return $this->where(array('id' => $id))->save(array('user_pass' => sp_password($password)));
    }

    public function getMmeberShopUser($id){
        return $this->where(array('id' => $id))->getField('user_login');
    }


    /**
     * 修改支付密码
     * @param $id
     * @param $password
     * @return bool
     */
    public function editPay($id, $password) {
        return $this->where(array('id' => $id))->save(array('taking_pass' => sp_password($password)));
    }

    /**
     * 提现操作余额
     * @param $id
     * @param $money
     * @return bool
     */
    public function editBalance($id, $money) {
        return $this->where(array('id' => $id))->setDec('balance', $money);
    }
}
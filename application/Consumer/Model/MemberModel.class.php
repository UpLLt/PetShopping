<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/9
 * Time: 14:39
 */

namespace Consumer\Model;

use Common\Model\CommonModel;
use Community\Model\ComScoreModel;

class MemberModel extends CommonModel
{

    const SEX_DEFAULT = 0;
    const SEX_BOY = 1;
    const SEX_GIRL = 2;

    //自动验证
    protected $_validate = [
        //array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
//        array('username', 'require', '用户名必填！', 1, 'regex', CommonModel:: MODEL_INSERT),
        ['username', '', '该手机号码已经被注册，不能重复注册。', 0, 'unique', CommonModel:: MODEL_INSERT],
        ['paypassword', '6,20', '密码长度为6-20位', 0, 'length', CommonModel:: MODEL_UPDATE],
    ];

    public function getNickNameByid($id)
    {
        return $this->where(['id' => $id])->getField('nickname');
    }
    public function getNickNameByHeading($id)
    {
        return setUrl( $this->where(['id' => $id])->getField('headimg') );
    }

    /**
     * 检查密码
     *
     * @param $id
     * @param $password
     *
     * @return bool
     */
    public function check_user_password($id, $password)
    {
        $pay_world = $this->where(['id' => $id])->getField('pay_password');
        return sp_compare_password($password, $pay_world);
    }

    public function getUserNameByid($id)
    {
        return $this->where(['id' => $id])->getField('username');
    }

    public function getUseridBybindcode($bindcode)
    {
        return $this->where(['code' => $bindcode])->getField('id');
    }

    public function getUseridByCode($code)
    {
        return $this->where(['code' => $code])->getField('id');
    }

    public function getUserDataByCode($code, $filed = '')
    {
        return $this->field($filed)->where(['code' => $code])->find();
    }

    public function checkUserName($phone)
    {
        return $this->where(['username' => $phone])->count();
    }

    /**
     * 获取剩余抽奖次数
     *
     * @param $mid
     *
     * @return mixed
     */
    public function getRaffleByMid($mid)
    {
        return $this->where(['id' => $mid])->getField('raffle');
    }

    public function raffleAdd($mid)
    {
        return $this->save(["id" => $mid, "raffle" => ["exp", "raffle+1"]]);
    }

    public function raffleSub($mid)
    {
        if ($this->getRaffleByMid($mid) < 1) return false;
        return $this->save(["id" => $mid, "raffle" => ["exp", "raffle-1"]]);
    }


    /**
     * 获取userid
     *
     * @param $bindcode_store
     *
     * @return mixed
     */
    public function getUseridBystorecode($bindcode_store)
    {
        return $this->where(['bindcode_store' => $bindcode_store])->getField('id');
    }


    public function getSexTostring($sex)
    {
        switch ($sex) {
            case self::SEX_BOY:
                return '男';
                break;
            case self::SEX_GIRL:
                return '女';
                break;
            default:
                return '未知';
                break;
        }
    }


    /**
     * 注册
     *
     * @param $username
     * @param $password
     *
     * @return bool
     */
    public function register($username, $password)
    {
        if (empty($username) && empty($password))
            return false;

        $wallet_model = new WalletModel();
        $com_score_model = new ComScoreModel();
        $ticket_model = new TicketModel();
        $coupon_model = new CouponModel();
        $iscommit = true;
        $this->startTrans();

        $data = [
            'username'    => $username,
            'password'    => sp_password($password),
            'create_time' => time(),
            'update_time' => time(),
        ];


        $mid = $this->add($data);

        //注册时增加 优惠劵
        $ticket_mo = $ticket_model->where(['ttype' => 1])->select();
        if( $ticket_mo ){
            foreach( $ticket_mo as $k => $v ){
                $coupon[] = [
                    'mid'=> $mid,
                    'tid'=> $v['id'],
                    'coupon_number' =>$coupon_model->getCouponnumber(),
                    'create_time' => time(),
                    'expiration_time' => strtotime("+".$v['validity']." day"),
                    'cou_type' =>$v['ttype'],
                    'cou_status'=>CouponModel::STATUS_VALIDITY,
                ];
            }

            $coupon_model->addAll($coupon);
        }




        //初始化钱包
        if (!$wallet_model->init($mid)) $iscommit = false;
        //初始化积分
        if (!$com_score_model->init($mid)) $iscommit = false;

        if ($iscommit) {
            $this->commit();
            return true;
        } else {
            $this->rollback();
            return false;
        }
    }


    /**
     * 修改支付密码
     *
     * @param $mid
     * @param $password
     *
     * @return bool
     */
    public function set_pay_password($mid, $password)
    {
        return $this->where(['id' => $mid])->save(['pay_password' => sp_password($password)]);
    }

}
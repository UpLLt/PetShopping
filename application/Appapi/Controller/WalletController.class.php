<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/7/15
 * Time: 12:33
 */

namespace Appapi\Controller;

use Common\Model\SmslogModel;
use Common\Model\WithdrawalsModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\MemberModel;
use Consumer\Model\TicketModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;


/**
 * 用户钱包操作
 * Class WalletController
 * @package Appapi\Controller
 */
class WalletController extends ApibaseController
{
    protected $wallet_model;
    protected $wallet_bill_model;
    protected $withdrawal_model;
    private $com_score_model, $com_record_model;
    private $coupon_model;
    private $ticket_model;
    private $member_model;
    private $smslog_model;


    public function __construct()
    {

        parent::__construct();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
        $this->withdrawal_model = new WithdrawalsModel();
        $this->coupon_model = new CouponModel();
        $this->ticket_model = new TicketModel();
        $this->member_model = new MemberModel();
        $this->smslog_model = new SmslogModel();
    }

    public function getBill()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $ie = I('post.type');
            $this->checkparam([$mid, $token]);
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $where = ['mid' => $mid, 'bill_type' => $ie];

            $result = $this->wallet_bill_model
                ->field('id,bill_amt,bill_source,bill_type,create_time')
                ->order('create_time desc')
                ->where($where)
                ->select();

            foreach ($result as $k => $v) {
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $result[$k]['create_time']);
                $data[$k]['source'] = $result[$k]['bill_source'];
                $data[$k]['bill_type'] = $this->wallet_bill_model->getStatusValues($result[$k]['bill_type']);
                $data[$k]['price'] = $v['bill_amt'];
            }


            exit($this->returnApiSuccess($data));
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }

    public function getWallet()
    {

        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');

            $this->checkparam([$mid, $token]);
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $balance = $this->wallet_model->getBalance($mid);
            $sco_member = $this->com_score_model->info($mid);

            $data['balance'] = $balance;
            $data['sco_now'] = $sco_member['sco_now'];

            exit($this->returnApiSuccess($data));
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }

    public function getBank()
    {


        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam([$mid, $token]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }
        $data = $this->withdrawal_model->getBank();
        exit($this->returnApiSuccess($data));
    }

    /**
     * 提现申请
     */
    public function Withdrawal()
    {
        if (IS_POST) {
            $mid = I('post.mid');
            $token = I('post.token');
            $money = I('post.money');
            $bank = I('post.bcode');
            $bank_card = I('post.bank_card');
            $realname = I('post.realname');
            $pay_password = I('post.pay_password');

            $where = [
                'id'       => $mid,
                'pay_password' => sp_password($pay_password),
            ];
            $members = $this->member_model
                ->where($where)
                ->find();
            if (!$members) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '支付密码不正确.'));
            $this->checkparam([$mid, $token, $bank, $money, $realname, $bank_card, $pay_password]);
            if (!$this->checktoken($mid, $token)) exit($this->returnApiError(ApibaseController::TOKEN_ERROR));

            $w = date('d', time());
            if ($w == 1 || $w == 20 || $w == 10) {

                $is_bank = $this->withdrawal_model->getBankStr($bank);
                if (empty($is_bank)) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '银行不正确'));


                if (strlen($bank_card) < 15) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '银行卡号格式不正确'));

//            if ($this->withdrawal_model->where(['wi_status' => WithdrawalsModel::EXAMINE_WAIT, 'mid' => $mid])->count() > 0)
//                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '有未处理完成的提现申请，请等待处理完毕'));

                //资金变动前的金额
                $before_change = $this->wallet_model->getBalance($mid);
                if ($before_change < $money) {
                    exit($this->returnApiError(ApibaseController::FATAL_ERROR, '余额不足'));
                }

                $data = [
                    'mid'          => $mid,
                    'wi_money'     => $money,
                    'wi_bank'      => $bank,
                    'wi_bank_card' => $bank_card,
                    'wi_bank_name' => $realname,
                    'wi_status'    => WithdrawalsModel::EXAMINE_WAIT,
                    'create_time'  => time(),
                    'wi_type'      => WithdrawalsModel::WIDTH_TYPE_USER,
                    'update_time'  => time(),
                ];

                if (!$this->withdrawal_model->create($data))
                    exit($this->returnApiError(ApibaseController::FATAL_ERROR, $this->withdrawal_model->getError()));

                $iscommit = true;
                $this->wallet_model->startTrans();

                //新增提现申请记录
                if (!$this->withdrawal_model->add($data))
                    $iscommit = false;

                //用户钱包操作
                $result_wallet = $this->wallet_model->submoney($mid, $money);
                if ($result_wallet === false) {
                    $iscommit = false;
                }
                //钱包流水记录
                $result_wallet_bill = $this->wallet_bill_model->addBill($mid, $money, $before_change, '用户提现', WalletBillModel::BILL_TYPE_OUT);
                if ($result_wallet_bill === false) {
                    $iscommit = false;
                }
                $username = $this->member_model->where(['id'=>$mid])->field('username')->find();

                $content = C('TIXIAN_CONTENT');
                $data = [
                    'content'     => $content,
                    'mobile'      => $username['username'],
                    'create_time' => time(),
                    'end_time'    => time()
                ];
                vendor("Cxsms.Cxsms");
                $options = C('SMS_ACCOUNT');

                $Cxsms = new \Cxsms($options);
                $result = $Cxsms->send( $username['username'] , $content);
                if ($result && $result['returnsms']['returnstatus'] == 'Success') {
                    $adds = $this->smslog_model->add($data);
                    if( !$adds )   $iscommit = false;
                } else {
                    exit(   $iscommit = false );
                }

                if ($iscommit) {
                    $this->wallet_model->commit();
                    exit($this->returnApiSuccess());
                } else {
                    $this->wallet_model->rollback();
                    exit($this->returnApiError(ApibaseController::FATAL_ERROR,'提现失败'));
                }
            } else {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '当前时间不允许提现'));

            }
        } else {
            exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        }
    }


    public function withdrawal_lists()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam([$mid, $token]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $lists = $this->withdrawal_model->where('mid=' . $mid)->select();
        foreach ($lists as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['status_str'] = $this->withdrawal_model->getStatusValus($v['wi_status']);
            $data[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            $data[$k]['price'] = $v['wi_money'];
            $data[$k]['stat_detail'] = $this->withdrawal_model->getValusString($v['wi_status']);
        }
        exit($this->returnApiSuccess($data));

    }

    /**
     * 优惠劵
     */
    public function coupon()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $cou_status = I('post.status');
        $total = I('post.total'); //当前订单金额

        $this->checkparam([$mid, $token, $cou_status]);
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $where = [
            'mid'             => $mid,
            'expiration_time' => ['gt', time()],  //过期时间大于当前时间
            'a.cou_status'    => $cou_status,
        ];

        if ($total)
            $where[] = [
                'b.full_use' => ['lt', $total],
            ];

        $this->coupon_model->CouponStatusChange();
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
        $lists = $this->coupon_model
            ->alias('a')
            ->where($where)
            ->join($join)
            ->field('a.*,b.price,b.ttype,b.full_use')
            ->select();

        foreach ($lists as $k => $v) {

            $lists[$k]['coupon_id'] = $v['coupon_id'];
            $lists[$k]['expiration_time'] = date('Y-m-d', $v['expiration_time']);
            $lists[$k]['price'] = $v['price'];
            $lists[$k]['coupon_number'] = $v['coupon_number'];
            $lists[$k]['ttype'] = '满'.$v['full_use'].'元使用';

        }

//        $result['sql'] = $this->coupon_model->getLastSql();
//        $result['list'] = $lists;

        exit($this->returnApiSuccess($lists));
    }


}
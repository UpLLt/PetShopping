<?php
namespace Web\Controller;

use Common\Model\RechargeModel;
use Common\Model\SmslogModel;
use Think\Controller;

use Common\Model\WithdrawalsModel;
use Community\Model\ComRecordModel;
use Community\Model\ComScoreModel;
use Consumer\Model\CouponModel;
use Consumer\Model\MemberModel;
use Consumer\Model\TicketModel;
use Consumer\Model\WalletBillModel;
use Consumer\Model\WalletModel;
/**
 * 资产管理
 * Class IndexController
 * @package Web\Controller
 */


class AssetController extends BaseController
{
    protected $wallet_model;
    protected $wallet_bill_model;
    protected $withdrawal_model;
    protected $com_score_model;
    protected $coupon_model;
    protected $ticket_model;
    protected $member_model;
    protected $com_record_model;
    protected $recharge_model;
    protected $smslog_model;


    public function __construct()
    {

        parent::__construct();
        $this->_initialize();
        $this->wallet_model = new WalletModel();
        $this->wallet_bill_model = new WalletBillModel();
        $this->com_score_model = new ComScoreModel();
        $this->com_record_model = new ComRecordModel();
        $this->withdrawal_model = new WithdrawalsModel();
        $this->coupon_model = new CouponModel();
        $this->ticket_model = new TicketModel();
        $this->member_model = new MemberModel();
        $this->recharge_model = new RechargeModel();
        $this->smslog_model = new SmslogModel();
    }


    public function _initialize()
    {
        vendor('Alipay_PC.Corefunction');
        vendor('Alipay_PC.Md5function');
        vendor('Alipay_PC.Notify');
        vendor('Alipay_PC.Submit');

    }

    /**
     * 我的钱包
     */
    public function wallet()
    {
        $this->is_login();
        $mid = session('mid');
        $this->getBill($mid);
        $this->getWallet($mid);
        $this->display();
    }

    public function getWallet($mid)
    {
        $balance = $this->wallet_model->getBalance($mid);
        $sco_member = $this->com_score_model->info($mid);
        $data['balance'] = $balance;
        $data['sco_now'] = $sco_member['sco_now'];

        $this->assign('wallet',$data);

    }

    public function getBill($mid)
    {

            $where = ['mid' => $mid];
            $_GET['mid']  = $mid;
            $count = $this->wallet_bill_model
                          ->where($where)
                          ->count();
            $page  = $this->page( $count,10 );
            $result = $this->wallet_bill_model
                ->field('id,bill_amt,bill_source,bill_type,create_time')
                ->order('create_time desc')
                ->limit($page->firstRow . ',' .$page->listRows)
                ->where($where)
                ->select();

            foreach ($result as $k => $v) {
                $data[$k]['create_time'] = date('Y-m-d H:i:s', $result[$k]['create_time']);
                $data[$k]['source'] = $result[$k]['bill_source'];
                $data[$k]['bill_type'] = $this->wallet_bill_model->getStatusValues($result[$k]['bill_type']);
                $data[$k]['price'] = $v['bill_amt'];
            }

            $this->assign('lists',$data);
            $this->assign('Page',$page->show('Admin'));

    }

    /**
     * 充值
     */
    public function recharge(){
        $this->is_login();
        $this->display();
    }

    /**
     * 用户提现
     */
    public function withdraw(){



        $this->is_login();
        $mid = session('mid');
        $this->withdrawal_lists($mid);
        $bank = $this->withdrawal_model->getBank();
        $this->assign('bank',$bank);
        $this->display();
    }

    /**
     * 提现申请
     */
    public function Withdrawal()
    {
            $this->is_login();
            $mid = session('mid');
            $money = I('post.money');
            $bank = I('post.bcode');
            $bank_card = I('post.bank_card');
            $realname = I('post.realname');
            $pay_password = I('post.pay_password');

            $where = [
                'id'       => $mid,
                'password' => sp_password($pay_password),
            ];
            $members = $this->member_model
                ->where($where)
                ->find();
            if (!$members) exit($this->error( '支付密码不正确.'));
            $this->checkparam([$mid, $bank, $money, $realname, $bank_card, $pay_password]);

            $w = date('d',time());

            if ($w == 1 || $w == 20 ||  $w == 10) {

                $is_bank = $this->withdrawal_model->getBankStr($bank);
                if (empty($is_bank)) exit($this->error('银行不正确'));


                if (strlen($bank_card) < 15) exit($this->error(  '银行卡号格式不正确'));

    //            if ($this->withdrawal_model->where(['wi_status' => WithdrawalsModel::EXAMINE_WAIT, 'mid' => $mid])->count() > 0)
    //                exit($this->error(  '有未处理完成的提现申请，请等待处理完毕'));

                //资金变动前的金额
                $before_change = $this->wallet_model->getBalance($mid);
                if ($before_change < $money) {
                    exit($this->error(  '余额不足'));
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
                    exit($this->error(  $this->withdrawal_model->getError()));

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

                $this->wallet_model->commit();

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

                    exit($this->success('提现成功'));
                } else {
                    $this->wallet_model->rollback();
                    exit($this->error( '提现失败' ));
                }
            }else{
                exit($this->error( '当前时间不允许提现' ));
            }
    }

    public function withdrawal_lists($mid)
    {
        $count = $this->withdrawal_model->where('mid=' . $mid)->count();
        $page  = $this->page($count,10);
        $lists = $this->withdrawal_model->where('mid=' . $mid)->limit($page->firstRow.','.$page->listRows)->select();

        foreach ($lists as $k => $v) {
            $data[$k]['id'] = $v['id'];
            $data[$k]['status_str'] = $this->withdrawal_model->getStatusValus($v['wi_status']);
            $data[$k]['create_time'] = date('Y-m-d', $v['create_time']);
            $data[$k]['price'] = $v['wi_money'];
            $data[$k]['stat_detail'] = $this->withdrawal_model->getValusString($v['wi_status']);
        }


        $this->assign('withdrawal',$data);
        $this->assign('Page',$page->show('Admin'));
    }



    /**
     * 优惠劵
     */
    public function coupon(){

        $this->is_login();
        $mid = session('mid');

        $total = I('post.total'); //当前订单金额


        if ($total) {
            $where = [
                'full_use' => ['lt', $total],
            ];
        }
        $where['mid'] = $mid;
        $this->coupon_model->CouponStatusChange();
        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'ticket as b on a.tid = b.id';
        $lists = $this->coupon_model
            ->alias('a')
            ->where(['mid' => $mid, 'expiration_time' => ['gt', time()]])
            ->where($where)
            ->join($join)
            ->field('a.*,b.price,b.ttype,b.full_use')
            ->select();

        foreach ($lists as $k => $v) {
            if( $v['cou_status'] ==  1 ){
                $data[$k]['full_use']  =  floor($v['full_use']) ;
                $data[$k]['coupon_id'] = $v['coupon_id'];
                $data[$k]['expiration_time'] = date('Y-m-d', $v['expiration_time']);
                $data[$k]['price'] = floor($v['price']);
                $data[$k]['coupon_number'] = $v['coupon_number'];
                $data[$k]['ttype'] = $this->ticket_model->getTypeStr($v['ttype']);
            }else{
                $list[$k]['full_use']  =   floor($v['full_use']);
                $list[$k]['coupon_id'] = $v['coupon_id'];
                $list[$k]['expiration_time'] = date('Y-m-d', $v['expiration_time']);
                $list[$k]['price'] = floor($v['price']);
                $list[$k]['coupon_number'] = $v['coupon_number'];
                $list[$k]['ttype'] = $this->ticket_model->getTypeStr($v['ttype']);
            }
        }

        sort($data);
        sort($list);

        $this->assign('notoverdue',$data);
        $this->assign('overdue',$list);
        $this->display();
    }

    public function UnifiedRecharge()
    {
        $mid   = session('mid');
        $price = I('get.price');

        $out_trade_no = $this->recharge_model->getOrderNumber();
        $insert_data = [
            'mid'           => $mid,
            'out_trade_no'  => $out_trade_no,
            'total_fee'     => $price,
            'status'        => RechargeModel::STATUS_WAIT_FOR_PAY,
            'notify_status' => RechargeModel::NOTIFY_STATUS_DEFAULT,
            'create_time'   => time(),
            'update_time'   => time(),
        ];

        if (!$this->recharge_model->create($insert_data))
            exit($this->error( $this->recharge_model->getError()));

        if (!$this->recharge_model->add())
            exit($this->error( '失败'));
            $config = C('WEIXINPAY_CONFIG');
            $order = [
                'body'         => '咪咻',
                'total_fee'    => $price * 100,
                'out_trade_no' => $out_trade_no,
                'product_id'   => $out_trade_no,
                'notify_url'   => $config['NOTIFY_RECHARGE_URL'],
            ];

            weixinpay($order);

    }


    public function doalipay()
    {

        $mid   = session('mid');
        $price = I('price');

        $out_trade_no = $this->recharge_model->getOrderNumber();
        $insert_data = [
            'mid'           => $mid,
            'out_trade_no'  => $out_trade_no,
            'total_fee'     => $price,
            'status'        => RechargeModel::STATUS_WAIT_FOR_PAY,
            'notify_status' => RechargeModel::NOTIFY_STATUS_DEFAULT,
            'create_time'   => time(),
            'update_time'   => time(),
        ];

        if (!$this->recharge_model->create($insert_data))
            exit($this->error( $this->recharge_model->getError()));

        if (!$this->recharge_model->add())
            exit($this->error( '失败'));
        $money_all = $price;

        $alipay_config = C('alipay_config');
        /**************************请求参数**************************/
        $payment_type = "1"; //支付类型 //必填，不能修改
        $notify_url = C('alipay.notify_recharge'); //服务器异步通知页面路径
        $return_url = C('alipay.return_url_recharge'); //页面跳转同步通知页面路径
        $seller_email = C('alipay.seller_email');//卖家支付宝帐户必填
        $out_trade_no = $out_trade_no;//商户订单号 通过支付页面的表单进行传递，注意要唯一！
        $subject = "咪咻";  //订单名称 //必填 通过支付页面的表单进行传递
        $total_fee = $money_all;   //付款金额  //必填 通过支付页面的表单进行传递
        $body = "咪咻";  //订单描述 通过支付页面的表单进行传递
        $show_url = U('Index/index');  //商品展示地址 通过支付页面的表单进行传递
        $anti_phishing_key = "";//防钓鱼时间戳 //若要使用请调用类文件submit中的query_timestamp函数
        $exter_invoke_ip = get_client_ip(); //客户端的IP地址
        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "create_direct_pay_by_user",
            "partner" => trim($alipay_config['partner']),
            "payment_type" => $payment_type,
            "notify_url" => $notify_url,
            "return_url" => $return_url,
            "seller_email" => $seller_email,
            "out_trade_no" => $out_trade_no,
            "subject" => $subject,
            "total_fee" => $total_fee,
            "body" => $body,
            "show_url" => $show_url,
            "anti_phishing_key" => $anti_phishing_key,
            "exter_invoke_ip" => $exter_invoke_ip,
            "_input_charset" => trim(strtolower($alipay_config['input_charset']))
        );

        //建立请求
        $alipaySubmit = new  \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter, "post", "确认");
        echo $html_text;
    }



}
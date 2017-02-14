<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/20
 * Time: 15:38
 */

namespace Common\Model;


class OrderModel extends CommonModel
{
    const ORDER_TYPE_GOODS = 1; //商品
    const ORDER_TYPE_PET = 2;   //宠物
    const ORDER_TYPE_TRANSPORT = 3;//运输
    const ORDER_TYPE_FUNERAL = 4;//殡仪
    const ORDER_TYPE_FOSTER = 5;//寄养
    const ORDER_TYPE_MARRIAGE = 6;//婚介
    const ORDER_TYPE_HOSPITAL = 7;//医疗


    const PAY_TYPE_ALIPAY = 1;  //支付宝支付
    const PAY_TYPE_WX = 2;      //微信支付
    const PAY_TYPE_BALANCE = 3; //余额支付


    //订单公共状态
    const STATUS_CANCEL = 1;        //用户取消  //取消
    const STATUS_WAIT_FOR_PAY = 2;  //待付款   //未付款
    const STATUS_PAY_SUCCESS = 3;  //已付款、待分配、待联系   //待发货
    const STATUS_SEND = 8;          //已发货、已分配、已联系   //待收货
    const STATUS_COMPLETE = 9;      //已完成


    const REFUND_STATUS_DEFAULT = 0; //退款默认状态
    const REFUND_STATUS_APPLY = 1; //申请退款
    const REFUND_STATUS_COMPLETE = 2; //退款完成

    //订单列表
    public $refundDescList = [
        [
            'id'         => 1,
            'name'       => '重复下单',
            'need_photo' => '1',
        ],
        [
            'id'         => 2,
            'name'       => '不想买了',
            'need_photo' => '1',
        ],
        [
            'id'         => 3,
            'name'       => '商品损坏',
            'need_photo' => '2',
        ],
    ];


    public function deleteOrder($id)
    {
        if (empty($id)) return false;

        return $this->save(['id' => $id, 'shows' => 0]);
    }

    /**
     * 获取订单状态 option
     *
     * @param string $status
     *
     * @return string
     */
    public function getStatusOption($status = '')
    {
        $data[] = self::STATUS_CANCEL;
        $data[] = self::STATUS_WAIT_FOR_PAY;
        $data[] = self::STATUS_PAY_SUCCESS;
        $data[] = self::STATUS_SEND;
        $data[] = self::STATUS_COMPLETE;

        $str_option = '';
        foreach ($data as $k => $v) {
            $state = '';
            if ($status == $v) $state = 'selected="selected"';
            $str_option .= '<option ' . $state . ' value="' . $v . '">' . $this->getStatustoString($v) . '</option>';
        }
        return $str_option;
    }


    public function getOrderNumber()
    {
        $num = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $num;
    }


    /**
     *
     * @param $id
     *
     * @return mixed
     */
    public function getRefundDesc($id)
    {
        foreach ($this->refundDescList as $k => $v) {
            if ($v['id'] == $id) {
                return $v['name'];
            }
        }
        return '退货理由';
    }

    /**
     * 获取订单类型中文描述
     *
     * @param $order_type
     *
     * @return string
     */
    public function getOrdrTypetoString($order_type)
    {
        switch ($order_type) {
            case self::ORDER_TYPE_GOODS:
                return '商品';
                break;
            case self::ORDER_TYPE_PET:
                return '宠物';
                break;
            case self::ORDER_TYPE_TRANSPORT:
                return '运输';
                break;
            case self::ORDER_TYPE_FUNERAL:
                return '殡仪';
                break;
            case self::ORDER_TYPE_FOSTER:
                return '寄养';
                break;
            case self::ORDER_TYPE_MARRIAGE:
                return '婚介';
                break;
            case self::ORDER_TYPE_HOSPITAL:
                return '医疗';
                break;
            default :
                return '未知类型';
                break;
        }
    }

    /**
     * 获取订单 code
     *
     * @param $order_type
     *
     * @return array|int
     */
    public function getOrderTypeByCode($order_type)
    {
        switch ($order_type) {
            case 'not_paid':
                $where['status'] = self::STATUS_WAIT_FOR_PAY;
                $where['order_type'] =
                    ['in',
                        OrderModel::ORDER_TYPE_GOODS //商品
                        . ',' .
                        OrderModel::ORDER_TYPE_PET //宠物
                        . ',' .
                        OrderModel::ORDER_TYPE_TRANSPORT //运输
                        . ',' .
                        OrderModel::ORDER_TYPE_FUNERAL //殡仪
                        . ',' .
                        OrderModel::ORDER_TYPE_FOSTER //寄养
                        . ',' .
                        OrderModel::ORDER_TYPE_MARRIAGE //婚介
//                       .',' .
//                        OrderModel::ORDER_TYPE_HOSPITAL //医疗
                ];
                return $where;
                break;
            case 'paid':
                $where['status'] = self::STATUS_PAY_SUCCESS;
                return $where;
                break;
            case 'sign':
                $where['status'] = self::STATUS_SEND;
                $where['order_type'] =
                    ['in',
                        OrderModel::ORDER_TYPE_GOODS //商品
                        . ',' .
                        OrderModel::ORDER_TYPE_PET //宠物
//                        . ',' .
//                        OrderModel::ORDER_TYPE_TRANSPORT //运输
//                        . ',' .
//                        OrderModel::ORDER_TYPE_FUNERAL //殡仪
//                        . ',' .
//                        OrderModel::ORDER_TYPE_FOSTER //寄养
//                        . ',' .
//                        OrderModel::ORDER_TYPE_MARRIAGE //婚介
//                       .',' .
//                        OrderModel::ORDER_TYPE_HOSPITAL //医疗
                ];
                return $where;
                break;
            case 'reviews':
                $where['status'] = self::STATUS_COMPLETE;
                $where['comment_status'] = 0;
                $where['returns_status'] = 0;
                $where['order_type'] = ['in',
                    OrderModel::ORDER_TYPE_GOODS
                    . ',' .
                    OrderModel::ORDER_TYPE_HOSPITAL
                    . ',' .
                    OrderModel::ORDER_TYPE_TRANSPORT //运输
                    . ',' .
                    OrderModel::ORDER_TYPE_FUNERAL //殡仪
                    . ',' .
                    OrderModel::ORDER_TYPE_FOSTER, //寄养
                ];
                return $where;
                break;
            case 'complete':
                $where['status'] = self::STATUS_COMPLETE;
                $where['comment_status'] = 1;
                $where['returns_status'] = ['neq', self::REFUND_STATUS_APPLY];
                $where['shows'] = 1;
                return $where;
                break;
            case 'refund':
                $where['status'] = self::STATUS_COMPLETE;
                $where['comment_status'] = 0;
                $where['returns_status'] = self::REFUND_STATUS_APPLY;
                return $where;
                break;
            default :
                $status = [self::STATUS_WAIT_FOR_PAY, self::STATUS_PAY_SUCCESS, self::STATUS_SEND, self::STATUS_COMPLETE];
                $ids = '';
                foreach ($status as $k => $v) {
                    $ids .= $ids ? ',' . $v : $v;
                }
                $where['status'] = ['in', $ids];
                return $where;
                break;
        }
    }


    /**
     * 默认获取订单状态
     *
     * @param $order_type
     *
     * @return string
     */
    public function getOrderTypetoString($order_type)
    {
        switch ($order_type) {
            case 'not_paid':
                return '待付款';
                break;
            case 'paid':
                return '已付款';
                break;
            case 'sign':
                return '待收货';
                break;
            case 'reviews ':
                return '待评价';
                break;
            case 'complete':
                return '已完成';
                break;
            case 'refund':
                return '待退款';
                break;
        }
    }




    /**
     * 默认获取订单状态
     *
     * @param $status
     *
     * @return string
     */
    public function getStatustoString($status)
    {
        switch ($status) {
            case self::STATUS_CANCEL:
                return '用户取消';
                break;
            case self::STATUS_WAIT_FOR_PAY:
                return '等待付款';
                break;
            case self::STATUS_PAY_SUCCESS:
                return '已付款';
                break;
            case self::STATUS_SEND:
                return '已发货';
                break;
            case self::STATUS_COMPLETE:
                return '已完成';
                break;
            default :
                return '未声明状态';
                break;
        }
    }

    /**
     * 默认获取医疗订单状态
     *
     * @param $status
     *
     * @return string
     */
    public function getStatusMertoString($status)
    {
        switch ($status) {

            case self::STATUS_WAIT_FOR_PAY:
                return '等待付款';
                break;
            case self::STATUS_COMPLETE:
                return '已完成';
                break;
            default :
                return '未声明状态';
                break;
        }
    }



    public function getStatus($order_sn)
    {
        if (empty($order_sn)) return false;

        return $this->where(['order_sn' => $order_sn])->getField('status');
    }


    /**
     * 修改订单状态
     *
     * @param $order_id
     * @param $status
     *
     * @return bool
     */
    public function setStatus($order_id, $status)
    {
        if (empty($order_id) && empty($status)) return 0;

        return $this->where(['id' => $order_id])->save(['status' => $status]);
    }


    /**
     * 更改活体宠物状态
     */
    public function getPetOrderStatus(){
        $where['status']      = self::STATUS_WAIT_FOR_PAY;
        $where['order_type']  = self::ORDER_TYPE_PET ;
        $where['create_time'] = array('ELT',strtotime("-10 minute"));

        $pet_order = $this->where($where)
                     ->alias('a')
                     ->join('LEFT JOIN ego_order_pet as b on a.id = b.order_id ')
                     ->field('a.id,b.product_pet_id')
                     ->select();

        $del_str = '';
        $productid = '';
        foreach( $pet_order as $k => $v ){
            $del_str .= empty($del_str) ? $v['id'] : ','.$v['id'];
            $productid .= empty($productid) ? $v['product_pet_id'] : ','.$v['product_pet_id'];
        }

        D('product_pet')->startTrans();
        $iscommit = true;

        $res_del = $this->delete($del_str);
        if( !$res_del ) $iscommit = false;

        $res_save = D('product_pet')->where(array('id'=>array('in',$productid)))->setField('status','0');

        if( !$res_save  ) $iscommit = false;


        if( $iscommit ){
            D('product_pet')->commit();
        }else{
            D('product_pet')->rollback();
        }
    }

    /**
     * 查询订单
     *
     * @param $mid
     * @param $order_id
     * @param $field
     *
     * @return bool|mixed
     */
    public function getOrderData($mid, $order_id, $field = '')
    {
        if (empty($mid) || empty($order_id)) return false;
        return $this->where(['id' => $order_id, 'mid' => $mid])->field($field)->find();
    }


    /**
     * 支付方式
     *
     * @param $type
     *
     * @return string
     */
    public function payTypetoString($type)
    {
        switch ($type) {
            case self::PAY_TYPE_ALIPAY:
                return '支付宝';
                break;
            case self::PAY_TYPE_WX:
                return '微信';
                break;
            case self::PAY_TYPE_BALANCE:
                return '余额';
                break;
            default :
                return '';
                break;
        }
    }

}
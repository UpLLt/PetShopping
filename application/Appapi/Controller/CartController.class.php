<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 10:57
 */

namespace Appapi\Controller;


use Common\Model\AddressModel;
use Common\Model\CartModel;
use Common\Model\OptionModel;
use Common\Model\ProductModel;
use Common\Model\ProductSkuModel;

class CartController extends ApibaseController
{
    private $cart_model;
    private $product_model;
    private $address_model;

    private $option_model;
    private $product_sku_model;

    public function __construct()
    {
        parent::__construct();
        $this->cart_model = new CartModel();
        $this->product_model = new ProductModel();
        $this->address_model = new AddressModel();

        $this->option_model = new OptionModel();
        $this->product_sku_model = new ProductSkuModel();
    }


    public function lists()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $group = array();
        $group = $this->cart_model
            ->where(array('mid' => $mid))
            ->field('date_day')
            ->group('date_day')
            ->order('date_day asc')
            ->select();



        foreach ($group as $k => $v) {
            $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'product as b on a.product_id = b.id'; //1
//            $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_option_value as c on a.option = c.product_option_value_id';
//            $join3 = 'LEFT JOIN ' . C('DB_PREFIX') . 'option_value as d on c.option_value_id = d.option_value_id';
            $join4 = 'LEFT JOIN ' . C('DB_PREFIX') . 'product_sku as e on e.sku_id = a.option';
            $result = $this->cart_model
                ->alias('a')
                ->join($join)
                ->join($join4)
                ->where(array('a.mid' => $mid, 'a.date_day' => $v['date_day']))
                ->order('a.id desc')
                ->field('a.id as cartid,a.mid,a.quantity,a.option,b.name as product_name ,a.option_values as option_value_name, b.price,b.original_price,b.inventory,ship_address,b.smeta,b.tariff,b.status,e.attr_option_path,e.quantity as inventory')
                ->select();

            foreach ($result as $key => $val) {
                //下架处理，将下架的商品从购物车中清除
                if (!$val['status']) {
                    unset($result[$key]);
                    $this->cart_model->delete($val['cartid']);
                    continue;
                }

                $result[$key]['smeta'] = json_decode($val['smeta'], true);
                $result[$key]['smeta'] = $result[$key]['smeta']['thumb'];
                $result[$key]['smeta'] = $this->geturl($result[$key]['smeta']);

                if ($val['option_price_prefix'] == '+') {
                    $result[$key]['price'] = $val['price'] + $val['option_price'];
                    $result[$key]['option_price_prefix'] = 'add';
                }

                if ($val['option_price_prefix'] == '-') {
                    $result[$key]['price'] = $val['price'] - $val['option_price'];
                    $result[$key]['option_price_prefix'] = 'sub';
                }

                //库存查询
//                $option_datas = $this->option_model->where(array('option_key_id' => array('in', $val['attr_option_path'])))->select();
//                $result[$key]['inventory'] = $option_datas['quantity'] ? $option_datas['quantity'] : $result[$key]['inventory'];
//                $result[$key]['option_value_name'] = $val['option_value_name'] ? $val['option_value_name'] : '官方标配';
            }

            $result = array_merge($result);

            $group[$k]['date_format'] = date('Y年m月d日', strtotime($v['date_day']));
            $group[$k]['lists'] = $result;

            unset($result);
            unset($val);
        }

        exit($this->returnApiSuccess($group));
    }

    public function addCart()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $product_id = I('post.product_id');
        $quantity = I('post.quantity');
        $option = I('post.option');
        $this->checkparam(array($mid, $token, $product_id, $quantity, $option));

        $this->checkisNumber(array($quantity));

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->product_model->find($product_id);
        if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR));

        $product_sku_data = $this->product_sku_model->where(array('sku_id' => $option, 'product_key_id' => $product_id))->find();
        if (!$product_sku_data) exit($this->returnApiError(ApibaseController::FATAL_ERROR, '产品不存在'));

        unset($result);

        $has = $this->cart_model
            ->where(array('product_id' => $product_id, 'option' => $option, 'mid' => $mid))
            ->select();

        if ($has) {
            $data = array(
                'quantity' => array("exp", "quantity+" . $quantity),
                'create_time' => time(),
            );
            $result = $this->cart_model->where(array('product_id' => $product_id, 'mid' => $mid, 'option' => $option))->save($data);
            if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        } else {

            $option_datas = $this->option_model->where(array('option_key_id' => array('in', $product_sku_data['attr_option_path'])))->select();
            foreach ($option_datas as $k => $v) {
                $option_value_name .= $v['option_name'];
            }

            $data = array(
                'mid' => $mid,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'create_time' => time(),
                'option' => $option,
                'option_values' => $option_value_name,
                'date_day' => date('Ymd', time()),
            );
            $result = $this->cart_model->add($data);
            if (!$result) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        }
        exit($this->returnApiSuccess());
    }


    public function editCart()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $mid = I('post.mid');
        $token = I('post.token');
        $cartid = I('post.cartid');
        $quantity = I('post.quantity');
        $this->checkparam(array($mid, $token, $quantity, $cartid));

        $this->checkisNumber(array($quantity));

        $cart_data = $this->cart_model->find($cartid);
        if (!$cart_data) exit($this->returnApiSuccess(ApibaseController::FATAL_ERROR, '请刷新购物车'));
        $product_id = $cart_data['product_id'];

        //最大库存
        $inventory = $this->product_model->where(array('id' => $product_id))->getField('inventory');

        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        if ($quantity > $cart_data['quantity']) {
            if ($quantity > $inventory) {
                exit($this->returnApiError(ApibaseController::FATAL_ERROR, '数量超过库存'));
            }
        } else {
            if ($quantity > $inventory) {
                $result = $this->cart_model
                    ->where(array('id' => $cartid))
                    ->save(array(
                        'quantity' => $inventory,
                        'create_time' => time(),
                    ));

                if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
                exit($this->returnApiSuccess());
            }
        }

        $result = $this->cart_model
            ->where(array('id' => $cartid))
            ->save(array(
                'quantity' => $quantity,
                'create_time' => time(),
            ));

        if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        exit($this->returnApiSuccess());
    }


    public function delProduct()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $cartid = I('post.cartid');

        $this->checkparam(array($mid, $token, $cartid));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->cart_model->delete($cartid);
        if ($result)
            exit($this->returnApiSuccess());
        else
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在，请刷新页面'));
    }


    public function addressList()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $this->checkparam(array($mid, $token));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->address_model
            ->where(array('mid' => $mid))
            ->order('id desc')
            ->select();
        $data['lists'] = $result;

        exit($this->returnApiSuccess($data));

    }


    public function addressAdd()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $fullname = I('post.fullname');
        $shopping_telephone = I('post.shopping_telephone');
        $address = I('post.address');

        $this->checkparam(array($mid, $token, $fullname, $shopping_telephone, $address));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data = array(
            'mid' => $mid,
            'fullname' => $fullname,
            'shopping_telephone' => $shopping_telephone,
            'address' => $address,
        );

        $result = $this->address_model->add($data);
        if ($result) exit($this->returnApiSuccess());
        else exit($this->returnApiError(ApibaseController::FATAL_ERROR));
    }

    public function addressEdit()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $addressid = I('post.id');
        $fullname = I('post.fullname');
        $shopping_telephone = I('post.shopping_telephone');
        $address = I('post.address');

        $this->checkparam(array($mid, $token, $fullname, $shopping_telephone, $address, $addressid));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $data = array(
            'fullname' => $fullname,
            'shopping_telephone' => $shopping_telephone,
            'address' => $address,
        );

        $result = $this->address_model
            ->where(array('id' => $addressid))
            ->save($data);

        if ($result === false) exit($this->returnApiError(ApibaseController::FATAL_ERROR));
        else  exit($this->returnApiSuccess());
    }

    public function addressDelete()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $mid = I('post.mid');
        $token = I('post.token');
        $addressid = I('post.id');
        $this->checkparam(array($mid, $token, $addressid));
        if (!$this->checktoken($mid, $token)) {
            exit($this->returnApiError(ApibaseController::TOKEN_ERROR));
        }

        $result = $this->address_model
            ->where(array('id' => $addressid))
            ->delete();

        exit($this->returnApiSuccess());
    }
}

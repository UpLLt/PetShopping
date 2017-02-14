<?php
/**
 * Created by PhpStorm.
 * User: long
 * Date: 2016/9/22
 * Time: 19:13
 */

namespace Appapi\Controller;


use Category\Model\CategoryModel;
use Common\Model\CommentModel;
use Common\Model\LogisticsTempModel;
use Common\Model\OrderModel;
use Common\Model\PetModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;

/**
 * 商品接口
 * Class ProductController
 * @package Appapi\Controller
 */
class ProductController extends ApibaseController
{
    private $product_model;
    private $product_option_model;
    private $category_model;
    private $pet_model;
    private $logisticsTemp_model;

    private $comment_model;

    public function __construct()
    {
        parent::__construct();
        $this->product_model = new ProductModel();
        $this->product_option_model = new ProductOptionModel();
        $this->category_model = new CategoryModel();
        $this->pet_model = new PetModel();
        $this->logisticsTemp_model = new LogisticsTempModel();
        $this->comment_model = new CommentModel();
    }


    /**
     * 分类
     */
    public function getCategory()
    {
        $result = $this->category_model
            ->field('id,parentid,name,tag')
            ->select();

        foreach ($result as $k => $v) {
            if ($v['parentid'] == 0)
                $parent[] = $v;
        }

        unset($v);

        foreach ($parent as $k => $v) {
            $v['name'] = '全部';
            $parent[$k]['child'][] = $v;
        }

        unset($v);
        foreach ($parent as $k => $v) {
            foreach ($result as $key => $value) {
                if ($v['id'] == $value['parentid'])
                    $parent[$k]['child'][] = $value;
            }
        }

        exit($this->returnApiSuccess($parent));
    }


    /**
     * 商品列表
     */
    public function getProductByCategory()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $category_id = I('post.category_id');
        $pet_type = I('post.pet_type');
        $page = I('post.page');
        $pagenum = I('post.pagenum');

        //可选参数
        $pet_body_id = I('post.pet_body_id');
        $pet_age_id = I('post.pet_age_id');
        $make_place = I('post.make_place');

        $this->checkparam([$category_id, $pet_type, $page, $pagenum]);

        $field = 'id,pet_type,pro_name,sales_volume,make_place,smeta,pro_shop_type,pro_thirdparty_url';

        $category = $this->category_model->find($category_id);

        if (0 == $category['parentid']) {
            $ids = $this->category_model->where(['parentid' => $category_id])->field('id')->select();
            $id_string = '';
            foreach ($ids as $k => $v) {
                $id_string .= $id_string ? ',' . $v['id'] : $v['id'];
            }
            $where['category_id'] = ['in', $id_string];
        } else {
            $where['category_id'] = $category_id;
        }

        $where['pet_type'] = $pet_type;

        if ($pet_body_id)
            $where['pet_body_id'] = $pet_body_id;
        if ($pet_age_id)
            $where['pet_age_id'] = $pet_age_id;
        if ($make_place)
            $where['make_place'] = $make_place;

        $count = $this->product_model
            ->where($where)
            ->where(['status' => 1])
            ->field($field)
            ->count();

        $result = $this->product_model
            ->where($where)
            ->where(['status' => 1])
            ->limit($page * ($page - 1), $pagenum)
            ->field($field)
            ->select();

        foreach ($result as $k => $v) {
            $result[$k]['price'] = $this->product_option_model->where(['product_id' => $v['id']])->Min('option_price');
            $result[$k]['price'] = sprintf("%0.2f", number_format($result[$k]['price'], 0));

            $result[$k]['make_place'] = $v['make_place'] == 1 ? '国产' : '进口';

            $result[$k]['smeta'] = json_decode($v['smeta'], true);
            $result[$k]['smeta'] = $result[$k]['smeta'][0]['url'];
            $result[$k]['smeta'] = $result[$k]['smeta'] ? $this->setUrl($result[$k]['smeta']) : $result[$k]['smeta'];
        }


        if ($count > 0) {
            $Totalpage = $count / $pagenum;
            $Totalpage = floor($Totalpage);
            $b = $count % $pagenum;
            if ($b) $Totalpage += 1;

            $data['Lists'] = $result;
            $data['Page'] = $page;
            $data['Totalpage'] = $Totalpage;

        } else {
            $data['Lists'] = [];
            $data['Page'] = 0;
            $data['Totalpage'] = 0;
        }

        exit($this->returnApiSuccess($data));
    }


    /**
     * 其他分类
     */
    public function getOtherCategory()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));

        $data['pet_body'] = array_merge([['id' => 0, 'name' => '全部']], $this->pet_model->body_type);
        $data['pet_foot_body'] = array_merge([['id' => 0, 'name' => '全部']], $this->pet_model->body_type_more);
        $data['pet_age'] = array_merge([['id' => 0, 'name' => '全部']], $this->pet_model->age);
        $data['pet_foot_age'] = array_merge([['id' => 0, 'name' => '全部']], $this->pet_model->age_more_type);
        $data['make_place'] = array_merge([['id' => 0, 'name' => '全部']], $this->pet_model->make_place);

        exit($this->returnApiSuccess($data));
    }


    /**
     * 详情
     */
    public function details()
    {
        if (!IS_POST) exit($this->returnApiError(ApibaseController::INVALID_INTERFACE));
        $product_id = I('post.product_id');

        $field = 'id,pet_type,category_id,pro_name,sales_volume,pet_body_id,pet_age_id
        ,make_place,logistics_id,pro_shop_type,pro_thirdparty_url,smeta';

        $product_data = $this->product_model
            ->field($field)
            ->where(['status' => 1])
            ->find($product_id);

        if (!$product_data)
            exit($this->returnApiError(ApibaseController::FATAL_ERROR, '商品不存在'));

        $product_data['smeta'] = json_decode($product_data['smeta'], true);
        $product_data['smeta'] = $product_data['smeta'] ? $product_data['smeta'] : [];
        foreach ($product_data['smeta'] as $k => $v) {
            $product_data['smeta'][$k]['url'] = $this->setUrl($product_data['smeta'][$k]['url']);
            unset($product_data['smeta'][$k]['alt']);
        }

        if ($product_data['smeta'])
            $product_data['cover'] = $product_data['smeta'][0]['url'];

        $product_data['pro_price'] = $this->product_option_model->where(['product_id' => $product_id])->Min('option_price');
        $product_data['logistics_price'] = $this->logisticsTemp_model->where(['temp_id' => $product_data['logistics_id']])->getField('in_price');

        /*$product_data['pro_price'] = 100;
        $product_data['logistics_price'] = 100;*/

        $option = $this->product_option_model->getOption($product_data['id']);

        $join2 = 'LEFT JOIN ' . C('DB_PREFIX') . 'member as b on a.mid = b.id';
        $join3 = 'LEFT JOIN ego_com_score as c on b.id = c.sco_member_id';
        $comments = $this->comment_model
            ->alias('a')
            ->join($join2)
            ->join($join3)
            ->where(['relevance_id' => $product_data['id'], 'status' => 2, 'order_type' => OrderModel::ORDER_TYPE_GOODS])
            ->field('a.star,a.full_name,a.content,a.create_time, a.replay, b.headimg, c.sco_level')
            ->limit(0, 50)
            ->select();

        unset($v);
        foreach ($comments as $k => $v) {
            $comments[$k]['create_time'] = dateDefault($v['create_time']);
            $comments[$k]['headimg'] = $this->setUrl($v['headimg']);
            $comments[$k]['sco_level'] = $v['sco_level'];
        }

        $product_data['options'] = $option ? $option : [];
        $product_data['comments'] = $comments;


        $product_data['web_view_url'] = $this->geturl('/Wap/Product/detail/id/' . $product_id);

        exit($this->returnApiSuccess($product_data));
    }


}
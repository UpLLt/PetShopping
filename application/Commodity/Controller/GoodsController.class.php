<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/21
 * Time: 下午6:04
 */

namespace Commodity\Controller;


use Category\Model\CategoryModel;
use Common\Controller\AdminbaseController;
use Common\Model\LogisticsTempModel;
use Common\Model\PetModel;
use Common\Model\ProductModel;
use Common\Model\ProductOptionModel;

class GoodsController extends AdminbaseController
{
    private $pet_model;
    private $category_model;
    private $product_model;
    private $logistis_temp_model;
    private $product_option_model;

    public function __construct()
    {
        $this->pet_model = new PetModel();
        $this->category_model = new CategoryModel();
        $this->product_model = new ProductModel();
        $this->logistis_temp_model = new LogisticsTempModel();
        $this->product_option_model = new ProductOptionModel();
        parent::__construct();
    }


    public function lists()
    {
        $this->_lists();
        $this->display();
    }

    private function _lists()
    {
        $fields = [
            'keyword' => ["field" => "a.pro_name", "operator" => "like", 'datatype' => 'string'],
            'pet_type' => ["field" => "a.pet_type", "operator" => "=", 'datatype' => 'string'],
            'id'      => ["field" => "a.id", "operator" => "=", 'datatype' => 'string'],
        ];

        $where_ands = [];
        if (IS_POST) {
            foreach ($fields as $param => $val) {
                if (isset($_POST[$param]) && !empty($_POST[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_POST[$param];
                    $_GET[$param] = $get;

                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        } else {
            foreach ($fields as $param => $val) {
                if (isset($_GET[$param]) && !empty($_GET[$param])) {
                    $operator = $val['operator'];
                    $field = $val['field'];
                    $datatype = $val['datatype'];
                    $get = $_GET[$param];
                    if ($operator == "like") {
                        $get = "%$get%";
                    }
                    if ($datatype == 'time')
                        $get = strtotime($get);
                    array_push($where_ands, "$field $operator '$get'");
                }
            }
        }

        $where = join(" and ", $where_ands);

        $join = 'LEFT JOIN ' . C('DB_PREFIX') . 'category as b on a.category_id = b.id';

        $count = $this->product_model
            ->alias('a')
            ->where($where)

            ->join($join)
            ->count();
        $page = $this->page($count, 20);
        $result = $this->product_model
            ->alias('a')
            ->join($join)
            ->order('id desc')
            ->where($where)
            ->limit($page->firstRow . ',' . $page->listRows)
            ->field('a.* , b.name as category_name')
            ->select();


        $tablebody = '';
        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] .= '<a class="js-ajax-btn-dialog" href="' . U('Goods/edit', ['id' => $v['id']]) . '">编辑</a>';
            $result[$k]['str_manage'] .= ' | ';
            $result[$k]['str_manage'] .= '<a class="js-ajax-delete" href="' . U('Goods/delete', ['id' => $v['id']]) . '">删除</a>';

            $v['pro_price'] = $this->product_option_model->where(['product_id' => $v['id']])->Avg('option_price');

            if($v['status'] == 1 && $v['hot'] == 0) {
                $hot = '<a class="" href="' . U('Goods/uphot', ['id' => $v['id'], 'hot' => 1]) . '">推荐上热门</a>';
            } elseif($v['status'] == 1 && $v['hot'] == 1) {
                $hot = '<a class="" href="' . U('Goods/uphot', ['id' => $v['id'], 'hot' => 0]) . '">下热门</a>';
            } else {
                $hot = '-';
            }
            if($v['status'] ==  0) {
                $updown = '下架 | '. '<a class="" href="' . U('Goods/updown', ['id' => $v['id'], 'status' => 1]) . '">上架</a>';
            }
            if($v['status'] ==  1) {
                $updown = '上架 | '.  '<a class="" href="' . U('Goods/updown', ['id' => $v['id'], 'status' => 0]) . '">下架</a>';
            }
            $tablebody .= '<tr>
               <td>' . ($k + 1) . '</td>
               <td>' . $v['id'] . '</td>
               <td>' . $this->pet_model->petTypetoString($v['pet_type']) . '</td>
               <td>' . $v['pro_name'] . '</td>
               <td>' . number_format($v['pro_price'], 2) . '</td>
               <td>' . $v['category_name'] . '</td>
               <td>' . $v['sales_volume'] . '</td>
               <td>' . $updown . '</td>
               <td>'.$hot.'</td>
               <td>' . $result[$k]['str_manage'] . '</td>
           </tr>';
        }

        $this->assign('formget', I(''));
        $this->assign('tablebody', $tablebody);
        $this->assign("Page", $page->show());
    }


    public function add()
    {
        $this->assign('PetOption', $this->pet_model->getPetOption());
        $this->assign('category', $this->category_model->getCategoryTree('', true));

        $this->assign('ageOption', $this->pet_model->getAgeOption());
        $this->assign('bodyOption', $this->pet_model->getBodyTypeOption());

        $this->assign('bodyMoreOption', $this->pet_model->getBodyTypeMoreOption());
        $this->assign('ageMoreOption',$this->pet_model->getAgeMoreOption());

        $this->assign('logOption', $this->logistis_temp_model->getOption());

        $this->display();
    }


    public function add_post()
    {
        if (IS_POST) {
            /*$images = upload_img('Good');
            if(empty($images)) {
                $this->error('封面不能为空');
            }*/
            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['post']['smeta'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
                }
            }

//            $post['cover'] = $images[0];
            $post = I('post.post');
            $post['pro_attr'] = json_encode($post['pro_attr']);
            $post['smeta'] = json_encode($post['smeta']);
            $post['content'] = htmlspecialchars_decode($post['content']);

            if (!empty($_POST['option_name']) && !empty($_POST['option_price']) && !empty($_POST['inventory'])) {
                foreach ($_POST['option_name'] as $key => $value) {
                    $product_option[] = [
                        "option_name"  => $value,
                        "option_price" => $_POST['option_price'][$key],
                        "inventory"    => $_POST['inventory'][$key],
                    ];
                }
            }

            if (!$this->product_model->create($post))
                $this->error($this->category_model->getError());

            foreach ($product_option as $k => $v) {
                if (!$this->product_option_model->create($v)) $this->error($this->product_option_model->getError());
            }
            unset($v);

            $this->product_model->startTrans();
            $iscommit = true;
            if (!$this->product_model->add($post)) $iscommit = false;

            $product_id = $this->product_model->getLastInsID();
            foreach ($product_option as $k => $v) {
                $v['product_id'] = $product_id;
                if (!$this->product_option_model->add($v)) $iscommit = false;
            }

            if ($iscommit) {
                $this->product_model->commit();
                $this->success('success');
            } else {
                $this->product_model->rollback();
                $this->error('error');
            }
        }
    }

    public function getAttr()
    {
        if (!IS_AJAX) exit;
        $id = intval(I('id'));
        $option = $this->category_model->where(['id'=>$id])->getField('parentid');

        if( $option == 1 ){
            $this->ajaxReturn(['msg' => $option, 'status' => 'success']);
        }else{
            $this->ajaxReturn(['msg' => '', 'status' => 'fail']);
        }
//        if (empty($id))
//            $this->ajaxReturn(['msg' => '', 'status' => 'fail']);
//        $option = $this->category_model->getAttTableBody($id);
//        if (!$option) $this->ajaxReturn(['msg' => '', 'status' => 'fail']);
//        $this->ajaxReturn(['msg' => $option, 'status' => 'success']);
    }


    public function delete()
    {
        $id = intval(I("get.id"));
        if (empty($id)) $this->error('empty');
        if ($this->product_model->delete($id) !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    public function edit()
    {
        $id = intval(I("get.id"));
        if (empty($id)) $this->error('empty');

        $result = $this->product_model->find($id);

        $product_option = $this->product_option_model->where(['product_id' => $id])->select();
        $opion = '';
        foreach ($product_option as $k => $v) {
            $opion .= '<tr>
                            <td>
                                <input name="option_key_id[]" type="hidden" value="' . $v['option_key_id'] . '">
                                <input name="product_id[]" type="hidden" value="' . $v['product_id'] . '">
                                <input name="option_name[]" required type="text" value="' . $v['option_name'] . '">
                            </td>
                            <td>
                                <input name="option_price[]" required type="text" value="' . $v['option_price'] . '">
                            </td>
                            <td>
                                <input name="inventory[]" required type="number" value="' . $v['inventory'] . '">
                                <img class="imgs"
                                     onclick="javascript:if($(\'#goods_attr\').find(\'tr\').length > 1){$(this).parent().parent().remove()};"
                                     style="width: 30px;height: 30px;"
                                     src="/public/images/reduce.png">
                            </td>
                        </tr>';
        }



//        $this->assign('PetOption', $this->pet_model->getPetOption());
//        $this->assign('category', $this->category_model->getCategoryTree('', true));
//
//        $this->assign('ageOption', $this->pet_model->getAgeOption());
//        $this->assign('bodyOption', $this->pet_model->getBodyTypeOption());
//
//        $this->assign('bodyMoreOption', $this->pet_model->getBodyTypeMoreOption());
//        $this->assign('ageMoreOption',$this->pet_model->getAgeMoreOption());
//
//        $this->assign('logOption', $this->logistis_temp_model->getOption());



        $this->assign('PetOption', $this->pet_model->getPetOption($result['pet_type']));
        $this->assign('category', $this->category_model->getCategoryTree($result['category_id'], true));

        $this->assign('ageOption', $this->pet_model->getAgeOption($result['pet_age_id']));
        $this->assign('bodyOption', $this->pet_model->getBodyTypeOption($result['pet_body_id']));

        $this->assign('bodyMoreOption', $this->pet_model->getBodyTypeMoreOption($result['pet_body_id']));
        $this->assign('ageMoreOption',$this->pet_model->getAgeMoreOption($result['pet_age_id']));

        $this->assign('logOption', $this->logistis_temp_model->getOption($result['logistics_id']));

        $result['pro_attr'] = json_decode($result['pro_attr'], true);
        $this->assign('category_attr', $this->category_model->getAttTableBody($result['category_id'], $result['pro_attr']));

        $this->assign('smeta', json_decode($result['smeta'], true));
        $this->assign('tablebody_option', $opion);

        $this->assign('data', $result);
        $this->display();
    }


    public function edit_post()
    {
        if (IS_POST) {
            if (!empty($_POST['photos_alt']) && !empty($_POST['photos_url'])) {
                foreach ($_POST['photos_url'] as $key => $url) {
                    $photourl = sp_asset_relative_url($url);
                    $_POST['post']['smeta'][] = ["url" => $photourl, "alt" => $_POST['photos_alt'][$key]];
                }
            }

            $post = I('post.post');
            $post['pro_attr'] = json_encode($post['pro_attr']);
            $post['smeta'] = json_encode($post['smeta']);
            $post['content'] = htmlspecialchars_decode($post['content']);

            if (!empty($_POST['option_name']) && !empty($_POST['option_price']) && !empty($_POST['inventory'])) {
                foreach ($_POST['option_name'] as $key => $value) {
                    $product_option[] = [
                        "product_id"   => $post['id'],
                        "option_name"  => $value,
                        "option_price" => $_POST['option_price'][$key],
                        "inventory"    => $_POST['inventory'][$key],
                    ];
                }
            }

            $error = '';

            if (!$this->product_model->create($post))
                $this->error($this->category_model->getError());

            foreach ($product_option as $k => $v) {
                if (!$this->product_option_model->create($v)) $this->error($this->product_option_model->getError());
            }
            unset($v);

            $this->product_model->startTrans();
            $iscommit = true;

            if ($this->product_model->save($post) === false) {
                $iscommit = false;
                $error .= "product_model_save \n";
            }

            if ($this->product_option_model->where(['product_id' => $post['id']])->delete() === false) {
                $iscommit = false;
                $error .= "delete " . $this->product_option_model->getLastSql() . " \n";
            }

            foreach ($product_option as $k => $v) {
                if (!$this->product_option_model->add($v)) {
                    $iscommit = false;
                    $error .= "add \n";
                }
            }

            if ($iscommit) {
                $this->product_model->commit();
                $this->success('success');
            } else {
                $this->product_model->rollback();
                $this->error('error');
            }
        }
    }

    /**
     * 推荐上/下首页热门
     */
    public function upHot(){
        $id = I('get.id');
        $hot = I('get.hot');

        if($hot) {
            $category = $this->product_model
                ->alias('a')
                ->join('LEFT JOIN ego_category as b on a.category_id = b.id')
                ->where(array('a.id' => $id))
                ->field('a.category_id, b.parentid')
                ->find();
            $cateids = $category['category_id'];
            if($category['parentid'] != 0 ) {
                $cateids = $this->category_model
                    ->where(array('parentid' => $category['parentid']))
                    ->getField('id', true);
            }
//            dump($cateids);//exit;
            $count = $this->product_model
                ->where(array('category_id' => array('in', $cateids), 'status' => 1, 'hot' => 1))
                ->count();
//            dump($count);exit;
            if($count >= 8) {
                $this->error('一种大类推荐热门不能超过8个，请先行取消其他热门再设置');
            }
        }
        $rst = $this->product_model
            ->where(array('id' => $id))
            ->setField('hot', $hot);
        if($rst) {
            $this->success('');
        } else {
            $this->error('失败，请重试');
        }

    }

    /**
     * 上下架
     */
    public function updown() {
        $id = I('get.id');
        $status = I('get.status');

        $rst = $this->product_model
            ->where(array('id' => $id))
            ->setField('status', $status);
        if($rst) {
            $this->success('');
        } else {
            $this->error('失败，请重试');
        }
    }
}
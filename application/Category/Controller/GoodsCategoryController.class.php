<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/11/30
 * Time: 下午7:47
 */

namespace Category\Controller;


use Category\Model\CategoryModel;
use Common\Controller\AdminbaseController;
use Common\Model\PetModel;

/**
 *
 * 商品分类
 * Class GoodsCategoryController
 * @package Category\Controller
 */
class GoodsCategoryController extends AdminbaseController
{
    private $category_model;
    private $pet_model;

    public function __construct()
    {
        parent::__construct();
        $this->category_model = new CategoryModel();
        $this->pet_model = new PetModel();
    }

    public function lists()
    {
        $this->_lists();
        $this->display();
    }


    public function _lists()
    {
        $result = $this->category_model->order('listorder asc')->select();
        $tree = new \Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';

        foreach ($result as $k => $v) {
            $result[$k]['str_manage'] = $v['parentid'] == 0 ? '<a href="' . U("GoodsCategory/add", ["parent" => $v['id']]) . '">添加子类</a> |' : '';
            $result[$k]['str_manage'] .= ' <a href="' . U("GoodsCategory/edit", ["id" => $v['id']]) . '">编辑</a> ';
//            $result[$k]['str_manage'] .= '| <a href="' . U("GoodsCategory/delete", ["id" => $v['id']]) . '">删除</a> ';
            $result[$k]['attr'] = json_decode($result[$k]['attr'], true);
//            $attr = '';
//            foreach ($result[$k]['attr'] as $key => $value) {
//                $attr .= $attr ? ' | ' . $value : $value;
//            }
//            $result[$k]['attr'] = $attr;
            $result[$k]['pet_type'] = $this->pet_model->petTypetoString($v['pet_type']);


        }


        $tree->init($result);
        $str = "<tr>
					<td><input name='listorders[\$id]' type='text' size='3' value='\$listorder' class='input input-order'></td>
					<td>\$id</td>
					<td>\$spacer\$name</td>

					<td>\$str_manage</td>
				</tr>";

        $taxonomys = $tree->get_tree(0, $str);
        $this->assign('categorys', $taxonomys);
    }

    public function add()
    {
//        $this->assign('PetOption', $this->pet_model->getPetOption());
        $this->_getCategoryTree(PetModel::PET_TYPE_CAT);
        $this->display();
    }


    private function _getCategoryTree($pet_type = 0)
    {
        $parentid = intval(I('get.parent'));
        $result = $this->category_model->select();
        foreach ($result as $k => $v) {
            $result[$k]['selected'] = $v['id'] == (!empty($parentid) && $v['id'] == $parentid) ? 'selected' : '';
//            if ($pet_type) {
//                if ($pet_type == $v['pet_type']) {
//                    unset($result[$k]);
//                }
//            }
        }
//        $result = array_merge($result);

        $tree = new \Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);
        $str = "<option value='\$id' \$selected>\$spacer\$name</option>";
        $taxonomys = $tree->get_tree(0, $str);
        $this->assign("category_tree", $taxonomys);
    }

    public function listorders()
    {
        $status = parent::_listorders($this->category_model);
        if ($status) {
            $this->success("排序更新成功！");
        } else {
            $this->error("排序更新失败！");
        }
    }

    public function edit()
    {
        $id = intval(I("get.id"));
        $data = $this->category_model->find($id);
        $madify_id = $this->category_model->where(['id' => $id])->field('parentid,id')->find();

        $taxonomys = '';
        if ($madify_id['parentid'] == 0) {
            $taxonomys = "<option value=' 0 '>一级父类</option>";
        } else {
            $id = $madify_id['parentid'];
            $category_opition = $this->category_model->where(['parentid' => 0])->select();
            foreach ($category_opition as $k => $v) {
                $selected = $id == $v['id'] ? 'selected' : '';
                $taxonomys .= "<option /$selected  value='" . $v['id'] . "'>" . $v['name'] . "</option>";
            }
        }

        $this->assign('PetOption', $this->pet_model->getPetOption($madify_id['pet_type']));

        $data['attr'] = json_decode($data['attr'], true);
        $this->assign('data', $data);
        $this->assign('category_tree', $taxonomys);
        $this->display();
    }

    public function add_post()
    {
        if (IS_POST) {
            $data = I('post.');
//            foreach ($data['attr'] as $k => $v) {
//                if (empty($v))
//                    unset($data['attr'][$k]);
//            }
//            $data['attr'] = json_encode($data['attr']);

            if ($this->category_model->create($data)) {
                if ($this->category_model->add()) {
                    $this->success('成功');
                } else {
                    $this->error('失败');
                }
            } else {
                $this->error($this->category_model->getError());
            }
        }
    }


    public function delete()
    {
//        $id = intval(I("get.id"));
//        $count = $this->category_model->where(["parentid" => $id])->count();
//
//        if ($count > 0) {
//            $this->error("该菜单下还有子类，无法删除！");
//        }
//
//        if ($this->category_model->delete($id) !== false) {
//            $this->success("删除成功！");
//        } else {
//            $this->error("删除失败！");
//        }
    }


    public function edit_post()
    {
        if (IS_POST) {
            $data = I('post.');
//            foreach ($data['attr'] as $k => $v) {
//                if (empty($v))
//                    unset($data['attr'][$k]);
//            }
//            $data['attr'] = json_encode($data['attr']);

            if ($this->category_model->create($data)) {
                if ($this->category_model->save() !== false) {
                    $this->success("修改成功！");
                } else {
                    $this->error("修改失败！", U('Category/lists'));
                }
            } else {
                $this->error($this->category_model->getError());
            }
        }
    }


    public function getCategoryPetType()
    {
        if (IS_AJAX) {
            $id = I('post.id');
            $result = $this->category_model->find($id);
            $option = '';
            if ($result['parentid'] == 0) {
                $option = '<option value="0">不区分</option>';
            } else {
//                $option = '<option value="0">不区分</option>';
                $option .= $this->pet_model->getPetOption();
            }
            $this->ajaxReturn(['msg' => $option]);
        }
    }

}
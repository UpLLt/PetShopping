<?php
/**
 * Created by PhpStorm.
 * User: yunlongw
 * Date: 2016/12/1
 * Time: 上午11:13
 */

namespace Category\Model;


use Common\Model\CommonModel;
use Think\Log;

class CategoryModel extends CommonModel
{
    /**
     * @return string
     */
    public function getCategoryTree($parentid = '', $disabled = false)
    {
        $result = $this->select();
        foreach ($result as $k => $v) {
            $result[$k]['selected'] = $v['id'] == (!empty($parentid) && $v['id'] == $parentid) ? 'selected' : '';
            if ($disabled) {
                $result[$k]['disabled'] = '';
                if ($v['parentid'] == 0 && $this->where(['parentid' => $v['id']])->count() > 0)
                    $result[$k]['disabled'] = 'disabled="disabled"';
            }
        }

        $tree = new \Tree();
        $tree->icon = ['&nbsp;&nbsp;&nbsp;│ ', '&nbsp;&nbsp;&nbsp;├─ ', '&nbsp;&nbsp;&nbsp;└─ '];
        $tree->nbsp = '&nbsp;&nbsp;&nbsp;';
        $tree->init($result);

        $str = "<option  value='\$id' \$disabled \$selected>\$spacer\$name</option>";

        return $tree->get_tree(0, $str);
    }


    /**
     * 获取 Attr
     *
     * @param $id
     *
     * @return mixed
     */
    public function getAttr($id)
    {
        $result = $this->field('attr')->find($id);
        return json_decode($result['attr'], true);
    }


    /**
     * 获取 tablebody temp
     *
     * @param $id
     *
     * @return string
     */
    public function getAttTableBody($id, $data = [])
    {
        $result = $this->getAttr($id);
        $option = '';
        foreach ($result as $k => $v) {
            $option .= '<tr>
                        <td><span>' . $v . '</span></td>
                        <td><input type="text" name="post[pro_attr][' . $v . ']" value="' . $data[$v] . '"></td>
                    </tr>';
        }
        return $option;
    }

    /**
     * @param $parentid
     *
     * @return mixed
     */
    public function getChild($parentid)
    {
        return $this->where(['parentid' => $parentid])->field('id')->select();
    }

    /**
     * @param $parentid
     *
     * @return string
     */
    public function getChildString($parentid)
    {
        $result = $this->getChild($parentid);
        $string = '';
        foreach ($result as $k => $v) {
            $string .= $string ? ',' . $v['id'] : $v['id'];
        }
        return $string;
    }
}
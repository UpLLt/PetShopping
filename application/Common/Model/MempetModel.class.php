<?php

namespace Common\Model;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/7/9
 * Time: 16:15
 */
class MempetModel extends PetModel
{
    //自动完成
    protected $_auto = array(
        array('create_time','time',1,'function'),

    );

}
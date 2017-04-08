<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/23
 * Time: 14:25
 */
namespace Admin\Controller;
use Think\Controller;
class GoodsController extends Controller{
    //添加商品
    public function add(){
        if(IS_POST){//判断是否有表单提交,如果有表单提交就处理, 没有表单提交就显示添加界面
            $goods = D('Goods');//实例化自定义模型
            if($create = $goods->create(I('post.'),1)){//过滤XSS攻击//1是增加,2是修改,不写自动判断
                $goods->addGoods($create) ? $this->success('添加成功', U('showAllGoods')) : $this->error('添加失败');
            }else{
                $this->error($goods->getError());
            }
        }else{
            $this->display('add');
        }
    }
    //商品显示主页
    public function showAllGoods(){
        $goods = D('Goods')->getAllgoods();//取出所有的数据
        $this->show = $goods['show'];//同$this->assign('show',$show);
        $this->goods = $goods['goods'];
        $this->display('showAllGoods');
    }
    //删除商品
    public function del(){
        $goods = D('Goods');
        $goods->del() ? $this->success('删除成功',U('showAllGoods')) : $this->error('删除失败');
    }

    //修改商品
    public function update(){
        if(IS_POST){//判断是否有表单提交,若有就修改,没有就显示修改界面
            $goods = D('Goods');
            if($create = $goods->create(I('post.'),2)){//过滤XSS攻击//1是增加,2是修改,不写自动判断
                false !== $goods->saveGoods($create) ? $this->success('修改成功', U('showAllGoods')) : $this->error('修改失败');
            }
        }else{//显示修改界面
            $goods = D('Goods');
            $data = $goods->getUpdateData();
            $this->data = $data;
            $this->display('update');
        }
    }
}



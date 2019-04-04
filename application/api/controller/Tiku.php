<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/4
 * Time: 10:05
 */

namespace app\api\controller;


use app\api\library\Base;
use app\common\controller\Api;
use app\common\model\item\ItemBank;
use app\common\model\item\ItemCategory;
use app\common\model\item\ItemInfo;
use fast\Tree;
use think\Db;


class Tiku extends Api
{

    protected $noNeedLogin='*';

    /**
     * 获取章节
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_category(){
        $type = $this->request->post('type','公务员');
        $where = [
            'c.type'=>$type
        ];
        $list = Db::table('item_category')
            ->alias('c')
            ->join('item_bank b','c.id=b.cid','LEFT')
            ->where($where)
            ->field('c.id,c.name,c.pid,c.level,COUNT(b.id) as num')
            ->group('c.id')
            ->select();
        $tree = new Tree();
        $tree->init($list,'pid');
        $tree_list = $tree->getTreeArray(0);
        $this->result('success',$tree_list,200);
    }

    /**
     * 获取列表
     * @throws \think\Exception
     */
    public function get_list(){
        $cid = $this->request->post('cid');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        if(!$cid) $this->error('缺少参数');
        $cid_list = ItemCategory::where('pid',$cid)->column('id');
        $cid_list[]=$cid;
        $where = [
            'cid' => ['IN',$cid_list]
        ];
        $total = ItemBank::where($where)->count('id');
        $list = ItemBank::where($where)
            ->group('rand()')
            ->limit($offset,$pageSize)
            ->column('id,type,content,val,a,b,c,d,analysis');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 获取资讯
     * @throws \think\Exception
     */
    public function get_zx(){
        $city = $this->request->post('city','sichuan');
        $type = $this->request->post('type','公务员');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        $where = [
            'city' => $city,
            'type'=>$type
        ];
        $total = ItemInfo::where($where)->count('id');
        $list = ItemInfo::where($where)
            ->order('create_time','DESC')
            ->limit($offset,$pageSize)
            ->column('id,name,content,create_time createTime');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }


}
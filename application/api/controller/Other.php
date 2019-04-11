<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22
 * Time: 15:20
 */

namespace app\api\controller;


use app\api\library\Base;
use app\common\controller\Api;
use app\common\model\cnaps\Bank;
use app\common\model\cnaps\Canps;
use app\common\model\cnaps\City;
use app\common\model\Zuowen;
use fast\Tree;

/**
 * 其余接口
 * Class Other
 * @package app\api\controller
 */
class Other extends Api
{
    protected $noNeedLogin = '*';

    protected $noNeedRight = '*';

    /**
     * 获取作文列表
     * @throws \think\Exception
     */
    public function get_z_list()
    {
        $cid = $this->request->post('cid');
        $size = $this->request->post('size');
        $keyword = $this->request->post('keyword');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * $pageSize;
        $where = [];
        if ($cid) $where['cid'] = $cid;
        if (intval($size)) {
            $size = intval($size);
            $where['size'] = ['between', [$size, $size + 100]];;
        }
        if ($keyword) {
            $where['name'] = ['LIKE', '%' . $keyword . '%'];
        }
        $total = Zuowen::where($where)->count('id');
        $list = Zuowen::where($where)
            ->order('view', 'desc')
            ->limit($offset, $pageSize)
            ->column('id,name,size,view,cate,content');
        foreach ($list as $k => $v) {
            $list[$k]['desc'] = mb_substr(strip_tags($v['content']), 0, 100) . '....';
            unset($list[$k]['content']);
        }
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 获取推荐
     */
    public function z_recommend()
    {
        $zuowen = new Zuowen();
        $list = $zuowen->order('view', 'desc')
            ->limit(0, 10)
            ->column('id,name,size,view,cate,content');
        foreach ($list as $k => $v) {
            $list[$k]['desc'] = mb_substr(strip_tags($v['content']), 0, 100) . '....';
            unset($list[$k]['content']);
        }
        $result = [
            'total' => 10,
            'currentPage' => 1,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 详情
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function z_detail()
    {
        $id = $this->request->post('id');
        $data = Zuowen::get($id);
        Zuowen::where('id', $id)->setInc('view', 1);
        $result = [
            'name' => $data['name'],
            'size' => $data['size'],
            'content' => $data['content'],
            'view' => $data['view'],
            'cate' => $data['cate'],
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 获取banner
     */
    public function get_z_banner()
    {
        $list = $this->getBannerList(1);
        $this->result('success', $list, 200);
    }

    /**
     * 获取银行
     * @throws \think\exception\DbException
     */
    public function canaps_bank()
    {
        $list = Bank::all();
        $this->result('success', $list, 200);
    }

    /**
     * 根据银行获取城市
     */
    public function canps_city()
    {
        $cid = $this->request->post('bank_id');
        if (empty($cid)) $this->error('缺少参数');
        $list = City::where('cid', $cid)->column('id,pid,name,level');
        $tree = new Tree();
        $tree->init($list, 'pid');
        $relist = $tree->getTreeArray(0);
        $this->result('success', $relist, 200);
    }

    /**
     * 获取编码
     * @throws \think\Exception
     */
    public function canps()
    {
        $bank_id = $this->request->post('bank_id');
        $city_id = $this->request->post('city');
        $keyword = $this->request->post('keyword');
        $page = $this->request->param('page', 1);
        $pageSize = $this->request->param('page_size', 10);
        if (intval($page) < 1) $page = 1;
        $offset = ($page - 1) * intval($pageSize);
        if (!$bank_id) $this->error('缺少参数');
        $where = [
            'bank_id' => $bank_id
        ];
        if ($city_id) $where['cid'] = $city_id;
        if ($keyword) $where['name'] = ['LIKE', '%' . $keyword . '%'];
        $total = Canps::where($where)->count('id');
        $list = Canps::where($where)->limit($offset, intval($pageSize))->column('id,name,cnaps,address,lon,lat');
        $result = [
            'total' => $total,
            'currentPage' => $page,
            'list' => array_values($list)
        ];
        $this->result('success', $result, 200);
    }

    /**
     * 获取充电桩
     */
    public function charge_list()
    {
        $lon = $this->request->post('lon', '116.404');
        $lat = $this->request->post('lat', '39.915');
        $url = 'http://api.map.baidu.com/place/v2/search?';
        $ak = 'OS6Qtuz8irbVWgxwxVjof6RumkDg4kI2';
        $tag = '交通设施';
        $params = [
            'query' => '充电桩',
            'radius' => 2000,
            'output' => 'json',
            'ak' => $ak,
            'tag' => $tag,
            'location' => trim($lat) . ',' . trim($lon)
        ];
        $get_params = http_build_query($params);
        $result = $this->http_request($url . $get_params);
        $result = json_decode($result, true);
        if ($result['status'] == 0) {
            $this->result('success', $result['results'], 200);
        } else {
            $this->error('暂无数据');
        }
    }

    /**
     * 获取油价
     */
    public function youjia(){
        $redis = new Base(['select'=>7]);
        $list = $redis->handle()->sMembers('youjia_list');
        if($list){
            $result=[];
            foreach ($list as $k=>$v){
                $res = json_decode($v,true);
                $result[]=$res;
            }
            $this->success('success',$result,200);
        } else{
            $this->error('暂无数据');
        }
    }


}
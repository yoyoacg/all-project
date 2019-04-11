<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/15
 * Time: 10:25
 */

namespace app\api\library;

/**
 * redis 基础类
 * Trait Base
 * @package redis
 */
class Base
{
    protected $options = [
        'host' => '127.0.0.1',
        'port' => '6379',
        'password' => '',
        'select' => 2,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => true,
        'prefix' => '',
    ];


    protected $redis;

    /**
     * 构造函数
     * @param array $options 缓存参数
     * @access public
     */
    public function __construct($options = [])
    {

        if (!extension_loaded('redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $this->redis = new \Redis();
        if ($this->options['persistent']) {
            $this->redis->pconnect($this->options['host'], $this->options['port'], $this->options['timeout'], 'persistent_id_' . $this->options['select']);

        } else {
            $this->redis->connect($this->options['host'], $this->options['port'], $this->options['timeout']);
        }

        if ('' != $this->options['password']) {
            $this->redis->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->redis->select($this->options['select']);
        }
    }

    /**
     * 清空整个redis服务器
     */
    public function clearAll()
    {
        $this->redis->flushAll();
    }

    /**
     * 删除集合
     * @param string $key
     * @return int
     */
    public function delete(string $key)
    {
        $result = $this->redis->del($key);
        return $result;
    }

    /**
     * 返回句柄
     * @return \Redis
     */
    public function handle()
    {
        return $this->redis;
    }

    /**
     * 检查给定的KEY是否存在
     * @param string $key
     * @return bool
     */
    public function exists(string $key)
    {
        return $this->redis->exists($key);
    }

    /**
     * 设置过期时间
     * @param string $key
     * @param int $timeout
     * @return bool
     */
    public function expire(string $key, int $timeout)
    {
        return $this->redis->expire($key, $timeout);
    }

    /**
     * 查找所有符合给定模式 pattern 的 key
     * @param string $pattern
     * @return array
     */
    public function keys(string $pattern)
    {
        return $this->redis->keys($pattern);
    }

    /**
     * 将key移动到指定的库
     * @param string $key
     * @param int $select
     * @return bool
     */
    public function move(string $key, int $select)
    {
        return $this->redis->move($key, $select);
    }

    /**
     * 重命名key
     * @param string $old_key
     * @param string $new_key
     * @return bool
     */
    public function reName(string $old_key, string $new_key)
    {
        return $this->redis->rename($old_key, $new_key);
    }

    /**
     * curl请求
     * @param string $url
     * @param null $data
     * @param array $header
     * @return bool|string
     */
    protected function http_request($url = '', $data = null, $header = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_HEADER, $header);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * 树图转一维数据
     * @param array $data
     * @param string $child
     * @param null $key
     * @return array
     */
    protected function set_list($data = [], $child = '', $key = null,$pid=0,$levle=0)
    {
        $array = [];
        static $id=0;
        foreach ($data as $k => $v) {
            $id++;
            if($key==null){
                $indata = $v;
                unset($indata[$child]);
            }else{
                $indata = $v[$key];
            }
            $indata['id'] =$id;
            $indata['pid']=$pid;
            $indata['level'] = $levle+1;
            $array[] = $indata;
            if (isset($v[$child])) {
                $children = $this->set_list($v[$child], $child, $key, $indata['id'],$indata['level']);
                if ($children) {
                    $array = array_merge($array, $children);
                }
            }
        }
        return $array;
    }

    public function Rand_Ip()
    {
        $ip2id = round(rand(600000, 2550000) / 10000); //第一种方法，直接生成
        $ip3id = round(rand(600000, 2550000) / 10000);
        $ip4id = round(rand(600000, 2550000) / 10000);
        //下面是第二种方法，在以下数据中随机抽取
        $arr_1 = array("218", "218", "66", "66", "218", "218", "60", "60", "202", "204", "66", "66", "66", "59", "61", "60", "222", "221", "66", "59", "60", "60", "66", "218", "218", "62", "63", "64", "66", "66", "122", "211");
        $randarr = mt_rand(0, count($arr_1) - 1);
        $ip1id = $arr_1[$randarr];
        return $ip1id . "." . $ip2id . "." . $ip3id . "." . $ip4id;
    }

    /**
     * curl请求
     * @param string $url
     * @param null $data
     * @param array $header
     * @return bool|string
     */
    protected function x_http_request($url = '', $data = null, $header = [])
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($curl, CURLOPT_HEADER, $header);
        curl_setopt($curl,CURLOPT_HTTPHEADER,$header);
        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

}
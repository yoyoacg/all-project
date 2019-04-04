<?php

namespace app\common\controller;

use app\admin\model\Banner;
use app\common\library\Auth;
use think\Config;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Hook;
use think\Lang;
use think\Loader;
use think\Request;
use think\Response;

/**
 * API控制器基类
 */
class Api
{

    /**
     * @var Request Request 实例
     */
    protected $request;

    /**
     * @var bool 验证失败是否抛出异常
     */
    protected $failException = false;

    /**
     * @var bool 是否批量验证
     */
    protected $batchValidate = false;

    /**
     * @var array 前置操作方法列表
     */
    protected $beforeActionList = [];

    /**
     * 无需登录的方法,同时也就不需要鉴权了
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法,但需要登录
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 权限Auth
     * @var Auth 
     */
    protected $auth = null;

    /**
     * 默认响应输出类型,支持json/xml
     * @var string 
     */
    protected $responseType = 'json';

    /**
     * 构造方法
     * @access public
     * @param Request $request Request 对象
     */
    public function __construct(Request $request = null)
    {
        $this->request = is_null($request) ? Request::instance() : $request;

        // 控制器初始化
        $this->_initialize();

        // 前置操作方法
        if ($this->beforeActionList)
        {
            foreach ($this->beforeActionList as $method => $options)
            {
                is_numeric($method) ?
                                $this->beforeAction($options) :
                                $this->beforeAction($method, $options);
            }
        }
    }

    /**
     * 初始化操作
     * @access protected
     */
    protected function _initialize()
    {
        //移除HTML标签
        $this->request->filter('strip_tags');

        $this->auth = Auth::instance();

        $modulename = $this->request->module();
        $controllername = strtolower($this->request->controller());
        $actionname = strtolower($this->request->action());

        // token
        $token = $this->request->server('HTTP_TOKEN', $this->request->request('token', \think\Cookie::get('token')));

        $path = str_replace('.', '/', $controllername) . '/' . $actionname;
        // 设置当前请求的URI
        $this->auth->setRequestUri($path);
        // 检测是否需要验证登录
        if (!$this->auth->match($this->noNeedLogin))
        {
            //初始化
            $this->auth->init($token);
            //检测是否登录
            if (!$this->auth->isLogin())
            {
                $this->error(__('Please login first'), null, 401);
            }
            // 判断是否需要验证权限
            if (!$this->auth->match($this->noNeedRight))
            {
                // 判断控制器和方法判断是否有对应权限
                if (!$this->auth->check($path))
                {
                    $this->error(__('You have no permission'), null, 403);
                }
            }
        }
        else
        {
            // 如果有传递token才验证是否登录状态
            if ($token)
            {
                $this->auth->init($token);
            }
        }

        $upload = \app\common\model\Config::upload();

        // 上传信息配置后
        Hook::listen("upload_config_init", $upload);

        Config::set('upload', array_merge(Config::get('upload'), $upload));

        // 加载当前控制器语言包
        $this->loadlang($controllername);
    }

    /**
     * 加载语言文件
     * @param string $name
     */
    protected function loadlang($name)
    {
        Lang::load(APP_PATH . $this->request->module() . '/lang/' . $this->request->langset() . '/' . str_replace('.', '/', $name) . '.php');
    }

    /**
     * 操作成功返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为1
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function success($msg = '', $data = null, $code = 1, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 操作失败返回的数据
     * @param string $msg   提示信息
     * @param mixed $data   要返回的数据
     * @param int   $code   错误码，默认为0
     * @param string $type  输出类型
     * @param array $header 发送的 Header 信息
     */
    protected function error($msg = '', $data = null, $code = 0, $type = null, array $header = [])
    {
        $this->result($msg, $data, $code, $type, $header);
    }

    /**
     * 返回封装后的 API 数据到客户端
     * @access protected
     * @param mixed  $msg    提示信息
     * @param mixed  $data   要返回的数据
     * @param int    $code   错误码，默认为0
     * @param string $type   输出类型，支持json/xml/jsonp
     * @param array  $header 发送的 Header 信息
     * @return void
     * @throws HttpResponseException
     */
    protected function result($msg, $data = null, $code = 0, $type = null, array $header = [])
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'time' => Request::instance()->server('REQUEST_TIME'),
            'data' => $data,
        ];
        // 如果未设置类型则自动判断
        $type = $type ? $type : ($this->request->param(config('var_jsonp_handler')) ? 'jsonp' : $this->responseType);

        if (isset($header['statuscode']))
        {
            $code = $header['statuscode'];
            unset($header['statuscode']);
        }
        else
        {
            //未设置状态码,根据code值判断
            $code = $code >= 1000 || $code < 200 ? 200 : $code;
        }
        $response = Response::create($result, $type, $code)->header($header);
        throw new HttpResponseException($response);
    }

    /**
     * 前置操作
     * @access protected
     * @param  string $method  前置操作方法名
     * @param  array  $options 调用参数 ['only'=>[...]] 或者 ['except'=>[...]]
     * @return void
     */
    protected function beforeAction($method, $options = [])
    {
        if (isset($options['only']))
        {
            if (is_string($options['only']))
            {
                $options['only'] = explode(',', $options['only']);
            }

            if (!in_array($this->request->action(), $options['only']))
            {
                return;
            }
        }
        elseif (isset($options['except']))
        {
            if (is_string($options['except']))
            {
                $options['except'] = explode(',', $options['except']);
            }

            if (in_array($this->request->action(), $options['except']))
            {
                return;
            }
        }

        call_user_func([$this, $method]);
    }

    /**
     * 设置验证失败后是否抛出异常
     * @access protected
     * @param bool $fail 是否抛出异常
     * @return $this
     */
    protected function validateFailException($fail = true)
    {
        $this->failException = $fail;

        return $this;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @param  mixed        $callback 回调方法（闭包）
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        if (is_array($validate))
        {
            $v = Loader::validate();
            $v->rule($validate);
        }
        else
        {
            // 支持场景
            if (strpos($validate, '.'))
            {
                list($validate, $scene) = explode('.', $validate);
            }

            $v = Loader::validate($validate);

            !empty($scene) && $v->scene($scene);
        }

        // 批量验证
        if ($batch || $this->batchValidate)
            $v->batch(true);
        // 设置错误信息
        if (is_array($message))
            $v->message($message);
        // 使用回调验证
        if ($callback && is_callable($callback))
        {
            call_user_func_array($callback, [$v, &$data]);
        }

        if (!$v->check($data))
        {
            if ($this->failException)
            {
                throw new ValidateException($v->getError());
            }

            return $v->getError();
        }

        return true;
    }

    /**
     * 获取中文首字母
     * @param $str
     * @return string|null
     */
    protected function  getFirstCharter($str){
        if (empty($str)) {
            return '';
        }
        $fchar = ord($str{0});

        if ($fchar >= ord('A') && $fchar <= ord('z'))
            return strtoupper($str{0});

        $s1 = iconv('UTF-8', 'gb2312', $str);

        $s2 = iconv('gb2312', 'UTF-8', $s1);

        $s = $s2 == $str ? $s1 : $str;

        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;

        if ($asc >= -20319 && $asc <= -20284)
            return 'A';

        if ($asc >= -20283 && $asc <= -19776)
            return 'B';

        if ($asc >= -19775 && $asc <= -19219)
            return 'C';

        if ($asc >= -19218 && $asc <= -18711)
            return 'D';

        if ($asc >= -18710 && $asc <= -18527)
            return 'E';

        if ($asc >= -18526 && $asc <= -18240)
            return 'F';

        if ($asc >= -18239 && $asc <= -17923)
            return 'G';

        if ($asc >= -17922 && $asc <= -17418)
            return 'H';

        if ($asc >= -17417 && $asc <= -16475)
            return 'J';

        if ($asc >= -16474 && $asc <= -16213)
            return 'K';

        if ($asc >= -16212 && $asc <= -15641)
            return 'L';

        if ($asc >= -15640 && $asc <= -15166)
            return 'M';

        if ($asc >= -15165 && $asc <= -14923)
            return 'N';

        if ($asc >= -14922 && $asc <= -14915)
            return 'O';

        if ($asc >= -14914 && $asc <= -14631)
            return 'P';

        if ($asc >= -14630 && $asc <= -14150)
            return 'Q';

        if ($asc >= -14149 && $asc <= -14091)
            return 'R';

        if ($asc >= -14090 && $asc <= -13319)
            return 'S';

        if ($asc >= -13318 && $asc <= -12839)
            return 'T';

        if ($asc >= -12838 && $asc <= -12557)
            return 'W';

        if ($asc >= -12556 && $asc <= -11848)
            return 'X';

        if ($asc >= -11847 && $asc <= -11056)
            return 'Y';

        if ($asc >= -11055 && $asc <= -10247)
            return 'Z';

        return null;
    }

    /**
     * 获取banner
     * @param int $cid
     * @return array
     */
    protected function getBannerList($cid=0){
        $where=[
            'category_id'=>$cid,
            'status'=>'normal'
        ];
        $list = Banner::where($where)->order('weigh','DESC')
            ->column('id,name,img,link');
        foreach ($list as $k=>$v){
            $list[$k]['img']='http://'.$_SERVER['HTTP_HOST'].$v['img'];
        }
        return array_values($list);
    }

    /**
     * 下划线转驼峰
     * @param $str
     * @param bool $ucfirst
     * @return mixed|string
     */
    protected function convertUnderline($str,$ucfirst=true)
    {
        $str = ucwords(str_replace('_', ' ', $str));
        $str = str_replace(' ','',lcfirst($str));
        return $ucfirst ? ucfirst($str) : $str;
    }

    /**
     * 数组下标装换
     * @param array $data
     * @param bool $ucfirst
     * @return array
     */
    protected function cUL($data=[],$ucfirst=true){
        $list=[];
        foreach ((array)$data as $k=>$v){
            $list[$this->convertUnderline($k,$ucfirst)] = $v;
        }
        return $list;
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
     * 根据经纬度获取距离
     * 单位 ：米
     * @param $lat1
     * @param $lng1
     * @param $lat2
     * @param $lng2
     * @return float|int
     */
    protected function getDistance($lat1,$lng1,$lat2,$lng2){
        $radLat1=deg2rad($lat1);//deg2rad()函数将角度转换为弧度

        $radLat2=deg2rad($lat2);

        $radLng1=deg2rad($lng1);

        $radLng2=deg2rad($lng2);

        $a=$radLat1-$radLat2;

        $b=$radLng1-$radLng2;

        $s=2*asin(sqrt(pow(sin($a/2),2)+cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)))*6378.137*1000;
        return $s;
    }

}

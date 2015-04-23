<?php

/**
 * weixin library
 * @author geekzhumail@gmail.com
 * @since 2014-09-24
 * @edit 2015-03-05
 */
class SDKRuntimeException extends Exception {

    public function errorMessage() {
        return $this->getMessage();
    }
}

class WechatSDK extends WechatBase{

    /**
     * 配置参数
     * @var
     */
    protected $config = [
        'appid' => 'wxdf0a039cf4ea94d1',
        'appsecret' => '7100f0fe837a5287f075b6d2d9814ca0',
    ];

    /**
     * api地址
     * @var
     */
    protected $apiPrefix = 'https://api.weixin.qq.com/';
    /**
     * @var access_token
     */
    protected $accessToken = '';

    public function __construct() {
        parent::__construct();
        $this->accessToken = $this->getToken();
    }

    /**
     * 初始化数据
     */
    public function init($config) {
        $this->config = $config;
    }

    /**
     * 获取Appid
     */
    public function getAppid() {
        return $this->config['appid'];
    }

    /**
     * weixin signature
     */
    public function checkSignature($signature, $timestamp, $nonce) {
        $token = $this->config['token'];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * web oauth2
     * @params string $redirectUri 回调地址
     * @params array $state 额外的参数
     * @params string $type 授权模式
     * @return string 授权地址
     */
    public function webOauth2($redirectUri, $state = [], $type = 'snsapi_base') {
        $params = [];
        $params['appid'] = $this->config['appid'];
        $params['redirect_uri'] = $redirectUri;
        $params['response_type'] = 'code';
        $params['scope'] = $type;
        if (empty($state['type'])) {
            $state['type'] = 'product';
        }
        $params['state'] = urldecode(http_build_query($state));
        $url = 'http://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($params) . '#wechat_redirect';
        return $url;
    }

    /**
     * get access token by code
     */
    public function getUserTokenByCode($code) {
        $params = array();
        $params['appid'] = $this->config['appid'];
        $params['secret'] = $this->config['appsecret'];
        $params['code'] = $code;
        $params['grant_type'] = 'authorization_code';
        $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?' . http_build_query($params);
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * refresh access token
     * @param string $token 填写通过access_token获取到的refresh_token参数
     */
    public function refreshUserToken($token) {
        $params = [];
        $params['appid'] = $this->config['appid'];
        $params['grant_type'] = 'refresh_token';
        $params['refresh_token'] = $token;
        $url = 'https://api.weixin.qq.com/sns/oauth2/refresh_token?' . http_build_query($params);
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * 根据网页授权拉取用户信息(需scope为 snsapi_userinfo)
     * @param string $openid 用户的唯一标识
     * @param string $token 网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     */
    public function getUserInfoByOauth($openid, $token) {
        $params = [];
        $params['openid'] = $openid;
        $params['access_token'] = $token;
        $params['lang'] = 'zh_CN';
        $response = $this->get('sns/userinfo', $params);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * 验证授权凭证（access_token）是否有效
     * @param string $openid 用户的唯一标识
     * @param string $token 网页授权接口调用凭证,注意：此access_token与基础支持的access_token不同
     */
    public function validUserToken($openid, $token) {
        $params = [];
        $params['openid'] = $openid;
        $params['access_token'] = $token;
        $url = 'https://api.weixin.qq.com/sns/auth?' . http_build_query($params);
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * 获取公众号token
     */
    public function getToken() {
        $cache = $this->di->getCache();
        if ($cache->exists('wechat_access_token')) {
            return $cache->get('wechat_access_token');
        }
        $params = [];
        $params['grant_type'] = 'client_credential';
        $params['appid'] = $this->config['appid'];
        $params['secret'] = $this->config['appsecret'];
        $url = 'https://api.weixin.qq.com/cgi-bin/token?' . http_build_query($params);
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $response = json_decode($response, true);
        // 写入缓存
        $expiredAt = $response['expires_in'] - 5 * 60;// 设置缓存时间（分钟）
        $cache->save('wechat_access_token', $response['access_token'], $expiredAt);
        return $response['access_token'];
    }

    /**
     * 获取用户基本信息（包括UnionID机制）
     * @param string $openid 用户的唯一标识
     * @param string $token 调用接口凭证，默认值为Wechat::getToken()
     */
    public function getUserInfo($openid, $token = '') {
        if (empty($token)) {
            $token = $this->getToken();
        }
        $params = [];
        $params['openid'] = $openid;
        $params['access_token'] = $token;
        $params['lang'] = 'zh_CN';
        $response = $this->get('cgi-bin/user/info', $params);
        $response = json_decode($response, true);
        return $response;
    }

    /**
     * 下载已经上传到微信的资源文件
     */
    public function downloadMedia($mediaid, $token = '') {
        if (empty($token)) {
            $token = $this->getToken();
        }
        $params = [];
        $params['access_token'] = $token;
        $params['media_id'] = $mediaid;
        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?' . http_build_query($params);
        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_HEADER => true,
            CURLOPT_NOBODY => false,
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') {
            // 正常则分割header和body
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($content, 0, $headerSize);
            $body = substr($content, $headerSize);
        }
        curl_close($ch);
        $resp['info'] = $info;
        $resp['header'] = $header;
        $resp['body'] = $body;
        return $resp;
    }

    /**
     * 获取 js api ticket
     */
    public function getJsapiTicket()
    {
        $cache = $this->di->getCache();
        if ($cache->exists('wechat_js_ticket')) {
            return $cache->get('wechat_js_ticket');
        }
        $resp = json_decode($this->get('cgi-bin/ticket/getticket', ['access_token' => $this->accessToken, 'type' => 'jsapi']), true);

        // 写入缓存
        $expiredAt = $resp['expires_in'] - 5 * 60;// 设置缓存时间（分钟）
        $cache->save('wechat_js_ticket', $resp['ticket'], $expiredAt);
        return $resp['ticket'];
    }

    /**
     * 获取js api 签名
     */
    public function getSignPackage($url = '')
    {
        $jsticket = $this->getJsapiTicket();

        if (! $url) {
            $url = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        }
        $timestamp = time();
        $nonceStr = $this->createNoncestr(16);
        // 这里参数的顺序要按照 key 值 ASCII 码升序排序
        $string = "jsapi_ticket={$jsticket}&noncestr={$nonceStr}&timestamp={$timestamp}&url={$url}";
        return [
            "appId"     => $this->config['appid'],
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => sha1($string),
            "rawString" => $string
        ];
    }


    // 微信支付

    /**
     * 获取微信付款码的prepayid
     * @params array $params 参数
     */
    public function getPrepayId($params) {
        $sdk = new UnifiedOrderSDK;
        $sdk->setParameter("trade_type","JSAPI");//交易类型
        foreach ($params as $key => $val) {
            $sdk->setParameter($key, $val);
        }
        $prepayid = $sdk->getPrepayId();
        return $prepayid;
    }

    /**
     * 获取支付js api 签名
     */
    public function getPaySignPackage($prepayid)
    {
        $jsApiObj["appId"] = $this->config['appid'];
        $timeStamp = time();
        $jsApiObj["timeStamp"] = "$timeStamp";
        $jsApiObj["nonceStr"] = $this->createNoncestr();
        $jsApiObj["package"] = "prepay_id=$prepayid";
        $jsApiObj["signType"] = "MD5";
        $jsApiObj["paySign"] = $this->getSign($jsApiObj);
        $signPackage = $jsApiObj;

        return $signPackage;
    }

    /**
     * 发送红包接口
     * @params array $params 参数
     */
    public function sendRedPack($params) {
        $sdk = new RedPackSDK;
        foreach ($params as $key => $val) {
            $sdk->setParameter($key, $val);
        }
        $result = $sdk->send();
        return $result;
    }
}

/**
 * 所有接口的基类
 */
class WechatBase
{
    /**
     * 配置参数
     * @var
     */
    protected $config = [
        'appid' => '',
        'appsecret' => '',
    ];

    /**
     * api地址
     * @var
     */
    protected $apiPrefix = 'https://api.weixin.qq.com/cgi-bin/';

    public function __construct() {
        $this->di = Phalcon\DI::getDefault();
        $this->config = $this->di->getConfig()['wechat'];
    }

    protected function get($url, $params = '')
    {
        if (strpos($url, 'https://') === false && strpos($url, 'http://') === false) {
            $url = $this->apiPrefix.$url;
        }
        if ($params) {
            $url .= '?'.http_build_query($params);
        }

        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $resp_status = curl_getinfo($ch);
        curl_close($ch);
        if (intval($resp_status["http_code"]) == 200) {
            return $response;
        } else {
            throw new Exception('WechatSDK curl get error');
        }
    }

    protected function post($url, $params = '', $data = '')
    {
        if (strpos($url, 'https://') === false) {
            $url = $this->apiPrefix . $url;
        }
        if ($params) {
            $url .= '?'.http_build_query($params);
        }

        $ch = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
        ];
        if ($data) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        $resp_status = curl_getinfo($ch);
        curl_close($ch);
        if (intval($resp_status["http_code"]) == 200) {
            return $response;
        } else {
            throw new Exception('WechatSDK curl post error');
        }
    }

    public function trimString($value)
    {
        $ret = null;
        if (null != $value)
        {
            $ret = $value;
            if (strlen($ret) == 0)
            {
                $ret = null;
            }
        }
        return $ret;
    }

    /**
     * 	作用：产生随机字符串，不长于32位
     */
    public function createNoncestr( $length = 32, $type = 'all')
    {
        if ($type == 'all') {
            $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        } else if($type == 'number') {
            $chars = "0123456789";
        }
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 	作用：格式化参数，签名过程需要使用
     */
    public function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
               $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    /**
     * 	作用：生成签名
     */
    public function getSign($Obj)
    {
        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //echo '【string1】'.$String.'</br>';
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->config['key'];
        //echo "【string2】".$String."</br>";
        //签名步骤三：MD5加密
        $String = md5($String);
        //echo "【string3】 ".$String."</br>";
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        //echo "【result】 ".$result_."</br>";
        return $result_;
    }

    /**
     * 	作用：array转xml
     */
    public function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
             if (is_numeric($val))
             {
                $xml.="<".$key.">".$val."</".$key.">";

             }
             else
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 	作用：将xml转为array
     */
    public function xmlToArray($xml)
    {
        //将XML转为array
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }

    /**
     * 	作用：以post方式提交xml到对应的接口url
     */
    public function postXmlCurl($xml,$url,$second=30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        curl_close($ch);
        //返回结果
        if($data)
        {
            return $data;
        }
        else
        {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            return false;
        }
    }

    /**
     * 	作用：使用证书，以post方式提交xml到对应的接口url
     */
    public function postXmlSSLCurl($xml,$url,$second=30)
    {
        $ch = curl_init();
        //超时时间
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        //设置header
        curl_setopt($ch,CURLOPT_HEADER,FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        //设置证书
        //使用证书：cert 与 key 分别属于两个.pem文件
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT, $this->config['sslcert_path']);
        //默认格式为PEM，可以注释
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY, $this->config['sslkey_path']);
        //post提交方式
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        }
        else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
            curl_close($ch);
            return false;
        }
    }

}

/**
 * 请求型接口的基类
 */
class WechatPayRequestBase extends WechatBase
{
    var $parameters;//请求参数，类型为关联数组
    public $response;//微信返回的响应
    public $result;//返回参数，类型为关联数组
    var $url;//接口链接
    var $curl_timeout;//curl超时时间

    /**
     * 	作用：设置请求参数
     */
    function setParameter($parameter, $parameterValue)
    {
        $this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
    }

    /**
     * 	作用：设置标配的请求参数，生成签名，生成接口参数xml
     */
    function createXml()
    {
        $this->parameters["appid"] = $this->config['appid'];//公众账号ID
        $this->parameters["mch_id"] = $this->config['mchid'];//商户号
        $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
        $this->parameters["sign"] = $this->getSign($this->parameters);//签名
        return  $this->arrayToXml($this->parameters);
    }

    /**
     * 	作用：post请求xml
     */
    function postXml()
    {
        $xml = $this->createXml();
        $this->response = $this->postXmlCurl($xml,$this->url,$this->curl_timeout);
        return $this->response;
    }

    /**
     * 	作用：使用证书post请求xml
     */
    function postXmlSSL()
    {
        $xml = $this->createXml();
        $this->response = $this->postXmlSSLCurl($xml,$this->url,$this->curl_timeout);
        return $this->response;
    }

    /**
     * 	作用：获取结果，默认不使用证书
     */
    function getResult()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        return $this->result;
    }
}

/**
 * 统一支付接口类
 */
class UnifiedOrderSDK extends WechatPayRequestBase
{
    function __construct()
    {
        parent::__construct();
        //设置接口链接
        $this->url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        //设置curl超时时间
        $this->curl_timeout = $this->config['curl_timeout'];
    }

    /**
     * 生成接口参数xml
     */
    function createXml()
    {
        //检测必填参数
        if(empty($this->parameters["out_trade_no"]))
        {
            throw new SDKRuntimeException("缺少统一支付接口必填参数out_trade_no！"."<br>");
        }elseif(empty($this->parameters["body"])){
            throw new SDKRuntimeException("缺少统一支付接口必填参数body！"."<br>");
        }elseif (empty($this->parameters["total_fee"])) {
            throw new SDKRuntimeException("缺少统一支付接口必填参数total_fee！"."<br>");
        }elseif (empty($this->parameters["notify_url"])) {
            throw new SDKRuntimeException("缺少统一支付接口必填参数notify_url！"."<br>");
        }elseif (empty($this->parameters["trade_type"])) {
            throw new SDKRuntimeException("缺少统一支付接口必填参数trade_type！"."<br>");
        }elseif ($this->parameters["trade_type"] == "JSAPI" &&
            empty($this->parameters["openid"])){
            throw new SDKRuntimeException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！"."<br>");
        }
        $this->parameters["appid"] = $this->config['appid'];//公众账号ID
        $this->parameters["mch_id"] = $this->config['mchid'];//商户号
        $this->parameters["spbill_create_ip"] = Request::ip();//终端ip
        $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
        $this->parameters["sign"] = $this->getSign($this->parameters);//签名
        return  $this->arrayToXml($this->parameters);
    }

    /**
     * 获取prepay_id
     */
    function getPrepayId()
    {
        $this->postXml();
        $this->result = $this->xmlToArray($this->response);
        if ($this->result['return_code'] == 'FAIL') {
            throw new SDKRuntimeException($this->result['return_msg']);
        }
        if ($this->result['result_code'] == 'FAIL') {
            throw new SDKRuntimeException($this->result['err_code_des']);
        }
        $prepay_id = $this->result["prepay_id"];
        return $prepay_id;
    }

}

/**
 * 响应型接口基类
 */
class WechatPayServerBase extends WechatBase
{
	public $data;//接收到的数据，类型为关联数组
	var $returnParameters;//返回参数，类型为关联数组

	/**
	 * 将微信的请求xml转换成关联数组，以方便数据处理
	 */
	function saveData($xml)
	{
		$this->data = $this->xmlToArray($xml);
	}

	function checkSign()
	{
		$tmpData = $this->data;
		unset($tmpData['sign']);
		$sign = $this->getSign($tmpData);//本地签名
        if (App::isLocal()) {
            echo $sign;
        }
		if ($this->data['sign'] == $sign) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * 获取微信的请求数据
	 */
	function getData()
	{
		return $this->data;
	}

	/**
	 * 设置返回微信的xml数据
	 */
	function setReturnParameter($parameter, $parameterValue)
	{
		$this->returnParameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}

	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		return $this->arrayToXml($this->returnParameters);
	}

	/**
	 * 将xml数据返回微信
	 */
	function returnXml()
	{
		$returnXml = $this->createXml();
		return $returnXml;
	}
}

/**
 * 红包发送接口类
 */
class RedPackSDK extends WechatPayRequestBase
{
    function __construct()
    {
        parent::__construct();
        //设置接口链接
        $this->url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack";
        //设置curl超时时间
        $this->curl_timeout = $this->config['curl_timeout'];
    }

    /**
     * 生成接口参数xml
     */
    function createXml()
    {
        $requiredParams = [
            'mch_billno',
            'nick_name',
            'send_name',
            're_openid',
            'total_amount',
            'min_value',
            'max_value',
            'total_num',
            'wishing',
            'act_name',
            'remark',
        ];
        //检测必填参数
        foreach ($requiredParams as $val) {
            if (empty($this->parameters[$val])) {
                throw new SDKRuntimeException("缺少红包接口必填参数{$val}！"."<br>");
            }
        }
        $this->parameters["wxappid"] = $this->config['appid'];//公众账号ID
        $this->parameters["mch_id"] = $this->config['mchid'];//商户号
        $this->parameters["client_ip"] = gethostbyname(gethostname());//终端ip
        $this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
        $this->parameters["sign"] = $this->getSign($this->parameters);//签名
        return  $this->arrayToXml($this->parameters);
    }

    /**
     * 发送红包
     *
     * curl 返回例子
     *  <xml>
        <return_code><![CDATA[SUCCESS]]></return_code>
        <return_msg><![CDATA[发放成功.]]></return_msg>
        <result_code><![CDATA[SUCCESS]]></result_code>
        <err_code><![CDATA[0]]></err_code>
        <err_code_des><![CDATA[发放成功.]]></err_code_des>
        <mch_billno><![CDATA[1228563102201503261427336509]]></mch_billno>
        <mch_id>1228563102</mch_id>
        <wxappid><![CDATA[wxdceccb9098a978b8]]></wxappid>
        <re_openid><![CDATA[o55e0t1NHPxdcRCjbIu_xnjHc2u8]]></re_openid>
        <total_amount>100</total_amount>
        </xml>
     */
    public function send() {
        $this->postXmlSSL();
        $this->result = $this->xmlToArray($this->response);
        if ($this->result['return_code'] == 'FAIL') {
            throw new SDKRuntimeException($this->result['return_msg']);
        }
        if ($this->result['result_code'] == 'FAIL') {
            throw new SDKRuntimeException($this->result['err_code_des']);
        }
        return $this->result;
    }
}

<?php
/**
 * 流量充值接口
 * @author geekzhumail@gmail.com
 * @since 2013-09-01
 */
class Flow {

    /**
     * 流量充值金额
     */
    const FlowType3 = 3;
    const FlowType5 = 5;
    const FlowType10 = 10;
    const FlowType30 = 30;
    const FlowType50 = 50;
    const FlowType80 = 80;
    const FlowType100 = 100;
    const FlowType200 = 200;

    /**
     * 流量产品编号
     */
    const FlowProCode3 = 'prod.10086000000121';
    const FlowProCode5 = 'prod.10086000000101';
    const FlowProCode10 = 'prod.10086000000102';
    const FlowProCode20 = 'prod.10086000000103';
    const FlowProCode30 = 'prod.10086000000104';
    const FlowProCode50 = 'prod.10086000000105';
    const FlowProCode80 = 'prod.10086000000106';
    const FlowProCode100 = 'prod.10086000000107';
    const FlowProCode200 = 'prod.10086000000108';
    const FlowProCode = 'prod.10000008585200';

    /**
     * ngec object
     */
    protected $ngec = null;

    /**
     * api url
     */
    //protected $url = 'http://221.179.7.250/NGADCInfcText/NGADCServicesForEC.svc?wsdl';// 联调环境
    protected $url = "http://221.179.7.247:8201/NGADCInterface/NGADCServicesForEC.svc?wsdl"; // 正式环境 java、php等语言使用


    /**
     * 配置参数
     */
    protected $config = [];
    /**
     * 充值金额与产品编码关系对照
     */
    protected $flowTypes = [
        3 => 'prod.10086000000121',
        5 => 'prod.10000008585101',
        10 => 'prod.10000008585102',
        20 => 'prod.10000008585103',
        30 => 'prod.10000008585104',
        50 => 'prod.10000008585105',
        80 => 'prod.10000008585106',
        100 => 'prod.10000008585107',
        200 => 'prod.10000008585108'
    ];

    /**
     * 记录错误信息
     */
    protected $errMesg = '';

    /**
     * 判断是否发生错误
     */
    protected $isError = false;

    /**
     * 设置错误
     */
    protected function setError($mesg = '') {
        $this->isError = true;
        $this->errMesg = $mesg;
    }

    /**
     * 返回数据格式
     * @var Object $response SoapClient返回的对象
     * @return array
     */
    protected function respFormat(stdClass $response) {
        $svcCont = $response->AdcServicesResult->SvcCont;
        $arr = XmlObject::xml2array($svcCont);
        $result = [
            'rspCode' => $response->AdcServicesResult->Response->RspCode,
            'rspDesc' => $response->AdcServicesResult->Response->RspDesc,
            'proccessTime' => $response->AdcServicesResult->ProcessTime,
            'orderId' => $response->AdcServicesResult->TransIDO,
            'svcCont' => $arr,
        ];
        return $result;
    }

    /**
     * 初始化
     */
    public function __construct($config = []) {
        $this->config = include('config.ini.php.xihe');
    }

    /**
     * 返回错误信息
     */
    public function getError() {
        return $this->errMesg;
    }

    /**
     * 判断是否发生错误
     */
    public function isError() {
        return $this->isError;
    }

    /**
     * 流量充值接口
     * @var int $money 充值金额
     * @var string $teleNum 充值号码
     * @var int $cycle 周期数 使用周期月数
     */
    public function recharge($money, $teleNum, $cycle = 1) {
        try {
            $client = new SoapClient($this->url);

            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0001';// 充值代号
            // 设置SvcCont内容
            $svc = new MemberShipRequest($this->config['ECCode'], $this->config['PrdOrdNum']);
            $member = new Member([
                'OptType' => 0,
                'PayFlag' => 0,
                'UsecyCle' => $cycle,
                'Mobile' => $teleNum,
                'UserName' => $this->config['CompanyName'],
                'EffType' => 2
            ]);
            $member->setDefaultPrdList();
            $member->add('PrdList', ['PrdCode' => $this->flowTypes[$money], 'OptType' => 0]);
            $svc->add('Member', $member->toArray());
            $ngec->SvcCont = $svc->getXml();// 获取SvcCont的XML格式内容
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }

    /**
     * 流量充值接口
     * @var int $flow 流量值（单位：M）
     * @var string $teleNum 充值号码
     * @var int $effType 生效方式 0-默认 2-立即 3-下月
     * @var int $cycle 周期数 使用周期月数
     */
    public function customRecharge($flow, $teleNum, $effType = 3, $cycle = 1) {
        try {
            $client = new SoapClient($this->url);

            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0001';// 充值代号
            // 设置SvcCont内容
            $svc = new MemberShipRequest($this->config['ECCode'], $this->config['PrdOrdNum']);
            $member = new Member([
                'OptType' => 0,
                'PayFlag' => 0,
                'UsecyCle' => $cycle,
                'Mobile' => $teleNum,
                'UserName' => $this->config['CompanyName'],
                'EffType' => $effType
            ]);
            $member->setDefaultPrdList();

            $prdList = new PrdList();
            $service = new Service();
            // 设置Service
            $service->ServiceCode = '8585.MemAllot';
            $service->addUserInfoMap(0, 'GroupGPRSAllot', $flow);
            // 设置PrdList
            $prdList->PrdCode = static::FlowProCode;
            $prdList->OptType = 0;
            $prdList->addService($service->toArray());

            $member->add('PrdList', $prdList->toArray());
            $svc->add('Member', $member->toArray());
            $ngec->SvcCont = $svc->getXml();// 获取SvcCont的XML格式内容
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }

    /**
     * 流量套餐撤销
     * @var int $proCode 流量产品编码
     * @var string $teleNum 操作号码
     * @var int $optType 操作类型 0-开通 1-暂停 2-注销 3-恢复 4-修改
     * @var int $effType 生效方式 0-默认 2-立即 3-下月
     * @var int $prdOptType 产品操作类型 0-订购 2-修改 4-取消
     * @var int $cycle 周期数 使用周期月数
     */
    public function cancelPackage($proCode, $teleNum, $optType, $effType, $prdOptType, $cycle = 1) {
        try {
            $client = new SoapClient($this->url);

            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0001';// 代号
            // 设置SvcCont内容
            $svc = new MemberShipRequest($this->config['ECCode'], $this->config['PrdOrdNum']);
            $member = new Member([
                'OptType' => $optType,
                'PayFlag' => 0,
                'UsecyCle' => $cycle,
                'Mobile' => $teleNum,
                'UserName' => $this->config['CompanyName'],
                'EffType' => $effType
            ]);
            $member->add('PrdList', ['PrdCode' => $proCode, 'OptType' => $prdOptType]);
            $svc->add('Member', $member->toArray());
            $ngec->SvcCont = $svc->getXml();// 获取SvcCont的XML格式内容
            //header("Content-type:text/xml;charset=utf-8");
            //echo $svc->getXml();
            //exit;
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }

    /**
     * 客户套餐查询接口(EC0002)
     */
    public function queryPackage($teleNum) {
        try {
            $client = new SoapClient($this->url);
            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0002';// 查询套餐代号
            $svc = new PackgeSearchRequest($this->config['ECCode'], $teleNum);
            $ngec->SvcCont = $svc->getXml();
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }

    /**
     * 统一剩余资源明细查询接口(EC0006)
     * @var string $teleNum 成员时，填写手机，集团时填写集团编号
     * @var int $type 1 集团号码、2 成员号
     * @var int $giftSmallType 只针对$type=2的成员号有用，指明是否匹配小类对应的剩余流量, 空或0时，不需要；1需要匹配，3 查询个人流量800的总流量
     * @var int $giftBigType 免费资源大类，详细请见文档
     *
     */
    public function queryFlow($teleNum, $type = 2, $giftSmallType = 0, $giftBigType = null) {
        try {
            $client = new SoapClient($this->url);
            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0006';// 查询剩余流量代号
            $svc = new FlowSerachRequest($teleNum, $type, $giftSmallType, $giftBigType);
            $ngec->SvcCont = $svc->getXml();
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }

    /**
     * 查询手机流量清单功能接口(EC0007)
     */
    public function queryFlowList() {
        try {
            $client = new SoapClient($this->url);
            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0007';// 查询代号
            $svc = new MobileFlowListRequest([
                'MobileNo' => '15876598724',
                'BeginDate' => '20140101',
                'EndDate' => '20141107',
                'Type' => '0',
                'DataType' => '0',
                'JobCode' => '108256',
                'PWDType' => '0'
            ]);
            $ngec->SvcCont = $svc->getXml();
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }

    /**
     * 成员订购关系对账查询接口(EC0008)
     */
    public function queryMemOrderDetail() {
        try {
            $client = new SoapClient($this->url);
            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0008';// 查询代号
            $svc = new MemberBillRequest($this->config['PrdOrdNum']);
            $ngec->SvcCont = $svc->getXml();
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }

    /**
     * 集团订购关系查询(EC0009)
     * @var int $type 查询类型 0 查所有的，1 只查询订购关系正常的
     */
    public function queryGroupOrder($type = 0) {
        try {
            $client = new SoapClient($this->url);
            $ngec = new NgecObject($this->config);
            $ngec->BIPCode = 'EC0009';// 查询代号
            $svc = new ECOrderQueryRequest($type);
            $ngec->SvcCont = $svc->getXml();
            $req = $ngec->toArray();
            $response = $client->AdcServices(['request' => $req]);
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            //echo $e->getMessage();
            return false;
        }
        return $this->respFormat($response);
    }


    /**
     * 测试线上接口
     */
    public function remoteCall($req) {
        echo file_get_contents('http://flow.geekzhu.com//index/test?req=' . $req);
    }

    /**
     * 远程测试接口
     */
    public function remoteTest($req) {
        var_dump($req);
        ini_set('soap.wsdl_cache_enabled', '0');
        ini_set('soap.wsdl_cache_ttl', '0');
        $client = new SoapClient('http://221.179.7.247:8201/NGADCInterface/NGADCServicesForEC.svc?wsdl');
        $response = $client->AdcServices(['request' => $req]);
        var_dump($response);
    }
}

/**
 * XML对象
 */
class XmlObject {

    /**
     * XML属性数组
     */
    protected $attr = [];


    /**
     * XML所有属性数组
     */
    protected $fields = [];

    /**
     * xml version
     */
    protected $version = '1.0';

    /**
     * xml encoding
     */
    protected $encoding = 'UTF-8';


    /**
     * @var xml
     */
    protected $xml = null;

    /**
     * 数组转换为XML
     * @var array attr 数组
     * @var string parentDom 父元素名
     */
    protected function toXml($attr = []) {
        foreach ($attr as $key => $val) {
            if (is_numeric($key)) {
                // 如果时数字则生成多个元素
                $this->toXml($val);
                continue;
            }
            if (is_array($val)) {
                // 如果是数组则递归
                $this->xml->startElement($key);
                $this->toXml($val);
                $this->xml->endElement();
                continue;
            }
            $this->xml->writeElement($key, $val);
        }
    }

    /**
     * 初始化$attr
     * @var array $opt 属性
     */
    public function __construct($opt = []) {
        foreach ($this->fields as $key) {
            if (isset($opt[$key])) {
                $this->attr[$key] = $opt[$key];
            }
        }
    }
    /**
     * 将当前对象转换为XML
     * @var isRoot 是否为跟节点，是则加上<?xml version='1.0' encoding='utf-8' ?>
     */
    public function getXml($isRoot = true) {
        $this->xml = new XmlWriter();
        $this->xml->openMemory();
        $this->xml->startDocument($this->version, $this->encoding);
        $this->toXml($this->attr);
        return $this->xml->outputMemory(true);
    }

    /**
     * setting attr
     */
    public function __set($key, $val) {
        $this->attr[$key] = $val;
    }

    /**
     * 增加一个属性，重复则另外一行
     */
    public function add($key, $val) {
        if (isset($this->attr[$key])) {
            $this->attr[][$key] = $val;
            return;
        }
        $this->attr[$key] = $val;
    }

    /**
     * get attr
     */
    public function __get($key) {
        return isset($this->attr[$key]) ? $this->attr[$key] : '';
    }
    /**
     * xml数据转换为数组
     */
    public static function xml2array($xml_values, $get_attributes = 1, $priority = 'tag') {
        $contents = "";
        if (!function_exists('xml_parser_create'))
        {
            return array ();
        }
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8");
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($xml_values), $xml_arr);
        xml_parser_free($parser);
        if (!$xml_arr)
            return; //Hmm...
        $xml_array = array ();
        $parents = array ();
        $opened_tags = array ();
        $arr = array ();
        $current = & $xml_array;
        $repeated_tag_index = array ();
        foreach ($xml_arr as $data)
        {
            unset ($attributes, $value);
            extract($data);
            $result = array ();
            $attributes_data = array ();
            if (isset ($value))
            {
                if ($priority == 'tag')
                    $result = $value;
                else
                    $result['value'] = $value;
            }
            if (isset ($attributes) and $get_attributes)
            {
                foreach ($attributes as $attr => $val)
                {
                    if ($priority == 'tag')
                        $attributes_data[$attr] = $val;
                    else
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                }
            }
            if ($type == "open")
            {
                $parent[$level -1] = & $current;
                if (!is_array($current) or (!in_array($tag, array_keys($current))))
                {
                    $current[$tag] = $result;
                    if ($attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = & $current[$tag];
                }
                else
                {
                    if (isset ($current[$tag][0]))
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else
                    {
                        $current[$tag] = array (
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        if (isset ($current[$tag . '_attr']))
                        {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset ($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = & $current[$tag][$last_item_index];
                }
            }
            elseif ($type == "complete")
            {
                if (!isset ($current[$tag]))
                {
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data)
                        $current[$tag . '_attr'] = $attributes_data;
                }
                else
                {
                    if (isset ($current[$tag][0]) and is_array($current[$tag]))
                    {
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data)
                        {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;
                    }
                    else
                    {
                        $current[$tag] = array (
                            $current[$tag],
                            $result
                        );
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes)
                        {
                            if (isset ($current[$tag . '_attr']))
                            {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset ($current[$tag . '_attr']);
                            }
                            if ($attributes_data)
                            {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                    }
                }
            }
            elseif ($type == 'close')
            {
                $current = & $parent[$level -1];
            }
        }
        return ($xml_array);
    }
}

/**
 * ngec传输报文数据对象
 */
class NgecObject extends XmlObject{

    /**
     * 根节点名字
     */
    protected $rootName = 'NGEC';

    /**
     * 必需属性
     */
    protected $attr = [
        'OrigDomain' => 'NGEC',
        'BIPVer' => 'V1.0',
        'BIPCode' => '',
        'TransIDO' => '',
        'Areacode' => '',
        'ECCode' => '',
        'ECUserName' => '',
        'ECUserPwd' => '',
        'ProcessTime' => '',
        'SvcCont' => ''
    ];
    /**
     * 所有属性
     */
    protected $fields = [
        'OrigDomain',
        'BIPVer',
        'BIPCode',
        'TransIDO',
        'Areacode',
        'ECCode',
        'ECUserName',
        'ECUserPwd',
        'ProcessTime',
        'SvcCont'
    ];


    /**
     * 获取attr
     */
    public function toArray() {
        $this->attr['TransIDO'] = 'flow_' . date('YmdHis');
        $this->attr['ProcessTime'] = date('YmdHis');
        return $this->attr;
    }
}

/**
 * 传输报文体对象
 */
class SvcCont extends XmlObject{

    /**
     * 根节点名字
     */
    protected $rootName = '';

    /**
     * setting attr
     */
    public function __set($key, $val) {
        $this->attr[$this->rootName]['BODY'][$key] = $val;
    }

    /**
     * 增加一个属性，重复则另外一行
     */
    public function add($key, $val) {
        if (isset($this->attr[$this->rootName]['BODY'][$key])) {
            $this->attr[$this->rootName]['BODY'][][$key] = $val;
            return;
        }
        $this->attr[$this->rootName]['BODY'][$key] = $val;
    }

    /**
     * get attr
     */
    public function __get($key) {
        return isset($this->attr[$this->rootName]['BODY'][$key]) ? $this->attr[$this->rootName]['BODY'][$key] : '';
    }

}

/**
 * 用于EC0001的报文体
 */
class MemberShipRequest extends SvcCont {

    /**
     * extends SvcCont
     */
    protected $rootName = 'MemberShipRequest';

    /**
     * extends parents
     */
    protected $attr = [
        'MemberShipRequest' => [
            'BODY' => [
                'ECCode' => '',
                'PrdOrdNum' => '',
            ]
        ]
    ];

    /**
     * 初始化$attr
     * @var string  ecCode ECCode
     */
    public function __construct($ecCode = '', $prdOrdNum = '') {
        $this->ECCode = $ecCode;
        $this->PrdOrdNum = $prdOrdNum;
    }

}

/**
 * XML 子元素
 */
class ChildrenDom extends XmlObject{

    /**
     * 获取attr元素
     */
    public function toArray() {
        return $this->attr;
    }

}

/**
 * XML member 子元素
 */
class Member extends ChildrenDom{

    /**
     * 必须属性, 附带默认值
     */
    protected $attr = [
        'OptType' => '0',
        'PayFlag' => '0',
        'UsecyCle' => '0',
        'Mobile' => '',
        'UserName' => '',
        'EffType' => ''
    ];

    /**
     * 所有属性
     */
    protected $fields = [
        'OptType',
        'PayFlag',
        'UsecyCle',
        'Mobile',
        'UserName',
        'EffType',
        'PrdList'
    ];


    /**
     * 设置PrdList
     * @var array $prdList PrdList->toArray()
     */
    public function addPrdList($prdList) {
        $this->attr[] = [
            'PrdList' => $prdList
        ];
    }

    /**
     * 设置默认的PrdList
     */
    public function setDefaultPrdList($optType = 0) {
        $prdList = new PrdList();
        $service = new Service();
        // 设置Service
        $service->ServiceCode = 'Service8585.Mem';
        $service->addUserInfoMap($optType, 'IFPersonPay', '0');
        // 设置PrdList
        $prdList->PrdCode = 'AppendAttr.8585';
        $prdList->OptType = $optType;
        $prdList->addService($service->toArray());
        $this->attr[] = ['PrdList' => $prdList->toArray()];
    }

}

/**
 * XML PrdList 子元素
 */
class PrdList extends ChildrenDom {

    /**
     * 必须属性, 附带默认值
     */
    protected $attr = [
        'PrdCode' => '0',
        'OptType' => '0'
    ];

    /**
     * 所有属性
     */
    protected $fields = [
        'PrdCode',
        'OptType',
        'Service'
    ];

    /**
     * 设置Service
     * @var array $service Service->toArray()
     */
    public function addService($service) {
        $this->attr[] = [
            'Service' => $service
        ];
    }
}

/**
 * XML PrdList 子元素
 */
class Service extends ChildrenDom {

    /**
     * 必须属性, 附带默认值
     */
    protected $attr = [
        'ServiceCode' => '0'
    ];

    /**
     * 所有属性
     */
    protected $fields = [
        'ServiceCode',
        'USERINFOMAP'
    ];

    /**
     * 设置USERINFOMAP的ItemName和ItemValue
     * @var string $optType 操作类型 0-订购 2-修改 4-取消
     * @var string $key ItemName
     * @var string $val ItemValue
     */
    public function addUserInfoMap($optType, $key, $val) {
        $this->attr[] = [
            'USERINFOMAP' => [
                'OptType' => $optType,
                'ItemName' => $key,
                'ItemValue' => $val
            ]
        ];
    }
}

/**
 * 用于EC0002的报文体
 */
class PackgeSearchRequest extends SvcCont {

    /**
     * extends SvcCont
     */
    protected $rootName = 'FlowSerachRequest';

    /**
     * extends parents
     */
    protected $attr = [
        'FlowSerachRequest' => [
            'BODY' => [
                'ECCode' => '',
                'Mobile' => ''
            ]
        ]
    ];

    /**
     * 初始化$attr
     * @var string  ecCode ECCode
     */
    public function __construct($ecCode = '', $mobile = '') {
        $this->ECCode = $ecCode;
        $this->Mobile = $mobile;
    }
}

/**
 * 用于EC0006的报文体
 */
class FlowSerachRequest extends SvcCont {

    /**
     * extends SvcCont
     */
    protected $rootName = 'FlowSerachRequest';

    /**
     * extends parents
     */
    protected $attr = [
        'FlowSerachRequest' => [
            'BODY' => [
                'ServNumber' => '',
                'Type' => ''
            ]
        ]
    ];

    /**
     * 初始化$attr
     * @var string  ecCode ECCode
     */
    public function __construct($servNumber = '', $type = '', $giftSmallType = null, $giftBigType = null) {
        $this->ServNumber = $servNumber;
        $this->Type = $type;
        if (! is_null($giftSmallType)) {
            $this->IsMathGiftSmallType = $giftSmallType;
        }
        if (! is_null($giftBigType)) {
            $this->GiftBigType = $giftBigType;
        }
    }
}

/**
 * 用于EC0007的报文体
 */
class MobileFlowListRequest extends SvcCont {

    /**
     * extends SvcCont
     */
    protected $rootName = 'MobileFlowListRequest';

    /**
     * extends parents
     */
    protected $attr = [
        'MobileFlowListRequest' => [
            'BODY' => [
                'MobileNo' => '',
                'BeginDate' => '',
                'EndDate' => '',
                'Type' => '',
                'DataType' => '',
                'JobCode' => '',
                'PWDType' => '',
            ]
        ]
    ];

    /**
     * 初始化$attr
     * @var string $ecPrdCode
     */
    public function __construct($config = []) {
        foreach ($this->attr[$this->rootName]['BODY'] as $key => $val) {
            if (isset($config[$key])) {
                $this->attr[$this->rootName]['BODY'][$key] = $config[$key];
            }
        }
    }
}

/**
 * 用于EC0008的报文体
 */
class MemberBillRequest extends SvcCont {

    /**
     * extends SvcCont
     */
    protected $rootName = 'MemberBillRequest';

    /**
     * extends parents
     */
    protected $attr = [
        'MemberBillRequest' => [
            'BODY' => [
                'EcPrdCode' => ''
            ]
        ]
    ];

    /**
     * 初始化$attr
     * @var string $ecPrdCode
     */
    public function __construct($ecPrdCode) {
        $this->EcPrdCode = $ecPrdCode;
    }
}

/**
 * 用于EC0009的报文体
 */
class ECOrderQueryRequest extends SvcCont {

    /**
     * extends SvcCont
     */
    protected $rootName = 'ECOrderQueryRequest';

    /**
     * extends parents
     */
    protected $attr = [
        'ECOrderQueryRequest' => [
            'BODY' => [
                'QueryType' => ''
            ]
        ]
    ];

    /**
     * 初始化$attr
     * @var string $ecPrdCode
     */
    public function __construct($type = 0) {
        $this->QueryType = $type;
    }
}

class NgecException extends exception {

}


<?php

/**
 * 流量充值接口API函数
 * @author geekzhumail@gmail.com
 * @since 2014-11-04
 */
class ApiController extends ApiBaseController {

    /**
     * @var 流量充值接口实例对象
     */
    protected $flow = null;

    /**
     * @var 方法对控制器的映射关系，允许的方法
     */
    protected $method2action = [
        'GET' => [
            'query_package',
            'query_flow',
            'member_order',
            'group_order'
        ],
        'POST' => [
            'recharge',
            'custom_recharge'
        ],
        'PUT' => [
            'package'
        ]
    ];

    /**
     * 构造函数
     */
    public function initialize() {
        try {
            $isAllow = false;// 是否允许访问标记
            $url = $this->request->getURI();
            //var_dump($url);
            $split = explode('/', trim($url, '/'));
            if (count($split) < 2) {
                $this->throwError('路径有误', 404);
            }
            foreach ($this->method2action as $method => $actions) {
                foreach ($actions as $action) {
                    if ($action == $split[1] && $this->request->isMethod($method)) {
                        $isAllow = true;
                        break;
                    }
                }
                if ($isAllow) {
                    break;
                }
            }
            if (! $isAllow) {
                $this->throwError('路径有误', 404);
            }
        } catch (Exception $e) {
            $this->exceptionHandle($e);
            echo json_encode($this->getRetMesg());
            exit;
        }
        $this->flow = new Flow();
    }

    public function indexAction() {

    }

    /**
     * post 充值
     */
    public function rechargeAction() {
        try {
            $input = $this->request->getPost();
            $cycle = 1;
            if (empty($input['money'])) {
                $this->throwError('金额不能为空');
            }
            if ($input['money'] > 3) {
                $this->throwError('测试环境，金额不能过高');
            }
            if (empty($input['tele_num'])) {
                $this->throwError('电话号码不能为空');
            }
            if (! empty($input['cycle']) && is_numeric($input['cycle'])) {
                $cycle = $input['cycle'];
            }

            echo <<<EOF
{
    "c": 0,
    "mesg": "success",
    "response": {
        "rspCode": "0000",
        "rspDesc": "成功",
        "proccessTime": "20141212173729",
        "orderId": "flow_20141212173729",
        "svcCont": {
            "MemberShipResponse": {
                "BODY": {
                    "ECCode": "2000127998",
                    "PrdOrdNum": "50115004971",
                    "Member": {
                        "Mobile": "15876598724",
                        "CRMApplyCode": "80005191591313",
                        "ResultCode": "1",
                        "ResultMsg": []
                    }
                }
            }
        }
    }
}
EOF;
exit;
            $money = $input['money'];
            $teleNum = $input['tele_num'];
            $ret = $this->flow->recharge($money, $teleNum, $cycle);
            if ($this->flow->isError()) {
                $this->throwError($this->flow->getError());
            }
            $this->setRetMesg([
                'c' => 0,
                'mesg' => 'success',
                'response' => $ret
            ]);
        } catch (Exception $e) {
            $this->exceptionHandle($e);
        }
        echo json_encode($this->getRetMesg());
    }

    /**
     * 自定义充值
     */
    public function custom_rechargeAction() {
        try {
            $input = $this->request->getPost();
            $effType = 3;
            $cycle = 1;
            if (empty($input['flow'])) {
                $this->throwError('流量值不能为空');
            }
            if (empty($input['tele_num'])) {
                $this->throwError('电话号码不能为空');
            }
            if (! empty($input['eff_type']) && is_numeric($input['eff_type'])) {
                $effType = $input['eff_type'];
            }
            if (! empty($input['cycle']) && is_numeric($input['cycle'])) {
                $cycle = $input['cycle'];
            }

            $flow = $input['flow'];
            $teleNum = $input['tele_num'];
            $ret = $this->flow->customRecharge($flow, $teleNum, $effType, $cycle);
            if ($this->flow->isError()) {
                $this->throwError($this->flow->getError());
            }
            $this->setRetMesg([
                'c' => 0,
                'mesg' => 'success',
                'response' => $ret
            ]);
        } catch (Exception $e) {
            $this->exceptionHandle($e);
        }
        echo json_encode($this->getRetMesg());

    }

    /**
     * 撤销套餐
     */
    public function packageAction() {
        try {
            $input = $this->request->getPut();
            $cycle = 1;
            $effType = 3;
            if (empty($input['pro_code'])) {
                $this->throwError('流量产品编码不能为空');
            }
            if (empty($input['tele_num'])) {
                $this->throwError('电话号码不能为空');
            }
            if (empty($input['opt_type'])) {
                $this->throwError('操作类型不能为空');
            }
            if (empty($input['prd_opt_type'])) {
                $this->throwError('产品操作类型不能为空');
            }
            if (! empty($input['eff_type']) && is_numeric($input['eff_type'])) {
                $effType = $input['eff_type'];
            }
            if (! empty($input['cycle']) && is_numeric($input['cycle'])) {
                $cycle = $input['cycle'];
            }

            $proCode = $input['pro_code'];
            $teleNum = $input['tele_num'];
            $optType = $input['opt_type'];
            $prdOptType = $input['prd_opt_type'];

            $ret = $this->flow->cancelPackage($proCode, $teleNum, $optType, $effType, $prdOptType, $cycle);
            if ($this->flow->isError()) {
                $this->throwError($this->flow->getError());
            }
            $this->setRetMesg([
                'c' => 0,
                'mesg' => 'success',
                'response' => $ret
            ]);
        } catch (Exception $e) {
            $this->exceptionHandle($e);
        }
        echo json_encode($this->getRetMesg());
    }

    /**
     * 查询套餐
     */
    public function query_packageAction($teleNum = 0) {
        try {
            if (empty($teleNum)) {
                $this->throwError('电话号码不能为空');
            }
            $ret = $this->flow->queryPackage($teleNum);
            if ($this->flow->isError()) {
                $this->throwError($this->flow->getError());
            }
            $this->setRetMesg([
                'c' => 0,
                'mesg' => 'success',
                'response' => $ret
            ]);
        } catch (Exception $e) {
            $this->exceptionHandle($e);
        }
        echo json_encode($this->getRetMesg());
    }

    /**
     * 剩余流量查询接口
     */
    public function query_flowAction($teleNum = 0) {
        try {
            $input = $this->request->get();
            $type = 2;
            $giftSmallType = 0;
            $giftBigType = null;
            if (empty($teleNum)) {
                $this->throwError('电话号码不能为空');
            }
            if (! empty($input['type']) && is_numeric($input['type'])) {
                $type = $input['type'];
            }
            if (! empty($input['gift_big_type']) && is_numeric($input['gift_big_type'])) {
                $giftBigType = $input['gift_big_type'];
            }
            if (! empty($input['gift_small_type'])) {
                $giftSmallType = intval($input['gift_small_type']);
            }

            $ret = $this->flow->queryFlow($teleNum, $type, $giftSmallType, $giftBigType);
            if ($this->flow->isError()) {
                $this->throwError($this->flow->getError());
            }
            $this->setRetMesg([
                'c' => 0,
                'mesg' => 'success',
                'response' => $ret
            ]);
        } catch (Exception $e) {
            $this->exceptionHandle($e);
        }
        echo json_encode($this->getRetMesg());
    }

    /**
     * 查询成员订购关系
     */
    public function member_orderAction() {
        try {
            $ret = $this->flow->queryMemOrderDetail();
            if ($this->flow->isError()) {
                $this->throwError($this->flow->getError());
            }
            $this->setRetMesg([
                'c' => 0,
                'mesg' => 'success',
                'response' => $ret
            ]);
        } catch (Exception $e) {
            $this->exceptionHandle($e);
        }
        echo json_encode($this->getRetMesg());
    }

    /**
     * 查询集团订购关系
     */
    public function group_orderAction() {
        try {
            $type = 0;
            if (! empty($input['type']) && is_numeric($input['type'])) {
                $type = $input['type'];
            }

            $ret = $this->flow->queryGroupOrder($type);
            if ($this->flow->isError()) {
                $this->throwError($this->flow->getError());
            }
            $this->setRetMesg([
                'c' => 0,
                'mesg' => 'success',
                'response' => $ret
            ]);
        } catch (Exception $e) {
            $this->exceptionHandle($e);
        }
        echo json_encode($this->getRetMesg());
    }
}


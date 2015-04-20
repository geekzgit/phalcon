<?php

namespace Juice\Auth;

use Phalcon\Mvc\Model;
use Phalcon\Mvc\User\Component;

/**
 * Juice\Auth\Auth
 * Manages Authentication/Identity Management in Juice
 */
class Auth extends Component
{

    /**
     * di
     * @var
     */
    protected $di = null;

    /**
     * session
     * @var
     */
    protected $session = null;

    /**
     * user
     * @var
     */
    protected $user = null;

    public function __construct() {
        $this->di = \Phalcon\DI::getDefault();
        $this->session = $this->di->getSession();
        $info = $this->session->get('auth_info');
        if ($info) {
            $this->user = \WxUser::findFirstById($info['id']);
        }
    }

    /**
     * 检测用户是否登录
     */
    public function check() {
        if ($this->user) {
            return true;
        }
        return false;
    }

    /**
     * 检测用户是否没有登陆
     */
    public function guest() {
        if ($this->check()) {
            return false;
        }
        return true;
    }
    /**
     * 根据model实例登录
     */
    public function login($user) {
        $info['id'] = $user->id;
        $this->session->set('auth_info', $info);
    }

    /**
     * 获取授权用户信息
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * 获取授权用户ID
     */
    public function id()
    {
        return $this->user->id;
    }
}

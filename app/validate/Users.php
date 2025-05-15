<?php
/**
 *
 * User: 会飞的鱼
 * Date: 2023/7/30
 * QQ: 137691250
 * Email: <137691250@qq.com>
 */

namespace app\Validate;


use think\Validate;

class Users extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'email' => ['email'],
        'password' => ['require', 'min:6', 'max:20'],
        'old_password' => ['require', 'min:6', 'max:20'],
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'email' => '邮箱必填',
        'email.email' => '邮箱错误',
        'password.require' => '密码不能为空',
        'old_password.require' => '旧密码密码不能为空',
        'password.min' => '密码长度不应低于6位',
        'password.max' => '密码长度不应超过20位',
    ];

    protected $scene = [
        'sendCode' => ['email'],
        'updateCode' => ['email'],
        'setPassword' => ['old_password', 'password'],
        'useMailSetPassword' => ['email', 'password'],
        'down_file' => ['email','url'],
        'signUp' => ['email', 'password'],
        'signIn' => ['email', 'password']
    ];
}

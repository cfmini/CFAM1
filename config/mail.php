<?php

/**
 * 示例配置文件
 *
 * 可以配置在 mail.php 或 config.php 文件中, 但要保证能通过 mail.driver, mail.host 访问到配置信息
 */
return [
    'scheme'          => 'smtp',
    'host'            => 'smtp.qq.com', // 服务器地址
    'username'        => '',
    'password'        => '', // 密码
    'port'            => 465, // SMTP服务器端口号,一般为25
    'options'         => [],
    'debug'           => false, // 开启debug模式会直接抛出异常, 记录邮件发送日志
    'embed'           => 'embed:', // 邮件中嵌入图片元数据标记
];
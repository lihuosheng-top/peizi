<?php

/* *
 * 配置文件
 * 版本：1.0
 * 日期：2014-06-16
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */
global $llpay_config;
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201306081000001016
/*
$llpay_config['oid_partner'] = '201408071000001539';

//秘钥格式注意不能修改（左对齐，右边有回车符）
$llpay_config['RSA_PRIVATE_KEY'] ='-----BEGIN RSA PRIVATE KEY-----
MIICXAIBAAKBgQCmRl6Zn4MmtoBoelHRT6j6ounts/x1+GiJTB9/eBTl01cBK50h
mOUtGBcOVrJCa0C1NkR8BYgOT/WLfFT8cICw6XSJtf2uzZco71jbwXfFe8MiEx/L
XiQNQHuclpkUa1hXFUUo6Qat8X8L++pVZfjav40dPKf7oFWCYLWBCDOdyQIDAQAB
AoGANe0mqz4/o+OWu8vIE1F5pWgG5G/2VjBtfvHwWUARzwP++MMzX/0dfsWMXLsj
b0UnpF3oUizdFn86TLXTPlgidDg6h0RbGwMZou/OIcwWRzgMaCVePT/D1cuhyD7Y
V8YkjVHGnErfxyia1COswAqcpiS4lcTG/RqkAMsdwSZe640CQQDRvkQ7M2WJdydc
9QLQ9FoIMnKx9mDge7+aN6ijs9gEOgh1gKUjenLr6hcGlLRyvYDKQ4b1kes22FUT
/n+AMaEPAkEAyvH05KRzax3NNdRPI45N1KuT1kydIwL3KpOK6mWuHlffed2EiWLS
dhZNiZy9wWuwFPqkrZ8g+jL0iKcCD0mjpwJBAKbWxWmeCZ+eY3ZjAtl59X/duTRs
ekU2yoN+0KtfLG64RvBI45NkHLQiIiy+7wbyTNcXfewrJUIcNRjRcVRkpesCQEM8
BbX6BYLnTKUYwV82NfLPJRtKJoUC5n/kgZFGPnkvA4qMKOybIL6ehPGiS/tYge1x
XD1pCrPZTco4CiambuECQDNtlC31iqzSKmgSWmA5kErqVJB0f1i+a0CbQLlaPGYN
/qwa7TE13yByaUdDDaTIEUrDyuqWd5+IvlbwuVsSlMw=
-----END RSA PRIVATE KEY-----';

//安全检验码，以数字和字母组成的字符
$llpay_config['key'] ='201408071000001539_sahdisa_20141205';

*/

$llpay_config['oid_partner'] = '201707241001904516';

//秘钥格式注意不能修改（左对齐，右边有回车符）
$llpay_config['RSA_PRIVATE_KEY'] ='-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQCzMsMpPW7PGABD28S/+5C1zdm6aiKRV9SO7mgT7lWsjIKaU3lK
1ryiGHRz76JdIpYRJD2hnrlD04stNeEdfTHyL/tRLSN0XnuILqFIylgMN2fgirSQ
fL9TTYlHw2cAwaDLygCNKxisza0CDJiaCyrMFRFuy+qGv3/sntPhsGvX3QIDAQAB
AoGAA/b5jm4Hh5l2WAoCvmSofP1C9fbnMOQKleb8coRxbNHnCdcS9e7uWX/FA9On
efwL6fEtU+gZHUEO8T8h7e8ZLcMbpGIFmzlpbzluZBegqza2Vd77Sahkmv/suaKk
SjQ/cslMpGAaKLONWnUP0jSxLOiC+dBblCDuJJD7FR5d6NkCQQDXwL8Mn1VEaZit
qAnvEEK1liBYNLWqINd4LcfZ177m+38YqEs6Kt7nDP3UleDRSEXQqYh/5aCS/KVt
KZVH8h1jAkEA1KBav8SE+A5KzgShBBslxsENS119qOgJVZUiGT3k5JgNWu9xeot/
6n/h5PtwCwgGJVpsmAsrStTvEqd0wlXZvwJBALpxjCeiQRMflZrrzbnTeXJmS4kt
85cTTmBCX6P29rewugJa3LdleL590ZQ1+NOh+wL4nka37u1WerY86w4DQjUCQEt+
R9pQxzlfsbWmNRlFHkuMXdEFd8lR4YaOddXqgOudBRjlbTAqeZdkImtvzt9L0QrQ
KUBursdaBSxlYnJhkvcCQQDTF1FeFqyf+LXCuj3+lQccABLL7uRvKSWDDcPxHiML
qSZQ1HR+oZ3IOo+LgZpENNJkjyPufJt7Xi+D/1397siN
-----END RSA PRIVATE KEY-----';

//安全检验码，以数字和字母组成的字符
$llpay_config['key'] ='201707241001904516_sahdisa_20170724';


//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//版本号
$llpay_config['version'] = '1.0';

//防钓鱼ip 可不传或者传下滑线格式 
//$llpay_config['userreq_ip'] = '10_10_246_110';

//证件类型
$llpay_config['id_type'] = '0';

//签名方式 不需修改
$llpay_config['sign_type'] = strtoupper('RSA');

//订单有效时间  分钟为单位，默认为10080分钟（7天） 
$llpay_config['valid_order'] ="10080";

//字符编码格式 目前支持 gbk 或 utf-8
$llpay_config['input_charset'] = strtolower('utf-8');

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$llpay_config['transport'] = 'http';
?>
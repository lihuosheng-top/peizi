<?php

/* *
 * 配置文件
 * 版本：1.2
 * 日期：2014-06-13
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 */

global $llpay_config;
//↓↓↓↓↓↓↓↓↓↓请在这里配置您的基本信息↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓
//商户编号是商户在连连钱包支付平台上开设的商户号码，为18位数字，如：201408071000001543
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
$llpay_config['key'] = '201408071000001539_sahdisa_20141205';

//↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

//版本号
$llpay_config['version'] = '1.2';

//请求应用标识 为wap版本，不需修改
$llpay_config['app_request'] = '3';


//签名方式 不需修改
$llpay_config['sign_type'] = strtoupper('RSA');

//订单有效时间  分钟为单位，默认为10080分钟（7天） 
$llpay_config['valid_order'] ="10080";

//字符编码格式 目前支持 gbk 或 utf-8
$llpay_config['input_charset'] = strtolower('utf-8');

//访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
$llpay_config['transport'] = 'http';
?>
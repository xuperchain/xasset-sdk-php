# XASSET PHP SDK

## 概述

本项目提供Xasset PHP语言版的开发者工具包（SDK），开发者可以基于该SDK使用PHP语言接入到Xasset平台。

## 使用说明

- 1.从平台申请获得到API准入AK/SK。注意AK/SK是准入凭证，不要泄露，不要下发或配置在客户端使用。
- 2.在开发中引用包含（require_once）对应文件，开发业务程序。
- 3.接入联调环境联调测试，测试通过后更换到线上环境，完成接入。
- 4.从平台申请获得加密和生成区块链账户的二进制文件。

### 运行环境

PHP SDK可以在PHP7.1及以上环境下运行。

### 引用SDK包

```

// 需要拷贝整个SDK包，再引用需要的文件
//SDK_PATH需要定义成实际项目中的路径
define('SDK_PATH'. dirname(__FILE__) . '/../');
require_once(SDK_PATH. 'client/xasset/XassetClient.php');

```

### 配置说明

```
//请求的HOST
$host = 'http://120.48.16.137:8360';
//从平台申请获得到API准入AK/SK
$appId = 0;
$ak = 'xxx';
$sk = 'xxx';
//区块链账户公私钥对
$account = array(
    'address'     => 'xxx',
    'public_key'  => 'xxx',
    'private_key' => 'xxx',
);
可以由auth/Account.php中的createAccount函数生成区块链账户
```

### 使用示例

```
// 引用文件
require_once('XassetClient.php');
require_once('../../auth/EcdsaSigner.php');
require_once('../../utils/Utils.php');



//配置
//binary file path
$binPath = ROOT_PATH . 'tools/xasset-cli/xasset-cli';
$crypto = new EcdsaCrypto($binPath);
$config = new XassetConfig($crypto);
//配置准入ak sk
$appId = 0;
$ak = 'xxx';
$sk = 'xxx';
$config->setCredentials($appId, $ak, $sk);

$config->endPoint = "http://120.48.16.137:8360";
$xHandle = new XassetClient($config);

// 调用SDK方法，可以参考单元测试
$account = array(
    'address'     => 'xxx',
    'public_key'  => 'xxx',
    'private_key' => 'xxx',
);
$stoken = $xHandle->getStoken($account);
```
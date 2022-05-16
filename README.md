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

// 需要拷贝整个SDK包，再引用index.php
require_once('./index.php');

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

### 获取加密binary程序
```
方式一：
直接从百度云BOS下载bin文件：
Linux: https://xasset-open.cdn.bcebos.com/resources/xasset-cli/linux/xasset-cli
Windows: https://xasset-open.cdn.bcebos.com/resources/xasset-cli/windows/xasset-cli.exe
Mac: https://xasset-open.cdn.bcebos.com/resources/xasset-cli/mac/xasset-cli

方式二：
通过go sdk源码编译生成bin文件
1. 下载xasset-sdk-go
git clone git@github.com:xuperchain/xasset-sdk-go.git
2. 编译
cd xasset-sdk-go
cd tools/xasset-cli
go build -o xasset-cli main.go

通过方式一或方式二获取xchain-cli文件, 把xasset-cli文件拷贝到xasset-sdk-php项目中, 例如tools/xasset-cli/目录下
```

### 使用示例

```
// 引用文件
require_once('index.php');

//配置
//binary file path
//linux mac
$binPath = XASSET_PATH . 'tools/xasset-cli/xasset-cli';
//windows
//$binPath = XASSET_PATH . 'tools/xasset-cli/xasset-cli.exe';
$crypto = new EcdsaCrypto($binPath);
$config = new XassetConfig($crypto);
//配置准入ak sk
$appId = 0;
$ak = 'xxx';
$sk = 'xxx';
$config->setCredentials($appId, $ak, $sk);

$config->endPoint = "http://120.48.16.137:8360";
$xHandle = new XassetClient($config);

// 调用SDK方法，可以参考demo.php和单元测试文件
$account = array(
    'address'     => 'xxx',
    'public_key'  => 'xxx',
    'private_key' => 'xxx',
);
$stoken = $xHandle->getStoken($account);
```

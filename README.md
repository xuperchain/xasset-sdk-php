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

### 编译加密binary程序
```
1. 下载xasset-sdk-go
git clone git@github.com:xuperchain/xasset-sdk-go.git
2. 编译
cd xasset-sdk-go
cd tools/xasset-cli
go build -o xasset-cli main.go
3. 将编译出的xasset-cli文件拷贝到xasset-sdk-php项目中
```

### 使用示例

```
// 详细可参考 demo.php

概要流程如下：
// 引用文件

//配置
//binary file path

//配置准入ak sk
$appId = 0;
$ak = 'xxx';
$sk = 'xxx';

//调用SDK方法，可参考相关单元测试

//如与项目相关结构冲突，请自行参考相关测试脚本重新封装，勿过渡依赖此SDK

```
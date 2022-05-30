<?php
require_once('../index.php');

//binary file path
//linux mac
$binPath = XASSET_PATH . 'tools/xasset-cli/xasset-cli';
//windows
//$binPath = XASSET_PATH . 'tools/xasset-cli/xasset-cli.exe';
$accClient = new Account($binPath);
$mnemonic = '呈 仓 冯 滚 刚 伙 此 丈 锅 语 揭 弃 精 塘 界 戴 玩 爬 奶 滩 哀 极 样 费';
$arrAcc = $accClient->retrieveAccount($mnemonic);
var_dump($arrAcc);
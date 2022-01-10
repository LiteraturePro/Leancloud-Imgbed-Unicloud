<?php
error_reporting(0);

include 'UnicloudClient.php';

$spaceId = getenv("spaceId");
$clientSecret = getenv("clientSecret");


$unicloud = new UnicloudClient($spaceId, $clientSecret);

$act=isset($_GET['act'])?trim($_GET['act']):null;

session_start();
@header('Content-Type: application/json; charset=UTF-8');

if(isset($_SESSION['access_token']) && isset($_SESSION['access_token_expire']) && $_SESSION['access_token_expire']>time()){
	$access_token = $_SESSION['access_token'];
	$unicloud->set_access_token($access_token);
}else{
	try{
		$access_token = $unicloud->get_access_token();
	} catch (Exception $e) {
		exit(json_encode(['code'=>-1, 'msg'=>'获取AccessToken失败:' . $e->getMessage()]));
	}
	$_SESSION['access_token'] = $access_token;
	$_SESSION['access_token_expire'] = time()+600;
}

switch($act){
case 'pre_upload':
	if(!isset($_POST['filename']))exit('{"code":-1,"msg":"请选择文件"}');
	try{
		$result = $unicloud->pre_upload_file($_POST['filename']);
		exit(json_encode(['code'=>0, 'data'=>$result]));
	} catch (Exception $e) {
		exit(json_encode(['code'=>-1, 'msg'=>'准备文件上传失败:' . $e->getMessage()]));
	}
	break;
case 'complete_upload':
	if(!isset($_POST['id']))exit('{"code":-1,"msg":"no id"}');
	try{
		$unicloud->complete_upload_file($_POST['id']);
		exit(json_encode(['code'=>0]));
	} catch (Exception $e) {
		exit(json_encode(['code'=>-1, 'msg'=>'完成文件上传失败:' . $e->getMessage()]));
	}
	break;
default:
	exit('{"code":-4,"msg":"No Act"}');
	break;
}

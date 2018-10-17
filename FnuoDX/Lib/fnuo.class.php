<?php

//定义核心包目录

defined('MSG_ROOT') or define('MSG_ROOT', dirname(__FILE__).DIRECTORY_SEPARATOR);

class fnuoClass{	

	protected $url='http://api.fnuo123.com/';

	protected $app_id;

	protected $app_key;

	public function __construct($app_id,$app_key){

		//序列号

		$this->app_id=$app_id;

		//秘钥

		$this->app_key=$app_key;

	}

	public function maikToken($type=true,$data=NULL){

		//时间验证码

		$time=time();

		//随机验证码

		$rand=rand();

		if($type){

			//生成校验码

			$key=$time.$rand;

			/*

			 * 储存校验码与短信内容

			 * 此处可用于读取数据库

			 */

			$path=MSG_ROOT.'Temp'.DIRECTORY_SEPARATOR;

			if (!file_exists($path)){

				mkdir($path, 0777);

			}

			//生成通讯密令

			$secret=md5(md5($this->app_id.$this->app_key).$key);

			//保存校验码与短信内容

			$cheack=file_put_contents($path.$secret.'.data',json_encode(array('key'=>$key,'data'=>$data)));

			if(!$cheack){die("make data error");}

		}else{

			//生成通讯密令

			$secret=md5(md5($this->app_id.$this->app_key).$time);

		}

		return 'app_id='.$this->app_id.'&app_key='.$this->app_key.'&time='.$time.'&secret='.$secret;

	}

	public function getInformation(){

		$url=$this->url.'api.msg.information?'.$this->maikToken(false);

		return file_get_contents($url);

	}

	public function sendMsg($data){
		//购买街验证码
		// $url=$this->url.'api.msg.send?'.$this->maikToken(true,$data);

		// return file_get_contents($url);





		//花蒜验证码
		$this->url.'api.msg.send?'.$this->maikToken(true,$data);

		if(preg_match('/\d+/',$data['msg'],$arr)){
	       $code = trim($arr[0]);
	    }
		$url = "https://www.juhuivip.com/app/index.php?i=2&c=entry&m=ewei_shopv2&do=mobile&r=account.baili.verifycode&mobile=".$data['phone']."&code=".$code."&temp=sms_bind";

		$res = file_get_contents($url);

		$res = json_decode($res);
		$data = array('errorCode'=>0);
		if($res->status != 1)
		{
			$data = array();
		}

		return json_encode($data);
	}

}

?>
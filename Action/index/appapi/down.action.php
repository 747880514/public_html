<?php

fun("zfun");



class downAction extends Action{

	public function supdownload($url=NULL){

		//百里 如果没有已授权标识，则先注册用户
		if(empty($_GET['registered']) && empty($_GET['test']) == 1)
		{
			header("location:http://app.juhuivip.com?registered=1&tgid=".strval($_GET['tgid']));
		}
		else
		{
			$baili['uid'] = $_GET['uid'];
			$baili['mobile'] = !empty($_GET['mobile']) ? $_GET['mobile'] : -1;
			$this->assign("baili", $baili);
		}



		$set=zfun::f_getset("AppDisplayName,Weblogo,xz_wz_title,dow_img,xz_wz_up,xz_wz_down,dow_img01");



		if(!empty($set['dow_img01']))$set['AppLogo']=UPLOAD_URL."slide/".$set['dow_img01'];

		else $set['AppLogo']="View/index/img/appapi/down/sup_download/publicity_logo.png";

		if(!empty($set['dow_img']))$set['dow_img']=UPLOAD_URL."slide/".$set['dow_img'];

		else $set['dow_img']="View/index/img/appapi/down/sup_download/publicity_pic.png";

		if(empty($set['xz_wz_up']))$set['xz_wz_up']='网购成功，获得返利';

		if(empty($set['xz_wz_down']))$set['xz_wz_down']='网购成功，获得返利';

		if(empty($set['xz_wz_title']))$set['xz_wz_title']=$set['AppDisplayName'].'app下载';



		$set['tgid']='';

		if(!empty($_GET['tgid']))$set['tgid']="邀请码:".$_GET['tgid'];

		$this->assign("set",$set);

		$this->display();

		$this->play();

		/*if(file_exists(ROOT_PATH."Action/index/default/ordermessage.action.php")==false)return;

		if(!empty($_COOKIE['jxfw']))return;

		$set=zfun::f_getset("webset_webnick,android_url,ios_url");

		self::assign("set",$set);

		self::display("downloadapp","index",'wap');

		if(self::iswx()){

			self::runplay("wap","comm","iswx");

		}

		self::play();*/

		$GLOBALS['jxfw']=1;

	}

	public function setcookie_(){

		setcookie("jxfw",1,time()+3600,"/");

		zfun::fecho(1,1,1);

	}

	public function setcookie__(){

		setcookie("jxfw",1,time()+3600,"/");

		zfun::jsjump(self::getUrl("index","index",array(),"wap"));

	}

	public static function iswx(){

		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false )return 1;

		else return 0;

	}



	public function downurl(){

		 $version=zfun::f_row("AppVersion","only=1");

		 $set=zfun::f_getset("android_url,ios_url");

	 	if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){

			$tg_url=$set['ios_url'];

		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){

			$tg_url=$set['android_url'] ;

			if(empty($tg_url))	$tg_url=INDEX_WEB_URL . 'Upload/apk/'.$version['name'];



		}else{

			$tg_url=$set['android_url'];

			if(empty($tg_url))$tg_url= INDEX_WEB_URL . 'Upload/apk/'.$version['name'];

		}

		zfun::jump($tg_url);

	 }

}

?>
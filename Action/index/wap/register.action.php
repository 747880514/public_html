<?php

include_once ROOT_PATH."comm/zfun.php";

class registerAction extends Action{

	//1149

	//13903

	public function index(){

		if(!empty($_GET['tgid'])){

			$Decodekey = $this -> getApp('Tgidkey');

			$tgid = intval($Decodekey -> Decodekey($_GET['tgid']));

			$user=zfun::f_count("User","id=$tgid");

			if(!empty($user))setcookie("tgid",$tgid,time()+86400,"/");

		}

		self::display("register","register-index","wap");	

		self::play();

	}

	public function checkcode(){

		/*

		$_SESSION['wapyzm1']=md5("explosion".$yzm);

		$_SESSION['wapphone1']=$phone;

		*/

		if(empty($_POST['yzm']))zfun::fecho("参数错误");

		if(empty($_SESSION['wapyzm1'])||empty($_SESSION['wapphone1']))zfun::fecho("请先获取验证码");

		$code=md5($_POST['yzm']);

		if($code!=$_SESSION['wapyzm1'])zfun::fecho("验证码错误");

		$_SESSION['reguser']=array("phone"=>$_SESSION['wapphone1']);

		zfun::fecho("成功",1,1);

	}

	public function register1(){

		zfun::f_play();

	}

	public function register2(){

		//if(empty($_SESSION['reguser']['phone']))zfun::alert("非法操作");

		$tgid=intval($_COOKIE['tgid']);

		$tuser=zfun::f_row("User","id=$tgid","nickname,phone");

		$tuser['phoneshow']=mb_substr($tuser['phone'],0,3,"utf-8")."******".mb_substr($tuser['phone'],-2,2,"utf-8");

		$tuser['nickname']=mb_substr($tuser['nickname'],0,3,"utf-8")."******".mb_substr($tuser['nickname'],-2,2,"utf-8");

		

		//zheli

		$O2OProvince=zfun::f_select("O2OProvince");

		self::assign("O2OProvince",$O2OProvince);

		self::assign("tuser",$tuser);

		self::assign("reguser",$_SESSION['reguser']);

		zfun::f_play();	

	}

	public function register3(){

		$phone=$_SESSION['reguser']['phone'];

		$tmp=zfun::f_count("User","phone='".$phone."'");

		if(!empty($tmp))zfun::alert("该手机号已经被注册");

		if(empty($phone))zfun::alert("非法操作");

		$tgid=intval($_COOKIE['tgid']);

		$count=zfun::f_count("User","id=$tgid");

		if(empty($count))$tgid=0;//推荐人不存在

		if($_POST['password']!=$_POST['password1'])zfun::alert("密码不能为空");

		$password=md5($_POST['password']);

		$jf_reg=floatval(self::getSetting("jf_reg"));

		$commission_reg=floatval(self::getSetting("commission_reg"));

		//百里
		$blocking_price_endtime = self::getSetting('blocking_price_endday') > 0 ? self::getSetting('blocking_price_endday') : 3;
		$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

		$arr=array(

			"nickname"=>$phone,

			"phone"=>$phone,

			"password"=>$password,

			"reg_time"=>time(),

			"login_time"=>time(),

			// "commission"=>$commission_reg,

			"integral"=>$jf_reg,

			'head_img' => 'default.png', 

			"extend_id"=>$tgid,//邀请人id

			"dq1"=>intval($_POST['dq1']),

			"dq2"=>intval($_POST['dq2']),

			"dq3"=>intval($_POST['dq3']),

			"dq4"=>intval($_POST['dq4']),

			"blocking_price"=>$commission_reg,
			"blocking_price_endtime"=>$blocking_price_endtime,

		);

		$arr["address"]=$arr['dq1']."-".$arr['dq2']."-".$arr['dq3'];

		$uid = intval(zfun::f_insert("User",$arr));

		if (empty($uid))zfun::alert("数据库操作失败!");

		unset($_SESSION['reguser']['phone']);

		$this -> setSessionUser($uid, $phone);

		

		if($jf_reg>0){

			$jfname=self::getSetting("jf_name");

			zfun::f_adddetail('注册送' . $jf_reg . $jfname,$uid,6,0,$jf_reg);	

		}

		if($commission_reg>0){

			zfun::f_adddetail('注册送' . $commission_reg . '佣金',$uid,6,0,$commission_reg);		

		}

		

		zfun::alert("注册成功",INDEX_WEB_URL);

		

	}

	

}

?>
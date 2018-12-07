<?php
fun("zfun");
include_once ROOT_PATH.'Action/index/appapi/dgappcomm.php';
class dg_loginAction extends Action{
	//登录页面图片
	public function pic(){
		appcomm::signcheck();
		$data=zfun::f_row("Guanggao","type='apploginimg' and hide=0","img,ktype,url");
		if(!empty($data))$data['UIIdentifier']=$data['ktype'];unset($data['ktype']);
		if(empty($data['img']))$data['img']=INDEX_WEB_URL."View/index/img/appapi/comm/land_bj.png";
		else $data['img']=UPLOAD_URL."slide/".$data['img'];
		$set=zfun::f_getset("login_str1,login_str2,login_str3,login_str4,phone_kj_onoff,qq_tlogin_onoff,taobao_tlogin_onoff,weixin_tlogin_onoff");
		$data['phone_kj_onoff']=intval($set['phone_kj_onoff']);
		
		$data['weixin']=intval($set['weixin_tlogin_onoff']);
		$data['qq']=intval($set['qq_tlogin_onoff']);
		$data['taobao']=intval($set['taobao_tlogin_onoff']);
		$data['login_btn_img']=INDEX_WEB_URL."View/index/img/appapi/comm/login_btn_img.png?time=".time();
		$data['login_str1']=str_replace("#","",$set['login_str1']);
		if(empty($data['login_str1']))$data['login_str1']='CCCCCC';
		$data['login_str2']=str_replace("#","",$set['login_str2']);
		if(empty($data['login_str2']))$data['login_str2']='f43e79';
		$data['login_str3']=str_replace("#","",$set['login_str3']);
		if(empty($data['login_str3']))$data['login_str3']='f43e79';
		$data['login_str4']=str_replace("#","",$set['login_str4']);
		if(empty($data['login_str4']))$data['login_str4']='f43e79';
		zfun::fecho("登录页面图片",$data,1);	
	}
	public function checkIsExist(){
		appcomm::signcheck();
		$tmp=appcomm::parametercheck("phone");
		$phone=filter_check($tmp['phone']).'';
		$data=zfun::f_row("User","phone='$phone'");
		$arr=array();
		$arr['is_exist']=0;
		if(!empty($data))$arr['is_exist']=1;
		zfun::fecho("是否存在该账户",$arr,1);
	}
	public function login(){
		appcomm::signcheck();
		$tmp=appcomm::parametercheck("phone,captch");
		
		$phone=filter_check($tmp['phone']).'';
		$captch=filter_check($tmp['captch']).'';
		$data=zfun::f_row("User","phone='$phone'");
		$set=zfun::f_getset("jf_reg,commission_reg,extendreg,blocking_price_endday");	//百里.追加blocking_price_endday
		$jf_reg=floatval($set['jf_reg']);
		$commission_reg=floatval($set['commission_reg']);
		$token = md5(base64_encode($phone . time() . uniqid(rand())));
		if(empty($_POST['tgid'])&&$set['extendreg']==1&&empty($data))zfun::fecho("请输入邀请码");
		$tgid=0;
		if(!empty($_POST['tgid'])&&empty($data)){
			$tg_code_low=strtolower($_POST['tgid']);
			$tg_user=zfun::f_row("User","tg_code_low='".$tg_code_low."' and tg_code_low<>''");
			$tgid=$tg_user['id'];
			if(empty($tg_user)){
				$tgid=$this -> getApp('Tgidkey')-> Decodekey($_POST['tgid']);
				$tg_user=zfun::f_row("User","id='$tgid'");
			}
			if(empty($tg_user))zfun::fecho("推荐人不存在");
		}
		self::checkcode();//检测验证码
		if(empty($data)){
			
			// 百里.修改前
			// $userdata = array('extend_id'=>$tgid,'loginname'=>$phone,'phone' => $phone, 'nickname' => $phone,'integral' => $jf_reg, 'reg_time' => time(),"commission"=>$commission_reg, 'token' => $token);
			// 百里.修改后
			//首单结束期限
			$blocking_price_endtime = $set['blocking_price_endday'] > 0 ? $set['blocking_price_endday'] : 3;
			$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;
			$userdata = array(
				'extend_id'=>$tgid,
				'loginname'=>$phone,
				'phone' => $phone,
				'nickname' => $phone,
				'integral' => $jf_reg,
				'reg_time' => time(),
				// "commission"=>$commission_reg,
				"blocking_price"=>$commission_reg,
				"blocking_price_endtime"=>$blocking_price_endtime,
				'token' => $token
			);
			
			$insertid =zfun::f_insert("User",$userdata);
			$this -> setSessionUser($insertid, $phone);
			//注册送积分事件
			actionfun("default/api");
			apiAction::reg_shijian($insertid);
			$data=zfun::f_row("User","id='$insertid'");
		}
		if(!empty($data['token'])){$token=$data['token'];}
		//没有token的加一下
		zfun::f_update("User","id='".$data['id']."'",array("token"=>$token));
		
		$tmp=array("token"=>$token);
		zfun::fecho("登录成功",$tmp,1);
	}
	public static function checkcode(){
		 //判断验证 验证码
		if(empty($_POST['captch']))zfun::fecho("请输入验证码");
		$phone=filter_check($_POST['phone']).'';
		$captch=filter_check($_POST['captch']).'';
		$session_capth = self::getCache('captch', md5($phone));
		$sessioncode = $session_capth['emailcode'];
		if (md5($captch)!=$sessioncode)zfun::fecho("验证码不正确");
		self::delCache("captch", md5($phone));
	}
	public function getcode() {
		appcomm::signcheck();
		zfun::add_f("getcode");//同一秒 不能发送
		$phone=filter_check($_POST['phone']).'';
		if(empty($phone))zfun::fecho("手机号不能为空");

		//同一号码 十秒内不能发送
		$num=$phone;$key=substr(time()."",0,-1);
		zfun::f_insert("F",array("num"=>$num."_".$key,"time"=>time()));

		$set=zfun::f_getset("dxappisopen,dxappname,app_quick_login_yzmstr");
		$dxappisopen = $set['dxappisopen'];
		$dxappname = $set['dxappname'];
		if(empty($dxappisopen))zfun::fecho("发送失败");
		$yzm = $this -> getRandStr();
		$msgstr = '验证码：' . $yzm . '【' . $dxappname . '】';
		if(!empty($set['app_quick_login_yzmstr'])){
			$msgstr=$set['app_quick_login_yzmstr'];
			$msgstr=str_replace("{code}",$yzm,$msgstr);
			$msgstr.='【' . $dxappname . '】';
		}
		//zfun::isoff($msgstr);
		$fndx = $this -> getApi('sendDx');
		$end = $fndx -> send($msgstr, $phone);
		if(empty($end))zfun::fecho("发送失败");
		$session_info['emailcode'] = md5($yzm);
		$session_info['emailtime'] = time();
		$session_info['email'] = $phone;
		//fpre($yzm);
		$result=$this -> setCache('captch', md5($phone), $session_info);
		if(empty($result))zfun::fecho("发送失败");
		zfun::fecho("发送成功",1,1);
	}
	// 产生随机码
	public function getRandStr() {
		$str = '0123456789';
		$randString = '';
		$len = strlen($str) - 1;
		for ($i = 0; $i < 6; $i++) {
			$num = mt_rand(0, $len);
			$randString .= $str[$num];
		}
		return $randString;
	}
}
?>
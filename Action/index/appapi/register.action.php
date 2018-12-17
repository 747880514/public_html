<?php
include_once ROOT_PATH."comm/zfun.php";
include_once ROOT_PATH.'Action/index/appapi/dgappcomm.php';
class registerAction extends Action{
	//省市县转换
	public static function ssx(){
		$arr=appcomm::parametercheck('ProvinceName,CityName,DistrictName,position');
		foreach($arr as $k=>$v){
			$arr[$k]=str_replace(array("省","市","区","自治区","特别行政区")	,'',$v);
		}
		$dq1=zfun::f_row("O2OProvince","ProvinceName like '%".$arr['ProvinceName']."%'");
		if(empty($dq1))zfun::fecho("省 ".$arr['ProvinceName']." 不存在");
		$arr['dq1']=$dq1['ProvinceID'];
		$dq2=zfun::f_row("O2OCity","CityName like '%".$arr['CityName']."%'");
		if(empty($dq2))zfun::fecho("市 ".$arr['CityName']." 不存在");
		$arr['dq2']=$dq2['CityID'];
		$dq3=zfun::f_row("O2ODistrict","DistrictName like '%".$arr['DistrictName']."%'");
		if(empty($dq3))zfun::fecho("县 ".$arr['DistrictName']." 不存在");
		$arr['dq3']=$dq3['DistrictID'];
		return $arr;
	}
	//获取网点
	public function GetDot(){
		appcomm::signcheck();
		$arr=appcomm::parametercheck("DistrictName");
		$dq3=zfun::f_row("O2ODistrict","DistrictName like '%".$arr['DistrictName']."%'");
		if(empty($dq3))zfun::fecho("县 ".$arr['DistrictName']." 不存在");
		$arr=array();
		$arr[]=array(
			"WID"=>1,
			"WName"=>"测试网点1",
			"DistrictID"=>1776,//区id
		);
		$arr[]=array(
			"WID"=>1,
			"WName"=>"测试网点2",
			"DistrictID"=>1776,//区id
		);
		$arr[]=array(
			"WID"=>1,
			"WName"=>"测试网点3",
			"DistrictID"=>1776,//区id
		);
		zfun::fecho("获取网点",$arr,1);
	}
	public function doing(){
		appcomm::signcheck();
		//$ssx=self::ssx();
		if(empty($_POST['username']))zfun::fecho("缺少参数username");
		if(empty($_POST['pwd']))zfun::fecho("缺少参数pwd");	
		$userModel = $this -> getDatabase('User');
		//判断用户是否已注册
		$regUser = $userModel -> select("email='{$_POST['username']}' or phone='{$_POST['username']}'");
		$regCount = count($regUser);
		if ($regCount <> 0)zfun::fecho("该账号已被注册");
		if (!empty($_POST['token'])) {
			$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		}
		$webname=self::getSetting("webset_webnick");
		$set=zfun::f_getset("jf_reg");
		$jf_reg = floatval($set['jf_reg']);
		
		$wid=intval($_POST['wid']);
		if (!empty($_POST['type'])) {
			
			if(!empty($_POST['tid'])){
				$Decodekey = $this -> getApp('Tgidkey');
				$_POST['tid'] = intval($Decodekey -> Decodekey($_POST['tid']));
				$tmp=zfun::f_count("User","id=".$_POST['tid']);
				if(empty($tmp))zfun::fecho("推荐人不存在");
			}
			switch($_POST['type']) {
				case 1 :
					$token = md5(base64_encode($_POST['username'] . time() . uniqid(rand())));
					$userdata = array('phone' => $_POST['username'], 'nickname' => $_POST['username'], 'password' => $_POST['pwd'], 'integral' => $jf_reg, 'reg_time' => time(), 'token' => $token);
					$userdata['dq1']=intval($ssx['dq1']);
					$userdata['dq2']=intval($ssx['dq2']);
					$userdata['dq3']=intval($ssx['dq3']);
					$userdata['dq4']=$wid;
					if(!empty($_POST['nickname'])){
						$userdata['nickname']=filter_check($_POST['nickname']);
					}
					$userdata['position']=$ssx['position'];
					if (!empty($_POST['tid'])) {
						$userdata['extend_id'] = $_POST['tid'];
					}
					$insertid = $userModel -> insertId($userdata);
					$this -> setSessionUser($insertid, $_POST['username']);
					//注册送积分事件
					self::reg_shijian($insertid);
					$path=ROOT_PATH."comm/cwdl_rule.php";
					//这是要把累积的金额计算处理,然后升级
					if(file_exists($path)==true){
						include_once $path;
						cwdl_rule::yq_friend($_POST['tid']);
						cwdl_rule::yq_next_friend($_POST['tid']);
					}
					zfun::fecho("注册成功",'',1);
					break;
				case 2 :
					$jf_reg = $this -> getSetting('jf_reg');
					$arr = explode("@", $_POST['username']);
					$token = md5(base64_encode($_POST['username'] . time() . uniqid(rand())));
					$userdata = array('email' => $_POST['username'], 'nickname' => $arr[0], 'password' => $_POST['pwd'], 'integral' => $jf_reg, 'reg_time' => time(), 'token' => $token);
					$userdata['dq1']=intval($ssx['dq1']);
					$userdata['dq2']=intval($ssx['dq2']);
					$userdata['dq3']=intval($ssx['dq3']);
					$userdata['dq4']=$wid;
					if(!empty($_POST['nickname'])){
						$userdata['nickname']=filter_check($_POST['nickname']);
					}
					$userdata['position']=$ssx['position'];
					if (!empty($_POST['tid'])) {
						$userdata['extend_id'] = $_POST['tid'];
					}
					$insertid = $userModel -> insertId($userdata);
					$this -> setSessionUser($insertid, $_POST['username']);
					//注册送积分事件
					self::reg_shijian($insertid);
					$path=ROOT_PATH."comm/cwdl_rule.php";
					//这是要把累积的金额计算处理,然后升级
					if(file_exists($path)==true){
						include_once $path;
						cwdl_rule::yq_friend($_POST['tid']);
						cwdl_rule::yq_next_friend($_POST['tid']);
					}
					zfun::fecho("注册成功",'',1);
					break;
			}
		}	
	}
	public function threelogin() {
		appcomm::signcheck();
		//$ssx=self::ssx();
		$userModel = $this -> getDatabase('User');
		$Decodekey = $this -> getApp('Tgidkey');
		if (!empty($_POST['token'])) {
			$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
			$person_id = $userid['id'];
			if (!empty($person_id)) {
				$person_tgid = $Decodekey -> Decodekey($person_id);
			} else {
				$person_tgid = 0;
			}
		}
		if (empty($_POST['type']))zfun::fecho("缺少参数");
		$set=zfun::f_getset("jf_reg,blocking_price_endday");	//百里，追加blocking_price_endday
		$jf_reg = floatval($set['jf_reg']);
		switch($_POST['type']) {
			case 1:
				$openid = $_POST["openid"];
				$finduser = $userModel -> selectRow("qq_au='$openid'");
				if (!empty($finduser))zfun::fecho("已有账号",$finduser,1);
				$first = 'qq_';
				$userdata['loginname'] = 'qq_' . $_POST["openid"];
				$userdata['password'] = $_POST["openid"];
				$userdata['nickname'] = $_POST['nickname'];
				$userdata['three_nickname'] = $_POST['nickname'];
				if (!empty($_POST['user_sex'])) {
					switch($_POST['user_sex']) {
						case '男' :
							$userdata['sex'] = 1;
							break;
						case '女' :
							$userdata['sex'] = 0;
							break;
					}
				}
				$userdata['address'] = $_POST['user_address'];
				$userdata['integral'] = floatval($this -> getSetting('jf_reg'));
				//计算注册积分
				$userdata['head_img'] = $_POST['figureurl_qq_2'];
				$userdata['qq_au'] = $_POST["openid"];
				$userdata['reg_time'] = time();
				$userdata['login_time'] = time();
				$userdata['login_num'] = 1;
				$userdata['extend_id'] = $person_tgid + 0;
				$userdata['token'] = $token = md5(base64_encode($_POST["openid"] . time() . uniqid(rand())));
				break;
			case 2 :
				$taobaoid = $_POST['taobaoid'];
				$finduser = $userModel -> selectRow("taobao_au='$taobaoid'");
				if (!empty($finduser)) {
					zfun::fecho("已有账号",$finduser,1);
				}
				$first = 'taobao_';
				$userdata['loginname'] = 'taobao_' . $taobaoid;
				$userdata['password'] = $taobaoid;
				$userdata['nickname'] = $_POST['user_nick_name_taobao'];
				$userdata['three_nickname'] = $_POST['user_nick_name_taobao'];
				$userdata['integral'] = floatval($this -> getSetting('jf_reg'));
				//计算注册积分
				$userdata['head_img'] = $_POST['taobao_avatar_hd'];
				$userdata['taobao_au'] = $taobaoid;
				$userdata['reg_time'] = time();
				$userdata['login_time'] = time();
				$userdata['login_num'] = 1;
				$userdata['extend_id'] = $person_tgid + 0;
				$userdata['token'] = $token = md5(base64_encode($_POST['taobaoid'] . time() . uniqid(rand())));
				break;
			case 3 :
				$weixinid = $_POST['weixinid'];
				$finduser = $userModel -> selectRow("weixin_au='$weixinid'");
				if (!empty($finduser)) {
					zfun::fecho("已有账号",$finduser,1);
				}
				$first = 'weixin_';
				$userdata['loginname'] = 'weixin_' . $weixinid;
				$userdata['password'] = $weixinid;
				$userdata['nickname'] = $_POST['weixin_screen_name'];
				$userdata['three_nickname'] = $_POST['weixin_screen_name'];
				$userdata['integral'] = floatval($this -> getSetting('jf_reg'));
				//计算注册积分
				$userdata['head_img'] = $_POST['weixin_avatar_hd'];
				$userdata['weixin_au'] = $weixinid;
				$userdata['reg_time'] = time();
				$userdata['login_time'] = time();
				$userdata['login_num'] = 1;
				$userdata['extend_id'] = $person_tgid + 0;
				$userdata['token'] = $token = md5(base64_encode($_POST['weixinid'] . time() . uniqid(rand())));
				break;
		}

		$userdata['dq1']=intval($ssx['dq1']);
		$userdata['dq2']=intval($ssx['dq2']);
		$userdata['dq3']=intval($ssx['dq3']);
		$userdata['dq4']=intval($_POST['wid']);
		$userdata['position']=$ssx['position'];

		//百里
		//首单结束期限
		$blocking_price_endtime = $set['blocking_price_endday'] > 0 ? $set['blocking_price_endday'] : 3;
		$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

		$userdata["blocking_price"] = $commission_reg;	//*****需要注释掉commission字段
		$userdata["blocking_price_endtime"] = $blocking_price_endtime;	//首单结束期限


		$otheruserid = $userModel -> insertId($userdata);
		self::reg_shijian($otheruserid);//the fuck
		$newNickName = $first . $Decodekey -> addkey($otheruserid) . '_' . $this -> getRandStr();
		$userModel -> update('id=' . $otheruserid, array('loginname' => $newNickName));
		if ($person_tgid != 0) {
			$jf_spread_jf = $this -> getSetting('jf_spread_jf');
			$tgUser = $userModel -> selectRow("id='$person_tgid'");
			$newintegral = $tgUser['integral'] + $jf_spread_jf;
			$userModel -> update('id=' . $person_tgid, array('integral' => $newintegral));
		}
		$result = $userModel -> selectRow('token="' . $token . '"');
		zfun::fecho("第三方登录",$result,1);
	}
	public function reg_shijian($uid=0){
		if(empty($uid))zfun::fecho("注册失败");
		$set=zfun::f_getset("jf_reg");
		$jf_reg = floatval($set['jf_reg']);
		
		if($jf_reg>0){
			zfun::f_adddetail("注册送 ".$jf_reg ." 积分",$uid,6,0,$jf_reg);
		}
		$webname=self::getSetting("webset_webnick");
		$sysMsgModel = $this -> getDatabase('sysMsg');
		$sysMsgModel -> insert(array('time' => time(), 'uid' => $uid, 'msg' => '尊敬的用户，欢迎来到'.$webname, 'title' => '温馨提示'));		
		
		return true;
	}
	
	//获取验证码
	public function getcode(){
		appcomm::signcheck();
		if (empty($_POST['type'])) {
			zfun::fecho("缺少参数");
		}
		if (!empty($_POST['check']) && $_POST['check'] == 1) {
			$userModel = $this -> getDatabase('User');
			//判断用户是否已注册
			$regUser = $userModel -> select("email='{$_POST['username']}' or phone='{$_POST['username']}'");
			$regCount = count($regUser);
			//确认密码6～16位，区分大小写
			if ($regCount <> 0) {
				zfun::fecho("该账号已被注册");
				//该用户已被注册
			}
		}
		if ($_POST['type'] == 1) {
			if (!empty($_POST['username'])) {
				//$this -> fecho(null, 1, "手机号码");
				$dxappisopen = $this -> getSetting('dxappisopen');
				$phone = filter_check($_POST['username']);
				if (!empty($phone)) {
					if ($dxappisopen == 1) {
						$dxappname = $this -> getSetting('dxappname');
						$yzm = $this -> getRandStr();
						$msgstr = '验证码：' . $yzm . '【' . $dxappname . '】';
						$fndx = $this -> getApi('sendDx');
						$end = $fndx -> send($msgstr, $phone);
						if ($end) {
							$session_info['emailcode'] = md5($yzm);
							$session_info['emailtime'] = time();
							$session_info['email'] = $phone;
							if ($this -> setCache('captch', md5(base64_encode($_POST['username'])), $session_info)) {
								zfun::fecho("发送成功!",1,1);
							} else {
								zfun::fecho("发送失败!");
							}
						} else {
							zfun::fecho("发送失败");
						}
						zfun::fecho("发送失败");
					}
				}
			}
		} else if ($_POST['type'] == 2) {
			if (!empty($_POST['username'])) {
				$send = $this -> getApp('StoreForget');
				$emailcode = $send -> smtp_mail($_POST['username'], 998);
				if (!empty($emailcode['emailcode']) && !empty($emailcode['emailtime'])) {
					if ($this -> setCache('captch', md5(base64_encode($_POST['username'])), $emailcode)) {
						zfun::fecho("发送成功",1,1);
					} else {
						zfun::fecho("发送失败");
					}
				} else {
					zfun::fecho("发送失败");
				}
			}
		}	
	}
	//检验验证码
	public function checkcode() {
		appcomm::signcheck();
		if (empty($_POST['username']))zfun::fecho("请输入用户名");
		if (!empty($_POST['captch'])) {
			$session_capth = $this -> getCache('captch', md5(base64_encode($_POST['username'])));
			$sessioncode = $session_capth['emailcode'];
			$sessiontime = $session_capth['emailtime'];
			$sessionemail = $session_capth['email'];
			if ("$sessionemail" != "{$_POST['username']}") {
				zfun::fecho("用户名不匹配");
			}
			$mdemailcode = md5($_POST['captch']);
			$emailtime = time();
			$time = 300;
			$chaju = $emailtime - $sessiontime;
			if ("$mdemailcode" != "$sessioncode") {
				zfun::fecho("验证码不正确");
				//验证码不正确9
			} else if ($chaju > $time) {
				zfun::fecho("验证码有效时间已过");
				//验证码有效时间已过
			} else {
				$this -> delCache("captch", $_POST['username']);
				zfun::fecho("正确",1,1);
				//正确
			}
		} else {
			zfun::fecho("没有输入验证码");
		}
	}
	
	public function login() {//zhe
		appcomm::signcheck();
		if (empty($_POST['username']))zfun::fecho("缺少参数username");
		if (empty($_POST['pwd']))zfun::fecho("缺少参数pwd");
		$userModel = $this -> getDatabase('User');
		$chkUsername = $userModel -> selectRow("loginname='{$_POST['username']}' or phone='{$_POST['username']}' or email='{$_POST['username']}'");
		if ($chkUsername) {
			if ($chkUsername['password'] == $_POST['pwd']) {
				$token = md5(base64_encode($_POST['username'] . time() . uniqid(rand())));
				if(!empty($chkUsername['token'])){
					$token=$chkUsername['token'];
				}
				$userModel -> update('id=' . $chkUsername['id'], array('token' => $token, 'login_time' => time()));
				$this -> setSessionUser($chkUsername['id'], $chkUsername['nickname']);
				$chkUsername['token'] = $token;
				if (!preg_match("/\b(([\w-]+:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/", $chkUsername['head_img'])) {
						$chkUsername['head_img'] = UPLOAD_URL . 'user/' . $chkUsername['head_img'];
				}	
				$chkUsername['tid'] = $chkUsername['id'];
				zfun::fecho("成功",$chkUsername,1);
				// 成功
			} else {
				zfun::fecho("密码错误");
			}
		} else {
			zfun::fecho("没有该用户");
			// 没有该用户
		}
	}
	
	public function updatePwd() {
		appcomm::signcheck();
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['token']))zfun::fecho("没有登录");
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if (empty($_POST['pwd']))zfun::fecho("缺少参数");
		$user = $userModel -> update('id=' . $userid['id'], array("pwd" => $_POST['pwd']));
		zfun::fecho("修改成功",1,1);
	}

	public function forgetPwd() {
		appcomm::signcheck();
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['username']))zfun::fecho("没有登录");
		$userid = $userModel -> selectRow("email='{$_POST['username']}' or phone='{$_POST['username']}'");
		if (empty($_POST['pwd']))zfun::fecho("缺少参数");
		$user = $userModel -> update('id=' . $userid['id'], array("password" => $_POST['pwd']));
		zfun::fecho("修改成功",1,1);
	}
	
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
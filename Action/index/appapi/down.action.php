<?php

fun("zfun");



class downAction extends Action{

	public function __construct()
	{
		session_start();
	}

	public function supdownload($url=NULL){
		$set=zfun::f_getset("share_host");

		//百里 如果没有已授权标识，则先注册用户（域名正常访问）
		// if(empty($_GET['registered']) && empty($_GET['test']) == 1)
		// {
		// 	header("location:http://app.juhuivip.com?registered=1&tgid=".strval($_GET['tgid']));
		// }
		// else
		// {
		// 	$baili['uid'] = $_GET['uid'];
		// 	$baili['mobile'] = !empty($_GET['mobile']) ? $_GET['mobile'] : -1;
		// 	$this->assign("baili", $baili);
		// }

		//通过微信授权登录（域名被封解决方案）
		$baili['uid'] = $_GET['uid'];
		$baili['mobile'] = !empty($_GET['mobile']) ? $_GET['mobile'] : -1;
		$baili['share_host'] = $set['share_host'];

		//读取花蒜后台自定义页面
		//顶部背景图
		$page = file_get_contents("https://www.juhuivip.com/app/index.php?i=2&c=entry&m=ewei_shopv2&do=mobile&r=diypage&id=152");
		$pattern = "/<div class=\"default-items\">(.*?)<div class=\"custom-items\"><\/div>/ies";
		preg_match_all($pattern, $page, $matches);
		$page = $matches[0][0];
		$page = str_replace("<div class=\"custom-items\"></div>", "", $page);
		$baili['html'] = $page;

		//底部背景色
		$pattern = "/background: (.*?);/ies";
		preg_match_all($pattern, $page, $matches);
		$baili['bgcolor'] = $matches[1][0];

		//百里.数据返回
		$this->assign("baili", $baili);



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

		$this->assign("unionid",$_REQUEST['unionid']);

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

	 /**
	  * [get_unionid 百里]
	  * @Author   Baili
	  * @Email    baili@juhuivip.com
	  * @DateTime 2018-12-22T18:18:14+0800
	  * @return   [type]                   [description]
	  */
	public function get_unionid()
	{
		$tgid = $_REQUEST['tgid'];
		$_SESSION['down_tgid'] = $tgid;

		$set=zfun::f_getset("share_host");

		$redirect_uri = urlencode("http://".$set['share_host']."/?mod=appapi&act=down&ctrl=callback");
		$url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx22b99a9b76e68aff&redirect_uri=".$redirect_uri."&response_type=code&scope=snsapi_userinfo&state=1";
		header('Location: '.$url);
	}

	/**
	 * [callback 百里]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2018-12-22T18:18:20+0800
	 * @return   function                 [description]
	 */
	public function callback()
	{
		$code = $_REQUEST['code'];
		$tgid = $_SESSION['down_tgid'];
		$set=zfun::f_getset("share_host");

		/*根据code获取用户openid*/
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx22b99a9b76e68aff&secret=7c34b8015bf2129fe57184c1ce1344c2&code=".$code."&grant_type=authorization_code";

		$abs = file_get_contents($url);
		$obj=json_decode($abs);

		$access_token = $obj->access_token;
		$openid = $obj->openid;
		/*根据code获取用户openid end*/

		/*根据用户openid获取用户基本信息*/
		$abs_url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
		$abs_url_data = file_get_contents($abs_url);
		$obj_data=json_decode($abs_url_data);
		$obj_data->access_token = $access_token;

		$unionid = $obj_data->unionid;
		$openid = $obj_data->openid;
		$nickname = $obj_data->nickname;
		$headimgurl = $obj_data->headimgurl;

		$sex = $obj_data->sex;

		$dq1 = zfun::f_row("Province", "ProvinceName LIKE '%".$obj_data->province."%'", "ProvinceID");
		$dq1 = $dq1['ProvinceID'];

		// $dq2 = zfun::f_row("District", " DistrictName LIKE '%".$obj_data->city."%'", "DistrictID");
		// $dq2 = $dq2['DistrictID'];

		if(!empty($obj_data->city))
		{
			$dq2 = zfun::f_row("City", "ProvinceID = {$dq1} AND CityName LIKE '%".$obj_data->city."%'", "CityID");
			$dq2 = $dq2['CityID'];
		}

		if(empty($dq2) && !empty($dq1))
		{
			$dq2 = zfun::f_row("City", "ProvinceID = {$dq1}", "CityID");
			$dq2 = $dq2['CityID'];

			$dq3 = zfun::f_row("District", "CityID = {$dq2} AND DistrictName LIKE '%".$obj_data->city."%'", "DistrictID");
			$dq3 = $dq3['DistrictID'];
		}

		//检测用户，注册
		$user = zfun::f_row("User", "weixin_au = '".$unionid."' OR weixin_au = '".$openid."'");

		$tgidkey = $this->getApp('Tgidkey');
		$tgid = $tgidkey->Decodekey($tgid);

		if(!$user)
		{
			$commission_reg = floatval(self::getSetting("commission_reg"));
			$jf_reg=floatval(self::getSetting("jf_reg"));

			//百里
			$blocking_price_endtime = self::getSetting('blocking_price_endday') > 0 ? self::getSetting('blocking_price_endday') : 3;
			$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

			$arr=array(

				"nickname"=>$nickname,

				"reg_time"=>time(),

				"login_time"=>time(),

				// "commission"=>$commission_reg,
				"commission"=>0,

				"integral"=>$jf_reg,

				'head_img' => $headimgurl,

				"extend_id"=>$tgid,//邀请人id

				"weixin_au" => $unionid,

				"wx_openid" => $unionid,

				"token" => md5(base64_encode($tgid . time() . uniqid(rand()))),

				"sex" => $sex,

				"dq1" => $dq1,

				"dq2" => $dq2,

				"dq3" => $dq3,

				"wx_data" => serialize($obj_data),

				"blocking_price" => $commission_reg,

				"blocking_price_endtime" => $blocking_price_endtime,	//3天内

			);

			$uid = zfun::f_insert("User", $arr);
			$mobile = -1;
		}
		else
		{
			$uid = $user['id'];
			$mobile = $user['phone'];

			//未下单的用户，可以随意修改上下级关系
			if($user['is_create_order'] != 1 && $user['extend_id'] != $tgid)
			{
				$is_create_order = zfun::f_row("Order", "uid='{$uid}' AND `status` IN ('订单付款','订单结算')");
				if($is_create_order)
				{
					$updatedata['is_create_order'] = 1;	//已下单，不能更改上下级，固定标识
				}
				else
				{
					$updatedata['extend_id'] = $tgid;	//未下单，可以更改上下级
				}

				if($user['extend_id'] == '2094')
				{
					$updatedata['is_sqdl'] = 1;	//如果上级在公司名下，降级处理
				}

				zfun::f_update("User", "id = '{$uid}'", $updatedata);
			}
		}


		$downurl = "http://".$set['share_host']."/?mod=appapi&act=down&ctrl=supdownload&uid=".$uid."&mobile=".$mobile;
		header('Location: '.$downurl);
	}


	/**
	 * [bind_mobile 百里绑定手机号]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2018-10-27T11:50:13+0800
	 * @return   [type]                   [description]
	 */
	public function bind_mobile()
	{

		$uid = intval($_POST['uid']);

		$mobile = strval(trim($_POST['mobile']));

		$result = array(
			'status' => 1,
			'msg' => '绑定成功！点击确定下载APP',
		);

		//uid数据错误
		$user = zfun::f_row("User","id='{$uid}'");
		if(!$user)
		{
			$result = array(
				'status' => 0,
				'msg' => '参数错误！',
			);
			echo json_encode($result);
			die;
		}

		//手机号格式不正确
		if(!preg_match("/^1\d{10}$/",$mobile)){
		    $result = array(
				'status' => 0,
				'msg' => '手机号格式不正确',
			);
			echo json_encode($result);
			die;
		}



		//手机号已存在
		$hasmobile = zfun::f_row("User","phone='{$mobile}'");
		if($hasmobile)
		{
			$result = array(
				'status' => 1,
				'msg' => '手机号验证通过',	//负责老用户下载
			);

			echo json_encode($result);
			die;
		}

		//绑定成功/失败
		$res = false;
		if(empty($user['phone']))
		{
			$res = zfun::f_update("User","id='{$uid}'",array('phone'=>$mobile));
		}

		if(!$res)
		{
			$result = array(
				'status' => 0,
				'msg' => '绑定失败，请稍后重试！',
			);
		}
		echo json_encode($result);

	}

}

?>
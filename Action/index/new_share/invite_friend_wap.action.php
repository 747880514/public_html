<?php

include_once ROOT_PATH."comm/zfun.php";

include_once ROOT_PATH."Action/index/default/alimama.action.php";

class invite_friend_wapAction extends Action{

	/*新用户页面*/

	public function new_packet(){

		$time=time();

		$goods=zfun::f_row("Goods","is_invite=1 AND start_time<$time AND end_time>$time",NULL,"tg_sort DESC");

		$display="display:-webkit-box";

		if(empty($goods))$display="display:none";

		$str="xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,fx_icon,app_sjdl_onoff";

		$set = zfun::f_getset($str);

		if(!empty($set['fx_icon']))$set['fx_icon']=UPLOAD_URL."slide/".$set['fx_icon'];

		$set['xrhb']=$set['xinren_hongbao'];

		$goods=zfun::f_fgoodscommission(array($goods));$goods=reset($goods);

		$att=explode(".",zfun::dian($goods['goods_price']));

		$goods['top']=$att[0];$goods['btm']=$att[1];

		$tggoods=zfun::f_goods("Goods","is_invite=1 AND start_time<$time AND end_time>$time",NULL,"tg_sort DESC",$arr,0);

		$tggoods=zfun::f_fgoodscommission($tggoods);

		foreach($tggoods as $k=>$v){

			$att=explode(".",zfun::dian($v['goods_price']));

			$tggoods[$k]['top']=$att[0];$tggoods[$k]['btm']=$att[1];

		}

		/*介绍*/

		$help=zfun::f_row("HelperArticle","type='appfenxiang'");

		$this->assign("help",$help);

		$this->assign("goods",$goods);

		$this->assign("set",$set);

		$this->assign("display",$display);

		$this->assign("tggoods",$tggoods);

		$this->assign("app_sjdl_onoff",$set['app_sjdl_onoff']);

		$this->display("invite_friend_wap","new_packet","wap");

		$this->play();

	}

	/*商品页面*/

	public function goods_detail(){

		$id=filter_check($_GET['id']);

		$goods=zfun::f_row("Goods","fnuo_id='$id'");

		if(empty($goods)){

			$goods1=alimamaAction::getcommission($id);

			//if(empty($goods))zfun::fecho("商品无效");

			$goods['goods_title']=$goods1['title'];

			$goods['fnuo_id']=$id;

			$goods['goods_price']=$goods1['zkPrice'];

			$goods['goods_img']="https:".$goods1['pictUrl']."_300x300.jpg";

			$goods['goods_img_min']="https:".$goods1['pictUrl']."_300x300.jpg";

			$goods['commission']=floatval($goods1['tkRate']);

			$goods['yhq_span']=$goods1['couponInfo'];

			$goods['yhq_price']=floatval($goods1['couponAmount']);

		}

		$tgid = intval($_GET['tgid']);

		$Decodekey = $this -> getApp('Tgidkey');

		$tgid = $Decodekey -> Decodekey($tgid);

		$tguser=zfun::f_row("User","id='$tgid'");

		$_POST['yhq']=1;

		$goods=zfun::f_fgoodscommission(array($goods));$goods=reset($goods);

		$display="display:block";$display1="display:none";

		if(intval($goods['yhq_price'])<=0){

			$display="display:none";$display1="display:block";

		}

		$display3="display:block";$display2="display:none";

		if($tguser['is_sqdl']==1){

			$display3="display:none";$display2="display:block";

		}

		$arr=explode(",",$goods['goods_img_min']);

		foreach($arr as $k=>$v){

			if(empty($v))continue;

			if(strstr($v,"http")==false)$arr[$k]=UPLOAD_URL.$v;

		}

		if($goods['yhq_price']>0){

			actionfun("default/tbk_coupon");

			$tmp=tbk_couponAction::getone($goods['goods_title'],$goods['fnuo_id']);

			if(!empty($tmp['url']))$goods['yhq_url']=$tmp['url'];

		}

		if(!empty($goods['yhq_url'])&&strstr($goods['yhq_url'],"uland.taobao.com")==false){

			$goods['yhq_url']="https://uland.taobao.com/coupon/edetail?activityId=".self::getin($goods['yhq_url'],"activityId")."&itemId=".$goods['fnuo_id']."&pid=".$this->getSetting('taobaopid')."&nowake=1";

		}

		$type=0;

		if($goods['shop_id']==4)$type=1;

		$goods['kouling']=self::kouling($goods['fnuo_id'],$tgid,$type);

		$goods['tkl']=self::taokouling($goods);

		$set=zfun::f_getset("xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,android_url,ios_url");

		$set['xrhb']=$set['xinren_hongbao'];

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){

			$set['url']=$set['ios_url'];

		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){

			$set['url']=$set['android_url'];

		}else{

			$set['url']=$set['android_url'];

		}

		$this->assign("arr",$arr);

		$this->assign("set",$set);

		$this->assign("goods",$goods);

		$this->assign("display1",$display1);

		$this->assign("display",$display);

		$this->assign("display2",$display2);

		$this->assign("display3",$display3);

		$this->display("invite_friend_wap","goods_detail","wap");

		$this->play();

	}

	/*老用户页面*/

	public function old_packet(){

		$set=zfun::f_getset("xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,AppLogo,android_url,ios_url");

		$set['xrhb']=$set['xinren_hongbao'];

		if(!empty($set['AppLogo']))$set['AppLogo']=UPLOAD_URL."slide/".$set['AppLogo'];

		else $set['AppLogo']='View/index/img/wap/invite_friend_wap/old_packet/packet_logo.png';

		if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){

			$set['url']=$set['ios_url'];

		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){

			$set['url']=$set['android_url'];

		}else{

			$set['url']=$set['android_url'];

		}

		$this->assign("set",$set);

		$this->display("invite_friend_wap","old_packet","wap");

		$this->play();

	}

	/*找回订单*/

	public function zhdd(){

		$id=filter_check($_POST['orderId']);

		if(!empty($_POST['token'])){

			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");

			$uid=$user['id'];

		}

		if(empty($uid))zfun::fecho("请登录");

		$order=zfun::f_row("Order","uid=0 AND orderId='$id'");

		if(empty($order))zfun::fecho("订单不存在");

		$result=zfun::f_update("Order", "orderId='$id'", array("uid" => $uid));

		if(empty($result))zfun::fecho("订单找回失败");

		zfun::fecho("订单找回成功");

	}

	/*生成返利口令*/

	public static function kouling($fnuo_id,$uid=0,$type){

		$_POST['type']=$type;

		$pset['fnuo_id']=$fnuo_id;

		$garr=array();

		$garr['yhq_span']="";

		$garr['yhq_price']=0;

		if(intval($_POST['type'])==0){

			$goods=alimamaAction::getcommission($pset['fnuo_id']);

			//if(empty($goods))zfun::fecho("商品无效");

			$garr['goods_title']=$goods['title'];

			$garr['goods_price']=$goods['zkPrice'];

			$garr['goods_img']="https:".$goods['pictUrl']."_300x300.jpg";

			$garr['commission']=floatval($goods['tkRate']);

			$garr['yhq_span']=$goods['couponInfo'];

			$garr['yhq_price']=floatval($goods['couponAmount']);

			//fpre($goods);

		}

		else{

			self::getjdset();

			fun('jdapi');

			$goods=reset(jdapi::getgoods($pset['fnuo_id']));

			$price=reset(jdapi::getprice($pset['fnuo_id']));

			$commission=reset(jdapi::getgoodsinfo($pset['fnuo_id']));

			$garr['goods_title']=$goods['name'];

			$garr['goods_price']=$price["price"];

			$garr['goods_img']=$goods['imagePath'];

			$garr['commission']=floatval($commission['commisionRatioPc']);

		}

		//if(empty($goods))zfun::fecho("error");

		$tmp=$uid."_".$pset['fnuo_id']."_".$garr['goods_title'];

			

		$code=substr(md5($tmp),0,6);

		$arr=array(

			"code"=>$code,

			"extend_id"=>$uid,

			"fnuo_id"=>$pset['fnuo_id'],

			"type"=>intval($_POST['type']),

			"time"=>time(),

			"goods_title"=>addslashes($garr['goods_title']),

			"goods_price"=>$garr['goods_price'],

			"goods_img"=>$garr['goods_img'],

			"commission"=>$garr['commission'],

			"yhq_span"=>$garr['yhq_span'],

			"yhq_price"=>$garr['yhq_price'],

		);

		$where="extend_id='$uid' and fnuo_id='".$pset['fnuo_id']."'";

		$kouling=zfun::f_row("Kouling",$where);

		if(empty($kouling)){

			zfun::f_insert("Kouling",$arr);

		}

		else{

			zfun::f_update("Kouling",$where,$arr);	

		}

		$code="#".$code."#";

		return $code;

	}

	/*淘口令*/

	public static function taokouling($goods){

		include_once ROOT_PATH."Action/index/weixin/tkl.action.php";

		$tkl=tkl::gettkl($goods);

		return $tkl;

	}

	/*超级问卷*/

	public function questionnaire(){

		$set=zfun::f_getset("xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,AppLogo");

		$set['xrhb']=$set['xinren_hongbao'];

		$this->assign("set",$set);

		$this->display("invite_friend_wap","questionnaire","wap");

		$this->play();

	}

	/*新人领取红包后*/

	public function succeed(){

		$time=time();

		$goods=zfun::f_goods("Goods","is_invite=1 AND start_time<$time AND end_time>$time",NULL,"tg_sort DESC",$arr,0);

		$goods=zfun::f_fgoodscommission($goods);

		foreach($goods as $k=>$v){

			$att=explode(".",zfun::dian($v['goods_price']));

			$goods[$k]['top']=$att[0];$goods[$k]['btm']=$att[1];

		}

		$set=zfun::f_getset("xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,AppLogo");

		$set['xrhb']=$set['xinren_hongbao'];

		if(!empty($set['AppLogo']))$set['AppLogo']=UPLOAD_URL."slide/".$set['AppLogo'];

		else $set['AppLogo']='View/index/img/wap/invite_friend_wap/old_packet/packet_logo.png';

		$this->assign("set",$set);

		$this->assign("goods",$goods);

		$this->display("invite_friend_wap","succeed","wap");

		$this->play();

	}

	public function wapyzm1(){//手机注册验证码

		if(empty($_POST['code']))return false;

		$phone=intval($_POST['code']);

		$yzm=$this->getRandStr();

		$set=zfun::f_getset("dxappname");

		$msgstr='【'.$set['dxappname'].'】'.'验证码：'.$yzm;

		$fndx=$this->getApi('sendDx');

		$end=$fndx->send($msgstr,$phone);

		if(!$end)zfun::fecho("发送失败!");

		$_SESSION['wapyzm1']=md5($yzm);

		$_SESSION['wapphone1']=$phone;

		unset($_SESSION['wapyzm2']);

		unset($_SESSION['wapemail2']);

		zfun::fecho("发送成功!",1,1);

	}

	public function checkcode(){

		$yzm=md5(strtoupper($_POST['yzm']));

		$phone=trim($_POST['phone']);

		if(empty($_SESSION['wapyzm1']))zfun::fecho("请先发送验证码");

		if($_SESSION['wapphone1']!=$phone)zfun::fecho("手机号不正确");

		if(empty($_POST['password']))zfun::fecho("请输入密码");

		if($_SESSION['wapyzm1']!=$yzm)zfun::fecho("验证码错误");

		$user=zfun::f_count("User","phone='$phone' OR loginname='$phone'");

		unset($_SESSION['wapyzm1'],$_SESSION['wapphone1']);

		if($user>0)zfun::fecho("您已经是老用户",array("old"=>1),1);

		$password=md5($_POST['password']);

		//获取后台设置的新用户注册赠送的积分

		if(!empty($_POST['tgid'])){

			$tgid = intval($_POST['tgid']);

			$Decodekey = $this -> getApp('Tgidkey');

			$tgid = $Decodekey -> Decodekey($tgid);

			$tmp=zfun::f_count("User","id='$tgid'");

			if(empty($tmp))zfun::fecho("推荐人不存在");

			

		}

		$set=zfun::f_getset("jf_reg,commission_reg,jf_name,extendreg,xinren_hongbao,blocking_price_endday");

		$set['xrhb']=$set['xinren_hongbao'];

		//$extendreg=intval($set["extendreg"]);

		//if($extendreg&&empty($tgid))zfun::alert("推荐人ID必填");

		$jf_reg=floatval($set['jf_reg']);

		$invite_hongbao=floatval($set['xrhb']);	

		$commission_reg=floatval($set['commission_reg']);

		$data['loginname']=$phone;

		$data['phone']=$phone;

		$data['password']=$password;

		$data['integral']=$jf_reg;

		// $data['commission']=$commission_reg+$invite_hongbao;

		$data['nickname']=$phone;

		$data['head_img']='default.png';

		$data['extend_id']=intval($tgid);

		$data['reg_time']=time();

		$data['login_time']=time();

		//百里
		$blocking_price_endtime = $set['blocking_price_endday'] > 0 ? $set['blocking_price_endday'] : 3;
		$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

		$data["blocking_price"] = $commission_reg+$invite_hongbao;
		$data["blocking_price_endtime"] = $blocking_price_endtime;

		//省市县网点

		$data['dq1']=intval($_POST['dq1']);

		$data['dq2']=intval($_POST['dq2']);

		$data['dq3']=intval($_POST['dq3']);

		$data['dq4']=intval($_POST['dq4']);

		

		if(!empty($_SESSION['wxuser']['openid'])){//微信openid

			$data['wx_openid']=$_SESSION['wxuser']['openid'];	

		}

		$new_user_id=zfun::f_insert("User",$data);

		if(empty($new_user_id))zfun::fecho("领取失败");

		//注册送积分事件

		if($invite_hongbao>0){

			zfun::f_adddetail('您领取了' . $invite_hongbao ."元的红包",$new_user_id,6,0,$invite_hongbao);	

		}

		if($jf_reg>0){

			zfun::f_adddetail('注册送' . $jf_reg . $set['jf_name'],$new_user_id,6,0,$jf_reg);	

		}

		if($commission_reg>0){

			zfun::f_adddetail('注册送' . $commission_reg . '佣金',$new_user_id,6,0,$commission_reg);		

		}

		zfun::fecho("正确",array("news"=>1),1);

	}

	public function getRandStr(){//验证码

		$str = '0123456789'; 

		$randString = '';

		$len = strlen($str)-1; 

		for($i = 0;$i < 6;$i ++){ 

			$num = mt_rand(0, $len);

			$randString .= $str[$num]; 

		} 

		return $randString ;  

	}

	public static function getjdset() {

        $set = zfun::f_getset("jd_app_key,jd_app_secret,jd_unionId,jd_webId,jd_access_token");

        $GLOBALS['appkey'] = $set["jd_app_key"];

        $GLOBALS['appsecret'] = $set["jd_app_secret"];

        $GLOBALS['unionId'] = $set["jd_unionId"];

        $GLOBALS['webId'] = $set["jd_webId"];

        $GLOBALS['access_token'] = $set["jd_access_token"];

    }

	public static function getin($url="",$name=''){

		$url=str_replace("?","&",$url);

		$tmp=explode("&".$name."=",$url);

		$tmp=explode("&",$tmp[1]);

		return $tmp[0];

	}

	//获取省市县

	public function getdq(){

		if(empty($_POST['id'])&&$_POST['type']!=1)zfun::fecho("null",array(),1);

		if(empty($_POST['type']))zfun::fecho("error");

		$type=intval($_POST['type']);

		$dq_arr=array(

			1=>array(

				"database"=>"Province",

				"id"=>"ProvinceID",

				"pid"=>"",

				"name"=>"ProvinceName",

				"dq_type"=>"省",

			),

			2=>array(

				"database"=>"City",

				"id"=>"CityID",

				"pid"=>"ProvinceID",

				"name"=>"CityName",

				"dq_type"=>"市",

			),

			3=>array(

				"database"=>"District",

				"id"=>"DistrictID",

				"pid"=>"CityID",

				"name"=>"DistrictName",

				"dq_type"=>"县",

			),

			4=>array(

				"database"=>"WD",

				"id"=>"WdID",

				"pid"=>"DistrictID",

				"name"=>"WdName",

				"dq_type"=>"网点",

			),

		);

		$where=$dq_arr[$type]['pid']."='".$_POST['id']."'";

		if($type==1)$where=NULL;

		$data=zfun::f_select($dq_arr[$type]['database'],$where);

		

		$arr=array();

		foreach($data as $k=>$v){

			$arr[$k]['id']=$v[$dq_arr[$type]['id']];

			$arr[$k]['name']=$v[$dq_arr[$type]['name']];

		}

		zfun::fecho($dq_arr[$type]['dq_type'],$arr,1);

	}

}

?>
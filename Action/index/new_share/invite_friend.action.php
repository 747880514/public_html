<?php
include_once ROOT_PATH."comm/zfun.php";
include_once ROOT_PATH."Action/index/default/alimama.action.php";
class invite_friendAction extends Action{
	/*新用户页面*/
	public function new_packet(){
		$time=time();
		$goods=zfun::f_row("Goods","is_invite=1 AND start_time<$time AND end_time>$time",NULL,"tg_sort DESC");
		$display="display:-webkit-box";
		if(empty($goods))$display="display:none";
		$str="share_inv_title,CustomUnit,xinren_hongbao,AppLogo,yqzc_tx_onoff,AppDisplayName,fx_detail,fx_middetail,fx_icon,app_sjdl_onoff";
		$str.=",share_inv_color,share_inv_backcolor,share_inv_btnfont,inv_icon1,inv_icon2";
		$set = zfun::f_getset($str);
		if(empty($set['share_inv_color']))$set['share_inv_color']='cd5a18';
		if(empty($set['share_inv_btnfont']))$set['share_inv_btnfont']='立即注册';
		if(empty($set['share_inv_backcolor']))$set['share_inv_backcolor']='eb3f31';
		
		$titles=$set['share_inv_title'];
		if(empty($set['share_inv_title']))$titles="您还有一个红包待领取";
		
		if(!empty($set['fx_icon']))$set['fx_icon']=UPLOAD_URL."slide/".$set['fx_icon'];
		if(!empty($set['inv_icon1']))$set['inv_icon1']=UPLOAD_URL."slide/".$set['inv_icon1'];
		else $set['inv_icon1']=UPLOAD_URL."slide/paper_bj.png";
		if(!empty($set['inv_icon2']))$set['inv_icon2']=UPLOAD_URL."slide/".$set['inv_icon2'];
		else $set['inv_icon2']=UPLOAD_URL."slide/paper_pic.png";
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
		
		$display_img="";
		if(!empty($set['yqzc_tx_onoff']))$display_img="on";
		$this->assign("titles",$titles);
		$this->assign("t",time());
		$this->assign("display_img", $display_img);
		$this->assign("help",$help);
		$this->assign("goods",$goods);
		$this->assign("set",$set);
		$this->assign("display",$display);
		$this->assign("tggoods",$tggoods);
		$this->assign("app_sjdl_onoff",$set['app_sjdl_onoff']);
		$this->display("invite_friend","new_packet","wap");
		$this->play();
	}
	/*商品页面*/
	public function goods_detail(){
		$id=filter_check($_GET['id']);
		$goods=zfun::f_row("Goods","fnuo_id='$id'");
		if(empty($goods)){
			$goods1=alimamaAction::getcommission($id);
			$goods['goods_title']=$goods1['title'];
			$goods['fnuo_id']=$id;
			$goods['goods_price']=$goods1['zkPrice'];
			$goods['goods_img']="https:".$goods1['pictUrl']."_300x300.jpg";
			$goods['goods_img_min']="https:".$goods1['pictUrl']."_300x300.jpg";
			$goods['commission']=floatval($goods1['tkRate']);
			$goods['yhq_span']=$goods1['couponInfo'];
			$goods['yhq_price']=floatval($goods1['couponAmount']);
		}
		$tgid = ($_GET['tgid']);
		$tguser=zfun::f_row("User","tg_code='".$tgid."'");
		if(empty($tguser)){
			$Decodekey = $this -> getApp('Tgidkey');
			$tgid = $Decodekey -> Decodekey($tgid);
			$tguser=zfun::f_row("User","id='$tgid'");
		}else $tgid = ($tguser['id']);
		
		$tg_pid=$tguser['tg_pid'];
		
		$set=zfun::f_getset("taobaopid");
		//fpre($set);exit;
		if(!empty($tg_pid)){
			$tmp=explode("_",$set['taobaopid']);
			$GLOBALS['taobaopid']=$set['taobaopid']=$tg_pid=$tmp[0]."_".$tmp[1]."_".$tmp[2]."_".$tg_pid;
		}
		zfun::isoff($tguser,1);
		$_POST['yhq']=1;
		$goods=zfun::f_fgoodscommission(array($goods));$goods=reset($goods);
		
		//life is a shipwreck
		if(!empty($tg_pid)){
			$goods['fnuo_url'].="&pid=".$GLOBALS['taobaopid'];
				
		}
		
		$display="display:block";$display1="display:none";
		if(intval($goods['yhq_price'])<=0){
			$display="display:none";$display1="display:block";
		}
		$display3="display:block";$display2="display:none";
		if($tguser['is_sqdl']>=1){
			$display3="display:none";$display2="display:block";
		}
		$arr=explode(",",$goods['goods_img_min']);
		foreach($arr as $k=>$v){
			if(empty($v))continue;
			if(strstr($v,"http")==false)$arr[$k]=UPLOAD_URL.$v;
		}
		
		if(!empty($goods['yhq_url'])&&strstr($goods['yhq_url'],"uland.taobao.com")==false){
			$goods['yhq_url']="https://uland.taobao.com/coupon/edetail?activityId=".self::getin($goods['yhq_url'],"activityId")."&itemId=".$goods['fnuo_id']."&pid=".$set['taobaopid']."&nowake=1";
		}
		
		if($goods['yhq_price']>0){
			
			actionfun("default/gototaobao");
			if(empty($goods['yhq_url']))$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$goods['goods_title'],"fnuo_id"=>$goods['fnuo_id']),1);
			if(!empty($tmp_yhq_url)){
				$goods['yhq_url']=$tmp_yhq_url;
				
			}
			actionfun("default/tbk_coupon");
			$tmp=tbk_couponAction::getone($goods['goods_title'],$goods['fnuo_id']);
			if(!empty($tmp['url']))$goods['yhq_url']=$tmp['url'];
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
		$this->display("invite_friend","goods_detail","wap");
		$this->play();
	}
	/*老用户页面*/
	public function old_packet(){
		$set=zfun::f_getset("share_get_str4,share_get_str1,share_get_str2,share_get_str3,share_inv_title,xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,AppLogo,android_url,ios_url");
		$set['xrhb']=$set['xinren_hongbao'];
		$titles=$set['share_inv_title'];
		if(empty($set['share_inv_title']))$titles="您还有一个红包待领取";
		$this->assign("titles",$titles);
		if(!empty($set['AppLogo']))$set['AppLogo']=UPLOAD_URL."slide/".$set['AppLogo'];
		else $set['AppLogo']='View/index/img/wap/invite_friend/old_packet/packet_logo.png';
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
			$set['url']=$set['ios_url'];
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
			$set['url']=$set['android_url'];
		}else{
			$set['url']=$set['android_url'];
		}
		$this->assign("set",$set);
		$this->display("invite_friend","old_packet","wap");
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
		include_once ROOT_PATH."Action/index/appapi/tkl.action.php";
		$tkl=tkl::gettkl($goods);
		return $tkl;
	}
	/*超级问卷*/
	public function questionnaire(){
		$set=zfun::f_getset("xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,AppLogo");
		$set['xrhb']=$set['xinren_hongbao'];
		$this->assign("set",$set);
		$this->display("invite_friend","questionnaire","wap");
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
		$set=zfun::f_getset("rmb_ico,share_get_str5,share_inv_title,xinren_hongbao,AppDisplayName,fx_detail,fx_middetail,AppLogo");
		$set['xrhb']=$set['xinren_hongbao'];
		$set['share_get_str5']=str_replace("[APP]",$set['AppDisplayName'],$set['share_get_str5']);
		$set['rmb_ico']=UPLOAD_URL."geticos/".$set['rmb_ico'];
		$titles=$set['share_inv_title'];
		if(empty($set['share_inv_title']))$titles="您还有一个红包待领取";
		$this->assign("titles",$titles);
		if(!empty($set['AppLogo']))$set['AppLogo']=UPLOAD_URL."slide/".$set['AppLogo'];
		else $set['AppLogo']='View/index/img/wap/invite_friend/old_packet/packet_logo.png';
		$this->assign("set",$set);
		$this->assign("goods",$goods);
		$this->display("invite_friend","succeed","wap");
		$this->play();
	}
	public function wapyzm1(){//手机注册验证码
		$set1=zfun::f_getset("app_invite_alipay_yzmstr,yqzc_tx_onoff");
		if(empty($set1['yqzc_tx_onoff'])){
			if (empty($_POST['tx_code'])){zfun::fecho("请输入图形验证码!");}
			if(empty($_SESSION['captcha'])){zfun::fecho("请输入图形验证码!");}
			if($_SESSION['captcha']!=md5(strtoupper($_POST['tx_code']).'')){zfun::fecho("图形验证码错误!");}
			unset($_SESSION['captcha']);
		}
		if(empty($_POST['code']))return false;
		zfun::add_f("wapyzm1");
		$phone=($_POST['code']);
		$user = zfun::f_count("User", "phone='$phone'");
		if($user>0)zfun::fecho("您已经是老用户",2,0);
		
		$yzm=$this->getRandStr();
		$set=zfun::f_getset("dxappname");
		$msgstr='【'.$set['dxappname'].'】'.'验证码：'.$yzm;
		if(!empty($set1['app_invite_alipay_yzmstr']))$msgstr=str_replace("{code}",$yzm,'【'.$set['dxappname'].'】'.$set1['app_invite_alipay_yzmstr']);

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
			$tgid = ($_POST['tgid']);
			$tmp=zfun::f_row("User","tg_code='".$tgid."'");
			if(empty($tmp)){
				$Decodekey = $this -> getApp('Tgidkey');
				$tgid = $Decodekey -> Decodekey($tgid);
				$tmp=zfun::f_count("User","id='$tgid'");
			}else $tgid =$tmp['id'];
			
			if(empty($tmp))zfun::fecho("推荐人不存在");
			$sett=zfun::f_getset("lhb_num_tg");
			//if(empty($sett['lhb_num_tg']))$sett['lhb_num_tg']=1;
			if(!empty($sett['lhb_num_tg'])){//领红包资格
				zfun::addval("User","id='$tgid'",array("l_num"=>$sett['lhb_num_tg']));
				$str="恭喜你获得".$sett['lhb_num_tg']."个抢红包资格";
				zfun::f_adddetail($str,$tgid,66,2,$sett['lhb_num_tg']);
			}
		}
		$set=zfun::f_getset("jf_reg,jf_name,extendreg,xinren_hongbao,blocking_price_endday");	//百里。追加blocking_price_endday
		$set['xrhb']=$set['xinren_hongbao'];
		//$extendreg=intval($set["extendreg"]);
		//if($extendreg&&empty($tgid))zfun::alert("推荐人ID必填");
		$jf_reg=floatval($set['jf_reg']);
		$invite_hongbao=floatval($set['xrhb']);	
		$data['loginname']=$phone;
		$data['phone']=$phone;
		$data['password']=$password;
		$data['integral']=$jf_reg;
		// $data['commission']=$invite_hongbao;
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
		$new_user_id=$uid=zfun::f_insert("User",$data);
		
		//绑定运营商id
		actionfun("comm/register");
		register::set_operator_id($uid);
		
		if(empty($new_user_id))zfun::fecho("领取失败");
		$path=ROOT_PATH."comm/cwdl_rule.php";
		//这是要把累积的金额计算处理,然后升级
		if(file_exists($path)==true){
			include_once $path;
			cwdl_rule::zdsj_doing($tgid);
			
		}
		//注册送积分事件
		if($invite_hongbao>0){
			zfun::f_adddetail('您领取了' . $invite_hongbao ."元的红包",$new_user_id,6,0,$invite_hongbao);	
		}
		if($jf_reg>0){
			zfun::f_adddetail('注册送' . $jf_reg . $set['jf_name'],$new_user_id,6,0,$jf_reg);	
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
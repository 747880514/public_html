<?php
include_once ROOT_PATH."comm/zfun.php";
include_once ROOT_PATH."Action/index/appapi/dgappcomm.php";
include_once ROOT_PATH."Action/index/default/alimama.action.php";
actionfun("comm/tbmaterial");
actionfun("comm/order");
actionfun("appapi/commGetMore");
class agencyAction extends Action{
	/*申请页面*/
	public function index(){
		appcomm::signcheck();
		$set=zfun::f_getset("dl_bjt,dl_hy_xz,dl_zdjs,dl_hy_title,AppDisplayName");
		 $fxdl_lv = intval(self::getSetting("fxdl_lv"));
	 	 for ($i = 2; $i <= $fxdl_lv; $i++) {
		  $fxdldata[$i]['id']=$i;
		  $fxdldata[$i]['title']=self::getSetting("fxdl_name" . $i);
          $money = self::getSetting("fxdl_money" . $i);
		  if(empty($money))$money="免费";
		  else $money.="元";
		   $fxdldata[$i]['title']=$money."升级".$fxdldata[$i]['title'];
		 }
		$fxdldata=array_values($fxdldata);
		$set['dl_list']=array();
		$set['dl_hy_xz']=filter_check($set['dl_hy_xz']);
		$qian=array(" ","　","\t","\n","\r");  
  		$set['dl_hy_xz']=str_replace($qian, '', $set['dl_hy_xz']);    
		if(!empty($fxdldata))$set['dl_list']=$fxdldata;
		if(empty($set['dl_hy_title']))$set['dl_hy_title']="会员需知 MEMBERS NEED";
		if(empty($set['dl_hy_xz']))$set['dl_hy_xz']="凡是入驻".$set['AppDisplayName']."平台，代理技术服务费概不退，还请认真考虑后加入！";
		if(!empty($set['dl_bjt']))$set['dl_bjt']=UPLOAD_URL."slide/".$set['dl_bjt'];
		if(!empty($set['dl_bjt']))$set['dl_zdjs']=UPLOAD_URL."slide/".$set['dl_zdjs'];
		$set['zhushi']="注：若升级到更高等级需缴纳相应的费用";
		unset($set['AppDisplayName']);
		zfun::fecho("申请页面",$set,1);
	}
	
	/*海报*/
	public function ssAnnual(){
		appcomm::signcheck();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");$uid=$user['id'];
			if(empty($user))zfun::fecho("用户不存在");
		}
		$data=array();
		$model=zfun::f_select("DgAppModel","hide=0","id,img_max,content_first,is_check",0,0,"sort DESC");
		$set=zfun::f_getset("AppDisplayName");
		foreach($model as $k=>$v){
			if(empty($v))continue;
			if(empty($v['content_first']))$v['content_first']='扫一扫注册下载'.$set['AppDisplayName'];
			$url=INDEX_WEB_URL."?mod=new_share&act=agency&ctrl=getcode&token=".$_POST['token']."&id=".$v['id']."&time=".time();
			$model[$k]['image']=$url;
			unset($model[$k]['content_first']);unset($model[$k]['img_max']);
			
		}
		zfun::fecho("海报",$model,1);
	}
	public function getcode(){
		$id=intval($_GET['id']);
		$token=filter_check($_GET['token']);
		$set=zfun::f_getset("AppDisplayName");
		$user=zfun::f_row("User","token='$token'");$uid=$user['id'];
		$model=zfun::f_row("DgAppModel","id='$id'");
		if(empty($model['content_first']))$model['content_first']='扫一扫注册下载'.$set['AppDisplayName'];
		self::qrcode2($model,$user,1);
	}
	
	public function dl_list(){
		appcomm::signcheck();
		if(empty($_POST['token']))zfun::fecho("请登录");
		$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'","commission,operator_lv,is_sqdl,is_sqdl_time,id,tg_pid,head_img,dlcommission,nickname,phone,operator_lv");$uid=$user['id'];
		$head_img=$user['head_img'];
		if(empty($head_img))$head_img='default.png';
		if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;
		$user['head_img']=$head_img;
		$user['nickname']=self::getSetting("fxdl_name".intval($user['is_sqdl']+1));
		$sett=zfun::f_getset("rmb_ico,operator_name,operator_name_2,CustomUnit");
		$user['tx_url']=$this->getUrl("drawals","withdrawal",array("t"=>6,"token"=>$_POST['token']),"wap");
		$data=commGetMoreAction::getcommSy($user);
		$user['dlcommission']=$user['commission'];
		$user['byyg']=zfun::dian($data['by_yg']);
		$user['syyg']=zfun::dian($data['sy_yg']);
		$user['today_yes']=array(
			0=>array(
				"count"=>intval($data['t_count']),
				"money"=>zfun::dian($data['t_yugu']),/*这是预估的*/
				"hl_money"=>zfun::dian($data['t_yugu']),/*这是预估的*/
			),
			1=>array(
				"count"=>intval($data['y_count']),
				"money"=>zfun::dian($data['y_yugu']),/*这是预估的*/
				"hl_money"=>zfun::dian($data['y_yugu']),/*这是预估的*/
			),
		);
		$user['str1']='提币';
		$user['str2']=$sett['CustomUnit'];
		$user['icon']=UPLOAD_URL."geticos/".$sett['rmb_ico'];
		unset($user['is_sqdl']);
		zfun::fecho("代理页面",$user,1);
	}
	public function dl_order(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$where="(status IN('订单付款','订单结算','订单成功')) and uid='{$uid}'";
		
		//$fi="id,orderId,now_user,fenxiang_returnstatus,share_uid,uid,postage,goodsNum,info,shop_title,goodsId,status,createDate,payDate,status,orderType,goodsInfo,commission,goods_img,return_commision,estimate,payment,returnstatus,choujiang_n,choujiang_sum,choujiang_data,choujiang_money";
		$num=20;
		$order = appcomm::f_goods("Rebate", $where, $fi, 'order_create_time desc', NULL, $num);
		$arr=commGetMoreAction::secondOrder($order,$user);
		
		$sarr=array("创建订单"=>"待付款","订单付款"=>"已付款","订单结算"=>"未到账","订单失效"=>"已失效");
		$buy_user=zfun::f_kdata("User",$arr,"uid","id","id,extend_id,is_sqdl");
		$invite_user=zfun::f_kdata("User",$buy_user,"extend_id","id","id,nickname,tg_pid,is_sqdl");
		foreach($arr as $k=>$v){
			//之前搞错字段了，app读的这个字段
			$arr[$k]['commission']=$v['fcommission'];
			$arr[$k]['status']=$sarr[$v['status']];
			if($v['fenxiang_returnstatus']==1)$arr[$k]['status']="已到账";
			if($v['returnstatus']==1)$arr[$k]['status']="已到账";
			$arr[$k]['tgNickname']=$invite_user[$buy_user[$v['uid']]['extend_id']]['nickname'];
		}
		if(empty($arr))$arr=array();
		zfun::fecho("代理全部订单",$arr,1);
	}
	public function postRechargeSuc(){
		if(empty($_POST['out_trade_no'])||empty($_POST['total_amount'])||$_POST['trade_status']!='TRADE_SUCCESS')
		zfun::fecho("error");
		$order=$_POST['out_trade_no'];
		$money=floatval($_POST['total_amount']);
		if(strstr($order,"DGDL_")==false)zfun::fecho("error");
		$tmp=explode("_",$order);
		$uid=intval($tmp[1]);
		if(empty($uid))zfun::fecho("error");
		$did=intval($tmp[3]);
		$set=zfun::f_getset("fxdl_zdssdl_onoff");
		$data=array("is_pay"=>1);
		if(!empty($set['fxdl_zdssdl_onoff'])){
			$data['checks']=1;
			$data['succ_time']=time();
		}
		$arr=zfun::f_row("DLList","id='$did' and uid='$uid'");
		$result=zfun::f_update("DLList","id='$did' and uid='$uid'",$data);
		if(!empty($set['fxdl_zdssdl_onoff'])&&!empty($result)){
			include_once ROOT_PATH."Action/admin/dg_cwdl_set.action.php";
			dg_cwdl_setAction::zdsh_set($arr,$did,$uid);//条件
		}

		zfun::fecho("ok",1,1);
	}
	public static function rsaSign($data, $private_key) {
		//以下为了初始化私钥，保证在您填写私钥时不管是带格式还是不带格式都可以通过验证。
		$private_key=str_replace(array("-----BEGIN RSA PRIVATE KEY-----","-----END RSA PRIVATE KEY-----","\n"),"",$private_key);
		$private_key="-----BEGIN RSA PRIVATE KEY-----".PHP_EOL .wordwrap($private_key, 64, "\n", true). PHP_EOL."-----END RSA PRIVATE KEY-----";
		$res=openssl_get_privatekey($private_key);
		if($res){openssl_sign($data, $sign,$res);}
		else{echo "您的私钥格式不正确!"."<br/>"."The format of your private_key is incorrect!";exit();}
		openssl_free_key($res);
		$sign = base64_encode($sign);
		return $sign;
	}
	public static function apisign($arr){
		ksort($arr);$str="";
		foreach($arr as $k=>$v)$str.=$k."=".$v."&";
		$str=mb_substr($str,0,-1,"utf-8");
		return self::rsaSign($str,app_alipay_rsa);
	}
	public static function tbsend($method,$arr){
		$set=zfun::f_getset("app_alipay_payment_type,app_alipay_payment_http_type");
		ksort($arr);
		$biz_content=zfun::f_json_encode($arr);
		$arr=array("biz_content"=>$biz_content);
		$arr['version']="1.0";
		$arr['method']=$method;
		$arr['format']="json";
		$arr['app_id']=app_alipay_appid;
		$arr['sign_type']="RSA";
		$arr['timestamp']=date("Y-m-d H:i:s",time());
		$arr['charset']="utf-8";
		$arr['notify_url']="http://".$_SERVER['HTTP_HOST']."/payapi.php";
		if($set['app_alipay_payment_http_type'].''=='1'){
			$arr['notify_url']=str_replace("http://","https://",$arr['notify_url']);
		}
		$arr['sign']=self::apisign($arr);
		//$url="https://openapi.alipay.com/gateway.do?";
		$url='?';
		foreach($arr as $k=>$v)$url.="&".$k."=".urlencode($v);
		$url=str_replace("?&","",$url);
		return $url;
	}
	//explosion
	public static function getc($uid, $tidname = "extend_id", $maxlv = 9) {//获取下级
		if (empty($uid))
			return 0;
		$arr = array();
		$arr[0] = intval($uid);
		$lv = 0;
		$eid = 0;
		$tid = $uid;
		do {
			$lv++;
			$user = zfun::f_select("User", "$tidname IN($tid) and $tidname<>0 and $tidname<>''");
			if (!empty($user)) {
				$tid = "";
				foreach ($user as $k => $v)
					$tid .= "," . $v['id'];
				$tid = substr($tid, 1);
				$arr[$lv] = $tid;
			}
		} while(!empty($user)&&$lv<$maxlv);
		$ids = implode(",", $arr);
		if (empty($ids))
			$ids = -1;
		return $ids;
		/*
		$user = zfun::f_select("User", "$tidname IN($ids) and id<>0");
		$arr = array();
		foreach ($user as $k => $v)
			$arr[$v[$tidname]][$v['id']] = $v;
		return $arr;*/
	}
	//explosion
	public static function getcc($uid, $tidname = "extend_id", $maxlv = 9) {//获取下级
		$maxlv++;
		if (empty($uid))
			return 0;
		$arr = array();
		$arr[0] = intval($uid);
		$lv = 0;
		$eid = 0;
		$tid = $uid;
		do {
			$lv++;
			$user = zfun::f_select("User", "$tidname IN($tid) and $tidname<>0 and $tidname<>''");
			if (!empty($user)) {
				$tid = "";
				foreach ($user as $k => $v)
					$tid .= "," . $v['id'];
				$tid = substr($tid, 1);
				$arr[$lv] = $tid;
			}
		} while(!empty($user)&&$lv<$maxlv);
		unset($arr[0]);
		if(empty($arr))$arr=array();
		//zheli
		$darr=array();
		$uids=-1;
		$cou=0;
		foreach($arr as $k=>$v){
			$tmp=explode(",",$v);
			foreach($tmp as $k1=>$v1){
				$darr[$v1]=$k;
				$uids.=",".$v1;
				$cou++;
			}
		}
		return array("darr"=>$darr,"uids"=>$uids,"count"=>$cou);
		
	}
	public static function qrcode2($arr,$user,$new=0){//生成二维码
		$tgidkey = self::getApp('Tgidkey');
		$tid = $tgidkey -> addkey($user['id']);
		$user['tid'] = $tid;
		if(!empty($user['tg_code']))$user['tid']=$user['tg_code'];
		
		$set=zfun::f_getset("share_host,haibao_share_onoff,android_url");
		$url=self::getUrl('invite_friend', 'new_packet', array('tgid' => $user['tid']),'new_share');
		if($set['haibao_share_onoff']==1)$url = (self::getUrl('down', 'supdownload', array('tgid' => $user['tid']),'appapi'));
		if(!empty($set['share_host'])){
			$url=str_replace(HTTP_HOST,$set['share_host'],$url);
		}
		$url4=$set['android_url'];
		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";
		if($set['haibao_share_onoff']==2)$url=$url4;
		$data = array();
		$data['width']=790;
		$data['height']=1280;
        $data['list'][0] = array(//背景图
            "url" => UPLOAD_URL."model/".$arr['img_max'],
            "x" => 0,
            "y" => 0,
            "width" => 790,
            "height" => 1280,
			"type"=>"png"
        );

         //百里.获取小程序二维码
        // $hs_qrcode = self::get_hs_qrcode($tid);
        // $hs_qrcode = json_decode($hs_qrcode);
        // $hs_qrcode = $hs_qrcode->url;

        //百里.替换域名生成二维码
        $url = "http://".$set['share_host']."/?mod=appapi&act=down&ctrl=get_unionid&tgid=".$tid;

        //百里.邀请码位置调整（不同位数）
        $leftpx = 285 - ( strlen($user['tid']) - 5 ) * 8;

        //百里.修改样式
		$data['list'][1] = array(//二维码
           // "url" => INDEX_WEB_URL."comm/qrcode/?url=".$arr."&size=10&codeKB=2",
		   "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($url)."&size=10&codeKB=2",
		   	// "url" => $hs_qrcode,	//开启此项需要开启获取小程序二维码
            "x" => 291,
            "y" => 1000,
            "width" => 200,
            "height" => 200,
			"type"=>"png"
        );
       	$data['text'][0]=array(
			"size"=>26,
			"x"=>$leftpx,	//285,	//百里
			"y"=>970,
			"width" => 214,
            "height" => 20,
			"val"=>"邀请码".$user['tid'],
			"color"=>0,
		);
		$data['text'][1]=array(
			"size"=>16,
			"x"=>230,
			"y"=>1260,
			"width" => 214,
            "height" => 20,
			"val"=>$arr['content_first'],
			"color"=>0,
		);
		if($new==1){
			fun("pic");
			return pic::getpic($data);
		}
		$data=zfun::arr64_encode($data);
		//zfun::head("jpg");
		$url=INDEX_WEB_URL."comm/pic.php?pic_ctrl=getpic&data=".urlencode($data);
		
	//	echo "<img src='".$url."'>";;
		//exit;
		return $url;
		//fpre($url);exit;
		//echo zfun::get(INDEX_WEB_URL."comm/pic.php?type=getpic&data=".$data);
		
	}

	//百里.获取二维码
	public static function get_hs_qrcode($tgid)
	{
		if($tgid <= 0)
		{
			return '';
		}

	    $postUrl = 'https://www.juhuivip.com/app/index.php?i=2&c=entry&m=ewei_shopv2&do=mobile&r=member.baili_wxapp.get_qr_code';
        $curlPost = array('tgid'=>$tgid);
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        if(!empty($headers))
        {
        	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
	}
}
?>
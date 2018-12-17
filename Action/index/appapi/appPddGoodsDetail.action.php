<?php
actionfun("appapi/dgappcomm");
actionfun("comm/pinduoduo");
actionfun("appapi/newgoods_detail");
actionfun("appapi/appHhr");
actionfun("appapi/goods_fenxiang");
actionfun("appapi/gotopinduoduo");
actionfun("appapi/appJdGoodsDetail");
class appPddGoodsDetailAction extends Action{
	public static function pass_goods(){
		$fnuo_id=filter_check($_POST['fnuo_id']);
		$data=json_decode($_POST['data'],true);
		$data=self::comm_goods($data);
		
		zfun::fecho("传输处理",$data,1);
	}
	public static function comm_goods($data=array()){
		$uid=$GLOBALS['usermsg']['id'];
		$user=$GLOBALS['usermsg'];
		$data['is_store']=0;
		$data['shop_id']=5;
		$data['shop_type']='拼多多';
		$data['pdd']=1;
		$data['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/pdd.png";
		$data['yhq_url']=$data['fnuo_url']=self::getUrl("gotopinduoduo","index",array("gid"=>$data['fnuo_id']),"appapi");
		if(!empty($data['yhq_start_time'])&&!empty($data['yhq_start_time'])){
			$data['yhq_use_time']=date("Y.m.d",$data['yhq_start_time'])."-".date("Y.m.d",$data['yhq_end_time']);
		}
		if(!empty($data['yhq_price']))$data['yhq_span']=$data['yhq_price']."元优惠券";
		newgoods_detailAction::footmark($uid,$data);//足迹
		//轮播图
		$data['imgArr']=array($data['goods_img']);
		if(!empty($data['goods_img_list']))$data['imgArr']=$data['goods_img_list'];//商品图片集
		$data['dpArr']=array();
		$data['xggoodsArr']=array();
		$data['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
		if(!empty($data['yhq_price']))$data['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
		
		//这里是收藏
		$mywhere="goodsid='".$data['fnuo_id']."' AND uid='".intval($uid)."'";
		$my=zfun::f_count("MyLike",$mywhere);
		$data['is_collect']=0;$data['is_mylike']=0;
		if($my>0){$data['is_collect']=1;$data['is_mylike']=1;}
		unset($data['goods_img_list']);
		$data['cate_id']=$data['cid'];	
		$data['quan_bjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/quan_bjimg.png";
		$data['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
		if(!empty($data['yhq_price']))$data['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
		$data['detail_goods_sjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/detail_goods_sjimg.png";
		$data['detail_goods_fxzimg']=INDEX_WEB_URL."View/index/img/appapi/comm/detail_goods_fxzimg.png";
		actionfun("appapi/goods_detail_fanli");
		$data=goods_detail_fanliAction::index($user,$data);
		return $data;
	}
	//详情
	public function pddIndex(){
		appcomm::signcheck();
		$fnuo_id=filter_check($_POST['fnuo_id']);
		if(empty($fnuo_id))zfun::fecho("fnuo_id不能为空");
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".$_POST['token']."'");
			$uid=$user['id'];
			$tgidkey = $this -> getApp('Tgidkey');
			$uid1 = $tgidkey -> addkey($uid);
			if(!empty($user['tg_code']))$uid1=$user['tg_code'];
			$user['tguid']=$uid1;
			$GLOBALS['usermsg']=$user;
		}
		if(!empty($_POST['data']))self::pass_goods();
		$lv=(intval($user['is_sqdl'])+1);
		//调用拼多多获取商品信息
		$data=pinduoduo::id($fnuo_id);
		if(empty($data))zfun::fecho("拼多多详情",$data,1);
		$data=zfun::f_fgoodscommission(array($data));$data=reset($data);
		
		$data['share_url'] = ($this -> getUrl('invite_friend', 'goods_detail', array('tgid' => $uid1,"id"=>$data['fnuo_id']),'new_share'));
		$data['str']='';
		$set=zfun::f_getset("hhrshare_noflstr,goods_detail_str1,goods_detail_str2,app_fanli_onoff,fx_goods_fl,CustomUnit,hhrapitype,fxdl_fxyjbili".$lv.",fxdl_show_fl".$lv);// jj explosion
		$tmpgoods=array($data);
		$tmpgoods=appcomm::goodsfeixiang($tmpgoods);
		$tmpgoods=reset($tmpgoods);
		$fx_commission=$tmpgoods['fx_commission'];
		$data['fx_commission']=$fx_commission;
		if(empty($set['fx_goods_fl'])){
			$data['fxz']="分享成单后即赚 ".$fx_commission."".$set['CustomUnit'];
			$data['str']="购买此商品可存入".$data['fcommission']."".$set['CustomUnit'];
			if(!empty($set['goods_detail_str1'])){
				$data['fxz']=str_replace("[金额]",$fx_commission,$set['goods_detail_str1']);
			}
			
			if(!empty($set['goods_detail_str2'])){
				$data['str']=str_replace("[金额]",$data['fcommission'],$set['goods_detail_str2']);
			}
			if($set["fxdl_show_fl".$lv]==1&&$user["operator_lv"]==0){
				$data['str']="";// jj explosion
				$data['fxz']=$set['hhrshare_noflstr'];// jj explosion
			}
			if($set["fxdl_show_fl".$lv]==2&&$user["operator_lv"]==0){
				$data['str']="";// jj explosion
			}
		}
		if($set['app_fanli_onoff']==0)$data['str']="";
	
		//拼多多商品详情图片
		$data['detailArr']=self::getpddgoodsinfoimg($fnuo_id);
		
		$data=self::comm_goods($data);

		//百里
		//获取当前会员等级比例
		$user_lv = $user['is_sqdl'];
		switch ($user_lv) {
			case '0':
				$hs_bili = 0;
				break;
			case '1':
				$hs_bili = 0.51;
				break;
			case '2':
				$hs_bili = 0.76;
				break;
			case '3':
				$hs_bili = 0.88;
				break;
			default:
				$hs_bili = 0.51;
				break;
		}
		// $data['hs_bili'] = explode("￥", $data['btn_fxz']['bili'])[1];
		// $data['new_hs_bili'] = $data['goods_price'] * ($data['commission']/100) * $hs_bili;
		// $data['new_hs_bili'] = sprintf("%.2f", $data['new_hs_bili']);
		// $data['hs_bili'] = str_replace($data['hs_bili'], $data['new_hs_bili'], $data['btn_fxz']['bili']);
		$data['hs_bili'] = $data['goods_price'] * ($data['commission']/100) * $hs_bili;
		$data['hs_bili'] = sprintf("%.2f", $data['hs_bili']);

		$data['btn_fxz']['bili'] = $data['hs_bili'];
		$data['btn_zgz']['bili'] = $data['hs_bili'];
		$data['img_fxz']['bili'] = $data['hs_bili'];

		//升级奖
		$user_lv = $user['is_sqdl'] + 1;
		$up_hs_bili = 0;
		switch ($user_lv) {
			case '1':
				$up_hs_bili = 0.51;
				break;
			case '2':
				$up_hs_bili = 0.76;
				break;
			case '3':
				$up_hs_bili = 0.88;
				break;
		}
		if($up_hs_bili > 0)
		{
			$data['fcommission'] = $data['goods_price'] * ($data['commission']/100) * $up_hs_bili;
			$data['fcommission'] = sprintf("%.2f", $data['fcommission']);


			$data['up_hs_bili'] = explode("￥", $data['img_sjz']['bili'])[1];
			$data['img_sjz']['bili'] = str_replace($data['up_hs_bili'], $data['fcommission'], $data['img_sjz']['bili']);
		}

		zfun::fecho("拼多多详情",$data,1);
	}
	
	//获取拼多多详情
	static function getpddgoodsinfoimg($gid=''){
		if(empty($gid))zfun::fecho("getpddgoodsinfoimg val empry");
		ob_end_clean();
		
		//读取缓存
		$cookie_name=__FUNCTION__." ".$gid;
		$cookie_arr=array("op"=>1);
		$cookie_data=actfun::read_cookie($cookie_name,$cookie_arr,"dgapp");
		if(!empty($cookie_data))return $cookie_data;
		
		$url="http://mobile.yangkeduo.com/goods.html?goods_id={$gid}";
		$data=curl_get($url);
		$data=trim(actfun::getin($data,'window.rawData= ','</script>'));
		$data=substr($data,0,-1);
		$data=json_decode($data,true);
		if(empty($data))zfun::fecho("拼多多详情图片获取失败");
		$imgarr=array();
		if(!empty($data['goods']['detailGallery'])){
			foreach($data['goods']['detailGallery'] as $k=>$v){
				$imgarr[]="http:".$v['url'];	
			}
		}else if($data['initDataObj']['goods']['detailGallery']){
			foreach($data['initDataObj']['goods']['detailGallery'] as $k=>$v){
				$imgarr[]="http:".$v['url'];	
			}
		}
		
		//写入缓存
		if(!empty($imgarr))actfun::set_cookie($cookie_name,$cookie_arr,$imgarr,"dgapp",86400);
		
		return $imgarr;
	}
	
	//拼多多跳转
	public function pddUrl(){
		appcomm::signcheck();
		$gid=$_POST['fnuo_id'];
		if(empty($gid))zfun::fecho("商品id不存在");
		$uid=0;
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".$_POST['token']."'");
			$uid=$user['id'];
		}
		$set=gotopinduoduoAction::getset();
		$pid='';
		if(empty($uid)&&empty($tgid))$pid=gotopinduoduoAction::zhanzhang_pid($set);//站长的
		if(empty($tgid)||$tgid==$uid)$pid=gotopinduoduoAction::own_pid($uid,$set,$pid);//普通
		if(empty($pid))zfun::fecho("pid生成失败");
		$open_app=intval($set['pdd_open_app']);
		$link_type=intval($set['pdd_link_type']);
		$arr=array(
			"gid"=>$gid,
			"pid"=>$pid,
			"open_app"=>$open_app,
			"link_type"=>$link_type,
		);
		$data=pinduoduo::zmsend("pdd.get_buy_url",$arr);
		
		if(!empty($data['url'])){
			$arr['url']=$data['url'];
			$arr['no_open_url']=$data['url'];
		}
		if($open_app==1){
			$tmp=array(
				"gid"=>$gid,
				"pid"=>$pid,
				"open_app"=>0,
				"link_type"=>$link_type,
			);
		
			$data=pinduoduo::zmsend("pdd.get_buy_url",$arr);
			if(!empty($data['url']))$arr['no_open_url']=$data['url'];
		}
		zfun::fecho("拼多多跳转",$arr,1);
	}
	//多图分享
	public function more_share(){
		appcomm::signcheck();
		$fnuo_id=filter_check($_POST['fnuo_id']);
		if(empty($fnuo_id))zfun::fecho("fnuo_id不能为空");
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".$_POST['token']."'");
			$uid=$user['id'];
			$tid=self::getApp('Tgidkey')->addkey($uid);
			if(!empty($user['tg_code']))$tid=$user['tg_code'];
		}
		//调用拼多多获取商品信息
		$garr=pinduoduo::id($fnuo_id);
		$garr=zfun::f_fgoodscommission(array($garr));
		$garr=appcomm::goodsfeixiang(($garr));
		$garr=appcomm::goodsfanlioff(($garr));
		$garr=reset($garr);
		$data=$garr;
		$set=zfun::f_getset("goods_share_fontcolor,android_url,fxdl_hhrshare_onoff,app_pddgoods_tw_url,tg_durl,is_openbd,app_pddgoods_fenxiang_str1,app_pddgoods_fenxiang_str2");
		//分享赚判断
		goods_fenxiangAction::fenxiang_onoff($set,$user);	
		if(empty($garr['yhq_price']))$con=$set['app_pddgoods_fenxiang_str1'];
		else $con=$set['app_pddgoods_fenxiang_str2'];
		$urls=self::urls($set,$data,$tid);
		$url=$urls['url'];$url1=$urls['url1'];$url2=$urls['url2'];$url3=$urls['url3'];$url4=$urls['url4'];
	
		$con=str_replace("{商品标题}",$garr['goods_title'],$con);
		$con=str_replace("{价格}",$garr['goods_cost_price'],$con);
		$con=str_replace("{券后价}",$garr['goods_price'],$con);
		$con=str_replace("{应用宝下载链接}",$url4,$con);
		$con=str_replace("{下载链接}",$url2,$con);
		$con=str_replace("{商品链接}",$url1,$con);
		$con=str_replace("{邀请注册链接}",$url3,$con);
		$con=str_replace("{邀请码}",$tid,$con);
		$commission=goods_fenxiangAction::get_user_bili($garr);
		$con=str_replace("{自购佣金}",$commission,$con);
		$con=str_replace("{分享人自购佣金}",$garr['fcommission'],$con);
		$data['goods_img']=array($garr['goods_img']);
		if(!empty($garr['goods_img_list']))$data['goods_img']=$garr['goods_img_list'];
		foreach($data['goods_img'] as $k=>$v){
			if($k!=0)continue;
			$data['goods_img'][$k]=INDEX_WEB_URL."?mod=appapi&act=appJdGoodsDetail&img=".urlencode($v)."&ctrl=getcode&pdd=1&fnuo_id=".$fnuo_id."&getGoodsType=".$_POST['getGoodsType']."&token=".$_POST['token'];
		}

		$data['ShareContentType']="goods_img_share";
		$data['str']=$con;
		$data['goods_share_img']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_share_img.png?time=".time();
		$data['goods_share_fontcolor']='FFFFFF';
		
		if(!empty($set['goods_share_fontcolor']))$data['goods_share_fontcolor']=$set['goods_share_fontcolor'];

		//百里
		//获取当前会员等级比例
		$user_lv = $user['is_sqdl'];
		switch ($user_lv) {
			case '0':
				$hs_bili = 0;
				break;
			case '1':
				$hs_bili = 0.51;
				break;
			case '2':
				$hs_bili = 0.76;
				break;
			case '3':
				$hs_bili = 0.88;
				break;
			default:
				$hs_bili = 0.51;
				break;
		}
		$data['old_fx_commission'] = $data['fx_commission'];
		$data['fx_commission'] = sprintf("%.2f", $data['goods_price'] * ($data['commission']/100) * $hs_bili);
		$data['fcommission'] = $data['fx_commission'];
		$data['fxz'] =  str_replace($data['old_fx_commission'], $data['fx_commission'], $data['fxz']);
		
		
		zfun::fecho("多图分享",$data,1,1);
	}
	//分享
	public function share(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$tid=self::getApp('Tgidkey')->addkey($uid);
		if(!empty($user['tg_code']))$tid=$user['tg_code'];
		$pset=appcomm::parametercheck("fnuo_id");
		$fnuo_id=$pset['fnuo_id'];
		//调用拼多多获取商品信息
		$garr=pinduoduo::id($fnuo_id);
		$garr=zfun::f_fgoodscommission(array($garr));
		$garr=appcomm::goodsfeixiang(($garr));
		$garr=appcomm::goodsfanlioff(($garr));
		$garr=reset($garr);
		$data=array("fnuo_id"=>$fnuo_id);
		$set=zfun::f_getset("android_url,fxdl_hhrshare_onoff,app_pddgoods_fenxiang_str3,tg_durl,is_openbd,app_pddgoods_fenxiang_str1,app_pddgoods_fenxiang_type,AppDisplayName,app_pddgoods_fenxiang_str2,taobaopid,webset_webnick,app_pddgoods_tw_url");
		//分享赚判断
		goods_fenxiangAction::fenxiang_onoff($set,$user);	
		$data['str1']=$set['app_pddgoods_fenxiang_str3'];
		if(empty($data['str1'])){
			$data['str1']=' 当小伙伴复制您分享的淘宝淘口令进入淘宝 , 购买了该商品 , 您可获得收益 ! 如小伙伴是您的新用户, TA将成为您的邀请用户 , 小伙伴今后的每一笔订单 , 您将获得收益';
		}
		$urls=self::urls($set,$data,$tid);
		$url=$urls['url'];$url1=$urls['url1'];$url2=$urls['url2'];$url3=$urls['url3'];$url4=$urls['url4'];
		if(empty($garr['yhq_price']))$data['str2']=$set['app_pddgoods_fenxiang_str1'];
		else $data['str2']=$set['app_pddgoods_fenxiang_str2'];
		$data['str2']=str_replace("{商品标题}","【".$garr['goods_title']."】",$data['str2']);
		$data['str2']=str_replace("{应用宝下载链接}",$url4,$data['str2']);
		$data['str2']=str_replace("{下载链接}",$url2,$data['str2']);
		$data['str2']=str_replace("{商品链接}",$url1,$data['str2']);
		$data['str2']=str_replace("{邀请注册链接}",$url3,$data['str2']);
		$data['str2']=str_replace("{券后价}",$garr['goods_price'],$data['str2']);
		$data['str2']=str_replace("{价格}",$garr['goods_cost_price'],$data['str2']);
		$data['str2']=str_replace("{邀请码}",$tid,$data['str2']);
		$commission=goods_fenxiangAction::get_user_bili($garr);
		$data['str2']=str_replace("{自购佣金}",$commission,$data['str2']);
		$data['str2']=str_replace("{分享人自购佣金}",$garr['fcommission'],$data['str2']);
		$data['title1']=$garr['goods_title'];
		$data['goods_img']=$garr['goods_img'];
		$data['title2']=$set['webset_webnick'];
		$data['ShareContentType']="goods_img_share";
		$data['url']=$url;
		$url1=self::getUrl("gotopinduoduo","index",array("gid"=>$fnuo_id,"tgid"=>$tid),"appapi");

		$data['share_img']=INDEX_WEB_URL."?mod=appapi&act=appJdGoodsDetail&ctrl=getcode&pdd=1&fnuo_id=".$fnuo_id."&getGoodsType=".$_POST['getGoodsType']."&token=".$_POST['token'];
		zfun::fecho("分享",$data,1,1);
	}
	
	//链接
	public static function urls($set=array(),$data=array(),$tid=0){
		$url4=$set['android_url'];
		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";
		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$data['fnuo_id'],'tgid' => $tid),'new_share');
		$url2=self::getUrl("down","supdownload",array('tgid' => $tid),"appapi");/*更换*/
		$url1=self::getUrl("gotopinduoduo","index",array("gid"=>$data['fnuo_id'],"tgid"=>$tid),"appapi");
		$tg_url=appJdGoodsDetailAction::get_pdd_buy_url($data,$tid);
		if(!empty($tg_url))$url1=$tg_url;
		if(intval($set['tg_durl'])==1){
			//$url3=INDEX_WEB_URL."new_share-".$tid."-".$data['fnuo_id']."-1.html";
			if(!empty($set['is_openbd']))$bd1="http://fanyi.baidu.com/transpage?query=".urlencode($url1)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				
				$bd1=$url1;
			}
			if(!empty($set['is_openbd']))$bd2="http://fanyi.baidu.com/transpage?query=".urlencode($url2)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				$bd2=$url2;
			}
			if(!empty($set['is_openbd']))$bd3="http://fanyi.baidu.com/transpage?query=".urlencode($url3)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				$bd3=$url3;
			}
			
			$arrulr=appHhrAction::bdurl($bd1,$bd2,$bd3);
			$url1=$arrulr[0];
			$url2=$arrulr[1];
			$url3=$arrulr[2];
		}
		$url=$url1;
		
		if(intval($set['app_pddgoods_tw_url'])==1){
			$url=$url2;
		}
		if(intval($set['app_pddgoods_tw_url'])==2){
			$url=$url3;
		}
		if(intval($set['app_pddgoods_tw_url'])==3){
			$url=$url4;
		}
		$arr=array("url"=>$url,"url1"=>$url1,"url2"=>$url2,"url3"=>$url3,"url4"=>$url4);
		return $arr;
	}
	
}
?>

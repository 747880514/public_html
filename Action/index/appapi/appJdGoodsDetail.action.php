<?php
actionfun("appapi/dgappcomm");
actionfun("comm/jingdong");
actionfun("comm/pinduoduo");
actionfun("appapi/newgoods_detail");
actionfun("appapi/appHhr");
actionfun("appapi/goods_fenxiang");
actionfun("comm/zmCouponJingdong");
class appJdGoodsDetailAction extends Action{
	//详情
	public function jdIndex(){
		appcomm::signcheck();
		//appcomm::read_app_cookie();
		$fnuo_id=filter_check($_POST['fnuo_id']);
		if(empty($fnuo_id))zfun::fecho("fnuo_id不能为空");
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".$_POST['token']."'");
			$uid=$user['id'];
			$tgidkey = $this -> getApp('Tgidkey');
			$uid1 = $tgidkey -> addkey($uid);
			if(!empty($user['tg_code']))$uid1=$user['tg_code'];
		}
		$lv=(intval($user['is_sqdl'])+1);
		//调用京东获取商品信息
		$data=jingdong::id($fnuo_id);
		$yhqs=zmCouponJingdong::id($fnuo_id);//京东优惠券商品接口
		if(empty($data['id']))$data=$yhqs;
		if(($data['id'])==''){//京推推的商品详情
			$data=self::getJttGoods($fnuo_id);
		}
		if(!empty($yhqs)){
			$data['yhq']=1;
			$data['yhq_price']=floatval($yhqs['yhq_price']);
			$data['yhq_span']=$yhqs['yhq_span'];
			$data['yhq_url']=$yhqs['yhq_url'];

		}
		if(empty($data['yhq_price'])){
			$arr=self::getYhqUrl($data);$arr['discount']=floatval($arr['discount']);
			if(!empty($arr['discount'])){
				$data['yhq_price']=$arr['discount'];
				$data['yhq']=1;
				$data['yhq_price']=$arr['discount'];
				$data['yhq_span']=$arr['discount']."元";
				
			}
		}
		$data=zfun::f_fgoodscommission(array($data));
	
		$data=reset($data);
		$data['is_store']=0;
		$data['shop_id']=3;
		$data['shop_type']='京东';
		$data['jd']=1;
		$data['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/jd.png?time=".time();
		if(intval($data['isJdSale'])==1)$data['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/jd1.png?time=".time();

		//$data['yhq_url']=$data['fnuo_url']=self::getUrl("gotojingdong","index",array("gid"=>$data['fnuo_id']),"appapi");
		$data['yhq_url']=$data['fnuo_url']=INDEX_WEB_URL."?mod=appapi&act=gotojingdong&gid=".$fnuo_id."&yhq_url=".urlencode($_POST['yhq_url']);

		if(!empty($data['yhq_start_time'])&&!empty($data['yhq_start_time'])){
			$data['yhq_use_time']=date("Y.m.d",$data['yhq_start_time'])."-".date("Y.m.d",$data['yhq_end_time']);
		}
		
		newgoods_detailAction::footmark($uid,$goods);//足迹
		$data['share_url'] = ($this -> getUrl('invite_friend', 'goods_detail', array('tgid' => $uid1,"id"=>$data['fnuo_id']),'new_share'));
		$data['str']='';
		$set=zfun::f_getset("hhrshare_noflstr,goods_detail_str1,goods_detail_str2,jd_open_app,app_fanli_onoff,fx_goods_fl,CustomUnit,hhrapitype,fxdl_fxyjbili".$lv.",fxdl_show_fl".$lv);// jj explosion
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
		$data['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png?time=".time();
		if(!empty($data['yhq_price']))$data['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png?time=".time();
		//轮播图
		$data['imgArr']=array($data['goods_img']);
		$data['dpArr']=array();
		
		$data['xggoodsArr']=array();
		
		
		//这里是收藏
		$mywhere="goodsid='".$data['fnuo_id']."' AND uid='".intval($uid)."'";
		$my=zfun::f_count("MyLike",$mywhere);
		$data['is_collect']=0;$data['is_mylike']=0;
		if($my>0){$data['is_collect']=1;$data['is_mylike']=1;}
		//判断要不要调起京东
		$data['is_dqjd']=intval($set['jd_open_app']);
		//详情图片
		$data['detailArr']=self::getjdgoodsinfoimg($fnuo_id);
		$data['cate_id']=$data['cid2'];	
		$data['quan_bjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/quan_bjimg.png?time=".time();

		$data=newgoods_detailAction::getfanli($data,$user);
		
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

	//	appcomm::set_app_cookie($data);
		zfun::fecho("京东详情",$data,1);
	}
	//获取京东的优惠券
	static function getYhqUrl($data=array()){
		$url=filter_check($_POST['yhq_url']);
		$arr=array();
		if(empty($url))$url=filter_check($_GET['yhq_url']);
		if(empty($url))return;
		actionfun("comm/jtt_goods");
		$name='jtt_getYhqUrl';
		$tmp=array("url"=>$url);
		$send_arr=array("fnuo_id",$data['fnuo_id']);
		$set=zfun::f_getset("jtt_appid,jtt_appkey");
		if(empty($set['jtt_appid']))return $arr;
		if(empty($set['jtt_appkey']))return $arr;
		$cookie_data=actfun::read_cookie($name,$send_arr,"dgapp");
		if(!empty($cookie_data))$arr=$cookie_data;
		else{
			$url='http://japi.jingtuitui.com/api/get_coupom_info';
			$data=jtt_goods::send($url,$tmp);
			$arr=$data['result'];
			actfun::set_cookie($name,$send_arr,$arr,"dgapp",86400);
		}
		return $arr;
	}
	//获取京推推的商品
	static function getJttGoods($fnuo_id=''){
		$arr=array();
		if(empty($fnuo_id))return;
		actionfun("comm/jtt_goods");
		$name='getJttGoods';
		$tmp=array("gid"=>$fnuo_id);
		$send_arr=array("fnuo_id",$fnuo_id);
		$set=zfun::f_getset("jtt_appid,jtt_appkey");
		if(empty($set['jtt_appid']))return $arr;
		if(empty($set['jtt_appkey']))return $arr;
		$cookie_data=actfun::read_cookie($name,$send_arr,"dgapp");
		if(!empty($cookie_data))$arr=$cookie_data;
		else{
			$url='http://japi.jingtuitui.com/api/get_goods_info';
			$data=jtt_goods::send($url,$tmp);
			
			$arr=$data['result'];
			$arr=self::update_goods(array($arr));
			
			$arr=reset($arr);
			
			actfun::set_cookie($name,$send_arr,$arr,"dgapp",86400);
		}
		return $arr;
	}
	static function update_goods($data=array()){
		if(empty($data))return array();
		$goods=array();
		foreach($data as $k=>$v){
			$g_arr=array(
				"fnuo_id"=>$v['skuId'],
				"goods_title"=>addslashes($v['goodsName']),
				"goods_price"=>doubleval($v['wlUnitPrice']),
				"goods_cost_price"=>doubleval($v['wlUnitPrice']),
				"goods_img"=>$v['imgUrl'],
				"commission"=>doubleval($v['commisionRatioWl']),
				"yhq"=>0,
				"yhq_span"=>"",
				"yhq_price"=>0,
				"yhq_url"=>"",
				"jd"=>1,
			);
			$g_arr['shop_id']=4;
			
			$goods[]=$g_arr;
		}
		return $goods;
	}
	//获取京东详情图片
	static function getjdgoodsinfoimg($gid=""){
		if(empty($gid))zfun::fecho("getjdgoodsinfoimg val empry");
		
		//读取缓存
		$cookie_name=__FUNCTION__." ".$gid;
		$cookie_arr=array("op"=>1);
		$cookie_data=actfun::read_cookie($cookie_name,$cookie_arr,"dgapp");
		if(!empty($cookie_data))return $cookie_data;
		
		$url="https://wqsitem.jd.com/detail/{$gid}_d{$gid}_normal.html";
		
		$data=curl_get($url);
		ob_end_clean();
		$data=trim(iconv("gbk","utf-8",$data));
		if(strstr($data,'({"content":"')==false)zfun::fecho("京东详情获取失败");
		$data=actfun::getin($data,'({"content":"',':"1"})');
		$data='{"content":"'.$data.':"1"}';
		$data=json_decode($data,true);
		if(empty($data))zfun::fecho("京东详情获取失败2");
		$data=$data['content'];
		
		$imgarr=array();
		//格式1
		if(strstr($data,'src="')){
			$data=explode('src="',$data);unset($data[0]);
			foreach($data as $k=>$v){
				$url=actfun::getin("``".$v,"``","\"");
				//给没有http的加上http
				if(strstr($url,'http')==false)$url="http:".$url;
				$imgarr[]=$url;
			}
		}
		elseif(strstr($data,' background-image:url(')){//格式2
			$data=explode('background-image:url(',$data);unset($data[0]);
			foreach($data as $k=>$v){
				$url=actfun::getin("``".$v,"``",")");
				//给没有http的加上http
				if(strstr($url,'http')==false)$url="https:".$url;
				$imgarr[]=$url;
			}	
		}
		else{
			//die("1");
			//fpre($data);	
		}
		
		
		
		//写入缓存
		if(!empty($imgarr))actfun::set_cookie($cookie_name,$cookie_arr,$imgarr,"dgapp",86400);
		return $imgarr;

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
		//调用京东获取商品信息
		$garr=jingdong::id($fnuo_id);
		$yhqs=zmCouponJingdong::id($fnuo_id);//京东优惠券商品接口
		if(empty($garr['id']))$garr=$yhqs;
		if(!empty($yhqs)){
			$garr['yhq']=1;
			$garr['yhq_price']=floatval($yhqs['yhq_price']);
			$garr['yhq_span']=$yhqs['yhq_span'];
			$garr['yhq_url']=$yhqs['yhq_url'];
		}
		$garr['yhq_price']=floatval($garr['yhq_price']);
		if(empty($garr['yhq_price'])){
			$arr=self::getYhqUrl($garr);$arr['discount']=floatval($arr['discount']);
			if(!empty($arr['discount'])){
				$garr['yhq_price']=$arr['discount'];
				$garr['yhq']=1;
				$garr['yhq_price']=$arr['discount'];
				$garr['yhq_span']=$arr['discount']."元";
				
			}
		}
		$garr=zfun::f_fgoodscommission(array($garr));	
		$garr=appcomm::goodsfeixiang(($garr));
		$garr=appcomm::goodsfanlioff(($garr));
		$garr=reset($garr);
		$garr['yhq_price']=floatval($garr['yhq_price']);
		$data=$garr;
		
		$set=zfun::f_getset("goods_share_fontcolor,android_url,fxdl_hhrshare_onoff,app_jdgoods_tw_url,tg_durl,is_openbd,app_jdgoods_fenxiang_str1,app_jdgoods_fenxiang_str2");
		//分享赚判断
		goods_fenxiangAction::fenxiang_onoff($set,$user);	
		if(empty($garr['yhq_price']))$con=$set['app_jdgoods_fenxiang_str1'];
		else $con=$set['app_jdgoods_fenxiang_str2'];
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
		$data['goods_img']=array($garr['goods_img']);
		if(empty($garr['goods_img']))$data['goods_img']=array();
		foreach($data['goods_img'] as $k=>$v){
			$data['goods_img'][$k]=INDEX_WEB_URL."?mod=appapi&act=appJdGoodsDetail&ctrl=getcode&jd=1&yhq_url=".urlencode($_POST['yhq_url'])."&img=".urlencode($v)."&fnuo_id=".$fnuo_id."&getGoodsType=".$_POST['getGoodsType']."&token=".$_POST['token'];
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
		//调用京东获取商品信息
		$garr=jingdong::id($fnuo_id);
		$yhqs=zmCouponJingdong::id($fnuo_id);//京东优惠券商品接口
		if(empty($garr['id']))$garr=$yhqs;
		if(!empty($yhqs)){
			$garr['yhq']=1;
			$garr['yhq_price']=floatval($yhqs['yhq_price']);
			$garr['yhq_span']=$yhqs['yhq_span'];
			$garr['yhq_url']=$yhqs['yhq_url'];

		}
		$garr['yhq_price']=floatval($garr['yhq_price']);
		if(empty($garr['yhq_price'])){
			$arr=self::getYhqUrl($garr);$arr['discount']=floatval($arr['discount']);
			if(!empty($arr['discount'])){
				$garr['yhq_price']=$arr['discount'];
				$garr['yhq']=1;
				$garr['yhq_price']=$arr['discount'];
				$garr['yhq_span']=$arr['discount']."元";
				
			}
		}
		$garr=zfun::f_fgoodscommission(array($garr));
		$garr=appcomm::goodsfeixiang(($garr));
		$garr=appcomm::goodsfanlioff(($garr));
		$garr=reset($garr);
		$data=array("fnuo_id"=>$fnuo_id);
		$set=zfun::f_getset("android_url,fxdl_hhrshare_onoff,app_jdgoods_fenxiang_str3,tg_durl,is_openbd,app_jdgoods_fenxiang_str1,app_jdgoods_fenxiang_type,AppDisplayName,app_jdgoods_fenxiang_str2,taobaopid,webset_webnick,app_jdgoods_tw_url");
		//分享赚判断
		goods_fenxiangAction::fenxiang_onoff($set,$user);	
		
		
		$data['str1']=$set['app_jdgoods_fenxiang_str3'];
		if(empty($data['str1'])){
			$data['str1']=' 当小伙伴复制您分享的淘宝淘口令进入淘宝 , 购买了该商品 , 您可获得收益 ! 如小伙伴是您的新用户, TA将成为您的邀请用户 , 小伙伴今后的每一笔订单 , 您将获得收益';
		}
		$urls=self::urls($set,$data,$tid);
		$url=$urls['url'];$url1=$urls['url1'];$url2=$urls['url2'];$url3=$urls['url3'];$url4=$urls['url4'];
		$garr['yhq_price']=floatval($garr['yhq_price']);
		$data['str2']=$set['app_jdgoods_fenxiang_str2'];
		if(empty($garr['yhq_price']))$data['str2']=$set['app_jdgoods_fenxiang_str1'];
		
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
		$data['title1']=$garr['goods_title'];
		$data['goods_img']=$garr['goods_img'];
		$data['title2']=$set['webset_webnick'];
		$data['ShareContentType']="goods_img_share";
		$data['url']=$url;
		//$url1=self::getUrl("gotojingdong","index",array("gid"=>$fnuo_id,"tgid"=>$tid),"appapi");
		$url1=INDEX_WEB_URL."?mod=appapi&act=gotojingdong&tgid=".$tid."&gid=".$fnuo_id."&yhq_url=".urlencode($_POST['yhq_url']);

		$data['share_img']=INDEX_WEB_URL."?mod=appapi&act=appJdGoodsDetail&ctrl=getcode&jd=1&yhq_url=".urlencode($_POST['yhq_url'])."&fnuo_id=".$fnuo_id."&getGoodsType=".$_POST['getGoodsType']."&token=".$_POST['token'];

		
		zfun::fecho("分享",$data,1,1);
	}
	public function getcode(){
		$fnuo_id=$_GET['fnuo_id'];
		$token=filter_check($_GET['token']);
		$user=zfun::f_row("User","token='$token'");
		$uid=$user['id'];
		$tid=self::getApp('Tgidkey')->addkey($uid);
		if(!empty($user['tg_code']))$tid=$user['tg_code'];
		//调用京东获取商品信息
		if($_GET['jd']==1){
			$garr=jingdong::id($fnuo_id);
			$yhqs=zmCouponJingdong::id($fnuo_id);//京东优惠券商品接口
			if(empty($garr['id']))$garr=$yhqs;
			if(!empty($yhqs)){
				$garr['yhq']=1;
				$garr['yhq_price']=floatval($yhqs['yhq_price']);
				$garr['yhq_span']=$yhqs['yhq_span'];
				$garr['yhq_url']=$yhqs['yhq_url'];
	
			}
			$garr['yhq_price']=floatval($garr['yhq_price']);
			
			if(empty($garr['yhq_price'])){
				$arr=self::getYhqUrl($garr);$arr['discount']=floatval($arr['discount']);
				
				if(!empty($arr['discount'])){
					$garr['yhq_price']=$arr['discount'];
					$garr['yhq']=1;
					$garr['yhq_price']=$arr['discount'];
					$garr['yhq_span']=$arr['discount']."元";
					
				}
			}
			//$url1=self::getUrl("gotojingdong","index",array("gid"=>$fnuo_id,"tgid"=>$tid),"appapi");
			$url1=INDEX_WEB_URL."?mod=appapi&act=gotojingdong&tgid=".$tid."&gid=".$fnuo_id."&yhq_url=".urlencode($_GET['yhq_url']);
			$tg_url=self::get_buy_url($garr,$tid);
			if(!empty($tg_url))$url1=$tg_url;
		}else{
			 $garr=pinduoduo::id($fnuo_id);
			
			 $url1=self::getUrl("gotopinduoduo","index",array("gid"=>$fnuo_id,"tgid"=>$tid),"appapi");
			
			 $tg_url=self::get_pdd_buy_url($garr,$tid);
			 
			 if(!empty($tg_url))$url1=$tg_url;
		}
		$garr=zfun::f_fgoodscommission(array($garr));$garr=reset($garr);
		$set=zfun::f_getset("android_url,fxdl_hhrshare_onoff,app_jdgoods_fenxiang_str3,tg_durl,is_openbd,app_jdgoods_fenxiang_str1,app_jdgoods_fenxiang_type,AppDisplayName,app_jdgoods_fenxiang_str2,taobaopid,webset_webnick,app_jdgoods_tw_url");
		//分享赚判断
		goods_fenxiangAction::fenxiang_onoff($set,$user);	
		if(!empty($_GET['img']))$garr['goods_img']=$_GET['img'];
		self::qrcode2($garr,$user,$url1);
		
	}

	//百里.修改分享图
	public static function qrcode2($arr=array(),$user=array(),$urls=''){//生成二维码
		
		$img=str_replace("https:","http:",$arr['goods_img']);
		if($arr['shop_id']==1)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/taobao_one.png?time=".time();
		if($arr['shop_id']==2)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/tmall_one.png?time=".time();
		if($arr['pdd']==1){$shop_width='95';$shop_height='48';$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/pdd_one.png?time=".time();}
		if($arr['jd']==1)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/jd_one.png?time=".time();

		$tgidkey = self::getApp('Tgidkey');
		$tgid = $tgidkey->addkey($user['id']);
		if(!empty($user['tg_code']))$tgid=$user['tg_code'];
		$getgoodstype=filter_check($_POST['getGoodsType']);//类型 物料 大淘客
		//$tg_url=INDEX_WEB_URL."?mod=wap&act=rebate_DG&ctrl=rebate_detail&getgoodstype=$getgoodstype&tgid=".intval($tgid)."&fnuo_id=".filter_check($arr['fnuo_id']);
		$tg_url=$urls;
		$set=zfun::f_getset("android_url,tg_durl,is_openbd,app_goods_tw_url");
		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$pset['fnuo_id'],'tgid' => $tgid),'new_share');
		$url2=self::getUrl("down","supdownload",array('tgid' => $tgid),"appapi");/*更换*/
		$url4=$set['android_url'];
		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";
		if(intval($set['tg_durl'])==1){
			$url1=$urls;
			if(!empty($set['is_openbd']))$bd="http://fanyi.baidu.com/transpage?query=".urlencode($url1)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				$url1=$urls;
				$bd=$url1;
			}
			//$tg_url=self::bdurl($bd);
			
		//	$url2=INDEX_WEB_URL."down_supdownload_appapi.html";
			
			if(!empty($set['is_openbd']))$bd2="http://fanyi.baidu.com/transpage?query=".urlencode($url2)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				$bd2=$url2;
			}
			//$url2=self::bdurl($bd2);
			
			
			//$url3=INDEX_WEB_URL."new_share-".$tgid."-".$arr['fnuo_id']."-1.html";
			
			if(!empty($set['is_openbd']))$bd3="http://fanyi.baidu.com/transpage?query=".urlencode($url3)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				$bd3=$url3;
			}
			//$url3=self::bdurl($bd3);
			
			$arrulr=appHhrAction::bdurl($bd,$bd2,$bd3);
			
			$tg_url=$arrulr[0];
			$url2=$arrulr[1];
			$url3=$arrulr[2];
		} 
		
		if(intval($set['app_goods_tw_url'])==1){
			$tg_url=$url2;
		}
		if(intval($set['app_goods_tw_url'])==2){
			$tg_url=$url3;
		}
		if(intval($set['app_goods_tw_url'])==3){
			$tg_url=$url4;
		}
		
		$data = array();
		$data['width']=750;
		$data['height']=1334;
		
       $data['list'][0] = array(//底部的框
            "url" => INDEX_WEB_URL."View/index/img/appapi/comm/code_bg_0.png?time=".time(),
            "x" => 0,
            "y" => 1054,
            "width" => 750,
            "height" => 270,
			"type"=>"png"
        );
		$data['list'][1] = array(//二维码
		   "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($tg_url)."&size=20&codeKB=1",
            "x" => 27,
            "y" => 1165,
            "width" => 130,
            "height" => 130,
			"type"=>"png"
        );
		//
		$data['list'][2] = array(//商品图【图片
            "url" => $img,
            "x" =>30,
            "y" => 350,
            "width" => 680,
            "height" => 680,
			"type"=>"jpg"
        );
		// $data['list'][3] = array(//商品来源
  //           "url" => $shop_img,
  //           "x" =>34,
  //           "y" => 75,
  //           "width" => 64,
  //           "height" => 32,
		// 	"type"=>"jpg"
  //       );
		$data['list'][4] = array(//
            "url" => INDEX_WEB_URL."View/index/img/appapi/comm/h2_nr_kuang_gold.png?time=".time(),
            "x" =>475,
            "y" => 890,
            "width" => 252,
            "height" => 105,
			"type"=>"png"
        );
		$data['list'][7] = array(//logo
            "url" => INDEX_WEB_URL."View/index/img/appapi/comm/good_share_logo.png?time=".time(),
            "x" =>30,
            "y" => 50,
            "width" => 257,
            "height" => 65,
			"type"=>"png"
        );
		for($i=0;$i<3;$i++){
			$data['text'][$i]=array(
				"size"=>22,
				"x"=>30,
				"y"=>180+40*$i,
				"val"=>mb_substr($arr['goods_title'],intval(590/25)*$i,intval(590/25),'utf-8'),
				"i"=>$i,
			);
			if($i!=0){
				$data['text'][$i]['x']=30;
			}
		}
		foreach($data['text'] as $k=>$v){
			if(empty($v['val']))unset($data['text'][$k]);
		}
		$data['text']=array_values($data['text']);
		$ii=end($data['text']);
		$y=80+30*$ii['i'];
		// $data['text'][$ii['i']+1]=array(
		// 	"size"=>22,
		// 	"x"=>140,
		// 	"y"=>$y+108,
		// 	"val"=>"￥".(floatval($arr['goods_price'])),
		// 	"color"=>'red',
		// );
	
		// $quan=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan_one.png?time=".time();
	
		// if(floatval($arr['yhq_price'])==0){$quan=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan_one.png?time=".time();}
		// $data['list'][5] = array(
  //           "url" => $quan,
  //           "x" =>33,
  //           "y" => $y+80,
  //           "width" => 89,
  //           "height" => 32,
		// 	"type"=>"png"
  //       );
		$data['text'][$ii['i']+2]=array(
			"size"=>34,
			"x"=>520,
			"y"=>965,
			"val"=>"￥".(floatval($arr['goods_price'])),
			"color"=>'white',
		);
		
		$arr['yhq_price']=floatval($arr['yhq_price']);
		if(!empty($arr['yhq_price'])){
			$len=strlen(floatval($arr['yhq_price'])."元优惠券");
			$data['text'][$ii['i']+3] = array(
				"size"=>25,
				"x"=>55,
				"y"=>$y+192,
				"val"=>(floatval($arr['yhq_price']))."元优惠券",
				"color"=>'white',
			);
			$data['list'][6] = array(
				"url" => INDEX_WEB_URL."View/index/img/appapi/comm/h2_nr_kuang_gold_one.png?time=".time(),
				"x" =>30,
				"y" => $y+155,
				"width" => 225,
				"height" => 50,
				"type"=>"png"
			);
		}
		
		fun("pic");
				//fpre($data);
			return pic::getpic($data);
		$data=zfun::arr64_encode($data);
		//zfun::head("jpg");
		
		$url=INDEX_WEB_URL."comm/pic.php?pic_ctrl=getpic&data=".urlencode($data);
		
		//fpre($url);
		//echo "<img src='".$url."'>";;
		//exit;
		return $url;
		//fpre($url);exit;
		
		//echo zfun::get(INDEX_WEB_URL."comm/pic.php?type=getpic&data=".$data);
		
	}
	//链接
	public static function urls($set=array(),$data=array(),$tid=0){
		$url4=$set['android_url'];
		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";
		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$data['fnuo_id'],'tgid' => $tid),'new_share');
		$url2=self::getUrl("down","supdownload",array('tgid' => $tid),"appapi");/*更换*/
	//	$url1=self::getUrl("gotojingdong","index",array("gid"=>$data['fnuo_id'],"tgid"=>$tid),"appapi");
		$url1=INDEX_WEB_URL."?mod=appapi&act=gotojingdong&tgid=".$tid."&gid=".$data['fnuo_id']."&yhq_url=".urlencode($_POST['yhq_url']);
		$tg_url=self::get_buy_url($data,$tid);
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
		
		if(intval($set['app_jdgoods_tw_url'])==1){
			$url=$url2;
		}
		if(intval($set['app_jdgoods_tw_url'])==2){
			$url=$url3;
		}
		if(intval($set['app_jdgoods_tw_url'])==3){
			$url=$url4;
		}
		$arr=array("url"=>$url,"url1"=>$url1,"url2"=>$url2,"url3"=>$url3,"url4"=>$url4);
		return $arr;
	}
	//获取京东推广url接口
	public static function get_buy_url($goods=array(),$tgid=0){
		actionfun("appapi/gotojingdong");
		if($tgid){
			$count=zfun::f_row("User","tg_code='".$tgid."'");
			if(empty($count)){
				$Decodekey = $GLOBALS['action'] -> getApp('Tgidkey'); $tgid = $Decodekey -> Decodekey($tgid);
			}else $tgid=$count['id'];
		 }
		if($tgid==0)return '';
		if(empty($goods))return '';
		$set=gotojingdongAction::getset();
		$pid='';
		if(empty($set['jd_site_id'])&&empty($set['jd_moshi_onoff']))zfun::fecho("京东联盟网站ID未填写");
		if(!empty($set['jd_moshi_onoff']))$set['jd_site_id']='';//当时聊天工具的时候，清空联盟id
		$pid=gotojingdongAction::tg_pid(0,$tgid,$set,$pid);
    	if(empty($pid))zfun::fecho("pid生成失败",$pid);
		$gid=$goods['fnuo_id'];
		$coupon_url=$goods['yhq_url'];
		if(!empty($_POST['yhq_url'])&&empty($coupon_url))$coupon_url=$_POST['yhq_url'];
		$arr=array(
			"gid"=>$gid,
			"coupon_url"=>$coupon_url,
			"pid"=>$pid,
		);
		
		$data=jingdong::zmsend("jd.get_buy_url",$arr);
		return $data['url'];
	}
	//获取拼多多推广url接口
	public static function get_pdd_buy_url($goods=array(),$tgid=0){
		actionfun("appapi/gotopinduoduo");
		if($tgid){
			$count=zfun::f_row("User","tg_code='".$tgid."'");
			if(empty($count)){
				$Decodekey = $GLOBALS['action'] -> getApp('Tgidkey'); $tgid = $Decodekey -> Decodekey($tgid);
			}else $tgid=$count['id'];
		 }
		if($tgid==0)return '';
		if(empty($goods))return '';
		$set=gotopinduoduoAction::getset();
		$pid='';
		$pid=gotopinduoduoAction::tg_pid(0,$tgid,$set,$pid);
    	if(empty($pid))zfun::fecho("pid生成失败",$pid);
		$gid=$goods['fnuo_id'];
		$arr=array(
			"gid"=>$gid,
			"pid"=>$pid,
			"open_app"=>intval($set['pdd_open_app']),
			"link_type"=>intval($set['pdd_link_type']),
		);
		$data=pinduoduo::zmsend("pdd.get_buy_url",$arr);
		return $data['url'];
	}
}
?>

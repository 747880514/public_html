<?php
actionfun("appapi/dgappcomm");
actionfun("default/alimama");
actionfun("appapi/tb_jhs");
actionfun("comm/dtk");
class newgoods_detailAction extends Action{
	static function handle_goods($goods=array()){
		if(!empty($goods))return $goods;

		$fnuo_id=filter_check($_POST['fnuo_id']);
		$dtk_goods=dtk::getgoods(str_replace(array("dtk","_"),"",$fnuo_id));
		if(!empty($dtk_goods))$fnuo_id=$dtk_goods['fnuo_id'];
		$fi="goods_img,goods_title,cate_id,cjtime,goods_sales,highcommission_url,shop_id,buy_url,goods_price,goods_cost_price,dp_id,yhq,yhq_span,yhq_end_time,yhq_price,id,commission,fnuo_id";
		//$goods=zfun::f_row("Goods","fnuo_id='$fnuo_id'",$fi);
		$shop_arr=array("","淘宝","天猫");
		$buy_url=$goods['buy_url'];
		/*if(!empty($goods)){
			//站内
			$goods=zfun::f_gethdprice(array($goods));$goods=reset($goods);
			$goods['yhq_price']=floatval($goods['yhq_price']);
			$goods['yhq_use_time']='';
			if(!empty($goods['cjtime'])&&!empty($goods['yhq_end_time'])){
			  $goods['yhq_use_time']="使用期限：".date("Y.m.d",$goods['cjtime'])."-".date("Y.m.d",$goods['yhq_end_time']);
			}
		}else{*/
			//物料
			actionfun("comm/tbmaterial");
			$goods=tbmaterial::id($fnuo_id);
			$goods['yhq_price']=floatval($goods['yhq_price']);
			if(($goods['yhq_price'])!=0){
				$goods['wl_yhq_url']=$goods['yhq_url'];
				$goods['yhq_use_time']="使用期限：".date("Y.m.d",$goods['start_time'])."-".date("Y.m.d",$goods['end_time']);
			}
		//}

		$goods['shop_type']=$shop_arr[$goods['shop_id']];
		$goods["id"]=$goods['fnuo_id'];
		$goods["pt_url"]=INDEX_WEB_URL."?mod=appapi&act=noyhq_gototaobao&ctrl=js&fnuo_id=".$fnuo_id;
		if(empty($goods['goods_title']))zfun::fecho("商品不存在",array(),1);
		$getgoodstype=filter_check($_POST['getGoodsType']);//类型 物料 大淘客
		if(!empty($goods['yhq_price']))$goods['yhq']=1;
		$goods['yhq_type']=0;
		if(!empty($GLOBALS['yhq_type']))$goods['yhq_type']=1;//是否是隐藏券
		$goods['getGoodsType']=$_POST['getGoodsType'];
		if(empty($getgoodstype)||$getgoodstype=='dtk'){//jj explosion 读取大淘客 佣金
			if(!empty($dtk_goods['commission']))$goods['commission']=$dtk_goods['commission'];
			$goods['getGoodsType']='dtk';
		}

		/*********详情图******/
		//$tmp=curl_get('https://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7Bitem_num_id%3A"'.$fnuo_id.'"%7D&type=jsonp&dataType=jsonp');
		//$tmp="[".self::getinstr($tmp,'"images":[',']').']';
		//$detail_img=json_decode($tmp,true);
		actionfun("comm/tb_web_api");
		$detail_img=tb_web_api::detail_img($fnuo_id);
		if(empty($detail_img))$detail_img=array();
		$goods['detailArr']=$detail_img;//商品详情图片集
		$goods_img=self::get_bc_img_list($fnuo_id);
		$goods['imgArr']=array();
		if(!empty($goods_img))$goods['imgArr']=$goods_img;//商品图片集

		if(empty($goods['imgArr'])){
			$goods['imgArr']=$goods['small_img'];
			$goods['imgArr'][]=$goods['goods_img'];
		}
		if(empty($goods['imgArr']))$goods['imgArr'][]=$goods['goods_img'];//如果没有图片默认一张
		foreach($goods['imgArr'] as $k=>$v){
			$goods['imgArr'][$k]=str_replace("_250x250.jpg","_500x500.jpg",$v);
		}
		$goods['dpArr']=self::store_msg($goods);//店铺信息
		$goods['is_store']=1;
		if(empty($goods['dpArr']))$goods['is_store']=0;
		$goods['xggoodsArr']=self::xggoods_msg($goods);//相似商品

		return $goods;
	}
	public function index(){
		appcomm::signcheck();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			$uid=$user['id'];
			$tgidkey = $this -> getApp('Tgidkey');
			$uid1 = $tgidkey -> addkey($uid);
			if(!empty($user['tg_code']))$uid1=$user['tg_code'];
		}
		$str="hhrshare_noflstr,fx_goods_fl,goods_detail_str1,goods_detail_str2,app_fanli_onoff,CustomUnit,almm_tongxun_onoff,app_zhuanlian_type,ggapitype";
		//淘礼金 开关
		$str.=",tb_tlj_onoff,tb_tlj_source_onoff";
		$set=zfun::f_getset($str);
		/***************设置缓存********************/
		$cookie_name='new_goods_detail';
		$cookie_arr=array("fnuo_id"=>$_POST['fnuo_id']);
		$goods=actfun::read_cookie($cookie_name,$cookie_arr,"dgapp");

		$huancun=0;if($goods)$huancun=1;
		/***********************************/
		//是否高佣商品
		$is_highcommission=0;
		//写入跟单记录
		if(!empty($uid)){//存入记录用户跟单
			$arr=array(
				"uid"=>$uid,
				"fnuo_id"=>$fnuo_id,
				"time"=>time(),
			);
			$where="uid=$uid and fnuo_id='".$fnuo_id."'";
			$tmp=zfun::f_count("Gendanjilu",$where);
			if(!empty($tmp))zfun::f_update("Gendanjilu",$where,$arr);
			else zfun::f_insert("Gendanjilu",$arr);
			$t1=strtotime("today")-86400;
			zfun::f_delete("Gendanjilu","time < ".$t1);
			if(!empty($_GET['onoff']))return;
		}
		/*********商品处理*********/
		$goods=self::handle_goods($goods);

		self::footmark($uid,$goods);//足迹
		$goods['share_url'] = $GLOBALS['action'] -> getUrl('invite_friend', 'goods_detail', array('tgid' => $uid1,"id"=>$goods['fnuo_id']),'new_share');
		$goods['str']='';
		$goods['yhq_span']=$goods['yhq_price']."元券";
		$goods['zhe']=$goods['zhe']."折";
		$goods['yhq_price']=floatval($goods['yhq_price']);
		$goods['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
		if(($goods['yhq_price'])!=0)$goods['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";

		/*****************设置缓存********************/
		if(!empty($goods)&&$huancun==0)actfun::set_cookie($cookie_name,$cookie_arr,$goods,"dgapp",7200);
		/*************************************/
		$mywhere="goodsid='".$goods['fnuo_id']."' AND uid=".intval($uid);
		$my=zfun::f_count("MyLike",$mywhere);
		$goods['is_collect']=0;$goods['is_mylike']=0;
		if($my>0){$goods['is_collect']=1;$goods['is_mylike']=1;}
		/**************佣金处理*************/
		$goods=zfun::f_fgoodscommission(array($goods));
		$goods=reset($goods);$goods['id']=$goods['fnuo_id'];
		$tmpgoods=array($goods);
		$tmpgoods=appcomm::goodsfeixiang($tmpgoods);
		$tmpgoods=reset($tmpgoods);
		$fx_commission=$tmpgoods['fx_commission'];
		$goods['fx_commission']=$fx_commission;
		if(empty($set['fx_goods_fl'])){
			$lv=(intval($user['is_sqdl'])+1);
			$sety=zfun::f_getset("hhrapitype,fxdl_fxyjbili".$lv.",fxdl_show_fl".$lv);// jj explosion
			$goods['fxz']="分享成单后即赚 ".$fx_commission."".$set['CustomUnit'];
			$goods['str']="购买此商品可存入".$goods['fcommission']."".$set['CustomUnit'];
			if(!empty($set['goods_detail_str1'])){
				$goods['fxz']=str_replace("[金额]",$fx_commission,$set['goods_detail_str1']);
			}
			if(!empty($set['goods_detail_str2'])){
				$goods['str']=str_replace("[金额]",$goods['fcommission'],$set['goods_detail_str2']);

			}
			if($sety["fxdl_show_fl".$lv]==1&&$user["operator_lv"]==0){
				$goods['str']="";// jj explosion
				$goods['fxz']=$set['hhrshare_noflstr'];// jj explosion
			}
			if($sety["fxdl_show_fl".$lv]==2&&$user["operator_lv"]==0){
				$goods['str']="";// jj explosion
			}

		}
		if($set['app_fanli_onoff']==0){
			$goods['str']="";
		}
		//判断要不要调起手淘
		$goods['is_not_dqst']=0;
		//开启后 需要时高佣的商品才会 关闭手淘打开
		if($set['almm_tongxun_onoff']==1&&$set['app_zhuanlian_type']=="zhushou"){//如果转链类型是 助手转链
			if($is_highcommission==1){
				$goods['is_not_dqst']=1;
				$goods['is_highcommission']=1;
			}
			if(!empty($goods['yhq_price'])){
				$goods['is_not_dqst']=1;
			}
			if(!empty($buy_url))$goods['is_not_dqst']=1;
		}



		//开启了高佣api
		if($set['app_zhuanlian_type']=='gy_api'||$set['app_zhuanlian_type']=='wlgy_api'){
			$goods['is_not_dqst']=1;

		}
		//如果后台开启了淘礼金 选择了栏目
	/*	$is_tlj=$_POST['is_tlj'];
		if(!empty($set['tb_tlj_source_onoff'])&&$set['tb_tlj_onoff'].''=='1'){
			$is_tlj=self::check_tlj($goods['fnuo_id'],$is_tlj);
		}
		//如果是全部商品时
		if(strstr($set['tb_tlj_source_onoff'],",all,")&&$set['tb_tlj_onoff'].''=='1'){$is_tlj=1;}
		//掏礼金开关
		if($set['tb_tlj_onoff'].''=='1'&&intval($is_tlj)==1){
			$goods['is_not_dqst']=1;
		}*/
		$is_tlj=0;
		do{
			//如果淘礼金没开启
			if($set['tb_tlj_onoff'].''=='0')break;
			//如果没有选择淘礼金商品类型
			if(empty($set['tb_tlj_source_onoff']))break;
			$where="fnuo_id='".$goods['fnuo_id']."' and data LIKE '%taolijin%'  AND start_time<" . time() . " AND end_time>" . time();
			$zngoods=zfun::f_count("Goods",$where);
			//如果开启全部类型 或者 是站外商品
			if(strstr($set['tb_tlj_source_onoff'],",all,")||$_POST['is_tlj'].''=='1'){
				$is_tlj=1;
				break;
			}
			//如果站内有这个商品
			if(!empty($zngoods)){
				$is_tlj=1;
				break;
			}

		}while(false);
		if($is_tlj==1)$goods['is_not_dqst']=1;

		/*********兼容旧版本*******/
		if($_POST['version']<3)$goods['is_not_dqst']=0;

		/*
			普通商品
			555374166883
			高佣商品
			564625657513
		*/
		//$getgoodstype=filter_check($_POST['getGoodsType']);//类型 物料 大淘客
	//	$goods['getGoodsType']=$getgoodstype;
		$goods['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/taobao.png";
		if($goods['shop_id']==2)$goods['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/tmall.png";
		unset($goods['fcommissionshow'],$goods['fbili'],$goods['detailurl']);
		$goods['cate_id']=intval($goods['cate_id']);
		$goods['quan_bjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/quan_bjimg.png";
		$goods=self::getfanli($goods,$user);

		if(!empty($goods['fnuo_url']))$goods['fnuo_url']=$goods['fnuo_url']."&is_tlj=".intval($is_tlj);
		$goods['is_tlj']=intval($is_tlj);

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
		$goods['hs_bili'] = explode("￥", $goods['btn_fxz']['bili'])[1];
		$goods['new_hs_bili'] = $goods['goods_price'] * ($goods['commission']/100) * $hs_bili;
		$goods['new_hs_bili'] = sprintf("%.2f", $goods['new_hs_bili']);
		$goods['hs_bili'] = str_replace($goods['hs_bili'], $goods['new_hs_bili'], $goods['btn_fxz']['bili']);

		$goods['btn_fxz']['bili'] = $goods['hs_bili'];
		$goods['btn_zgz']['bili'] = $goods['hs_bili'];
		$goods['img_fxz']['bili'] = $goods['hs_bili'];

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
			$goods['fcommission'] = $goods['goods_price'] * ($goods['commission']/100) * $up_hs_bili;
			$goods['fcommission'] = sprintf("%.2f", $goods['fcommission']);


			$goods['up_hs_bili'] = explode("￥", $goods['img_sjz']['bili'])[1];
			$goods['img_sjz']['bili'] = str_replace($goods['up_hs_bili'], $goods['fcommission'], $goods['img_sjz']['bili']);
		}

		zfun::fecho("商品详情",$goods,1,1);
	}

	//百川商品图片列表
	static function get_bc_img_list($fnuo_id=""){
		fun("bcapi");
		$arr=array(
			"item_id"=>$fnuo_id,
			"fields"=>"item",
		);
		$tmp=bcapi::tbsend("taobao.item.detail.get",$arr,"item_detail_get_response,data");
		$tmp=json_decode($tmp,true);
		$img_list=$tmp['item']['images'];
		foreach($img_list as $k=>$v){
			$img_list[$k]=$v."_500x500.jpg";
		}
		return $img_list;
	}

	//关于升级赚返利的文字
	public static function getfanli($goods=array(),$user=array()){
		$set=zfun::f_getset("operator_onoff,checkVersion");
		if(intval($set['operator_onoff'])=='0')$goods=self::getFanlimodel($goods,$user);//返利模式
		elseif(intval($set['operator_onoff'])=='1') $goods=self::getTuiguangmodel($goods,$user);//推广模式
		elseif(intval($set['operator_onoff'])=='2') $goods=self::getTwomodel($goods,$user);//双轨模式

		$goods['detail_goods_sjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/detail_goods_sjimg.png";
		$goods['detail_goods_fxzimg']=INDEX_WEB_URL."View/index/img/appapi/comm/detail_goods_fxzimg.png";
		//判断iOS审核隐藏返利之类的
		if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
			/*隐藏图片上的升级赚 分享赚*/
			$goods['img_fxz']['is_show']=0;
			$goods['img_sjz']['is_show']=0;
			/*底部按钮文字修改*/
			$goods['btn_zgz']['str']='立即购买';
			$goods['btn_zgz']['bili']='';
			$goods['btn_fxz']['str']='领券购';
			$goods['btn_fxz']['bili']='';
			$goods['btn_fxz']['is_share']=0;
			/*升级入口*/
			$goods['mid_zgz']['is_show']=0;
		}
		return $goods;
	}
	static function getsets($fxset){
		if(empty($fxset['hhrshare_noflstr']))$fxset['hhrshare_noflstr']='分享赚钱';
		if(empty($fxset['hhrshare_flstr']))$fxset['hhrshare_flstr']='分享赚';
		if(empty($fxset['goods_detail_btnleftloginstr']))$fxset['goods_detail_btnleftloginstr']='升级赚';
		if(empty($fxset['goods_detail_commissionstr']))$fxset['goods_detail_commissionstr']='￥[佣金]';
		if(empty($fxset['goods_detail_btnleftstr']))$fxset['goods_detail_btnleftstr']='分享赚';
		if(empty($fxset['goods_detail_btnrightstr']))$fxset['goods_detail_btnrightstr']='自购赚';
		if(empty($fxset['goods_detail_btnleftstr2']))$fxset['goods_detail_btnleftstr2']='立即分享';
		if(empty($fxset['goods_detail_btnrightstr2']))$fxset['goods_detail_btnrightstr2']='立即购买';
		if(empty($fxset['goods_detail_getlvstr']))$fxset['goods_detail_getlvstr']='点击申请更高收益';
		if(empty($fxset['goods_detail_getlvstr1']))$fxset['goods_detail_getlvstr1']='现在升级[等级]，最高可得收益[金额]';
		if(empty($fxset['goods_detail_getlvstr2']))$fxset['goods_detail_getlvstr2']='升级更高等级获得自购收益';
		if(empty($fxset['goods_detail_btnleftstr']))$fxset['goods_detail_btnleftstr']='分享赚';
		if(empty($fxset['goods_detail_sjzstr']))$fxset['goods_detail_sjzstr']='升级赚';
		if(empty($fxset['goods_detail_fxzstr']))$fxset['goods_detail_fxzstr']='分享赚';
		if(empty($fxset['goods_detail_btnleft_bjcolor']))$fxset['goods_detail_btnleft_bjcolor']='EB973A';
		if(empty($fxset['goods_detail_btnleft_fontcolor']))$fxset['goods_detail_btnleft_fontcolor']='FFFFFF';
		if(empty($fxset['goods_detail_btnright_bjcolor']))$fxset['goods_detail_btnright_bjcolor']='f43e79';
		if(empty($fxset['goods_detail_btnright_fontcolor']))$fxset['goods_detail_btnright_fontcolor']='FFFFFF';
		return $fxset;
	}
	//推广模式
	public static function getTuiguangmodel($goods=array(),$user=array()){
		$fxset=zfun::f_getset("goods_detail_btnleft_bjcolor,goods_detail_btnleft_fontcolor,goods_detail_btnright_bjcolor,goods_detail_btnright_fontcolor,goods_detail_getlvstr1,goods_detail_getlvstr2,goods_detail_getlvstr,goods_detail_commissionstr,goods_detail_btnleftloginstr,goods_detail_btnleftstr,goods_detail_btnrightstr,goods_detail_btnleftstr2,goods_detail_sjzstr,goods_detail_btnrightstr2,goods_detail_fxzstr,fxdl_lv,operator_wuxian_bili,CustomUnit,fx_goods_fl,operator_name,operator_name_2");
		$fxset=self::getsets($fxset);

		$str="fxdl_lv";
		//分享佣金  推广模式 读 一级佣金
		for($i=1;$i<=10;$i++)$str.=",fxdl_tjhy_bili1_".$i;
		$set=zfun::f_getset($str);unset($set['fxdl_lv']);
		$key = array_search(max($set),$set); //最大值下标

		for($i=1;$i<=10;$i++){$str.=",fxdl_show_fl".$i;$str.=",fxdl_name".$i;}
		$showset=zfun::f_getset($str);
		$maxbili=$set[$key]/100;
		$num=substr($key,-1,1);
		$name=$showset['fxdl_name'.$num];
		if(($user['is_sqdl'])>0){$maxbili=floatval($fxset['operator_wuxian_bili']/100);$name=$fxset['operator_name'];}
		if($user['operator_lv']==1){$maxbili=floatval($fxset['operator_wuxian_bili']/100);$name=$fxset['operator_name_2'];}
		//未登录状态
		$dian=100;
		$sjzbili=0;$zgbili=$goods['fcommission'];$fxzbili=$goods['fx_commission'];$fxzshow=0;$sjzshow=0;
		$zgstr=$fxset['goods_detail_btnrightstr'];$fxzstr=$fxset['goods_detail_btnleftstr'];

		$midzgbili=round($goods['goods_price']*($goods['commission']/100)*$maxbili*$dian)/$dian;
		$midzgstr='现在升级'.$name.",最高可得收益".$midzgbili.$fxset['CustomUnit'];
		if(!empty($fxset['goods_detail_getlvstr1'])){
			$midzgstr=str_replace("[等级]",$name,$fxset['goods_detail_getlvstr1']);
			$midzgstr=str_replace("[金额]",$midzgbili,$midzgstr);
		}
		$midzgstr1=$fxset['goods_detail_getlvstr'];
		$midshow=1;
		if(empty($goods['fcommission'])){$zgstr=$fxset['goods_detail_btnrightstr2'];$zgbili='';$midzgstr=$fxset['goods_detail_getlvstr2'];}
		if(empty($user)){
			$sjzbili=$fxset['operator_wuxian_bili']/100;//升级赚比例  无极限比例
			$zgbili=floatval($fxset['operator_wuxian_bili']/100);//自购比例    无极限比例
			$fxzstr=$fxset['goods_detail_btnleftloginstr'];
			$fxzbili=$sjzbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;

			if(!empty($zgbili)){$zgstr=$fxset['goods_detail_btnrightstr'];$zgbili=round($goods['goods_price']*($goods['commission']/100)*$zgbili*$dian)/$dian;}
			else {$zgstr=$fxset['goods_detail_btnrightstr2'];$zgbili='';}
			$midzgbili=str_replace("￥","",$zgbili);

			//$midzgstr='升级更高等级获得自购收益'.$midzgbili.$fxset['CustomUnit'];
			$bili=floatval($fxset['operator_wuxian_bili']/100);
			$midzgbili=round($goods['goods_price']*($goods['commission']/100)*$bili*$dian)/$dian;
			$midzgstr='现在升级'.$fxset['operator_name'].",最高可得收益".$midzgbili.$fxset['CustomUnit'];
			if(!empty($fxset['goods_detail_getlvstr1'])){
				$midzgstr=str_replace("[等级]",$fxset['operator_name'],$fxset['goods_detail_getlvstr1']);
				$midzgstr=str_replace("[金额]",$midzgbili,$midzgstr);
			}
			if(empty($midzgbili))$midzgstr=$fxset['goods_detail_getlvstr2'];

			$sjzshow=1;
		}
		//普通会员
		if(!empty($user)&&$user['is_sqdl']==0&&$user['operator_lv']==0){
			$sjzbili=$set[$key]/100;//分享赚比例  代理比例

			$sjzbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;
			$sjzshow=1;
			$fxzbili=$sjzbili;
			$zgbili='';
			$zgstr=$fxset['goods_detail_btnrightstr2'];$fxzstr=$fxset['goods_detail_btnleftloginstr'];
			//$midzgbili=str_replace("￥","",$sjzbili);
			//$midzgstr='升级更高等级获得自购收益'.$midzgbili.$fxset['CustomUnit'];
			$bili=$set[$key]/100;
			$num=substr($key,-1,1);
			$midzgbili=round($goods['goods_price']*($goods['commission']/100)*$bili*$dian)/$dian;
			$midzgstr='现在升级'.$showset['fxdl_name'.$num].",最高可得收益".$midzgbili.$fxset['CustomUnit'];
			if(!empty($fxset['goods_detail_getlvstr1'])){
				$midzgstr=str_replace("[等级]",$showset['fxdl_name'.$num],$fxset['goods_detail_getlvstr1']);
				$midzgstr=str_replace("[金额]",$midzgbili,$midzgstr);
			}
			if(empty($midzgbili))$midzgstr=$fxset['goods_detail_getlvstr2'];
		}
		//代理
		if(!empty($user)&&$user['is_sqdl']>0){
			$sjzbili=$fxset['operator_wuxian_bili']/100;//分享赚比例  比例
			$sjzbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;
			$sjzshow=1;$fxzshow=1;

		}
		//运营商
		if(!empty($user)&&$user['operator_lv']>0){
			$sjzshow=0;$fxzshow=1;
			$midshow=0;$midzgstr1='';
		}

		//如果后台关了分享赚显示时处理
		if(!empty($fxset['fx_goods_fl'])){$midshow=0;$fxzstr=$fxset['goods_detail_btnleftstr2'];$fxzshow=0;$fxzbili='';}
		//如果后台会员等级关了自购返利和分享赚
		if(($showset['fxdl_show_fl'.($user['is_sqdl']+1)])==1&&($user['operator_lv'])==0){$sjzshow=0;$midshow=0;$fxzbili='';$zgbili='';$zgstr=$fxset['goods_detail_btnrightstr2'];$fxzstr=$fxset['goods_detail_btnleftstr2'];$fxzshow=0;}
		//如果后台会员等级关了自购返利
		if(($showset['fxdl_show_fl'.($user['is_sqdl']+1)])==2&&($user['operator_lv'])==0){$zgbili='';$zgstr='立即购买';}

		if(!empty($fxzbili))$fxzbili=str_replace("[佣金]",$fxzbili,$fxset['goods_detail_commissionstr']);
		if(!empty($sjzbili))$sjzbili=str_replace("[佣金]",$sjzbili,$fxset['goods_detail_commissionstr']);
		if(!empty($zgbili))$zgbili=str_replace("[佣金]",$zgbili,$fxset['goods_detail_commissionstr']);

		$left_bjcolor=$fxset['goods_detail_btnleft_bjcolor'];
		$left_fontcolor=$fxset['goods_detail_btnleft_fontcolor'];
		$right_bjcolor=$fxset['goods_detail_btnright_bjcolor'];
		$right_fontcolor=$fxset['goods_detail_btnright_fontcolor'];
		$goods["img_sjz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$fxset['goods_detail_sjzstr'],"str1"=>'',"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/detail_up.png',"bili"=>$sjzbili,"is_show"=>$sjzshow);
		$goods["img_fxz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$fxset['goods_detail_fxzstr'],"str1"=>'',"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/detail_share.png',"bili"=>$fxzbili,"is_show"=>$fxzshow);
		$goods["btn_zgz"]=array("fontcolor"=>$right_fontcolor,"bjcolor"=>$right_bjcolor,"str"=>$zgstr,"str1"=>'',"img"=>'',"bili"=>$zgbili,"is_show"=>1);
		$goods["btn_fxz"]=array("fontcolor"=>$left_fontcolor,"bjcolor"=>$left_bjcolor,"is_share"=>1,"str"=>$fxzstr,"str1"=>'',"img"=>'',"bili"=>$fxzbili,"is_show"=>1);
		$goods["mid_zgz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$midzgstr,"str1"=>$midzgstr1,"img"=>'',"bili"=>$midzgbili,"is_show"=>$midshow);
		return $goods;
	}
	//返利模式
	public static function getFanlimodel($goods=array(),$user=array()){
		$fxset=zfun::f_getset("goods_detail_btnleft_bjcolor,goods_detail_btnleft_fontcolor,goods_detail_btnright_bjcolor,goods_detail_btnright_fontcolor,goods_detail_getlvstr1,goods_detail_getlvstr2,goods_detail_getlvstr,goods_detail_commissionstr,goods_detail_btnleftloginstr,goods_detail_btnleftstr,goods_detail_btnrightstr,goods_detail_btnleftstr2,goods_detail_sjzstr,goods_detail_btnrightstr2,goods_detail_fxzstr,fxdl_lv,CustomUnit,fx_goods_fl");
		$fxset=self::getsets($fxset);
		$str="fxdl_lv";
		for($i=1;$i<=$fxset['fxdl_lv'];$i++){$str.=",fxdl_show_fl".$i;$str.=",fxdl_name".$i;}
		$showset=zfun::f_getset($str);
		$str="fxdl_lv";
		//分享佣金
		for($i=1;$i<=$fxset['fxdl_lv'];$i++)$str.=",fxdl_fxyjbili".$i;
		$set=zfun::f_getset($str);unset($set['fxdl_lv'],$gwset['fxdl_fxyjbili1']);

		$key = array_search(max($set),$set); //最大值下标
		//自购佣金
		$str="fxdl_lv";
		for($i=1;$i<=$fxset['fxdl_lv'];$i++)$str.=",fxdl_gwbili".$i;
		$gwset=zfun::f_getset($str);unset($gwset['fxdl_lv'],$gwset['fxdl_gwbili1']);

		$gwkey = array_search(max($gwset),$gwset); //最大值下标
		$name=$showset['fxdl_name'.($user['is_sqdl']+2)];
		$maxbili=floatval($gwset['fxdl_gwbili'.($user['is_sqdl']+2)]/100);
		//未登录状态
		$dian=100;
		$sjzbili=0;$zgbili=$goods['fcommission'];$fxzbili=$goods['fx_commission'];$fxzshow=0;$sjzshow=0;
		$zgstr=$fxset['goods_detail_btnrightstr'];$fxzstr=$fxset['goods_detail_btnleftstr'];
		$midzgbili=round($goods['goods_price']*($goods['commission']/100)*$maxbili*$dian)/$dian;
		$midzgstr='现在升级'.$name.",最高可得收益".$midzgbili.$fxset['CustomUnit'];
		if(!empty($fxset['goods_detail_getlvstr1'])){
			$midzgstr=str_replace("[等级]",$name,$fxset['goods_detail_getlvstr1']);
			$midzgstr=str_replace("[金额]",$midzgbili,$midzgstr);
		}
		$midzgstr1=$fxset['goods_detail_getlvstr'];
		$midshow=1;
		if(empty($goods['fcommission'])){$zgstr=$fxset['goods_detail_btnrightstr2'];$zgbili='';$midzgstr=$fxset['goods_detail_getlvstr2'];}
		if(empty($user)){
			$fxzbili=$set[$key]/100;//分享赚比例
			$zgbili=$gwset[$gwkey]/100;//自购比例
			$fxzstr=$fxset['goods_detail_btnleftloginstr'];
			$sjzbili=$fxzbili=round($goods['goods_price']*($goods['commission']/100)*$fxzbili*$dian)/$dian;
			if(!empty($zgbili)){$zgstr=$fxset['goods_detail_btnrightstr'];$zgbili=round($goods['goods_price']*($goods['commission']/100)*$zgbili*$dian)/$dian;}
			else {$zgstr=$fxset['goods_detail_btnrightstr2'];$zgbili='';}
			$midzgbili=str_replace("￥","",$zgbili);
			//$midzgstr='升级更高等级获得自购收益'.$midzgbili.$fxset['CustomUnit'];
			$bili=floatval($gwset['fxdl_gwbili'.($fxset['fxdl_lv'])]/100);
			$midzgbili=round($goods['goods_price']*($goods['commission']/100)*$bili*$dian)/$dian;
			$midzgstr='现在升级'.$showset['fxdl_name'.($fxset['fxdl_lv'])].",最高可得收益".$midzgbili.$fxset['CustomUnit'];
			if(!empty($fxset['goods_detail_getlvstr1'])){
				$midzgstr=str_replace("[等级]",$showset['fxdl_name'.($fxset['fxdl_lv'])],$fxset['goods_detail_getlvstr1']);
				$midzgstr=str_replace("[金额]",$midzgbili,$midzgstr);
			}
			if(empty($midzgbili))$midzgstr=$fxset['goods_detail_getlvstr2'];
			$sjzshow=1;
		}
		//登录状态
		//如果小于最高等级
		if(!empty($user)&&($user['is_sqdl']+1)<$fxset['fxdl_lv']&&$user['is_sqdl']>0){
			$sjzbili=$set["fxdl_fxyjbili".($user['is_sqdl']+2)]/100;//升级赚比例
			$sjzbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;
			$fxzshow=1;$sjzshow=1;

		}
		if(!empty($user)&&$user['is_sqdl']==0&&$user['operator_lv']==0){
			$sjzbili=$set["fxdl_fxyjbili".($user['is_sqdl']+2)]/100;//升级赚比例
			$sjzbili=$fxzbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;
			$fxzstr=$fxset['goods_detail_btnleftloginstr'];
			$sjzshow=1;

		}
		//如果等于最高等级
		if(!empty($user)&&($user['is_sqdl']+1)>=$fxset['fxdl_lv']){
			$fxzshow=1;
			$midshow=0;$midzgstr1='';
		}
		//如果他是运营商，但是他开了返利模式
		if(!empty($user)&&($user['operator_lv'])>0){
			$fxzshow=1;$sjzshow=0;$sjzbili='';
				$midshow=0;$midzgstr1='';
		}
		//如果后台关了分享赚显示时处理
		if(!empty($fxset['fx_goods_fl'])){$fxzstr=$fxset['goods_detail_btnleftstr2'];$midshow=0;$fxzshow=0;$fxzbili='';}
		//如果后台会员等级关了自购返利和分享赚
		if(($showset['fxdl_show_fl'.($user['is_sqdl']+1)])==1&&($user['operator_lv'])==0){$midshow=0;$fxzbili='';$zgbili='';$zgstr=$fxset['goods_detail_btnrightstr2'];$fxzstr=$fxset['goods_detail_btnleftstr2'];$fxzshow=0;}
		//如果后台会员等级关了自购返利
		if(($showset['fxdl_show_fl'.($user['is_sqdl']+1)])==2&&($user['operator_lv'])==0){$zgbili='';$zgstr=$fxset['goods_detail_btnrightstr2'];}


		if(!empty($fxzbili))$fxzbili=str_replace("[佣金]",$fxzbili,$fxset['goods_detail_commissionstr']);
		if(!empty($sjzbili))$sjzbili=str_replace("[佣金]",$sjzbili,$fxset['goods_detail_commissionstr']);
		if(!empty($zgbili))$zgbili=str_replace("[佣金]",$zgbili,$fxset['goods_detail_commissionstr']);
		$left_bjcolor=$fxset['goods_detail_btnleft_bjcolor'];
		$left_fontcolor=$fxset['goods_detail_btnleft_fontcolor'];
		$right_bjcolor=$fxset['goods_detail_btnright_bjcolor'];
		$right_fontcolor=$fxset['goods_detail_btnright_fontcolor'];
		$goods["img_sjz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$fxset['goods_detail_sjzstr'],"str1"=>'',"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/detail_up.png',"bili"=>$sjzbili,"is_show"=>$sjzshow);
		$goods["img_fxz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$fxset['goods_detail_fxzstr'],"str1"=>'',"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/detail_share.png',"bili"=>$fxzbili,"is_show"=>$fxzshow);
		$goods["btn_zgz"]=array("fontcolor"=>$right_fontcolor,"bjcolor"=>$right_bjcolor,"str"=>$zgstr,"str1"=>'',"img"=>'',"bili"=>$zgbili,"is_show"=>1);
		$goods["btn_fxz"]=array("fontcolor"=>$left_fontcolor,"bjcolor"=>$left_bjcolor,"str"=>$fxzstr,"str1"=>'',"img"=>'',"bili"=>$fxzbili,"is_show"=>1);
		$goods["mid_zgz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$midzgstr,"str1"=>$midzgstr1,"img"=>'',"bili"=>$midzgbili,"is_show"=>$midshow);
		return $goods;
	}

	//双轨模式
	public static function getTwomodel($goods=array(),$user=array(),$dian=100){
		$fxset=zfun::f_getset("goods_detail_btnleft_bjcolor,goods_detail_btnleft_fontcolor,goods_detail_btnright_bjcolor,goods_detail_btnright_fontcolor,fxdl_lv");
		$str="fxdl_lv,goods_detail_getlvstr1,goods_detail_getlvstr2,goods_detail_getlvstr,goods_detail_commissionstr,goods_detail_btnleftloginstr,goods_detail_btnleftstr,goods_detail_btnrightstr,goods_detail_btnleftstr2,goods_detail_sjzstr,goods_detail_btnrightstr2,goods_detail_fxzstr,fxdl_lv,CustomUnit,fx_goods_fl";
		for($i=1;$i<=$fxset['fxdl_lv'];$i++){$str.=",fxdl_show_fl".$i;$str.=",fxdl_name".$i;}
		$showset=zfun::f_getset($str);
		$showset=self::getsets($showset);
		$lv=intval($user['is_sqdl']);
		$next_lv=intval($user['is_sqdl'])+1;
		$name=$showset["fxdl_name".($lv+2)];
		actionfun("comm/twoway");$twoset=twoway::set();
		$max=0;$maxbili=0;
		foreach($twoset as $k=>$v){if($v['自购比例']>$bili){$max=$k;$maxbili=$v['自购比例'];}}
		$maxbili=$maxbili/100;
		/***************这是未登录***************/
		$fxzstr=$showset['goods_detail_btnleftstr'];
		$zgstr=$showset['goods_detail_btnrightstr'];
		$midzgstr1=$showset['goods_detail_getlvstr'];
		$fxzshow=0;$sjzshow=0;$midshow=0;

		/****************未登录****************/
		if(empty($user)){
			$fxzstr=$showset['goods_detail_btnleftloginstr'];//未登录显示
			$commision=round($goods['goods_price']*($goods['commission']/100)*$maxbili*$dian)/$dian;
			$fxzbili=$zgbili='';$midzgbili='';
			if(!empty($commision))$midzgbili=$sjzbili=$fxzbili=$zgbili=$commision;
			$midzgstr='现在升级'.$showset['fxdl_name'.($showset['fxdl_lv'])].",最高可得收益".$commision.$showset['CustomUnit'];
			if(!empty($showset['goods_detail_getlvstr1'])){
				$midzgstr=str_replace("[等级]",$showset['fxdl_name'.($showset['fxdl_lv'])],$showset['goods_detail_getlvstr1']);
				$midzgstr=str_replace("[金额]",$commision,$midzgstr);
			}
			if(empty($commision))$midzgstr=$showset['goods_detail_getlvstr2'];
			$sjzshow=1;
		}

		if(!empty($user)){
			$midshow=1;
			//自购
			$fxzbili=$goods['fx_commission'];

			//百里.修改前
			// $zgbili=$goods['fcommission'];
			//百里.修改后
			$zgbili=$goods['fx_commission'];

			//升级赚
			$sjset=$twoset[$next_lv];$sjzbili=$sjset['自购比例']/100;
			$zgstr=$showset['goods_detail_btnrightstr'];$fxzstr=$showset['goods_detail_btnleftstr'];
			$midzgbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;
			$midzgstr='现在升级'.$name.",最高可得收益".$midzgbili.$showset['CustomUnit'];
			if(!empty($showset['goods_detail_getlvstr1'])){
				$midzgstr=str_replace("[等级]",$name,$showset['goods_detail_getlvstr1']);
				$midzgstr=str_replace("[金额]",$midzgbili,$midzgstr);
			}
		}
		if(empty($goods['fcommission'])){$zgstr=$showset['goods_detail_btnrightstr2'];$zgbili='';$midzgstr=$showset['goods_detail_getlvstr2'];}

		//如果小于最高等级
		if(!empty($user)&&($user['is_sqdl']+1)<$showset['fxdl_lv']&&$user['is_sqdl']>0){
			$oneset=$twoset[$next_lv];

			//百里.修改前
			//$sjzbili=$oneset['自购比例']/100;//升级赚比例

			//百里.修改后
			$sjzbili=($oneset['自购比例']+$oneset['推广1级比例']+$oneset['团队存在1合伙人比例']+$oneset['团队存在2合伙人比例']+$oneset['团队存在1同级比例']+$oneset['团队存在2同级比例'])/100;//升级赚比例

			$sjzbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;
			$fxzshow=1;$sjzshow=1;

		}
		if(!empty($user)&&$user['is_sqdl']==0&&$user['operator_lv']==0){
			$oneset=$twoset[$next_lv];

			//百里.修改前
			// $sjzbili=$oneset['自购比例']/100;//升级赚比例

			//百里.修改后
			$sjzbili=($oneset['自购比例']+$oneset['推广1级比例']+$oneset['团队存在1合伙人比例']+$oneset['团队存在2合伙人比例']+$oneset['团队存在1同级比例']+$oneset['团队存在2同级比例'])/100;//升级赚比例

			$sjzbili=$fxzbili=round($goods['goods_price']*($goods['commission']/100)*$sjzbili*$dian)/$dian;
			$fxzstr=$showset['goods_detail_btnleftloginstr'];
			$sjzshow=1;
		}
		//如果等于最高等级
		if(!empty($user)&&($user['is_sqdl']+1)>=$showset['fxdl_lv']){
			$fxzshow=1;
			$midshow=0;$midzgstr1='';
		}
		//如果他是运营商，但是他开了双轨模式
		if(!empty($user)&&($user['operator_lv'])!='0'){
			$fxzshow=0;$sjzshow=0;$sjzbili='';$zgbili='';
			$midshow=0;$midzgstr1='';$fxzbili='';
			$zgstr=$showset['goods_detail_btnrightstr2'];$fxzstr=$showset['goods_detail_btnleftstr2'];
		}
		//如果后台关了分享赚显示时处理
		if(!empty($showset['fx_goods_fl'])){$fxzstr=$showset['goods_detail_btnleftstr2'];$midshow=0;$fxzshow=0;$fxzbili='';}
		//如果后台会员等级关了自购返利和分享赚
		if(($showset['fxdl_show_fl'.($user['is_sqdl']+1)])==1&&($user['operator_lv'])==0){$midshow=0;$fxzbili='';$zgbili='';$zgstr=$showset['goods_detail_btnrightstr2'];$fxzstr=$showset['goods_detail_btnleftstr2'];$fxzshow=0;}
		//如果后台会员等级关了自购返利
		if(($showset['fxdl_show_fl'.($user['is_sqdl']+1)])==2&&($user['operator_lv'])==0){$zgbili='';$zgstr=$showset['goods_detail_btnrightstr2'];}


		if(!empty($fxzbili))$fxzbili=str_replace("[佣金]",$fxzbili,$showset['goods_detail_commissionstr']);
		if(!empty($sjzbili))$sjzbili=str_replace("[佣金]",$sjzbili,$showset['goods_detail_commissionstr']);
		if(!empty($zgbili))$zgbili=str_replace("[佣金]",$zgbili,$showset['goods_detail_commissionstr']);
		if(empty($fxset['goods_detail_btnleft_bjcolor']))$fxset['goods_detail_btnleft_bjcolor']='EB973A';
		if(empty($fxset['goods_detail_btnleft_fontcolor']))$fxset['goods_detail_btnleft_fontcolor']='FFFFFF';
		if(empty($fxset['goods_detail_btnright_bjcolor']))$fxset['goods_detail_btnright_bjcolor']='f43e79';
		if(empty($fxset['goods_detail_btnright_fontcolor']))$fxset['goods_detail_btnright_fontcolor']='FFFFFF';
		$left_bjcolor=$fxset['goods_detail_btnleft_bjcolor'];
		$left_fontcolor=$fxset['goods_detail_btnleft_fontcolor'];
		$right_bjcolor=$fxset['goods_detail_btnright_bjcolor'];
		$right_fontcolor=$fxset['goods_detail_btnright_fontcolor'];

		$goods["img_sjz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$showset['goods_detail_sjzstr'],"str1"=>'',"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/detail_up.png',"bili"=>$sjzbili,"is_show"=>$sjzshow);
		$goods["img_fxz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$showset['goods_detail_fxzstr'],"str1"=>'',"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/detail_share.png',"bili"=>$fxzbili,"is_show"=>$fxzshow);
		$goods["btn_zgz"]=array("fontcolor"=>$right_fontcolor,"bjcolor"=>$right_bjcolor,"str"=>$zgstr,"str1"=>'',"img"=>'',"bili"=>$zgbili,"is_show"=>1);
		$goods["btn_fxz"]=array("fontcolor"=>$left_fontcolor,"bjcolor"=>$left_bjcolor,"str"=>$fxzstr,"str1"=>'',"img"=>'',"bili"=>$fxzbili,"is_show"=>1);
		$goods["mid_zgz"]=array("fontcolor"=>'',"bjcolor"=>'',"str"=>$midzgstr,"str1"=>$midzgstr1,"img"=>'',"bili"=>$midzgbili,"is_show"=>$midshow);
		return $goods;
	}

	//足迹
	public static function footmark($uid=0,$goods=array()){
		if(empty($uid))return false ;
		if(empty($goods['fnuo_id']))return false ;
		$count=zfun::f_row("FootMark","uid='$uid' and goodsId='".$goods['fnuo_id']."'");
		$time=$count['starttime'];
		if(empty($time))$time=time();
		$tmp=array("goodsId"=>$goods['fnuo_id'],"uid"=>$uid,"starttime"=>$time,"endtime"=>time());
		$data=array(
			"goods_title"=>$goods['goods_title'],
			"goods_price"=>$goods['goods_price'],
			"goods_cost_price"=>$goods['goods_cost_price'],
			"goods_img"=>$goods['goods_img'],
			"goods_sales"=>$goods['goods_sales'],
			"commission"=>$goods['commission'],
			"shop_id"=>$goods['shop_id'],
			"fnuo_id"=>$goods['fnuo_id'],
			"yhq"=>$goods['yhq'],
			"yhq_price"=>$goods['yhq_price'],
			"yhq_span"=>$goods['yhq_span'],
		);
		$tmp['data']=zfun::f_json_encode($data);

		if(!empty($count)){
			zfun::f_update("FootMark","uid='$uid' and goodsId='".$goods['fnuo_id']."'",$tmp);
			return false;
		}
		zfun::f_insert("FootMark",$tmp);
	}
	//店铺信息
	public static function store_msg($goods){
		$arr=array();
		if(empty($goods['shop_title']))return $arr;
		$dp=self::getshopdetail($goods['shop_title'],$goods['seller_id'],$goods['shop_id']);
		if(empty($dp))return $arr;
		/*if(empty($goods['dp_id']))return $arr;
		$dp=zfun::f_row("Dp","dp_id='".$goods['dp_id']."'","name,dp_id,logo,type,data");
		if(empty($dp))return $arr;*/
		//$data=json_decode($dp['data'],true);
		$data=array();
		if(empty($data['bbms']))$data['bbms']='5.0';
		if(empty($data['mjfw']))$data['mjfw']='5.0';
		if(empty($data['wlfw']))$data['wlfw']='5.0';
		$dp['name']=$dp['shop_title'];
		$dp['logo']=$dp['shop_img'];
		if($dp['shop_type']==1)$dp['shop_type_img']=INDEX_WEB_URL."View/index/img/appapi/comm/shop_taobao.png";
		if($dp['shop_type']==2)$dp['shop_type_img']=INDEX_WEB_URL."View/index/img/appapi/comm/shop_tmall.png";
		/*$dp['fs']=array(
			array(
				"score"=>"宝贝描述：".$data['bbms'],
				"img"=>self::score_type($data['bbms']),
			),
			array(
				"score"=>"卖家服务：".$data['mjfw'],
				"img"=>self::score_type($data['mjfw']),
			),
			array(
				"score"=>"物流服务：".$data['wlfw'],
				"img"=>self::score_type($data['wlfw']),
			),
		);*/
		$dp['fs']=array();
		unset($dp['data']);
		return $dp;

	}
	public static function getshopdetail($keyword="",$seller_id=0,$shop_id=1){
		if(empty($keyword))zfun::fecho("keyword is null");

		//防止重复调用
		if(!empty($GLOBALS['1tbapi_getshopdetail_'.$keyword]))return $GLOBALS['1tbapi_getshopdetail_'.$keyword];
		$arr=array(
			"fields"=>"user_id,shop_title,shop_type,seller_nick,pict_url,shop_url",
			"q"=>$keyword,//店铺
			"page_no"=>1,//第几页
			"page_size"=>20,//多少个
		);
		$data=tbapi::tbsend("taobao.tbk.shop.get",$arr,"tbk_shop_get_response,results,n_tbk_shop");//$data=reset($data);

		if(empty($data))return array();
		$tmp=array();
		$shop_type=array("B"=>2,"C"=>1);
		foreach($data as $k=>$v){
			$type=$shop_type[$v['shop_type']];
			if(empty($seller_id)&&$shop_id==$type)$tmp=$v;
			if($v['user_id']!=$seller_id)continue;
			$tmp=$v;
		}
		if(empty($seller_id)&&empty($tmp))$tmp=$data[0];

		$arr=array(
			"seller_id"=>$tmp['user_id'],
			"shop_title"=>$tmp['shop_title'],
			"seller_nick"=>$tmp['seller_nick'],
			"shop_img"=>$tmp['pict_url'],
			"shop_url"=>$tmp['shop_url'],
			"shop_type"=>$shop_type[$tmp['shop_type']],
		);
		$GLOBALS['1tbapi_getgoodsdetail_'.$keyword]=$arr;
		return $arr;
	}
	//店铺页面
	public function dp_index(){
		appcomm::signcheck();
		$dp_id=filter_check($_POST['dp_id']);
		$goods=array();$goods['dp_id']=$dp_id;
		$dp=self::store_msg($goods);
		zfun::fecho("店铺页面",$dp,1);
	}
	//店铺新的页面
	public function dp_newindex(){
		appcomm::signcheck();
		$shop_title=filter_check($_POST['shop_title']);
		$goods=array();$goods['shop_title']=$shop_title;
		$dp=self::store_msg($goods);
		zfun::fecho("店铺页面",$dp,1);
	}
	//店铺商品
	public function dp_goods(){
		appcomm::signcheck();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			$uid=$user['id'];
		}
		$dp_id=filter_check($_POST['dp_id']);
		$fi="goods_title,goods_img,cate_id,goods_sales,highcommission_url,shop_id,goods_price,goods_cost_price,dp_id,yhq,yhq_span,yhq_end_time,yhq_price,id,commission,fnuo_id";
		$goods=appcomm::f_goods("Goods","dp_id='$dp_id'",$fi,"tg_sort desc",filter_check($_POST),20);
		$shop_type=array("淘宝","淘宝","天猫");
		$set=zfun::f_getset("fx_goods_fl,goods_ico_one");
		$sety=zfun::f_getset("hhrapitype,fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1));
		$bili=$sety["fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1)];
		$goods=zfun::f_gethdprice($goods);
		$goods=zfun::f_fgoodscommission($goods);
		foreach($goods as $k=>$v){
			$goods[$k]['shop_type']=$shop_type[$v['shop_id']];
			$mywhere="goodsid='".$v['fnuo_id']."' AND uid=".intval($uid);
			$my=zfun::f_count("MyLike",$mywhere);
			$goods[$k]['is_mylike']=0;
			if($my>0)$goods[$k]['is_mylike']=1;
 			if(empty($set['fx_goods_fl'])){
				$commission1=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));
				$goods[$k]['fxz']="分享成单后即赚 ".$commission1."元红包";
			}
			$v['yhq_price']=floatval($v['yhq_price']);
			if(!empty($v['yhq_price']))$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($v['yhq_price']))$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";

		}
		zfun::fecho("店铺商品",$goods,1);
	}
	//动态
	public function lqss(){
		appcomm::signcheck();

		$time=time();
		$time4=strtotime(date("Y-m-d",$time));
		$time3=$time4+86400;
		$fnuo_id=filter_check($_POST['fnuo_id']);
		$dtk_goods=dtk::getgoods(str_replace(array("dtk","_"),"",$fnuo_id));
		if(!empty($dtk_goods))$fnuo_id=$dtk_goods['fnuo_id'];
		//假数据
		$set=zfun::f_getset("lqss_jsj_onoff");
		$where="(gid<>'' and uid<>0 and gid='$fnuo_id' and  time>=$time4 AND time<=$time3)";
		if(intval($set['lqss_jsj_onoff'])==1){
			$where.=" or is_jsj=1";
		}
		$count=zfun::f_count("LQSS",$where);
		$num=rand(0,$count-1);
		$data=zfun::f_select("LQSS",$where,"id,is_show,data,uid,is_jsj,time",1,$num,"time DESC");
		$data=reset($data);
		$arr=array();

		if(!empty($data)){
			if(empty($data['is_jsj'])){
				$user=zfun::f_row("User","id='".intval($data['uid'])."'","nickname,head_img");
				$time1=$time-$data['time'];
				if($time1<60)$time2=(intval($time1)+1)."秒前";
				if($time1>60)$time2=intval($time1/60)."分钟前";
				if($time1>3600)$time2=intval($time1/3600)."小时前";
				if($time1>86400)$time2=intval($time1/86400)."天前";
				$arr['str']=self::xphone($user['nickname'])."   ".$time2."领券购买这个商品";
			}else{
				$arr['str']=$data['data'];
				if(strpos($data['data'],"content")==true){

					$tmp=json_decode($data['data'],true);
					$arr['str']=$tmp['content'];
					$user['head_img']=$tmp['img'];
				}
			}
			$head_img=$user['head_img'];
			if(empty($head_img))$head_img="default.png";
			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;
			$arr['head_img']=$head_img;

		}
		zfun::fecho("领券动态",$arr,1);
	}
	//点击立即领取
	public function click_yhq(){
		appcomm::signcheck();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			$uid=$user['id'];
		}
		$fnuo_id=filter_check($_POST['fnuo_id']);
		if(empty($uid))zfun::fecho("");
		if(empty($fnuo_id))zfun::fecho("");
		$arr=array(
			"uid"=>$uid,
			"gid"=>$fnuo_id,
			"time"=>time(),
		);
		zfun::f_insert("LQSS",$arr);
		zfun::fecho("",1,1);
	}
	public static function xphone($phone=''){
		$phone.="";
		$len=strlen($phone);
		if($len>=11){
			return mb_substr($phone,0,3,"utf-8")."******".mb_substr($phone,-2,2,"utf-8");
		}
		if($len>=5){
			return mb_substr($phone,0,2,"utf-8")."***".mb_substr($phone,-1,1,"utf-8");
		}
		return mb_substr($phone,0,1,"utf-8")."*";

	}
	//相似商品
	public static function xggoods_msg($goods){
		if(empty($goods))return array();
		$cid=intval($goods['cate_id']);
		$count=strlen($goods['goods_title']);
		//$title=mb_substr($goods['goods_title'],intval(ceil($count/8)-2),5,"utf-8");
		$rand=rand(1,5);
		$goods_title=mb_substr($goods['goods_title'],$rand,4,'utf-8');
		$where="shop_id<>4 and goods_title LIKE '%".$goods_title."%'";
		//if(!empty($title))$where.=" and goods_title LIKE '%".$title."%'";
	//	if(!empty($cid))$where.=" and cate_id='$cid'";

		$num=zfun::f_count("Goods",$where);
		$rand=mt_rand(0,$num);
		if(empty($num))return array();
		$fi='id,fnuo_id,goods_sales,goods_type,shop_id,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,shop_id,highcommission_wap_url,yhq_price,yhq_url,stock,yhq,yhq_span';
		$data = zfun::f_select("Goods", $where,$fi,10, $rand,"id asc");
		$data=zfun::f_gethdprice($data);
		$data=zfun::f_fgoodscommission($data);
		foreach($data as $k=>$v){
			if(empty($v['shop_id']))$data[$k]['shop_id']=1;
			$data[$k]['id']=$v['fnuo_id'];
			$data[$k]['yhq_span']=$v['yhq_price']."元券";
			$data[$k]['zhe']=$v['zhe']."折";
			$data[$k]['yhq_price']=floatval($v['yhq_price']);
			$data[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($v['yhq_price']))$data[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";

		}
		return $data;
	}
	public static function score_type($data){
		$data=floatval($data);
		if($data>=5)$img=INDEX_WEB_URL."View/index/img/appapi/comm/shop_appraise_high.png";
		else if($data>=3)$img=INDEX_WEB_URL."View/index/img/appapi/comm/shop_appraise_flat.png";
		else if($data>=0)$img=INDEX_WEB_URL."View/index/img/appapi/comm/shop_appraise_low.png";
		return $img;
	}




	public static function get_bc_detail($fnuo_id){
		fun("bcapi");
		$arr=array(
			"item_id"=>$fnuo_id,
			"fields"=>"seller,item,price,delivery,skuBase,skuCore,trade,feature,props,debug",
		);

		$tmp=bcapi::tbsend("taobao.item.detail.get",$arr,"item_detail_get_response,data");
		//fpre($tmp);exit;
		//exit;
		//$tmp=json_decode($tmp,true);
		//因为不能直接解析 所以唯有这样

		if(strstr($tmp,'基本信息":[')==false)return array();
		//$store=json_decode("{".self::getinstr($tmp,'"seller":{','}')."}",true);

		$url=self::getinstr($tmp,'"taobaoDescUrl":"','"');
		//参数
		$canshu="[".self::getinstr($tmp,'基本信息":[',']')."]";
		$canshu=json_decode($canshu,true);
		$arr=array();
		foreach($canshu as $k=>$v){
			$arr[$k]=array();
			foreach($v as $k1=>$v1){
				$arr[$k]['name']=$k1;
				$arr[$k]['val']=$v1;
			}

		}
		//图片
		$img=json_decode('['.self::getinstr($tmp,'"images":[',']').']',true);
		foreach($img as $k=>$v){
			$img[$k]=$v."_500x500.jpg";
		}
		$data=array();
		$data['url']=$url;
		$data['img']=$img;
		$data['canshu']=$arr;

		$tmp=curl_get('https://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7Bitem_num_id%3A"'.$fnuo_id.'"%7D&type=jsonp&dataType=jsonp');

		if(strstr($tmp,"接口调用成功")==false){return $data;
			zfun::fecho("error newgoods_detail ".__LINE__);
		}

		$tmp="[".self::getinstr($tmp,'"images":[',']').']';
		$detail_img=json_decode($tmp,true);
		foreach($detail_img as $k=>$v){

			//$detail_img[$k]=$v."_500x500.jpg";
			if(strstr($v,".gif"))unset($detail_img[$k]);
			else $detail_img[$k]=$v;
		}
		$data['detail_img']=array_values($detail_img);

		//fpre($detail_img);exit;
		return $data;

	}
	public static function getinstr($str='',$str1='',$str2=''){
		$tmp=explode($str1,$str);
		if(empty($tmp[1]))$tmp[1]='';
		$tmp=explode($str2,$tmp[1]);
		return $tmp[0];
	}
}
?>



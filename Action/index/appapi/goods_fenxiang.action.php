<?php
actionfun("appapi/dgappcomm");actionfun("appapi/appHhr");fun("comm");

class goods_fenxiangAction extends Action{
	public function index(){
		$user=appcomm::signcheck();
		appcomm::read_app_cookie();
		
		$set=zfun::f_getset("goods_share_fontcolor,android_url,fxdl_hhrshare_onoff,app_goods_fenxiang_str1,app_goods_fenxiang_str2,taobaopid,ggapitype,tb_gy_api_onoff,tb_wl_gy_api_onoff,share_host");
		//分享赚判断
		self::fenxiang_onoff($set,$user);	

		$pset=appcomm::parametercheck("fnuo_id");
		$fnuo_id=$pset['fnuo_id'];
		$tg_pid="";
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".$_POST['token']."'");
			$uid=$user['id'];
			if(!empty($user)&&!empty($user['tg_pid'])){
				zfun::$set['pid']=$user['tg_pid'];
				$tmp=explode("_",$set['taobaopid']);
				$GLOBALS['taobaopid']=$set['taobaopid']=$tg_pid=$tmp[0]."_".$tmp[1]."_".$tmp[2]."_".$user['tg_pid'];
			}	
			$tid=self::getApp('Tgidkey')->addkey($uid);
			if(!empty($user['tg_code']))$tid=$user['tg_code'];
			
		}
		if(empty($tid))$tid=0;
		$garr=array();
		$garr['yhq_span']="";
		$garr['yhq_price']=0;

		actionfun("comm/tbmaterial");
		$goods=tbmaterial::id($pset['fnuo_id']);

		//fpre($goods);
		

	
		$ku_goods=zfun::f_row("Goods","fnuo_id='".$pset['fnuo_id']."'");
		
		$fnuo_url="";
		if(!empty($ku_goods)&&empty($GLOBALS['taobaopid'])){
			if(!empty($ku_goods['highcommission_url']))$fnuo_url=$garr['highcommission_url']=$ku_goods['highcommission_url'];
			if(!empty($ku_goods['yhq_url']))$fnuo_url=$garr['yhq_url']=$ku_goods['yhq_url'];
			if(!empty($ku_goods['buy_url']))$fnuo_url=$garr['buy_url']=$ku_goods['buy_url'];
		}

		
		
		if(empty($goods))zfun::fecho("商品无效");
		$fnuo_id=$garr['fnuo_id']=$pset['fnuo_id'];
		if($set['ggapitype']!=2){
			$garr['goods_title']=$goods['title'];
			$garr['goods_price']=$goods['zkPrice'];
			$garr['goods_cost_price']=$goods['zkPrice'];
			$garr['goods_img']="https:".$goods['pictUrl']."_300x300.jpg";
			$garr['commission']=floatval($goods['tkRate']);
			$garr['yhq_span']=$goods['couponInfo'];
			$garr['yhq_price']=floatval($goods['couponAmount']);
		}
		else{
			$garr['goods_title']=$goods['goods_title'];
			$garr['goods_price']=$goods['goods_price'];
			$garr['goods_cost_price']=$goods['goods_cost_price'];
			$garr['goods_img']=str_replace("_250x250.jpg","",$goods['goods_img'])."_300x300.jpg";
			$garr['commission']=$goods['commission'];
			$garr['yhq_span']=$goods['yhq_span'];
			$garr['yhq_price']=$goods['yhq_price'];
		}
		
		
		//if(empty($fnuo_url)){
		actionfun("default/gototaobao");
		
		//$yhq_url=gototaobaoAction::check_yhq_url($garr,1);

		

		//高佣转链
		if($set['tb_gy_api_onoff']==1){
			actionfun('comm/tb_gy_api');
			$yhq_url=tb_gy_api::get_coupon($fnuo_id);
		}
		//物料高佣转链
		if($set['tb_wl_gy_api_onoff']==1){
			actionfun("comm/tb_wl_gy_api");
			$yhq_url=tb_wl_gy_api::get($fnuo_id,$goods['goods_title']);
		}
		
		
		if(!empty($yhq_url))$garr['yhq_url']=$fnuo_url=$yhq_url;
		//}
		
		/*if(empty($fnuo_url)){
			$fnuo_url="http://item.taobao.com/item.htm?id=".$fnuo_id;	
			if(!empty($user['tg_pid']))$fnuo_url.="&pid=".$user['tg_pid'];
		}*/
		actionfun("appapi/tkl");
		$tkl=tkl::gettkl($garr);//0.1
		
		if(empty($garr['yhq_price']))$con=$set['app_goods_fenxiang_str1'];
		else $con=$set['app_goods_fenxiang_str2'];
		$url4=$set['android_url'];
		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";
		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$pset['fnuo_id'],'tgid' => $tid),'new_share');
		$url2=self::getUrl("down","supdownload",array('tgid' => $tid),"appapi");/*更换*/
		$url1=self::getUrl("rebate_DG","rebate_detail",array("is_goods_share"=>1,"fnuo_id"=>$pset['fnuo_id'],'tgid' => $tid),"wap");
		//新商品详情
		$goods_down_url=self::getUrl("rebate_DG","rebate_detail",array("type"=>'down',"is_goods_share"=>1,"fnuo_id"=>$pset['fnuo_id'],'tgid' => $tid),"wap");
	
		if(!empty($set['share_host'])){
			$url1=str_replace(HTTP_HOST,$set['share_host'],$url1);
			$url2=str_replace(HTTP_HOST,$set['share_host'],$url2);
			$url3=str_replace(HTTP_HOST,$set['share_host'],$url3);
			$goods_down_url=str_replace(HTTP_HOST,$set['share_host'],$goods_down_url);
		}
		
		$settt=zfun::f_getset("tg_durl,is_openbd");

		
		if(intval($settt['tg_durl'])==1){
			//$url3=INDEX_WEB_URL."new_share-".$tid."-".$pset['fnuo_id']."-1.html";
			//$url2=INDEX_WEB_URL."down_supdownload_appapi.html";
		//	$url1=INDEX_WEB_URL."rebate_rebateShareDetail_wap-".$pset['fnuo_id']."-".$tid.".html";
			if(!empty($settt['is_openbd']))$bd1="http://fanyi.baidu.com/transpage?query=".urlencode($url1)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				//$url1=INDEX_WEB_URL."rebate-".$pset['fnuo_id']."-".$tid.".html";
				$bd1=$url1;
			}
			
			//$url1=appHhrAction::bdurl($bd1);
			if(!empty($settt['is_openbd']))$bd2="http://fanyi.baidu.com/transpage?query=".urlencode($url2)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				$bd2=$url2;
			}
			
			//$url2=appHhrAction::bdurl($bd2);
			if(!empty($settt['is_openbd']))$bd3="http://fanyi.baidu.com/transpage?query=".urlencode($url3)."&source=url&ie=utf8&from=en&to=zh&render=1";
			else{
				$bd3=$url3;
			}
			//$url3=appHhrAction::bdurl($bd3);
			$arrulr=appHhrAction::bdurl($bd1,$bd2,$bd3,$goods_down_url);//0.13
			
			$url1=$arrulr[0];
			$url2=$arrulr[1];
			$url3=$arrulr[2];
			$goods_down_url=$arrulr[3];
		}
		
		
		$url=$url1;
		
		if(intval($set['app_goods_tw_url'])==1){
			$url=$url2;
		}
		if(intval($set['app_goods_tw_url'])==2){
			$url=$url3;
		}
		if(intval($set['app_goods_tw_url'])==3){
			$url=$url4;
		}
		if(intval($set['app_goods_tw_url'])==4){
			$url=$goods_down_url;
		}
		$data=array();
		$getgoodstype=filter_check($_POST['getGoodsType']);
		if($_POST['getGoodsType']=='dtk')$_POST['getGoodsType']='';
		if(empty($getgoodstype)||$getgoodstype=='dtk'){	
			if(!empty($GLOBALS['yhq_price']))$garr['yhq_price']=$GLOBALS['yhq_price'];
			if(!empty($GLOBALS['yhq_span']))$garr['yhq_span']=$GLOBALS['yhq_span'];
			if(!empty($GLOBALS['goods_cost_price']))$garr['goods_cost_price']=$GLOBALS['goods_cost_price'];
			if(!empty($GLOBALS['goods_price']))$garr['goods_price']=$GLOBALS['goods_price'];
		}
		$garr=zfun::f_fgoodscommission(array($garr));
		
		$garr=appcomm::goodsfeixiang(($garr));
		$garr=appcomm::goodsfanlioff(($garr));
		
		$garr=reset($garr);/*boom*/
		if(floatval($garr['yhq_price'])==0)$garr['goods_cost_price']=$garr['goods_price'];
		$con=str_replace(array("￥"),"",$con);
		$con=str_replace("{商品标题}",$garr['goods_title'],$con);
		$con=str_replace("{价格}",$garr['goods_cost_price'],$con);
		$con=str_replace("{券后价}",$garr['goods_price'],$con);
		$con=str_replace("{淘口令}",$tkl,$con);
		$con=str_replace("{应用宝下载链接}",$url4,$con);
		$con=str_replace("{下载链接}",$url2,$con);
		$con=str_replace("{商品链接}",$url1,$con);
		$con=str_replace("{商品与下载链接}",$goods_down_url,$con);
		$con=str_replace("{邀请注册链接}",$url3,$con);
		$con=str_replace("{邀请码}",$tid,$con);
		$commission=self::get_user_bili($garr);
		$con=str_replace("{自购佣金}",$commission,$con);
		//判断是否
		//fpre($goods);
		
		fun("tbapi");
		//fpre($garr);
		
		//调用淘宝api详情
		
		$arr=array(
			"fields"=>"num_iid,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url",
			"num_iids"=>$fnuo_id,
		);
		
		
		//获取商品 简版信息接口
		/*$tmp_goods=tbapi::tbsend("taobao.tbk.item.info.get",$arr,"tbk_item_info_get_response,results,n_tbk_item");
		
		if(empty($tmp_goods))zfun::fecho("error");

		$tmp_goods=reset($tmp_goods);
		$goods_img=$tmp_goods['small_images']['string'];
		if(empty($goods_img))$goods_img=array();
		foreach($goods_img as $k=>$v){
			$goods_img[$k]=$v."_400x400.jpg";
		}
		
		$garr['goods_img']=$goods_img;*/
		//actionfun("appapi/newgoods_detail");
	//	$detail=newgoods_detailAction::get_bc_detail($fnuo_id);//0.3 oh my god
		
		//$goods_img=$garr['goods_img'];
		$goods_img=self::get_bc_img_list($fnuo_id);
		$garr['goods_img']=array();
		
		if(!empty($detail['img']))$garr['goods_img']=$detail['img'];//商品图片集
		if(empty($detail['img'])&&!empty($garr['goods_img']))$garr['goods_img'][]=$goods_img;//如果没有图片默认一张
		if(empty($garr['goods_img'])){
			$garr['goods_img']=$goods['small_img'];
			$garr['goods_img'][]=$goods['goods_img'];
			foreach($garr['goods_img'] as $k=>$v){
				$garr['goods_img'][$k]=str_replace("_250x250.jpg","_500x500.jpg",$v);
			}
		}
		foreach($garr['goods_img'] as $k=>$v){
			
			$garr['goods_img'][$k]=INDEX_WEB_URL."?mod=appapi&act=appHhr&ctrl=getcode&img=".urlencode($v)."&fnuo_id=".$fnuo_id."&getGoodsType=".$_POST['getGoodsType']."&token=".$_POST['token'];
			if(!empty($set['share_host'])){
				$garr['goods_img'][$k]=str_replace(HTTP_HOST,$set['share_host'],$garr['goods_img'][$k]);
			}

		}
		$garr['goods_share_img']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_share_img.png?time=".time();
		$garr['goods_share_fontcolor']='FFFFFF';
		if(!empty($set['goods_share_fontcolor']))$garr['goods_share_fontcolor']=$set['goods_share_fontcolor'];
		$garr['str']=$con;
		$garr['getGoodsType']=$_POST['getGoodsType'];
		if($garr['getGoodsType']=='')$garr['getGoodsType']='dtk';

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
		$garr["hs_bili"] =  sprintf("%.2f", $garr['goods_price'] * ($garr['commission']/100) * $hs_bili);
		$garr['fxz'] = str_replace($garr['fx_commission'], $garr["hs_bili"], $garr['fxz']);
		$garr['fx_commission'] = $garr["hs_bili"];

		appcomm::set_app_cookie($garr);
		zfun::fecho("分享",$garr,1,1);
		//fpre($arr);
	}
	//普通用户比例
	static function get_user_bili($goods=array()){
		$dian=100;
		$set=zfun::f_getset("operator_onoff");
		$set['operator_onoff']=intval($set['operator_onoff']).'';
		$is_sqdl=0;
		if($set['operator_onoff']=='1'){//如果开了 推广模式
			$bili=floatval($GLOBALS['action']->getSetting("fxdl_tjhy_bili1_".intval($is_sqdl+1)))/100;	
		}
		if($set['operator_onoff']=='0'){//默认模式
			$bili=floatval($GLOBALS['action']->getSetting("fxdl_gwbili".intval($is_sqdl+1)))/100;
		}
		if($set['operator_onoff']=='2'){//双轨模式
			actionfun("comm/twoway");$twoset=twoway::set();
			$one_set=$twoset[intval($is_sqdl).''];
			$bili=doubleval($one_set['自购比例'])/100;
		}
		
		$commission=round($goods['goods_price']*($goods['commission']/100)*$bili*$dian)/$dian;
		return $commission;
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
			$img_list[$k]=$v."_300x300.jpg";
		}
		return $img_list;
	}

	//分享赚判断
	public static function fenxiang_onoff($set=array(),$user=array()){
		$fxdl_hhrshare_onoff=intval($set['fxdl_hhrshare_onoff']);
		if(!empty($user)&&$user['operator_lv'].''!='0')return;
		if($fxdl_hhrshare_onoff==0&&intval($user['is_sqdl'])<=0){
			zfun::fecho("请升级更高等级享受分享赚");
		}
		if(intval($user['hhr_gq_time'])>0&&$user['hhr_gq_time']<time()){
			zfun::fecho("请续费会员享受分享赚");
		}
	}
	
	public function tdj(){
		if(empty($_GET['fnuo_id']))zfun::fecho("error");
		$_GET['fnuo_id']=str_replace(array("[","]"),"",$_GET['fnuo_id']);
		$uid=self::getUserId();
		$set=zfun::f_getset("taobaopid,un_setting_tdj,almm_tongxun_onoff,tb_gy_api_onoff,tb_wl_gy_api_onoff");
		
		//如果开启 高佣转链 关闭通讯
		if($set['tb_gy_api_onoff']==1){$set['almm_tongxun_onoff']=0;}//关闭通讯	

		//如果开启 物料高佣转链 关闭通讯
		if($set['tb_wl_gy_api_onoff']==1){$set['almm_tongxun_onoff']=0;}//关闭通讯	

		
		$tg_pid='';
		if(!empty($uid)){
			$user=zfun::f_row("User","id=".$uid);
			$tmp=explode("_",$set['taobaopid']);
			if(!empty($user)&&!empty($user['tg_pid'])){
				$GLOBALS['taobaopid']=$set['taobaopid']=$tmp[0]."_".$tmp[1]."_".$tmp[2]."_".$user['tg_pid'];
				$tg_pid=$user['tg_pid'];
			}
			
		}
		$gids=explode(",",$_GET['fnuo_id']);
		self::assign("gids",$gids);
		self::assign("taobaopid",$set['taobaopid']);
		$tdj=$set['un_setting_tdj'];
		if(!empty($tg_pid)){
			$tmp_pid=self::getin($tdj,'pid: "','"');
			$tdj=str_replace($tmp_pid,$set['taobaopid'],$tdj);	
		}
		
		// jj explosion
		self::assign("almm_tongxun_onoff",intval($set['almm_tongxun_onoff']));
		self::assign("host",md5(str_replace("www.","",INDEX_WEB_URL)."我要加密了"));
		self::assign("token",self::gettoken());
		self::assign("pid",$GLOBALS['taobaopid']);
		
		
		self::assign("tdj",$tdj);
		self::assign("tg_pid",$tg_pid);
		self::assign("tb_gy_api_onoff",$set['tb_gy_api_onoff']);
		zfun::f_play();
	}
	static function gettoken(){
		$str=INDEX_WEB_URL;
		foreach($_COOKIE as $k=>$v)$str.=$k.$v.time();
		return md5($str);
	}
	
	//淘点金 记录 用户淘口令
	public function tdj_doing(){
		if(empty($_POST['submit_']))zfun::fecho("error");
		$data=array();
		$n=-1;
		foreach($_POST as $k=>$v){
			if(strstr($k,"g_url_")==false)continue;
			$n++;
			$tmp=explode("_",$k);
			$data[$n]['fnuo_id']=$tmp[2];
			$url=urldecode(self::getin($v,'&f=','&'));
			$url=str_replace("http://s.click.taobao.com","https://s.click.taobao.com",$url);
			$data[$n]['url']=$url;
			$data[$n]['pid']=$_POST['tg_pid'];
			$data[$n]['time']=time();
		}
		foreach($data as $k=>$v){
			$where="fnuo_id='".$v['fnuo_id']."' and pid='".$v['pid']."'";
			$tmp=zfun::f_count("Dlgoodsurl",$where);
			if(empty($tmp)){
				zfun::f_insert("Dlgoodsurl",$v);	
			}
			else{
				zfun::f_update("Dlgoodsurl",$where,$v);	
			}
		}
		zfun::fecho("ok",1,1);
	}
	
	//通讯 记录 用户淘口令
	public function tx_doing(){
		if(empty($_POST['submit_']))zfun::fecho("error");
		if(empty($_POST['tkl'])||empty($_POST['fnuo_id'])||empty($_POST['url']))zfun::fecho("error`");
		
		$arr=array(
			"tkl"=>filter_check($_POST['tkl']),
			"fnuo_id"=>filter_check($_POST['fnuo_id']),
			"url"=>filter_check($_POST['url']),
			"pid"=>$_POST['tg_pid'].'',
			"time"=>time(),
		);
		
		$where="fnuo_id='".$arr['fnuo_id']."' and pid='".$arr['pid']."'";
		$tmp=zfun::f_count("Dlgoodsurl",$where);
		if(empty($tmp)){
			zfun::f_insert("Dlgoodsurl",$arr);	
		}
		else{
			zfun::f_update("Dlgoodsurl",$where,$arr);	
		}
		zfun::fecho("ok",1,1);
	}
	
	public static function getin($str='',$str1='',$str2=''){
		$tmp=explode($str1,$str);
		$tmp=explode($str2,$tmp[1]);
		return $tmp[0];
	}
	
	public function m1(){
		$url="http://redirect.simba.taobao.com/rd?&f=http%3A%2F%2Fs.click.taobao.com%2Ft%3Fe%3Dm%253D2%2526s%253De2oxKI47bpwcQipKwQzePOeEDrYVVa64yK8Cckff7TVuwRIiPOGbYEgYf%252BPR8O59J1gyddu7kN93nuN9fdBK0g352j6Gg0dyPlnJvOI4YiEdw%252FbikFq0wTapFiz3khE6msmAHdEykQ5xiZWw8aT18GOeGjYhto2gaJQbeGjLi%252FSiZ%252BQMlGz6FQ%253D%253D&k=7ca9e08409870ccd&p=mm_13406079_15484476_59734010&pvid=3da8bfb35a9659427c38077075a58d26&posid=&b=display_1_4_0_0_0&w=unionapijs&c=un";	
		echo urldecode($url);
	}
	
}
?>
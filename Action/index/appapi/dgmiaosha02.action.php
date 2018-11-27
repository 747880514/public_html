<?php
include_once ROOT_PATH."comm/zfun.php";
include_once ROOT_PATH.'Action/index/appapi/dgappcomm.php';

actionfun("appapi/baili");//百里

class dgmiaosha02Action extends Action{
	public function index(){
		appcomm::signcheck();
		$set=zfun::f_getset("app_miaosha_title,app_miaosha_time,app_miaosha_title2");
		$set['app_miaosha_time']=self::gettimestatus($set['app_miaosha_time']);
		zfun::fecho("秒杀",$set,1);
	}
	public static function gettimestatus($app_miaosha_time){
		$tmp=explode(",",$app_miaosha_time);
		$time=time();
		$today=date("Y-m-d ",time());
		$todaytime=strtotime("today");
		$arr=array();
		foreach($tmp as $k=>$v){
			$arr[$k]=array();
			$arr[$k]['time']=$v;
			$tmptime=strtotime($today.$v);
			$arr[$k]['start_time']=$tmptime;
		}
		foreach($arr as $k=>$v){
			if(!empty($arr[$k+1]['start_time']))$arr[$k]['end_time']=$arr[$k+1]['start_time'];
			else $arr[$k]['end_time']=$todaytime+86400;
			$start_time=$arr[$k]['start_time'];
			$end_time=$arr[$k]['end_time'];
			if($start_time<$time && $end_time >$time)$arr[$k]['str']="疯抢中";
			if($start_time>$time)$arr[$k]['str']="即将开始";
			if($end_time<$time)$arr[$k]['str']="疯抢中";
		}
		return $arr;
	}
	//公共
	static function comm_time(){
		$set=zfun::f_getset("app_miaosha_time");
		$arr=explode(",",$set['app_miaosha_time']);
		$time1=date("H:i",time());
		foreach($arr as $k=>$v){
			if($v<=$time1){
				$arr1=$v;
				$end_time=date("Y-m-d",time())." 23:59";
				if(!empty($arr[$k+1])){
					$end_time=date("Y-m-d",time())." ".$arr[$k+1];
				}
				$tmp1['end_time']=strtotime($end_time);
			}
		}
		if($time1<=$arr[0]){
			$arr1=$arr[0];
			$end_time=date("Y-m-d",time())." ".$arr[0];
			$tmp1['end_time']=strtotime($end_time);
		}
		$tmp1['str_index']="限时限量抢购";
		$web=zfun::f_row("Webset","var='qg_count'","val");
		$tmp1['qg_count']=intval($web['val'])."人正在抢购";
		$_POST['time_']=$GLOBALS['time_']=$arr1;
		$tmp=self::gettimestatus($set["app_miaosha_time"]);
		$arr=array();
		foreach($tmp as $k=>$v){$arr[$v['time']]=$v;}
		$timeset=$arr[$_POST['time_']];
		$GLOBALS['start_time']=$timeset['start_time'];
		return $tmp1;
	}
	//秒杀商品
	public function getgoods(){
		appcomm::signcheck();
		//首页时返回倒计时
		if(intval($_POST['is_index'])==1){

			$tmp1=self::comm_time();
		}

		//appcomm::parametercheck("time_");
		$set=zfun::f_getset("app_V,app_miaosha_time");
		//版本一样时没有商品  ios审核用
		if(!empty($_POST['app_V'])&&$set['checkVersion']==$_POST['app_V'])zfun::fecho("导购秒杀商品",array(),1);


		$uid=0;
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'","id,is_sqdl");
			$uid=intval($user['id']);
		}
		$tmp=self::gettimestatus($set["app_miaosha_time"]);
		$arr=array();
		foreach($tmp as $k=>$v){$arr[$v['time']]=$v;}
		$timeset=$arr[$_POST['time_']];
		if(empty($timeset))zfun::fecho("error");
		if($timeset['str']=='疯抢中')$timeset['str']="马上抢";
		if($timeset['str']=='即将开始')$timeset['str']="未开始";
		$_GET['p']=intval($_POST['p']);if(empty($_GET['p']))$_GET['p']=1;
		$start_time=$timeset['start_time'];

		$where="start_time =$start_time";
		$fi="id,fnuo_id,goods_title,goods_img,goods_price,goods_cost_price,goods_sales,shop_id,goods_type,stock,commission,yhq_span,yhq,yhq_url,yhq_price,(goods_price - yhq_price) as qh_money";
		$goods=zfun::f_goods("Goods",$where,$fi,"start_time asc,goods_sales desc",filter_check($_GET),20);
		//$remind=zfun::f_kdata("Remind",$goods,'id',"gid","id,gid","uid=$uid");
		$goods=appcomm::gethdprice($goods);
		$goods=zfun::f_fgoodscommission($goods);
		foreach($goods as $k=>$v){
			$goods[$k]['is_start']=0;
			if($timeset['str']=='马上抢')$goods[$k]['is_start']=1;
			if($v['shop_id']==4)$goods[$k]['shop_id']=3;
			$goods[$k]['jd_url']='';
			if($goods[$k]['shop_id']==3)$goods[$k]['jd_url']=INDEX_WEB_URL."?act=jdapi&ctrl=gotobuy&gid=".$v['fnuo_id'];;
			$goods[$k]['ds_price']=$v['qh_money'];
			if($timeset['str']=='提醒我'){
				$goods[$k]['tixing']=0;
				$goods[$k]['str2']=$_POST['time_']."准时开抢";

			}
			if($timeset['str']=="马上抢"){

				$goods[$k]['str2']="已抢".$goods[$k]['jindu']."%";
			}
			$goods[$k]['jindu']=intval(($v['goods_sales']/$v['stock'])*100)/100;
			$goods[$k]['str']=$timeset['str'];
			//if(!empty($remind[$v['id']]))$goods[$k]['str']="已提醒";
			$goods[$k]['is_start']=0;
			if($goods[$k]['str']=='马上抢')$goods[$k]['is_start']=1;

			$goods[$k]['miaosha_goods_img']=INDEX_WEB_URL."View/index/img/appapi/comm/miaosha_goods_img.png?time=".time();
			if(empty($v['yhq_span']))$goods[$k]['yhq_span']=intval($v['yhq_price'])."元";
			unset($goods[$k]['detailurl'],$goods[$k]['fbili'],$goods[$k]['zhe'],$goods[$k]['fcommissionshow']);
		}
		appcomm::goodsfeixiang($goods);
		appcomm::goodsfanlioff($goods);

		//百里
		$goods = baili::hs_commission($goods);

		zfun::fecho("导购秒杀商品",$goods,1);
	}
}
//im going to explosion
?>
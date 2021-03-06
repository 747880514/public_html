<?php
fun("zfun");
actionfun("appapi/dgappcomm");
actionfun("appapi/tzbs_new");

class appDiyIndexAction extends Action{
	//首页数据
	public function getIndex(){
		appcomm::signcheck();
		$type='index';
		$set=zfun::f_getset($type."_style_id");
		$index_style_id=$set[$type.'_style_id'];
		$arr=self::getCSS($type);
		$arr=array_values($arr);

		//百里
		//外链带token，实现H5页面获取会员信息
		foreach ($arr as $key => &$value)
		{
			$carry = array('index_huandengpian_01','index_kuaisurukou_01');
			if(in_array($value['type'], $carry))
			{
				foreach ($value['list'] as $k => &$v)
				{
					if($v['SkipUIIdentifier'] == 'pub_wailian')
					{
						$guanlian = strstr($v['url'],'?') ? '&' : '?';
						$v['url'] .= $guanlian."token=".filter_check($_POST['token']);
					}
				}
			}
		}

		echo str_replace('"success":1','"success":"1"',json_encode(array("msg"=>"首页数据","style_id"=>$index_style_id,"data"=>$arr,"success"=>1))); exit;
	}
	//跑马灯
	public function super_msg(){
		appcomm::signcheck();
		$set=zfun::f_getset("pmd_shuju_onoff");
		if(empty($set['pmd_shuju_onoff']))$inter=self::mingxi();
		else $inter=self::xunimingxi();
		zfun::fecho("超级快报",$inter,1);
	}
	//虚拟数据
	public static function xunimingxi(){
		$id=intval($_POST['id']);
		$count=zfun::f_count("IndexPmd","id>'$id' and id<>0 ");
		$where='id>0';
		$zj_count=zfun::f_count("IndexPmd",$where);
		if(empty($count)){
			$rand=rand(0,$zj_count-1);
		}else $where.=" and id>='$id'";
		$inter=zfun::f_select("IndexPmd",$where,"id,content,time",100,$rand,"time desc");
		$inter=self::sortarr($inter,"time","asc");
		foreach($inter as $k=>$v){
			$inter[$k]['detail']=$v['content'];
			$inter[$k]['interal']='';
		}
		return $inter;	
	}
	//明细数据
	public static function mingxi(){
		$id=intval($_POST['id']);
		$set=zfun::f_getset("qb_kb_day");
		if(empty($set['qb_kb_day']))$set['qb_kb_day']=7;
		$time=strtotime("today")-intval($set['qb_kb_day'])*86400;
		$count=zfun::f_count("Interal","time >$time and id>'$id' and id<>0 and uid<>0");
		$where="time >$time and uid<>0  ";
		//$where.=" and detail NOT LIKE '%签到%' and detail NOT LIKE '%升级成为%' and detail NOT LIKE '%恭喜成为%' and detail NOT LIKE '%积分%'";
		$zj_count=zfun::f_count("Interal",$where);
		if(empty($count)){
			$rand=rand(0,$zj_count-1);
		}else $where.=" and id>='$id'";
		$inter=zfun::f_select("Interal",$where,"id,interal,detail,data,uid,time",100,$rand,"time desc");
		$inter=self::sortarr($inter,"time","asc");
		$user=zfun::f_kdata("User",$inter,"uid","id","id,nickname,phone");
		foreach($inter as $k=>$v){
			$nickname=$user[$v['uid']]['nickname'];
			if(empty($nickname))$nickname=$user[$v['uid']]['phone'];
			if(strstr($v['detail'],"提币")==true){
				$v['detail']="提币 ".abs($v['interal'])." 元";
			}
			$inter[$k]['detail']=self::xphone($nickname)." ".$v['detail'];
			unset($inter[$k]['data'],$inter[$k]['uid']);
		}
		return $inter;	
	}
	//获取样式
	public static function getCSS($type='index'){
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			$uid=intval($user['id']);
			if(empty($uid))zfun::fecho("用户不存在，请重新登录");
		}
		$where="is_show=1 and id>0 and web_type='".$type."'";
		$arr=zfun::f_select("IndexModel",$where,"type,data,mac,jiange",0,0,"sort desc");
		$guanggao=zfun::f_row("Guanggao","type ='index_cgfjx_ico' and hide=0","id,img,type");
		$ggt=array();
		$gg=zfun::f_select("Guanggao","type IN('index_ksrk','index_tw') and hide=0","img,type");
		foreach($gg as $k=>$v){
			$ggarr=explode(",",$v['img']);
			if(!empty($v['img'])&&$v['type']=='index_ksrk')$ggt['ksrk']=UPLOAD_URL."slide/".$ggarr[0];
			if(!empty($v['img'])&&$v['type']=='index_tw')$ggt['tw']=UPLOAD_URL."slide/".$ggarr[0];
		}
		$data=array();
		foreach($arr as $k=>$v){
			$v['data']=str_replace("&#34;",'"',$v['data']);
			$json_data=$v['data']=json_decode($v['data'],true);
			$arr[$k]['banner_speed']=$v['banner_speed']=$json_data['banner_speed'];
			$arr[$k]['banner_bili']=$v['banner_bili']=$json_data['banner_bili'];
			if(empty($arr[$k]['banner_speed']))$arr[$k]['banner_speed']='4000';
			if(empty($arr[$k]['banner_bili']))$arr[$k]['banner_bili']='0.52';
			$arr[$k]['img']='';
			if(!empty($guanggao['img']))$arr[$k]['img']=UPLOAD_URL."slide/".$guanggao['img'];
			$arr[$k]['ksrk']=$ggt['ksrk'];
			$arr[$k]['tw']=$ggt['tw'];
			$arr[$k]['list']=array();
			if(!empty($v['mac'])){
				$mac=explode(" ",$v['mac']);
				$act_path=ROOT_PATH."Action/index/".$mac[0]."/".$mac[1].".action.php";
				if(file_exists($act_path)==false){unset($arr[$k]);continue;}
				include_once $act_path;
				$tmpact= $mac[1]."Action";
				$tmp=new $tmpact();
				
				if(method_exists($tmp,$mac[2])==false){unset($arr[$k]);continue;}
				$actions=$mac[2].'';
				$tmp=$tmp->$actions($user,$v);
				$arr[$k]['list']=$tmp;
				if($v['type']==$type."_miaosha_01"){
					actionfun("appapi/dgmiaosha02");
					dgmiaosha02Action::comm_time();
					unset($_POST['time_']);
					$start_time=$GLOBALS['start_time'];
					$where="start_time ='$start_time'";
					$goods=zfun::f_count("Goods",$where);
					if(empty($goods))unset($arr[$k]);
				}
			}
			$tmp=array($type."_paomadeng_01",$type."_goods_01");//这些是app要调另一个接口的
			if(empty($v['mac'])&&!in_array($v['type'],$tmp))unset($arr[$k]);//如果没有方法名不要他
			if(empty($arr[$k]['list'])&&!in_array($v['type'],$tmp))unset($arr[$k]);//如果没有数据不要他
			unset($arr[$k]['data']);
			$data[$v['type']]=$arr[$k];
		
		}
		zfun::isoff($tmp);
		
		
		return $arr;
	}
	//以下非接口
	public static function xphone($phone = '') {
		$phone.= "";
		$len = strlen($phone);
		if ($len >= 11) {
			return mb_substr($phone, 0, 3, "utf-8") . "******" . mb_substr($phone, -2, 2, "utf-8");
		}
		if ($len >= 7) {
			return mb_substr($phone, 0, 2, "utf-8") . "***" . mb_substr($phone, -1, 1, "utf-8");
		}
	
		return mb_substr($phone, 0, 1, "utf-8") . "*";
	}
	public static function sortarr($arr=array(),$key='',$type="asc"){//二位数组排序
		$arr=array_values($arr);
		foreach($arr as $k=>$v)$arr[$k][$key]=floatval($v[$key]);
		$tmp=array();
		foreach ($arr as $k=>$v)$tmp[$k] = $v[$key];
		if($type=="desc")$type=SORT_DESC;else $type=SORT_ASC;
		array_multisort($arr,SORT_NUMERIC,$tmp,$type);
		return $arr;	
	}
	
	
	
}
?>
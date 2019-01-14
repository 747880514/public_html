<?php
include_once ROOT_PATH."Action/index/appapi/dgappcomm.php";
include_once ROOT_PATH."Action/index/appapi/appHhr.action.php";
class appFamilyAction extends Action{
	//家族成员级别
	public function lv_list(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$lv=($user['is_sqdl']+1);
		$set=zfun::f_getset("jzcy_three_name,operator_name,fxdl_tjhydj_".$lv);
		if(empty($set["fxdl_tjhydj_".$lv]))$set["fxdl_tjhydj_".$lv]=2;
		$show_lv=$set["fxdl_tjhydj_".$lv];
		$arr=array("","一","二","三","四","五","六","七","八","九","十");
		$data=array();
		$str='';
		for($i=0;$i<$show_lv;$i++){
			if(empty($str))$str="fxdl_djnames_".($i+1);
			else $str.=",fxdl_djnames_".($i+1);
		}
		$set2=zfun::f_getset($str);
		for($i=0;$i<$show_lv;$i++){
			$count=zfun::f_count("Nexus","extend_uid='{$uid}' and bili > 0 and tg_lv='".($i+1)."'");
			$data[$i]['str']=$arr[$i+1]."度成员";
			if(!empty($set2["fxdl_djnames_".($i+1)]))$data[$i]['str']=$set2["fxdl_djnames_".($i+1)];
			$data[$i]['str'].="(".$count."人)";
			$data[$i]['lv']=$i;
		}
		
		if(empty($set['jzcy_three_name'])){
			$set['jzcy_three_name']="普通会员,合伙人,运营商";	
		}
		$name_arr=explode(",",$set['jzcy_three_name']);
		
		if($user['operator_lv'].''!='0'){
			$operator=zfun::f_count("Nexus","extend_uid='{$uid}' and bili > 0 and lower_operator_lv>'0'");
			$agent=zfun::f_count("Nexus","extend_uid='{$uid}' and bili > 0 and lower_is_sqdl!='0'");
			$default=zfun::f_count("Nexus","extend_uid='{$uid}' and bili > 0 and lower_is_sqdl='0' and lower_operator_lv='0'");
	
			$data=array(
				array(
					"str"=>$name_arr[0]."(".$default."人)",
					"lv"=>'default',
				),
				array(
					"str"=>$name_arr[1]."(".$agent."人)",
					"lv"=>"agent",
				),
				array(
					"str"=>$name_arr[2]."(".$operator."人)",
					"lv"=>"operator",
				),
			);	
		}
		
		zfun::fecho("家族成员级别",$data,1);
	}
	
	/*一级二级成员*/
	public function myHhr(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$is_hhr=($_POST['is_hhr']);
		
		if($user['operator_lv'].''!='0'){$is_operator=1;}
		else $is_operator=0;
		//$operator_set=zfun::f_getset("");
		
		$set=zfun::f_getset("operator_name,operator_name_2,family_strs");
		$data=array();
		$data['family']=$this->getUrl("super_new","appfamily",array(),"wap");
		$data['family_strs']=$set['family_strs'];
		if(empty($data['family_strs']))$data['family_strs']='点亮星星的成员，代表已经成为联盟合伙人';
		$where="extend_uid='{$uid}' and bili > 0";
		actionfun("comm/order");
		$lv=$is_hhr+1;//我等级从0开始了
		$uids=-1;
		//这是筛选的
		if(strstr($is_hhr,"_")){
			$lvarr=explode("_",$is_hhr);
			$is_hhr=$lvarr[0];
			if($lvarr[1]=='o1'){$where.=" and lower_operator_lv='1'";}
			elseif($lvarr[1]=='o2'){$where.=" and lower_operator_lv='2'";}
			elseif($lvarr[1]>0)$where.=" and lower_is_sqdl='".$lvarr[1]."'";
			else $where.=" and lower_is_sqdl='0' and lower_operator_lv='0'";
		}
		if($is_hhr=='operator')$where.=" and lower_operator_lv>'0'";
		elseif($is_hhr=='agent')$where.=" and lower_is_sqdl!='0'";
		elseif($is_hhr=='default')$where.=" and lower_is_sqdl='0' and lower_operator_lv='0'";
		else $where.=" and tg_lv='".($is_hhr+1)."'";
		
		$sort="lower_reg_time desc";
		$nexus=appcomm::f_goods("Nexus",$where,NULL,$sort,NULL,20);
		$nexus_user=zfun::f_kdata("User",$nexus,"lower_uid","id","id,head_img,is_sqdl,nickname,reg_time,operator_lv,yq_all_count,phone,tg_pid,tb_app_pid,ios_tb_app_pid");	//百里追加
		//$hhr_next_fl=zfun::f_kdata("HhrNextJl",$user1,"id","uid","uid,sum","  extend_id='$uid'");
		foreach($nexus  as $k=>$v){
			$one_user=$nexus_user[$v['lower_uid'].''];
			$head_img=$one_user['head_img'];
			if(empty($head_img))$head_img='default.png';
			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;
			$nexus[$k]['head_img']=$head_img;
			$nexus[$k]['nickname']=appHhrAction::xphone($one_user['nickname']);
			$nexus[$k]['Vname']=self::getSetting("fxdl_name".($one_user['is_sqdl']+1));
			
			//百里.展示状态
			//仅锁粉待激活（未填写手机号）
			//未安装APP(有手机号）
			//未登录APP(无推广位）
			//待激活(有推广位无首单或订单为0
			if(empty($one_user['phone']))
			{
				$nexus[$k]['Vname'] = '未下载';//"仅锁粉";
			}
			else
			{
				if($one_user['tg_pid'] != '' || $one_user['tb_app_pid'] != '' || $one_user['ios_tb_app_pid'] != '' )
				{
					//查询有效订单
					$validorder = zfun::f_row("Order", "status != '订单失效' AND uid = '{$one_user['id']}'");
					if($validorder)
					{
						//存在
						$validorderend = zfun::f_row("Order", "status = '订单结算' AND returnstatus = 1 AND uid = '{$one_user['id']}'");
						if(!$validorderend)
						{
							$nexus[$k]['Vname'] = '已下单';//"待激活";
						}
					}
					else
					{
						$nexus[$k]['Vname'] = '未下单';//"待激活";
					}
				}
				else
				{
					$nexus[$k]['Vname'] = "未登录";
				}
			}
			//有手机号，追加手机号
			if(!empty($one_user['phone']))
			{
				$nexus[$k]['Vname'] .= '/'.$one_user['phone'];
			}
			
			if($one_user['operator_lv']=='1')$nexus[$k]['Vname']=$set['operator_name'];//运营商
			if($one_user['operator_lv']=='2')$nexus[$k]['Vname']=$set['operator_name_2'];//联合创始人
			
			$nexus[$k]['commission']=zfun::dian($v['lower_offer']);
			//$user1[$k]['count']=zfun::f_count("User","extend_id='".$v['id']."'");
			$nexus[$k]['count']=$one_user['yq_all_count'];
			$nexus[$k]['reg_time']=$v['lower_reg_time'];//注册时间
		}
		$data['fan']=$nexus;
		zfun::fecho("我的粉丝",$data,1);
		//set
	}
	//会员等级
	function user_lv(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		
		$setlv=zfun::f_getset("fxdl_lv");
		
		$is_hhr=($_POST['is_hhr']);
		$str='-1';
		for($i=1;$i<=$setlv['fxdl_lv'];$i++){$str.=",fxdl_name".$i;}
		$str=substr($str,3);
		$set=zfun::f_getset($str.",operator_onoff,operator_name,operator_name_2");
		
		$data=array();
		for($i=1;$i<=$setlv['fxdl_lv'];$i++){
			$where="extend_uid='{$uid}' and bili > 0 and lower_is_sqdl='".($i-1)."'";
			if($i==1)$where.=" and lower_operator_lv='0'";
			$where.=" and tg_lv='".($is_hhr+1)."'";
			$count=zfun::f_count("Nexus",$where);
			$data[$i]['str']=$set['fxdl_name'.$i].'('.$count.'人)';
			$data[$i]['lv']=$is_hhr."_".($i-1);
		}
		
		if($set['operator_onoff'].''=='1'){
			$where="extend_uid='{$uid}' and bili > 0 and lower_operator_lv='1'";
			$count1=zfun::f_count("Nexus",$where);
			$where="extend_uid='{$uid}' and bili > 0 and lower_operator_lv='2'";
			$count2=zfun::f_count("Nexus",$where);
			$data[$setlv['fxdl_lv']+1]['str']=$set['operator_name'].'('.$count1.'人)';
			$data[$setlv['fxdl_lv']+1]['lv']=$is_hhr."_o1";;
			$data[$setlv['fxdl_lv']+2]['str']=$set['operator_name_2'].'('.$count2.'人)';
			$data[$setlv['fxdl_lv']+2]['lv']=$is_hhr."_o2";;
		}
		$where="extend_uid='{$uid}' and bili > 0 ";
		$where.=" and tg_lv='".($is_hhr+1)."'";
		$count=zfun::f_count("Nexus",$where);
		array_unshift($data,array("str"=>"全部(".$count."人)","lv"=>$is_hhr));
		$data=array_values($data);
		
		$arr=array("default","agent","operator");
		if(in_array($is_hhr,$arr)){
			if($is_hhr=='operator')$count=zfun::f_count("Nexus","extend_uid='{$uid}' and bili > 0 and lower_operator_lv>'0'");
			if($is_hhr=='agent')$count=zfun::f_count("Nexus","extend_uid='{$uid}' and bili > 0 and lower_is_sqdl!='0'");
			if($is_hhr=='default')$count=zfun::f_count("Nexus","extend_uid='{$uid}' and bili > 0 and lower_is_sqdl='0' and lower_operator_lv='0'");
			$data=array(array("str"=>"全部","lv"=>$is_hhr));
		}
		zfun::fecho("二级等级分类",$data,1);
	}
	/*家族成员*/
	public function teamUser(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		
		$type=intval($_POST['type']);
		
		$eids=self::getc($uid,"extend_id",1);
		
		$where="id in($eids)";
		$user1=zfun::f_select("User",$where,"id,head_img,nickname");
		
		foreach($user1 as $k=>$v){
			$eids1=self::getc($v['id'],"extend_id",1);
			$hhr_next_fl=zfun::f_sum("HhrNextJl","uid IN($eids1) and  extend_id='".intval($v['id'])."'","sum");
			$head_img=$v['head_img'];
			if(empty($head_img))$head_img='default.png';
			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;
			$user1[$k]['head_img']=$head_img;
			$user1[$k]['nickname']=self::xphone($v['nickname']);
			$user1[$k]['commission']=zfun::dian($hhr_next_fl);
			if(empty($hhr_next_fl))unset($user1[$k]);
		}
		
		$user1=self::sortarr($user1,"commission","desc");
		$data=array();
		foreach($user1 as $k=>$v){
			if(count($data)==10)continue;
			$data[]=$v;
		}
		foreach($data as $k=>$v){
			$data[$k]['val']=$k+1;
			$data[$k]['img']='';
			if($k==0)$data[$k]['img']=INDEX_WEB_URL."View/index/img/wap/comm/hero_one.png";
			if($k==1)$data[$k]['img']=INDEX_WEB_URL."View/index/img/wap/comm/hero_two.png";
			if($k==2)$data[$k]['img']=INDEX_WEB_URL."View/index/img/wap/comm/hero_three.png";
		}
		if($_POST['p']>1)$data=array();
		zfun::fecho("家族",$data,1);
	}
	/*家族成员*/
	public function teamUserCount(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		
		$type=intval($_POST['type']);
		
		$eids=self::getc($uid,"extend_id",1);
		
		$where="id in($eids)";
		$user1=zfun::f_select("User",$where,"id,head_img,nickname");
		
		foreach($user1 as $k=>$v){
			$count=zfun::f_count("User","extend_id='".intval($v['id'])."'");
			$user1[$k]['count']=$count;
			$head_img=$v['head_img'];
			if(empty($head_img))$head_img='default.png';
			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;
			$user1[$k]['head_img']=$head_img;
			$user1[$k]['nickname']=self::xphone($v['nickname']);
			if(empty($count))unset($user1[$k]);
		}
		
		$user1=self::sortarr($user1,"count","desc");
		$data=array();
		foreach($user1 as $k=>$v){
			if(count($data)==10)continue;
			$data[]=$v;
		}
		foreach($data as $k=>$v){
			$data[$k]['val']=$k+1;
			$data[$k]['img']='';
			if($k==0)$data[$k]['img']=INDEX_WEB_URL."View/index/img/wap/comm/hero_one.png";
			if($k==1)$data[$k]['img']=INDEX_WEB_URL."View/index/img/wap/comm/hero_two.png";
			if($k==2)$data[$k]['img']=INDEX_WEB_URL."View/index/img/wap/comm/hero_three.png";
		}
		if($_POST['p']>1)$data=array();
		zfun::fecho("家族成员",$data,1);
	}
	/*邀请页面*/
	public function invite(){
		appcomm::signcheck();
		$settt=zfun::f_getset("tg_durl,is_openbd");
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");$uid=$user['id'];
			if(empty($user))zfun::fecho("用户不存在");
			$tgidkey = $this -> getApp('Tgidkey');
			$uid1 = $tgidkey -> addkey($uid);
			$data['share_url'] = $this -> getUrl('invite_friend', 'new_packet', array('tgid' => $uid1),'new_share');
			if(intval($settt['tg_durl'])==1){
				$url3=INDEX_WEB_URL."new_share-".$uid1."-0-0.html";
				if(!empty($settt['is_openbd']))$bd3="http://fanyi.baidu.com/transpage?query=".urlencode($url3)."&source=url&ie=utf8&from=en&to=zh&render=1";
				else{
					$bd3=$url3;
				}
				$data['share_url']=appHhrAction::bdurl($bd3);
			}
			
		}
		/*背景图片*/
		$set=zfun::f_getset("invite_bj,appshareword,userExplain,ml_shareInfo_two,appshareimg,newUserImg,oldUserImg,newUserExplain,oldUserExplain,gold,silver,copper");
		$data['shareInfo'] = $set['appshareword'];
		$data['subHead'] = $set['ml_shareInfo_two'];
		$data['shareImg']='';
		if(!empty($set['appshareimg']))$data['shareImg'] = UPLOAD_URL.'slide/'.$set['appshareimg'];
		if(!empty($set['invite_bj']))$data['invite_bj']=UPLOAD_URL."slide/".$set['invite_bj'];
		//if(!empty($set['userExplain']))$set['userExplain']=UPLOAD_URL."invite/".$set['userExplain'];
		
		if(!empty($set['newUserImg']))$data['newUserImg']=UPLOAD_URL."invite/".$set['newUserImg'];
		if(!empty($set['oldUserImg']))$data['oldUserImg']=UPLOAD_URL."invite/".$set['oldUserImg'];
		
		$count=0;$orderSum=0;
		if(!empty($uid)){
			$userCount=zfun::f_select("User","extend_id='$uid'","id");
			$ids=zfun::f_kstr($userCount);
			$count=zfun::f_count("User","extend_id='$uid'");
			$where="id IN($ids) ";
			$orderSum=zfun::f_sum("User",$where,"orderSum");
		}
		$data['inviteGains']=array(
			0=>array(
				'inviteTop'=>'已成功邀请',
				'inviteMid'=>$count,
				'inviteBtm'=>"位好友",
			),
			1=>array(
				'inviteTop'=>'好友累计存入',
				'inviteMid'=>zfun::dian($orderSum),
				'inviteBtm'=>"元",
			),
		);
		$num=20;
		
		$phuser=appcomm::f_goods("User",$where,"id,head_img,login_time,nickname,orderSum","orderSum DESC",NULL,$num);
		$hhr_next_fl=zfun::f_kdata("HhrNextJl",$phuser,"id","uid","uid,sum","  extend_id='$uid'");

		foreach($phuser as $k=>$v){
			
			$phuser[$k]['nickname']=self::xphone($v['nickname']);
			$head_img=$v['head_img'];
			if(empty($head_img))$head_img="default.png";
			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;
			$phuser[$k]['head_img']=$head_img;
			$phuser[$k]['logo']='';
			$phuser[$k]['reg_time']=self::timeStr($v['login_time']);
			unset($phuser[$k]['login_time']);
			if(floatval($v['orderSum'])==0){
				unset($phuser[$k]);
				
			}
		}
		
		$p=intval($_POST['p']);
		if(empty($p))$p=1;
		$phuser=array_values($phuser);
		foreach($phuser as $k=>$v){
			$phuser[$k]['num']=intval(($p-1)*$num+($k+1));
			$phuser[$k]['commission']=zfun::dian($hhr_next_fl[$v['id']]['sum']);
			if($k==0&&$p==1)$phuser[$k]['logo']=UPLOAD_URL."invite/".$set['gold'];
			else if($k==1&&$p==1)$phuser[$k]['logo']=UPLOAD_URL."invite/".$set['silver'];
			else if($k==2&&$p==1)$phuser[$k]['logo']=UPLOAD_URL."invite/".$set['copper'];
		}
		$data['phb']=$phuser;
		$data['phbArr']=array("名称/最后登陆","累计存入(元)","我的收益");
		
		//规则
		$yqrule=zfun::f_row("HelperArticle","type='appinvate'","content");
		$data['yqrule']=filter_check($yqrule['content']);
		if(!empty($data	)){
			appcomm::set_app_cookie(array("msg"=>"邀请好友","data"=>$data,"success"=>"1"));	
		}
		zfun::fecho("邀请好友",$data,1,1);
	}
	public static function timeStr($time){
		$nowtime=time();
		$sjc=$nowtime-$time;
		$year=60*60*24*365;
		$month=60*60*24*30;
		$day=60*60*24;
		$tim=60*60;
		$reg_time='';
		if($sjc>$year){
			$reg_time=intval($sjc/$year)."年前";
		}else if($sjc>$month){
			$reg_time=intval($sjc/$month)."个月前";
		}else if($sjc>$day){
			$reg_time=intval($sjc/$day)."天前";
		}else if($sjc>$tim){
			$reg_time=intval($sjc/$day)."个小时前";
		}else {
			$reg_time=intval($sjc/60)."分钟前";
		}
		return $reg_time;
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
	public static function sortarr($arr=array(),$key='',$type="asc"){//二位数组排序
		$arr=array_values($arr);
		foreach($arr as $k=>$v)$arr[$k][$key]=floatval($v[$key]);
		$tmp=array();
		foreach ($arr as $k=>$v)$tmp[$k] = $v[$key];
		if($type=="desc")$type=SORT_DESC;else $type=SORT_ASC;
		array_multisort($arr,SORT_NUMERIC,$tmp,$type);
		return $arr;	
	}
	public static function getc($uid, $tidname = "extend_id", $maxlv = 9) {//获取下级
		
		if (empty($uid))
			return 0;
		$arr = array();
		$arr[0] = -1;
		$lv = 0;
		$eid = 0;
		$tid = $uid;
		
		do {
			$lv++;
			$where="$tidname IN($tid) and $tidname<>0 and $tidname<>'' ";
			
			$user = zfun::f_select("User",$where,"id");
			
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
	public static function getcarr($uid, $tidname = "extend_id", $maxlv = 9,$is_sqdl=0,$is_cy=0) {//获取下级
		$maxlv++;
		if (empty($uid))return 0;
		$arr = array();
		$arr[0] = intval($uid);
		$lv = 0;
		$eid = 0;
		$tid = $uid;
			
		do {
			$lv++;
			$where="$tidname IN($tid) and $tidname<>0 and $tidname<>'' ";
			if($is_sqdl==1)$where.= "and is_sqdl>0";
			$user = zfun::f_select("User",$where,"id");
			if (!empty($user)) {
				$tid = "";
				foreach ($user as $k => $v)
					$tid .= "," . $v['id'];
				$tid = substr($tid, 1);
				$arr[$lv] = $tid;
				
				if($lv<=$is_cy&&!empty($is_cy))unset($arr[$lv]);
			}
		} while(!empty($user)&&$lv<$maxlv-1);
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
}
?>
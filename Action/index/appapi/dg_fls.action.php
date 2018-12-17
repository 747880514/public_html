<?php
fun("zfun");
include_once ROOT_PATH.'Action/index/appapi/dgappcomm.php';
include_once ROOT_PATH.'Action/index/default/api.action.php';
include_once ROOT_PATH."Action/index/appapi/tzbs_new.action.php";
actionfun("comm/order");
class dg_flsAction extends Action{
	//福利社
	public function index(){
		appcomm::signcheck();
		$arr=filter_check($_POST).'';
		$data=appcomm::f_goods("FLS","id>0 and hide=0","img,title,SkipUIIdentifier,url","sort desc",$arr,20);
		foreach($data as $k=>$v){
			if(empty($v['title']))$data[$k]['title']=$v['title']='福利社';
			if(!empty($v['img']))$data[$k]['img']=UPLOAD_URL."slide/".$v['img'];
			$data[$k]['type']=$v['type']=$v['SkipUIIdentifier'];
			$SkipUIIdentifier=tzbs_newAction::getarr_ksrk_fuck($data[$k]);
			
			if(!empty($SkipUIIdentifier)){
				$data[$k]['SkipUIIdentifier']=$SkipUIIdentifier;
				$v['type']=$SkipUIIdentifier;
			}
			$data[$k]['UIIdentifier']=$v['type'];
			
			$tmp=apiAction::view_type($v,1);
			
			$data[$k]['name']=$v['title'];
			$data[$k]['view_type']=$tmp['view_type'];
			if(!empty($v['url'])){
				$data[$k]['SkipUIIdentifier']="pub_wailian";	
			}
			$tmp=apiAction::view_img($v);
			$data[$k]['goodslist_img']=$tmp['img'];
			if($data[$k]['view_type']==2&&$v['type']!=34)$data[$k]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";;
			$data[$k]['goodslist_str']=$tmp['str'];
			$login=tzbs_newAction::getarr_login($data[$k]);
			$data[$k]['is_need_login']=intval($login);
			$data[$k]['goods_detail']=array();
		}
		zfun::fecho("福利社",$data,1);
	}
	//收款帐号
	public function skzh(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$arr=array();
		if(empty($user['alipay']))$user['alipay']=$user['zfb_au'];
		if(!empty($user['realname']))$user['realname']=self::xphone($user['realname']);
		if(!empty($user['alipay']))$user['alipay']=self::xphone($user['alipay']);
		$set=zfun::f_getset("skzh_top_img,skzh_top_title1,skzh_top_title2");
		if(!empty($user['alipay'])){
			$arr[0]=array(
				"img"=>UPLOAD_URL."geticos/".$set['skzh_top_img'],
				"bj_img"=>INDEX_WEB_URL."View/index/img/appapi/comm/pay_alipay_bj.png",
				"str"=>$set['skzh_top_title2'],
				"top_str"=>$set['skzh_top_title1'],
				"realname"=>$user['realname'],
				"alipay"=>$user['alipay'],
				"id"=>1,
			);
		}
		zfun::fecho("收款帐号",$arr,1);
	}
	public function qiandao(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$str="qiandao_get_btn,qiandao_get_btn1,CustomUnit,qiandao_yuan_img,qiandao_guang_img,qiandao_top_title,qiandao_type_onoff,qiandao_jifen_val,qiandao_commission_val";
		$set=zfun::f_getset($str);
		$time=strtotime("today");
		$yes_time=$time-86400;
		$tianshu=0;//签到天数
		$commission=zfun::f_sum("Interal","uid='$uid' and detail LIKE '%签到%' and type='45'","interal");
		$jifen=zfun::f_sum("Interal","uid='$uid' and detail LIKE '%签到%' and type='44'","interal");
		$data=array();
		$data['title']="今日签到";
		if(!empty($set['qiandao_top_title']))$data['title']=$set['qiandao_top_title'];
		$data['mx_title']="奖励明细";
		$data['str']=$set['qiandao_get_btn'];
		$data['str1']=$set['qiandao_get_btn1'];
		$data['money']=$commission;
		$data['is_qiandao']=0;
		if(!empty($set['qiandao_type_onoff'])){$data['mx_title']="积分明细";$data['money']=$jifen;$data['str']="签到领积分";$data['str1']="我的积分:";}
		//今天是否签到
		$today_jl=zfun::f_row("QDJL","uid='$uid' and time>=$time");
		if(!empty($today_jl)){
			$tianshu=$today_jl['lj_qdts'];
			$data['str']="已签到";
			$data['is_qiandao']=1;
		}
		//昨天是否签到
		$yes_jl=zfun::f_row("QDJL","uid='$uid' and time=$yes_time");
		if(!empty($yes_jl)&&empty($today_jl))$tianshu=$yes_jl['lj_qdts'];
		$data['tianshu']=$tianshu;
		$data['img']=INDEX_WEB_URL."View/index/img/appapi/comm/sign_coin.png";
		$data['qiandao_yuan_img']=$set['qiandao_yuan_img'];
		if(empty($set['qiandao_yuan_img']))$data['qiandao_yuan_img']=INDEX_WEB_URL."View/index/img/appapi/comm/qiandao_yuan_img.png";
		else $data['qiandao_yuan_img']=UPLOAD_URL."slide/".$set['qiandao_yuan_img'];

		if(empty($set['qiandao_guang_img']))$data['qiandao_guang_img']=INDEX_WEB_URL."View/index/img/appapi/comm/qiandao_guang_img.png";
		else $data['qiandao_guang_img']=UPLOAD_URL."slide/".$set['qiandao_guang_img'];
		$data['week']=self::qd_date($user);
		zfun::fecho("签到页面",$data,1);
	}
	//签到的日期
	public static function qd_date($user){
		$uid=intval($user['id']);
		$week=date("w");
		
		$time=strtotime("today")-($week)*86400;
		$jl=zfun::f_select("QDJL","uid<>0 and uid='$uid' and time>$time");
		$att=array("周日","周一","周二","周三","周四","周五","周六");
		$arr=array();
		for($i=0;$i<=6;$i++){
			$arr[$i]['is_check']=0;
			$arr[$i]['name']=$att[$i];
			foreach($jl as $k=>$v){
				if(empty($v['time']))continue;
				$date=date("w",$v['time']);
				
				if($i!=$date)continue;
				$arr[$i]['is_check']=1;
			}
		}
		return $arr;
	}
	//签到记录
	public function qd_jl(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$str="qiandao_type_onoff,qiandao_jifen_val,qiandao_commission_val";
		$set=zfun::f_getset($str);
		$type=45;
		if(!empty($set['qiandao_type_onoff']))$type=44;
		$where="uid='$uid' and detail LIKE '%签到%' and type=".$type;
		$data=appcomm::f_goods("Interal",$where,"interal,detail,time,id","time desc",NULL,20);
		foreach($data as $k=>$v){
			$data[$k]['interal']="+".$v['interal'];
			$data[$k]['img']=INDEX_WEB_URL."View/index/img/appapi/comm/sign_coin.png";
			
		}
		zfun::fecho("签到记录",$data,1);
	}
	//签到操作
	public function qd_doing(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$str="CustomUnit,qiandao_type_onoff,qiandao_jifen_val,qiandao_commission_val";
		$set=zfun::f_getset($str);
		//判断今天是否已签到
		$time=strtotime("today");
		$count=zfun::f_count("QDJL","uid='$uid' and time=$time");
		if(!empty($count))zfun::fecho("您今天已经签到，请明天再来");
		$yes_time=$time-86400;
		$yes_jl=zfun::f_row("QDJL","uid='$uid' and time=$yes_time");
		$tianshu=1;//签到天数
		if(!empty($yes_jl))$tianshu=$yes_jl['lj_qdts']+1;//如果昨天存在则天数加1
		//奖励
		$money=floatval($set['qiandao_commission_val']);
		$data=array();
		if(!empty($set['qiandao_type_onoff'])){
			$money=floatval($set['qiandao_jifen_val']);
			$data['integral']=$money;
			$type="jifen";
			$type1="44";
			$str1="签到成功获得+".$money."积分";
		}else{$str1="签到成功获得+".$money.$set['CustomUnit'];$data['commission']=$money;$type="yongjin";$type1="45";}
		$arr=array(
			"uid"=>$uid,
			"lj_qdts"=>$tianshu,
			"time"=>$time,
			"type"=>$type,
			"money"=>$money,
		);
		if(empty($money))$str1="签到成功";
		$result=zfun::f_insert("QDJL",$arr);
		if(empty($result))zfun::fecho("签到失败");
		if(!empty($money))$result=zfun::addval("User","id='$uid'",$data);
		
		if(!empty($money))self::adddetail("签到",$uid,$type1,array("qd_type"=>$type,"qiaodao"=>$money),time(),$money);
		zfun::fecho("签到成功",array("money"=>$money,"str"=>$str1),1);
	}
	//会员中心
	public function mem_index(){
		appcomm::signcheck();
		
		$data=array();
		$tgid=0;
		$uid=0;
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			if(empty($user))zfun::fecho("用户不存在");
			$uid=intval($user['id']);
			$tgidkey = $this -> getApp('Tgidkey');
			$tgid = $tgidkey -> addkey($uid);
			if(!empty($user['tg_code']))$tgid=$user['tg_code'];
		}
		
		//是否运营商
		if($user['operator_lv']!='0')$is_operator=1;
		else $is_operator=0;
		
		$img_arr=self::pic_arr();//图片集
		$cum=zfun::f_getset("CustomUnit");
		if(empty($cum['CustomUnit']))$cum['CustomUnit']='佣金';
			/*
		$where="detail LIKE '购买商品获得%'";
		$where.=" and detail NOT LIKE '%积分%' and detail NOT LIKE '%成长值%'";
		$where.=" and uid='$uid' and uid<>0";
		$inter=zfun::f_sum("Interal",$where,"interal");
		//自购佣金
		$zgcommission=zfun::dian($inter);*/
		$zgcommission=zfun::f_sum("Rebate","uid='{$uid}' and comment ='自购' and returnstatus=1","fcommission");
		//即将到账佣金
		
		
		//即将到账佣金
		
		
		$uids=-1;
		$xj_count=0;
		if(!empty($uid)){
			/*$result=order::get_lower($uid);
			$cwdl_rule_arr=array("人数"=>count($result['user_arr'])-1);
			$userdata=$result['user_arr'];
			$uids=$result['uids'];*/
			$xj_count=zfun::f_count("Nexus","extend_uid='{$uid}' and bili<>0");

		}
	
		$where="(status='订单结算' or status='订单付款' or status='订单成功')  and  returnstatus=0 and uid='{$uid}'";
		$jjdzcommission_sum=zfun::f_sum("Rebate",$where,"fcommission");
		
		//累计提币佣金
		$file_arr=array("token_s"=>$_POST['token']);
		$GLOBALS['times']=100;
		$datar=self::read_cookie("ljtx_",$file_arr);
		$money=$datar['money'];
		if(empty($money)){
			$au=zfun::f_select("Authentication","type IN(3,7) and uid<>0 and uid='$uid' and audit_status=1","data");
			$money=0;
			foreach($au as $k=>$v){
				$arr=json_decode($v['data'],true);
				if(empty($arr['money']))$arr['money']=$arr['txmoney'];
				$au[$k]['money']=$arr['money'];
				unset($au[$k]['data']);
				$money+=abs($au[$k]['money']);
			}
			if(!empty($money)){
				$data['money']=$money;
				self::set_cookie("ljtx_",$file_arr,$data);
			}
		}
		$set1=self::wz_set($user);//文字后台读取
		
		$is_tx=0;
		$title='';
		//提币时间
		if(!empty($set1['tx_time_hyzx'])){
			$day=date("d");
			$dayarr=str_replace("，",",",$set1['tx_time_hyzx']);
			$dayarr=explode(",",$dayarr);
			$count=count($dayarr);
			foreach($dayarr as $k=>$v){
				if(!empty($title))continue;
				if($day==$v){
					$is_tx=1;
					$day1=$dayarr[$k+1];
					if($k+1==$count){$day1=$dayarr[0];}
					$title="每月".$day1."号提币";
					continue;
				}
				if($day<$v){
					$title="每月".$v."号提币";
					continue;
				}
				if($k+1==$count){
					$day1=$dayarr[0];
					$title="每月".$day1."号提币";
				}
			}
			
		}else $is_tx=1;
		if(!empty($set1['tx_notshow_hyzx']))$title=$set1['tx_notshow_hyzx'];
		//更新累计收益
		$commission_sums=zfun::dian($user['commission']+$user['dlcommission']+$money);
		if($commission_sums>$user['commission_sum'])zfun::f_update("User","id='".$uid."' and id<>0",array("commission_sum"=>$commission_sums));
		//会员中心logo
		
		
		$data['mem_font_color']=$set1['mem_font_color'];

		//百里.邀请人
		$extend_user = zfun::f_row("User", "id = '".$user['extend_id']."'");
		$extend_user_nickname = "";
		if($extend_user['nickname'])
		{
			$extend_user['nickname'] = mb_substr($extend_user['nickname'],0,2,"utf-8")."**".mb_substr($extend_user['nickname'],-1,2,"utf-8");
			$extend_user_nickname = "我的邀请人:".$extend_user['nickname']."    ";
		}

		$data['yq']=array(
			"title"=>$extend_user_nickname . $set1['yq_tg_title'],
			"tgid"=>$tgid,
		);

		//百里.重写自购佣金，修改为邀请待解锁奖励
		$set=zfun::f_getset("fxdl_yqzcjl1,fxdl_yqzcjl2,fxdl_yqzcjl3,fxdl_yqzcjl4,fxdl_yqzcjl5");	//配置
		$fxdl_yqzcjl = $user['is_sqdl']+1;	//等级

		//百里.处理冻结金额
		self::hs_blocking($user['id']);

		//百里.执行新用户数据,添加返佣金额
		$new_user_js_ids = array();
		$new_user_js_ids = zfun::f_select("User", "extend_id='{$user['id']}' and huasuan_jstime = 0", "id,huasuan_jsmoney");
		if(!empty($new_user_js_ids))
		{
			//当前等级返佣
			$huasuan_jsmoney = $set['fxdl_yqzcjl'.$fxdl_yqzcjl];

			foreach ($new_user_js_ids as $k => $v) {
				//未保存返佣金额
				if($v['huasuan_jsmoney'] <= 0 || $v['huasuan_jsmoney'] == '')
				{
					zfun::f_update("User","id = '{$v['id']}'", array('huasuan_jsmoney'=>$huasuan_jsmoney));
				}

				//有金额，未结算
				if($v['huasuan_jsmoney'] > 0)
				{
					//查询是否结算
					$row = zfun::f_row("Interal", "uid = '{$user['id']}' and next_id = '{$v['id']}' and type = 100", "time");
					//更新
					if($row)
					{
						zfun::f_update("User","id = '{$v['id']}'",array("huasuan_jstime"=>$row['time']));
					}
				}
			}
		}

		//百里.查询自己数据
		if($user['huasuan_jsmoney'] == 0 && $user['extend_id'] > 0)
		{
			$extend_user_sqdl = zfun::f_row("User", "id='{$user['extend_id']}'", "is_sqdl");
			$extend_user_sqdl = $extend_user_sqdl['is_sqdl']+1;	//等级
			$extend_user_sqdl = $set['fxdl_yqzcjl'.$extend_user_sqdl];
			zfun::f_update("User", "id='{$user['id']}'", array("huasuan_jsmoney"=>$extend_user_sqdl));
		}


		//查询当前用户数据
		$zgcommission2 = '0.00';
		if($user['id'] > 0)
		{
			$zgcommission2 = zfun::f_sum("User","extend_id='{$user['id']}' and huasuan_jsmoney > 0 and clear_huasuan_jsmoney = 0 and (ISNULL(huasuan_jstime) OR huasuan_jstime = 0)","huasuan_jsmoney");	//待解锁

			//待解锁奖励=邀请待解锁奖励+注册赠送冻结余额
			$blocking = zfun::f_row("User", "id = '{$user['id']}'", 'commission,blocking_price,blocking_price_endtime');
			$zgcommission2 += $blocking['blocking_price'];

			$zgcommission2 = max( sprintf("%.2f", $zgcommission2), '0.00');	//格式化金额
		}

		$set2=zfun::f_getset("mem_wallet_data");
		$wall_data=zfun::arr64_decode($set2['mem_wallet_data']);
		if(empty($wall_data)){
			$wall_data=self::mr_data();
		}
		
		$wall_data=self::sortarr($wall_data,"sort","desc");
		foreach($wall_data as $k=>$v){
			if($v['is_show']==0)unset($wall_data[$k]);
		}
		
		$wall_data=array_values($wall_data);
		for($i=0;$i<=3;$i++){
			if(empty($wall_data[$i]['type'])){
				$wall_data[$i]['type']='';$wall_data[$i]['name']='';$wall_data[$i]['val']='';$wall_data[$i]['is_show']=1;
			}
		}
		//unset($wall_data[4]);
		
		foreach($wall_data as $k=>$v){
			$wall_data[$k]['val']='';
			if($v['type']=='ljsy')$wall_data[$k]['val']=zfun::dian($user['commission']+$user['dlcommission']+$money);
			// if($v['type']=='zgsy')$wall_data[$k]['val']=$zgcommission;
			if($v['type']=='zgsy')$wall_data[$k]['val']=$zgcommission2;	//百里
			if($v['type']=='jjdz')$wall_data[$k]['val']=zfun::dian($jjdzcommission_sum);
			if($v['type']=='ljtx')$wall_data[$k]['val']=zfun::dian($money);
			if($v['type']=='zhye')$wall_data[$k]['val']=zfun::dian($user['commission']+$user['dlcommission']);
		}
		$data['wallet']=array(
			"title"=>$set1['wallet_tx_title'],
			"title1"=>$title,
			"is_tx"=>$is_tx,
			"img"=>$img_arr['wallet_tx_ico'],
			"list"=>$wall_data,
		);
		
		$data['order']=array(
			"title"=>$set1['order_list_title'],
			"title1"=>'查看全部订单',
			
			"list"=>array(
				array(
					"name"=>'全部订单',
					"img"=>$img_arr['all_order_ico'],
					"type"=>0,
				),
				array(
					"name"=>'即将到账',
					"img"=>$img_arr['jjdz_ico'],
					"type"=>1,
				),
				array(
					"name"=>'已到帐',
					"img"=>$img_arr['ydz_ico'],
					"type"=>2,
				),
				array(
					"name"=>'无效订单',
					"img"=>$img_arr['wxdd_ico'],
					"type"=>3,
				),
			),
		);
		
		
		
		$is_hhr=0;
		if(!empty($user['is_sqdl']))$is_hhr=1;
		//jj explosion
		if(!empty($user['operator_lv']))$is_hhr=1;
		$where=" (status IN('订单付款','订单结算','订单成功') or status='订单成功') and uid<>'$uid'";
		$where.=" and ((share_uid='$uid' and share_uid<>0))";
		$fxzcount=zfun::f_count("Order",$where);
		$where="uid='$uid' and bili<>0  and comment<>'自购'";
		$teamcount=zfun::f_count("Rebate",$where);
		if(($user['hhr_gq_time'])<time()&&!empty($user['hhr_gq_time']))$is_hhr=0;
		if($_POST['version']<2)$set1['hhr_mem_SkipUIIdentifier']='pub_shouyibaobiao';
		$data['hhr']=array(
			"title"=>$set1['hhr_list_title'],
			"title1"=>$set1['hhr_right_title'],
			"is_hhr"=>$is_hhr,
			"SkipUIIdentifier"=>'pub_hehuorenzhongxin',
			"list"=>array(
				array(
					"name"=>'收益报表',
					"img"=>$img_arr['sybb_ico'],
					"SkipUIIdentifier"=>$set1['hhr_mem_SkipUIIdentifier'],
					"val"=>'',
					"is_need_login"=>1,
				),
				array(
					"name"=>'共'.intval($xj_count).'粉丝',
					"img"=>$img_arr['fs_ico'],
					"SkipUIIdentifier"=>"pub_wodefensi",
					"val"=>intval($xj_count),
					"is_need_login"=>1,
				),
				
				array(
					"name"=>'分享订单'.$fxzcount.'个',
					"img"=>$img_arr['fxdd_ico1'],
					"SkipUIIdentifier"=>"pub_fenxiangdingdan",
					"val"=>$fxzcount,
					"is_need_login"=>1,	
				),
				array(
					"name"=>'家族订单'.$teamcount.'个',
					"img"=>$img_arr['fxdd_ico'],
					"SkipUIIdentifier"=>"pub_jiazudingdan",
					"val"=>$teamcount,
					"is_need_login"=>1,	
				),
			),
		);
		
		if(!empty($user['is_sqdl'])&&$is_hhr==1){
			$sett=zfun::f_getset("fxdl_name".($user['is_sqdl']+1));
			$data['hhr']['title']=$set1['hhr_left_title'];
			$data['hhr']['title1']='查看全部报表';
		}
		$data['hy_ico']=array("title"=>$set1['hytb_list_title']);
	
		$data['vip_btn_str']='升级{等级}';
		$data['vip_btn_color']='FFE400';
		$data['vip_btn_fontcolor']='000000';
		$data['is_vip_max']=0;
		self::mem_list($data,$user);
		fun("cwdl_rule");
		//die("".__LINE__);
		//cwdl_rule::yq_next_friend($uid);//检测是否要更新用户数量
		zfun::fecho("会员中心",$data,1);
	}
	//会员数据
	public static function mem_list(&$data=array(),$user=array()){
		$str="fxdl_lv,operator_onoff,fxdl_lvmemimg_operator1,fxdl_lvmemimg_operator2,operator_name,operator_name_2";
		$str.=",mem_vip_btn_fontcolor,mem_vip_btn_color,mem_vip_btn_str,is_vip_btn_show,mem_maxvip_btn_str,mem_font_color,mem_vipbtn_onoff";
		$set=zfun::f_getset($str);
		$lv=$user['is_sqdl']+1;$next_lv=$user['is_sqdl']+2;
		if($user['operator_lv'].''=='1'){$lv=$set['fxdl_lv']+1;$next_lv=$set['fxdl_lv']+2;}	if($user['operator_lv'].''=='2'){$lv=$set['fxdl_lv']+2;$next_lv=$set['fxdl_lv']+2;}

		for($i=1;$i<=$set['fxdl_lv'];$i++){
			$str.=",fxdl_name".$i;
			$str.=",fxdl_lvmemimg".$i;
		}
		$vipset=zfun::f_getset($str);
		$arr=array();
		for($i=1;$i<=$set['fxdl_lv'];$i++){
			$arr[$i]['name']=$vipset['fxdl_name'.$i];
			$arr[$i]['vip_img']=INDEX_WEB_URL.'View/index/img/appapi/vips/vip'.($i-1).".png";
			if(!empty($vipset['fxdl_lvmemimg'.$i]))$arr[$i]['vip_img']=UPLOAD_URL."slide/".$vipset['fxdl_lvmemimg'.$i];
		}
		$data['is_vip_max']=0;
		if($set['operator_onoff'].''=='1'){
			if($lv==$set['fxdl_lv']+2)$data['is_vip_max']=1;
			$arr[$set['fxdl_lv']+1]['name']=$vipset['operator_name'];
			$arr[$set['fxdl_lv']+1]['vip_img']=INDEX_WEB_URL.'View/index/img/appapi/vips/vip1.png';
			if(!empty($vipset['fxdl_lvmemimg_operator1']))$arr[$set['fxdl_lv']+1]['vip_img']=UPLOAD_URL."slide/".$vipset['fxdl_lvmemimg_operator1'];
			$arr[$set['fxdl_lv']+2]['name']=$vipset['operator_name_2'];
			$arr[$set['fxdl_lv']+2]['vip_img']=INDEX_WEB_URL.'View/index/img/appapi/vips/vip1.png';
			if(!empty($vipset['fxdl_lvmemimg_operator2']))$arr[$set['fxdl_lv']+2]['vip_img']=UPLOAD_URL."slide/".$vipset['fxdl_lvmemimg_operator2'];
		}else{
			if($lv==$set['fxdl_lv'])$data['is_vip_max']=1;
		}
		$data['vip_logo']=$arr[$lv]['vip_img'];
		if(!empty($vipset['mem_vip_btn_str']))$data['vip_btn_str']=$vipset['mem_vip_btn_str'];
		$next_name=$arr[$next_lv]['name'];
		$data['vip_btn_str']=str_replace("{等级}",$next_name,$data['vip_btn_str']);
		if(empty($vipset['mem_maxvip_btn_str']))$vipset['mem_maxvip_btn_str']='已达到最高等级';
		if($data['is_vip_max']==1)$data['vip_btn_str']=$vipset['mem_maxvip_btn_str'];
		if(!empty($vipset['mem_vip_btn_color']))$data['vip_btn_color']=$vipset['mem_vip_btn_color'];
		if(!empty($vipset['mem_vip_btn_fontcolor']))$data['vip_btn_fontcolor']=$vipset['mem_vip_btn_fontcolor'];
		$arr=array("1","0");
		$mem_vipbtn_onoff=intval($vipset['mem_vipbtn_onoff']);
		$data['is_vip_btn_show']=$arr[$mem_vipbtn_onoff];

	}
	//文字后台读取
	public static function wz_set($user){
		$str1="tx_notshow_hyzx,tx_time_hyzx,hhr_right_title,hhr_left_title,yq_tg_title,wallet_tx_title,order_list_title,hhr_list_title,hytb_list_title";
		 $str1.=",wdqb_1_title,wdqb_2_title,wdqb_3_title,wdqb_4_title,hhr_mem_SkipUIIdentifier";
		 //运营商
		$str1.=",operator_wuxian_bili,operator_name,operator_name_2,mem_font_color";
		$set1=zfun::f_getset($str1);
		if(empty($set1['yq_tg_title']))$set1['yq_tg_title']="我的邀请ID:";
		if(empty($set1['wallet_tx_title']))$set1['wallet_tx_title']="我的钱包";
		if(empty($set1['order_list_title']))$set1['order_list_title']="交易订单";
		if(empty($set1['hhr_list_title']))$set1['hhr_list_title']="申请成为合伙人";
		if(empty($set1['hhr_right_title']))$set1['hhr_right_title']="高人一等 瓜分一个亿";
		if(empty($set1['hhr_left_title']))$set1['hhr_left_title']="合伙人-{等级}";
		if(empty($set1['hytb_list_title']))$set1['hytb_list_title']="抢先体验-心花怒放";
		if(empty($set1['wdqb_1_title']))$set1['wdqb_1_title']="累计收益";
		if(empty($set1['wdqb_2_title']))$set1['wdqb_2_title']="自购收益";
		if(empty($set1['wdqb_3_title']))$set1['wdqb_3_title']="即将到账";
		if(empty($set1['wdqb_4_title']))$set1['wdqb_4_title']="累计提币";
		if(empty($set1['hhr_mem_SkipUIIdentifier']))$set1['hhr_mem_SkipUIIdentifier']="pub_shouyibaobiao";
		if(empty($set1['mem_font_color']))$set1['mem_font_color']="FFFFFF";
		
		$sett=zfun::f_getset("fxdl_name".($user['is_sqdl']+1));
		$fxdl_name=$sett["fxdl_name".($user['is_sqdl']+1)];
		if($user['operator_lv']==1)$fxdl_name=$set1['operator_name'];
		if($user['operator_lv']==2)$fxdl_name=$set1['operator_name_2'];
		

		$set1['hhr_left_title']=str_replace("{等级}",$fxdl_name,$set1['hhr_left_title']);
		return $set1;
	}
	
	//图片集
	public static function pic_arr(){
		$str="wallet_tx_ico,all_order_ico,jjdz_ico,ydz_ico,wxdd_ico,sybb_ico,fs_ico,fxdd_ico,fxdd_ico1";
		$set=zfun::f_getset($str);
		if(!empty($set['wallet_tx_ico']))$set['wallet_tx_ico']=UPLOAD_URL."slide/".$set['wallet_tx_ico'];
		else $set['wallet_tx_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_wallet_withdraw.png";
		
		if(!empty($set['all_order_ico']))$set['all_order_ico']=UPLOAD_URL."slide/".$set['all_order_ico'];
		else $set['all_order_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_order_shz.png";
		
		if(!empty($set['jjdz_ico']))$set['jjdz_ico']=UPLOAD_URL."slide/".$set['jjdz_ico'];
		else $set['jjdz_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_order_jjdz.png";
		
		if(!empty($set['ydz_ico']))$set['ydz_ico']=UPLOAD_URL."slide/".$set['ydz_ico'];
		else $set['ydz_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_order_ydz.png";
		
		if(!empty($set['wxdd_ico']))$set['wxdd_ico']=UPLOAD_URL."slide/".$set['wxdd_ico'];
		else $set['wxdd_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_order_wxdd.png";

		if(!empty($set['sybb_ico']))$set['sybb_ico']=UPLOAD_URL."slide/".$set['sybb_ico'];
		else $set['sybb_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_partner_sorder.png";
		
		if(!empty($set['fs_ico']))$set['fs_ico']=UPLOAD_URL."slide/".$set['fs_ico'];
		else $set['fs_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_partner_fans.png";

		if(!empty($set['fxdd_ico']))$set['fxdd_ico']=UPLOAD_URL."slide/".$set['fxdd_ico'];
		else $set['fxdd_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/my_partner_family.png";
		if(!empty($set['fxdd_ico1']))$set['fxdd_ico1']=UPLOAD_URL."slide/".$set['fxdd_ico1'];
		else $set['fxdd_ico1']=INDEX_WEB_URL."View/index/img/appapi/comm/my_partner_form.png";
		return $set;
	}
	//以下非接口
	public static function adddetail($msg='',$uid=0,$type=0,$data=array(),$time=0,$money=0){
		$arr=array("time"=>time(),"uid"=>$uid,"detail"=>$msg,"type"=>$type,"interal"=>$money);
		if(!empty($data)){
			$arr['data']=zfun::f_json_encode($data);
			if(!empty($data['oid']))$arr['oid']=$data['oid'];
		}
		if(!empty($time))$arr['time']=$time;
		$result=zfun::f_insert('Interal',$arr);
		if($result==false)return false;
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
	
	public static function set_cookie($name="",$file_arr=array(),$data=array(),$cookie_path="dgapp"){
		if(empty($name)||empty($file_arr))zfun::fecho("set_cookie error");
		$str=$name."_";
		foreach($file_arr as $k=>$v){
			if(empty($v)||$k=='time'||$k=='sign'||$k=='token')continue;
			$str.=$k.$v;	
		}	
		$name=md5($str);
		$path=ROOT_PATH."Temp/".$cookie_path."/".$name.".cache";
		if(empty($GLOBALS['times']))$GLOBALS['times']=3600;
		$arr=array(
			"end_time"=>time()+$GLOBALS['times'],
			"data"=>$data,
		);
		zfun::wfile($path,json_encode($arr));
		return true;
	}
	
	public static function read_cookie($name="",$file_arr=array(),$cookie_path="dgapp"){
		if(empty($name)||empty($file_arr))zfun::fecho("set_cookie error");
		$str=$name."_";
		foreach($file_arr as $k=>$v){
			if(empty($v)||$k=='time'||$k=='sign'||$k=='token')continue;
			$str.=$k.$v;	
		}	
		$name=md5($str);
		$path=ROOT_PATH."Temp/".$cookie_path."/".$name.".cache";
		if(empty($GLOBALS['times']))$GLOBALS['times']=3600;
		$arr=array(
			"end_time"=>time()+$GLOBALS['times'],
			"data"=>$data,
		);
		if(file_exists($path)==false)return array();
		$data=json_decode(zfun::get($path),true);
		if($data['end_time']<time())return array();
		return $data['data'];
	}
	//explosion
	public static function getc($uid, $tidname = "extend_id", $maxlv = 9,$is_sqdl=0,$is_cy=0,$is_xxj=0) {//获取下级
		$set=zfun::f_getset("operator_onoff");
		if (empty($uid))return 0;
		$arr = array();
		$arr[0] = -1;
		$lv = 0;
		$eid = 0;
		$tid = $uid;
		do {
			$lv++;
			$where="$tidname IN($tid) and $tidname<>0 and $tidname<>'' ";
			if($is_sqdl==1)$where.= "and is_sqdl>0";
			$user = zfun::f_select("User",$where,"id,is_sqdl");
		
			if (!empty($user)) {
				$tid = "";
				foreach ($user as $k => $v){
					//如果最后一级 会员是代理过滤掉  推广模式才会过滤
					if($lv==$maxlv&&$v['is_sqdl']>0&&$set['operator_onoff']==1)continue;
					if(!empty($v['id']))$tid .= "," . $v['id'];
				}
				
				$tid = substr($tid, 1);
				if(!empty($tid))$arr[$lv] = $tid;
				
				if($is_cy==1&&$lv==1)unset($arr[$lv]);
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
	static function mr_data(){
		$data=array(
			array(
				"sort"=>"6",
				"name"=>'账户余额',
				"is_show"=>1,
				"type"=>'zhye',
			),
			array(
				"sort"=>"10",
				"name"=>'累计收益',
				"is_show"=>1,
				
				"type"=>'ljsy',
			),
			array(
				"sort"=>"9",
				"name"=>'自购收益',
				"is_show"=>1,
				
				"type"=>'zgsy',
			),
			array(
				"sort"=>"8",
				"name"=>'即将到账',
				"is_show"=>1,
				
				"type"=>'jjdz',
			),
			array(
				"sort"=>"7",
				"name"=>'累计提现',
				"is_show"=>1,
				
				"type"=>'ljtx',
			),
			
		);
		return $data;
	}
	//二位数组排序
	static function sortarr($arr=array(),$key='',$type="desc"){
		$tmp=array();
		foreach ($arr as $k=>$v)$tmp[$k] = $v[$key];
		if($type=="desc")$type=SORT_DESC;
		else $type=SORT_ASC;
		array_multisort($arr,$type,$tmp);
		
		return $arr;	
	}

	/**
	 * [hs_blocking 百里.花蒜冻结金额处理]
	 * 1、处理自己
	 * 2、处理下级
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2018-11-24T09:46:05+0800
	 * @param    [type]                   $uid [description]
	 * @return   [type]                        [description]
	 */
	public static function hs_blocking($uid)
	{
		$where = "(id = '{$uid}' OR extend_id = '{$uid}') AND blocking_price > 0 AND clear_huasuan_jsmoney != 1";
		$files = "id,commission,huasuan_jsmoney,huasuan_jstime,blocking_price,blocking_price_endtime";
		$users = zfun::f_select("User", $where, $files);

		foreach ($users as $key => $value) {
			//当前时间已经超过结算时间，结算
			if(time() > $value['blocking_price_endtime'] && $value['blocking_price_endtime'] > 0)
			{
				//判断指定时间内存在有效订单（已付款+已结算）
				$where = "uid='{$value['id']}' AND `status` IN ('订单付款','订单结算') AND `createDate` <= {$value['blocking_price_endtime']}";
				$haspay = zfun::f_count("Order", $where);

				$hs_update = array();

				//指定时间内未出现结算订单
				if(!$haspay)
				{
					$hs_update['huasuan_jsmoney'] = 0;	//清除邀请人的奖励
					$hs_update['huasuan_jstime'] = 0;	//清除邀请人的奖励解锁时间
					$hs_update['blocking_price'] = 0;				//清除冻结奖励
					$hs_update['blocking_price_endtime'] = NULL;	//清除冻结奖励时间
					$hs_update['clear_huasuan_jsmoney'] = 1;	//不再写入
				}
				else
				{
					//是否存在结算订单
					$where = "uid='{$value['id']}' AND `status` = '订单结算' AND returnstatus = 1 AND `createDate` <= {$value['blocking_price_endtime']}";
					$hasend = zfun::f_row("Order", $where);

					//如果出现已结算
					if($hasend['status'] == '订单结算' && $hasend['returnstatus'] == 1)
					{
						$hs_update['commission'] = $value['commission'] + $value['blocking_price'];	//将冻结奖励合并到余额
						$hs_update['blocking_price'] = 0;				//清除冻结奖励
						$hs_update['blocking_price_endtime'] = NULL;	//清除冻结奖励时间
						$hs_update['clear_huasuan_jsmoney'] = 1;	//不再写入
					}
				}

				if(!empty($hs_update))
				{
					zfun::f_update("User", "id = '{$value['id']}'", $hs_update);
				}
			}
		}
	}
}
?>


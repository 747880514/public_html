<?php

actionfun("appapi/dgappcomm");

actionfun("appapi/tzbs_new");

class appMemberFunctionAction extends Action{

	//收益

	static function income($user=array(),$data=array()){

		$uid=intval($user['id']);

		$arr=array();

		actionfun("appapi/financial_statements");

		$tx_arr=financial_statementsAction::tx_check();

		if(empty($user['id']))$tx_arr['is_tx']=0;

		$extend=zfun::f_row("User","id='".$user['extend_id']."' and id<>0");$user['extend_id']=intval($user['extend_id']);

		if(empty($extend['phone']))$extend['phone']=$extend['nickname'];

		$today=strtotime("today");

		$today_sum=zfun::f_sum("Rebate","uid='{$uid}' and uid>0 and bili>0 and status IN('订单付款','订单成功','订单结算') and order_create_time>=".$today,"fcommission");

		$last=mktime(0,0,0,date('m'),1,date('Y'));

		$last_sum=zfun::f_sum("Rebate","uid='{$uid}' and uid>0 and bili>0 and status IN('订单付款','订单成功','订单结算') and order_create_time>=".$last,"fcommission");

		$agent_sum=zfun::f_sum("Interal","uid='{$uid}' and uid>0 and detail LIKE '%推荐%成为%'","interal");



		$str='mem_sy_color8,mem_sy_font8,mem_sy_selects,mem_notx_font_color,mem_notx_bj_color,mem_tip_str,mem_tip_content,mem_extend_str,mem_sy_color1,mem_sy_color2,mem_sy_color3,mem_sy_color4,mem_sy_color5,mem_sy_font1,mem_sy_font2,mem_sy_font3,mem_sy_font4,mem_sy_font5,mem_sy_font6,mem_sy_font_color,mem_tx_font_color,mem_tx_bj_color';

		$set=zfun::f_getset($str);

		if(empty($set['mem_sy_font_color']))$set['mem_sy_font_color']='000000';

		if(empty($set['mem_sy_font1']))$set['mem_sy_font1']='可提现金额(元) :';if(empty($set['mem_sy_font2']))$set['mem_sy_font2']='今日预估收益';if(empty($set['mem_sy_font3']))$set['mem_sy_font3']='代理费收益';

		if(empty($set['mem_sy_font4']))$set['mem_sy_font4']='团队粉丝';if(empty($set['mem_sy_font5']))$set['mem_sy_font5']='推荐人';if(empty($set['mem_sy_font6']))$set['mem_sy_font6']='每月25号结算上月收入';

		if(empty($set['mem_sy_color1']))$set['mem_sy_color1']='FF0500';if(empty($set['mem_sy_color2']))$set['mem_sy_color2']='181818';if(empty($set['mem_sy_color3']))$set['mem_sy_color3']='181818';

		if(empty($set['mem_sy_color4']))$set['mem_sy_color4']='11B9FE';if(empty($set['mem_sy_color5']))$set['mem_sy_color5']='30DDDC';if(empty($set['mem_tx_font_color']))$set['mem_tx_font_color']='FFFFFF';if(empty($set['mem_tx_bj_color']))$set['mem_tx_bj_color']='FF3C3C';

		if($set['mem_tx_font_color']=='FFFFF')$set['mem_tx_font_color']='FFFFFF';

		if(empty($set['mem_notx_font_color']))$set['mem_notx_font_color']='FFFFFF';if(empty($set['mem_notx_bj_color']))$set['mem_notx_bj_color']='CCCCCC';

		if(empty($set['mem_sy_font8']))$_POST['mem_sy_font8']='当月预估收益';

		if(empty($set['mem_sy_color8']))$_POST['mem_sy_color8']='181818';

		if(empty($set['mem_sy_selects']))$_POST['mem_sy_selects']='today_sy,agent_sy';

		$imgs=INDEX_WEB_URL."View/index/img/appapi/comm/mem_sy_bj_img.png";

		if(!empty($data['data']['img']))$imgs=UPLOAD_URL."geticos/".$data['data']['img'];

		if(empty($set['mem_extend_str']))$set['mem_extend_str']='填写邀请码领福利';

		if(empty($set['mem_tip_str']))$set['mem_tip_str']='绑定邀请码';

		if(empty($set['mem_tip_content']))$set['mem_tip_content']='绑定邀请码领取奖励';

		$is_need_bind=0;

		if(empty($user['extend_id'])){$extend['phone']=$set['mem_extend_str'];$is_need_bind=1;}

		//百里
		if(!empty($extend['nickname']))
		{
			$extend_num = mb_strcut($extend['nickname'], 0, 15, "UTF-8");
			if(strlen($extend['nickname']) > 15)	$extend_num .= "...";
		}
		else
		{
			$extend_num = $extend['phone'];
		}

		$zgcommission2 = '0.00';
		$zgcommission2 = zfun::f_sum("User","extend_id='{$user['id']}' and huasuan_jsmoney > 0 and clear_huasuan_jsmoney = 0 and (ISNULL(huasuan_jstime) OR huasuan_jstime = 0)","huasuan_jsmoney");	//待解锁

		//待解锁奖励=邀请待解锁奖励+注册赠送冻结余额+剩余返利金额(fanliosjingbgouyytbo_fnuoossimple_zhaoshang_presell表)
		$blocking = zfun::f_row("User", "id = '{$user['id']}'", 'commission,blocking_price,blocking_price_endtime,commission_sum');
		$zgcommission2 += $blocking['blocking_price'];
		$zhaoshang_presell = zfun::f_row("Zhaoshangpresell", "uid = {$user['id']}");
		$zgcommission2 += $zhaoshang_presell['balance'];

		$zgcommission2 = max( sprintf("%.2f", $zgcommission2), '0.00');	//格式化金额
		$set['mem_sy_font6'] = '累计收益(元)：'.$blocking['commission_sum'].'  冻结金额(元)：'.$zgcommission2;

		$arr[0]=array(

			"str"=>$set['mem_sy_font1'],

			"color"=>$set['mem_sy_font_color'],

			"tx_money"=>zfun::dian($user['commission']),

			"tx_moneycolor"=>$set['mem_sy_color1'],

			"str1"=>$set['mem_sy_font6'],

			"tx_str"=>$tx_arr['title'],

			"is_tx"=>$tx_arr['is_tx'],

			"tx_color"=>$set['mem_tx_font_color'],

			"tx_bjcolor"=>$set['mem_tx_bj_color'],

			"extend_color"=>$set['mem_sy_color5'],

			"fan_color"=>$set['mem_sy_color4'],

			"fan_str"=>$set['mem_sy_font4'],

			"fan_num"=>intval($user['yq_all_count']),

			"extend_str"=>$set['mem_sy_font5'],

			//百里
			// "extend_num"=>$extend['phone'],
			"extend_num"=>$extend_num,

			"bj_img"=>$imgs,

			"is_can_bind"=>$is_need_bind,

			"tip_str"=>$set['mem_tip_str'],

			"tip_content"=>$set['mem_tip_content'],

		);

		$tmp=array();

		if(strstr($set['mem_sy_selects'],"today_sy")){

			$tmp[]=array("str"=>$set['mem_sy_font2'],"num"=>zfun::dian($today_sum),"color"=>$set['mem_sy_color2']);

		}

		if(strstr($set['mem_sy_selects'],"last_sy")){

			$tmp[]=array("str"=>$set['mem_sy_font8'],"num"=>zfun::dian($last_sum),"color"=>$set['mem_sy_color8']);

		}

		if(strstr($set['mem_sy_selects'],"agent_sy")){

			$tmp[]=array("str"=>$set['mem_sy_font3'],"num"=>zfun::dian($agent_sum),"color"=>$set['mem_sy_color3']);

		}

		$arr[0]['yg_str']=$tmp[0]['str'];

		$arr[0]['yg_num']=$tmp[0]['num'];

		$arr[0]['yg_color']=$tmp[0]['color'];

		$arr[0]['agent_str']=$tmp[1]['str'];

		$arr[0]['agent_num']=$tmp[1]['num'];

		$arr[0]['agent_color']=$tmp[1]['color'];

		if($tx_arr['is_tx']==0){

			$arr[0]['tx_color']=$set['mem_notx_font_color'];

			$arr[0]['tx_bjcolor']=$set['mem_notx_bj_color'];

		}

		return $arr;

	}

	

	//一张广告图

	static function one_banner($user=array(),$tmp=array()){

		

		$data=self::comm_doing($user,$tmp);

		return $data;

	}

	//两张张广告图

	static function two_banner($user=array(),$tmp=array()){

		$data=self::comm_doing($user,$tmp);

		return $data;

	}

	//图标

	static function mem_ico($user=array(),$tmp=array()){

		$data=self::comm_doing($user,$tmp);

		return $data;

	}

	//公共处理

	static function comm_doing($user=array(),$tmp=array()){

		$appIcon=$tmp['data']['list'];

		$set=zfun::f_getset("checkVersion");

		$appIcon=tzbs_newAction::bs_zhuanhuan($appIcon);

		foreach ($appIcon as $k => $v) {

			$appIcon[$k]['name']=$appIcon[$k]['title']=$v['title'];

			

			$appIcon[$k]['img']='';

			if(!empty($v['img']))$appIcon[$k]['img'] = UPLOAD_URL . 'geticos/' . $v['img'];

			$appIcon[$k]['type']=$appIcon[$k]['UIIdentifier']=$v['SkipUIIdentifier'];

			$appIcon[$k]['goodslist_img']=$v['goodslist_img'];

			$appIcon[$k]['goodslist_str']=$v['goodslist_str'];

			$data=tzbs_newAction::view_type($appIcon[$k],1);

			$appIcon[$k]['view_type']=$data['view_type'];

			$appIcon[$k]['is_showcate']=$data['is_showcate'];

			if($appIcon[$k]['view_type']==2&&$appIcon[$k]['SkipUIIdentifier']!=34)$appIcon[$k]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";;

			//公共处理一下

			tzbs_newAction::comm_doing($appIcon[$k]);

			$appIcon[$k]['goods_detail']=array();

			$login=tzbs_newAction::getarr_login($appIcon[$k]);

			$appIcon[$k]['is_need_login']=intval($login);

			$url=tzbs_newAction::getduomai($v['url'],$user['id']);

			$appIcon[$k]['url']=$url;

			$json=json_decode($v['data_json'],true);unset($appIcon[$k]['data_json']);

			$getscreen=self::getscreen($appIcon[$k],$json);//筛选条件

			$appIcon[$k]=$getscreen;

			$appIcon[$k]['url']=str_replace("[token]",$_POST['token'],$appIcon[$k]['url']);

			$appIcon[$k]['font_color']=str_replace("#","",$v['font_color']);

			if(empty($appIcon[$k]['font_color']))$appIcon[$k]['font_color']='000000';

			$appIcon[$k]['show_name']=$appIcon[$k]['name'];

			if($_POST['version']<=8&&$appIcon[$k]['SkipUIIdentifier']=='pub_friend_list')$appIcon[$k]['SkipUIIdentifier']='pub_jiazuchengyuan';

			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){

				$skip=array("pub_fenxiaozhongxin","pub_qianghongbao","pub_yaoqinghaoyou","pub_yaojiangjilu","pub_hehuorenzhongxin","pub_laxinhuodong","pub_member_upgrade");

				if(in_array($appIcon[$k]['SkipUIIdentifier'],$skip))unset($appIcon[$k]);

			}

			$check=self::check_oldversion($appIcon[$k]);

			if($check==1)unset($appIcon[$k]);

		}

		$appIcon=array_values($appIcon);

		return $appIcon;

	}

	//兼容IOS

	static function check_oldversion($data=array()){

		//IOS版本3没有

		$skip=array("pub_doubleMain","pub_doubleTmall","pub_doubleGather","pub_tljGoods");

		$skip1=array("pub_miandan");

		$check=0;

		if($_POST['version']<4&&$_POST['platform']=='iOS'&&in_array($data['SkipUIIdentifier'],$skip)){

			$check=1;

		}

		if($_POST['version']<8&&in_array($data['SkipUIIdentifier'],$skip1)){

			$check=1;

		}

		return $check;

	}

	//get

	static function getscreen($data,$json=array()){

		$data['fnuo_id']=($json['fnuo_id']);

		$data['shop_type']=($json['shop_type']);

		$data['start_price']=floatval($json['start_price']);

		$data['end_price']=floatval($json['end_price']);

		$data['commission']=floatval($json['commission']);

		$data['goods_sales']=intval($json['goods_sales']);

		$data['keyword']=($json['search_keyword']);

		$data['yhq_onoff']=intval($json['yhq_onoff']);

		$data['goods_pd_onoff']=($json['goods_pd_onoff']);

		$data['dtk_goods_onoff']=($json['dtk_goods_onoff']);

		$data['show_type_str']=($json['show_type_str']);

		$data['goods_type_name']='淘宝';

		$data['getGoodsType']='buy_taobao';

		if($data['SkipUIIdentifier']=='pub_jingdongshangpin'){$data['goods_type_name']='京东';$data['getGoodsType']='buy_jingdong';}

		if($data['SkipUIIdentifier']=='pub_pddshangpin'){$data['goods_type_name']='拼多多';$data['getGoodsType']='buy_pinduoduo';}

		$data['goods_msg']=self::getgoodsmsg($data);

		$data['goodsInfo']=$data['goods_title']=$data['goods_msg'][0]['goods_title'];

		return $data;

	}

	//匹配商品详情接口

	public static function getgoodsmsg($data=array()){

		if($data['SkipUIIdentifier']!='pub_xuanzheshangpin')return array();

		$fnuo_id=$data['fnuo_id'];

		$tmp=array();

		if(empty($fnuo_id))return array();

		switch($data['shop_type'].''){

			case "buy_jingdong":

				actionfun("comm/jingdong");

				$tmp=jingdong::id($fnuo_id);

				break;

			case "buy_pinduoduo":

				actionfun("comm/pinduoduo");

				$tmp=pinduoduo::id($fnuo_id);

				break;

			default:

				actionfun("comm/tbmaterial");

				$tmp=tbmaterial::id($fnuo_id);

				break;

		}

		if(empty($tmp))return $tmp;

		

		$tmp=self::comm_update_goods(array($tmp));

		return $tmp;

	}

	static function comm_update_goods($arr_gg=array()){

		$set=self::getset();

		if(empty($arr_gg))return array();

		if(!empty($_GET['fuck']))fpre(reset($arr_gg));

		$arr_gg=zfun::f_fgoodscommission($arr_gg);

		if(!empty($_GET['fuck']))fpre(reset($arr_gg));

		$shop_type=array("淘宝","淘宝","天猫","京东","京东");

		foreach($arr_gg as $k=>$v){

			$arr_gg[$k]['shop_type']=$shop_type[$v['shop_id']];

			if($v['shop_id']==4){

				$arr_gg[$k]['shop_id']=3;

				$arr_gg[$k]['fnuo_url']=self::getUrl("gotojingdong","index",array("gid"=>$v['fnuo_id']),"appapi");

				if(!empty($GLOBALS['is_jtt']))$arr_gg[$k]['fnuo_url']=INDEX_WEB_URL."?mod=appapi&act=gotojingdong&gid=".$v['fnuo_id']."&yhq_url=".urlencode($v['yhq_url']);

			}

			if($v['pdd']==1){

				$arr_gg[$k]['shop_id']=5;

				$arr_gg[$k]['shop_type']='拼多多';

				$arr_gg[$k]['fnuo_url']=self::getUrl("gotopinduoduo","index",array("gid"=>$v['fnuo_id']),"appapi");

			}

			$arr_gg[$k]['zhe']=$v['zhe']."折";

			$arr_gg[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";

			if(!empty($v['yhq_price']))$arr_gg[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";

			$arr_gg[$k]['open_iid']=$v['fnuo_id'];

			$arr_gg[$k]['is_mylike']=0;

			$arr_gg[$k]['is_support']=0;

			$arr_gg[$k]['jindu']=intval($v['goods_sales']/100);

			if($arr_gg[$k]['jindu']>95)$arr_gg[$k]['jindu']='95';

			if(floatval($arr_gg[$k]['commission'])<>0)$arr_gg[$k]['is_support']=1;

			$arr_gg[$k]['qgStr']="已抢".$v['goods_sales']."件";

			unset($arr_gg[$k]['detailurl']);

			$arr_gg[$k]['is_qiangguang']=0;

			if(!empty($v['shop_name']))$arr_gg[$k]['shop_title']=$v['shop_name'];

			$arr_gg[$k]['yhq_span']=intval($v['yhq_price'])."元券";

		}

		appcomm::goodsfeixiang($arr_gg);

		appcomm::goodsfanlioff($arr_gg);

		return $arr_gg;

	}



}

?>
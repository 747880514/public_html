<?php
actionfun("comm/order");
//记录当前时间 订单 用户等级 比例佣金
class set_order_user_lvAction extends Action{
	static $set=array();
	//匹配旧的订单的 分享人购买人id
	function check_buy_share_uid(){
		$rebate=zfun::f_select("Rebate","buy_share_uid='0'",NULL,1000,0,"id asc");
		if(empty($rebate))fpre("check_buy_share_uid 完成");
		$rebate_sum=zfun::f_count("Rebate","buy_share_uid='0'");
		fpre("rebate_sum : ".$rebate_sum);
		$order=zfun::f_kdata("Order",$rebate,"orderId","orderId","id,orderId,uid,share_uid");
		//过滤重复记录
		$cf_arr=array();
		foreach($rebate as $k=>$v){
			$key=$v['orderId'].'';
			if(!empty($cf_arr[$key])){continue;}$cf_arr[$key]=1;//过滤重复记录
			if(empty($order[$v['orderId'].''])){
				zfun::f_update("Rebate","id='".$v['id']."'",array("buy_share_uid"=>"null"));
				continue;
			}
			$one=$order[$v['orderId'].''];
			if(intval($one['uid'])!='0')$buy_share_uid=$one['uid'];
			elseif(intval($one['share_uid'])!='0')$buy_share_uid=$one['share_uid'];
			else $buy_share_uid='null';
			zfun::f_update("Rebate","orderId='".$v['orderId']."'",array("buy_share_uid"=>$buy_share_uid));
		}
	}
	function index(){
		//if(empty($_GET['run']))zfun::fecho("测试中断");
		ignore_user_abort();set_time_limit(0);
		self::check_buy_share_uid();//匹配旧的订单的 分享人购买人id
		$where="(uid>0 or share_uid >0) and status IN('创建订单','订单付款','订单成功','订单结算','订单失效') and is_rebate=0";
		fpre(zfun::f_count("Order",$where));
		$order=zfun::f_select("Order",$where,"id,orderId,status,orderType,uid,share_uid,is_rebate,commission,createDate,now_user,returnstatus",100,0,"id asc");
		if(empty($order))$order=array();
		$order=zfun::ordercommission($order);

		foreach($order as $k=>$v){
			order::$set['tmp_oid']=$v['orderId'];
			$uid=$v['uid'];
			if($v['share_uid']!='0')$uid=$v['share_uid'];
			$buy_share_uid=$uid;//购买分享人id
			$now_user=json_decode($v['now_user'],true);
			order::$set['now_user']=json_decode($v['now_user'],true);//读取预设 用户等级
			if(empty(order::$set['now_user']))order::$set['now_user']=array();
			if($v['orderType'].''=='1')$v['orderType']='tb';
			$ex_user=order::get_extend($uid);

			$ex_user=$ex_user['user_arr'];
			$order_arr=array();
			if(empty($now_user)){//存入当前 会员等级
				$now_user=array();
				foreach($ex_user as $k1=>$v1){
					$now_user[$v1['id'].'']=$v1['lv'];
				}
				$order_arr['now_user']=addslashes(json_encode($now_user));
			}

			//百里.花蒜重置返佣比,记得修改下方循环的2处bili=>hs_bili
			$ex_user = self::hs_fenyong($ex_user);

			foreach($ex_user as $k1=>$v1){
				//百里
				if($v1['hs_bili'] <= 0)
				{
					continue;
				}
				$arr=array(
					"uid"=>$v1['id'],
					"oid"=>$v['id'],
					"orderId"=>$v['orderId'],
					"platform"=>$v['orderType'],
					"order_create_time"=>$v['createDate'],
					// "bili"=>$v1['bili'],	//百里
					"bili"=>$v1['hs_bili'],	//百里
					"time"=>time(),
					"lv"=>$v1['lv'],
					"gx_str"=>$v1['gx_str'],
					"gx_id"=>$v1['gx_id'],
					"comment"=>$v1['gx_type_str'],
					// "fcommission"=>zfun::dian(doubleval($v['commission'])*$v1['bili'],1000),//返利佣金	百里
					"fcommission"=>zfun::dian(doubleval($v['commission'])*$v1['hs_bili'],1000),//返利佣金	百里
					"status"=>$v['status'],//订单状态
					"returnstatus"=>$v['returnstatus'],//是否已经返利
					"buy_share_uid"=>$buy_share_uid,//购买分享人id jj explosion
				);

				//判断是否自购分享
				if($v1['gx_type_str']=='自购分享'){
					if($v['share_uid'].''!='0')$arr['comment']="分享";
					else $arr['comment']='自购';
				}
				$comment=$arr['comment'];
				$where="uid='".$arr['uid']."' and oid='".$arr['oid']."'";
				$tmp=zfun::f_row("Rebate",$where);

				// 百里.修改前
				// if(empty($tmp))zfun::f_insert("Rebate",$arr);
				// else{
				// 	//只更新佣金 不更新关系
				// 	$arr=array();
				// 	$arr['fcommission']=zfun::dian(doubleval($v['commission'])*doubleval($tmp['bili']),1000);//返利佣金;
				// 	$arr['status']=$v['status'];//订单状态
				// 	$arr['comment']=$comment;
				// 	zfun::f_update("Rebate",$where,$arr);
				// }
				// 百里.修改后
				if($v1['gx_type_str'] == '订单分红')
				{
					zfun::f_insert("Rebate",$arr);
				}
				else
				{
					if(empty($tmp))zfun::f_insert("Rebate",$arr);
					else{
						//只更新佣金 不更新关系
						$arr=array();
						$arr['fcommission']=zfun::dian(doubleval($v['commission'])*doubleval($tmp['bili']),1000);//返利佣金;
						$arr['status']=$v['status'];//订单状态
						$arr['comment']=$comment;
						zfun::f_update("Rebate",$where,$arr);
					}
				}
			}

			//修改订单 已分配状态
			$order_arr['is_rebate']=1;
			zfun::f_update("Order","id='".$v['id']."'",$order_arr);
		}

		/*
		if(count($order)!=0){
			echo '<script>window.location=window.location.href;</script>';
		}
		else{die("完成");}*/

		zfun::fecho("run ".count($order),array(),1);
	}

	/**
	 * [hs_fenyong 花蒜分佣.百里]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2018-11-12T17:21:22+0800
	 * @param    [type]                   $ex_user [description]
	 * @return   [type]                            [description]
	 */
	public static function hs_fenyong($ex_user)
	{

		//百里.每个人能拿到的最大比例
		$total_bili = 0.91;
		$ex_user = array_values($ex_user);	//重置key
		$hs_buy_lv = '';	//购买者等级

		$hs_bili 	= array('v1'=>0.51,'v2'=>0.76,'v3'=>0.88);	//每个等级拿到的最低比例
		// $hs_v1_bili = array(0.10, 0.15, 0.06, 0.06, 0.03);	//蒜头购买：[0]蒜苗分享者/蒜头分享者 [1][2]蒜苗 [3][4]蒜花
		// $hs_v2_bili = array(0.06, 0.06, 0.03);	//蒜苗购买：[0]蒜苗 [1][2]蒜花
		// $hs_v3_bili = array(0.03);	//蒜花购买 [0]蒜花
		$hs_v1_bili = array(0.10, 0.15, 0.06);	//蒜头购买：[0]蒜苗分享者/蒜头分享者 [1][2]蒜苗 [3][4]蒜花
		$hs_v2_bili = array(0.06);	//蒜苗购买：[0]蒜苗 [1][2]蒜花
		// $hs_v3_bili = array(0.03);	//蒜花购买 [0]蒜花

		//重新计算分配比
		$hs_lv = array();
		foreach ($ex_user as $bk => &$bv)
		{
			if($bv['is_sqdl'] > 3)
			{
				$bv['lv'] = 'v3';
			}

			$hs_lv[$bv['lv']] += 1;
			if($hs_lv[$bv['lv']] > 2)
			{
				unset($ex_user[$bk]);
				$hs_lv[$bv['lv']] -= 1;
			}
		}

		foreach ($ex_user as $bk => &$bv)
		{
			$bv['hs_bili'] = 0;
			if($bk == 0)
			{
				switch ($bv['lv']){
					case 'v1':
						$bv['hs_bili'] = $hs_bili['v1'];
						break;
					case 'v2':
						$bv['hs_bili'] = $hs_bili['v2'];
						if($hs_lv['v2'] == 1)
						{
							$bv['hs_bili'] += $hs_v2_bili[0];
							$hs_v2_bili[0] = 0;
						}
						break;
					case 'v3':
						$bv['hs_bili'] = $hs_bili['v3'];
						if($hs_lv['v3'] == 1)
						{
							$bv['hs_bili'] += $hs_v3_bili[0];
							$hs_v3_bili[0] = 0;
						}
						break;
					default:
						$bv['hs_bili'] = 0;
						break;
				}

				$hs_buy_lv = $bv['lv'];
			}
			else
			{
				//蒜头购买
				if($hs_buy_lv == 'v1')
				{
					switch ($bv['lv']) {
						case 'v1':
							$bv['hs_bili'] = $hs_v1_bili[0] > 0 ? $hs_v1_bili[0] : 0;
							$hs_v1_bili[0] = 0;
							break;
						case 'v2':
							if($hs_lv['v2'] == 1)
							{
								if($hs_v1_bili[0] > 0)
								{
									$bv['hs_bili'] = $hs_v1_bili[0];
									$hs_v1_bili[0] = 0;
								}
								$bv['hs_bili'] += $hs_v1_bili[1] + $hs_v1_bili[2];
								$hs_v1_bili[1] = $hs_v1_bili[2] = 0;
							}
							else if($hs_lv['v2'] == 2)
							{
								if($hs_v1_bili[0] > 0)
								{
									$bv['hs_bili'] = $hs_v1_bili[0];
									$hs_v1_bili[0] = 0;
								}
								if($hs_v1_bili[1] > 0)
								{
									$bv['hs_bili'] += $hs_v1_bili[1];
									$hs_v1_bili[1] = 0;
								}
								elseif($hs_v1_bili[1] == 0 && $hs_v1_bili[2] > 0)
								{
									$bv['hs_bili'] += $hs_v1_bili[2];
									$hs_v1_bili[2] = 0;
								}
							}
							break;

						// case 'v3':
						// 	if($hs_lv['v2'] == 0 || !isset($hs_lv['v2']))
						// 	{
						// 		if($hs_v1_bili[0] > 0)
						// 		{
						// 			$bv['hs_bili'] += $hs_v1_bili[0];
						// 			$hs_v1_bili[0] = 0;
						// 		}
						// 		if($hs_v1_bili[1] > 0)
						// 		{
						// 			$bv['hs_bili'] += $hs_v1_bili[1];
						// 			$hs_v1_bili[1] = 0;
						// 		}
						// 		if($hs_v1_bili[2] > 0)
						// 		{
						// 			$bv['hs_bili'] += $hs_v1_bili[2];
						// 			$hs_v1_bili[2] = 0;
						// 		}
						// 	}

						// 	if($hs_lv['v3'] == 1)
						// 	{
						// 		$bv['hs_bili'] += $hs_v1_bili[3];
						// 		$bv['hs_bili'] += $hs_v1_bili[4];
						// 		$hs_v1_bili[3] = $hs_v1_bili[4] = 0;
						// 	}
						// 	else if($hs_lv['v3'] == 2)
						// 	{
						// 		if($hs_v1_bili[3] > 0)
						// 		{
						// 			$bv['hs_bili'] += $hs_v1_bili[3];
						// 			$hs_v1_bili[3] = 0;
						// 		}
						// 		else if($hs_v1_bili[4] > 0)
						// 		{
						// 			$bv['hs_bili'] += $hs_v1_bili[4];
						// 			$hs_v1_bili[4] = 0;
						// 		}
						// 	}
						// 	break;
					}
				}
				//蒜苗购买
				else if($hs_buy_lv == 'v2')
				{
					switch ($bv['lv']) {
						case 'v2':
							if($hs_lv['v2'] == 2)
							{
								$bv['hs_bili'] = $hs_v2_bili[0];
								$hs_v2_bili[0] = 0;
							}

						// case 'v3':
						// 	if($hs_lv['v3'] == 1)
						// 	{
						// 		$bv['hs_bili'] = $hs_v2_bili[1] + $hs_v2_bili[2];
						// 		$hs_v2_bili[1] = $hs_v2_bili[2] = 0;
						// 	}
						// 	else if($hs_lv['v3'] == 2)
						// 	{
						// 		if($hs_v2_bili[1] > 0)
						// 		{
						// 			$bv['hs_bili'] = $hs_v2_bili[1];
						// 			$hs_v2_bili[1] = 0;
						// 		}
						// 		else if($hs_v2_bili[2] > 0)
						// 		{
						// 			$bv['hs_bili'] = $hs_v2_bili[2];
						// 			$hs_v2_bili[2] = 0;
						// 		}
						// 	}
						// 	break;
					}
				}
				//蒜花购买
				else if($hs_buy_lv == 'v3')
				{
					// switch ($bv['lv']) {
					// 	case 'v3':
					// 		$bv['hs_bili'] = $hs_v3_bili[0] > 0 ? $hs_v3_bili[0] : 0;
					// 		$hs_v3_bili[0] = 0;
					// 		break;
					// }
				}
			}
		}

		//9%的团队分佣
		$ex_user_exp = self::hs_fenyong_exp($ex_user[0]['id']);
		$ex_user = array_merge($ex_user,$ex_user_exp);

		return $ex_user;
	}

	/**
	 * [hs_fenyong_exp 9%的分佣.百里]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2019-01-11T11:42:50+0800
	 * @return   [type]                   [description]
	 */
	public static function hs_fenyong_exp($uid)
	{
		//查询会员的所有上级
		actionfun("comm/twoway");
		$extends = twoway::get_extend_all($uid);
		$extends = array_values($extends);

		unset($extends[0]);	//删除当前会员单元

		//最高3级分佣
		$lvnum = array('3'=>0,'4'=>0,'5'=>0,'6'=>0);	//每一级个数
		$level = array('3'=>0.03,'4'=>0.05,'5'=>0.07,'6'=>0.09);	//每一级预估分佣比
		foreach ($extends as $key => $value)
		{
			//高于蒜花才拿分佣
			if($value['is_sqdl'] >= 3)
			{
				if($lvnum[$value['is_sqdl']] < 4)
				{
					$lvnum[$value['is_sqdl']]++;
				}
				else
				{
					unset($extends[$key]);
				}
			}
			else
			{
				unset($extends[$key]);
				continue;
			}
		}
		$extends = array_values($extends);

		$level2 = array('3'=>0,'4'=>0,'5'=>0,'6'=>0);	//每一级确定分佣比
		foreach ($lvnum as $key => $value)
		{
			//存在
			if($value > 0)
			{
				$level2[$key] = $level[$key] - array_sum($level2);//预估百分比求极差
			}
		}

		//等级对应返利百分比(极差算法)
		$gx_str = $gx_id = "";
		foreach ($extends as $key => &$value)
		{
			if($extends[$key-1])
			{
				$gx_str = ",".$gx_str;
				$gx_id = ",".$gx_id;
			}
			$gx_str = $value['lv'] . $gx_str;
			$gx_id = $value['id'] . $gx_id;
			//比例=等级对应返利比例 - 已返的总比例
			$value['hs_bili'] = sprintf("%.3f", $level2[$value['is_sqdl']] / $lvnum[$value['is_sqdl']]);	//每一级确定百分比/每一级个数
			$value['hs_bili'] = max($value['hs_bili'], 0);

			$value['gx_str'] = $gx_str;
			$value['gx_id'] = $gx_id;
			$value['gx_type_str'] = '订单分红';
		}

		return $extends;
	}

}
?>
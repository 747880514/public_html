<?php
actionfun("comm/order");
//记录当前时间 订单 用户等级 比例佣金
class set_order_user_lvAction extends Action{
	static $set=array();
	function index(){
		//if(empty($_GET['run']))zfun::fecho("测试中断");
		ignore_user_abort();set_time_limit(0);
		$where="(uid>0 or share_uid >0) and status IN('创建订单','订单付款','订单结算','订单失效') and is_rebate=0";
		//fpre(zfun::f_count("Order",$where));
		$order=zfun::f_select("Order",$where,"id,orderId,status,orderType,uid,share_uid,is_rebate,commission,createDate,now_user,returnstatus",100,0,"id asc");
		if(empty($order))$order=array();
		$order=zfun::ordercommission($order);
		
		foreach($order as $k=>$v){
			order::$set['tmp_oid']=$v['orderId'];
			$uid=$v['uid'];
			if($v['share_uid']!='0')$uid=$v['share_uid'];
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

			//百里.每个人能拿到的最大比例
			$baili_first = false;
			$total_bili = 0.91;
			foreach ($ex_user as $bk => &$bv) {
				//计算拿最大比
				switch ($bv['is_sqdl']) {
					case '1':
						$bv['max_bili'] = 0.51;
						break;
					case '2':
						$bv['max_bili'] = 0.82;
						break;
					case '3':
						$bv['max_bili'] = 0.91;
						break;
					default:
						$bv['max_bili'] = 0.51;
						break;
				}

				//计算当前比例和最大比例差
				$bv['cha_bili'] = $bv['max_bili'] - $bv['bili'] > 0 ? $bv['max_bili'] - $bv['bili'] : 0;

				//计算购买人拿的最大比(总最大比)
				// if(!$baili_first)
				// {
				// 	$total_bili = $bv['max_bili'];
				// 	$baili_first = true;
				// }

				//计算剩余比
				$total_bili -= $bv['bili'];
			}


			//百里.重新计算比例
			foreach ($ex_user as $bk => &$bv) {
				//蒜头最高10%,多余10%拿出来给上级分
				if($bv['is_sqdl'] == '1' && $bv['bili'] > 0.1)
				{
					$bv['bili'] = 0.1;
					$total_bili += $bv['bili'] - 0.1;
				}
				else
				{
					if($bv['cha_bili'] > 0 && $total_bili > 0)
					{
						$this_bili = $total_bili - $bv['cha_bili'] > 0 ? $bv['cha_bili'] : $total_bili;
						$bv['bili'] += $this_bili;
						$total_bili -= $this_bili;
					}
				}
			}

			foreach($ex_user as $k1=>$v1){
				$arr=array(
					"uid"=>$v1['id'],
					"oid"=>$v['id'],
					"orderId"=>$v['orderId'],
					"platform"=>$v['orderType'],
					"order_create_time"=>$v['createDate'],
					"bili"=>$v1['bili'],
					"time"=>time(),
					"lv"=>$v1['lv'],
					"gx_str"=>$v1['gx_str'],
					"gx_id"=>$v1['gx_id'],
					"comment"=>$v1['gx_type_str'],
					"fcommission"=>zfun::dian(doubleval($v['commission'])*$v1['bili'],1000),//返利佣金
					"status"=>$v['status'],//订单状态
					"returnstatus"=>$v['returnstatus']//是否已经返利
				);

				//判断是否自购分享
				if($v1['gx_type_str']=='自购分享'){
					if($v['share_uid'].''!='0')$arr['comment']="分享";
					else $arr['comment']='自购';
				}

				$where="uid='".$arr['uid']."' and oid='".$arr['oid']."'";
				$tmp=zfun::f_row("Rebate",$where);

				if(empty($tmp))zfun::f_insert("Rebate",$arr);
				else{
					//只更新佣金 不更新关系
					$arr=array();
					$arr['fcommission']=zfun::dian(doubleval($v['commission'])*doubleval($tmp['bili']),1000);//返利佣金;
					$arr['status']=$v['status'];//订单状态
					zfun::f_update("Rebate",$where,$arr);
				}
			}

			//修改订单 已分配状态
			$order_arr['is_rebate']=1;
			zfun::f_update("Order","id='".$v['id']."'",$order_arr);
		}

		/*
		if(count($order)==25){
			echo '<script>window.location=window.location.href;</script>';
		}
		else{die("完成");}
		*/
		

		zfun::fecho("run ".count($order),array(),1);
	}

}
?>
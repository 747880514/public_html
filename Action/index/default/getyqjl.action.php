<?php

ignore_user_abort();

set_time_limit(0);
class getyqjlAction extends Action{
	public function index(){
		$time='1524222916';
		
		$str="operator_yqzcjl1,operator_yqzcjl2,fxdl_yqzcjl1,fxdl_yqzcjl2,fxdl_yqzcjl3,fxdl_yqzcjl4,fxdl_yqzcjl5";
		$str.=",fxdl_yqzcjl6,fxdl_yqzcjl7,fxdl_yqzcjl8,fxdl_yqzcjl9,fxdl_yqzcjl10";
		$set=zfun::f_getset($str.",get_newrule_time,yq_fh_onoff,yqcommission01,commission_spread");
		$yqcommission01=floatval($set['yqcommission01']);
		if(!empty($set['get_newrule_time']))$time=$set['get_newrule_time'];
		zfun::wfile("123.log",time());
		if(empty($set['yq_fh_onoff']))zfun::fecho("不是这种模式");
		if(empty($yqcommission01))zfun::fecho("后台满多少消费金额没设置");
		$times=time();

		//百里.修改前
		// $user=zfun::f_select("User","reg_time>$time and (next_visit_time<$times ) and extend_id<>0 and is_fan_yqyj=0 ","extend_id,is_sqdl,id,commission",200,0,"id asc");
		//百里.修改后
		$user=zfun::f_select("User","reg_time>$time and (next_visit_time<$times ) and extend_id<>0 and is_fan_yqyj=0 and commission>".$yqcommission01,"extend_id,is_sqdl,id,commission,huasuan_jsmoney",200,0,"id asc");

		fpre($user);
		if(empty($user))zfun::fecho("没用户");
		$extend=zfun::f_kdata("User",$user,"extend_id","id");
		foreach($user as $k=>$v){
			
			$commission_spread=floatval($set['fxdl_yqzcjl'.(intval($extend[$v['extend_id']]['is_sqdl'])+1)]);
			if($extend[$v['extend_id']]['operator_lv']=='1'){
				$commission_spread=floatval($set['operator_yqzcjl1']);
			}
			if($extend[$v['extend_id']]['operator_lv']=='2'){
				$commission_spread=floatval($set['operator_yqzcjl2']);
			}
			if($commission_spread==0){
				zfun::f_update("User","id='".$v['id']."'",array("next_visit_time"=>$times+12*3600));
				continue;
			}
			$sum=zfun::f_sum("Order","(status='订单结算') and uid='".$v['id']."'","payment");
			if($sum<$yqcommission01){
				zfun::f_update("User","id='".$v['id']."'",array("next_visit_time"=>$times+12*3600));
				echo '用户id='.$v['id'].'，不满足消费<br>';
				continue;
			}

			//百里.重置奖励金额
			if($v['huasuan_jsmoney'] > 0)
			{
				$commission_spread = $v['huasuan_jsmoney'];
			}
			
			$arr = array(
				"uid" => $v['extend_id'],
				"interal" => $commission_spread,
				"detail" => "邀请好友注册获得 $commission_spread 佣金",
				"time" => time(),
				'type' => 100,
				"data"=>$v['id'],
				"next_id"=>$v['id'],
			);

			zfun::f_update("User","id='".$v['id']."'",array("is_fan_yqyj"=>1));
			$tmp=zfun::f_count("Interal"," next_id='".$v['id']."'");
			if(!empty($tmp)){continue;}
			zfun::f_insert("Interal",$arr);
			zfun::addval("User","id='".$v['extend_id']."'",array("commission"=>$commission_spread));
			
		}
		zfun::fecho("成功了");
	}
}
?>
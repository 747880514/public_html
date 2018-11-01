<?php

include_once ROOT_PATH.'Action/index/appapi/dgappcomm.action.php';
include_once ROOT_PATH.'Action/index/appapi/tzbs_new.action.php';
include_once ROOT_PATH.'Action/index/default/api.action.php';
include_once ROOT_PATH.'Action/index/appapi/dg_payment.action.php';
class appHhr02Action extends Action{
	//合伙人申请页面
	public function index(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$user=appcomm::getheadimg(array($user));$user=reset($user);
		$data=array("is_hhr"=>0);
		$lv=$user['is_sqdl']+1;
		if($user['is_sqdl']>0)$data['is_hhr']=1;
		//如果是运营商
		if($user['operator_lv'].''!='0'){
			$data['is_hhr']=1;
			$user['hhr_gq_time']=$user['operator_time'];
		}
		$data['head_img']=$user['head_img'];
		$phone=$user['phone'];
		if(empty($phone))$phone=$user['nickname'];
		$str1="hq_bjImg,hq_conImg,hq_tqImg,hq_btnImg1,hq_btnImg2,hq_rule_img,hq_font_color01,hq_str01,hhr_rules";
		$set=zfun::f_getset($str1.",hhr_rules,fxdl_name".$lv);
		$data['bjImg']=INDEX_WEB_URL."View/index/img/appapi/comm/vip_bj0.png";
		if(!empty($set['hq_bjImg']))$data['bjImg']=UPLOAD_URL."slide/".$set['hq_bjImg'];
		$data['conImg']=INDEX_WEB_URL."View/index/img/appapi/comm/vip_bj.png";
		if(!empty($set['hq_conImg']))$data['conImg']=UPLOAD_URL."slide/".$set['hq_conImg'];
		$data['ruleImg']=INDEX_WEB_URL."View/index/img/appapi/comm/vip_rule.png";
		if(!empty($set['hq_rule_img']))$data['ruleImg']=UPLOAD_URL."slide/".$set['hq_rule_img'];
		$data['tqImg']=INDEX_WEB_URL."View/index/img/appapi/comm/vip_pic1.png";
		if(!empty($set['hq_tqImg']))$data['tqImg']=UPLOAD_URL."slide/".$set['hq_tqImg'];
		$data['btnImg1']=INDEX_WEB_URL."View/index/img/appapi/comm/vip_btn1.png";
		if(!empty($set['hq_btnImg1']))$data['btnImg1']=UPLOAD_URL."slide/".$set['hq_btnImg1'];
		$data['btnImg2']=INDEX_WEB_URL."View/index/img/appapi/comm/vip_btn2.png";
		if(!empty($set['hq_btnImg2']))$data['btnImg2']=UPLOAD_URL."slide/".$set['hq_btnImg2'];

		$data['rule']=$set['hhr_rules'];
		$str="";
		$str2=$set['fxdl_name'.$lv];
		$operator_set=zfun::f_getset("operator_name,operator_name_2");
		if($user['operator_lv'].''=='1')$str2=$operator_set['operator_name'];
		if($user['operator_lv'].''=='2')$str2=$operator_set['operator_name_2'];
		if($data['is_hhr']==1&&!empty($user['hhr_gq_time']))$str="您的".$str2."等级将于".date("Y-m-d",$user['hhr_gq_time'])."过期";
		if($data['is_hhr']==1&&!empty($user['hhr_gq_time'])&&$user['hhr_gq_time']<time())$str=$str="您的".$str2."等级已过期";
		$set['hq_font_color01']=str_replace("#","",$set['hq_font_color01']);
		if(empty($set['hq_str01']))$set['hq_str01']='少花钱，多生钱';
		if(empty($set['hq_font_color01']))$set['hq_font_color01']='FFFFFF';

		$data['font']=array(
			array(
				"str"=>$set['hq_str01'],
				"font_color"=>$set['hq_font_color01'],
			),
			array(
				"str"=>$str2,
				"font_color"=>$set['hq_font_color01'],
			),
			array(
				"str"=>$phone,
				"font_color"=>$set['hq_font_color01'],
			),
			array(
				"str"=>$str,
				"font_color"=>$set['hq_font_color01'],
			),
		);


		zfun::fecho("合伙人申请页面",$data,1);
	}
	//等级
	public function level(){
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$lv1=$user['is_sqdl']+1;
		$set=zfun::f_getset("fxdl_lv,fxdl_money".$lv1);
		$fxdl_lv = intval($set['fxdl_lv']);
		$dq_money=$set['fxdl_money'.$lv1];

		$str="CustomUnit";
		//运营商升级费
		$str.=",operator_update_money,operator_onoff,operator_name";
		for ($i = 2; $i <= $fxdl_lv; $i++) {
			$str.=",fxdl_name".$i;
			$str.=",fxdl_money".$i;
			$str.=",fxdl_ljzt".$i;
		}
		$fxdldata=array();
		$set=zfun::f_getset($str);
		$set['operator_onoff']=intval($set['operator_onoff']);
		//爆炸
		$set['operator_update_money']=doubleval($set['operator_update_money']);

		if($set['operator_update_money']=='0'){//没有设置
			$operator_update_str="免费升级".$set['operator_name'];
		}
		else{
			$_POST['id']='operator';
			$set['operator_update_money']=dg_paymentAction::difference_rule($user,$set['operator_update_money'],$dq_money);
			unset($_POST['id']);
			$operator_update_str=$set['operator_update_money'].$set['CustomUnit'].'升级'.$set['operator_name'];
		}

		$lv=$user['is_sqdl']+2;
		if($_POST['is_xufei']==1){
			$lv=$user['is_sqdl']+1;
			$fxdl_lv=$user['is_sqdl']+1;
		}
		for ($i = $lv; $i <= $fxdl_lv; $i++) {
			$_POST['id']=$i;
			$fxdldata[$i]['id']=$i;
			$fxdldata[$i]['title']=$set['fxdl_name'.$i];
			$money =$set['fxdl_money'.$i];
			$new_money=dg_paymentAction::difference_rule($user,$money,$dq_money);
			// if(empty($money)||$new_money<=0)$new_money="免费";
			// if(empty($money)||$new_money<=0)$new_money=$set['fxdl_ljzt'.$i]."人付费 ";
			// else $new_money.=$set['CustomUnit'];
			if(empty($money)||$new_money<=0)
			{
				if($fxdldata[$i]['title'] == '蒜头')
				{
					$new_money="邀请".$set['fxdl_ljzt'.$i]."人注册 免费";
				}
				if($fxdldata[$i]['title'] == '蒜苗')
				{
					$new_money="免费";
				}
				if($fxdldata[$i]['title'] == '蒜花')
				{
					$new_money="累计付费蒜苗".$set['fxdl_ljzt'.$i]."人 免费";
				}
			}
			else
			{
				$new_money.=$set['CustomUnit'].'/年 ';
			}

			if($_POST['is_xufei']==1){
				if(empty($money))$money="免费";
				else $money.=$set['CustomUnit'];
				$new_money=$money;
			}
			$fxdldata[$i]['title']=$new_money."升级".$fxdldata[$i]['title'];
		}
		$fxdldata=array_values($fxdldata);
		//续费了,而且是运营商
		if($user['operator_lv'].''!='0'){$fxdldata=array();}
		//fpre($fxdldata);

		//运营商处理
		do{
			if($set['operator_onoff'].''!='1')break;//运营商功能已关闭
			if($user['operator_lv'].''!='0'&&$_POST['is_xufei']==0)break;//已经是运营商，不是续费的时候判断
			if($user['operator_lv'].''=='0'&&$_POST['is_xufei']==1)break;//不是运营商，续费的时候判断
			$fxdldata[]=array(
				"id"=>"operator",
				"title"=>$operator_update_str,
			);
		}while(0);



		if(empty($fxdldata))zfun::fecho("已达到最高等级");


		zfun::fecho("等级",$fxdldata,1);
	}


}
?>
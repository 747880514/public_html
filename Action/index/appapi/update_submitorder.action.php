<?php

actionfun("appapi/dgappcomm");

actionfun("appapi/update_goods");

class update_submitorderAction extends Action{

	//展示提交订单信息

	function show_submitorder(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$check=appcomm::parametercheck("num,id");

		$num=intval($_POST['num']);

		$gid=intval($_POST['id']);

		$attr_id=filter_check($_POST['attr_id']);

		$goods=zfun::f_row("Updategoods","id='{$gid}'");

		$goods=update_goodsAction::update_goods($goods);

		if(empty($goods))zfun::fecho("商品不存在");

		$goods['goods_price']=zfun::dian($goods['price']*$num);

		$goods['payment']=zfun::dian($goods['goods_price']+$goods['postage']);

		$goods['postage_str']="快递 包邮";

		if(!empty($goods['postage']))$goods['is_postage']="快递 ".$goods['postage']."元";

		//属性

		$goods['attr']=self::attr($attr_id,$goods);

		/***********收货地址**************/

		$address=zfun::f_row("ReceiptAddress","defauls=0 and user_id='{$uid}'");

		$address=update_goodsAction::update_address($address);

		$is_hasaddress=0;

		if(!empty($address))$is_hasaddress=1;

		$data=array();

		$data['payment']=$goods['payment'];

		$data['is_hasaddress']=$is_hasaddress;

		$data['address']=array(

			"name"=>$address['name'],

			"phone"=>$address['phone'],

			"address_id"=>$address['id'],

			"address_msg"=>$address['address'],

		);

		$data['goodsInfo']=array(

			"num"=>"x".$num,

			"id"=>$goods['id'],

			"price"=>$goods['price'],

			"img"=>$goods['img'],

			"attr"=>$goods['attr'],

			"title"=>$goods['title'],

			"label1"=>$goods['label1'],

			"label_fontcolor1"=>$goods['label_fontcolor1'],

			"label_bjcolor1"=>$goods['label_bjcolor1'],

		);

		$data['msg']=array(

			array(

				"str"=>'小计',

				"val"=>"￥".$goods['goods_price'],

			),

			array(

				"str"=>'配送方式',

				"val"=>$goods['is_postage'],

			),

		);

		$is_not_pay=0;

		if($user['commission']-$goods['payment']<0)$is_not_pay=1;

		$data['alipay_type']=array(

			array(

				"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/icon_shop_tab.png?time'.time(),

				"str"=>'支付宝',

				"val"=>"",

				"type"=>"zfb",

				"is_not_pay"=>0,

			),

			array(

				"img"=>INDEX_WEB_URL.'View/index/img/appapi/comm/icon_shop_tab1.png?time'.time(),

				"str"=>'余额',

				"val"=>$user['commission']."元",

				"type"=>"yue",

				"is_not_pay"=>$is_not_pay,

			),

		);

		

	

		zfun::fecho("提交订单信息",$data,1);

	}

	//提交订单

	function create(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$set=zfun::f_getset("update_goods_onoff");

		if(intval($set['update_goods_onoff'])==0)zfun::fecho("该商品暂时不能购买");

		zfun::add_f($uid);//防止并发

		$tmp=appcomm::parametercheck("num,id,aid,alipay_type");

		/**************商品信息******************/

		

		$goods=zfun::f_row("Updategoods","id='".$tmp['id']."'",$fi);$goods=update_goodsAction::update_goods($goods);

		if(empty($goods))zfun::fecho("商品不存在");

		if($goods['stock']-$tmp['num']<0)zfun::fecho("库存不足,剩下".$goods['stock']."件");

		

		//判断是否错误升级

		if(strstr($goods['type'],"operator")&&$user['operator_lv'].''!='0')zfun::fecho("您的等级超过该商品可升级等级");

		elseif(strstr($goods['type'],"operator")==false&&doubleval($goods['type'])<doubleval($user['is_sqdl']))zfun::fecho("您的等级超过该商品可升级等级");

		$address=zfun::f_row("ReceiptAddress","id='".$tmp['aid']."' and user_id='{$uid}'");$address=update_goodsAction::update_address($address);

		$attr_id=filter_check($_POST['attr_id']);

		/*************json****************/

		$goods['attr']=self::attr($attr_id,$goods);

		if(empty($attr_id))$goods['attr']='';

		$json=array();$data=array();$time=time();

		$goods['num']=$tmp['num'];

		$json['goods']=$goods;

		$json['address']=$address;

		$json['num']=$tmp['num'];

		

		/*************表数据****************/

		$data['append']=base64_encode(json_encode($json));

		$data['uid']=$uid;

		$data['alipay_type']=$tmp['alipay_type'];

		$data['create_time']=$time;

		$data['update_type']=$goods['type'];

		$data['oid']=$uid.$time;

		$data['payment']=zfun::dian($goods['price']*$tmp['num']+$goods['postage']);

		$data['is_pay']=0;//是否已付款

		zfun::f_insert("Updateorder",$data);

		$arr=array("oid"=>$data['oid']);

		zfun::fecho("提交成功",$arr,1);	

	}

	//余额支付

	function yue_pay(){

		$user=appcomm::signcheck(1,"id,phone,commission");$uid=$user['id'];

		//防止并发

		zfun::add_f($uid."yue");

		$set=appcomm::parametercheck("oid");$oid=$set['oid'];

		$order=zfun::f_row("Updateorder","oid='{$oid}'");

		if(empty($order))zfun::fecho("订单不存在");

		if($order['is_pay']==1)zfun::fecho("订单已付款");

		$payment=zfun::dian($order['payment']);

		$money=$user['commission']-$payment;

		if($money<0)zfun::fecho("余额不足");

		zfun::addval("User","id='".$user['id']."'",array("commission"=>-$payment));

		$result=$GLOBALS['action']->payment_success($oid,"yue");

		if($result==0)zfun::fecho("支付失败");

		zfun::fecho("支付成功",1,1);

	}

	//支付宝支付

	function app_payment(){

		$user=appcomm::signcheck(1,"id,phone");$uid=$user['id'];

		//防止并发

		zfun::add_f($uid."p");

		$set=appcomm::parametercheck("oid");$oid=$set['oid'];

		$order=zfun::f_row("Updateorder","oid='{$oid}'");

		if(empty($order))zfun::fecho("订单不存在");

		if($order['is_pay']==1)zfun::fecho("订单已付款");

		$goods=json_decode(base64_decode($order['append']),true);

		$goods=$goods['goods'];

		$body=mb_substr($goods['title'],0,10,'utf-8');//字符截取

		actionfun("comm/alipay");

		$param=array(

			"body"=>$body,

			"payment"=>zfun::dian($order['payment']),

			"uid"=>$order['uid'],

			"type"=>"代理升级",

			"mac"=>"appapi update_submitorder payment_success",//回调

			"database"=>"Updateorder",

			"where"=>"oid='".$oid."'",//用于修改回调 付款id

		);

		$code=alipay::create_app_payment_code($param);//创建支付密钥

		zfun::fecho("app支付宝支付",array("code"=>$code),1);

	}

	//支付成功回调

	function payment_success($alipay_id='',$type='zfb'){

		if(empty($alipay_id))zfun::fecho("update_order payment_success alipay_id empty");

		$where="alipay_id='{$alipay_id}'";

		if($type=='yue')$where="oid='{$alipay_id}'";

		$order=zfun::f_row("Updateorder",$where);

		$append=json_decode(base64_decode($order['append']),true);

		if(empty($order)||$order['is_pay']==1)return 0;

		$oid=$order['oid'];

		$uid=$order['uid'];

		$user=zfun::f_row("User","id='{$uid}'");

		if(empty($user))return 0;

		$set=zfun::f_getset("update_goods_lvup_onoff");

		if(($set['update_goods_lvup_onoff'])==1){

			actionfun("comm/update_goods_do");

			update_goods_do::update($order,$user);

		}

		//修改订单为付款状态

		zfun::f_update("Updateorder","oid='{$oid}'",array("is_pay"=>1,"payment_time"=>time()));//写入付款时间

		zfun::addval("Updategoods","id='".$append['goods']['id']."'",array("stock"=>-$append['goods']['num']));

		//百里.会员返款操作
		actionfun("appapi/baili_zhaoshang");
		baili_zhaoshangAction::presell_return($uid);

		return 1;

	}

		

	//获取属性

	static function attr($attr_id='',$goods=array()){

		if(empty($attr_id))return '';

		$attr=explode(",",$attr_id);

		$str='';

		

		foreach($attr as $k=>$v){

			$tmp=explode("_",$v);

			$data=$goods['attr_data'][$tmp[0]];

			

			$attr_val=$data['attr_val'][$tmp[1]];

			$str.=" ".$data['name'].":".$attr_val['name'];

		}

		$str=substr($str,1);

		return $str;

	}

	static function getlvlist(){

		$set=zfun::f_getset("fxdl_lv,operator_name,operator_onoff,fxdl_hyday");

		if(empty($set['fxdl_hyday']))$set['fxdl_hyday']='365';

		$str='';

		for($i=1;$i<=$set['fxdl_lv'];$i++)$str.=",fxdl_name".$i;

		$str=substr($str,1);

		

		$data=zfun::f_getset($str);

		

		$tmp=array();

		$data=array_values($data);

		foreach($data as $k=>$v){

			$tmp[$k]['name']=$v;

			$tmp[$k]['lv']=$k;

		}

		unset($tmp[0]);

		if(intval($set['operator_onoff'])==1){

			$tmp['operator']=array(

				"name"=>$set['operator_name'],

				"lv"=>'operator',

				"day"=>$set['fxdl_hyday'],

			);

		}

		return $tmp;

	}

}

?>
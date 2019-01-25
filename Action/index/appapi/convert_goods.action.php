<?php

actionfun("appapi/dgappcomm");



class convert_goodsAction extends Action{

	//排序

	public function getsort(){

		appcomm::signcheck();

		$arr=array(

			array(

				"name"=>'综合',

				"up_sort"=>'zonghe',

				"down_sort"=>'zonghe',

				"is_has_up"=>0,

			),

			array(

				"name"=>'销量',

				"up_sort"=>'goods_sales_asc',

				"down_sort"=>'goods_sales_desc',

				"is_has_up"=>1,

			),

			array(

				"name"=>'积分',

				"up_sort"=>'integral_asc',

				"down_sort"=>'integral_desc',

				"is_has_up"=>1,

			),

			array(

				"name"=>'金额',

				"up_sort"=>'money_asc',

				"down_sort"=>'money_desc',

				"is_has_up"=>1,

			),

		);

		zfun::fecho("排序",$arr,1);

	}

	//商品分类

	public function cate(){

		appcomm::signcheck();

		$cate=zfun::f_select("Exchangerclass","hide=0","id,name",0,0,"sort desc");

		array_unshift($cate,array("id"=>0,"name"=>"精选"));

		zfun::fecho("商品分类",$cate,1);

	}

	public function goods(){

		$GLOBALS['user']=$user=appcomm::signcheck();

		$time=time();

		$fi='attr_data,id,counts,sales,data,label,label_color,detail_label,goods_type,postage,nowIntegral,oldIntegral,price,img,title,detail_getimg,banner_img';

		$where="hide=0 and startTime<$time and endTime>$time";

		if(!empty($_POST['cid']))$where.=" and cid='".$_POST['cid']."'";

		if(!empty($_POST['SkipUIIdentifier'])&&$_POST['SkipUIIdentifier']!='pub_integral_moneychangegoods'&&$_POST['SkipUIIdentifier']!='pub_integral_changegoods')$where.=" and goods_type LIKE '%".$_POST['SkipUIIdentifier']."%'";

		if($_POST['SkipUIIdentifier']=='pub_integral_moneychangegoods')$where.=" and ((price>0 and nowIntegral=0) or goods_type LIKE '%".$_POST['SkipUIIdentifier']."%')";

		if($_POST['SkipUIIdentifier']=='pub_integral_changegoods')$where.=" and (nowIntegral>0 or goods_type LIKE '%".$_POST['SkipUIIdentifier']."%')";

		if(!empty($_POST['is_integral']))$where.=" and price=0";

		$arr=array("zonghe"=>'sort desc',"goods_sales_asc"=>'sales asc',"goods_sales_desc"=>'sales desc',"integral_asc"=>'nowIntegral asc',"integral_desc"=>'nowIntegral desc',"money_asc"=>'price asc',"money_desc"=>'price desc');

		$sort=$arr[$_POST['sort']];

		if(empty($sort))$sort='sort desc';

		$goods=appcomm::f_goods("ExchangeRes",$where,$fi,$sort,NULL,20);

		$goods=self::comm_goods($goods);

		zfun::fecho("商品",$goods,1);

	}

	//商品详情

	public function detail(){

		$GLOBALS['user']=$user=appcomm::signcheck();

		$id=intval($_POST['id']);

		$fi='attr_data,id,counts,sales,data,label,label_color,detail_label,goods_type,postage,nowIntegral,oldIntegral,price,img,title,detail_getimg,banner_img';

		$goods=zfun::f_row("ExchangeRes","id='".$id."'",$fi);

		if(empty($goods))zfun::fecho("商品不存在");

		$goods=self::comm_goods(array($goods));

		$goods=reset($goods);

		//百里.蒜头不能购买
		if($user['is_sqdl'] < 2)
		{
			$goods['is_can_buy'] = 0;
			$goods['btn_str'] = "会员等级不足";
			$goods['btn_bjcolor']="999999";
		}

		zfun::fecho("商品",$goods,1);

	}

	//购买记录

	public function buy_record(){

		appcomm::signcheck();

		$goods_id=filter_check($_POST['gid']);

		$id=intval($_POST['id']);

		$where="gid<>'' and uid<>0 and gid='$goods_id' and is_pay=1";

		

		$count=zfun::f_count("ExchangeOrder",$where);

		if(empty($count)){

			$rand=rand(0,$zj_count-1);

		}else $where.=" and id>='$id'";

		$data=zfun::f_select("ExchangeOrder",$where,"id,uid,createDate",100,$rand,"createDate desc");

		actionfun("appapi/convert_integral");

		$data=convert_integralAction::sortarr($data,"createDate","asc");

		$arr=array();

		

		foreach($data as $k=>$v){

		

			$user=zfun::f_row("User","id='".intval($v['uid'])."'","nickname,phone,head_img");

			if(empty($user['phone']))$user['phone']=$user['nickname'];

			$time=date("H:i:s",$v['createDate']);

			

			$arr[$k]['str']=convert_integralAction::xphone($user['phone'])."在   ".$time."成功兑换商品 ";

			$arr[$k]['id']=$v['id'];

		}

		$arr=array_values($arr);

		zfun::fecho("购买记录",$arr,1);

	}

	//收货地址

	public function address(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$where="user_id='".$uid."'";

		$fi='province,city,district,area,address,name,phone,id,defauls,label';

		$address=appcomm::f_goods("ReceiptAddress",$where,$fi,"defauls asc,addtime desc",NULL,20);

		$address=self::update_address($address);

		zfun::fecho("收货地址",$address,1);	

	}

	//收货地址公共

	static function update_address($address=array()){

		if(empty($address))return array();

		$type=0;

		if(!empty($address['id'])){$address=array($address);$type=1;}

		$acquiesce=array(1,0);

		foreach($address as $k=>$v){

			$address[$k]['detail_address']=$v['address'];

			$address[$k]['address']=$v['province'].$v['city'].$v['district'].$v['area'].$v['address'];

			$address[$k]['surname']=mb_substr($v['name'],0,1,'utf-8');

			$address[$k]['is_acquiesce']=$acquiesce[$v['defauls']];

		}

		if($type)$address=$address[0];

		return $address;

	}

	//添加

	public function add_address(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$id=intval($_POST['id']);

		$data=zfun::getpost("province,city,district,area,name,phone,address,label");

		$is_acquiesce=intval($_POST['is_acquiesce']);

		$acquiesce=array(1,0);

		$data['defauls']=$acquiesce[$is_acquiesce];

		$data['addtime']=time();

		$data['user_id']=$uid;

		

		

		//如果默认选中

		if($data['defauls']==0){

			zfun::f_update("ReceiptAddress","user_id='$uid' and defauls=0",array("defauls"=>1));

		}

		if(empty($id)){

			$result=zfun::f_insert("ReceiptAddress",$data);

			if($result==false)zfun::fecho("添加失败");

			zfun::fecho("添加成功",1,1);

		}else{

			unset($data['addtime']);

			$result=zfun::f_update("ReceiptAddress","id='$id'",$data);

			if($result==false)zfun::fecho("编辑失败");

			zfun::fecho("编辑成功",1,1);

		}

	}

	//编辑页面

	public function edit_address(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$id=intval($_POST['id']);

		$fi='province,city,district,area,address,name,phone,id,defauls,label';

		$address=zfun::f_row("ReceiptAddress","id='$id'",$fi);

		$address=self::update_address($address);

		zfun::fecho("收货地址编辑页面",$address,1);	

	}

	//删除

	public function del_address(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$id=intval($_POST['id']);

		$result=zfun::f_delete("ReceiptAddress","id='$id' and user_id='{$uid}'");

		if(empty($result))zfun::fecho("删除失败");

		zfun::fecho("删除成功",1,1);	

	}

	//标签

	public function label_list(){

		appcomm::signcheck();

		$data=array(

			array("name"=>'家'),array("name"=>'公司'),array("name"=>'学校'),

		);

		zfun::fecho("标签",$data,1);	

	}

	//公共方法

	public static function comm_goods($goods=array()){

		if(empty($goods))return array();

		

		foreach($goods as $k=>$v){

			$v['nowIntegral']=floatval($v['nowIntegral']);$v['price']=floatval($v['price']);$v['postage']=floatval($v['postage']);

			if(!empty($v['nowIntegral'])){

				$goods[$k]['str']=$v['nowIntegral'].'积分';

			}

			if(!empty($v['price'])){

				$goods[$k]['str']=$v['price'].'元';

			}

			if(!empty($v['nowIntegral'])&&!empty($v['price'])){

				$goods[$k]['str']=$v['nowIntegral'].'积分 + '.$v['price'].'元';

			}

			$goods[$k]['str_color']='FF8314';

			if($v['sales']>=10000)$v['sales']=zfun::dian($v['sales']/10000,10)."w";

			$goods[$k]['sales_str']=$v['sales']."人已兑换";

			$goods[$k]['goods_img']='';

			if(empty($v['label_color']))$goods[$k]['label_color']='FF3E81';

			if(empty($v['label']))$goods[$k]['label_color']='00000000';

			if(!empty($v['img']))$goods[$k]['goods_img']=UPLOAD_URL."integral/".$v['img'];

			$goods[$k]['goods_title']=$v['title'];

			$goods[$k]['goods_cost_price']=$v['oldIntegral'];	$goods[$k]['stock']=$v['counts'];

			$goods[$k]['btn_str']='我要兑换';

			$goods[$k]['btn_color']='FFFFFF';

			$goods[$k]['btn_bjcolor']='FF8314';

			$goods[$k]['tip_str']='';$goods[$k]['tip_bjcolor']='FFEF82';$goods[$k]['tip_color']='FF7800';$goods[$k]['is_can_buy']=1;

			if(!empty($v['nowIntegral'])&&$GLOBALS['user']['integral']-$v['nowIntegral']<0){

				$goods[$k]['tip_str']='您的积分不足，可进入现金兑换';

				$goods[$k]['btn_color']='E8E8E8';

				$goods[$k]['btn_bjcolor']='C8C8C8';

				$goods[$k]['is_can_buy']=0;

			}

			$label=explode(",",$v['detail_label']);

			$goods[$k]['detail_label']=array();

			foreach($label as $k1=>$v1){

				if(empty($v1))continue;

				$goods[$k]['detail_label'][$k1]['str']=$v1;

				$goods[$k]['detail_label'][$k1]['img']=INDEX_WEB_URL."View/index/img/appapi/comm/integral_yes.png";

			}

			$goods[$k]['detail_label']=array_values($goods[$k]['detail_label']);

			$goods[$k]['postage_str']='快递:包邮';

			if(!empty($v['postage']))$goods[$k]['postage_str']='快递:'.$v['postage'].'元';

			$goods[$k]['kf_bjcolor']='FFFFFF';

			$goods[$k]['kf_str']='客服';$goods[$k]['kf_fontcolor']='3C3C3C';$goods[$k]['kf_img']=INDEX_WEB_URL."View/index/img/appapi/comm/integral_kf.png";

			$goods[$k]['buy_img']=INDEX_WEB_URL."View/index/img/appapi/comm/integral_buy.png";

			$img=explode(",",$v['banner_img']);

			$goods[$k]['banner_img']=array();

			foreach($img as $k1=>$v1){

				if(empty($v1))continue;

				$goods[$k]['banner_img'][]=UPLOAD_URL."integral/".$v1;

			}

			$goods[$k]['banner_img']=array_values($goods[$k]['banner_img']);

			if(empty($goods[$k]['banner_img'])&&!empty($goods[$k]['goods_img']))$goods[$k]['banner_img'][]=$goods[$k]['goods_img'];

			$img=explode(",",$v['detail_getimg']);

			$goods[$k]['detail_img']=array();

			foreach($img as $k1=>$v1){

				if(empty($v1))continue;

				$goods[$k]['detail_img'][]=UPLOAD_URL."integral/".$v1;

			}

			$goods[$k]['detail_getimg']=array_values($goods[$k]['detail_getimg']);

			$attr_data=json_decode($v['attr_data'],true);

			$goods[$k]['attr_data']=array();

			if(!empty($attr_data))$goods[$k]['attr_data']=$attr_data;

			unset($goods[$k]['img'],$goods[$k]['title'],$goods[$k]['oldIntegral'],$goods[$k]['counts']);

		}

		return $goods;

	}

	

}

?>
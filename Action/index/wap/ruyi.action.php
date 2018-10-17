<?php

include_once ROOT_PATH."comm/zfun.php";

class ruyiAction extends Action{



	public function set (){

		self::islogin();

		$url=INDEX_WEB_URL."?mod=wap&act=shouTu_wap&name=".urlencode("关于我们");

		$this->assign("url",$url);

		$this->display();

		$this->play();

	}

	public function sanji_dlsy(){

		$uid=intval(self::getUserId());

		self::islogin();

		$user=zfun::f_row("User","id='$uid'","is_sqdl,id,tg_pid,head_img,dlcommission,nickname,phone");$uid=$user['id'];



		$head_img=$user['head_img'];



		if(empty($head_img))$head_img='default.png';



		if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;



		$user['head_img']=$head_img;

		

		if(intval($user['is_sqdl'])==0){

			$url=INDEX_WEB_URL."?mod=wap&act=ruyi&ctrl=daili";

			zfun::jump($url);

		}



		$user['nickname']=self::getSetting("fxdl_name".intval($user['is_sqdl']+1));

		$lv_dl=intval($user['is_sqdl']+1);

		$sett=zfun::f_getset("fxdl_tjhydj_".intval($user['is_sqdl']+1));

		$eids=self::getc($user['id'],"extend_id",$sett["fxdl_tjhydj_".intval($user['is_sqdl']+1)]);

		$where="(status!='创建订单' or status!='订单失效')";

        $where.=" AND ( tg_pid='".filter_check($user['tg_pid'])."' OR uid IN($eids))";

		

		zfun::isoff($where);

		$bms=mktime(0, 0 , 0,date("m"),1,date("Y"));



		$bme=mktime(23,59,59,date("m"),date("t"),date("Y"));



		$lms=mktime(0, 0 , 0,date("m")-1,1,date("Y"));



		$lme=mktime(23,59,59,date("m") ,0,date("Y"));



		$today=strtotime("today");



		$today1=$today+86400;



		$yday=$today-86400;



		



		//$dl_bili=floatval($set['dl_bili']/100);



		$user['tx_url']=$this->getUrl("drawals","withdrawal",array("t"=>6,"token"=>$_POST['token']),"wap");

		$byyg=zfun::f_select("Order",$where." AND createDate>$bms AND createDate<$bme","commission,uid,tg_pid");

	

		$user['byyg']="0.00";

		$darr=self::getcc($user['id'],"extend_id",$sett["fxdl_tjhydj_".intval($user['is_sqdl']+1)]);

		

		$hy_dj=$darr['darr'];

		foreach($byyg as $k=>$v){

		

			if(!empty($v['tg_pid'])){

				$set=zfun::f_getset("fxdl_tjhy_bili1_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili1_".$lv_dl]/100);

			}else{

				$lv=$hy_dj[$v['uid']];

				$set=zfun::f_getset("fxdl_tjhy_bili".$lv."_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili".$lv."_".$lv_dl]/100);

			}

			$user['byyg']+=$v['commission']*$dl_bili;



		}



		$user['byyg']=zfun::dian($user['byyg']);



		$syyg=zfun::f_select("Order",$where." AND createDate>$lms AND createDate<$lme","commission,uid,tg_pid");



		$user['syyg']="0.00";



		

		foreach($syyg as $k=>$v){

			if(!empty($v['tg_pid'])){

				$set=zfun::f_getset("fxdl_tjhy_bili1_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili1_".$lv_dl]/100);

			}else{

				$lv=$hy_dj[$v['uid']];

				$set=zfun::f_getset("fxdl_tjhy_bili".$lv."_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili".$lv."_".$lv_dl]/100);

			}

			$user['syyg']+=$v['commission']*$dl_bili;



		}



		$user['syyg']=zfun::dian($user['syyg']);



		$jrhl=zfun::f_select("Order",$where." AND createDate>$today AND createDate<$today1","commission,uid,tg_pid");



		$jrhl1="0.00";

	

		foreach($jrhl as $k=>$v){

			if(!empty($v['tg_pid'])){

				$set=zfun::f_getset("fxdl_tjhy_bili1_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili1_".$lv_dl]/100);

			}else{

				$lv=$hy_dj[$v['uid']];

				$set=zfun::f_getset("fxdl_tjhy_bili".$lv."_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili".$lv."_".$lv_dl]/100);

			}

			

			$jrhl1+=$v['commission']*$dl_bili;



		}



		$jrhl1=zfun::dian($jrhl1);



		$jrmoney=zfun::f_sum("Order",$where." AND createDate>$today AND createDate<$today1","commission");



		



		$jrcount=zfun::f_count("Order",$where." AND createDate>$today AND createDate<$today1");



		$zrhl=zfun::f_select("Order",$where." AND createDate>$yday AND createDate<$today","commission,uid,tg_pid");



		$zrhl1="0.00";



		foreach($zrhl as $k=>$v){

			if(!empty($v['tg_pid'])){

				$set=zfun::f_getset("fxdl_tjhy_bili1_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili1_".$lv_dl]/100);

			}else{

				$lv=$hy_dj[$v['uid']];

				$set=zfun::f_getset("fxdl_tjhy_bili".$lv."_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili".$lv."_".$lv_dl]/100);

			}

			$zrhl1+=$v['commission']*$dl_bili;



		}



		$zrhl1=zfun::dian($zrhl1);



		$zrmoney=zfun::f_sum("Order",$where." AND createDate>$yday AND createDate<$today","commission");



		$zrcount=zfun::f_count("Order",$where." AND createDate>$yday AND createDate<$today");



		$user['today_yes']=array(



			0=>array(



				"count"=>floatval($jrcount),



				"money"=>floatval($jrmoney),



				"hl_money"=>floatval($jrhl1),



			),



			1=>array(



				"count"=>floatval($zrcount),



				"money"=>floatval($zrmoney),



				"hl_money"=>floatval($zrhl1),



			),



		);

		

		$this->assign("user",$user);

		$this->assign("today",$user['today_yes'][0]);

		$this->assign("yes",$user['today_yes'][1]);

		$this->display();

		$this->play();

	}

	public function sanji_hbimg(){

		$uid=$this->getUserId();

		//self::islogin();

		$user=zfun::f_row("User","id='$uid'");

		

		$data=array();

		$id=intval($_GET['id']);

		$model=zfun::f_row("DgAppModel","id='$id' AND hide=0","id,img_max,content_first,is_check");

		

		$set=zfun::f_getset("AppDisplayName,webset_webnick");

		if($set['AppDisplayName']==''||$set['AppDisplayName']=='花蒜')$set['AppDisplayName']=$set['webset_webnick'];





		if(empty($model['content_first']))$model['content_first']='扫一扫注册下载'.$set['AppDisplayName'];

			

		$url=self::qrcode2($model,$user);

		$model['image']=$url;

		

		$this->assign("img",$model['image']);

		$this->display();

		$this->play();

	}

	public function sanji_order_dl(){

		$uid=intval(self::getUserId());

		self::islogin();

		$user=zfun::f_row("User","id='$uid'","is_sqdl,id,tg_pid,head_img,dlcommission,nickname,phone");$uid=$user['id'];

		//if(intval($user['is_sqdl'])==0||empty($user['tg_pid']))zfun::fecho("您还不是代理");

		$lv_dl=intval($user['is_sqdl']+1);

		$sett=zfun::f_getset("fxdl_tjhydj_".intval($user['is_sqdl']+1));

		$eids=self::getc($user['id'],"extend_id",$sett["fxdl_tjhydj_".intval($user['is_sqdl']+1)]);

		//$where="(status!='创建订单' or status!='订单失效')";

        $where=" ( tg_pid='".filter_check($user['tg_pid'])."' OR uid IN($eids)) AND uid<>".intval($user['id']);



		$num=20;

	

		$order=zfun::f_goods("Order",$where,"id,info,goodsNum,orderType,goods_img,orderId,returnstatus,status,commission,payment,createDate,goodsId,tg_pid,uid","createDate DESC",$arr,$num);

		$sarr=array(



			"创建订单"=>"待付款",



			"订单付款"=>"已付款",



			"订单结算"=>"未到账",



			"订单失效"=>"已失效"



		);

		$lv_dl=intval($user['is_sqdl']+1);

		

		$darr=self::getcc($user['id'],"extend_id",$sett["fxdl_tjhydj_".intval($user['is_sqdl']+1)]);

		$hy_dj=$darr['darr'];

		/*foreach($order as $k=>$v){

			//zheli boom

			//如果没有图片

			$v['commission']=floatval($v['commission']);

			if((empty($order[$k]['goods_img'])&&$v['orderType']==1)||(empty($v['commission'])&&$v['orderType']==1)){

				$tmp=alimamaAction::getcommission($v['goodsId']);

				//fpre(1);

				if(!empty($tmp)){

					$order[$k]['goods_img']="https:".$tmp['pictUrl']."_290x290.jpg";

					$commission=($v['payment']-$v['postage'])*($tmp['tkRate']/100);

					$order[$k]['commission']=$commission=zfun::dian($commission)*intval($v['goodsNum']);

					$up_arr=array(

						"goods_img"=>$order[$k]['goods_img'],

						"commission"=>$commission,

						'info'=>str_replace("'",'',$tmp['title']),

					);

					zfun::f_update("Order","orderId='".$v['orderId']."'",$up_arr);

				}

				

			}	

		}*/

		foreach($order as $k=>$v){

			$order[$k]['status']=$sarr[$v['status']];

			$order[$k]['fnuo_id']=$v['goodsId'];

			if($v['returnstatus']==1)$order[$k]['status']="已到账";

			//$goods=zfun::f_row("Goods","fnuo_id=".intval($v['goodsId']));

			//$goods=zfun::f_fgoodscommission(array($goods));$goods=reset($goods);

			$order[$k]['goods_title']=$v['info'];

			//$order[$k]['goods_img']=$goods['goods_img'];

			$order[$k]['fnuo_url']=$goods['fnuo_url'];

			if(!empty($v['tg_pid'])){

				$set=zfun::f_getset("fxdl_tjhy_bili1_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili1_".$lv_dl]/100);

			}else{

				$lv=$hy_dj[$v['uid']];

				$set=zfun::f_getset("fxdl_tjhy_bili".$lv."_".$lv_dl);

				$dl_bili=floatval($set["fxdl_tjhy_bili".$lv."_".$lv_dl]/100);

			}

			$order[$k]['commission']=zfun::dian($v['commission']*$dl_bili);

			

		}

		$this->assign("order",$order);

		$this->display();

		$this->play();

	}

		public static function getcc($uid, $tidname = "extend_id", $maxlv = 9) {//获取下级



		$maxlv++;



		if (empty($uid))



			return 0;



		$arr = array();



		$arr[0] = intval($uid);



		$lv = 0;



		$eid = 0;



		$tid = $uid;



		do {



			$lv++;



			$user = zfun::f_select("User", "$tidname IN($tid) and $tidname<>0 and $tidname<>''");



			if (!empty($user)) {



				$tid = "";



				foreach ($user as $k => $v)



					$tid .= "," . $v['id'];



				$tid = substr($tid, 1);



				$arr[$lv] = $tid;



			}







		} while(!empty($user)&&$lv<$maxlv);



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

	public function checks(){

		$uid=intval(self::getUserId());

		$count=zfun::f_row("DLList","uid='$uid' AND is_pay=1");

		switch($_GET['type']){

			

			case 1:

				$title='审核中';

				break;

			case 2:

				$title='审核失败';

				break;

		}

		$this->assign("title",$title);

		$this->assign("count",$count);

		$this->display();

		$this->play();

	}

	/*代理*/

	public function daili(){

		$uid=intval(self::getUserId());

		self::islogin();

		$count=zfun::f_row("DLList","uid='$uid' AND is_pay=1");

		$user=zfun::f_row("User","id='$uid'");

		if(!empty($count)){

			switch($count['checks']){

				case 0:

					$url=INDEX_WEB_URL."?mod=wap&act=ruyi&ctrl=checks&type=1";

					zfun::jump($url);

					break;

				case 1:

					if(intval($user['is_sqdl'])>0){

					$url=INDEX_WEB_URL."?mod=wap&act=ruyi&ctrl=sanji_dlsy";

					zfun::jump($url);

					}

					break;

				case 2:

					$time=time();

					if($time-$count['time']<3600){

						$url=INDEX_WEB_URL."?mod=wap&act=ruyi&ctrl=checks&type=2";

						zfun::jump($url);

					}

					break;

			}

		}

		$set=zfun::f_getset("dl_bjt,dl_hy_xz,dl_zdjs,dl_hy_title,webset_webnick");

		$set['AppDisplayName']=$set['webset_webnick'];

		 $fxdl_lv = intval(self::getSetting("fxdl_lv"));



	 	 for ($i = 2; $i <= $fxdl_lv; $i++) {



		  $fxdldata[$i]['id']=$i;



		  $fxdldata[$i]['title']=self::getSetting("fxdl_name" . $i);



          $money = self::getSetting("fxdl_money" . $i);



		   $fxdldata[$i]['title']=$money."元升级".$fxdldata[$i]['title'];



		 }



		$fxdldata=array_values($fxdldata);

		

		$set['dl_list']=array();



		if(!empty($fxdldata))$set['dl_list']=$fxdldata;



		if(empty($set['dl_hy_title']))$set['dl_hy_title']="会员需知 MEMBERS NEED";



		



		if(empty($set['dl_hy_xz']))$set['dl_hy_xz']="凡是入驻".$set['AppDisplayName']."平台，代理技术服务费概不退，还请认真考虑后加入！";



		if(!empty($set['dl_bjt']))$set['dl_bjt']=UPLOAD_URL."slide/".$set['dl_bjt'];

		else $set['dl_bjt']='View/index/img/wap/set/daili/equity_adv.png';



		if(!empty($set['dl_zdjs']))$set['dl_zdjs']=UPLOAD_URL."slide/".$set['dl_zdjs'];

		else $set['dl_zdjs']='View/index/img/wap/set/daili/equity_pic.png';

		$set['zhushi']="注：若升级到更高等级需缴纳相应的费用";





		$this->assign("fxdldata",$fxdldata);

		$this->assign("set",$set);

		$this->display();

		$this->play();

	}

	//充值处理

	public function czpaydoing(){

		$money=floatval($_GET['id']);

		$uid=intval(self::getUserId());

		self::islogin();

		switch(intval($_GET['type'])){

			case 0:

				$this->Rechargedoing();exit;

				break;

			case 1:

				

				$arr=array(

					"money"=>$money,

					"uid"=>$uid,

					"oid"=>"CZ_".$uid."_".time(),

					"title"=>"充值".$money."元",

				);

				$code=zfun::arr64_encode($arr);

				$url="http://".$_SERVER['HTTP_HOST']."/comm/weixin/example/jsapic.php?code=".$code;

				zfun::jump($url);

				break;	

			

		}

	}

	//支付宝充值

	public function Rechargedoing() {

		$uid=intval(self::getUserId());

		if(empty($uid))zfun::alert("请先登录");

		$user=zfun::f_row("User","id='$uid'");

		$id=intval($_GET['id']);

		$set=zfun::f_getset("webset_webnick,fxdl_money".$id);

		$set['dl_price']=$set['fxdl_money'.$id];

		

		if(empty($id))zfun::alert("请选择代理等级");

		if(($user['is_sqdl']+1)>=$id)zfun::alert("您的代理等级高于当前购买等级");

		$arr=array(

			"time"=>time(),

			"uid"=>intval($uid),

			"dl_dj"=>intval($id)-1,

			"jnMoney"=>floatval($set['dl_price']),



		);

		$zarr = array();

		$zarr['zhifubaoID'] = $this -> getSetting('zhifubaoID');

		$zarr['zhifubaoPID'] = $this -> getSetting('zhifubaoPID');

		$zarr['zhifubaoKEY'] = $this -> getSetting('zhifubaoKEY');

		$count=zfun::f_row("DLList","uid='$uid'");

		

		if(empty($count))$result=zfun::f_insert("DLList",$arr);

		else{

			$count1=zfun::f_update("DLList","uid='$uid'",array("dl_dj"=>intval($id)-1,"jnMoney"=>floatval($set['dl_price'])));

			$result=$count['id'];

		}

		

		$price=floatval($set['dl_price']);

		$zfbOrder=$uid."_".time()."_".$result;

		$title="成为代理";

		$att=array(

			"uid"=>intval($uid),

			"type"=>"DGDL",

			"alipay_id"=>$zfbOrder,

			"time"=>time(),

			"mac"=>"wap ruyi postRechargeSuc",

			"pay_type"=>'zfb',

		);

		if (empty($zarr['zhifubaoID']) || empty($zarr['zhifubaoPID']) || empty($zarr['zhifubaoKEY'])) {

			//$this -> promptMsg($this -> getUrl('member', 'memindex'), '配置出错', 0);

			zfun::alert("配置出错");

			exit ;

		} else {

			$uid = $this -> getUserId();

			$zfbModel = $this -> getApi('zhifubao');

			$_SESSION['CZorder'] = $zfbOrder;

			$zfbModel -> buy($zfbOrder, $title, $price, '成为代理：' . $price . "元", 'payResult', "?mod=wap&act=ruyi&ctrl=RechargeSuc");

		}

	}

	public function postRechargeSuc(){



		if(empty($_POST['out_trade_no'])||empty($_POST['total_amount'])||$_POST['trade_status']!='TRADE_SUCCESS')zfun::fecho("error");

		$order=$_POST['out_trade_no'];

		$pay=zfun::f_row("Pay","alipay_id='".$order."'");

		if($pay['type']!="DGDL")zfun::fecho("error");



		$money=floatval($_POST['total_amount']);

		$tmp=explode("_",$order);

		$uid=intval($tmp[0]);

		$did=intval($tmp[2]);

		zfun::f_update("DLList","id='$did'",array("is_pay"=>1));

		if(empty($uid))zfun::fecho("error");

		zfun::fecho("ok",1,1);



	}

	public function RechargeSuc(){

		zfun::alert("申请成功",$this->getUrl("my","my_home",array(),"wap"));	

		

	}

	/*海报*/

	public function sanji_hb(){

		$uid=$this->getUserId();

		self::islogin();

		$user=zfun::f_row("User","id='$uid'");

		

		$data=array();

		$model=zfun::f_select("DgAppModel","hide=0","id,img_max,content_first,is_check",0,0,"sort DESC");

		

		$set=zfun::f_getset("AppDisplayName,webset_webnick");

		if($set['AppDisplayName']==''||$set['AppDisplayName']=='花蒜')$set['AppDisplayName']=$set['webset_webnick'];

		

		

		foreach($model as $k=>$v){

			if(empty($v))continue;

			if(empty($v['content_first']))$v['content_first']='扫一扫注册下载'.$set['AppDisplayName'];

				

			$url=self::qrcode2($v,$user);

			

			$model[$k]['image']=$url;

			

			unset($model[$k]['content_first']);unset($model[$k]['img_max']);

		}

		

		//fpre($model);exit;

		

		$this->assign("model",$model);

		$this->display();

		$this->play();

	}

	public function sanji_hbif(){

		

		

		$data['UcenterShareTitle']=$this->getSetting("UcenterShareTitle");

        $data['UcenterShareText']=$this->getSetting("UcenterShareText");

		

		if(empty($data['UcenterShareText']))$data['UcenterShareText']='打开链接注册送佣金{tgurl}';

		$this->assign("data",$data);

		$this->display();

		$this->play();

	}

	public static function qrcode2($arr,$user){//生成二维码

		$id=intval($arr['id']);

		$tgidkey = self::getApp('Tgidkey');

		$tid = $tgidkey -> addkey($user['id']);

		

		$user['tid'] = $tid;

		$tg_url=INDEX_WEB_URL."?mod=new_share&act=invite_friend_wap&ctrl=new_packet&tgid=".intval($user['tid']);

		//二维码

		$path=ROOT_PATH."Upload/hb_ico/".$user['id']."_".$id."_erwema".".png";

		$path1=INDEX_WEB_URL."Upload/hb_ico/".$user['id']."_".$id."_erwema".".png";

		fun("qrcode/phpqrcode");

		$GLOBALS['return_qrcode']=1;

		

		if(file_exists($path)==false){

			$data=QRcode::png(($tg_url),$path,"L",12,1);

		

		}

		$erweima=$path1;

		

		$data = array();

		$data['width']=790;

		$data['height']=1280;

        $data['list'][0] = array(//背景图

            "url" => UPLOAD_URL."model/".$arr['img_max'],

            "x" => 0,

            "y" => 0,

            "width" => 790,

            "height" => 1280,

			"type"=>"png"

        );

		$data['list'][1] = array(//二维码

           // "url" => INDEX_WEB_URL."comm/qrcode/?url=".$arr."&size=10&codeKB=2",

		   "url" =>$erweima ,

            "x" => 331,

            "y" => 1000,

            "width" => 127,

            "height" => 127,

			"type"=>"png"

        );

       

	

		

		$data['text'][1]=array(

			"size"=>20,

			"x"=>260,

			"y"=>1180,

			"width" => 214,

            "height" => 20,

			"val"=>$arr['content_first'],

			"color"=>3,

		);

	

		

		//$data=zfun::arr64_encode($data);

		//zfun::head("jpg");

		

		$path="Upload/hb_ico/".$user['id']."_".$id."_pic".".png";

		$path2=ROOT_PATH."Upload/hb_ico/".$user['id']."_".$id."_pic".".png";

		$path1=INDEX_WEB_URL."Upload/hb_ico/".$user['id']."_".$id."_pic".".png";

		fun("pic");



		if(file_exists($path2)==false){

			pic::getpic($data,$path);

			

		}

		

		$url=$path1;

		

	//	echo "<img src='".$url."'>";;

		//exit;

		return $url;

		//fpre($url);exit;

		

		//echo zfun::get(INDEX_WEB_URL."comm/pic.php?type=getpic&data=".$data);

		

	}

	public function sanji(){

		self::islogin();

		$uid=$this->getUserId();$user=zfun::f_row("User","id='$uid'");

		$arr=array();

			/*修改过*/

		if($user['is_sqdl']>0){

			$arr['commission']=$user['dlcommission'];

			

		}else{

			$arr['commission']=$user['commission'];

			

		}

		$sum=0;

		$tmp=zfun::f_select("Interal","uid=$uid and detail like '%推广会员分佣%'","interal");

		foreach($tmp as $k=>$v)$sum+=floatval($v['interal']);

		$arr['lj_commission']=$sum;

		$arr['nickname']=$user['nickname'];



		$tgidkey = $this -> getApp('Tgidkey');

		$tid = $tgidkey -> addkey($user['id']);

		$arr['tid'] =$tid;

		/*修改过*/

		$lv=intval($user['is_sqdl']+1);

		$djtg_lv=intval(self::getSetting("fxdl_tjhydj_".$lv));



		$data=self::getcarr($uid,"extend_id",$djtg_lv);

		$ids=$data['uids'];

		$arr['tjnum']=zfun::f_count("User","id IN($ids)");

		

		if(empty($user['head_img']))$user['head_img']="default.png";

		if(strstr($user['head_img'],"http")==false)$user['head_img']=UPLOAD_URL."user/".$user['head_img'];

		$arr['head_img']=$user['head_img'];

		

		$arr['time']=date("Y-m-d",$user['reg_time']);

		$tgidkey = $this -> getApp('Tgidkey');

		$tid = $tgidkey -> addkey($uid);

		$arr['tid']=$tid;

		$this->assign('arr',$arr);

		

		$this->display();

		$this->play();

	}

	public function sanji_share(){

		self::islogin();

		$uid=$this->getUserId();$user=zfun::f_row("User","id='$uid'");

		$tgidkey = $this -> getApp('Tgidkey');

		$uid = $tgidkey -> addkey($uid);

		$tg_url1 = ($this -> getUrl('invite_friend_wap', 'new_packet', array('tgid' => $uid),'new_share'));

		$tg_url = urlencode($this -> getUrl('invite_friend_wap', 'new_packet', array('tgid' => $uid),'new_share'));

		$url=INDEX_WEB_URL."/comm/qrcode/?url=$tg_url&size=7";

		$data['UcenterShareTitle']=$this->getSetting("UcenterShareTitle");

        $data['UcenterShareText']=$this->getSetting("UcenterShareText");

		if(empty($data['UcenterShareText']))$data['UcenterShareText']='打开链接注册送佣金{tgurl}';

		$data['UcenterShareText']=str_replace("{tgurl}",$tg_url1,$data['UcenterShareText']);

		

		$this->assign("url",$url);

		$this->assign("data",$data);

		$this->assign("tg_url1",$tg_url1);

		$this->display();

		$this->play();

	}

	public function sanji_order(){

		self::islogin();

		$uid=$this->getUserId();$user=zfun::f_row("User","id='$uid'");

		$lv_dj=intval($user['is_sqdl']+1);

		$djtg_lv=intval(self::getSetting("fxdl_tjhydj_".$lv_dj));

		//if(empty($djtg_lv))zfun::fecho("多级推广等级未设置");

		$data=self::getcarr($uid,"extend_id",$djtg_lv);

		$darr=$data['darr'];

		$lvarr=array("","一级订单","二级订单","三级订单","四级订单","五级订单","六级订单","七级订单","八级订单","九级订单");

		$uids=$data['uids'];

		

		$set=zfun::f_getset("djtg_lv1,djtg_lv2,djtg_lv3,djtg_lv4,djtg_lv5,djtg_lv6,djtg_lv7,djtg_lv8,djtg_lv9");

		foreach($set as $k=>$v){$set[$k]=$v/100;}

		

		$sort="createDate desc";

		$where="uid IN($uids) ";

	   if($user['is_sqdl']>0){$where="(uid IN($uids) or tg_pid='".filter_check($user['tg_pid'])."')";}

		$t=intval($_GET['t']);

		

		switch($t){

			case 1:

			$where.=" and status<>'订单结算' and status<>'订单付款'";

			break;

			case 2:

			$where.=" and (status='订单结算' or status='订单付款')";

			break;

			case 3:

			$where.=" and returnstatus=1";

			break;	

		}

		

		$order=zfun::f_goods("Order",$where,"id,uid,orderId,tg_pid,status,returnstatus,createDate,commission","createDate desc",filter_check($_GET),0);

		$user=zfun::f_kdata("User",$order,"uid","id","id,nickname,phone");

		foreach($user as $k=>$v){

			

			$user[$k]['phone']=self::xphone($v['phone']);

		}

		foreach($order as $k=>$v){

			

			$lv=$darr[$v['uid']];

			if(empty($v['tg_pid'])){

				$set=zfun::f_getset("fxdl_tjhy_bili".$lv."_".$lv_dj);

				$bili=floatval($set["fxdl_tjhy_bili".$lv."_".$lv_dj]/100);

			}else{

				$set=zfun::f_getset("fxdl_tjhy_bili1_".$lv_dj);

				$bili=floatval($set["fxdl_tjhy_bili1_".$lv_dj]/100);



			}

			$order[$k]['nickname']=self::xphone($user[$v['uid']]['nickname']);	

			$order[$k]['phone']=self::xphone($user[$v['uid']]['phone']);

			$order[$k]['return_commission']=$v['commission']*$bili;

			$order[$k]['commission']=$order[$k]['return_commission'];

			$order[$k]['time']=date("Y-m-d H:i:s",$v['createDate']);

			$order[$k]['lv']=$lvarr[$lv];

			

			$order[$k]['type']='待付款';

			if($v['status']=="订单创建")$order[$k]['type']='待付款';

			if($v['status']=="订单付款"||$v['status']=="订单结算")$order[$k]['type']='已付款';

			if($v['returnstatus']==1)$order[$k]['type']='已完成';

		}

		

		$this->assign("order",$order);

		$this->display();

		$this->play();

	}

	public function sanji_sy(){

		self::islogin();

		$uid=$this->getUserId();$user=zfun::f_row("User","id='$uid'");

		$arr=array();

		$moneyarr=array();

		$moneyarr[0]=0;

		

		$lv_dj=intval($user['is_sqdl']+1);

		$djtg_lv=intval(self::getSetting("fxdl_tjhydj_".$lv_dj));

		$data=self::getcarr($uid,"extend_id",$set['djtg_lv']);

		$darr=$data['darr'];

		$uids=$data['uids'];

		$where="uid IN($uids) ";

		$lvarr=array("","一级佣金","二级佣金","三级佣金","四级佣金","五级佣金","六级佣金","七级佣金","八级佣金","九级佣金");



	   if($user['is_sqdl']>0){$where="(uid IN($uids) or tg_pid='".filter_check($user['tg_pid'])."')";}

		$order=zfun::f_select("Order",$where,"uid,commission,tg_pid");

		

		foreach($order as $k=>$v){

			$lv=$darr[$v['uid']];

			

			if(empty($v['tg_pid'])){

				$set=zfun::f_getset("fxdl_tjhy_bili".$lv."_".$lv_dj);

				$bili=floatval($set["fxdl_tjhy_bili".$lv."_".$lv_dj]/100);

			}else{

				$set=zfun::f_getset("fxdl_tjhy_bili1_".$lv_dj);

				$bili=floatval($set["fxdl_tjhy_bili1_".$lv_dj]/100);



			}

			$commission=$v['commission']*$bili;

			$moneyarr[0]+=$commission;

			if(empty($moneyarr[$lv]))$moneyarr[$lv]=0;

			$moneyarr[$lv]+=$commission;

		}

		$arr=array();foreach($moneyarr as $k=>$v){

			$arr['money'.$k]=$v;

			if(empty($k))continue;

			$att[$k]['money']=$v;

			$att[$k]['type']=$lvarr[$k];

		

		}

		$arr['djtg_lv']=$djtg_lv;

		

		$this->assign("arr",$arr);

		$this->assign("att",$att);

		$this->display();

		$this->play();

	}

	public function sanji_td(){

		self::islogin();

		$uid=$this->getUserId();$user=zfun::f_row("User","id='$uid'");

		$tuid=$uid;

		$arr=array();

		$title="一级团队成员";

		$djtg_lv=intval(self::getSetting("djtg_lv"));

		if(!empty($_GET['id'])){

			$tuid=intval($_GET['id']);

			

			$lv=self::getcarr_lv($uid,"extend_id",$djtg_lv,$tuid);//查看等级

			

			if($lv==1)$title="二级团队成员";

			if($lv==2)$title="三级团队成员";

		}

		$arr['title']=$title;

		//if(empty($djtg_lv))zfun::fecho("多级推广等级未设置");

		$data=self::getcarr($tuid,"extend_id",1);

		

		$arr['count']=$data['count'];

		

		$set=zfun::f_getset("commission_reg,jf_reg");

		$_GET['p']=intval($_POST['p']);

		if(empty($_GET['p']))$_GET['p']=1;

		$sort="reg_time desc";

		$user=zfun::f_goods("User","id IN(".$data['uids'].")","id,head_img,nickname,phone,reg_time,realname",$sort,array(),0);

		if(empty($user))$arr['is_xj']=0;

		foreach($user as $k=>$v){

			$user[$k]['is_xj']=0;

			$img=$v['head_img'];

			if(empty($img))$img="default.png";

			if(strstr($img,"http")==false)$img=UPLOAD_URL."user/".$img;

			$user[$k]['head_img']=$img;

			$count=zfun::f_count("User","extend_id=".intval($v['id']));

			if(!empty($count))$user[$k]['is_xj']=1;

			if(($lv+1)>$djtg_lv)$user[$k]['is_xj']=0;

			$user[$k]['time']=date("Y-m-d H:i:s",$v['reg_time']);

			$user[$k]['j_commission']="+".floatval($set['commission_reg']);

			$user[$k]['count']=zfun::f_count("User","extend_id=".$v['id']);

			$user[$k]['phone']=self::xphone($v['phone']);

			if(empty($v['nickname']))$user[$k]['nickname']=$v['nickname']=$v['phone'];

			$user[$k]['nickname']=self::xphone($v['nickname']);

		}

		

		$arr['list']=$user;

		$this->assign("user",$user);

		$this->assign("arr",$arr);

		$this->display();

		$this->play();

	}



	

	//绑定支付宝

	public function bindpay(){

		self::islogin();

		$uid=self::getUserId();

		$user=zfun::f_row("User","id = {$uid}","id,loginname,realname,phone,alipay");



		if(!empty($_POST['yzm'])){

			

			if($_SESSION['phoneyzm']!=$_POST['yzm'])zfun::fecho("验证码错误!");

			unset($_SESSION['phoneyzm']);

			zfun::fecho("成功",1,1);

		}

		self::assign("user",$user);

		$this->display();

		$this->play();



	}

//绑定微信

	public function bindwx(){

		self::islogin();

		$uid=self::getUserId();

		$user=zfun::f_row("User","id = {$uid}","id,loginname,realname,phone,wechat");



		if(!empty($_POST['yzm'])){

			if($_SESSION['phoneyzm']!=$_POST['yzm'])zfun::alert("验证码错误!");

			unset($_SESSION['phoneyzm']);

			$this->linkNo("ruyi","changewx",array('uid'=>$uid),"wap");	

		}

		self::assign("user",$user);

		$this->display();

		$this->play();



	}

		//修改微信

	public function chagewx(){

		self::islogin();

		$uid=self::getUserId();

		$user=zfun::f_row("User","id=$uid","id,loginname,realname,phone,wechat");



		if(!empty($_POST['yzm'])){

			if($_SESSION['phoneyzm']!=$_POST['yzm'])zfun::alert("验证码错误!");

			unset($_SESSION['phoneyzm']);

			$this->linkNo("ruyi","changepay",array('uid'=>$uid),"wap");	

		}

		self::assign("user",$user);

		$this->display();

		$this->play();



	}

	//提现方式

	public function money(){

		self::islogin();

		$this->display();

		$this->play();

	}

	//提现方式

	public function aboutus(){

		

		$this->display();

		$this->play();

	}





	//支付宝提现

	public function paymoney(){

		self::islogin();



		$uid=self::getUserId();



		$user=zfun::f_row("User","id=$uid","id,loginname,realname,phone,commission,money,alipay");



		//如果是余额提现



		$title="返利金额提现";



		if(!empty($_GET['t'])){



			$user['commission']=$user['money'];



			$title="余额提现";



		}



		self::assign("title",$title);



		if(empty($user['alipay']))$user['alipay']='未绑定支付宝';



		$tixian_xiaxian=floatval(self::getSetting("tixian_xiaxian"));



		if(empty($tixian_xiaxian))$tixian_xiaxian=100;



		self::assign("tixian_xiaxian",floatval(self::getSetting("tixian_xiaxian")));



		self::assign("user",$user);



		$this->display();

		$this->play();

	}



	//提现记录

	public function txjilu(){

		self::islogin();

		$uid=self::getUserId();

		$list=zfun::f_goods("Authentication","uid = '$uid' and type='3' and info like '%提现%' ");

		self::assign("auth",$list);

		$this->display();

		$this->play();	

	}





	////账号密码支付宝

	public function changewx(){

		self::islogin();

		$uid=self::getUserId();

		if($_POST['wechat']){

		$wechat=filter_check($_POST['wechat']);



		$tmp=zfun::f_count("User","id<>$uid and wechat='$wechat'");

		print_r($tmp);exit;

		if(!empty($tmp))zfun::fecho("该支付宝已经被绑定","0",0);



		$arr=array(



			"wechat"=>$wechat,



			"realname"=>filter_check($_POST['realname']),

		

		);



		$result=zfun::f_update("User","id=$uid",$arr);



		zfun::fecho("修改成功",1,$result);

		}

		

		$this->display();

		$this->play();



	}

	//修改支付宝

	public function chagepay(){

		self::islogin();

		$uid=self::getUserId();

		$user=zfun::f_row("User","id=$uid","id,loginname,realname,phone,alipay");

		if(!empty($_POST['yzm'])){

			if($_SESSION['phoneyzm']!=$_POST['yzm'])zfun::alert("验证码错误!");

			unset($_SESSION['phoneyzm']);

			$this->linkNo("ruyi","changepay",array('uid'=>$uid),"wap");	

		}

		self::assign("user",$user);

		$this->display();

		$this->play();



	}



	//修改支付宝

	public function changepay(){

		self::islogin();

		$uid=self::getUserId();

		if($_POST['alipay']){

		$alipay=filter_check($_POST['alipay']);



		$tmp=zfun::f_count("User","id<>$uid and alipay='$alipay'");



		if(!empty($tmp))zfun::fecho("该支付宝已经被绑定","0",0);



		$arr=array(



			"alipay"=>$alipay,



			"realname"=>filter_check($_POST['realname']),

		

		);



		$result=zfun::f_update("User","id=$uid",$arr);



		zfun::fecho("修改成功",1,$result);

		}

		

		$this->display();

		$this->play();



	}





	// 账户安全

	public function safe(){

		self::islogin();

		$uid=self::getUserId();

		$user=zfun::f_row("User","id = {$uid}");

		self::assign("user",$user);

		$this->display();

		$this->play();

	}





	//账单

	public function check(){

		$uid=self::getUserId();

		 $where="uid = '$uid'";

		switch ($_GET['t']) {

			case '0':

				

				break;

			

			case '1':

				$where.=" AND interal > 0";

				break;



			case '2':

				$where.=" AND interal < 0";

				break;

		}

		$interal=zfun::f_goods("Interal",$where,null,"time desc",null,10);

		foreach ($interal as $k => $v) {

			$interal[$k]["time"]=date("Y-m-d H:i:s",$v['time']);

		}

		

		if($_GET['p']>1)zfun::fecho("账单",$interal,1);

		self::assign("interal",$interal);

		$this->display();

		$this->play();

	}





	public function tmail(){

		$orders=self::getorder();

		if(empty($orders['count']))$orders["count"]=0;



		self::assign("orders",$orders["order"]);



		self::assign("count",$orders["count"]);

		

		$set=zfun::f_getset("fljeonoff");

		self::assign("set",$set);

		self::assign("fljeonoff",$set['fljeonoff']);

		$this->display();

		$this->play();

	}





	public function getorder(){

		self::islogin();

		$uid=$this->getUserId();



		$goodsModel=$this->getDatabase('Goods');



		$storeModel=$this->getDatabase('Store');



		$goodsattrModel=$this->getDatabase('GoodsAttr');



		$where="uid=$uid and is_delete=0";



		$type=intval($_GET['type']);

	

		if(empty($type))$type=$_GET['type']=2;



		



		switch($type){



			case 1:



				$where.=" and orderType=3";



			break;



			case 2:



				$where.=" and orderType=1";



			break;



			case 3:



				$where.=" and orderType=4";



			break;



			case 4:



				$where.=" and orderType=2";



			break;



			case 5:



				$where.=" and orderType=5";//本地特色订单



			break;



		}



		



		if($type==1){



			switch($_GET['t']){	



				case 2:



					$where .= " and status='未付款'";



					



					break;



				case 3:



					$where .= " and returnType=0 and status='已付款'";



					break;



				case 4:



					$where .= " and returnType=2  and status='已付款'";



					break;



				case 5:



					if($where .= " and returnType=1 and status='已付款'"){



						$where .= " and commentType=0";



					}



					break;



				case 6:



					$where .= " and tk=1";



					break;



			}



			$url="link:my-order_details-wap";



		}else if($type==5){



			switch($_GET['t']){	



				case 2:



					$where .= " and status='未付款'";



					break;



				case 3:



					$where .= " and is_use=0 and status='已付款' and tk=0";



					break;



				case 4:



					$where .= " and is_use=1 and commentType=0 and status='已付款'  and tk=0";



					break;



				case 5:



					$where .= " and tk=1  and status='退款中'";



					break;



			}



			$url="link:takeout-indent_details-wap";



		}else{



			switch(intval($_GET['t'])){



				case 2:



					$where.=" and status='创建订单'";



				break;



				case 3:



					$where.=" and status='订单付款'";



				break;



				case 4:



				case 5:



					$where.=" and status='订单结算'";



				break;



				case 6:



					$where.=" and (status='创建订单' or status='订单付款' )";



				break;		



			}			



		}



		$this->assign("url",$url);



		$orders = zfun::f_goods("Order", $where, NULL, "id desc", filter_check($_GET), 0);

		$order_count=zfun::f_count("Order", $where, NULL, "id desc", filter_check($_GET), 0);

		$orders = self::getordercommission($orders);



		foreach($orders as $k=>$v){



			if($v['orderType']==1 || $v['orderType']==2 || $v['orderType']==4)break;



			$tmp=zfun::arr64_decode($v['goodsInfo']);



			$orders[$k]['goodsNum']=$tmp[0]['goods_number'];



			if(!empty($v['goodsId'])){



				$gid=$v['goodsId'];



				$gaid=$v['goods_attr_id'];



				/*echo $gid;*/



			    if($v['is_source']==1){



					$goods=zfun::f_row("SourceGoods","id IN($gid)");



				}else{



					$goods=$goodsModel->selectRow("id IN($gid)");



				} 



				//获取活动价格



				$goods=self::gethdprice($goods);



				



				$orders[$k]['goods_price']=self::getprice($goods,NULL,$v['goods_attr_id']);//获取标签价格



				



				if($goods['is_activity']=='1'){//如果是活动商品



					$orders[$k]['goods_price']=$goods['goods_price'];



				}



				



				/*$goods1=self::gethdprice($goods);



				if($goods['is_activity']=='1'){



					$orders[$k]['goods_price'] = $goods['goods_price'];



				}



				fpre($orders[$k]['goods_price']);*/



				$sid=$goods['store_id'];



				$store=$storeModel->selectRow("uid='$sid' and is_source=".intval($order['is_source']));



				$ogid = explode(',',$v['goodsId']);



				$orders[$k]['gid'] = $ogid[1];	



				



				$orders[$k]['storename'] = $store['storename'];



				$orders[$k]['goods_title'] = $goods['goods_title'];



				$goods=zfun::f_fgoodscommission(array($goods));$goods=reset($goods);



				$orders[$k]['goods_img']=$goods['goods_img'];



					



				if(empty($store['logo']) ||$store['logo']==NULL){



					$orders[$k]['logo']="View/index/img/wap/login/register/store.png";



				}else if(strstr($store['logo'],"http://")){



					$orders[$k]['logo']=$store['logo'];



				}else if(strstr($store['logo'],"jpg") || strstr($store['logo'],"png") || strstr($store['logo'],"gif")){



					$orders[$k]['logo']="Upload/".$store['logo'];



				}else if($store['logo']=='slide/'){



					$orders[$k]['logo']="Upload/".$store['logo']."store.png";



				}



				switch($orders[$k]['returnType']){



					case 0:



						$orders[$k]['returnType']='待发货';



						break;



					case 1:



						$orders[$k]['returnType']='已收货';



						break;



					case 2:



						$orders[$k]['returnType']='待收货';



						break;



				}



				switch($orders[$k]['commentType']){



					case 0:



						$orders[$k]['commentType']='待评价';



						break;



					case 1:



						$orders[$k]['commentType']='已评价';



						break;



				}



				



				if($type==1){ 



					if($v['status']=='未付款'){



						$orders[$k]['state']='未付款';



					}elseif($orders[$k]['returnType']=='待发货' && $v['status']=='已付款' ){



						$orders[$k]['state']='待发货';



					}elseif($orders[$k]['returnType']=='待收货' && $v['status']=='已付款' ){



						$orders[$k]['state']='待收货';



					}elseif($orders[$k]['commentType']=='待评价' && $v['status']=='已付款' &&  $orders[$k]['returnType']=='已收货'){



						$orders[$k]['state']='待评价';



					}elseif($v['status']=='退款中'){



						$orders[$k]['state']='退款中';



					}elseif($v['status']=='退款失败'){



						$orders[$k]['state']='退款失败';



					}elseif($v['tk']==1 && $v['tkcheck']==1){



						$orders[$k]['state']='已退款';



					}elseif($v['status']=='申诉中'){



						$orders[$k]['state']='申诉中';



					}elseif($v['status']=='申诉成功'){



						$orders[$k]['state']='申诉成功';



					}elseif($v['status']=='申诉失败'){



						$orders[$k]['state']='申诉失败';



					}else{



						$orders[$k]['state']='已完成';



					}



				}else if($type==5){



					if($v['status']=='未付款'){



						$orders[$k]['state']='未付款';



					}elseif($v['is_use']=='0' && $v['status']=='已付款' && $v['commentType']=='0' && $v['tk']=='0' ){



						$orders[$k]['state']='待使用';



					}elseif($v['commentType']=='0' && $v['status']=='已付款' && $v['is_use']=='1'){



						$orders[$k]['state']='待评价';



					}elseif($v['commentType']=='1'){



						$orders[$k]['state']='已评价';



					}elseif($v['tk']=='1'){



						$orders[$k]['state']='退款中';



					}elseif($v['tk']=='1' && $v['tkcheck']=='2' ){



						$orders[$k]['state']='已退款';



					}



				}







			}



			



		



		}



		



		//jj explosion



		foreach($orders as $k=>$v){



			if($v['orderType']==3 || $v['orderType']==5)break;



			$orders[$k]['storename']='';



			$orders[$k]['state']=$v['status'];



			$orders[$k]['goods_title']=$v['goodsInfo'];



			$orders[$k]['goods_price']=$v['price'];



			



			if(empty($v['goods_img'])){



			$orders[$k]['goods_img']=INDEX_WEB_URL."View/index/img/wap/login/register/detmo.png";}



			$orders[$k]['logo']="View/index/img/wap/login/register/store.png";



			



		}

		$arr=array();

		$arr["order"]=$orders;

		$arr["count"]=$order_count;





		return $arr;

	}





	public function iftmail(){

		$orders=self::getorder();



		self::assign("orders",$orders["order"]);

		self::assign("orders",$orders["count"]);



		if($_GET['p']>1)zfun::fecho("全部订单",$orders,1);

		$this->display();

		$this->play();

	}



	public function iftmail2(){

		$orders=self::getorder();

		self::assign("orders",$orders);

		if($_GET['p']>1)zfun::fecho("跟踪订单",$orders,1);



		$this->display();

		$this->play();

	}



	public function iftmail3(){

		$orders=self::getorder();

		self::assign("orders",$orders);

		if($_GET['p']>1)zfun::fecho("已结算订单",$orders,1);



		$this->display();

		$this->play();

	}







	public function getordercommission($order = array()) {



		$commissionbili = floatval(zfun::f_commissionbili());



		foreach ($order as $k => $v) {



			$order[$k]['fcommission'] = $v['commission'] * $commissionbili;



		}



		return $order;



	}



	public static function gethdprice($goods=array()){



		if(empty($goods))return array();



		$gid=intval($goods['id']);



		$time=time();



		$goodsact=zfun::f_row("GoodsActivity","gid=$gid and start_time < $time and end_time > $time",NULL,"start_time asc");



		if(empty($goodsact))return $goods;



		



		$goodsact=zfun::arrint($goodsact,"goods_price,commission,discount");



		if(!empty($goodsact['goods_price'])){



			$goods['goods_price']=$goodsact['goods_price'];



		}



		if(!empty($goodsact['commission'])){//佣金比例



			$goods['commission']=$goodsact['commission'];	



		}



		if(!empty($goodsact['discount'])){//折扣



			$goods['discount']=$goodsact['discount'];



		}



		



		$goods['hd_start_time']=$goodsact['start_time'];



		$goods['hd_end_time']=$goodsact['end_time'];



		



		$goods['activity_name']=$goodsact['name'];



		$goods['is_activity']=1;



		return $goods;



	}



	public static function getprice($goods=array(),$num=1,$attr=-1){



		if(empty($goods))return 0;



		if(empty($attr))return $goods['goods_price'];



		$where="id IN($attr) and goods_id=".$goods['id'];



		$data=zfun::f_select("GoodsAttr",$where);



		if(empty($data))return $goods['goods_price'];



		$price=$goods['goods_price'];



		foreach($data as $k=>$v){



			if(!empty($v['attr_price'])){



				if($price<floatval($v['attr_price']))$price=floatval($v['attr_price']);



			}



		}



		if(empty($price))return $goods['goods_price'];



		return $price;



	}





	public static function iswx(){



		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false )return 1;



		else return 0;



	}



	public function iswxlogin(){



		if(!self::iswx()&&empty($_SESSION['wxuser']))return;



		if(file_exists(ROOT_PATH."Action/index/weixin/wxlogin.action.php")==false)return;



		$this->runplay("weixin","wxlogin","login");



	}









	public function islogin() {



		if (!$this -> cheackLogin()) {



			$url=urlencode(self::getUrl("my","my_home",array(),"wap"));



			$url=self::getUrl('login', 'login',array("url"=>$url),"wap");



			zfun::jsjump($url);



		}



	}

	//explosion

	public static function getc($uid, $tidname = "extend_id", $maxlv = 9) {//获取下级

		if (empty($uid))

			return 0;

		$arr = array();

		$arr[0] = intval($uid);

		$lv = 0;

		$eid = 0;

		$tid = $uid;

		do {

			$lv++;

			$user = zfun::f_select("User", "$tidname IN($tid) and $tidname<>0 and $tidname<>''");

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

	//以下非接口

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

	public static function xphone2($phone=''){

		$phone.="";

		return mb_substr($phone,0,1,"utf-8")."******".mb_substr($phone,-1,1,"utf-8");	

	}

	public static function getcarr_lv($uid, $tidname = "extend_id", $maxlv = 9,$tuid) {//获取等级

		$maxlv++;

		

		if (empty($uid))return 0;

		$lv = 0;

		$tid = $uid;

		do {

			

			$lv++;

			

			$user = zfun::f_select("User", "$tidname IN($tid) and $tidname<>0 and $tidname<>''");

			

			if (!empty($user)) {

				$tid = "";



				foreach ($user as $k => $v){

					$tid .= "," . $v['id'];

					

					if($v['id']==$tuid)return $lv;

				}



					



				$tid = substr($tid, 1);

				

			}

		} while(!empty($user)&&$lv<$maxlv);

	}

	public static function getcarr($uid, $tidname = "extend_id", $maxlv = 9) {//获取下级

		$maxlv++;

		if (empty($uid))

			return 0;

		$arr = array();

		$arr[0] = intval($uid);

		$lv = 0;

		$eid = 0;

		$tid = $uid;

		do {

			$lv++;

			$user = zfun::f_select("User", "$tidname IN($tid) and $tidname<>0 and $tidname<>''");

			if (!empty($user)) {

				$tid = "";

				foreach ($user as $k => $v)

					$tid .= "," . $v['id'];

				$tid = substr($tid, 1);

				$arr[$lv] = $tid;

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
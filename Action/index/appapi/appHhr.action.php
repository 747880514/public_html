<?php

include_once ROOT_PATH."Action/index/appapi/dgappcomm.php";

include_once ROOT_PATH."Action/index/default/alimama.action.php";

actionfun("comm/tbmaterial");

actionfun("comm/order");

class appHhrAction extends Action{

	static $set=array();

	/*申请页面*/

	public function index(){

		appcomm::signcheck();

		$set=zfun::f_getset("apphhr_movies,ContactPhone,apphhr_img");

		$set['introduce']=array();

		$help=zfun::f_select("HelperArticle","hide=0 AND type='apphhr'","title,content",0,0,"sort desc");

		if(!empty($help))$set['introduce']=$help;

		$set['apphhr_movie']=$set['apphhr_movies'];

		unset($set['apphhr_movies']);

		if(!empty($set['apphhr_img']))$set['apphhr_img']=UPLOAD_URL."slide/".$set['apphhr_img'];

		else $set['apphhr_img']=INDEX_WEB_URL.'View/index/img/wap/comm/fmhhr.png';

		zfun::fecho("申请页面",$set,1,1);

	}

	/*申请操作*/

	public function sqhhr(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$data=zfun::getpost("content,name,qq,phone");

		$set=zfun::f_getset("hhr_openCheck");

		$data['uid']=$uid;

		$data['time']=time();

		$data['dl_dj']=1;

		$data['is_pay']=2;

		if(!empty($user['is_sqdl']))zfun::fecho("你已经是合伙人");

		$count=zfun::f_count("DLList","uid='$uid' AND checks<>2");

		if(!empty($count))zfun::fecho("你已经申请过，请勿再次申请");

		if(intval($set['hhr_openCheck'])==0){

			$arr=array("is_sqdl" => $data['dl_dj']);

			//jj explosion

			if($arr['is_sqdl']>0){

				//设置成为代理的时间

				$arr['is_sqdl_time']=time();

			}

			$result = zfun::f_update("User", "id='".intval($data['uid'])."'",$arr);

			//include ROOT_PATH."Action/admin/dg_newdl.action.php";

			//dg_newdlAction::fanli_3($uid);

			$data['checks']=1;

		}

		$result=zfun::f_insert("DLList",$data);

		if(empty($result))zfun::fecho("申请失败");

		zfun::fecho("申请成功",1,1);

	}

	public function hhrIndex(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$head_img=$user['head_img'];

		if(empty($head_img))$head_img='default.png';

		if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;

		$data['head_img']=$head_img;

		$data['nickname']=$user['nickname'];

		$str=array("普通会员","合伙人");

		$data['Vname']=self::getSetting("fxdl_name".($user['is_sqdl']+1));

		$djtg_lv=intval(self::getSetting("fxdl_tjhydj_".($user['is_sqdl']+1)));

		$operator_set=zfun::f_getset("operator_name,operator_name_2");

		//如果是运营商

		if($user['operator_lv'].''!='0')$data['Vname']=$operator_set['operator_name'];

		if($user['operator_lv'].''=='2')$data['Vname']=$operator_set['operator_name_2'];

		$att=self::getcarr($uid,"extend_id",$djtg_lv,"1");

		$data['count']=$att['count'];

		//$result=order::get_lower($uid);

		$set=zfun::f_getset("jzcy_fl_onoff");

		$where="extend_uid='{$uid}' and bili>0";

		if(intval($set['jzcy_fl_onoff'])==1)$where="extend_uid='{$uid}' ";//全部的

		$count=zfun::f_count("Nexus",$where);

		$data['allcount']=$count;

		if($user['operator_lv'].''!='0'){//如果是运营商

			//$data['allcount']=zfun::f_count("User","operator_id='{$uid}'");

		}

		$data['xxjc']=INDEX_WEB_URL."?mod=wap&act=super_new&ctrl=appjc";

		$data['question']=INDEX_WEB_URL."?mod=wap&act=super_new&ctrl=appquestion";

		zfun::fecho("合伙人中心",$data,1);

	}

	/*商品列表*/

	public function getGoods(){

		appcomm::signcheck();

		appcomm::read_app_cookie();

		//版本一样时没有商品  ios审核用

		if(!empty($_POST['app_V'])&&$set['checkVersion']==$_POST['app_V'])zfun::fecho("没有商品",array(),1);

		if (!empty($_POST['token'])) {

			$userid = zfun::f_row("User",'token="' . $_POST['token'] . '"');$uid = $userid['id'];

			if (empty($userid))zfun::fecho("您的账号在其他终端登陆，请重新登陆！");

			$this -> setSessionUser($userid['id'], $userid['nickname']);

		} else {

			$uid = 0;

		}

		$where = "is_hhr=1";

		$num=20;

		if(!empty($_POST['num']))$num=intval($_POST['num']);

		/*分类*/

		if (!empty($_POST['cid'])) {

			$categoryModel = $this -> getDatabase("Category");

			$cids = $categoryModel -> getCateId($_POST['cid']);

			$c = explode(",", $cids);

			if (is_array($c) && count($c) > 1)$where .= " AND cate_id in ($cids) ";

			else $where .= " AND cate_id=".intval($_POST['cid']);

		}

		$set=zfun::f_getset("hhrapitype,fxdl_tjhy_bili1_".(intval($userid['is_sqdl'])+1));

		$bili=$set["fxdl_tjhy_bili1_".(intval($userid['is_sqdl'])+1)];

		/*筛选*/$where=self::getWhere($where,floatval($_POST['start_price']),floatval($_POST['end_price']),$_POST['keyword'],$_POST['source']);

		/*排序*/ $sort=self::getSort($_POST['sort']);

		appcomm::read_app_cookie();

		if(intval($set['hhrapitype'])==0){

			include_once ROOT_PATH."Action/index/appapi/appdtk.action.php";

			include_once ROOT_PATH."Action/index/default/gototaobao.action.php";

			if(empty($_POST['p']))$_POST['p']=1;

			$data=array();

			$data['page']=ceil($_POST['p']*5/100);

			$p=$_POST['p'];

			$pp=$_POST['p']-1;

			$data['cid']=$_POST['cid'];

			$data['keyword']=$_POST['keyword'];

			$data['sort']=$_POST['sort'];

			$data['start_price']=$_POST['start_price'];

			$data['end_price']=$_POST['end_price'];

			$goods=appdtkAction::index($data);

			$j=0;

			$i=1;

			$goods=array_values($goods);

			$arr=array();

			foreach($goods as $k=>$v){

				if(empty($v['goods_price']))continue;

				if(empty($arr[$i]))$arr[$i]=array();

				$arr[$i][]=$v;

				if(count($arr[$i])==5)$i++;

				//if(($k+1)==($i+1)*5){$i++;}

			}

			foreach($arr as $k=>$v){

				if(empty($v))$arr[$k]=array();

			}

			$pa=($p-20*($data['page']-1));

			if($pa<=0)$pa=1;

			$p=$pa;

			if(empty($arr[$p]))$arr[$p]=array();

			foreach($arr[$p] as $k=>$v){

				if(empty($v['shop_id']))$arr[$p][$k]['shop_id']=1;

					$arr[$p][$k]['fnuo_id1']=$v['fnuo_id'];

					$idd=explode("_",$v['fnuo_id']);

					$itt=$idd[0];

					$goodss=self::get_dtk_hyq($itt);

					$arr[$p][$k]['commission']=$goodss['Commission'];

					$arr[$p][$k]['fnuo_id']=$goodss['GoodsID'];

					$arr[$p][$k]['yhq']=0;

					//$arr[$p][$k]['yhq_url']=$att['yhq_url'];

					$arr[$p][$k]['yhq_span']='';

					if(!empty($v['yhq_price'])){

						$arr[$p][$k]['yhq']=1;

						$arr[$p][$k]['yhq_span']=$v['yhq_price']."元";

					}

					if($v['shop_type']=='天猫')$arr[$p][$k]['shop_id']=2;

					$arr[$p][$k]['end_time']=$att['Quan_time'];

					$arr[$p][$k]['is_qg']=0;

					if(!empty($att['IsTmall']))$arr[$p][$k]['shop_id']=2;

					$arr[$p][$k]['is_mylike']=0;

					//$goods_img=http.str_replace(array("http:","https:"),"",$goods_img);

					$arr[$p][$k]['goods_img']="https:".str_replace(array("http:","https:"),"",$v['goods_img']);

					$arr[$p][$k]['is_support']=0;

					if(floatval($arr[$p][$k]['commission'])>0)$arr[$p][$k]['is_support']=1;

					if(!empty($uid)){

						$count=zfun::f_count("MyLike","uid='$uid' AND goodsid='".($arr[$p][$k]['fnuo_id'])."'");

						if($count>0)$arr[$p][$k]['is_mylike']=1;

					}



			}



			//$arr[$p]=array_values($arr[$p]);

			$arr[$p]=zfun::f_fgoodscommission($arr[$p]);

			/*foreach($arr[$p] as $k=>$v){

				//$arr[$p][$k]['commission1']=$v['commission'];

				//$arr[$p][$k]['fcommission']=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));

				//$arr[$p][$k]['commission']=zfun::dian($arr[$p][$k]['fcommission']/$v['goods_price']*100);

			}*/

			zfun::isoff($arr[$p]);

			appcomm::goodsfeixiang($arr[$p]);

			//jj explosion 因为接错字段先这样写着先

			appcomm::goodsfanlioff($arr[$p]);

			foreach($arr[$p] as $k=>$v){

				$gm_commission=$v['fcommission'];//用户购买的

				$arr[$p][$k]['fcommission']=$arr[$p][$k]['fcommissionshow']=$v['fx_commission'];//合伙人分享的

				$arr[$p][$k]['commission']=$v['fx_commission_bili'];//因为合伙人中心的要显示合伙人的比例

				$arr[$p][$k]['gm_commission']=$gm_commission;//用户购买的

			}

			appcomm::set_app_cookie($arr[$p]);

			zfun::fecho("商品列表",$arr[$p],1);

		}

		$fi="id,fnuo_id,goods_img,goods_title,stock,end_time,goods_price,highcommission,highcommission_start_time,highcommission_end_time,goods_type,commission,goods_cost_price,shop_id,goods_sales,yhq,yhq_url,yhq_span,yhq_price";

		$goods=appcomm::f_goods("Goods",$where,$fi,$sort,$arr,$num);

		$goods=zfun::f_fgoodscommission($goods);

		foreach($goods as $k=>$v){

			if(empty($v['shop_id']))$goods[$k]['shop_id']=1;

			$goods[$k]['is_mylike']=0;

			if(!empty($uid)){

				$count=zfun::f_count("MyLike","uid='$uid' AND goodsid=".intval($v['id']));

				if($count>0)$goods[$k]['is_mylike']=1;

			}

			$goods[$k]['is_support']=0;

			if(floatval($v['commission'])>0)$goods[$k]['is_support']=1;

			if($v['shop_id']==4)$goods[$k]['shop_id']=3;

		 	$goods[$k]['qgStr']="已抢".$v['goods_sales']."件";

			$goods[$k]['couponPrice']=$v['goods_price'];

			if($v['stock']==0){$goods[$k]['is_qg']=1;$goods[$k]['qgStr']='已抢光';}

			else $goods[$k]['is_qg']=0;

			$jindu=$v['goods_sales']/($v['stock']+$v['goods_sales']);

			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100;

			/*

			$goods[$k]['fcommission']=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));

			$goods[$k]['commission']=zfun::dian($goods[$k]['fcommission']/$v['goods_price']*100);

			*/

			unset($goods[$k]['goods_type']);unset($goods[$k]['fbili']);unset($goods[$k]['zhe']);

			unset($goods[$k]['fcommissionshow']);unset($goods[$k]['detailurl']);

			unset($goods[$k]['highcommission']);unset($goods[$k]['highcommission_start_time']);unset($goods[$k]['highcommission_end_time']);

		}

		//处理分享赚佣金

		appcomm::goodsfeixiang($goods);

		appcomm::goodsfanlioff($goods);

		appcomm::set_app_cookie($goods);

		zfun::fecho("商品列表",$goods,1);

	}

	public static function getin($str='',$str1='',$str2=''){

		$str=explode($str1,$str);

		if(empty($str[1]))$str[1]='';

		$str=explode($str2,$str[1]);

		return $str[0];

	}

	//大淘客数据

	public static function get_dtk_hyq($fnuo_id=''){

		if(empty($fnuo_id))return;

		if(empty($GLOBALS['get_dtk_hyq_set']))$set=$GLOBALS['get_dtk_hyq_set']=zfun::f_getset("almm_yhq_moshi,almm_yhq_pid,almm_yhq_host,dtk_key");

		$set=$GLOBALS['get_dtk_hyq_set'];

		//if(empty($set['almm_yhq_pid'])||$set['almm_yhq_moshi']=='0')return;

		$pid=$set['almm_yhq_pid'];

		// 如果 推广的 pid存在 使用 推广的

		//if(!empty($GLOBALS['taobaopid']))$pid=$GLOBALS['taobaopid'];

		//if(empty($pid))return;

		$url="http://api.dataoke.com/index.php?r=port/index&appkey=".$set['dtk_key']."&v=2&id=".$fnuo_id;

		$data1=self::read_app_cookie($url);

		if(!empty($data1))return $data1;

		$tmp=curl_get($url);

		$data=json_decode($tmp,true);

		if(empty($data))return;

		$data=$data['result'];

		$activityId=self::getin(str_replace("?","&",$data['Quan_link']),"&activity_id=","&");

		if(!empty($activityId)){

		$yhq_price=$GLOBALS['yhq_price']=floatval($data['Quan_price']);

		$yhq_span=$GLOBALS['yhq_span']="领券".$yhq_price."元";

		$data['yhq_url']="https://uland.taobao.com/coupon/edetail?activityId=$activityId&pid=$pid&itemId=$fnuo_id&src=tkzs_1&dx=1";

		self::set_app_cookie($data,86400,$url);

		}else{

			$data['yhq_url']='';

		}

		//$GLOBALS['yhq_type']=1;

		return $data;

	}

	//设置缓存

	public static function set_app_cookie($data=array(),$end_time=86400,$url='',$app=0,$t=0){

		//$t是因为有个地方要用到token了

		if(!empty($_GET['cookie'])&&$_GET['cookie']=="off")return;//测试用

		$c=$url;

		if($app==1){

			if(!isset($_GET['ctrl']))$_GET['ctrl']='';

			foreach($_POST as $k=>$v){

				if($k=='time'||$k=='sign')continue;

				if(strstr($_GET['ctrl'],"goods")==false&&$k=='token'&&$t==0)continue;

				$c.=$k.$v;

			}

		}

		$c=md5($c);

		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";

		zfun::wfile($url,json_encode(array("data"=>$data,"end_time"=>time()+$end_time)));

	}

	//读取缓存

	public static function read_app_cookie($url,$app=0,$t=0){

		//$t是因为有个地方要用到token了

		if(!empty($_GET['cookie'])&&$_GET['cookie']=="off")return;//测试用

		$c=$url;

		if($app==1){

			if(!isset($_GET['ctrl']))$_GET['ctrl']='';

			foreach($_POST as $k=>$v){

				if($k=='time'||$k=='sign')continue;

				if(strstr($_GET['ctrl'],"goods")==false&&$k=='token'&&$t==0)continue;

				$c.=$k.$v;

			}

		}

		$c=md5($c);

		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";

		if(file_exists($url)==false)return;

		$data=json_decode(zfun::get($url),true);

		if($data['end_time']<time())return;

		if(!empty($data['data']['success'])){return ($data['data']);}

		return $data['data'];

	}

	/*商品一级分类*/

	public function getCate(){

		appcomm::signcheck();

		/*$data=zfun::f_select("Category","pid=0 AND show_type=1","id,category_name,catename",0,0,"show_sort DESC");

		$arr=array("id"=>'0',"catename"=>'全部');

		array_unshift($data,$arr);

		foreach($data as $k=>$v){

			if(empty($v['catename']))$data[$k]['catename']=$v['category_name'];

			unset($data[$k]['category_name']);

		}	*/

		$data=array(

			array("catename"=>'全部',"id"=>0),

			array("catename"=>'女装',"id"=>1),

			array("catename"=>'男装',"id"=>9),

			array("catename"=>'内衣',"id"=>10),

			array("catename"=>'母婴',"id"=>2),

			array("catename"=>'化妆品',"id"=>3),

			array("catename"=>'居家',"id"=>4),

			array("catename"=>'鞋包配饰',"id"=>5),

			array("catename"=>'美食',"id"=>6),

			array("catename"=>'文体车品',"id"=>7),

			array("catename"=>'数码家电',"id"=>8),

		);

		zfun::fecho("商品一级分类",$data,1);

	}

	/*英雄榜*/

	public function yqFriend(){

		appcomm::signcheck();

		//排行榜

		$p=intval($_POST['p']);

		if(empty($p))$p=1;

		$num=20;

		$phuser=appcomm::f_goods("User","dlfl_sum>0","head_img,nickname,dlfl_sum","dlfl_sum DESC",NULL,$num);

		foreach($phuser as $k=>$v){

			$phuser[$k]['nickname']=self::xphone($v['nickname']);

			$head_img=$v['head_img'];

			if(empty($head_img))$head_img="default.png";

			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;

			$phuser[$k]['head_img']=$head_img;

			$phuser[$k]['logo']='';

			$phuser[$k]['num']=intval($k+1)+$num*($p-1);

			if($k==0&&intval($_POST['p'])<2)$phuser[$k]['logo']=INDEX_WEB_URL."View/index/img/wap/comm/invite_list_gold.png";

			else if($k==1&&intval($_POST['p'])<2)$phuser[$k]['logo']=INDEX_WEB_URL."View/index/img/wap/comm/invite_list_yin.png";

			else if($k==2&&intval($_POST['p'])<2)$phuser[$k]['logo']=INDEX_WEB_URL."View/index/img/wap/comm/invite_list_tong.png";

			$phuser[$k]['commission_sum']=zfun::dian($v['dlfl_sum']);

		}

		zfun::fecho("英雄榜",$phuser,1);

	}

	/*我的粉丝*/

	public function myFan(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$set=zfun::f_getset("jzcy_fl_onoff");

		$data['sum']=zfun::f_sum("Rebate","uid='{$uid}' and buy_share_uid<>'{$uid}' and returnstatus=1","fcommission");

		$where="extend_uid='{$uid}' and bili>0";

		if(intval($set['jzcy_fl_onoff'])==1)$where="extend_uid='{$uid}' ";//全部的

		$data['count']=zfun::f_count("Nexus",$where);

		$data['fan']=array();

		$sort_arr=array(

			"0"=>"lower_offer desc",//默认

			"1"=>"lower_reg_time desc",

			"2"=>"lower_reg_time asc",

			"3"=>"lower_offer desc",

			"4"=>"lower_offer asc",

		);

		$sort=$sort_arr[intval($_POST['sort']).''];



		$nexus=appcomm::f_goods("Nexus",$where,NULL,$sort,NULL,20);

		//fpre(count($user1));

		$nexus_user=zfun::f_kdata("User",$nexus,"lower_uid","id","id,head_img,is_sqdl,nickname,reg_time,phone,tg_pid,tb_app_pid,ios_tb_app_pid,wx_openid");	//百里追加

		foreach($nexus as $k=>$v){

			$one_user=$nexus_user[$v['lower_uid'].''];

			$head_img=$one_user['head_img'];

			if(empty($head_img))$head_img='default.png';

			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;

			$nexus[$k]['head_img']=$head_img;

			$nexus[$k]['nickname']=self::xphone($one_user['nickname']);

			$nexus[$k]['Vname']=self::getSetting("fxdl_name".($one_user['is_sqdl']+1));

			//百里.展示状态
			//仅锁粉待激活（未填写手机号）
			//未安装APP(有手机号）
			//未登录APP(无推广位）
			//待激活(有推广位无首单或订单为0
			if(empty($one_user['phone']) && empty($one_user['wx_openid']))
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

			$nexus[$k]['commission']=zfun::dian($v['lower_offer']);

			$nexus[$k]['reg_time']=$v['lower_reg_time'];

		}

		$set=zfun::f_getset("CustomUnit,YJCustomUnit");

		$data['str1']=$set['CustomUnit'];

		$data['str2']=$set['YJCustomUnit'];

		if(!empty($nexus))$data['fan']=$nexus;

		zfun::fecho("我的粉丝",$data,1);

	}

	/*下级返利排序*/

	public function next_fl_px($where,$uid,$url){

		$data1=self::read_app_cookie($url,1,1);

		if(!empty($data1))return $data1;



		$p=intval($_POST['p']);

		if(empty($p))$p=1;

		$user1=appcomm::f_goods("User",$where,"id,head_img,is_sqdl,nickname,reg_time",$sort,NULL,0);

		$hhr_next_fl=zfun::f_kdata("HhrNextJl",$user1,"id","uid","uid,sum","  extend_id='$uid'");

		foreach($user1  as $k=>$v){

			$head_img=$v['head_img'];

			if(empty($head_img))$head_img='default.png';

			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;

			$user1[$k]['head_img']=$head_img;

			$user1[$k]['nickname']=self::xphone($v['nickname']);

			$user1[$k]['Vname']=self::getSetting("fxdl_name".($v['is_sqdl']+1));

			$user1[$k]['commission']=zfun::dian($hhr_next_fl[$v['id']]['sum']);

			$user1[$k]['count']=zfun::f_count("User","extend_id='".$v['id']."'");

		}

		$sort='asc';

		if($_POST['sort']==3)$sort='desc';

		$user1=self::sortarr($user1,"commission",$sort);

		$i=1;

		$user1=array_values($user1);

		$arr=array();

		foreach($user1 as $k=>$v){

			if(empty($arr[$i]))$arr[$i]=array();

			$arr[$i][]=$v;

			if(count($arr[$i])==20)$i++;



		}

		foreach($arr as $k=>$v){

			if(empty($v))$arr[$k]=array();

		}

		if(empty($arr[$p]))$arr[$p]=array();

		self::set_app_cookie($arr[$p],3600,$url,1,1);

		return $arr[$p];

	}

	/*我邀请的合伙人*/

	public function myHhr(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		$djtg_lv=intval(self::getSetting("fxdl_tjhydj_".($user['is_sqdl']+1)));

		$att=self::getcarr($uid,"extend_id",1,"1");/*1级合伙人*/

		$att1=self::getcarr($uid,"extend_id",$djtg_lv,"1","1");/*2级之后合伙人*/

		$att2=self::getcarr($uid,"extend_id",$djtg_lv,"1");/*全部合伙人*/

		$data['count']=$att['count'];

		$data['all_count']=$att2['count'];

		$data['count_right']=$att1['count'];

		$where_hhr="uid IN(".$att2['uids'].") and extend_id='$uid'";

		$sum=zfun::f_sum("HhrNextJl",$where_hhr,"sum");

		$data['sum']=$sum;

		$where="id IN(".$att['uids'].") and is_sqdl>0";

		if(intval($_POST['is_hhr'])==1)$where="id IN(".$att1['uids'].") and is_sqdl>0";

		/*全部的佣金*/

		//$order_all=zfun::f_select("Order","(status IN('订单付款','订单结算','订单成功')) and uid IN(".$att2['uids'].")","commission,uid,tg_pid");

		$lvv=$att2['darr'];

		$lv_dl=intval($user['is_sqdl']+1);

		$data['fan']=array();

		/*佣金排序方法*/

		if(intval($_POST['sort'])>2){

			$url=zfun::thisurl();

			$data['fan']=self::next_fl_px($where,$uid,$url);

			zfun::fecho("我的粉丝",$data,1);

		}

		$sort=self::getSort1($_POST['sort']);

		$user1=appcomm::f_goods("User",$where,"id,head_img,is_sqdl,nickname,reg_time",$sort,NULL,20);

		$hhr_next_fl=zfun::f_kdata("HhrNextJl",$user1,"id","uid","uid,sum","  extend_id='$uid'");

		foreach($user1  as $k=>$v){

			$head_img=$v['head_img'];

			if(empty($head_img))$head_img='default.png';

			if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;

			$user1[$k]['head_img']=$head_img;

			$user1[$k]['nickname']=self::xphone($v['nickname']);

			$user1[$k]['Vname']=self::getSetting("fxdl_name".($v['is_sqdl']+1));

			$user1[$k]['commission']=zfun::dian($hhr_next_fl[$v['id']]['sum']);



		}

		if(!empty($user1))$data['fan']=$user1;

		zfun::fecho("我邀请的合伙人",$data,1);

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

	/*分享订单*/

	public function myOrder(){

		$user=appcomm::signcheck(1);$uid=$user['id'];

		if(empty($_POST['token']))zfun::fecho("请登录");

		$where="share_uid='$uid' and orderType='1'   AND uid<>".intval($user['id']);

		$data['fsxl']=zfun::f_count("Order",$where." and status IN ('订单付款','订单结算','订单成功')");

		$data['jjdz']=zfun::f_count("Order",$where." and status IN ('订单付款','订单结算','订单成功') and fenxiang_returnstatus=0");

		$data['ljjl']=zfun::f_count("Order",$where." and fenxiang_returnstatus=1");

		switch($_POST['type']){

			case 0:

				$where.=" and status IN ('订单付款','订单结算','订单成功')";

				break;

			case 1:

				$where.=" and status IN ('订单付款','订单结算','订单成功') and fenxiang_returnstatus=0";

				break;

			case 2:

				$where.=" and fenxiang_returnstatus=1";

				break;

		}

		$fi="id,orderId,now_user,fenxiang_returnstatus,share_uid,uid,postage,goodsNum,info,shop_title,goodsId,status,createDate,payDate,status,orderType,goodsInfo,commission,goods_img,return_commision,estimate,payment,returnstatus,choujiang_n,choujiang_sum,choujiang_data,choujiang_money";

		$num=20;

		$order = appcomm::f_goods("Order", $where, $fi, 'createDate desc', NULL, $num);

		actionfun("appapi/commGetMore");

		$arr=commGetMoreAction::firstOrder($order,$user);

		//appcomm::goodsfanlioff($arr);

		$sarr=array("创建订单"=>"待付款","订单付款"=>"已付款","订单结算"=>"未到账","订单失效"=>"已失效");

		foreach($arr as $k=>$v){

			//之前搞错字段了，app读的这个字段

			$arr[$k]['commission']=$v['fcommission'];

			$arr[$k]['status']=$sarr[$v['status']];

			if($v['fenxiang_returnstatus']==1)$arr[$k]['status']="已到账";

			$arr[$k]['tgNickname']='';

		}

		//图片缺失处理

		actionfun("comm/tbmaterial");

		foreach($arr as $k=>$v){

			if(empty($v['goods_img'])){

				$tmp=tbmaterial::id($v['goodsId']);

				if(!empty($tmp)){

					$arr[$k]['goods_img']=$tmp['goods_img'];

					zfun::f_update("Order","id='".$v['id']."'",array("goods_img"=>$tmp['goods_img']));

				}

			}

		}

		if(empty($arr))$arr=array();

		$data['order']=$arr;

		zfun::fecho("分享订单",$data,1);

	}

	/*我的团队订单*/

	public function myteamOrder(){

		$user=appcomm::signcheck(1);zfun::$set['uid']=$uid=$user['id'];

		$where=" platform='tb' and uid='$uid' and bili<>0 and comment<>'自购'";

		$data['all']=zfun::f_count("Rebate",$where);

		$data['yfk']=zfun::f_count("Rebate",$where." and status IN('订单付款') and returnstatus=0");

		$data['ysx']=zfun::f_count("Rebate",$where." and status='订单失效'");

		$data['yjs']=zfun::f_count("Rebate",$where." and returnstatus=1");

		if(!empty($_POST['show_ysh'])){

			$data['ysh']=zfun::f_count("Rebate",$where." and status IN('订单成功','订单结算') and returnstatus=0");

		}

		switch($_POST['type']){

			case 1:

				$where.=" and status IN('订单付款') and returnstatus=0";

				break;

			case 2:

				$where.=" and status='订单失效'";

				break;

			case 3:

				$where.=" and returnstatus=1 ";

				break;

			case 4:

				$where.=" and status IN('订单成功','订单结算') and returnstatus=0";

				break;

		}

		//$fi="id,orderId,now_user,share_uid,fenxiang_returnstatus,uid,postage,goodsNum,info,shop_title,goodsId,status,createDate,payDate,status,orderType,goodsInfo,commission,goods_img,return_commision,estimate,payment,returnstatus,choujiang_n,choujiang_sum,choujiang_data,choujiang_money";

		$num=20;

		$order = appcomm::f_goods("Rebate", $where, $fi, 'order_create_time desc', NULL, $num);

		actionfun("appapi/commGetMore");

		$arr=commGetMoreAction::secondOrder($order,$user);

		//appcomm::goodsfanlioff($arr);

		$sarr=array("创建订单"=>"待付款","订单付款"=>"已付款","订单成功"=>"未到账","订单结算"=>"未到账","订单失效"=>"已失效");

		$buy_user=zfun::f_kdata("User",$arr,"uid","id","id,extend_id,is_sqdl");

		$invite_user=zfun::f_kdata("User",$buy_user,"extend_id","id","id,nickname,tg_pid,is_sqdl");

		foreach($arr as $k=>$v){

			//之前搞错字段了，app读的这个字段

			$arr[$k]['commission']=$v['fcommission'];

			$arr[$k]['status']=$sarr[$v['status']];

			if($v['returnstatus']==1)$arr[$k]['status']="已到账";

			$arr[$k]['tgNickname']=$invite_user[$buy_user[$v['uid']]['extend_id']]['nickname'];

		}

		//图片缺失处理

		actionfun("comm/tbmaterial");

		foreach($arr as $k=>$v){

			if(empty($v['goods_img'])){

				$tmp=tbmaterial::id($v['goodsId']);

				if(!empty($tmp)){

					$arr[$k]['goods_img']=$tmp['goods_img'];

					zfun::f_update("Order","id='".$v['id']."'",array("goods_img"=>$tmp['goods_img']));

				}

			}

		}

		if(empty($arr))$arr=array();

		$data['order']=$arr;

		zfun::fecho("我的团队订单",$data,1);

	}

	public static function comm_dl($uid=0){

		$dllist=zfun::f_select("DLList","uid='$uid' and checks=1 and dl_dj<>0","",0,0,"dl_dj asc");

		$str=-1;

		for($i=1;$i<=10;$i++){

			$str.=",fxdl_tjhydj".$i;

		}

		$str=substr($str,3);

		$set=zfun::f_getset($str);

		foreach($dllist as $k=>$v){

			$lv=$set['fxdl_tjhydj'.($v['dl_dj']+1)];

			$eids=self::getc($user['id'],"extend_id",$lv);

			$time=$v['succ_time'];

			if(empty($v['succ_time']))$time=$v['time'];



		}

	}

	/*点击生成二维码*/

	public function ercode(){

		$user=appcomm::signcheck(1);

		$fnuo_id1=filter_check($_POST['fnuo_id']);

		$fnuo_id=explode("_",$fnuo_id1);

		$fnuo_id=$fnuo_id[0];

		$data=array();

		$data['img']=INDEX_WEB_URL."?mod=appapi&act=appHhr&ctrl=getcode&fnuo_id=".$fnuo_id."&getGoodsType=".$_POST['getGoodsType']."&token=".$_POST['token'];

		zfun::fecho("点击生成二维码",$data,1);

	}

	public function getcode(){

		ob_end_clean();

		$cookie_key="img getcode";



		foreach($_GET as $k=>$v){

			$cookie_key.=$k."_".$v;

		}

		$cookie_key=md5($cookie_key);

		$cookie_path=ROOT_PATH."Temp/dgapp/{$cookie_key}.log";

		if(file_exists($cookie_path)){

			$tmp=zfun::get($cookie_path);

			zfun::head("png");

			echo $tmp;

			return;

		}



		if(empty($_GET['create'])){

			$url=INDEX_WEB_URL."?".$_SERVER['QUERY_STRING']."&create=on";

			$data=curl_get($url);

			@file_put_contents($cookie_path,$data);

			zfun::head("png");

			echo $data;

			return;

		}



		$fnuo_id=$_GET['fnuo_id'];

		$token=filter_check($_GET['token']);

		$user=zfun::f_row("User","token='$token'");

		$getgoodstype=filter_check($_GET['getGoodsType']);//类型 物料 大淘客

		$set=zfun::f_getset("ggapitype");

			actionfun("comm/tbmaterial");

			$v=tbmaterial::id($fnuo_id);



			if(!empty($_GET['img']))$v['goods_img']=$_GET['img'];

			$arr=array(

				"goods_title"=>$v['goods_title'],

				"goods_price"=>$v['goods_price'],

				"goods_cost_price"=>$v['goods_cost_price'],

				"goods_img"=>str_replace("_250x250.jpg","_500x500.jpg",$v['goods_img']),

				"goods_sales"=>$v['goods_sales'],

				"commission"=>$v['commission'],

				"shop_id"=>$v['shop_id'],

				"fnuo_id"=>$v['fnuo_id'],

				"yhq_price"=>$v['yhq_price'],

				"start_time"=>$v['start_time'],

				"end_time"=>$v['end_time'],

			);

			actionfun("appapi/goods_check_yhq");

			$arr=goods_check_yhqAction::check(array($arr));$arr=reset($arr);



			if($v['shop_id']==1)$arr['shop_type']="淘宝";

			elseif($v['shop_id']==2)$arr['shop_type']="天猫";

		actionfun("default/gototaobao");

		$arr['yhq_url']='';

		//$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$v['goods_title'],"fnuo_id"=>$fnuo_id),1);

		if(!empty($tmp_yhq_url))$arr['yhq_url']=$tmp_yhq_url;

		if(empty($getgoodstype)){

			if(!empty($GLOBALS['yhq_span']))$arr['yhq_span']=$GLOBALS['yhq_span'];

			if(!empty($GLOBALS['yhq_price']))$arr['yhq_price']=$GLOBALS['yhq_price'];

			if(!empty($GLOBALS['yhq_span']))$arr['yhq_span']=$GLOBALS['yhq_span'];

			if(!empty($GLOBALS['goods_cost_price']))$arr['goods_cost_price']=$GLOBALS['goods_cost_price'];

			if(!empty($GLOBALS['goods_price']))$arr['goods_price']=$GLOBALS['goods_price'];

			//if(!empty($GLOBALS['dtk_commission'])&&$GLOBALS['dtk_commission']>$arr['commission'])$arr['commission']=$GLOBALS['dtk_commission'];

		}

		if(!empty($arr['yhq_price'])){

			$arr['yhq']=1;

		}

		$goods=zfun::f_fgoodscommission(array($arr));$goods=reset($goods);

		// 百里.修改前
		// self::new_qrcode($goods,$user,'',1);
		// 百里.修改后
		self::new_qrcode2($goods,$user,'',1);
	}

	public static function bdurl($bd,$bd2,$bd3,$bd4){

		//$bdurl='http://cj.fnuo123.com/rebate_rebateShareDetail_wap-545269808157-931.html';

		$set=zfun::f_getset("xinlang_key");

		$source=$set['xinlang_key'];

		if(empty($source))return array($bd,$bd2,$bd3,$bd4);

		$bd=urlencode($bd);

		$bd2=urlencode($bd2);

		$bd3=urlencode($bd3);

		$bd4=urlencode($bd4);

		$url="https://api.weibo.com/2/short_url/shorten.json?source=$source";

		if(!empty($bd))$url.="&url_long=$bd";

		if(!empty($bd2))$url.="&url_long=$bd2";

		if(!empty($bd3))$url.="&url_long=$bd3";

		if(!empty($bd4))$url.="&url_long=$bd4";

		$data=zfun::curl_get($url);

		$data=json_decode($data,true);

		$arr=array();

		foreach($data['urls']  as $k=>$v){

			$arr[]=$v['url_short'];

		}

		return $arr;

	}

	public static function qrcode2($arr=array(),$user=array(),$urls='',$getnew=0){//生成二维码

		$img=str_replace("https:","http:",$arr['goods_img']);

		$tgidkey = self::getApp('Tgidkey');

		$tgid = $tgidkey->addkey($user['id']);

		if(!empty($user['tg_code']))$tgid=$user['tg_code'];

		$getgoodstype=filter_check($_POST['getGoodsType']);//类型 物料 大淘客

		$tg_url=self::getUrl('rebate_DG', 'rebate_detail', array("getgoodstype"=>$getgoodstype,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),'wap');

		$set=zfun::f_getset("android_url,tg_durl,is_openbd,app_goods_tw_url");

		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$pset['fnuo_id'],'tgid' => $tgid),'new_share');

		$url2=self::getUrl("down","supdownload",array('tgid' => $tgid),"appapi");/*更换*/

		$url4=$set['android_url'];

		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";

		if(intval($set['tg_durl'])==1){

			//$url1=INDEX_WEB_URL."rebate_rebateShareDetail_wap-".$arr['fnuo_id']."-".$tgid.".html";

			$url1=INDEX_WEB_URL."?mod=wap&act=rebate_DG&ctrl=rebateShareDetail&getgoodstype=$getgoodstype&tgid=".($tgid)."&fnuo_id=".filter_check($arr['fnuo_id']);

			if(!empty($set['is_openbd']))$bd="http://fanyi.baidu.com/transpage?query=".urlencode($url1)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

				$url1=$tg_url;

				$bd=$url1;

			}

			if(!empty($set['is_openbd']))$bd2="http://fanyi.baidu.com/transpage?query=".urlencode($url2)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

				$bd2=$url2;

			}

			//$url3=INDEX_WEB_URL."new_share-".$tgid."-".$arr['fnuo_id']."-1.html";

			if(!empty($set['is_openbd']))$bd3="http://fanyi.baidu.com/transpage?query=".urlencode($url3)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

				$bd3=$url3;

			}

			$arrulr=self::bdurl($bd,$bd2,$bd3);

			if(!empty($arrulr[0]))$tg_url=$arrulr[0];

			if(!empty($arrulr[1]))$url2=$arrulr[1];

			if(!empty($arrulr[2]))$url3=$arrulr[2];

		}

		if(intval($set['app_goods_tw_url'])==1){

			$tg_url=$url2;

		}

		if(intval($set['app_goods_tw_url'])==2){

			$tg_url=$url3;

		}

		if(intval($set['app_goods_tw_url'])==3){

			$tg_url=$url4;

		}

		$data = array();

		$data['width']=600;

		$data['height']=880;



       $data['list'][0] = array(

            "url" => INDEX_WEB_URL."View/index/img/wap/takeOutt/sup_download/bj1.png",

            "x" => 0,

            "y" => 0,

            "width" => 600,

            "height" => 880,

			"type"=>"png"

        );

		$data['list'][1] = array(//右下角的框

            "url" => INDEX_WEB_URL."View/index/img/wap/takeOutt/sup_download/bj.png",

            "x" => 380,

            "y" => 650,

            "width" => 193,

            "height" => 177,

			"type"=>"png"

        );

		$data['list'][2] = array(//二维码

		   "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($tg_url)."&size=11&codeKB=1",

            "x" => 400,

            "y" => 660,

            "width" => 150,

            "height" => 150,

			"type"=>"png"

        );

		//

		$data['list'][3] = array(

            "url" => $img,

            "x" =>0,

            "y" => 0,

            "width" => 600,

            "height" => 600,

			"type"=>"jpg"

        );

      //	round(strlen($arr['goods_title'])/16)-1

		for($i=0;$i<3;$i++){

			$data['text'][$i]=array(

				"size"=>15,

				"x"=>10,

				"y"=>670+25*$i,

				"val"=>mb_substr($arr['goods_title'],16*$i,16,'utf-8'),

				"i"=>$i,

			);

		}

		$ii=end($data['text']);

		$data['text'][$ii['i']+1]=array(

			"size"=>16,

			"x"=>10,

			"y"=>670+22*$ii['i']+50,

			"val"=>"现价：￥".(floatval($arr['goods_price'])+floatval($arr['yhq_price'])),

		);

		if(floatval($arr['yhq_price'])>0){

			$data['list'][4] = array(//

				"url" => INDEX_WEB_URL."View/index/img/wap/takeOutt/sup_download/quan.png",

				"x" => 10,

				"y" => 670+22*$ii['i']+70,

				"width" => 123,

				"height" => 48,

				"type"=>"png"

			);

			$data['text'][$ii['i']+2]=array(

				"size"=>16,

				"x"=>60,

				"y"=>670+22*$ii['i']+100,

				"val"=>(floatval($arr['yhq_price'])),

			);

			$data['text'][$ii['i']+3]=array(

				"size"=>16,

				"x"=>150,

				"y"=>670+22*$ii['i']+100,

				"val"=>"券后价：￥".(floatval($arr['goods_price'])),

			);

		}

		foreach($data['text'] as $k=>$v){

			if(empty($v['val']))unset($data['text'][$k]);

		}

		$data['text']=array_values($data['text']);

		if($getnew==1){

			fun("pic");

				//fpre($data);

			return pic::getpic($data);

		}

		$data=zfun::arr64_encode($data);

		$url=INDEX_WEB_URL."comm/pic.php?pic_ctrl=getpic&data=".urlencode($data);

		return $url;



	}

	public static function new_qrcode($arr=array(),$user=array(),$urls='',$getnew=0){//生成二维码

		$img=str_replace("https:","http:",$arr['goods_img']);

		if($arr['shop_id']==1)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/taobao_one.png?time=".time();

		if($arr['shop_id']==2)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/tmall_one.png?time=".time();

		if($arr['pdd']==1){$shop_width='95';$shop_height='48';$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/pdd_one.png?time=".time();}

		if($arr['jd']==1)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/jd_one.png?time=".time();

		$tgidkey = self::getApp('Tgidkey');

		$tgid = $tgidkey->addkey($user['id']);

		if(!empty($user['tg_code']))$tgid=$user['tg_code'];

		$getgoodstype=filter_check($_POST['getGoodsType']);//类型 物料 大淘客

		$tg_url=self::getUrl('rebate_DG', 'rebate_detail', array("getgoodstype"=>$getgoodstype,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),'wap');

		$set=zfun::f_getset("share_host,android_url,tg_durl,is_openbd,app_goods_tw_url,app_goods_tljtw_url");

		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),'new_share');

		$url2=self::getUrl("down","supdownload",array('tgid' => $tgid),"appapi");/*更换*/

		//新商品详情

		$goods_down_url=self::getUrl("rebate_DG","rebate_detail",array("type"=>'down',"is_goods_share"=>1,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),"wap");

		if(!empty($set['share_host'])){

			$tg_url=str_replace(HTTP_HOST,$set['share_host'],$tg_url);

			$url2=str_replace(HTTP_HOST,$set['share_host'],$url2);

			$url3=str_replace(HTTP_HOST,$set['share_host'],$url3);

			$goods_down_url=str_replace(HTTP_HOST,$set['share_host'],$goods_down_url);

		}

		$url4=$set['android_url'];

		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";



		if(intval($set['tg_durl'])==1){

			//$url1=INDEX_WEB_URL."rebate_rebateShareDetail_wap-".$arr['fnuo_id']."-".$tgid.".html";

			$url1=INDEX_WEB_URL."?mod=wap&act=rebate_DG&ctrl=rebateShareDetail&getgoodstype=$getgoodstype&tgid=".($tgid)."&fnuo_id=".filter_check($arr['fnuo_id']);

			if(!empty($set['is_openbd']))$bd="http://fanyi.baidu.com/transpage?query=".urlencode($url1)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

			//	$url1=INDEX_WEB_URL."rebate-".$arr['fnuo_id']."-".$tgid.".html";

				$url1=$tg_url;

				$bd=$url1;

			}

			if(!empty($set['is_openbd']))$bd2="http://fanyi.baidu.com/transpage?query=".urlencode($url2)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

				$bd2=$url2;

			}

			//$url3=INDEX_WEB_URL."new_share-".$tgid."-".$arr['fnuo_id']."-1.html";

			if(!empty($set['is_openbd']))$bd3="http://fanyi.baidu.com/transpage?query=".urlencode($url3)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

				$bd3=$url3;

			}

			$arrulr=self::bdurl($bd,$bd2,$bd3,$goods_down_url);

			if(!empty($arrulr[0]))$tg_url=$arrulr[0];

			if(!empty($arrulr[1]))$url2=$arrulr[1];

			if(!empty($arrulr[2]))$url3=$arrulr[2];

			if(!empty($arrulr[3]))$goods_down_url=$arrulr[3];

		}



		//淘礼金

		if($_GET['tlj']==1)$set['app_goods_tw_url']=intval($set['app_goods_tljtw_url']);

		if(intval($set['app_goods_tw_url'])==1){

			$tg_url=$url2;

		}

		if(intval($set['app_goods_tw_url'])==2){

			$tg_url=$url3;

		}

		if(intval($set['app_goods_tw_url'])==3){

			$tg_url=$url4;

		}

		if(intval($set['app_goods_tw_url'])==4){

			$tg_url=$goods_down_url;

		}

		$data = array();

		$data['width']=750 * 2;

		$data['height']=1334 * 2;



       $data['list'][0] = array(//底部的框
            "url" => INDEX_WEB_URL."View/index/img/appapi/comm/code_bg_0.png?time=".time(),
            "x" => 0,
            "y" => 1054 * 2,
            "width" => 750 * 2,
            "height" => 270 * 2,
			"type"=>"png"
        );
		$data['list'][1] = array(//二维码
		   "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($tg_url)."&size=20&codeKB=1",
            "x" => 27 * 2,
            "y" => 1165 * 2,
            "width" => 130 * 2,
            "height" => 130 * 2,
			"type"=>"png"
        );
		//
		$data['list'][2] = array(//商品图【图片
            "url" => $img,
            "x" =>30 * 2,
            "y" => 350 * 2,
            "width" => 680 * 2,
            "height" => 680 * 2,
			"type"=>"jpg"
        );
		// $data['list'][3] = array(//商品来源
  //           "url" => $shop_img,
  //           "x" =>34,
  //           "y" => 75,
  //           "width" => 64,
  //           "height" => 32,
		// 	"type"=>"jpg"
  //       );
		$data['list'][4] = array(//
            "url" => INDEX_WEB_URL."View/index/img/appapi/comm/h2_nr_kuang_gold.png?time=".time(),
            "x" =>475 * 2,
            "y" => 890 * 2,
            "width" => 252 * 2,
            "height" => 105 * 2,
			"type"=>"png"
        );
        $data['list'][7] = array(//logo
            // "url" => INDEX_WEB_URL."View/index/img/appapi/comm/good_share_logo.png?time=".time(),
            "url" => INDEX_WEB_URL."Upload/huasuan/goods_share/header.png?time=".time(),
            "x" =>30 * 2,
            "y" => 50 * 2,
            "width" => 257 * 2,
            "height" => 65 * 2,
			"type"=>"png"
        );

		for($i=0;$i<3;$i++){
			$data['text'][$i]=array(
				"size"=>22 * 2,
				"x"=>30 * 2,
				"y"=>(180+40*$i)*2,
				"val"=>mb_substr($arr['goods_title'],intval(590/25)*$i,intval(590/25),'utf-8'),
				"i"=>$i,
			);
			if($i!=0){
				$data['text'][$i]['x']=30;
			}
		}
		foreach($data['text'] as $k=>$v){
			if(empty($v['val']))unset($data['text'][$k]);
		}
		$data['text']=array_values($data['text']);
		$ii=end($data['text']);
		$y=80+30*$ii['i'];
		// $data['text'][$ii['i']+1]=array(
		// 	"size"=>34,
		// 	"x"=>140,
		// 	"y"=>$y+128,
		// 	"val"=>"￥".(floatval($arr['goods_price'])),
		// 	"color"=>'red',
		// );

		// $quan=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan_one.png?time=".time();

		// if(floatval($arr['yhq_price'])==0){$quan=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan_one.png?time=".time();}
		// $data['list'][5] = array(
  //           "url" => $quan,
  //           "x" =>33,
  //           "y" => $y+80,
  //           "width" => 89,
  //           "height" => 32,
		// 	"type"=>"png"
  //       );
		$data['text'][$ii['i']+2]=array(
			"size"=>34 * 2,
			"x"=>520 * 2,
			"y"=>965 * 2,
			"val"=>"￥". sprintf("%.2f", floatval( $arr['goods_price'] ) ),
			"color"=>'white',
		);

		$arr['yhq_price']=floatval($arr['yhq_price']);
		if(!empty($arr['yhq_price'])){
			$len=strlen(floatval($arr['yhq_price'])."元优惠券");
			$data['text'][$ii['i']+3] = array(
				"size"=>25 * 2,
				"x"=>55 * 2,
				"y"=>($y+192)*2,
				"val"=>(floatval($arr['yhq_price']))."元优惠券",
				"color"=>'white',
			);
			$data['list'][6] = array(
				"url" => INDEX_WEB_URL."View/index/img/appapi/comm/h2_nr_kuang_gold_one.png?time=".time(),
				"x" =>30 * 2,
				"y" => ($y+155) * 2,
				"width" => 225 * 2,
				"height" => 50 * 2,
				"type"=>"png"
			);
		}

		if($getnew==1){
			fun("pic");
				//fpre($data);
			return pic::getpic($data);
		}
		$data=zfun::arr64_encode($data);
		$url=INDEX_WEB_URL."comm/pic.php?pic_ctrl=getpic&data=".urlencode($data);
		return $url;



	}


	/*合伙人收益中心*/

	public function dl_list(){

		appcomm::signcheck();

		if(empty($_POST['token']))zfun::fecho("请登录");

		$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'","commission,is_sqdl,commission_sum,is_sqdl_time,id,dlcommission,tg_pid,operator_lv");

		$uid=$user['id'];

		//zfun::run("Action/index/appapi/commGetMore.action.php");

		actionfun("appapi/commGetMore");

		$data=commGetMoreAction::getcommSy($user);

		$user['byyg']=zfun::dian($data['by_yg']);

		$user['byjs']=zfun::dian($data['by_js']);

		$user['syyg']=zfun::dian($data['sy_yg']);

		$user['syjs']=zfun::dian($data['sy_js']);

		$user['today_yes']=array(

			array(

				"money"=>zfun::dian($data['t_fxz']),//分享赚

				"teammoney"=>zfun::dian($data['t_team']),//团队

				"hl_money"=>zfun::dian($data['t_yg']),

			),

			array(

				"money"=>zfun::dian($data['y_fxz']),

				"teammoney"=>zfun::dian($data['y_team']),

				"hl_money"=>zfun::dian($data['y_yg']),

			),

		);

		$au=zfun::f_select("Authentication","type IN(3,7) and uid<>0 and uid='$uid' and audit_status=1","data");

		$money=0;

		foreach($au as $k=>$v){

			$arr=json_decode($v['data'],true);

			if(empty($arr['money']))$arr['money']=$arr['txmoney'];

			$au[$k]['money']=$arr['money'];

			unset($au[$k]['data']);

			$money+=abs($au[$k]['money']);

		}

		$user['commission_sum']=zfun::dian($user['commission']+$user['dlcommission']+$money);

		$user['dlcommission']=zfun::dian($user['commission']);

		$user['own_sum']=zfun::dian($data['all_fxz']);

		$user['team_sum']=zfun::dian($data['all_team']);

		$sett=zfun::f_getset("CustomUnit,rmb_ico");

		$user['str1']='立即提币';

		$user['str2']=$sett['CustomUnit'];

		$user['icon']=UPLOAD_URL."geticos/".$sett['rmb_ico'];

		unset($user['is_sqdl']);unset($user['is_sqdl_time']);

		zfun::fecho("合伙人收益中心",$user,1);

	}

	/*排序*/

	public static function getSort($getsort){

		switch(filter_check($getsort)) {

			case 1 :

				// 人气

				$sort = "tg_sort desc,tg_sort desc";

				break;

			case 2:

				// 价格低到高

				$sort = "tg_sort desc,goods_price asc";

				break;

			case 3:

				//最新

				$sort = "tg_sort desc,cj_time asc";

				break;

			case 4 :

				// 销量

				$sort = "tg_sort desc,goods_sales desc";

				break;



		}

		if(empty($sort))$sort='tg_sort desc';

		return $sort;

	}

	/*排序*/

	public static function getSort1($getsort){

		switch(filter_check($getsort)) {

			case 1 :

				// 入驻时间高到低

				$sort = "reg_time desc";

				break;

			case 2:

				// 入驻时间低到高

				$sort = "reg_time asc";

				break;

			/*case 3 :

				// 佣金高到低

				$sort = "sum desc";

				break;

			case 4:

				//  佣金低到高

				$sort = "sum asc";

				break;*/

		}

		return $sort;

	}

	/*筛选条件*/

	public static function getWhere($where,$start_price,$end_price,$keyword,$source){

		/*价格筛选*/

		if(!empty($start_price)&&empty($end_price))$where.=" AND goods_price>=$start_price";

		if(!empty($end_price)&&empty($start_time))$where.=" AND goods_price<=$end_price";

		if(!empty($end_price)&&!empty($start_price))$where.=" AND goods_price>=$start_price AND goods_price<=$end_price";

		/*关键词*/

		if (($keyword)) {

			$where .= " AND goods_title like '%".$_POST['keyword']."%' ";

		}

		/*商家*/

		switch($source){

			case 1:

				$where.=" AND shop_id IN(1,2)";//淘宝

				break;

			case 2:

				$where.=" AND shop_id=2";//天猫

				break;

			case 3:

				$where.=" AND shop_id=4";//京东

				break;

		}

		return $where;

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

				if($is_cy==1&&$lv==1)unset($arr[$lv]);

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

	public static function xphone($phone=''){

		// $phone.="";

		// $len=strlen($phone);

		// if($len>=11){

		// 	return mb_substr($phone,0,3,"utf-8")."******".mb_substr($phone,-2,2,"utf-8");

		// }

		// if($len>=5){

		// 	return mb_substr($phone,0,2,"utf-8")."***".mb_substr($phone,-1,1,"utf-8");

		// }

		// return mb_substr($phone,0,1,"utf-8")."*";

		//百里.修改后
		return mb_substr($phone,0,1,"utf-8")."**".mb_substr($phone,-1,1,"utf-8");

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

	//explosion

	public static function getalllv($uid, $tidname = "extend_id", $maxlv = 9,$getlv=0) {//获取下级

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

			if($is_sqdl==1)$where.= "and is_sqdl>0";

			$user = zfun::f_select("User",$where,"id");

			if (!empty($user)) {

				$tid = "";

				foreach ($user as $k => $v)

					$tid .= "," . $v['id'];

				$tid = substr($tid, 1);

				$arr[$lv] = $tid;

				if($lv<$getlv&&!empty($getlv))unset($arr[$lv]);

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

	/**
	 * [getcode2 百里.测试.无缓存]
	 * getcode 有缓存
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2019-01-18T16:23:53+0800
	 * @return   [type]                   [description]
	 */
	public function getcode2(){

		ob_end_clean();

		$cookie_key="img getcode";



		foreach($_GET as $k=>$v){

			$cookie_key.=$k."_".$v;

		}

		$cookie_key=md5($cookie_key);

		$cookie_path=ROOT_PATH."Temp/dgapp/{$cookie_key}.log";

		$fnuo_id=$_GET['fnuo_id'];

		$token=filter_check($_GET['token']);

		$user=zfun::f_row("User","token='$token'");

		$getgoodstype=filter_check($_GET['getGoodsType']);//类型 物料 大淘客

		$set=zfun::f_getset("ggapitype");

			actionfun("comm/tbmaterial");

			$v=tbmaterial::id($fnuo_id);



			if(!empty($_GET['img']))$v['goods_img']=$_GET['img'];

			$arr=array(

				"goods_title"=>$v['goods_title'],

				"goods_price"=>$v['goods_price'],

				"goods_cost_price"=>$v['goods_cost_price'],

				"goods_img"=>str_replace("_250x250.jpg","_500x500.jpg",$v['goods_img']),

				"goods_sales"=>$v['goods_sales'],

				"commission"=>$v['commission'],

				"shop_id"=>$v['shop_id'],

				"fnuo_id"=>$v['fnuo_id'],

				"yhq_price"=>$v['yhq_price'],

				"start_time"=>$v['start_time'],

				"end_time"=>$v['end_time'],

			);

			actionfun("appapi/goods_check_yhq");

			$arr=goods_check_yhqAction::check(array($arr));$arr=reset($arr);



			if($v['shop_id']==1)$arr['shop_type']="淘宝";

			elseif($v['shop_id']==2)$arr['shop_type']="天猫";

		actionfun("default/gototaobao");

		$arr['yhq_url']='';

		//$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$v['goods_title'],"fnuo_id"=>$fnuo_id),1);

		if(!empty($tmp_yhq_url))$arr['yhq_url']=$tmp_yhq_url;

		if(empty($getgoodstype)){

			if(!empty($GLOBALS['yhq_span']))$arr['yhq_span']=$GLOBALS['yhq_span'];

			if(!empty($GLOBALS['yhq_price']))$arr['yhq_price']=$GLOBALS['yhq_price'];

			if(!empty($GLOBALS['yhq_span']))$arr['yhq_span']=$GLOBALS['yhq_span'];

			if(!empty($GLOBALS['goods_cost_price']))$arr['goods_cost_price']=$GLOBALS['goods_cost_price'];

			if(!empty($GLOBALS['goods_price']))$arr['goods_price']=$GLOBALS['goods_price'];

			//if(!empty($GLOBALS['dtk_commission'])&&$GLOBALS['dtk_commission']>$arr['commission'])$arr['commission']=$GLOBALS['dtk_commission'];

		}

		if(!empty($arr['yhq_price'])){

			$arr['yhq']=1;

		}

		$goods=zfun::f_fgoodscommission(array($arr));$goods=reset($goods);

		// 百里.修改前
		// self::new_qrcode($goods,$user,'',1);
		// 百里.修改后
		self::new_qrcode2($goods,$user,'',1);
	}


	/**
	 * [new_qrcode2 百里.花蒜修改]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2019-01-18T16:11:26+0800
	 * @param    array                    $arr    [description]
	 * @param    array                    $user   [description]
	 * @param    string                   $urls   [description]
	 * @param    integer                  $getnew [description]
	 * @return   [type]                           [description]
	 */
	public static function new_qrcode2($arr=array(),$user=array(),$urls='',$getnew=0){//生成二维码

		$img=str_replace("https:","http:",$arr['goods_img']);

		if($arr['shop_id']==1)$shop_img=INDEX_WEB_URL."Upload/huasuan/goods_share/tb.png?time=".time();

		if($arr['shop_id']==2)$shop_img=INDEX_WEB_URL."Upload/huasuan/goods_share/tm.png?time=".time();

		if($arr['pdd']==1){$shop_width='95';$shop_height='48';$shop_img=INDEX_WEB_URL."Upload/huasuan/goods_share/pdd.png?time=".time();}

		if($arr['jd']==1)$shop_img=INDEX_WEB_URL."Upload/huasuan/goods_share/jd.png?time=".time();

		$tgidkey = self::getApp('Tgidkey');

		$tgid = $tgidkey->addkey($user['id']);

		if(!empty($user['tg_code']))$tgid=$user['tg_code'];

		$getgoodstype=filter_check($_POST['getGoodsType']);//类型 物料 大淘客

		$tg_url=self::getUrl('rebate_DG', 'rebate_detail', array("getgoodstype"=>$getgoodstype,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),'wap');

		$set=zfun::f_getset("share_host,android_url,tg_durl,is_openbd,app_goods_tw_url,app_goods_tljtw_url");

		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),'new_share');

		$url2=self::getUrl("down","supdownload",array('tgid' => $tgid),"appapi");/*更换*/

		//新商品详情

		$goods_down_url=self::getUrl("rebate_DG","rebate_detail",array("type"=>'down',"is_goods_share"=>1,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),"wap");

		if(!empty($set['share_host'])){

			$tg_url=str_replace(HTTP_HOST,$set['share_host'],$tg_url);

			$url2=str_replace(HTTP_HOST,$set['share_host'],$url2);

			$url3=str_replace(HTTP_HOST,$set['share_host'],$url3);

			$goods_down_url=str_replace(HTTP_HOST,$set['share_host'],$goods_down_url);

		}

		$url4=$set['android_url'];

		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";



		if(intval($set['tg_durl'])==1){

			//$url1=INDEX_WEB_URL."rebate_rebateShareDetail_wap-".$arr['fnuo_id']."-".$tgid.".html";

			$url1=INDEX_WEB_URL."?mod=wap&act=rebate_DG&ctrl=rebateShareDetail&getgoodstype=$getgoodstype&tgid=".($tgid)."&fnuo_id=".filter_check($arr['fnuo_id']);

			if(!empty($set['is_openbd']))$bd="http://fanyi.baidu.com/transpage?query=".urlencode($url1)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

			//	$url1=INDEX_WEB_URL."rebate-".$arr['fnuo_id']."-".$tgid.".html";

				$url1=$tg_url;

				$bd=$url1;

			}

			if(!empty($set['is_openbd']))$bd2="http://fanyi.baidu.com/transpage?query=".urlencode($url2)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

				$bd2=$url2;

			}

			//$url3=INDEX_WEB_URL."new_share-".$tgid."-".$arr['fnuo_id']."-1.html";

			if(!empty($set['is_openbd']))$bd3="http://fanyi.baidu.com/transpage?query=".urlencode($url3)."&source=url&ie=utf8&from=en&to=zh&render=1";

			else{

				$bd3=$url3;

			}

			$arrulr=self::bdurl($bd,$bd2,$bd3,$goods_down_url);

			if(!empty($arrulr[0]))$tg_url=$arrulr[0];

			if(!empty($arrulr[1]))$url2=$arrulr[1];

			if(!empty($arrulr[2]))$url3=$arrulr[2];

			if(!empty($arrulr[3]))$goods_down_url=$arrulr[3];

		}



		//淘礼金

		if($_GET['tlj']==1)$set['app_goods_tw_url']=intval($set['app_goods_tljtw_url']);

		if(intval($set['app_goods_tw_url'])==1){

			$tg_url=$url2;

		}

		if(intval($set['app_goods_tw_url'])==2){

			$tg_url=$url3;

		}

		if(intval($set['app_goods_tw_url'])==3){

			$tg_url=$url4;

		}

		if(intval($set['app_goods_tw_url'])==4){

			$tg_url=$goods_down_url;

		}

		$data = array();

		$data['width']=1080;

		$data['height']=1760;



       $data['list'][0] = array(//底部的框
            "url" => INDEX_WEB_URL."Upload/huasuan/goods_share/juxing.png?time=".time(),
            "x" => 51,
            "y" => 1287,
            "width" => 978,
            "height" => 108,
			"type"=>"png"
        );
		$data['list'][1] = array(//二维码
		   "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($tg_url)."&size=20&codeKB=1",
            "x" => 789,
            "y" => 1440,
            "width" => 240,
            "height" => 240,
			"type"=>"png"
        );
		//
		$data['list'][2] = array(//商品图【图片
            "url" => $img,
            "x" =>51,
            "y" => 308,
            "width" => 978,
            "height" => 978,
			"type"=>"jpg"
        );
		$data['list'][3] = array(//商品来源
            "url" => $shop_img,
            "x" =>51,
            "y" => 1456,
            "width" => 112,
            "height" => 50,
			"type"=>"jpg"
        );
		// $data['list'][4] = array(//
  //           "url" => INDEX_WEB_URL."View/index/img/appapi/comm/h2_nr_kuang_gold.png?time=".time(),
  //           "x" =>475 * 2,
  //           "y" => 890 * 2,
  //           "width" => 252 * 2,
  //           "height" => 105 * 2,
		// 	"type"=>"png"
  //       );
        $data['list'][7] = array(//logo
            // "url" => INDEX_WEB_URL."View/index/img/appapi/comm/good_share_logo.png?time=".time(),
            "url" => INDEX_WEB_URL."Upload/huasuan/goods_share/header.png?time=".time(),
            "x" =>0,
            "y" => 0,
            "width" => 1080,
            "height" => 309,
			"type"=>"png"
        );

        $data['list'][8] = array(//会员头像
            "url" => $user['head_img'],
            "x" =>51,
            "y" => 1670,
            "width" => 60,
            "height" => 60,
			"type"=>"png"
        );
        $data['list'][9] = array(//会员头像
            "url" => INDEX_WEB_URL."Upload/huasuan/goods_share/head_bg.png?time=".time(),
            "x" =>51,
            "y" => 1670,
            "width" => 60,
            "height" => 60,
			"type"=>"png"
        );

		for($i=0;$i<2;$i++){
			// $data['text'][$i]=array(
			// 	"size"=>32,
			// 	"x"=>180,
			// 	"y"=>(750+30*$i)*2,
			// 	"val"=>mb_substr($arr['goods_title'],intval(700/42)*$i,intval(700/42),'utf-8'),
			// 	"i"=>$i,
			// );
			// if($i!=0){
			// 	$data['text'][$i]['x']=51;
			// 	if(strlen($data['text'][$i]["val"]) > 15)
			// 	{
			// 		$data['text'][$i]["val"] = mb_substr($data['text'][$i]["val"],0,15,'utf-8') . "...";
			// 	}
			// }

			if($i == 0)
			{
				$data['text'][$i]=array(
					"size"=>32,
					"x"=>180,
					"y"=>(750+30*$i)*2,
					"val"=>mb_substr($arr['goods_title'],0,13,'utf-8'),
					"i"=>$i,
				);
			}
			else
			{
				$add = strlen($arr['goods_title']) > 31 ? '...' : '';
				$data['text'][$i]=array(
					"size"=>32,
					"x"=>51,
					"y"=>(750+30*$i)*2,
					"val"=>mb_substr($arr['goods_title'],13,14,'utf-8') . $add,
					"i"=>$i,
				);
			}
		}
		foreach($data['text'] as $k=>$v){
			if(empty($v['val']))unset($data['text'][$k]);
		}
		$data['text']=array_values($data['text']);
		$ii=end($data['text']);
		$y=80+30*$ii['i'];
		// $data['text'][$ii['i']+1]=array(
		// 	"size"=>34,
		// 	"x"=>140,
		// 	"y"=>$y+128,
		// 	"val"=>"￥".(floatval($arr['goods_price'])),
		// 	"color"=>'red',
		// );

		// $quan=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan_one.png?time=".time();

		// if(floatval($arr['yhq_price'])==0){$quan=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan_one.png?time=".time();}
		// $data['list'][5] = array(
  //           "url" => $quan,
  //           "x" =>33,
  //           "y" => $y+80,
  //           "width" => 89,
  //           "height" => 32,
		// 	"type"=>"png"
  //       );
		$data['text'][$ii['i']+2]=array(	//价格
			"size"=>42,
			"x"=>45,
			"y"=>1640,
			"val"=>"￥". sprintf("%.2f", floatval( $arr['goods_price'] ) ),
			// "color"=>'red',
			"rgb" => "255,32,74",
			"font" => "pingfang_heavy.ttf",
		);
		$data['text'][$ii['i']+5] = array(
			"size"=>24,
			"x"=>strlen(sprintf("%.2f", floatval( $arr['goods_price'] ) )) * 50 + 25,
			"y"=>1640,
			"val"=>"券后价",
			// "color"=>'red',
			"rgb" => "255,32,74",
		);
		$data['text'][$ii['i']+6] = array(
			"size"=>24,
			"x"=>795,
			"y"=>1725,
			"val"=>"长按二维码购买",
			"rgb" => "106,106,106",
		);
		$data['text'][$ii['i']+7] = array(
			"size"=>24,
			"x"=>130,
			"y"=>1710,
			"val"=>"来自".$user['nickname']."分享",
			"rgb" => "106,106,106",
		);

		$arr['yhq_price']=floatval($arr['yhq_price']);
		if(!empty($arr['yhq_price'])){
			$len=strlen(floatval($arr['yhq_price'])."元优惠券");
			$data['text'][$ii['i']+3] = array(
				"size"=>36,
				"x"=>780 - 25 * strlen(floatval($arr['yhq_price'])),
				"y"=>1356,
				"val"=>(floatval($arr['yhq_price']))."元优惠券",
				"color"=>'white',
			);
			$data['text'][$ii['i']+4] = array(
				"size"=>36,
				"x"=>75,
				"y"=>1356,
				"val"=>"限时优惠",
				"color"=>'white',
			);
			// $data['list'][6] = array(
			// 	"url" => INDEX_WEB_URL."View/index/img/appapi/comm/h2_nr_kuang_gold_one.png?time=".time(),
			// 	"x" =>30 * 2,
			// 	"y" => ($y+155) * 2,
			// 	"width" => 225 * 2,
			// 	"height" => 50 * 2,
			// 	"type"=>"png"
			// );
		}

		if($getnew==1){
			fun("pic");
				//fpre($data);
			return pic::getpic($data);
		}
		$data=zfun::arr64_encode($data);
		$url=INDEX_WEB_URL."comm/pic.php?pic_ctrl=getpic&data=".urlencode($data);
		return $url;
	}

}

?>
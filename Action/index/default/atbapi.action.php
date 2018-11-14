<?php
actionfun("appapi/dgappcomm");
actionfun("comm/actfun");
class atbapiAction extends Action{
	public static function sign() {
		$_POST['time'] = intval($_POST['time']);
		if (abs(time() - $_POST['time']) > 3 * 24 * 60 * 3600)zfun::fecho("请求过期");
		$sign = $_POST['sign'];unset($_POST['sign']);
		$syssign = self::getsign($_POST);
		if ($sign != $syssign)zfun::fecho("签名错误");
	}
	public static function getsign($arr = array()) {
		ksort($arr);$str='';
		foreach($arr as $k => $v)$str.=$k.$v;
		return md5('123'.$str.'123');
	}
	public static function fecho($data = NULL, $success = 0, $msg = NULL) {
		$arr=array("msg"=>$msg,"success"=>$success,"data"=>$data,);
		echo json_encode($arr);exit;
	}
	public static function getset(){
		if(!empty($GLOBALS['apiset']))return $GLOBALS['apiset'];
		$GLOBALS['apiset']=zfun::f_getset("tbappkey,tbappsecret");
		return $GLOBALS['apiset'];
	}

	public static function apisign($arr){
		ksort($arr);$str="";
		foreach($arr as $k=>$v)$str.=$k.$v;
		$str=$GLOBALS['apiset']['tbappsecret'].$str.$GLOBALS['apiset']['tbappsecret'];
		return strtoupper(md5($str));
	}
	public static function tbsend($method,$arr){
		self::getset();
		$arr['v']="2.0";
		$arr['method']=$method;
		$arr['format']="json";
		$arr['app_key']=$GLOBALS['apiset']['tbappkey'];
		$arr['sign_method']="md5";
		$arr['timestamp']=date("Y-m-d H:i:s",time()-120);
		$arr['sign']=self::apisign($arr);
		$url="http://gw.api.taobao.com/router/rest?";
		foreach($arr as $k=>$v)$url.="&".$k."=".urlencode($v);
		$url=str_replace("?&","?",$url);
		return zfun::getjson($url);
	}
	//物料商品
	static function wlgoods(){
		actionfun("comm/tbmaterial");
		$sort_arr=array(
			0=>"goods_sales desc",
			2=>"goods_price asc",//价格从低到高
			3=>"goods_price desc",//价格从高到低
			1=>"goods_sales desc",
			4=>"commission desc",
			5=>"commission asc",
			6=>"tg desc",
			7=>"tg asc",
		);
		$arr=array(
			"yhq"=>intval($_POST['yhq']),
			"sort"=>$sort_arr[intval($_POST['sort'])],
		);
		if(!empty($_POST['mall_item'])&&$_POST['mall_item']=='true')$arr['is_tmall']='true';//天猫
		if(!empty($_POST['start_price'])){
			$arr['start_price']=doubleval($_POST['start_price']);
			$arr['end_price']=10000;
		}
		if(($_POST['keyword'])=='')$_POST['keyword']='女装';
		if(!empty($_POST['end_price']))$arr['end_price']=doubleval($_POST['end_price']);
		if(!empty($_POST['commission']))$arr['start_commission']=doubleval($_POST['commission']);
		if(!empty($_POST['cid'])){
			$cate=zfun::f_row("Category","id=".intval($_POST['cid']),"id,category_name");
			if(empty($cate))return array();
			$_POST['keyword']=$cate['category_name'];//覆盖关键词
		}

		$data=tbmaterial::getlist($_POST['keyword'],$arr,$_POST['page_no']);
		foreach($data as $k=>$v){
			unset($data[$k]['small_img'],$data[$k]['dx'],$data[$k]['yx']);
			$data[$k]['getGoodsType']='wuliao';//物料商品
		}
		return $data;
	}

	static function comm_update_goods($arr_gg=array()){
		if(empty($arr_gg))return array();
		$arr_gg=zfun::f_fgoodscommission($arr_gg);
		$shop_type=array("淘宝","淘宝","天猫","京东","京东");
		foreach($arr_gg as $k=>$v){
			$arr_gg[$k]['shop_type']=$shop_type[$v['shop_id']];
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
			if(!empty($v['yhq_price']))$arr_gg[$k]['yhq_span']=$v['yhq_price']."元券";
			$citys=explode(" ",$v['ciry']);
			$provcity=$citys[0];
			if(!empty($citys[1]))$provcity=$citys[1];
			$arr_gg[$k]['provcity']=$provcity;
			unset($arr_gg[$k]['detailurl']);
		}
		appcomm::goodsfeixiang($arr_gg);
		appcomm::goodsfanlioff($arr_gg);
		return $arr_gg;
	}

	//淘宝客 taobao.tbk.item.get
	public function getgoods($return=0,$type=0){
		if($type==0)appcomm::signcheck();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			$GLOBALS['action']->setSessionUser($user['id'],$user['nickname']);
		}
		$set=zfun::f_getset("ggapitype,app_ggyhqtype,checkVersion,gg_goods_s_bili,app_goods_list_check_yhq,app_goods_list_check_yhq");

		foreach($set as $k=>$v)zfun::$set[$k]=$v;
		$_POST['keyword']=preg_replace('/^( |\s)*|( |\s)*$/', '', $_POST['keyword']);
		$GLOBALS['gg_goods_s_bili']=$set['gg_goods_s_bili'];
		//版本一样时没有商品  ios审核用
		if(!empty($_POST['app_V'])&&$set['checkVersion']==$_POST['app_V'])zfun::fecho("getgoods",array(),1);
		//记录搜索关键词 怪不的 第一页慢了一点点
		if(!empty($_POST['keyword'])&&$_POST['p']==1){actionfun("default/search");searchAction::addjl($_POST['keyword']);}

		//物料模式
		if($set['ggapitype']==2){
			$data=self::wlgoods();
			$goods=self::comm_update_goods($data);

			//百里
			//获取当前会员等级比例
			$user_lv = $user['is_sqdl'];
			switch ($user_lv) {
				case '0':
					$hs_bili = 0;
					break;
				case '1':
					$hs_bili = 0.51;
					break;
				case '2':
					$hs_bili = 0.76;
					break;
				case '3':
					$hs_bili = 0.88;
					break;
				default:
					$hs_bili = 0.51;
					break;
			}

			//修改显示比例
			foreach ($goods as $key => &$value) {
				// $value['fcommission'] = $value['fx_commission'];

				$value['fx_commission'] = sprintf("%.2f", $value['goods_price'] * ($value['commission']/100) * $hs_bili);
				$value['fcommission'] = $value['fx_commission'];
				$value['fxz'] = "分享奖：".$value['fx_commission'];
			}

			zfun::fecho("物料商品",$goods,1);
		}



		if(intval($_POST['yhq'])==1&&intval($set['app_ggyhqtype'])==1){/*这是淘宝联盟的*/
			actionfun("appapi/new_Alimama");
			if(!empty($_POST['p']))$_POST['page_no']=$_POST['p'];
			$tmp_al=new new_AlimamaAction();
			$arr_gg=$tmp_al->getgoods(1,1);
			//设置 大淘客优惠券
			$arr_gg=actfun::set_list_coupon($arr_gg);

			$arr_gg=zfun::f_fgoodscommission($arr_gg);
			$shop_type=array("淘宝","淘宝","天猫","京东","京东");
			foreach($arr_gg as $k=>$v){
				$arr_gg[$k]['shop_type']=$shop_type[$v['shop_id']];
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
			}
			appcomm::goodsfeixiang($arr_gg);
			appcomm::goodsfanlioff($arr_gg);
			echo str_replace('"success":1','"success":"1"',json_encode(array("msg"=>"阿里妈妈商品","data"=>$arr_gg,"success"=>1))); exit;
			return;
		}

		//好券清单优惠券
		if($_POST['yhq']==1&&$set['ggapitype']==0){
			actionfun("appapi/alimama");
			if(empty($_POST['page_size']))$_POST['page_size']=10;
			$tmp=new alimamaAction();
			$arr_gg=$tmp->getgoods(1,1);
			return;
		}

		if(!empty($set['ggapitype'])){
			actionfun("appapi/alimama");
			if(empty($_POST['page_size']))$_POST['page_size']=10;
			$tmp=new alimamaAction();
			$arr_gg=$tmp->getgoods(1,1);
			//设置 大淘客优惠券
			$arr_gg=actfun::set_list_coupon($arr_gg);
			$arr_gg=zfun::f_fgoodscommission($arr_gg);
			foreach($arr_gg as $k=>$v){

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
			}
			appcomm::goodsfeixiang($arr_gg);
			appcomm::goodsfanlioff($arr_gg);
			echo str_replace('"success":1','"success":"1"',json_encode(array("msg"=>"阿里妈妈商品","data"=>$arr_gg,"success"=>1))); exit;
			return;
		}



		$data=array();
		$data['q']=trim($_POST['keyword']);
		$sortarr=array(
			1=>"default",
			//2=>"price_desc",//价格从高到低
			//3=>"price_asc",//价格从低到高
			4=>"tk_rate_des",//佣金比率从高到低
			5=>"tk_rate_asc",//佣金比率从低到高
			6=>"tk_total_sales_des",//成交量成高到低
			7=>"tk_total_sales_asc",//成交量从低到高
		);
		if(!empty($_POST['sort'])&&$_POST['sort']!=1){
			$data['sort']=$sortarr[$_POST['sort']];
		}
		if($_POST['start_price']!=''){
			$data['start_price']=$_POST['start_price'];
			if(empty($_POST['end_price']))$data['end_price']=100000;
		}
		if($_POST['end_price']!=''){
			$data['end_price']=$_POST['end_price'];
			if(empty($_POST['start_price']))$data['start_price']=0;
		}

		if($_POST['mall_item']=='true')$data['is_tmall']='true';
		else $data['is_tmall']='false';


		if(!empty($_POST['p']))$_POST['page_no']=$_POST['p'];
		if(!empty($_POST['page_no']))$data['page_no']=$_POST['page_no'];
		if(empty($data['page_no']))$data['page_no']=1;
		$data['page_size']=$_POST['page_size'];
		$data['page_size']=10;

		if(!empty($set['gg_goods_s_bili']))$data['start_tk_rate']=floatval($set['gg_goods_s_bili']);

		$data['start_tk_rate']=$data['start_tk_rate']*100;
		$data['end_tk_rate']=10000;

		$data['fields']="num_iid,title,pict_url,reserve_price,zk_final_price,user_type,item_url,click_url,nick,seller_id,volume";

		$data=self::tbsend("taobao.tbk.item.get",$data);

		$data=$data['tbk_item_get_response']['results']['n_tbk_item'];
		if(!empty($_POST['off']))fpre($data);
		if(empty($data))$data=array();
		$arr=array();

		$shop_type=array("淘宝","天猫");
		$shop_id_arr=array(1,2);

		foreach($data as $k=>$v){
			//$arr[$k]['open_iid']=$v['num_iid'];
			$arr[$k]['goods_title']=$v['title'];
			$arr[$k]['goods_price']=$v['zk_final_price'];
			$arr[$k]['goods_cost_price']=$v['reserve_price'];
			$arr[$k]['goods_sales']=$v['volume'];
			$arr[$k]['shop_type']=$shop_type[$v['user_type']];
			$arr[$k]['shop_id']=$shop_id_arr[$v['user_type']];
			$arr[$k]['goods_img']=$v['pict_url'];
			$arr[$k]['fcommission']='有返利';
			$arr[$k]['fnuo_id']=$v['num_iid'];

			if(empty($v['yhq'])){
				$arr[$k]["yhq"]=0;
				$arr[$k]["yhq_url"]='';
				$arr[$k]["yhq_span"]='';
				$arr[$k]["yhq_price"]='';
			}

			$arr[$k]["actImg"]='';
			$arr[$k]["id"]='';
			$arr[$k]["is_qg"]=0;
			$arr[$k]["actLM"]='';
			$arr[$k]["couponPrice"]='';
			$arr[$k]["qgStr"]='已抢'.intval($v['biz30day']).'件';

			//jj explosion 没有的
			$arr[$k]['qgStr']='';

		}



		$arr=zfun::f_fgoodscommission($arr);
		//设置 大淘客优惠券

		$arr=actfun::set_list_dtk_coupon($arr);
		$shop_arr=array("","淘宝","天猫");
		foreach($arr as $k=>$v){
			$arr[$k]['is_support']=0;
			$arr[$k]['fcommission']="有返利";
			if(filter_check($arr[$k]['fcommission'])=='有返利')$arr[$k]['is_support']=1;
			$arr[$k]['is_mylike']=0;

			$arr[$k]['shop_type']=$shop_arr[$v['shop_id']];

			//unset($arr[$k]['shop_type']);unset($arr[$k]['goods_type']);unset($arr[$k]['fbili']);unset($arr[$k]['zhe']);
			//unset($arr[$k]['commission']);unset($arr[$k]['fcommissionshow']);
			$arr[$k]['goods_img']=$v['goods_img']."_250x250.jpg";
			unset($arr[$k]['detailurl']);
		}
		appcomm::goodsfeixiang($arr);
		appcomm::goodsfanlioff($arr);
		unset($data);
		//fuck
		echo str_replace('"success":1','"success":"1"',zfun::f_json_encode(array("msg"=>"爱淘宝","jd_search_url"=>"","data"=>$arr,"success"=>1))); exit;
		//zfun::fecho("了",$arr,1);
	}



}
//{"keyword":"黑暗之魂","sort":"commissionRate_desc","page_no":1,"page_size":10,"start_price":0,"end_price":20,"mall_item":"false","thisurl":"http://localhost:99/b2c/?act=atbapi&ctrl=getgoods"}
?>
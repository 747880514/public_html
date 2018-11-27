<?php
fun("zfun");
include_once ROOT_PATH.'Action/index/appapi/dgappcomm.php';
actionfun("comm/jingdong");
actionfun("comm/zmCouponJingdong");
actionfun("comm/pinduoduo");actionfun("comm/jtt_goods");
actionfun("default/api");
actionfun("default/atbapi");
actionfun("appapi/baili");//百里

class appGoods02Action extends Action{
	static function getset(){
		$set=zfun::f_getset("gg_goods_s_price,gg_goods_s_bili,dtk_sxtj_data,app_shouye_zhanwai_onoff,jd_indexgoods_type,pdd_indexgoods_type,app_shouye_zhanwai_onoff,zm_web_host,zm_api_pid,app_pddshouye_keyword,app_pddshouye_zhanwai_onoff,app_jdshouye_keyword,app_jdshouye_zhanwai_onoff");
		$thisurl=$set['zm_web_host'];
		if(empty($thisurl))$thisurl='http://www.izhim.net/';
		$tmp=explode("/",$thisurl);
		$set['zm_web_host']=$tmp[0]."//".$tmp[2]."/";
		$data=zfun::arr64_decode($set['dtk_sxtj_data']);
		unset($set['dtk_sxtj_data']);
		foreach($data as $k=>$v)$set[$k]=$v;

		return $set;
	}
	public function getGoods(){
		appcomm::signcheck();
		$set=self::getset();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".$_POST['token']."'");

		}
		$guanggaoset=zfun::f_row("GuanggaoSet","var<>'' and var='".$_POST['show_type_str']."'");
		$json=array();
		if(!empty($guanggaoset))$json=json_decode($guanggaoset['val'],true);
		$GLOBALS['json']=$json;
		$SkipUIIdentifier=filter_check($_POST['SkipUIIdentifier']);
		if($SkipUIIdentifier=='pub_jingdongshangpin')$SkipUIIdentifier='buy_jingdong';
		if($SkipUIIdentifier=='pub_pddshangpin')$SkipUIIdentifier='buy_pinduoduo';
		if($SkipUIIdentifier=='pub_gettaobao')$SkipUIIdentifier='buy_taobao';

		if(empty($SkipUIIdentifier))$SkipUIIdentifier='buy_taobao';

		$goods=array();
		$_POST['keyword']=preg_replace('/^( |\s)*|( |\s)*$/', '', $_POST['keyword']);
		if(!empty($json)){
			if(empty($json['keyword']))$GLOBALS['json']['keyword']=$json['keyword']=$json['search_keyword'];
			$_POST['start_price']=floatval($json['start_price']);
			$_POST['end_price']=floatval($json['end_price']);
			$_POST['yhq']=intval($json['yhq_onoff']);
			$_POST['keyword']=($json['search_keyword']);
		}
		if($SkipUIIdentifier=='buy_pinduoduo'){
			//拼多多首页
			if($_POST['is_index']==1) self::getIndexPddgoods($set);
			if(!empty($_POST['is_ksrk'])){
				$_POST['is_index']=1;self::getSearchPddgoods();
			}
			//拼多多搜索
			if(!empty($_POST['keyword'])&&empty($_POST['is_index'])){
				$_POST['start_price']=$set['gg_goods_s_price'];
				$_POST['commission']=$set['gg_goods_s_bili'];

				self::getSearchPddgoods();
			}
		}
		if($SkipUIIdentifier=='buy_taobao'){
			//淘宝首页
			if($_POST['is_index']==1)self::getIndexTaobao($set);
			if(!empty($_POST['is_ksrk'])){
				$_POST['is_index']=1;

				if(empty($json))self::getSearchTaobao();
				else{
					self::getKsrkTaobao();
				}
			}

			//淘宝搜索
			if(!empty($_POST['keyword'])&&empty($_POST['is_index'])){
				$_POST['start_price']=$set['gg_goods_s_price'];
				$_POST['commission']=$set['gg_goods_s_bili'];
				self::getSearchTaobao();
			}
		}
		if($SkipUIIdentifier=='buy_jingdong'){
			//京东首页
			if($_POST['is_index']==1)self::getIndexJdGoods($set);
			//快速入口
			if(!empty($_POST['is_ksrk'])){
				$_POST['is_index']=1;self::getSearchJdGoods();
			}
			//京东搜索
			if(!empty($_POST['keyword'])&&empty($_POST['is_index'])&&$_POST['yhq']==0){
				$_POST['start_price']=$set['gg_goods_s_price'];
				$_POST['commission']=$set['gg_goods_s_bili'];
				self::getSearchJdGoods();
			}
			//京东优惠券搜索
			if(!empty($_POST['keyword'])&&empty($_POST['is_index'])&&$_POST['yhq']==1){
				$_POST['start_price']=$set['gg_goods_s_price'];
				$_POST['commission']=$set['gg_goods_s_bili'];
				self::getSearchJdCouponGoods();
			}
		}
		zfun::fecho("商品",$goods,1);
	}
	//搜索淘宝商品
	public static function getSearchTaobao(){
		$sorts=array(
			"zonghe"=>0,//
			"goods_price_asc"=>2,//价格从低到高
			"goods_price_desc"=>3,//价格从高到低
			"commission_desc"=>4,//佣金从高到低
			"commission_asc"=>5,//佣金从低到高
			"goods_sales_desc"=>0,//	 销量从高到低
			"tg_desc"=>6,//	 人气从高到低
		);
		$_POST['sort']=$sorts[$_POST['sort']];
		$_POST['page_no']=$_POST['p'];
		if($_POST['is_tm']==1)$_POST['mall_item']='true';
		atbapiAction::getgoods(0,1);
	}

	//首页淘宝商品
	public static function getIndexTaobao(){
		$set=self::getset();
		$_POST['page_no']=$_POST['p'];

		if($set['app_shouye_zhanwai_onoff']==3){
			$sort_arr=array(
				"zonghe"=>0,
				"commission_desc"=>4,
				"tg_desc"=>6,
			);
			$_POST["sort"]=$sort_arr[($_POST['sort'])];
		}else if($set['app_shouye_zhanwai_onoff']==2){
			$sort_arr=array(
				"tg_desc"=>2,
				"commission_desc"=>5,
				"zonghe"=>0,
			);
			$_POST["sort"]=$sort_arr[($_POST['sort'])];
		}

		apiAction::goods_sx_new();//包含物料模式
		$data_str=apiAction::goods_sx_str();//筛选条件
		$arr=$data_str['arr'];
		$str2=$data_str['str'];
		//淘宝联盟路线
		if($set['app_shouye_zhanwai_onoff']==1)self::tblm($arr);

		$where = "id>0 and shop_id IN(1,2) AND start_time<" . time() . " AND end_time>" . time();
		if (!empty($_POST['cid'])) {
			$categoryModel = $GLOBALS['action']->getDatabase("Category");
			$cids = $categoryModel -> getCateId($_POST['cid']);
			$c = explode(",", $cids);
			if (is_array($c) && count($c) > 1) {
				$where .= " AND cate_id in ($cids) ";
			} else {
				$where .= " AND cate_id=" . $_POST['cid'] ;
			}
		}
		$where.=$str2;//当填了价格筛选之类的
		if(!empty($_POST['is_index'])&&$_POST['is_index']==1)apiAction::getwhereset($where,$sort,"app_dg_goods_indexset");
		switch($_POST['sort'].''){
			case "zonghe":
				$sort="cjtime desc";
				break;
			case "tg_desc":
				$sort="goods_sales desc";
				break;
			case "commission_desc":
				$sort="commission desc";

				break;
		}
		if(empty($sort))$sort='tg_sort desc,goods_sales desc';
		else $sort="tg_sort desc,".$sort;
		$num=20;
		$goods = appcomm::f_goods("Goods", $where, 'id,fnuo_id,goods_sales,goods_type,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,shop_id,highcommission_url,highcommission_wap_url,yhq_price,yhq_url,stock,yhq,yhq_span', $sort, NULL, $num);
		$goods=self::comm_update_goods($goods);
		zfun::fecho("首页淘宝商品",$goods,1);
	}
	//快速入口淘宝商品
	public static function getKsrkTaobao(){

		$set=self::getset();
		$_POST['page_no']=$_POST['p'];

		if($GLOBALS['json']['goods_pd_onoff']==3){
			$sort_arr=array(
				"zonghe"=>0,
				"commission_desc"=>4,
				"tg_desc"=>6,
			);
			$_POST["sort"]=$sort_arr[($_POST['sort'])];
		}else if($GLOBALS['json']['goods_pd_onoff']==2){
			$sort_arr=array(
				"tg_desc"=>2,
				"commission_desc"=>5,
				"zonghe"=>0,
			);
			$_POST["sort"]=$sort_arr[($_POST['sort'])];
		}

		apiAction::goods_sx_new();//包含物料模式
		$data_str=apiAction::goods_sx_str();//筛选条件
		//$arr=$data_str['arr'];
		//$str2=$data_str['str'];


		$where = "id>0 and shop_id IN(1,2) AND start_time<" . time() . " AND end_time>" . time();
		if(!empty($GLOBALS['json']['start_price']))$where.=" and goods_price>".$GLOBALS['json']['start_price'];
		if(!empty($GLOBALS['json']['end_price']))$where.=" and goods_price<".$GLOBALS['json']['end_price'];
		if(!empty($GLOBALS['json']['commission']))$where.=" and commission>".$GLOBALS['json']['commission'];
		if(!empty($GLOBALS['json']['goods_sales']))$where.=" and goods_sales>".$GLOBALS['json']['goods_sales'];
		if(!empty($GLOBALS['json']['yhq']))$where.=" and yhq=1";

		if (!empty($_POST['cid'])) {
			$categoryModel = $GLOBALS['action']->getDatabase("Category");
			$cids = $categoryModel -> getCateId($_POST['cid']);
			$c = explode(",", $cids);
			if (is_array($c) && count($c) > 1) {
				$where .= " AND cate_id in ($cids) ";
			} else {
				$where .= " AND cate_id=" . $_POST['cid'] ;
			}
		}
		$where.=$str2;//当填了价格筛选之类的
		if(!empty($_POST['is_index'])&&$_POST['is_index']==1)apiAction::getwhereset($where,$sort,"app_dg_goods_indexset");
		switch($_POST['sort'].''){
			case "zonghe":
				$sort="cjtime desc";
				break;
			case "tg_desc":
				$sort="goods_sales desc";
				break;
			case "commission_desc":
				$sort="commission desc";

				break;
		}
		if(empty($sort))$sort='tg_sort desc,goods_sales desc';
		else $sort="tg_sort desc,".$sort;
		$num=20;
		$goods = appcomm::f_goods("Goods", $where, 'id,fnuo_id,goods_sales,goods_type,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,shop_id,highcommission_url,highcommission_wap_url,yhq_price,yhq_url,stock,yhq,yhq_span', $sort, NULL, $num);
		$goods=self::comm_update_goods($goods);
		zfun::fecho("首页淘宝商品",$goods,1);
	}

	//首页拼多多
	public static function getIndexPddgoods($set,$t=0){
		if(empty($_POST['cid'])){
			$_POST['keyword']=$set['app_pddshouye_keyword'];
			if(empty($_POST['keyword']))$_POST['keyword']='女装';
		}
		if(intval($set['pdd_indexgoods_type'])==1)$_POST['yhq']=1;

		$GLOBALS['start_price']=$set['app_pddshouye_minprice_sx'];
		$GLOBALS['end_price']=$set['app_pddshouye_maxprice_sx'];
		$GLOBALS['start_commission_rate']=$set['app_pddshouye_yj_sx'];
		$GLOBALS['start_sales']=$set['app_pddshouye_xl_sx'];
		$GLOBALS['end_commission_rate']='';
		$GLOBALS['end_sales']='';
		$data=self::pddgoods(1);
		if($t==1)return $data;
		$goods=self::comm_update_goods($data);

		//百里
		$goods = baili::hs_commission($goods);

		zfun::fecho("首页拼多多商品",$goods,1);
	}
	//搜索页的拼多多
	public static function getSearchPddgoods($t=0){
		$GLOBALS['start_price']=$_POST['start_price'];
		$GLOBALS['end_price']=$_POST['end_price'];
		$GLOBALS['start_commission_rate']=$_POST['commission'];
		$GLOBALS['start_sales']=$_POST['goods_sales'];
		$GLOBALS['end_commission_rate']='';
		$GLOBALS['end_sales']='';
		$data=self::pddgoods();
		if($t==1)return $data;
		$goods=self::comm_update_goods($data);

		//百里
		$goods = baili::hs_commission($goods);

		zfun::fecho("首页拼多多商品",$goods,1);
	}
	//拼多多商品
	static function pddgoods($is_index=0){

		$sort_arr=array(
			"zonghe"=>"",//默认
			"goods_price_asc"=>"goods_price asc",//价格从低到高
			"goods_price_desc"=>"goods_price desc",//价格从高到低
			"commission_desc"=>"commission desc",//佣金从高到低
			"commission_asc"=>"commission asc",//佣金从低到高
			"goods_sales_desc"=>"goods_sales desc",//	 销量从高到低
			"goods_sales_asc"=>"goods_sales asc",//	 销量从低到高
		);
		$arr=array(
			"yhq"=>intval($_POST['yhq']),
			"cid"=>($_POST['cid']),
			"sort"=>$sort_arr[($_POST['sort'])],
		);
		$arr['start_price']=$GLOBALS['start_price'];
		$arr['end_price']=$GLOBALS['end_price'];
		$arr['start_commission_rate']=$GLOBALS['start_commission_rate'];
		$arr['end_commission_rate']=$GLOBALS['end_commission_rate'];
		$arr['start_sales']=$GLOBALS['start_sales'];
		$arr['end_sales']=$GLOBALS['end_sales'];
		if(empty($_POST['keyword'])&&!empty($_POST['is_ksrk']))$_POST['keyword']='女装';
		if(!empty($arr['cid'])&&!empty($_POST['is_ksrk']))$_POST['keyword']='';


		$data=pinduoduo::getlist($_POST['keyword'],$arr,$_POST['p']);
		foreach($data as $k=>$v){

			$data[$k]['getGoodsType']='buy_pinduoduo';//
		}
		return $data;
	}
	//首页京东
	public static function getIndexJdgoods($set,$t=0){
		if(empty($_POST['cid'])){
			$_POST['keyword']=$set['app_jdshouye_keyword'];
			if(empty($_POST['keyword']))$_POST['keyword']='手机';
		}
		$_POST['start_price']=$set['app_jdshouye_minprice_sx'];
		$_POST['end_price']=$set['app_jdshouye_maxprice_sx'];
		if($set['app_jdshouye_zhanwai_onoff']==6)$data=self::getJttGoods();//京推推
		else if(intval($set['jd_indexgoods_type'])==1)$data=self::jdCouponGoods();
		else $data=self::jdgoods();

		if($t==1)return $data;
		$goods=self::comm_update_goods($data);

		//百里
		$goods = baili::hs_commission($goods);

		zfun::fecho("首页京东商品",$goods,1);
	}
	//搜索页的京东
	public static function getSearchJdgoods($t=0){

		if($_POST['is_ksrk']==1&&$GLOBALS['json']){

			if($GLOBALS['json']['jdgoods_pd_onoff']==6)$data=self::getJttGoods();//京推推
			else if(intval($GLOBALS['json']['yhq_onoff'])==1)$data=self::jdCouponGoods();
			else $data=self::jdgoods();
		}else $data=self::jdgoods();
		if($t==1)return $data;
		$goods=self::comm_update_goods($data);

		//百里
		$goods = baili::hs_commission($goods);

		zfun::fecho("首页京东商品",$goods,1);
	}
	//京推推商品
	static function getJttGoods(){
		$sort_arr=array(
			"zonghe"=>"",//
			"commission_desc"=>"commission desc",//佣金从高到低
			"self_desc"=>"self desc",//销量从高到低
			"qhj_asc"=>"qhj asc",//精选
		);
		$arr=array(
			"sort"=>$sort_arr[($_POST['sort'])],
			"cid"=>($_POST['cid']),
		);
		if($arr['cid']==0)$arr['cid']='';
		$data=jtt_goods::getlist('',$arr,$_POST['p']);
		$GLOBALS['is_jtt']=1;
		foreach($data as $k=>$v){
			$arr=jingdong::id($v['fnuo_id']);
			$arr['commission']=floatval($arr['commission']);
			if(!empty($arr['commission']))$data[$k]['commission']=$arr['commission'];
			$data[$k]['goods_sales']=$arr['goods_sales'];
			$data[$k]['isJdSale']=$arr['isJdSale'];
			$data[$k]['getGoodsType']='buy_jingdong';//
			$data[$k]['goods_title']=str_replace(array(" ","\n","\r\n","/\s/"),"",$v['goods_title']);
			//$data[$k]['yhq']=0;
			//$data[$k]['yhq_price']=0;
			//$data[$k]['yhq_url']='';
			$data[$k]['goods_type']=0;
			$data[$k]['goods_img']=str_replace(array("_500x500.jpg"),"",$v['goods_img']);
		}


		return $data;
	}
	//京东商品
	static function jdgoods(){
		$sort_arr=array(
			"zonghe"=>"default",//
			"commission_desc"=>"commission desc",//佣金从高到低
			"goods_sales_desc"=>"goods_sales desc",//销量从高到低
			"new_desc"=>"new desc",//新品
		);
		$arr=array(
			"yhq"=>intval($_POST['yhq']),
			"isJdSale"=>intval($_POST['isJdSale']),
			"sort"=>$sort_arr[($_POST['sort'])],
			"cid"=>($_POST['cid']),
			"start_price"=>floatval($_POST['start_price']),
			"end_price"=>floatval($_POST['end_price']),
		);
		if(empty($_POST['keyword'])&&!empty($_POST['is_ksrk']))$_POST['keyword']='女装';
		if(!empty($arr['cid'])&&!empty($_POST['is_ksrk']))$_POST['keyword']='';
		$data=jingdong::getlist($_POST['keyword'],$arr,$_POST['p']);
		foreach($data as $k=>$v){
			$data[$k]['getGoodsType']='buy_jingdong';//
		}
		return $data;
	}
	//搜索页的优惠券京东
	public static function getSearchJdCouponGoods($t=0){

		$data=self::jdCouponGoods();
		if($t==1)return $data;
		$goods=self::comm_update_goods($data);

		//百里
		$goods = baili::hs_commission($goods);

		zfun::fecho("首页京东商品",$goods,1);
	}
	//搜索页的优惠券京东
	static function jdCouponGoods(){
		$sort_arr=array(
			"zonghe"=>"commission_rate desc",//
			"commission_desc"=>"commission_rate desc",//佣金从高到低
			"goods_sales_desc"=>"goods_sales desc",//销量从高到低
			"new_desc"=>"update_time desc",//新品
		);
		$arr=array(
			"yhq"=>1,
			"isJdSale"=>intval($_POST['isJdSale']),
			"sort"=>$sort_arr[($_POST['sort'])],
			"cid"=>($_POST['cid']),
			"start_price"=>floatval($_POST['start_price']),
			"end_price"=>floatval($_POST['end_price']),
		);
		if(empty($_POST['keyword'])&&!empty($_POST['is_ksrk']))$_POST['keyword']='女装';
		if(!empty($arr['cid'])&&!empty($_POST['is_ksrk']))$_POST['keyword']='';
		$data=zmCouponJingdong::getlist($_POST['keyword'],$arr,$_POST['p']);
		foreach($data as $k=>$v){
			$data[$k]['getGoodsType']='buy_jingdong';//
		}
		return $data;
	}
	//淘宝联盟
	public static function tblm($arr=array()){
		$set=zfun::f_getset("app_ggyhqtype");
		actionfun("appapi/alimama");
		$keyword="";
		if(!empty($_POST['keyword']))$keyword=$_POST['keyword'];
		if(!empty($_POST['cid'])){
			$tmp=zfun::f_row("Category","id=".intval($_POST['cid']." and category_name<>''"),"category_name");
			if(empty($tmp)||empty($tmp['category_name']))zfun::fecho("没有分类名",array(),1);
			$tmp['category_name']=str_replace("、"," ",$tmp['category_name']);
			if(empty($keyword))$keyword.=$tmp['category_name'];
			else $keyword.=" ".$tmp['category_name'];
		}
		//设置为超高返
		//$_POST['dpyhq']=1;
		//$_POST['yhq']=1;//jj explosion 兼容新接口
		$_POST['keyword']=$keyword;
		$_POST['size']=10;
		if(empty($_POST['price1']))$_POST['price1']=$arr['min_price'];
		if(empty($_POST['price2']))$_POST['price2']=$arr['max_price'];
		if(!empty($arr['commission']))$GLOBALS['gg_goods_s_bili']=$arr['commission'];
		if(!empty($arr['goods_sales']))$GLOBALS['goods_sales']=$arr['goods_sales'];
		if(!empty($_POST['price1']))$_POST['start_price']=$_POST['price1'];
		if(!empty($_POST['price2']))$_POST['end_price']=$_POST['price2'];
		if(!empty($_POST['sort'])&&$_POST['sort']==5)$_POST['sortType']=9;

		/*这是淘宝联盟的*/
		if(intval($set['app_ggyhqtype'])==1){
			actionfun("appapi/new_Alimama");
			if(!empty($_POST['p']))$_POST['page_no']=$_POST['p'];
			$tmp_al=new new_AlimamaAction();
			$goods=$tmp_al->getgoods(1,1);
			//unset($_POST['page_no']);
		}else{
			$GLOBALS['off_yhq']='on';
			$goods=alimamaAction::getgoods(1,1);
		}
		//检测商品列表优惠券
		$goods=actfun::set_list_coupon($goods);
		foreach($goods as $k=>$v){
			$goods[$k]['stock']=$set['mr_stock'];
			$goods[$k]['id']=$v['fnuo_id'];
			$goods[$k]['open_iid']=$v['fnuo_id'];
			if(!empty($v['yhq_price']))$goods[$k]['yhq_span']=$v['yhq_price']."元";
			$goods[$k]['getGoodsType']="taobaolianmeng";
		}
		$goods=self::comm_update_goods($goods);
		zfun::fecho("首页淘宝联盟商品",$goods,1);
	}
	static function comm_update_goods($arr_gg=array()){
		$set=self::getset();

		if(empty($arr_gg))return array();
		if(!empty($_GET['fuck']))fpre(reset($arr_gg));

		$arr_gg=zfun::f_fgoodscommission($arr_gg);

		if(!empty($_GET['fuck']))fpre(reset($arr_gg));
		$shop_type=array("淘宝","淘宝","天猫","京东","京东");
		foreach($arr_gg as $k=>$v){
			if($v['shop_id']==4){
				$arr_gg[$k]['shop_id']=3;
				$arr_gg[$k]['fnuo_url']=self::getUrl("gotojingdong","index",array("gid"=>$v['fnuo_id']),"appapi");
				if(!empty($GLOBALS['is_jtt']))$arr_gg[$k]['fnuo_url']=INDEX_WEB_URL."?mod=appapi&act=gotojingdong&gid=".$v['fnuo_id']."&yhq_url=".urlencode($v['yhq_url']);
			}
			$arr_gg[$k]['shop_type']=$shop_type[$v['shop_id']];
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


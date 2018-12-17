<?php
actionfun("appapi/dgappcomm");
class rebate_DGAction extends Action{

	public function dmshop($shops=array()){
		$uid=intval($this->getUserId());
		foreach($shops as $k=>$v){
			if(strstr($v['scdg_dizhi'],"euid")){
				$dizhi=$v['scdg_dizhi'];
				$dizhi=str_replace("&euid=","&shops=",$dizhi);
				$dizhi=str_replace("&t=","&euid=".$uid."&t=",$dizhi);
				$str1=explode("&shops=",$dizhi);
				$str2=explode("&euid=",$dizhi);
				$dizhi=$str1[0]."&euid=".$str2[1];
				$shops[$k]['scdg_dizhi']=$dizhi;
			}
		}
		return $shops;
	}
	public function gologin(){
		$uid=intval(self::getUserId());
		self::assign("uid",$uid);
		self::assign("isjflogin",intval(self::getSetting('isjflogin')));
	}

	public static function get_bc_detail($fnuo_id,$shop_id){
		if(empty($fnuo_id))zfun::fecho("rebate_FG get_bc_detail val empry");
		fun("bcapi");
		$arr=array(
			"item_id"=>$fnuo_id,
			"fields"=>"item,price,delivery,skuBase,skuCore,trade,feature,props,debug",
		);
		$tmp=bcapi::tbsend("taobao.item.detail.get",$arr,"item_detail_get_response,data");
		//exit;
		//$tmp=json_decode($tmp,true);
		//因为不能直接解析 所以唯有这样
		//fpre($tmp);
		$url=self::getinstr($tmp,'"taobaoDescUrl":"','"');
		//参数
		$canshu=array();
		if(strstr($tmp,'基本信息":[')==true){
			$canshu="[".self::getinstr($tmp,'基本信息":[',']')."]";
			$canshu=json_decode($canshu,true);
			$arr=array();
			foreach($canshu as $k=>$v){
				$arr[$k]=array();
				foreach($v as $k1=>$v1){
					$arr[$k]['name']=$k1;
					$arr[$k]['val']=$v1;
				}

			}
		}

		//图片
		$img=json_decode('['.self::getinstr($tmp,'"images":[',']').']',true);
		foreach($img as $k=>$v){
			$img[$k]=$v."_400x400.jpg";
		}
		$data=array();
		$data['url']=$url;
		$data['img']=$img;
		$data['canshu']=$arr;
		/*
		$url='https://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7Bitem_num_id%3A"'.$fnuo_id.'"%7D&type=jsonp&dataType=jsonp';
		$tmp=curl_get('https://hws.m.taobao.com/cache/mtop.wdetail.getItemDescx/4.1/?data=%7Bitem_num_id%3A"'.$fnuo_id.'"%7D&type=jsonp&dataType=jsonp');

		if(strstr($tmp,"接口调用成功")==false){return $data;
			zfun::fecho("error rebate_DG ".__LINE__);
		}
		$tmp="[".self::getinstr($tmp,'"images":[',']').']';
		$detail_img=json_decode($tmp,true);
		foreach($detail_img as $k=>$v){
			$detail_img[$k]=$v."_500x500.jpg";
		}*/
		actionfun("comm/tb_web_api");
		$detail_img=tb_web_api::pc_html_detail_img($fnuo_id,$shop_id);
		$data['detail_img']=$detail_img;
		//fpre($detail_img);exit;
		return $data;

	}
	public static function getinstr($str='',$str1='',$str2=''){
		$tmp=explode($str1,$str);
		if(empty($tmp[1]))$tmp[1]='';
		$tmp=explode($str2,$tmp[1]);
		return $tmp[0];
	}

      // 商品详情
	public function rebate_detail(){//zheli
        $uid=self::getUserId();
		if(empty($_GET['id'])&&empty($_GET['fnuo_id']))zfun::alert("该商品已下架");
		if(empty($_GET['code'])){
			$url="http://".HTTP_HOST."".$_SERVER['REQUEST_URI'];
			//兼容伪静态
			if(strstr($url,"?"))$url.="&code=run";
			else {
				$_GET['code']='run';
				$url=self::getUrl("rebate_DG","rebate_detail",$_GET,"wap");
			}
			$url=base64_encode($url);

			//百里.下载APP
			$tgid = ($_GET['tgid']);
			$set=zfun::f_getset("share_host");
			$downappurl = "http://".$set['share_host']."/?mod=appapi&act=down&ctrl=get_unionid&tgid=".$tgid;
			self::assign("downappurl",$downappurl);

			self::assign("url",$url);
			self::assign("title","详情");
			self::display("rebate_DG","dl_get");
			self::play();
		}
        $fnuo_id=$gid=intval($_GET['id']);
        //self::setseo("优惠券商品详情");

        //百里.下载APP
		$tgid = ($_GET['tgid']);
		$set=zfun::f_getset("share_host");
		$downappurl = "http://".$set['share_host']."/?mod=appapi&act=down&ctrl=get_unionid&tgid=".$tgid;
		self::assign("downappurl",$downappurl);
		self::assign("tgid", $tgid);


		$tgid = ($_GET['tgid']);
		$tguser=zfun::f_row("User","tg_code='".$tgid."'");
		if(empty($tguser)){
			$Decodekey = $this -> getApp('Tgidkey');
			$tgid = $Decodekey -> Decodekey($tgid);
			$tguser=zfun::f_row("User","id='$tgid'");

		}else $tgid = $tguser['id'];

		if(!empty($_GET['show_tgid']))fpre(array("show_tgid"=>$tgid));

		$tg_pid=$tguser['tg_pid'];

		$str="taobaopid,app_goods_tw_url,app_goods_tw_kouling,app_fanli_onoff,app_fanli_off_str";
		$str.=",CSharp_share_onoff";//助手分享开关
		$str.=",ggapitype,share_get_str7";
		$str.=",tb_gy_api_onoff,tb_wl_gy_api_onoff";//高佣接口开关
		$str.=",tlj_fenyong_onoff";//掏礼金是否参与分佣

		$set=zfun::f_getset($str);

		$this->assign("share_get_str7",$set['share_get_str7']);
		$set['CSharp_share_onoff']=intval($set['CSharp_share_onoff']);

		//fpre($set);exit;
		if(!empty($tg_pid)){
			zfun::$set['pid']=$tg_pid;

			$tmp=explode("_",$set['taobaopid']);
			$GLOBALS['taobaopid']=$set['taobaopid']=$tg_pid=$tmp[0]."_".$tmp[1]."_".$tmp[2]."_".$tg_pid;
		}

		if(!empty($_GET['id'])){
			$goods=zfun::f_row("Goods","id=$gid","id,yhq,fnuo_id,goods_type,commission,goods_img,goods_title,shop_id,goods_price,yhq_price,yhq_url,highcommission_url,highcommission,goods_detail,goods_desc,tlj_url");
			if(!empty($goods['goods_desc']))$goods['is_desc']=1;
			else $goods['is_desc']=0;
		}

        if(!empty($_GET['fnuo_id'])){
			$fnuo_id=$_GET['fnuo_id'];
			/*改*/
			if(strstr($fnuo_id,"dtk")==false){
				if($set['ggapitype']!=2){//不是物料 用联盟接口
				actionfun("default/alimama");
					$data=$v=alimamaAction::getcommission($fnuo_id);
				}
				else{//物料模式
					actionfun("comm/tbmaterial");
					$wl_goods=$v=tbmaterial::id($fnuo_id);
				}
			}

			$shop_arr=array(0=>1,1=>2);
			$shop_str=array(0=>"淘宝",1=>"天猫");

			if($set['ggapitype']!=2){
				$arr=array(
					"fnuo_id"=>$v['auctionId'],
					"goods_title"=>str_replace(array("<span class=H>","</span>"),"",$v['title']),
					"goods_price"=>floatval($v['zkPrice']),
					"goods_cost_price"=>floatval($v['reservePrice']),
					"goods_img"=>"https:".str_replace(array("https:","http:"),"",$v['pictUrl'])."_400x400.jpg",
					"goods_sales"=>intval($v['biz30day']),
					"commission"=>floatval($v['tkRate']),
					"shop_type"=>$shop_str[$v['userType']],
					"shop_id"=>$shop_arr[$v['userType']],
					"yhq"=>0,
					"yhq_price"=>zfun::dian(0),
					"yhq_span"=>'',
				);
				$arr['yhq_url']='';
				if((empty($v['couponInfo'])||$v['couponInfo']=="无")==false){
					$arr['yhq']=1;
					$arr['yhq_price']=zfun::dian($v['couponAmount']);
					$arr['yhq_span']=$v['couponInfo'];
				}
			}
			else{//物料模式
				$arr=array(
					"fnuo_id"=>$v['fnuo_id'],
					"goods_title"=>$v['goods_title'],
					"goods_price"=>$v['goods_price'],
					"goods_cost_price"=>$v['goods_cost_price'],
					"goods_img"=>str_replace("_250x250.jpg","",$v['goods_img'])."_400x400.jpg",
					"goods_sales"=>$v['goods_sales'],
					"commission"=>$v['commission'],
					"shop_id"=>$v['shop_id'],
					"yhq"=>0,
					"yhq_price"=>'0.00',
					"yhq_span"=>'',
				);
				$shop_name_arr=array(1=>'淘宝',2=>'天猫',);
				$arr['shop_type']=$shop_name_arr[$v['shop_id']];

			}
			actionfun("default/gototaobao");
			$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$arr['goods_title'],"fnuo_id"=>$arr['fnuo_id']),1);
			//高佣转链
			if($set['tb_gy_api_onoff']==1){
				$set['CSharp_share_onoff']=0;//关闭助手转链
				actionfun("comm/tb_gy_api");
				$tmp_yhq_url=tb_gy_api::get_coupon($fnuo_id);//调用高佣 链接
				//fpre($tmp_yhq_url);
			}
			//物料高佣转链
			if($set['tb_wl_gy_api_onoff']==1){
				$set['CSharp_share_onoff']=0;//关闭助手转链
				actionfun("comm/tb_wl_gy_api");
				$tmp_yhq_url=tb_wl_gy_api::get($fnuo_id);//调用物料高佣 链接
			}
			if(!empty($tmp_yhq_url)){
				$arr['yhq_url']=$tmp_yhq_url;
			}
			elseif(!empty($wl_goods['yhq_url'])){
				$arr['yhq_url']=$wl_goods['yhq_url'];
				$arr['yhq_price']=$wl_goods['yhq_price'];
			}
			$arr['noyhq_price']=$arr['goods_price'];
			if(!empty($GLOBALS['yhq_price']))$arr['yhq_price']=$GLOBALS['yhq_price'];
			if(!empty($GLOBALS['yhq_span']))$arr['yhq_span']=$GLOBALS['yhq_span'];
			if(!empty($GLOBALS['goods_cost_price']))$arr['goods_cost_price']=$GLOBALS['goods_cost_price'];
			if(!empty($GLOBALS['goods_price']))$arr['goods_price']=$GLOBALS['goods_price'];

			if(!empty($arr['yhq_price'])){
				$arr['yhq']=1;
			}
			$ku_goods=zfun::f_row("Goods","fnuo_id='".$fnuo_id."'");

			if(!empty($ku_goods)&&empty($arr['yhq_url'])&&empty($GLOBALS['taobaopid'])){
				if(!empty($ku_goods['highcommission_url']))$arr['yhq_url']=$garr['highcommission_url']=$ku_goods['highcommission_url'];
				if(!empty($ku_goods['yhq_url']))$arr['yhq_url']=$garr['yhq_url']=$ku_goods['yhq_url'];
				if(!empty($ku_goods['buy_url']))$arr['yhq_url']=$garr['buy_url']=$ku_goods['buy_url'];
			}
			//$arr['yhq_url']=$data['yhq_url'];
			$goods=$arr;

		}

 		//self::setseo($goods['goods_title']);
      //  if(empty($goods))zfun::alert("该商品已下架");
        if($_GET['data']){
           $goods = zfun::arr64_decode($_GET['data']);
        }

        $goods['tkl'] = '';
        $goods['qhj'] = $goods['goods_price'] - $goods['yhq_price'];
        $goods['goods_img'] = str_replace(array(
            "_290x290.jpg",
			"_290x290.jpg",
			"_400x400.jpg",
        ) , "", $goods['goods_img']) . "_400x400.jpg";
        $tdj = '';

		$goods['is_wx']=1;

		//掏礼金 jj explosion
		$tmp=zfun::f_row("Goods","fnuo_id='{$fnuo_id}'");
		if(!empty($tmp)&&!empty($tmp['tlj_url']))$goods['tlj_url']=$tmp['tlj_url'];

		include_once ROOT_PATH."Action/index/appapi/tkl.action.php";

		if($set['CSharp_share_onoff']===0)$goods['tkl']=tkl::gettkl($goods);

        $_POST['tdj']=1;
        $goods=zfun::f_fgoodscommission(array($goods));
		appcomm::goodsfanlioff($goods);
		$goods=reset($goods);


			//life is a shipwreck
		if(!empty($tg_pid)){
			$goods['fnuo_url'].="&pid=".$GLOBALS['taobaopid'];

		}else{
			$goods['fnuo_url'].="&pid=".$set['taobaopid'];
		}
		$goods['update']='浏览器购买';
		$goods['kltype']='淘宝';

		$settt=zfun::f_getset("tg_durl");

        $data = array();
        $data['tdj'] = $tdj;
        $data['button'] = '<a class="goto_buy" isconvert=1 data-itemid="' . $goods['fnuo_id'] . '" buy="on">马上抢</a>';
        $data['fnuo_id'] = $goods["fnuo_id"];
		$service=zfun::f_getset("qqservice");

        self::assign("kdata", json_encode($data));
		self::assign("service", $service['qqservice']);


		//插入或更新足迹
     	if(!empty($uid)){
            $fgoods= zfun::f_count("FootMark","goodsid = '$fnuo_id' and uid = '$uid'");
            if($fgoods == '0') {
                $fdata=array();
                $fdata['goodsid']=$goods['fnuo_id'];
                $fdata['starttime']=time();
                $fdata['endtime']=time();
                $fdata['uid']=intval($uid);
                zfun::f_insert("FootMark",$fdata);
            }else{
                $fdata['endtime']=time();
                zfun::f_update("FootMark","goodsid =  '$fnuo_id' and uid = '$uid'",$fdata);
            }
        }
        $share['UcenterShareTitle']=$this->getSetting("UcenterShareTitle");
        $share['UcenterShareText']=$this->getSetting("UcenterShareText");
        $uid=intval($uid);
        $tgidkey = $this -> getApp('Tgidkey');
        $uid1 = $tgidkey -> addkey($uid);
        $tg_url = ($this -> getUrl('invite_friend_wap', 'new_packet', array('tgid' => $uid1),'new_share_wap'));
   		$title="商品详情";
		self::assign("title", $title);

		//jj explosion
		if($set['CSharp_share_onoff']===1){
			$goods['fnuo_url']='';
			self::assign("tx_token",md5(time()."qwe了".$uid));
			self::assign("tx_fnuo_id",$_GET['fnuo_id']);
			$tdj_data=zfun::f_getset("tdj_web_url");
			$tdj_web_url=$tdj_data['tdj_web_url'];
			$tmp=explode("/",$tdj_web_url);
			$tdj_web_url=$tmp[0]."//".$tmp[2]."/";

			self::assign("tx_host",md5(str_replace("www.","",$tdj_web_url)."我要加密了"));
			self::assign("tx_pid",$GLOBALS['taobaopid']);

		}
		self::assign("set",$set);
        self::assign("goods", $goods);
        self::assign("data", $share);
        self::assign("tgurl",$tg_url);
		self::assign("app_goods_tw_url",$set['app_goods_tw_url']);
		//zheli boom
		$detail=self::get_bc_detail($fnuo_id,$goods['shop_id']);
		//fpre($detail);
		$iswx=self::iswx();
		//echo $_SERVER['HTTP_USER_AGENT'];
   		//if (preg_match('/QQBrowser/i',$_SERVER['HTTP_USER_AGENT' ]))$iswx=1;
		//if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE)$iswx=0;
		self::assign("iswx",$iswx);
		self::assign("ifurl",$detail['url']);
		self::assign("img",$detail['img']);
		self::assign("canshu",$detail['canshu']);
		self::assign("detail_img",$detail['detail_img']);
		//jj explosion
		self::assign("uid",intval(self::getUserId()));
		zfun::isoff($set);
        $this ->display("rebate_DG","rebate_detail","wap");
		//$this -> runplay("wap","comm","top");

		$this ->play();
	}

	//代理请求
	function dl_get(){
		if(empty($_GET['code']))zfun::fecho("error");
		actionfun("comm/actfun");
		$cookie_name="dl_get";
		$cookie_arr=array("code"=>$_GET['code']);
		$cookie_data=actfun::read_cookie($cookie_name,$cookie_arr,"dgapp");
		if(!empty($cookie_data)){echo $cookie_data;exit;}
		$url=base64_decode($_GET['code']);
		$data=zfun::curl_get($url);
		$data=str_replace(" ","[空]",$data);
		$data=str_replace("+","[加]",$data);
		$data=urlencode($data);
		$data="dl_get('".$data."');";
		actfun::set_cookie($cookie_name,$cookie_arr,$data,"dgapp");
		echo $data;
	}

	/*分享出去的详情*/
	public function rebateShareDetail(){
        $uid=self::getUserId();
        if (empty($_GET['id'])&&empty($_GET['fnuo_id']))zfun::alert("该商品已下架");
        $fnuo_id=$gid=intval($_GET['id']);


		$tgid = ($_GET['tgid']);
		$tguser=zfun::f_row("User","tg_code='".$tgid."'");
		if(empty($tguser)){
			$Decodekey = $this -> getApp('Tgidkey');
			$tgid = $Decodekey -> Decodekey($tgid);
			$tguser=zfun::f_row("User","id='$tgid'");
		}else $tgid = $tguser['id'];
		$tg_pid=$tguser['tg_pid'];
		$str="taobaopid,app_goods_tw_url,app_goods_tw_kouling,CSharp_share_onoff";
		$str.=",ggapitype,share_get_str7";
		$set=zfun::f_getset($str);
		$this->assign("share_get_str7",$set['share_get_str7']);
		$set['CSharp_share_onoff']=intval($set['CSharp_share_onoff']);

		if(!empty($tg_pid)){
			zfun::$set['pid']=$tg_pid;
			$tmp=explode("_",$set['taobaopid']);
			$GLOBALS['taobaopid']=$set['taobaopid']=$tg_pid=$tmp[0]."_".$tmp[1]."_".$tmp[2]."_".$tg_pid;
		}

       // self::setseo("优惠券商品详情");
		if(!empty($_GET['id'])){
			$goods=zfun::f_row("Goods","id=$gid","id,yhq,fnuo_id,goods_type,commission,goods_img,goods_title,shop_id,goods_price,yhq_price,yhq_url,highcommission_url,highcommission,goods_detail,goods_desc");
			if(!empty($goods['goods_desc']))$goods['is_desc']=1;
			else $goods['is_desc']=0;
		}

		$fnuo_id=$_GET['fnuo_id'];

		if($set['ggapitype']!=2){
			/*改*/
			if(strstr($fnuo_id,"dtk")==false){
				actionfun("default/alimama");
				$data=$v=alimamaAction::getcommission($fnuo_id);
			}
			$shop_arr=array(0=>1,1=>2);
			$shop_str=array(0=>"淘宝",1=>"天猫");
			$arr=array(
				"fnuo_id"=>$v['auctionId'],
				"goods_title"=>str_replace(array("<span class=H>","</span>"),"",$v['title']),
				"goods_price"=>floatval($v['zkPrice']),
				"goods_cost_price"=>floatval($v['reservePrice']),
				"goods_img"=>"https:".str_replace(array("https:","http:"),"",$v['pictUrl'])."_400x400.jpg",
				"goods_sales"=>intval($v['biz30day']),
				"commission"=>floatval($v['tkRate']),
				"shop_type"=>$shop_str[$v['userType']],
				"shop_id"=>$shop_arr[$v['userType']],
				"yhq"=>0,
				"yhq_price"=>zfun::dian(0),
				"yhq_span"=>'',
			);
			if((empty($v['couponInfo'])||$v['couponInfo']=="无")==false){
				$arr['yhq']=1;
				$arr['yhq_price']=zfun::dian($v['couponAmount']);
				$arr['yhq_span']=$v['couponInfo'];
			}
		}
		else{//物料模式
			//匹配物料
			actionfun("comm/tbmaterial");
			$wl_goods=$v=tbmaterial::id($fnuo_id);
			$arr=array(
				"fnuo_id"=>$v['fnuo_id'],
				"goods_title"=>$v['goods_title'],
				"goods_price"=>$v['goods_price'],
				"goods_cost_price"=>$v['goods_cost_price'],
				"goods_img"=>str_replace("_250x250.jpg","",$v['goods_img'])."_400x400.jpg",
				"goods_sales"=>$v['goods_sales'],
				"commission"=>$v['commission'],
				"shop_id"=>$v['shop_id'],
				"yhq"=>0,
				"yhq_price"=>'0.00',
				"yhq_span"=>'',
			);
			$shop_name_arr=array(1=>'淘宝',2=>'天猫',);
			$arr['shop_type']=$shop_name_arr[$v['shop_id']];

		}
		$arr['noyhq_price']=$arr['goods_price'];
		actionfun("default/gototaobao");
		$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$arr['goods_title'],"fnuo_id"=>$arr['fnuo_id']),1);
		if(!empty($tmp_yhq_url)){
			$arr['yhq_url']=$tmp_yhq_url;
		}
		elseif(!empty($wl_goods['yhq_url'])){
			$arr['yhq_url']=$wl_goods['yhq_url'];
			$arr['yhq_price']=$wl_goods['yhq_price'];
		}
			if(!empty($GLOBALS['yhq_price']))$arr['yhq_price']=$GLOBALS['yhq_price'];
			if(!empty($GLOBALS['yhq_span']))$arr['yhq_span']=$GLOBALS['yhq_span'];
			if(!empty($GLOBALS['goods_cost_price']))$arr['goods_cost_price']=$GLOBALS['goods_cost_price'];
			if(!empty($GLOBALS['goods_price']))$arr['goods_price']=$GLOBALS['goods_price'];
		if(!empty($arr['yhq_price'])){
			$arr['yhq']=1;
		}
		//$arr['yhq_url']=$data['yhq_url'];
		$goods=$arr;



 		//self::setseo($goods['goods_title']);
      //  if(empty($goods))zfun::alert("该商品已下架");
        if($_GET['data']){
           $goods = zfun::arr64_decode($_GET['data']);
        }

        $goods['tkl'] = '加载中';
        $goods['qhj'] = $goods['goods_price'] - $goods['yhq_price'];
        $goods['goods_img'] = str_replace(array(
            "_290x290.jpg",
            "_290x290.jpg",
			"_400x400.jpg"
        ) , "", $goods['goods_img']) ;
        $tdj = '';


       //if($goods['yhq']==1){

		//actionfun("default/gototaobao");
		//include_once ROOT_PATH."Action/index/default/tbk_coupon.action.php";
		// actionfun("default/tbk_coupon");
		//$tmp=tbk_couponAction::getone($goods['goods_title'],$goods['fnuo_id']);
		/*
		if(empty($goods['yhq_url'])){
			$tmp_yhq_url=gototaobaoAction::check_yhq_url($goods,1);
			if(!empty($tmp_yhq_url))$goods['yhq_url']=$tmp_yhq_url;
		}*/


        //}
		/*
        if(!empty($goods['yhq_url'])&&strstr($goods['yhq_url'],"uland.taobao.com")==false){
            $goods['yhq_url']="https://uland.taobao.com/coupon/edetail?activityId=".self::getin($goods['yhq_url'],"activityId")."&itemId=".$goods['fnuo_id']."&pid=".$this->getSetting('taobaopid')."&nowake=1";
        }*/
		/*
        if(self::iswx()&&file_exists(ROOT_PATH."Action/index/weixin/tkl.action.php")||!empty($_GET['show_tkl'])){
         */

		$goods['is_wx']=1;
		include_once ROOT_PATH."Action/index/appapi/tkl.action.php";
		if($set['CSharp_share_onoff']===0)$goods['tkl']=tkl::gettkl($goods);
		 /*
        }else{
		$goods['is_wx']=0;
		$tdj=$this->getSetting('un_setting_tdj');
       }*/
        $_POST['tdj']=1;
        $goods=zfun::f_fgoodscommission(array($goods));$goods=reset($goods);
		//jj explosion
		//life is a shipwreck
		if(!empty($tg_pid)){
			$goods['fnuo_url'].="&pid=".$GLOBALS['taobaopid'];

		}else{
			$goods['fnuo_url'].="&pid=".$set['taobaopid'];
		}

		$goods['update']='浏览器购买';
		$goods['kltype']='淘宝';
		if(intval($set['app_goods_tw_kouling'])==1){
			$goods['tkl']=self::kouling($goods['fnuo_id'],$tgid,$type);
			$goods['kltype']='站内';
		}
		$settt=zfun::f_getset("tg_durl");
		if(intval($set['app_goods_tw_url'])==1){
			//$goods['fnuo_url']=self::getUrl('invite_friend', 'new_packet', array('tgid' =>intval($_GET['tgid'])),'new_share');
			$goods['fnuo_url']=self::getUrl("downloadapp","index",array(),"wap");
			$goods['update']='下载APP';
		}
		if(intval($set['app_goods_tw_url'])==2){
			$goods['fnuo_url']=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$goods['fnuo_id'],'tgid' =>intval($_GET['tgid'])),'new_share');
			if(intval($settt['tg_durl'])==1){
			$goods['fnuo_url']=INDEX_WEB_URL."new_share-".intval($_GET['tgid'])."-".$goods['fnuo_id']."-1.html";
			}
			//$goods['fnuo_url']=self::getUrl("downloadapp","index",array(),"wap");
			$goods['update']='注册领红包';
		}
        $data = array();
        $data['tdj'] = $tdj;
        $data['button'] = '<a class="goto_buy" isconvert=1 data-itemid="' . $goods['fnuo_id'] . '" buy="on">马上抢</a>';
        $data['fnuo_id'] = $goods["fnuo_id"];
		$service=zfun::f_getset("qqservice");

        self::assign("kdata", json_encode($data));
		self::assign("service", $service['qqservice']);

        zfun::isoff($goods);
	//插入或更新足迹
     	if(!empty($uid)){
            $fgoods= zfun::f_count("FootMark","goodsid =  '$fnuo_id' and uid = '$uid'");
            if($fgoods == '0') {
                $fdata=array();
                $fdata['goodsid']=$goods['fnuo_id'];
                $fdata['starttime']=time();
                $fdata['endtime']=time();
                $fdata['uid']=intval($uid);
                zfun::f_insert("FootMark",$fdata);
            }else{
                $fdata['endtime']=time();
                zfun::f_update("FootMark","goodsid =  '$fnuo_id' and uid = '$uid'",$fdata);
            }
        }
        $share['UcenterShareTitle']=$this->getSetting("UcenterShareTitle");
        $share['UcenterShareText']=$this->getSetting("UcenterShareText");
        $uid=intval($uid);
        $tgidkey = $this -> getApp('Tgidkey');
        $uid1 = $tgidkey -> addkey($uid);
        $tg_url = ($this -> getUrl('invite_friend_wap', 'new_packet', array('tgid' => $uid1),'new_share_wap'));
   		$title="商品详情";
		self::assign("title", $title);

		//jj explosion
		self::assign("set",$set);
		if($set['CSharp_share_onoff']===1){
			$goods['fnuo_url']='';
			self::assign("tx_token",md5(time()."qwe了".$uid));
			self::assign("tx_fnuo_id",$_GET['fnuo_id']);
			self::assign("tx_host",md5(str_replace("www.","",INDEX_WEB_URL)."我要加密了"));
			self::assign("tx_pid",$GLOBALS['taobaopid']);

		}

        self::assign("goods", $goods);
        self::assign("data", $share);
        self::assign("tgurl",$tg_url);
		 self::assign("app_goods_tw_url",$set['app_goods_tw_url']);
		//zheli boom
		$detail=self::get_bc_detail($goods['fnuo_id']);
		//fpre($detail);
		self::assign("iswx",self::iswx());
		self::assign("ifurl",$detail['url']);
		self::assign("img",$detail['img']);
		self::assign("canshu",$detail['canshu']);
		self::assign("detail_img",$detail['detail_img']);
		//jj explosion
		self::assign("uid",intval(self::getUserId()));
        $this ->display();
        //$this -> runplay("wap","comm","top");
        $this ->play();
    }
	/*生成返利口令*/
	public static function kouling($fnuo_id,$uid=0,$type){
		$_POST['type']=$type;
		$pset['fnuo_id']=$fnuo_id;
		$garr=array();
		$garr['yhq_span']="";
		$garr['yhq_price']=0;
		if(intval($_POST['type'])==0){
			$goods=alimamaAction::getcommission($pset['fnuo_id']);
			//if(empty($goods))zfun::fecho("商品无效");
			$garr['goods_title']=$goods['title'];
			$garr['goods_price']=$goods['zkPrice'];
			$garr['goods_img']="https:".$goods['pictUrl']."_300x300.jpg";
			$garr['commission']=floatval($goods['tkRate']);
			$garr['yhq_span']=$goods['couponInfo'];
			$garr['yhq_price']=floatval($goods['couponAmount']);
			//fpre($goods);
		}
		else{
			self::getjdset();
			fun('jdapi');
			$goods=reset(jdapi::getgoods($pset['fnuo_id']));
			$price=reset(jdapi::getprice($pset['fnuo_id']));
			$commission=reset(jdapi::getgoodsinfo($pset['fnuo_id']));
			$garr['goods_title']=$goods['name'];
			$garr['goods_price']=$price["price"];
			$garr['goods_img']=$goods['imagePath'];
			$garr['commission']=floatval($commission['commisionRatioPc']);
		}
		//if(empty($goods))zfun::fecho("error");
		$tmp=$uid."_".$pset['fnuo_id']."_".$garr['goods_title'];

		$code=substr(md5($tmp),0,6);
		$arr=array(
			"code"=>$code,
			"extend_id"=>$uid,
			"fnuo_id"=>$pset['fnuo_id'],
			"type"=>intval($_POST['type']),
			"time"=>time(),
			"goods_title"=>addslashes($garr['goods_title']),
			"goods_price"=>$garr['goods_price'],
			"goods_img"=>$garr['goods_img'],
			"commission"=>$garr['commission'],
			"yhq_span"=>$garr['yhq_span'],
			"yhq_price"=>$garr['yhq_price'],
		);
		$where="extend_id='$uid' and fnuo_id='".$pset['fnuo_id']."'";
		$kouling=zfun::f_row("Kouling",$where);
		if(empty($kouling)){
			zfun::f_insert("Kouling",$arr);
		}
		else{
			zfun::f_update("Kouling",$where,$arr);
		}
		$code="#".$code."#";
		return $code;
	}

    // 获取分类
    public function getcate($pid,$t){
        $categoryModel=self::getDatabase("Category");
        if($t==1){
           $cate=$categoryModel->getCate("all","id,pid,catename");
        }else{
          if(!empty($pid))$cate=$categoryModel->getAll($pid,"id,pid,category_name,img");
          else$cate=$categoryModel->getAll("all");
        }
        return $cate;
    }
    //获取商品
    public function getgoods($type,$t,$p){
        $cid=$_GET['cid'];
        $where=" shop_id in (1,2,3)";
        if(!empty($cid)){
            $where.=" AND cate_id='$cid'";
        }
        if(!empty($_GET['keyword'])){
            $keyword=$_GET['keyword'];
            // self::gettaobaoUrl();
            $where.=" AND goods_title like '%$keyword%' ";
        }
        $type=6;
        switch ($type) {
            case '6':
                $where.=" AND highcommission=0";
                break;
        }
        $limit=20;
        $sort="cjtime desc";
        switch ($t) {
            case '0':

            break;

            case '1':
                $sort=" goods_sales desc ";
            break;
            case '2':
                $sort=" goods_price desc ";
            break;
            case '3':
                $sort=" goods_price asc ";
            break;

        }
        $filde="id,fnuo_id,goods_title,goods_img,goods_price,goods_cost_price,stock,goods_sales,goods_type,shop_id,cjtime,commission,yhq,highcommission,highcommission_wap_url,cate_id,yhq_price";
        $goods=zfun::f_goods("Goods",$where,$filde,$sort,null,$limit);
        $goods=zfun::f_fgoodscommission($goods);
        $arr=array("1"=>"taobao","2"=>"tianmao","3"=>"jingdong");
        foreach($goods as $k=> $v){
            $goods[$k]['detailurl']=self::getUrl("rebate","rebate_detail",array("id"=>$v['id']),"wap");
            $goods[$k]['shop_name']=$arr[$v['shop_id']];
            $goods[$k]['qh_price']=zfun::dian($v['goods_price']-$v['yhq_price'],"100");
        }
        return $goods;
    }
    public function gettaobaoUrl() {
        $keyword=$_POST['keyword']=$_GET['keyword'];
        $userModel = $this -> getDatabase('User');
        $uid=10;
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

       header("Location:".$url);
        //$isjifents = $this -> getSetting('isjifents');
    }
    public static function getin($url="",$name=''){
        $url=str_replace("?","&",$url);
        $tmp=explode("&".$name."=",$url);
        $tmp=explode("&",$tmp[1]);
        return $tmp[0];
    }
        //登录
    public function islogin(){
        $uid=$this->getUserId();

        if (empty($uid)) {
            $this -> linkNo('login', 'login',array(),"wap");
            exit ;
        }
    }
   public static function iswx(){
		if ( strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false )return 1;
		else return 0;
	}
	public function iswxlogin(){
		if(!self::iswx()&&empty($_SESSION['wxuser']))return;
		$set=zfun::f_getset("weixin_login_onoff");
		if(intval($set['weixin_login_onoff'])==0)return;//如果设置了不能微信登录
		if(file_exists(ROOT_PATH."Action/index/weixin/wxlogin.action.php")==false)return;
		$this->runplay("weixin","wxlogin","login");
	}

}
?>
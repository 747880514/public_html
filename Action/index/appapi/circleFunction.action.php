<?php
actionfun("appapi/dgappcomm");
actionfun("comm/tbmaterial");
class circleFunctionAction extends Action{
	//公共方法查询
	public static function commSelect($where='',$type=''){
		$num=5;
		if(!empty($_POST['num']))$num=intval($_POST['num']);
		$data=appcomm::f_goods("CircleOfFriends",$where,"","time desc",NULL,$num);
		$user=zfun::f_kdata("User",$data,"uid","id"); 
		$set=zfun::f_getset("circleoffriend_nickname,circleoffriend_head_img");
		$head_img='';
		$nickname=$set['circleoffriend_nickname'];
		if(!empty($GLOBALS['cate']['nickname']))$nickname=$GLOBALS['cate']['nickname'];
		if(!empty($set['circleoffriend_head_img']))$head_img=$set['circleoffriend_head_img'];
		if(!empty($GLOBALS['cate']['img']))$head_img=$GLOBALS['cate']['img'];
		$user=appcomm::getheadimg($user);
		foreach($data as $k=>$v){
			
			if(is_numeric($user[$v['uid']]['nickname']))$data[$k]['nickname']=self::xphone($user[$v['uid']]['nickname']);
			else if(strstr($user[$v['uid']]['nickname'],"@"))$data[$k]['nickname']=self::xphone($user[$v['uid']]['nickname']);
			else $data[$k]['nickname']=$user[$v['uid']]['nickname'];
			$data[$k]['head_img']=$user[$v['uid']]['head_img'];
			if($v['uid']=='admin'){
				$data[$k]['nickname']=$nickname;
				if(!empty($head_img))$data[$k]['head_img']=UPLOAD_URL."CircleOfFriend/".$head_img;
			}
			$img=explode(",",$v['img']);
			foreach($img as $k1=>$v1){
				if(empty($v1))unset($img[$k1]);
				elseif(strstr($v1,"http")==false) $img[$k1]=UPLOAD_URL."CircleOfFriend/".$v1;
				
			}
			$data[$k]['img']=array_values($img);
			
			$data[$k]['shop_img']=self::imgs($v['shop_type']);
			unset($data[$k]['thumbs_id'],$data[$k]['evaluate_id']);
			$data[$k]['is_thumb']=0;
			$count=zfun::f_count("CircleOfFriendThumb","uid='".$GLOBALS['uid']."' and cir_id='".$v['id']."' and uid<>0");
			if($count>0)$data[$k]['is_thumb']=1;
			$data[$k]['url']=str_replace("[INDEX_WEB_URL]",INDEX_WEB_URL,$v['url']);
			$data[$k]['jsurl']='';
			$data[$k]['is_need_js']=0;
			$json=json_decode($v['data'],true);$data[$k]['imgData']=array();
			$count=$GLOBALS['goods_count']=count($json['goods_data']);
			
			$data[$k]['content']=self::wenanUpdate($v,$type);
			$data[$k]['type']=$v['type']="pub_guanggao";
			if(!empty($json['goods_show_type'])){
				$data[$k]['type']=$v['type']="pub_".$json['goods_show_type'];
			}
			$goods_data='';
			if($count>1){
				foreach($json['goods_data'] as $k1=>$v1){
					if($data[$k]['type']=='pub_more_goods'){
						$goods_data=$v1;
						$goods_data=self::goods_comm_doing($goods_data);
						if(!empty($goods_data)){
							$data[$k]['imgData'][$k1]=$goods_data;
						}
					}
					$data[$k]['imgData'][$k1]['fnuo_id']=$v1['fnuo_id'];
					$data[$k]['imgData'][$k1]['goods_type']=$v1['goods_type'];
					$data[$k]['imgData'][$k1]['img']=$v1['img'];
					$data[$k]['imgData'][$k1]['type']=$data[$k]['type'];
				}
			}else {
				if($v['goods_type']=='dtk'){$data[$k]['goods_type']=$v['goods_type']='buy_taobao';$data[$k]['type']='pub_one_goods';}
				if($data[$k]['type']=='pub_one_goods'){
					$str=str_replace(array("，",","),",",$v['content']);
					$tmpstr=explode(",",$str);
					$str='-1';$j=2;$i=0;
					foreach($tmpstr as $k1=>$v1){if($k1==$j*$i){$i++;$str.=",\n".$v1;}else $str.=",".$v1;}
					$str=substr($str,3);
					$data[$k]['content']=str_replace(array("!","!"),"\n",$str);
					//一个商品的时候
					$goods_data=zfun::arr64_decode($v['goods_data']);
					
					if(empty($goods_data))$goods_data=$v;
					$goods_data=self::goods_comm_doing($goods_data);
				}
			
				foreach($data[$k]['img'] as $k1=>$v1){
					if(!empty($goods_data))$data[$k]['imgData'][$k1]=$goods_data;
					
					$data[$k]['imgData'][$k1]['fnuo_id']=$v['fnuo_id'];
					$data[$k]['imgData'][$k1]['goods_type']=$v['goods_type'];
					$data[$k]['imgData'][$k1]['img']=$v1;
					$data[$k]['imgData'][$k1]['type']=$data[$k]['type'];
				}
			}
			$data[$k]['imgData']=array_values($data[$k]['imgData']);
			unset($data[$k]['data']);
			
		}	
		return $data;
	}
	static function goods_comm_doing($goods=array()){
		$goods_data=$goods;
		if(empty($goods_data['goods_title'])){
			$goods_arr=self::goodsMsg($goods['fnuo_id'],'',$goods['goods_type']);
			
			$goods_data=$goods_arr['goods'];
		}
		
		if(empty($goods_data['goods_title']))return $goods;
		$goods_data=array($goods_data);
		$goods_data=zfun::f_fgoodscommission($goods_data);
		appcomm::goodsfanlioff($goods_data);
		appcomm::goodsfeixiang($goods_data);$goods_data=reset($goods_data);
		return $goods_data;
	}
	public static function imgs($type=''){
		$img='';
		switch($type.''){
			case "taobao":
				$img=INDEX_WEB_URL.'View/index/img/appapi/comm/circle_taobao.png?time='.time();
				break;
			case "tianmao":
				$img=INDEX_WEB_URL.'View/index/img/appapi/comm/circle_tmall.png?time='.time();
				break;
			case "jingdong":
				$img=INDEX_WEB_URL.'View/index/img/appapi/comm/circle_jd.png?time='.time();
				break;
			case "pinduoduo":
				$img=INDEX_WEB_URL.'View/index/img/appapi/comm/circle_pdd.png?time='.time();
				break;
			case "guanfang":
				$img=INDEX_WEB_URL.'View/index/img/appapi/comm/circle_official.png?time='.time();
				break;
		}
		$set=zfun::f_getset("circleoffriend_tip_img");
		if($type=='guanfang'&&$set['circleoffriend_tip_img'])$img=UPLOAD_URL."CircleOfFriend/".$set['circleoffriend_tip_img'];
		if(!empty($GLOBALS['cate']['ico'])&&$type=='guanfang')$img=UPLOAD_URL."CircleOfFriend/".$GLOBALS['cate']['ico'];
		return $img;
	}
	public function getcodes(){
		$token=filter_check($_GET['token']);
		$goods_type=filter_check($_GET['goods_type']);
		$fnuo_id=filter_check($_GET['fnuo_id']);
		if(empty($fnuo_id))return;
		if(!empty($token)){
			$user=zfun::f_row("User","token='$token'");
			$tgidkey = $this->getApp('Tgidkey');
			$tgid = $tgidkey->addkey($user['id']);
			if(!empty($user['tg_code']))$tgid=$user['tg_code'];
		}
		$arr=self::goodsMsg($fnuo_id,$tgid,$goods_type,"getcode");
		$data=$arr['goods'];
		$tg_url=$arr['tg_url'];
		if(empty($data))return;
		self::qrcode2($data,$user,$tg_url,$tgid);
	}
	//文案替换
	public static function wenanUpdate($data=array(),$type=''){
		
		$token=filter_check($_POST['token']);
		$set=zfun::f_getset("share_host,tg_durl,android_url,taobaopid,app_goods_fenxiang_str2,app_goods_fenxiang_str1,app_pddgoods_fenxiang_str2,app_pddgoods_fenxiang_str1,app_jdgoods_fenxiang_str1,app_jdgoods_fenxiang_str2");
		if(empty($data['fnuo_id']))$data['fnuo_id']=0;
		if(!empty($token)){
			$user=zfun::f_row("User","token='$token'");
			zfun::$set['pid']=$user['tg_pid'];
			$tmp=explode("_",$set['taobaopid']);
			$GLOBALS['taobaopid']=$set['taobaopid']=$tg_pid=$tmp[0]."_".$tmp[1]."_".$tmp[2]."_".$user['tg_pid'];
			$tgidkey = self::getApp('Tgidkey');
			$tgid = $tgidkey->addkey($user['id']);
			if(!empty($user['tg_code']))$tgid=$user['tg_code'];
		}
		$con=$data['content'];
		$strstr='';
		
		if($type=='share'&&$data['type']=='pub_one_goods'){
			//分享的时候才调起生成淘口令
			$arr=self::goodsMsg($data['fnuo_id'],$tgid,$data['goods_type']);
			$tmp=$arr['goods'];
			$tkl='';
			if($data['goods_type']=='taobao'||$data['goods_type']=='buy_taobao'){
				actionfun("appapi/tkl");
				$tmp=zfun::f_fgoodscommission(array($tmp));$tmp=reset($tmp);
				$tkl=tkl::gettkl($tmp);
			}
			$goods_type=array("buy_taobao"=>'',"taobao"=>'',"buy_jingdong"=>'jd',"buy_pinduoduo"=>'pdd');
			if(floatval($tmp['yhq_price'])>0){
				$strstr=str_replace("#","",$set['app_'.$goods_type[$data['goods_type']].'goods_fenxiang_str2']);
			}else{
				$strstr=str_replace("#","",$set['app_'.$goods_type[$data['goods_type']].'goods_fenxiang_str1']);
			}
			
		}
		//链接
		$tg_url='';
		if(!empty($data['fnuo_id']))$tg_url=self::goodsTgurl($data['fnuo_id'],$tgid,$data['goods_type'],"show_wenan");
		$url4=$set['android_url'];
		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";
		$url2=self::getUrl("down","supdownload",array('tgid' => $tgid),"appapi");/*更换*/
		$url1='';
		if(!empty($data['fnuo_id'])){
			//$url1=INDEX_WEB_URL."rebate-".$data['fnuo_id']."-".$tgid.".html";
			$url1=$tg_url;
		}
		$bd=$url1;
		$bd2=$url2;
		$url3='';
		if(!empty($_POST['token'])){
			//$url3=INDEX_WEB_URL."new_share-".$tgid."-".$data['fnuo_id']."-1.html";
			$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$data['fnuo_id'],'tgid' => $tgid),'new_share');
			
		}
		
		$bd3=$url3;
		$goods_down_url=$tg_url;
		if($data['goods_type']=='buy_taobao'){
			//新商品详情
			$goods_down_url=self::getUrl("rebate_DG","rebate_detail",array("type"=>'down',"is_goods_share"=>1,"fnuo_id"=>$data['fnuo_id'],'tgid' => $tgid),"wap");
		
		}
		if(!empty($set['share_host'])){
			$bd=$url1=$tg_url=str_replace(HTTP_HOST,$set['share_host'],$bd);
			$bd2=$url2=str_replace(HTTP_HOST,$set['share_host'],$bd2);
			$bd3=$url3=str_replace(HTTP_HOST,$set['share_host'],$bd3);
			$goods_down_url=str_replace(HTTP_HOST,$set['share_host'],$goods_down_url);
		}
		actionfun("appapi/appHhr");
		if($set['tg_durl']==1&&$type=='share'){
			$arrulr=appHhrAction::bdurl($bd,$bd2,$bd3,$goods_down_url);
	
			if(!empty($arrulr[0]))$tg_url=$arrulr[0];
	
			if(!empty($arrulr[1]))$url2=$arrulr[1];
	
			if(!empty($arrulr[2]))$url3=$arrulr[2];
			if(!empty($arrulr[3]))$goods_down_url=$arrulr[3];
		}
		$con=str_replace("{淘口令}",$tkl,$con);
		$con=str_replace("{应用宝下载链接}",$url4,$con);
		$con=str_replace("{下载链接}",$url2,$con);
		$con=str_replace("{商品链接}",$tg_url,$con);
		$con=str_replace("{邀请注册链接}",$url3,$con);
		$con=str_replace("{邀请码}",$tgid,$con);
		if($strstr){
			$strstr=str_replace("{商品标题}","",$strstr);
			$strstr=str_replace("{淘口令}",$tkl,$strstr);
			$strstr=str_replace("{应用宝下载链接}",$url4,$strstr);
			$strstr=str_replace("{下载链接}",$url2,$strstr);
			$strstr=str_replace("{商品链接}",$tg_url,$strstr);
			$strstr=str_replace("{邀请注册链接}",$url3,$strstr);
			$strstr=str_replace("{商品与下载链接}",$goods_down_url,$strstr);
			$strstr=str_replace("{券后价}",$tmp['goods_price'],$strstr);
			$strstr=str_replace("{价格}",$tmp['goods_cost_price'],$strstr);
			$strstr=str_replace("{邀请码}",$tgid,$strstr);
			actionfun("appapi/goods_fenxiang");
			
			$tmp=zfun::f_fgoodscommission(array($tmp));$tmp=reset($tmp);
			
			$commission=goods_fenxiangAction::get_user_bili($tmp);
			$strstr=str_replace("{自购佣金}",$commission,$strstr);
			$con.="\n".$strstr;
		}
		return $con;
	}
	
	//读取商品信息
	public static function goodsMsg($fnuo_id='',$tgid='',$goods_type='',$type=''){
		if(empty($fnuo_id))return array();
		$arr=array();
		$data=array();
		switch($goods_type.''){
			case "taobao":
			case "buy_taobao":
				actionfun("comm/tbmaterial");
				$data=tbmaterial::id($fnuo_id);
				actionfun("default/gototaobao");
			//	if($type='getcode')$tmp_yhq_url=gototaobaoAction::check_yhq_url(array("goods_title"=>$data['goods_title'],"fnuo_id"=>$fnuo_id),1);
				if(!empty($tmp_yhq_url)&&empty($data['yhq_url'])){
					$data['yhq_url']=$tmp_yhq_url;
					$data['yhq']=1;
				}
				//如果
				$data['yhq_type']=0;
				if(!empty($GLOBALS['yhq_type']))$data['yhq_type']=1;//是否是隐藏券
				if(!empty($dtk_goods['commission']))$data['commission']=$dtk_goods['commission'];	
				//jj explosion
				if(!empty($GLOBALS['yhq_price']))$data['yhq_price']=$GLOBALS['yhq_price'];
				if(!empty($GLOBALS['yhq_span']))$data['yhq_span']=$GLOBALS['yhq_span'];
				if(!empty($GLOBALS['goods_price']))$data['goods_cost_price']=$GLOBALS['goods_price'];
				if(!empty($GLOBALS['goods_price']))$data['goods_price']=$GLOBALS['goods_price'];
				if(!empty($GLOBALS['dtk_commission'])&&$GLOBALS['dtk_commission']>$data['commission'])$data['commission']=$GLOBALS['dtk_commission'];
				$tg_url= self::getUrl("rebate_DG","rebate_detail",array("getgoodstype"=>'wuliao',"fnuo_id"=>$fnuo_id,"tgid"=>$tgid),"wap");
				break;
			case "jingdong":
			case "buy_jingdong":
				actionfun("comm/jingdong");
				if($show!='show_wenan')$data=jingdong::id($fnuo_id);
				$tg_url= self::getUrl("gotojingdong","index",array("gid"=>$fnuo_id,"tgid"=>$tgid),"appapi");
				actionfun("appapi/appJdGoodsDetail");
				$tg_url1=appJdGoodsDetailAction::get_buy_url($data,$tgid);
				if($tg_url1)$tg_url=$tg_url1;
				break;
			case "pinduoduo":
			case "buy_pinduoduo":
				actionfun("comm/pinduoduo");
				if($show!='show_wenan')$data=pinduoduo::id($fnuo_id);
				$tg_url= self::getUrl("gotopinduoduo","index",array("gid"=>$fnuo_id,"tgid"=>$tgid),"appapi");
				actionfun("appapi/appJdGoodsDetail");
				$tg_url1=appJdGoodsDetailAction::get_pdd_buy_url($data,$tgid);
				if($tg_url1)$tg_url=$tg_url1;
				break;
		}
		$arr['goods']=$data;$arr['tg_url']=$tg_url;
		return $arr;
	}
	public static function goodsTgurl($fnuo_id='',$tgid='',$goods_type=''){
		$arr=array();
		$data=array();
		switch($goods_type.''){
			case "taobao"||"buy_taobao":
				$tg_url= self::getUrl("rebate_DG","rebate_detail",array("getgoodstype"=>'wuliao',"fnuo_id"=>$fnuo_id,"tgid"=>$tgid),"wap");
				break;
			case "jingdong"||"buy_jingdong":
				$tg_url= self::getUrl("gotojingdong","index",array("gid"=>$fnuo_id,"tgid"=>$tgid),"appapi");
				break;
			case "pinduoduo"||"buy_pinduoduo":
				$tg_url= self::getUrl("gotopinduoduo","index",array("gid"=>$fnuo_id,"tgid"=>$tgid),"appapi");
				break;
		}
		return $tg_url;
	}
	//百里
	//生成二维码
	public static function qrcode2($arr=array(),$user=array(),$tg_url='',$tgid=0){//生成二维码
		$img=str_replace(array("https:","ttps:"),"http:",$arr['goods_img']);
		$img=str_replace("_250x250.jpg","_800x800.jpg",$img);
		if(!empty($_GET['imgs']))$img=$_GET['imgs'];
		$arr=zfun::f_fgoodscommission(array($arr));$arr=reset($arr);
		$set=zfun::f_getset("share_host,android_url,app_pengyouquan_goods_tw_url");
		$url3=self::getUrl('invite_friend', 'new_packet', array("is_goods_share"=>1,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),'new_share');
		$url2=self::getUrl("down","supdownload",array('tgid' => $tgid),"appapi");/*更换*/
		//新商品详情
		$goods_down_url=$tg_url;
		if($arr['shop_id']=='1'||$arr['shop_id']=='2')$goods_down_url=self::getUrl("rebate_DG","rebate_detail",array("type"=>'down',"is_goods_share"=>1,"fnuo_id"=>$arr['fnuo_id'],'tgid' => $tgid),"wap");
		if(!empty($set['share_host'])){
			$tg_url=str_replace(HTTP_HOST,$set['share_host'],$tg_url);
			$url2=str_replace(HTTP_HOST,$set['share_host'],$url2);
			$url3=str_replace(HTTP_HOST,$set['share_host'],$url3);
			$goods_down_url=str_replace(HTTP_HOST,$set['share_host'],$goods_down_url);
		}

		$url4=$set['android_url'];
		if(intval($set['app_pengyouquan_goods_tw_url'])==1)$tg_url=$url2;
		if(intval($set['app_pengyouquan_goods_tw_url'])==2)$tg_url=$url3;
		if(intval($set['app_pengyouquan_goods_tw_url'])==3)$tg_url=$url4;
		if(intval($set['app_pengyouquan_goods_tw_url'])==4)$tg_url=$goods_down_url;
		
		$data = array();
		$data['width']=750;
		$data['height']=1334;
		if($arr['shop_id']==1)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/taobao_one.png?time=".time();
		if($arr['shop_id']==2)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/tmall_one.png?time=".time();
		if($arr['pdd']==1){$shop_width='95';$shop_height='48';$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/pdd_one.png?time=".time();}
		if($arr['jd']==1)$shop_img=INDEX_WEB_URL."View/index/img/appapi/comm/jd_one.png?time=".time();
       $data['list'][0] = array(//底部的框
            "url" => INDEX_WEB_URL."View/index/img/appapi/comm/code_bg_0.png?time=".time(),
            "x" => 0,
            "y" => 1054,
            "width" => 750,
            "height" => 270,
			"type"=>"png"
        );
		$data['list'][1] = array(//二维码
		   "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($tg_url)."&size=20&codeKB=1",
            "x" => 27,
            "y" => 1165,
            "width" => 130,
            "height" => 130,
			"type"=>"png"
        );
		//
		$data['list'][2] = array(//商品图【图片
            "url" => $img,
            "x" =>30,
            "y" => 350,
            "width" => 680,
            "height" => 680,
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
            "x" =>475,
            "y" => 890,
            "width" => 252,
            "height" => 105,
			"type"=>"png"
        );
        $data['list'][7] = array(//logo
            "url" => INDEX_WEB_URL."View/index/img/appapi/comm/good_share_logo.png?time=".time(),
            "x" =>30,
            "y" => 50,
            "width" => 257,
            "height" => 65,
			"type"=>"png"
        );
		for($i=0;$i<3;$i++){
			$data['text'][$i]=array(
				"size"=>22,
				"x"=>30,
				"y"=>180+40*$i,
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
		// 	"size"=>22,
		// 	"x"=>140,
		// 	"y"=>$y+108,
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
			"size"=>34,
			"x"=>520,
			"y"=>965,
			"val"=>"￥".(floatval($arr['goods_price'])),
			"color"=>'white',
		);
		$arr['yhq_price']=floatval($arr['yhq_price']);
		if(!empty($arr['yhq_price'])){
			$len=strlen(floatval($arr['yhq_price'])."元优惠券");
			$data['text'][$ii['i']+3] = array(
				"size"=>25,
				"x"=>55,
				"y"=>$y+192,
				"val"=>(floatval($arr['yhq_price']))."元优惠券",
				"color"=>'white',
			);
			$data['list'][6] = array(
				"url" => INDEX_WEB_URL."View/index/img/appapi/comm/h2_nr_kuang_gold_one.png?time=".time(),
				"x" =>30,
				"y" => $y+155,
				"width" => 225,
				"height" => 50,
				"type"=>"png"
			);
		}
		fun("pic");
		return pic::getpic($data);
		
	}
	public static function xphone($phone = '') {
		$phone.= "";
		$len = strlen($phone);
		if ($len >= 11) {
			return mb_substr($phone, 0, 3, "utf-8") . "******" . mb_substr($phone, -2, 2, "utf-8");
		}
		if ($len >= 5) {
			return mb_substr($phone, 0, 2, "utf-8") . "***" . mb_substr($phone, -1, 1, "utf-8");
		}
		return mb_substr($phone, 0, 1, "utf-8") . "*";
	}
}
?>
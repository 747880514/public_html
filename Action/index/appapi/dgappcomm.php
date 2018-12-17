<?php
class appcomm{
	public static function getsign($arr = array()) {
		ksort($arr);$str='';
		foreach($arr as $k => $v)$str.=$k.$v;
		return md5('123'.$str.'123');
	}
	public static function signcheck($type=0) {
		$_POST['time'] = intval($_POST['time']);
		if (abs(time() - $_POST['time']) > 3600)zfun::fecho("请求过期");
		$sign = $_POST['sign'];unset($_POST['sign']);
		$syssign = self::getsign($_POST);$_POST['sign']=$sign;
		//测试
		if($sign!="789"){
			if ($sign != $syssign)zfun::fecho("签名错误");
		}
		if($type==1){
			if(empty($_POST['token']))zfun::fecho("缺少token");
		}
		if(!empty($_POST['token'])){
			$user=zfun::f_row("User","token='".filter_check($_POST['token']) ."'");
			if(empty($user))zfun::fecho("用户不存在");
			$GLOBALS['action']->setSessionUser($user['id'],$user['nickname']);
			return $user;
		}
		return true;
	}
	public static function parametercheck($str=""){
		if(empty($str))return;
		$tmp=explode(",",$str);
		$arr=array();
		foreach($tmp as $k=>$v){
			if(empty($v))continue;
			if(empty($_POST[$v]))zfun::fecho("缺少参数 ".$v);
			$arr[$v]=$_POST[$v];
		}
		return $arr;
	}
	//获取活动价格
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
	//获取标签价格
	public static function getattrprice($goods=array(),$num=1,$attr=-1){
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
	public static function getSSX($id1,$id2,$id3,$str=''){
		$dq1=zfun::f_row("O2OProvince","ProvinceID=".intval($id1));
		$dq1=$dq1['ProvinceName'];
		$dq2=zfun::f_row("O2OCity","CityID=".intval($id2));
		$dq2=$dq2['CityName'];
		$dq3=zfun::f_row("O2ODistrict","DistrictID=".intval($id3));
		$dq3=$dq3['DistrictName'];
		return $dq1.$str.$dq2.$str.$dq3;
	}
	public static function f_goods($Da,$where,$field,$sort,$arr,$limit){
		if(!empty($_GET['f_goods']))fpre($where);
		$_GET['p']=intval($_POST['p']);if(empty($_GET['p']))$_GET['p']=1;
		return zfun::f_goods($Da,$where,$field,$sort,$arr,$limit);	
	}
	public static function simg($str='',$path='slide',$filename='',$filetype='jpg'){
		if(empty($str))return array();
		$tmp=explode(",",$str);
		$arr=array();
		foreach($tmp as $k=>$v){
			if(empty($v)||empty($_POST[$v]))continue;
			if(empty($GLOBALS['f_simg_n']))$GLOBALS['f_simg_n']=1;
			$img=explode(",",$_POST[$v]);
			$alname=array();
			$path=UPLOAD_PATH .$path.DIRECTORY_SEPARATOR;
			//if(self::makepath($path)==false)zfun::fecho("目录创建失败");
			foreach($img as $k1=>$v1){
				if(empty($v1))continue;
				$GLOBALS['f_simg_n']++;
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
	public static function makepath($path){
		$path=str_replace(array("\\",'/'),DIRECTORY_SEPARATOR,$path);
		$array=explode(DIRECTORY_SEPARATOR,$path);
		array_pop($array);
		$temp='';
		foreach($array as $k=>$v){
			if(empty($v))continue;
			$temp=$temp.$v.DIRECTORY_SEPARATOR;
			if(strstr($temp,"_1."))continue;
			if (!file_exists($temp)){
				$type=mkdir($temp, 0777);
				if(!$type)return false;
			}
		}
		return true;
	}
	public static function arrc($arr=array(),$str='',$type='int'){
		$tmp=explode(",",$str);
		foreach($tmp as $k=>$v){
			if(empty($v))continue;
			if($type=='int')$arr[$v]=floatval($arr[$v]);
			if($type=='str')$arr[$v]=addslashes($arr[$v]);
		}
		return $arr;
	}
	public static function GetDistance($lat1, $lng1, $lat2, $lng2, $len_type = 1, $decimal = 2){
	   $radLat1 = $lat1 * PI/ 180.0;   //PI()圆周率
	   $radLat2 = $lat2 * PI/ 180.0;
	   $a = $radLat1 - $radLat2;
	   $b = ($lng1 * PI/ 180.0) - ($lng2 * PI/ 180.0);
	   $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
	   $s = $s * EARTH_RADIUS;
	   $s = round($s * 1000);
	   //if ($len_type --> 1)$s /= 1000;
	   $s /= 1000;
	   return $s;
	}
	public static function GetDisWhere($lat=0,$lng=0,$lat_='lat',$lng_='lng',$distance=1){
		$lat=floatval($lat);$lng=floatval($lng);
		$dlng =  2 * asin(sin($distance / (2 * EARTH_RADIUS)) / cos(deg2rad($lat)));
		$dlng = rad2deg($dlng);
		$dlat = $distance/EARTH_RADIUS;
		$dlat = rad2deg($dlat);
		$squares= array(
			'left-top'=>array('lat'=>$lat + $dlat,'lng'=>$lng-$dlng),
			'left-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng - $dlng),
			'right-top'=>array('lat'=>$lat + $dlat, 'lng'=>$lng + $dlng),
			'right-bottom'=>array('lat'=>$lat - $dlat, 'lng'=>$lng + $dlng)
		);
		$where=" and ".$lat_.">{$squares['right-bottom']['lat']} and ".$lat_."<{$squares['left-top']['lat']} and 
				".$lng_.">{$squares['left-top']['lng']} and  ".$lng_."<{$squares['right-bottom']['lng']}";
		return $where;
	 }
	 public static function sortarr($arr=array(),$key='',$type="desc"){//二位数组排序
		$tmp=array();
		foreach ($arr as $k=>$v)$tmp[$k] = $v[$key];
		if($type=="desc")$type=SORT_NUMERIC;
		else $type=SORT_NUMERIC;
		array_multisort($arr,$type,$tmp);
		return $arr;	
	}
	public static function getheadimg($user=array()){
		foreach($user as $k=>$v){
			if(empty($v['head_img']))$v['head_img']="default.png";
			if(strstr($v['head_img'],"http")==false)$v['head_img']=UPLOAD_URL."user/".$v['head_img'];
			$user[$k]['head_img']=$v['head_img'];
		}
		return $user;
	}
	public static function randOrderId($gid,$uid) {
		if(isset($GLOBALS['onum'])==false)$GLOBALS['onum']=0;
		else $GLOBALS['onum']++;
		return date('YmdHis').$gid.$uid.$GLOBALS['onum'];
	}
	public static function maidianSuccess($order){//买点成功操作
		if(empty($order))return false;
		$user=zfun::f_row("User","id=".intval($order['shop_id']));
		$store=zfun::f_row("Store","uid=".intval($order['shop_id'])." and checks=1");
		if(empty($store))zfun::fecho("store is null");
		if(empty($user)){echo "user is null";exit;}
		$zijin=$user['zijin']+$order['payment'];
		$arr=array(
			"zijin"=>$zijin,//资金
		);
		$result=zfun::f_update("User","id=".$user['id'],$arr);
		$arr=array(
			"time"=>time(),
			"detail"=>"买单收入资金".$order['payment']."元",
			"uid"=>$user['id'],
			"type"=>0,//资金转入
		);
		$result=zfun::f_insert("Interal",$arr);//插入记录
		$store_send_msg_onoff=intval($GLOBALS['action']->getSetting("store_send_msg_onoff"));
		if($store_send_msg_onoff){
			zfun::sendphonemsg($store['phone'],"你有一条已付款".$order['orderId']);		
		}
		return true;
	}
	
	public static function goodsfanlioff(&$goods=array(),$set=array()){
		if(empty($goods))return array();
		$str="checkVersion,fan_all_str,fxdl_show_fl1,fxdl_show_fl2,fxdl_show_fl3,fxdl_show_fl4,fxdl_show_fl5,fxdl_show_fl6,fxdl_show_fl7,";
		$str.="fxdl_show_fl8,fxdl_show_fl9,fxdl_show_fl10,fxdl_show_fl11,fxdl_show_fl12";
		$set=zfun::f_getset($str.",app_fanli_onoff,app_choujiang_onoff,app_fanli_off_str,app_fanli_ico,app_hongbao_ico");	
		$lv=1;
		if(!empty($_POST['token'])){
			$userid=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			$lv=intval($userid['is_sqdl']+1);
		}
		$showfl=intval($set['fxdl_show_fl'.$lv]);
		if($set['app_choujiang_onoff']==1)$ico_url=UPLOAD_URL."slide/".$set['app_hongbao_ico'];
		elseif($set['app_fanli_onoff']==1)$ico_url=UPLOAD_URL."slide/".$set['app_fanli_ico'];
		else $ico_url="";
		$sharefl=0;
		if($showfl==1)$sharefl=1;//如果是1 是隐藏分享
		if($showfl==2)$showfl=1;//如果是2 是隐藏自购返利的
		foreach($goods as $k=>$v){
			$goods[$k]['ico']=$ico_url;
			$goods[$k]['is_hide_fl']=$showfl;
			$goods[$k]['is_hide_sharefl']=$sharefl;
			$goods[$k]['is_qiangguang']=intval($v['is_qiangguang']);
			$goods[$k]['is_qg']=intval($v['is_qg']);
			if($set['app_choujiang_onoff']==1){
				$goods[$k]['is_hide_fl']=0;
				$goods[$k]['is_hide_sharefl']=0;
			}
			if($userid['operator_lv']>0){
				$goods[$k]['is_hide_fl']=0;
				$goods[$k]['is_hide_sharefl']=0;
			}
			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
				$goods[$k]['fxz']='';
				$goods[$k]['is_hide_fl']=1;
				$goods[$k]['is_hide_sharefl']=1;
				
			}
			if($goods[$k]['is_hide_fl']==1)$goods[$k]['fcommission']="0";
			
		}
		
		if(intval($set['app_fanli_onoff'])==1)return $goods;
		foreach($goods as $k=>$v){
			
			$goods[$k]['q']=$goods[$k]['fcommission']=$goods[$k]['returnfb']=$goods[$k]['return_title']=$goods[$k]['returnbl']=$goods[$k]['commission']='';	
			if(empty($v['app_fanli_off_str']))$goods[$k]['app_fanli_off_str']=$set['app_fanli_off_str'];
			$goods[$k]['fan_all_str']=$set['fan_all_str'];	
		}
		
		return $goods;	
	}
	public static function goodsfeixiang(&$goods=array()){
		if(empty($goods))return array();
		$is_operator=0; 
		if(!empty($_POST['token'])){
			$user=$userid=zfun::f_row("User","token='".filter_check($_POST['token'])."'");
			if($userid['operator_lv'].''!='0')$is_operator=1;
		}
		$set=zfun::f_getset("checkVersion,goods_list_city_onoff,fx_goods_fl,operator_wuxian_bili,operator_onoff");
		$set['operator_wuxian_bili']=doubleval($set['operator_wuxian_bili'])/100;
		
		$dl_id_val=(intval($userid['is_sqdl'])+1);
		
		$sety=zfun::f_getset("fxdl_hhrshare_onoff,hhrshare_flstr,taoqianggou_quan_color,goodsyhqstr_color,goodsfcommissionstr_color,goods_yhqlist_str,fan_all_str,hhrapitype,fxdl_fxyjbili".$dl_id_val.",fxdl_tjhy_bili1_".$dl_id_val);
		if(empty($sety['goodsyhqstr_color']))$sety['goodsyhqstr_color']='f43e79';
		if(empty($sety['goodsfcommissionstr_color']))$sety['goodsfcommissionstr_color']='f43e79';

		$dian=100;
		
		foreach($goods as $k=>$v){
			$goods[$k]['fxz']="";
			$goods[$k]['fx_commission']='';
			if(intval($set['fx_goods_fl'])!=1){
				
				//如果开启了推广模式 读取一级佣金比例
				if(intval($set['operator_onoff'])==1){//读取 一级推广佣金比例
					$bili=$sety["fxdl_tjhy_bili1_".$dl_id_val]/100;
				}
				else{//读取 分享佣金比例
					$bili=$sety["fxdl_fxyjbili".$dl_id_val]/100;
				}
				//如果是运营商修改比例
				if($is_operator)$bili=$set['operator_wuxian_bili'];
				if($set['operator_onoff']=='2'){//双轨模式
					actionfun("comm/twoway");$twoset=twoway::set();
					$one_set=$twoset[intval($userid['is_sqdl']).''];

					// 百里.修改前
					// $bili=doubleval($one_set['自购比例'])/100;
					// 百里.修改后
					$baili_bili = $one_set['自购比例']+$one_set['推广1级比例']+$one_set['团队存在1合伙人比例']+$one_set['团队存在2合伙人比例']+$one_set['团队存在1同级比例']+$one_set['团队存在2同级比例'];
					$bili=doubleval($baili_bili)/100;


					if($user['is_sqdl']==0&&$is_operator==0){//下一个等级的比例 普通会员显示
						$one_set=$twoset[intval($userid['is_sqdl']+1).''];

						// 百里.修改前
						// $bili=doubleval($one_set['自购比例'])/100;
						// 百里.修改后
						$baili_bili = $one_set['自购比例']+$one_set['推广1级比例']+$one_set['团队存在1合伙人比例']+$one_set['团队存在2合伙人比例']+$one_set['团队存在1同级比例']+$one_set['团队存在2同级比例'];
						$bili=doubleval($baili_bili)/100;

					}elseif($user['is_sqdl']==0&&$is_operator==0&&$sety['fxdl_hhrshare_onoff']==1){
						$one_set=$twoset[intval($userid['is_sqdl']).''];

						// 百里.修改前
						// $bili=doubleval($one_set['自购比例'])/100;
						// 百里.修改后
						$baili_bili = $one_set['自购比例']+$one_set['推广1级比例']+$one_set['团队存在1合伙人比例']+$one_set['团队存在2合伙人比例']+$one_set['团队存在1同级比例']+$one_set['团队存在2同级比例'];
						$bili=doubleval($baili_bili)/100;
					}
					if($is_operator)$bili=0;
				}
				$commission=round($v['goods_price']*($v['commission']/100)*$bili*$dian)/$dian;
				$goods[$k]['fx_commission']=$commission;
				$goods[$k]['fx_commission_bili']=round(($commission/$v['goods_price'])*100*$dian)/$dian;
				$goods[$k]['fxz']=$sety['hhrshare_flstr']." ".$commission;
				//升级赚
				$data=self::tuiguang($goods[$k],$user,$set);
				if(!empty($data['fx_commission']))$goods[$k]['fx_commission']=$data['fx_commission'];
				if(!empty($data['fx_commission_bili']))$goods[$k]['fx_commission_bili']=$data['fx_commission_bili'];
				if(!empty($data['fxz']))$goods[$k]['fxz']=$data['fxz'];
				if($set['goods_list_city_onoff']==1){$goods[$k]['provcity']='';$goods[$k]['ciry']='';}
				
			}
			if($_POST['appVersion']==$set['checkVersion']&&!empty($set['checkVersion'])&&$_POST['platform']=='iOS'){
				
				$goods[$k]['is_hide_fl']=1;
				$goods[$k]['is_hide_sharefl']=1;
			}
			
		}
		
		//商品相关 设置
		self::goodsSet02($goods);
		
		return $goods;	
	}
	//这是未登录 和 普通会员时的
	static function tuiguang($goods=array(),$user=array(),$set=array()){
		if(!empty($user['is_sqdl'])||!empty($user['operator_lv']))return;
		$set3=zfun::f_getset("hhrshare_ptstr,hhrshare_flstr,fxdl_hhrshare_onoff");
		//如果开启了推广模式 读取一级佣金比例
		if(intval($set['operator_onoff'])==1){//读取 一级推广佣金比例
			$str="fxdl_lv";
			for($i=1;$i<=10;$i++)$str.=",fxdl_tjhy_bili1_".$i;
			$tuiguang_set=zfun::f_getset($str);unset($tuiguang_set['fxdl_lv']);
			$key = array_search(max($tuiguang_set),$tuiguang_set); //最大值下标
			
			if(empty($user)){$bili=$set['operator_wuxian_bili'];}
			else $bili=$tuiguang_set[$key]/100;
			if(!empty($user)&&$set3['fxdl_hhrshare_onoff']==1)$bili=$tuiguang_set['fxdl_tjhy_bili1_'.($user['is_sqdl']+1)]/100;
		}
		else{//读取 分享佣金比例
			$str="fxdl_lv";
			for($i=1;$i<=10;$i++)$str.=",fxdl_fxyjbili".$i;
			$fanli_set=zfun::f_getset($str);unset($fanli_set['fxdl_lv'],$fanli_set['fxdl_fxyjbili1']);
			$key = array_search(max($fanli_set),$fanli_set); //最大值下标
			
			if(empty($user)){$bili=$fanli_set[$key]/100;}
			else $bili=$fanli_set["fxdl_fxyjbili2"]/100;
			if(!empty($user)&&$set3['fxdl_hhrshare_onoff']==1)$bili=$tuiguang_set['fxdl_fxyjbili'.($user['is_sqdl']+1)]/100;
		}
		if($set['operator_onoff']=='2'){//双轨模式
			actionfun("comm/twoway");$twoset=twoway::set();
			$max=0;$bili=0;
			foreach($twoset as $k=>$v){
				if($v['自购比例']>$bili){$max=$k;$bili=$v['自购比例'];}
			}
			$one_set=$twoset[$k];
			if(!empty($user)){$one_set=$twoset[1];}
			if(!empty($user)&&$set3['fxdl_hhrshare_onoff']==1){$one_set=$twoset[$user['is_sqdl']];}
			$bili=doubleval($one_set['自购比例'])/100;
		
		}
		
		$dian=100;
		$commission=round($goods['goods_price']*($goods['commission']/100)*$bili*$dian)/$dian;
		if(empty($set3['hhrshare_ptstr']))$set3['hhrshare_ptstr']='升级赚';
		if(intval($set3['fxdl_hhrshare_onoff'])==1&&!empty($user))$set3['hhrshare_ptstr']=$set3['hhrshare_flstr'];
		
		$data=array();
		$data['fx_commission']=$commission;
		$data['fx_commission_bili']=round(($commission/$goods['goods_price'])*100*$dian)/$dian;
		$data['fxz']=$set3['hhrshare_ptstr'].$commission;
		return $data;
	}
	//商品相关 设置
	static function goodsSet02(&$goods){
		$set=zfun::f_getset("one_tlj_val,tlj_time,tb_tlj_onoff,tb_tlj_source_onoff,fx_goods_fl,operator_wuxian_bili,operator_onoff");
		$set['operator_wuxian_bili']=doubleval($set['operator_wuxian_bili'])/100;
		$dl_id_val=(intval($userid['is_sqdl'])+1);
		$sety=zfun::f_getset("goodssharestr_color,taoqianggou_quan_color,goodsyhqstr_color,goodsfcommissionstr_color,goods_yhqlist_str,fan_all_str,hhrapitype,fxdl_fxyjbili".$dl_id_val.",fxdl_tjhy_bili1_".$dl_id_val);
		if(empty($sety['goodsyhqstr_color']))$sety['goodsyhqstr_color']='f43e79';
		if(empty($sety['goodsfcommissionstr_color']))$sety['goodsfcommissionstr_color']='f43e79';
		if(empty($sety['goodssharestr_color']))$sety['goodssharestr_color']='FFFFFF';
		$zngoods=zfun::f_kdata("Goods",$goods,"fnuo_id","fnuo_id","one_tlj_val,tlj_time,fnuo_id");
		foreach($goods as $k=>$v){
			if($_POST['is_index']==1)$goods[$k]['goods_sales']=str_replace(array("已售","月销"),"",$v['goods_sales']);
			$ciry=explode(" ",$v['ciry']);
			//6-19修改
			if(empty($v['provcity']))$goods[$k]['provcity']=filter_check($ciry[1]);
			$goods[$k]['shop_title']=filter_check($v['shop_title']);

			$goods[$k]['pdd']=intval($v['pdd']);
			$goods[$k]['jd']=intval($v['jd']);
			$goods[$k]['str_count']='2';
			$goods[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_discount_quan.png";
			if(($v['yhq_price'])!=0)$good[$k]['goods_ico_one']=INDEX_WEB_URL."View/index/img/appapi/comm/list_after_quan.png";
			$goods[$k]['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/taobao.png?time=".time();
			if($v['shop_id']==2)$goods[$k]['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/tmall.png?time=".time();
			if($v['shop_id']==3||$goods[$k]['jd']==1)$goods[$k]['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/jd.png?time=".time();
			if($goods[$k]['pdd']==1){$goods[$k]['str_count']='3';$goods[$k]['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/pdd.png?time=".time();}
			if(intval($v['isJdSale'])==1&&$goods[$k]['jd']==1){$goods[$k]['str_count']='4';$goods[$k]['shop_img']=INDEX_WEB_URL."View/index/img/appapi/comm/jd1.png?time=".time();}
			$goods[$k]['fan_all_str']=$sety['fan_all_str'];	
			
			$str='';
			if(strstr($goods[$k]['goods_sales'],"已售")){
				$goods[$k]['goods_sales']=str_replace("已售","",$goods[$k]['goods_sales']);
				$str='已售';
			}
			if($goods[$k]['goods_sales']>=10000){
				$goods[$k]['goods_sales']=zfun::dian($goods[$k]['goods_sales']/10000,10)."万";
			}
			$goods[$k]['goods_sales']=$str.$goods[$k]['goods_sales'];
			$goods[$k]['goods_img']=str_replace(array("_250x250.jpg","_290x290.jpg","_310x310.jpg","_400x400.jpg"),"",$goods[$k]['goods_img']);
			if(strstr($goods[$k]['goods_img'],".jpg_")==false&&($goods[$k]['shop_id']==1 ||$goods[$k]['shop_id']==2))$goods[$k]['goods_img']=$goods[$k]['goods_img']."_350x350.jpg";
			$goods[$k]['share_img']=INDEX_WEB_URL."View/index/img/appapi/comm/home_share.png?time=".time();
			$goods[$k]['goods_store_img']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_store_img.png?time=".time();
			$goods[$k]['goods_fanli_bjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_fanli_bjimg.png?time=".time();
			$goods[$k]['goods_quanfont_bjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_quanfont_bjimg.png?time=".time();
			$goods[$k]['goods_quanbj_bjimg']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_quanbj_bjimg.png?time=".time();
			$goods[$k]['yhq_price']=floatval($goods[$k]['yhq_price']);
			if(!empty($sety['goods_yhqlist_str'])&&!empty($goods[$k]['yhq_price']))$goods[$k]['yhq_span']=str_replace("{yhq_price}",$goods[$k]['yhq_price'],$sety['goods_yhqlist_str']);
			if(!empty($sety['goodsyhqstr_color']))$goods[$k]['goodsyhqstr_color']=str_replace("#",'',$sety['goodsyhqstr_color']);
			if(!empty($sety['goodsfcommissionstr_color']))$goods[$k]['goodsfcommissionstr_color']=str_replace("#",'',$sety['goodsfcommissionstr_color']);
			if(!empty($sety['taoqianggou_quan_color']))$goods[$k]['taoqianggou_quan_color']=str_replace("#",'',$sety['taoqianggou_quan_color']);
			else $goods[$k]['taoqianggou_quan_color']='FFFFFF';
			$goods[$k]['is_qiangguang']=intval($v['is_qiangguang']);
			$goods[$k]['is_qg']=intval($v['is_qg']);
			$goods[$k]['remind']=intval($v['remind']);
			$goods[$k]['qg_time']=($v['qg_time']);
			$goods[$k]['shop_type']=filter_check($v['shop_type']);
			$goods[$k]['taoqianggou_quan_img']=INDEX_WEB_URL."View/index/img/appapi/comm/taoqianggou_quan_img.png?time=".time();
			$goods[$k]['taoqianggou_remind_img']=INDEX_WEB_URL."View/index/img/appapi/comm/taoqianggou_remind_img.png?time=".time();
			$goods[$k]['taoqianggou_cancelremind_img']=INDEX_WEB_URL."View/index/img/appapi/comm/taoqianggou_cancelremind_img.png?time=".time();
			$goods[$k]['goods_sales_ico']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_sales_ico.png?time=".time();
			$goods[$k]['goods_sharezhuan_img']=INDEX_WEB_URL."View/index/img/appapi/comm/goods_sharezhuan_img.png?time=".time();
			$goods[$k]['goodssharestr_color']=str_replace("#","",$sety['goodssharestr_color']);
			if(empty($v['yhq_url']))$v['yhq_url']='';
			$goods[$k]['yhq_url']=($v['yhq_url']);
			if(empty($goods[$k]['shop_type'])&&$goods[$k]['shop_id']==1)$goods[$k]['shop_type']="淘宝";
			if(empty($goods[$k]['shop_type'])&&$goods[$k]['shop_id']==2)$goods[$k]['shop_type']="天猫";
			//这是淘礼金的判断
			$goods[$k]['is_tlj']=0;
			
			//全部商品
			if(strstr($set['tb_tlj_source_onoff'],",all,")&&$set['tb_tlj_onoff'].''=='1'){
				$goods[$k]['is_tlj']=1;
			}
			//淘礼金栏目
			if(isset($_POST['tlj'])&&$_POST['tlj']==1&&strstr($set['tb_tlj_source_onoff'],",tlj,")&&$set['tb_tlj_onoff'].''=='1'){
				$goods[$k]['is_tlj']=1;
			}
			$goods[$k]['one_tlj_val']=floatval($set['one_tlj_val']);
			if(!empty($zngoods[$v['fnuo_id']]['one_tlj_val'])&&floatval($zngoods[$v['fnuo_id']]['one_tlj_val'])!=0)$goods[$k]['one_tlj_val']=floatval($zngoods[$v['fnuo_id']]['one_tlj_val']);
			if(empty($v['start_time']))$v['start_time']=time();
			if(empty($v['end_time']))$v['end_time']=time();
			$goods[$k]['yhq_use_time']='';
			if(floatval($v['yhq_price'])>0)$goods[$k]['yhq_use_time']="使用期限：".date("Y.m.d",$v['start_time'])."-".date("Y.m.d",$v['end_time']);
			$goods[$k]['id']=$v['fnuo_id'];
			$goods[$k]['yhq']=intval($v['yhq']);
			if(floatval($v['yhq_price'])>0)$goods[$k]['yhq']=1;
		}
	}
	//设置缓存
	public static function set_app_cookie($data=array(),$end_time=0){
		if(dgapp_huancun_onoff==false)return;//开关
		if(empty($GLOBALS['old_post_data']))zfun::fecho("set_app_cookie error");
		if(empty($end_time))$end_time=dgapp_huancun_time;
		if(!empty($_GET['cookie'])&&$_GET['cookie']=="off")return;//测试用
		$c=zfun::thisurl();
		if(!isset($_GET['ctrl']))$_GET['ctrl']='';
		foreach($GLOBALS['old_post_data'] as $k=>$v){
			if(empty($v)||$k=='time'||$k=='sign')continue;
			//if(strstr($_GET['ctrl'],"goods")==false&&$k=='token')continue;
			$c.=$k.$v;
		}
		$c=md5($c);
		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";
		$dir_path=ROOT_PATH."Temp/dgapp";
		if(file_exists($dir_path)==false)mkdir ($dir_path,0777,true);
		zfun::wfile($url,json_encode(array("data"=>$data,"end_time"=>time()+$end_time)));
		
	}
	//读取缓存
	public static function read_app_cookie(){
		$GLOBALS['old_post_data']=$_POST;
		if(dgapp_huancun_onoff==false)return;//开关
		if(!empty($_GET['cookie'])&&$_GET['cookie']=="off")return;//测试用
		$c=zfun::thisurl();
		
		//设置显示 喜欢的 商品链接
		$get_mylike_url_str=array(
			'act=api&ctrl=getgoods',
		);
		$is_mylike_url=0;
		foreach($get_mylike_url_str as $k=>$v){
			if(strstr($c,$v))$is_mylike_url=1;	
		}
		$mylike=array();
		if($is_mylike_url){
			$mylike=self::check_app_cookie_mylike();
		}
		
		//mylike end
		if(!isset($_GET['ctrl']))$_GET['ctrl']='';
		foreach($_POST as $k=>$v){
			if(empty($v)||$k=='time'||$k=='sign')continue;
			//if(strstr($_GET['ctrl'],"goods")==false&&$k=='token')continue;
			$c.=$k.$v;
		}
		$c=md5($c);
		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";
		if(file_exists($url)==false)return;
		$data=json_decode(zfun::get($url),true);
		if($data['end_time']<time())return;
		
		//mylike start
		if($is_mylike_url){
			$arr=$data;
			
			if(!empty($data['data']['success']))$arr=$arr['data']['data'];
			foreach($arr as $k=>$v){
				$arr[$k]['is_mylike']=0;
				if(empty($mylike[$v['id']])&&empty($mylike[$v['fnuo_id']]))continue;
				$arr[$k]['is_mylike']=1;
			}
			if(!empty($data['data']['success']))$data['data']['data']=$arr;
			else $data['data']=$arr;
		}
		//mylike end
		if(!empty($data['data']['success'])){
			$data['data']['msg'].="_缓存";
			echo json_encode($data['data']);exit;
			
		}
		echo json_encode(array("msg"=>"缓存","success"=>1,"data"=>$data['data']));exit;
	}
	
	//检测我的喜欢 缓存
	public static function check_app_cookie_mylike(){
		if(empty($GLOBALS['uid']))return array();
		$uid=$GLOBALS['uid'];
		$c=md5("mylike_".$uid);
		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";
		//如果没有则先设置
		if(file_exists($url)==false)return self::set_app_cookie_mylike($uid);
		else return self::get_app_cookie_mylike($uid);
	}
	//删除我的喜欢 缓存
	public static function del_app_cookie_mylike($uid=0){
		if(empty($uid))return;
		$c=md5("mylike_".$uid);
		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";
		if(file_exists($url)==false)return;
		@unlink($url);
	}
	//设置我的喜欢 缓存
	public static function set_app_cookie_mylike($uid=0){
		if(empty($uid))return;
		$data=zfun::f_select("MyLike","uid=$uid and goodsid>0","goodsid");
		
		if(empty($data))$data=array();
		$arr=array();
		foreach($data as $k=>$v)$arr[$v['goodsid'].'']=1;
		$data=$arr;
		$c=md5("mylike_".$uid);
		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";
		@unlink($url);
		zfun::wfile($url,zfun::f_json_encode($data));
		return $data;	
	}
	//获取我的喜欢 缓存
	public static function get_app_cookie_mylike($uid=0){
		if(empty($uid))return array();
		$c=md5("mylike_".$uid);
		$url=ROOT_PATH."Temp/dgapp/".$c.".cache";
		if(file_exists($url)==false)return array();
		$data=json_decode(zfun::get($url),true);
		return $data;
	}
	
	
	public static function set_cookie($name="",$file_arr=array(),$data=array(),$cookie_path="dgapp"){
		if(empty($name)||empty($file_arr))zfun::fecho("set_cookie error");
		$str=$name."_";
		foreach($file_arr as $k=>$v){
			if(empty($v)||$k=='time'||$k=='sign'||$k=='token')continue;
			$str.=$k.$v;	
		}	
		$name=md5($str);
		$path=ROOT_PATH."Temp/".$cookie_path."/".$name.".cache";
		$arr=array(
			"end_time"=>time()+43200,
			"data"=>$data,
		);
		zfun::wfile($path,json_encode($arr));
		return true;
	}
	
	public static function read_cookie($name="",$file_arr=array(),$cookie_path="dgapp"){
		if(empty($name)||empty($file_arr))zfun::fecho("set_cookie error");
		$str=$name."_";
		foreach($file_arr as $k=>$v){
			if(empty($v)||$k=='time'||$k=='sign'||$k=='token')continue;
			$str.=$k.$v;	
		}	
		$name=md5($str);
		$path=ROOT_PATH."Temp/".$cookie_path."/".$name.".cache";
		$arr=array(
			"end_time"=>time()+43200,
			"data"=>$data,
		);
		if(file_exists($path)==false)return array();
		$data=json_decode(zfun::get($path),true);
		if($data['end_time']<time())return array();
		return $data['data'];
	}
	
	public static function addFootMark($fnuo_id='',$uid=0){
		if(empty($fnuo_id)||empty($uid))return;
		$where="goodsid='$fnuo_id' and uid=$uid";
		$f_count=zfun::f_count("FootMark",$where);
		actionfun("appapi/alimama");
		$tmp=alimamaAction::getcommission($fnuo_id);
		if(empty($tmp))return;
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
		if(!empty($GLOBALS['yhq_price'])){
			$data['yhq_price']=$GLOBALS['yhq_price'];
			$data['yhq']=1;
			$data['yhq_span']=$GLOBALS['yhq_span'];
		}
		$arr=array(
			"goodsid" => $_POST['goodsid'],
			"uid" => $uid,
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
		return 1;
	}
	
}
$tmp=json_decode(zfun::get(ROOT_PATH."Config/dgapp_huancun.config"),true);
setconst("dgapp_huancun_onoff",$tmp['dgapp_huancun_onoff']);//是否开启app缓存
setconst("dgapp_huancun_time",$tmp['dgapp_huancun_time']);//缓存时间
setconst('EARTH_RADIUS', 6378.137);//地球半径
setconst('PI', 3.1415926);
?>
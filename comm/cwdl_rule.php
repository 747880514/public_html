<?php
include_once ROOT_PATH."comm/zfun.php";

class cwdl_rule{
	public static function setset(){
		$set=zfun::f_getset("fxdl_lv");
		$fxdl_lv=$set['fxdl_lv'];
		$str="fxdl_lv,fxdl_zdsj_onoff,fxdl_zdssdl_onoff";
		for($i=1;$i<=$fxdl_lv;$i++){
			$str.=",fxdl_ljzt".$i;
			$str.=",fxdl_jzlj".$i;
			$str.=",fxdl_zgfl".$i;
			$str.=",fxdl_name".$i;
			$str.=",fxdl_gwbili".$i;
			$str.=",fxdl_pay_tj".$i;
		}
		$set=zfun::f_getset($str);
		return $set;
	}
	public static function yq_friend($tgid=0,$t=0){//邀请下级累积
		if(empty($tgid))return false;
		$count=zfun::f_count("User","extend_id='$tgid'");
		$user=zfun::f_row("User","id='$tgid'","yq_xj_count,is_sqdl");
		if($user['yq_xj_count']<$count&&!empty($count)){
			zfun::f_update("User","id='$tgid'",array("yq_xj_count"=>$count));//修改下级的累积数量

		}else $count=$user['yq_xj_count'];
	    $count=intval($count);
	   	return $count;

	}

	static function yq_next_friend($uid=0,$t=0){
		if(empty($uid))return 0;
		$user=zfun::f_row("User","id='{$uid}'","id,yq_all_count");
		if(empty($user))return 0;
		actionfun("comm/order");
		$result=order::get_lower($uid);
		//判断是否要更新 用户团队人数
		if($user['yq_all_count'].''!=$result['lower_count'].''){
			zfun::f_update("User","id='{$uid}'",array("yq_all_count"=>$result['lower_count']));
		}
		//总人数
		$count=count($result['user_arr'])-1;
		return $count;
	}

	/*public static function yq_next_friend($tgid=0,$t=0){//邀请下级下下级累积
		if(empty($tgid))return false;
		$user=zfun::f_row("User","id='$tgid'","yq_all_count,is_sqdl");
		$vip=intval($user['is_sqdl']+1);
		$set=zfun::f_getset("fxdl_tjhydj_".$vip);
		$djtg_lv=intval($set["fxdl_tjhydj_".$vip]);
		if(empty($djtg_lv))$djtg_lv=3;
		$data_all=self::getcarr($tgid,"extend_id",$djtg_lv);//下级下下级的信息
		$count=$data_all['count'];
		$count=intval($count);
		return $count;

	}*/

	//累积自购佣金
	public static function lj_buy($tgid=0,$t=0){
		if(empty($tgid))return false;
		$user=zfun::f_row("User","id='$tgid'","gm_all_commission,is_sqdl");
		//订单佣金
		$count=zfun::f_sum("Order","status='订单结算' and uid='$tgid'","commission");
		if($user['gm_all_commission']<$count&&!empty($count)){
			zfun::f_update("User","id='$tgid'",array("gm_all_commission"=>$count));//修改返利

		}else $count=$user['gm_all_commission'];
	    $count=floatval($count);
		return $count;

	}
	//自动升级操作
	public static function zdsj_doing($tgid=0,$t=0){
		if(empty($tgid))return false;
		$arr=array();

		//百里.修改前（计算直接下级总人数）
		// $arr['fxdl_ljzt']=self::yq_friend($tgid);

		//百里.修改后（计算直接下级蒜苗）
		$arr['fxdl_ljzt']= zfun::f_count("User","extend_id='$tgid' and is_sqdl = 2");


		$arr['fxdl_jzlj']=self::yq_next_friend($tgid);
		$arr['fxdl_zgfl']=self::lj_buy($tgid);
		if($t==1)return $arr;
		$user=zfun::f_row("User","id='$tgid'");
		$set=self::setset();

		if(intval($set['fxdl_zdsj_onoff'])==0)return false;//如果开关是关闭不能自动升级
		$fxdl_lv=$set['fxdl_lv'];
		$vip=intval($user['is_sqdl'])+1;
		$sj_lv=0;

		//条件是否满足

		for($i=1;$i<=$fxdl_lv;$i++){
			if($vip>=$i)continue;

			$check_dl=self::cwdl_tj($user,$i,$arr,1);//条件
			if($check_dl==false)continue;
			$sj_lv=$i;

		}

		if(intval($sj_lv)==0)return;
		if(intval($sj_lv)<=$vip)return;
		/****************判断是不是要付款的，付款的不能自动升级*****************/
		$sj_tj=intval($set['fxdl_pay_tj'.$sj_lv]);
		if($sj_tj==0||$sj_tj==1)return false;

		/*********************************/
		$count_dljl=zfun::f_count("DLList","uid='$tgid' and checks=1");

		//if(empty($count_dljl)){
			$arr=array(
				"time"=>time(),
				"phone"=>filter_check($user['phone']),
				"name"=>filter_check($user['nickname']),
				"uid"=>intval($tgid),
				"dl_dj"=>intval($sj_lv)-1,
				"jnMoney"=>0,
				"checks"=>1,
				"is_pay"=>3,
			);
			$tmp=array();
			$tmp['zdsj']="zdsj";
			$tmp['fxdl_ljzt']=$arr['fxdl_ljzt'];
			$tmp['fxdl_jzlj']=$arr['fxdl_jzlj'];
			$tmp['fxdl_zgfl']=$arr['fxdl_zgfl'];
			$arr['data']=json_encode($tmp);
			zfun::f_insert("DLList",$arr);
		//}

		//存到记录表
		if($sj_lv>1){
			$msg="升级成为".$set['fxdl_name'.$sj_lv];
			$data=array("dqrs"=>$count,"sj_dl"=>$sj_lv);
			$result=self::adddetail($msg,$tgid,0,$data,time());
			if($result==false)return false;
		}
		$times=$user['is_sqdl_time'];
		if(empty($user['is_sqdl_time']))$times=time();
		zfun::f_update("User","id='$tgid'",array("is_sqdl"=>($sj_lv-1),"is_sqdl_time"=>$times));//满足条件修改等级
		return ($sj_lv-1);
	}
	//成为代理条件
	public static function cwdl_tj($user=array(),$id,$arr,$t=0){

		if(empty($user))return false;
		if(empty($arr))return false;
		if(empty($id))return false;
		$uid=intval($user['id']);
		$set=zfun::f_getset("fxdl_sjmodel_onoff,fxdl_zdssdl_onoff,fxdl_ljzt".$id.",fxdl_jzlj".$id.",fxdl_zgfl".$id);

		$sjms=intval($set['fxdl_sjmodel_onoff']);

		$count=intval($arr['fxdl_ljzt']);
		$count1=intval($arr['fxdl_jzlj']);
		$count2=floatval($arr['fxdl_zgfl']);
		$set['fxdl_ljzt'.$id]=doubleval($set['fxdl_ljzt'.$id]);
		$set['fxdl_jzlj'.$id]=doubleval($set['fxdl_jzlj'.$id]);
		$set['fxdl_zgfl'.$id]=doubleval($set['fxdl_zgfl'.$id]);
		$tj_arr=array(
			1=>array(
				"have_set"=>1,
				"have_full"=>0,
			),
			2=>array(
				"have_set"=>1,
				"have_full"=>0,
			),
			3=>array(
				"have_set"=>1,
				"have_full"=>0,
			),
		);

		//判斷 是否已經設置
		if(empty($set['fxdl_ljzt'.$id]))$tj_arr[1]['have_set']=0;
		if(empty($set['fxdl_jzlj'.$id]))$tj_arr[2]['have_set']=0;
		if(empty($set['fxdl_zgfl'.$id]))$tj_arr[3]['have_set']=0;
		//判斷是否滿足條件
		if($count>=intval($set['fxdl_ljzt'.$id])&&!empty($set['fxdl_ljzt'.$id]))$tj_arr[1]['have_full']=1;
		if($count1>=intval($set['fxdl_jzlj'.$id])&&!empty($set['fxdl_jzlj'.$id]))$tj_arr[2]['have_full']=1;
		if($count2>=floatval($set['fxdl_zgfl'.$id])&&!empty($set['fxdl_zgfl'.$id]))$tj_arr[3]['have_full']=1;
		zfun::isoff($tj_arr);
		$sj=1;
		$sj_count=0;
		foreach($tj_arr as $k=>$v){//全部条件满足情况下的判断
			//如果 後台設置 但是沒有满足条件
			if($v['have_full']==0&&$tj_arr[$k]['have_set']==1)$sj=0;
			if($v['have_full']==0&&$tj_arr[$k]['have_set']==0)$sj_count++;
		}
		if($sjms==1)$sj=0;
		foreach($tj_arr as $k=>$v){//一个条件满足情况下的判断
			//如果 後台設置 也满足条件
			if($v['have_full']==1&&$tj_arr[$k]['have_set']==1&&$sjms==1)$sj=1;
		}
		//如果三个都是0
		if($sj_count==3)$sj=0;
		zfun::isoff($sj,1);
		if($sj==1)return true;
		return false;

	}
	/**********非接口*********/
	//获取下级方法
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

	public static function adddetail($msg='',$uid=0,$type=0,$data=array(),$time=0){
		$arr=array("time"=>time(),"uid"=>$uid,"detail"=>$msg,"type"=>$type);
		if(!empty($data)){
			$arr['data']=zfun::f_json_encode($data);
			if(!empty($data['oid']))$arr['oid']=$data['oid'];
		}
		if(!empty($time))$arr['time']=$time;
		$result=zfun::f_insert('Interal',$arr);
		if($result==false)return false;
		return true;
	}
}
?>
<?php
actionfun("comm/actfun");
class update_goods_do{
	static function update($order=array(),$user=array()){
		$set=zfun::f_getset("update_goods_lvup_onoff,update_goods_onoff,operator_name,fxdl_name" . ($order['update_type']+1));
		if($set['update_goods_onoff']==0)return 0;
		$uid=$user['id'];
		$oid=$order['oid'];
		//修改会员等级 判断是 续期还是 新开
		$vip_set=self::getlvlist();
		$one_vip_set=$vip_set[$order['update_type']];

		if($order['update_type']!='operator'){
			$user_arr=array(
				"is_sqdl"=>$order['update_type'],
				"hhr_gq_time"=>time()+doubleval($one_vip_set['day'])*86400,
			);
			if($user['is_sqdl']>intval($user_arr['is_sqdl']))return 0;
			$user['hhr_gq_time']=doubleval($user['hhr_gq_time']);
			if($user['hhr_gq_time']!='0'&&$user['hhr_gq_time']>time()){//判断是否续费
				$user_arr['hhr_gq_time']=$user['hhr_gq_time']+doubleval($one_vip_set['day'])*86400;
			}
		}else{
			$user_arr=array(
				"operator_lv"=>1,
				"operator_time"=>time()+doubleval($one_vip_set['day'])*86400,
			);
			if($user['operator_lv']>intval($user_arr['operator_lv']))return 0;
			$user['operator_time']=doubleval($user['operator_time']);
			if($user['operator_time']!='0'&&$user['operator_time']>time()){//判断是否续费
				$user_arr['operator_time']=$user['operator_time']+doubleval($one_vip_set['day'])*86400;
			}
		}


		zfun::f_update("User","id='{$uid}'",$user_arr);//修改会员状态
		//修改订单
		zfun::f_update("Updateorder","oid='{$oid}'",array("is_check"=>1));
		actionfun("comm/hhr_td_dlf");
		$name=$set["fxdl_name" . ($order['update_type']+1)];
		if($order['update_type']=='operator'){
			$name=$set['operator_name'];
		}

		hhr_td_dlf::update_commission($uid,doubleval($order['payment']),$name);
		self::sjdl_jl($uid,$order['update_type'],$name);

		//百里.修改前
		// self::fanli_3($uid,$order['update_type'],doubleval($order['payment']));
		//百里.修改后
		self::fanli_6($uid,$order['update_type'],doubleval($order['payment']));

		return 1;
	}
	public static function sjdl_jl($uid=0,$dl_dj=0,$name=''){
		if(empty($uid))return;if(empty($dl_dj))return;
		$set=zfun::f_getset("fxdl_ewjl".($dl_dj+1));
		$ewjl=floatval($set['fxdl_ewjl'.($dl_dj+1)]);
		if(empty($ewjl))return;
		$arr1 = array(
			'uid' => $uid,
			'interal' => $ewjl ,
			'detail' => "升级".$name."，奖励".$ewjl."元",
			'time' => time() ,
			'type' => 0,
		 );
		$result=zfun::f_insert("Interal",$arr1);
		zfun::addval("User","id='$uid'",array("commission"=>$ewjl));
	}
	static function getlvlist(){
		$set=zfun::f_getset("fxdl_lv,operator_name,operator_onoff,operator_valid_day");
		$str='';
		$str1='';
		for($i=1;$i<=$set['fxdl_lv'];$i++){
			$str.=",fxdl_name".$i;
			$str1.=",fxdl_hydaylv".$i;
		}
		$str=substr($str,1);
		$str1=substr($str1,1);
		$hydaylv=zfun::f_getset($str1);
		$data=zfun::f_getset($str);

		$tmp=array();
		$data=array_values($data);
		foreach($data as $k=>$v){
			$tmp[$k]['name']=$v;
			$tmp[$k]['lv']=$k;
			if(intval($hydaylv['fxdl_hydaylv'.($k+1)])==0)$hydaylv['fxdl_hydaylv'.($k+1)]='365';
			$tmp[$k]['day']=$hydaylv['fxdl_hydaylv'.($k+1)];
		}
		unset($tmp[0]);
		if(intval($set['operator_onoff'])==1){
			if(intval($set['operator_valid_day'])==0)$set['operator_valid_day']='365';
			$tmp['operator']=array(
				"name"=>$set['operator_name'],
				"lv"=>'operator',
				"day"=>$set['operator_valid_day'],
			);
		}
		return $tmp;
	}
	//返还代理费
	public static function fanli_3($uid=0,$level=0,$money=0){
		$money=floatval($money);
		if(empty($uid))return;
		if(empty($level))return;//等级
		if(empty($money))return;//金额

		$str="fxdl_name,fxdl_lv,operator_name,operator_name_2,fxdl_tjdldj_operator_1,fxdl_tjdldj_operator_2";
		$is_fl=0;
		for($i=1;$i<=20;$i++){
			$str.=',fxdl_money'.$i.',fxdl_tjdldj_'.$i;
			$str.=',fxdl_tjdl_bili1_'.$i.',fxdl_tjdl_bili2_'.$i.',fxdl_tjdl_bili3_'.$i.',fxdl_tjdl_bili4_'.$i.',fxdl_tjdl_bili5_'.$i;
			$str.=',fxdl_tjdl_bili6_'.$i.',fxdl_tjdl_bili7_'.$i.',fxdl_tjdl_bili8_'.$i.',fxdl_tjdl_bili9_'.$i.',fxdl_tjdl_bili10_'.$i;
			$str.=',fxdl_tjdl_bili_operator1_'.$i.',fxdl_tjdl_bili_operator2_'.$i.',fxdl_tjdl_bili_operator3_'.$i.',fxdl_tjdl_bili_operator4_'.$i.',fxdl_tjdl_bili_operator5_'.$i;
			$str.=',fxdl_tjdl_bili_operator6_'.$i.',fxdl_tjdl_bili_operator7_'.$i.',fxdl_tjdl_bili_operator8_'.$i.',fxdl_tjdl_bili_operator9_'.$i.',fxdl_tjdl_bili_operator10_'.$i;
		}
		$set=zfun::f_getset($str);
		$fxdl_name=$set["fxdl_name".($level+1)];
		if($level=='operator')$fxdl_name=$set["operator_name"];
		$fxdl_lv=0;
		if(empty($set['fxdl_lv']))$set['fxdl_lv']=3;
		$djdata=self::getp($uid,"extend_id",intval($set['fxdl_lv']));

		if(empty($djdata))return;
		$user=zfun::f_row("User","id='$uid'");
		foreach($djdata as $k=>$v){
			//如果他不是代理也不是用户不给
			//if(intval($v['operator_lv'])==0&&$v['is_sqdl']==0){$is_fl=0;continue;}
			if(intval($v['is_sqdl'])>=0){//如果是普通或代理时的比例
				$id=$lv=intval($v['is_sqdl'])+1;
				if(floatval($set['fxdl_tjdldj_'.$lv])<$v['lv']){$is_fl=0;}
				$bili=$set["fxdl_tjdl_bili".$v['lv']."_".$lv];
			}
			if($v['operator_lv']==1){//如果是运营商时的比例
				if(floatval($set['fxdl_tjdldj_operator_1'])<$v['lv']){$is_fl=0;}
				$bili=$set["fxdl_tjdl_bili_operator".$v['lv']."_1"];
			}
			if($v['operator_lv']==2){//如果是联合创始人时的比例
				if(floatval($set['fxdl_tjdldj_operator_2'])<$v['lv']){$is_fl=0;}
				$bili=$set["fxdl_tjdl_bili_operator".$v['lv']."_2"];
			}

			if(floatval($bili)==0){$is_fl=0;continue;}
			$bili=floatval($bili/100);
			$commission=zfun::dian($money*floatval($bili));
			$result=zfun::addval("User","id=".$v['uid'],array("commission"=>$commission));
			if(empty($result)){$is_fl=0;continue;}
			$data=array(
				"gl_uid"=>$uid,//升级的用户
				"mem_level"=>$level,
				"gm_money"=>$money,
			);
			//百里
			$user['phone'] = !empty($user['phone']) ? $user['phone'] : $user['nickname'];
			$result=self::adddetail("推荐【".$user['phone']."】成为".$fxdl_name." ".$v['lv']."级 返利 ".$commission,$v['uid'],0,$data,time(),$commission);
			if(empty($result)){$is_fl=0;continue;}
			$is_fl=1;
		}


	}

	/**
	 * [fanli_6 百里.花蒜礼包返佣.MAX6级]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2018-11-19T14:10:25+0800
	 * @param    integer                  $uid   [description]
	 * @param    integer                  $level [description]
	 * @param    integer                  $money [description]
	 * @return   [type]                          [description]
	 */
	public static function fanli_6($uid=0,$level=0,$money=0){
		$money=floatval($money);
		if(empty($uid))return;
		if(empty($level))return;//等级
		if(empty($money))return;//金额
		$str="fxdl_name,fxdl_lv,operator_name,operator_name_2,fxdl_tjdldj_operator_1,fxdl_tjdldj_operator_2";
		$is_fl=0;
		for($i=1;$i<=20;$i++){
			$str.=',fxdl_money'.$i.',fxdl_tjdldj_'.$i;
			$str.=',fxdl_tjdl_bili1_'.$i.',fxdl_tjdl_bili2_'.$i.',fxdl_tjdl_bili3_'.$i.',fxdl_tjdl_bili4_'.$i.',fxdl_tjdl_bili5_'.$i;
			$str.=',fxdl_tjdl_bili6_'.$i.',fxdl_tjdl_bili7_'.$i.',fxdl_tjdl_bili8_'.$i.',fxdl_tjdl_bili9_'.$i.',fxdl_tjdl_bili10_'.$i;
			$str.=',fxdl_tjdl_bili_operator1_'.$i.',fxdl_tjdl_bili_operator2_'.$i.',fxdl_tjdl_bili_operator3_'.$i.',fxdl_tjdl_bili_operator4_'.$i.',fxdl_tjdl_bili_operator5_'.$i;
			$str.=',fxdl_tjdl_bili_operator6_'.$i.',fxdl_tjdl_bili_operator7_'.$i.',fxdl_tjdl_bili_operator8_'.$i.',fxdl_tjdl_bili_operator9_'.$i.',fxdl_tjdl_bili_operator10_'.$i;

			//百里
			$str .= ',fxdl_name'.$i;
		}

		$set=zfun::f_getset($str);
		$fxdl_name=$set["fxdl_name".($level+1)];
		if($level=='operator')$fxdl_name=$set["operator_name"];

		$user=zfun::f_row("User","id='$uid'");

		//获取推荐人信息
		$extend_user = "";
		if($user['extend_id'] > 0)
		{
			$extend_user = zfun::f_row("User", "id = '{$user['extend_id']}'", "nickname,phone");
			$extend_user = $extend_user['nickname'] ? $extend_user['nickname'] : $extends['phone'];
		}

		//百里.重新计算比例
		$djdata = self::hs_commission($uid);
		if(empty($djdata))return;

		foreach($djdata as $k=>$v){

			$bili = $v['bili'];

			if($bili > 0)
			{
				if(floatval($bili)==0){$is_fl=0;continue;}
				$bili=floatval($bili/100);
				$commission=zfun::dian($money*floatval($bili));
				$result=zfun::addval("User","id=".$v['uid'],array("commission"=>$commission));
				if(empty($result)){$is_fl=0;continue;}
				$data=array(
					"gl_uid"=>$uid,//升级的用户
					"mem_level"=>$level,
					"gm_money"=>$money,
					"is_sqdl" => $v['is_sqdl'],
					"bili"=>$bili,
				);
				$jibie = $k + 1;
				// 百里.修改前
				// $result=self::adddetail("推荐【".$user['phone']."】成为".$fxdl_name." ".$jibie."级 返利 ".$commission,$v['uid'],0,$data,time(),$commission);
				// 百里.修改后
				$user['nickname'] = !empty($user['nickname']) ? $user['nickname'] : $user['phone'];
				$result=self::adddetail($extend_user."邀请【".$user['nickname']."】成为".$fxdl_name." 获得".$v['bili_str'].$commission,$v['uid'],0,$data,time(),$commission);
				if(empty($result)){$is_fl=0;continue;}
				$is_fl=1;
			}
		}
	}
	public static function adddetail($msg='',$uid=0,$type=0,$data=array(),$time=0,$interal=0){
		$arr=array("time"=>time(),"uid"=>$uid,"detail"=>$msg,"type"=>$type,"interal"=>$interal);
		if(!empty($data)){
			$arr['data']=zfun::f_json_encode($data);
			if(!empty($data['oid']))$arr['oid']=$data['oid'];
		}
		if(!empty($time))$arr['time']=$time;
		$result=zfun::f_insert('Interal',$arr);
		if($result==false)return false;
		return true;
	}

	//获取上级
	public static function getp($uid,$tidname="extend_id",$maxlv=9){
		if(empty($uid)||empty($tidname))return array();
		$user=zfun::f_row("User","id='$uid'","id,extend_id");
		if(empty($user[$tidname]))return array();
		$pid=$user[$tidname];
		$arr=array();$lv=0;
		$addwhere="";
		if($tidname=='realid')$addwhere=" and ";
		do{
			$lv++;
			$user=zfun::f_row("User","id='$pid'","id,extend_id,is_sqdl,operator_lv");
			if(!empty($user['id'])){
				$arr[$lv]['lv']=$lv;
				$arr[$lv]['uid']=$user['id'];
				$arr[$lv]['is_sqdl']=$user['is_sqdl'];
				$arr[$lv]['operator_lv']=$user['operator_lv'];
				$pid=$user[$tidname];
			}
		}while(!empty($user[$tidname])&&$maxlv>$lv);
		return $arr;
	}

	/**
	 * [hs_getp 百里.获取上级 2+5模式]
	 * max 2蒜苗+max 5蒜花
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2018-11-19T14:16:00+0800
	 * @param    [type]                   $uid     [description]
	 * @param    string                   $tidname [description]
	 * @param    integer                  $maxlv   [description]
	 * @return   [type]                            [description]
	 */
	public static function hs_getp($uid){
		$tidname = "extend_id";
		$max_lv2 = 2;	//蒜苗最大数
		$max_lv3 = 5;	//蒜花最大数

		$lv = $lv2 = $lv3 = 0;

		if(empty($uid)||empty($tidname))return array();

		$user=zfun::f_row("User","id='$uid'","id,extend_id");

		if(empty($user[$tidname]))return array();

		$pid=$user[$tidname];

		$arr=array();

		do{
			$lv++;
			$user=zfun::f_row("User","id='$pid'","id,extend_id,is_sqdl,operator_lv");

			if(!empty($user['id']) && $user['is_sqdl'] >= '2'){
				// 【招商会】
				if(($user['is_sqdl'] == 2 && $lv2 < $max_lv2) OR ($user['is_sqdl'] >= 3 && $lv3 < $max_lv3))
				// if(($user['is_sqdl'] == 2 && $lv2 < $max_lv2) OR ($user['is_sqdl'] == 3 && $lv3 < $max_lv3))
				{

					$arr[$lv]['lv']=$lv;
					$arr[$lv]['uid']=$user['id'];
					$arr[$lv]['is_sqdl']=$user['is_sqdl'];
					$arr[$lv]['operator_lv']=$user['operator_lv'];

					if($user['is_sqdl'] == 2)
					{
						$lv2++;
					}
					if($user['is_sqdl'] == 3)
					{
						$lv2 = $max_lv2;	//一旦出现了蒜花，将不再查询蒜苗
						$lv3++;
					}
				}
			}
			$pid=$user[$tidname];
		}
		while(!empty($user[$tidname])&&($lv2 < $max_lv2||$lv3 < $max_lv3));

		$arr = array_values($arr);

		return $arr;
	}

	/**
	 * [hs_bili_mod 百里.重新计算比例]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2019-01-10T11:48:21+0800
	 * @param    [type]                   $lv_total [description]
	 * @return   [type]                             [description]
	 */
	public static function hs_bili_mod($lv_total)
	{
		$bili_mod = array();

		if ($lv_total[2] == 0)
		{
			switch ($lv_total[3]) {
				case '1':
					$bili_mod[] = 20;//40 + 8 + 6;
					break;
				case '2':
					$bili_mod[] = 20;//40 + 2;
					$bili_mod[] = 10;//8 + 4;
					break;
				case '3':
					$bili_mod[] = 20;//40 + 2;
					$bili_mod[] = 10;//8 + 2;
					$bili_mod[] = 2;
					break;
				case '4':
					$bili_mod[] = 20;//40 + 2;
					$bili_mod[] = 10;//8;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					break;
				case '5':
					$bili_mod[] = 20;//40;
					$bili_mod[] = 10;//8;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					break;
			}
		}
		elseif ($lv_total[2] == 1)
		{
			$bili_mod[] = 30;
			switch ($lv_total[3]) {
				case '1':
					$bili_mod[] = 10;//20 + 8 + 6;
					break;
				case '2':
					$bili_mod[] = 10;//20 + 2;
					$bili_mod[] = 8 + 4;
					break;
				case '3':
					$bili_mod[] = 10;//20 + 2;
					$bili_mod[] = 8 + 2;
					$bili_mod[] = 2;
					break;
				case '4':
					$bili_mod[] = 10;//20 + 2;
					$bili_mod[] = 8;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					break;
				case '5':
					$bili_mod[] = 10;//20;
					$bili_mod[] = 8;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					break;
			}
		}
		elseif ($lv_total[2] == 2)
		{
			$bili_mod[] = 30;
			$bili_mod[] = 15;
			switch ($lv_total[3]) {
				case '1':
					$bili_mod[] = 8 + 6;
					break;
				case '2':
					$bili_mod[] = 8 + 2;
					$bili_mod[] = 2 + 2;
					break;
				case '3':
					$bili_mod[] = 8 + 2;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					break;
				case '4':
					$bili_mod[] = 8;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					break;
				case '5':
					$bili_mod[] = 8;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					$bili_mod[] = 2;
					$bili_mod[] = 0;
					break;
			}
		}

		return $bili_mod;
	}



	/**
	 * [hs_commission 计算各层级分佣比]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2019-01-10T14:18:43+0800
	 * @param    [type]                   $uid [description]
	 * @return   [type]                        [description]
	 */
	public static function hs_commission($uid)
	{
		//查询会员的所有上级
		actionfun("comm/twoway");
		$extends = twoway::get_extend_all($uid);
		$extends = array_values($extends);

		unset($extends[0]);	//删除当前会员单元

		$bili_all = 0;	//返利的总百分比
		$extends_get = $is_sqdl = array();	//可以拿到返佣的上级
		$level = array('0'=>0, '1'=>0, '2'=>0, '3'=>6, '4'=>14, '5'=>24, '6'=>36);	//等级对应返利百分比(极差算法)
		$bili_str = array('直接奖励','间推奖励','管理奖励','团队分红','团队分红','团队分红');
		$k = 0;
		foreach ($extends as $key => &$value)
		{
			$value['uid'] = $value['id'];

			if($key == 1)	//直推单元
			{
				//比例=等级对应返利比例 - 已返的总比例 + 直推固定比例
				$value['bili'] = $level[$value['is_sqdl']] - $bili_all;
				$value['bili'] = max($value['bili'], 0);
				$bili_all += $value['bili'];

				$value['bili_str'] = "团队分红";//$bili_str[$k];
				$k++;

				$extends_get[] = $value;
				$is_sqdl[] = $value['is_sqdl'];

				//拆分处理
				$value['bili'] = 20;
				$value['bili_str'] = "直推奖励";
				$extends_get[] = $value;

			}
			elseif($key == 2)	//间推单元
			{
				//比例=等级对应返利比例 - 已返的总比例 + 直推固定比例
				$value['bili'] = $level[$value['is_sqdl']] - $bili_all;
				$value['bili'] = max($value['bili'], 0);
				$bili_all += $value['bili'];

				$value['bili_str'] = "团队分红";//$bili_str[$k];
				$k++;

				$extends_get[] = $value;
				$is_sqdl[] = $value['is_sqdl'];

				//拆分处理
				$value['bili'] = 10;
				$value['bili_str'] = "间推奖励";
				$extends_get[] = $value;
			}
			else
			{
				//非平级单元（>=蒜花）
				if(!in_array($value['is_sqdl'], $is_sqdl) && $value['is_sqdl'] >= 3)
				{
					//比例=等级对应返利比例 - 已返的总比例
					$value['bili'] = $level[$value['is_sqdl']] - $bili_all;
					$value['bili'] = max($value['bili'], 0);
					$bili_all += $value['bili'];

					$value['bili_str'] = $bili_str[$k];
					$k++;

					$extends_get[] = $value;
					$is_sqdl[] = $value['is_sqdl'];
				}
			}
		}

		return $extends_get;
	}

}
?>
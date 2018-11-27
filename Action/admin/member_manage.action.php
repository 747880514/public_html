<?php
fun("ztp");fun("admin");
class member_manageAction extends Action{
	
	//提现公共where
	static function tx_comm_where(&$where=""){
		$_GET=filter_check($_GET);
		if(!empty($_GET['uid']))$where.=" and uid=".intval($_GET['uid']);
		if(!empty($_GET['phone'])){
			$user=zfun::f_row("User","phone='".$_GET['phone']."'","id");	
			if(empty($user))$where.=" and id='-1'";
			else $where.=" and uid=".$user['id'];
		}
		if(!empty($_GET['start_time']))$where.=" and time >=".intval(strtotime($_GET['start_time']));
		if(!empty($_GET['end_time']))$where.=" and time <=".intval(strtotime($_GET['end_time']));
	}
	
	/*提现审核*/
    public function agency_audit(){
		if(!empty($_GET['daochu'])&&!empty($_GET['start_time'])&&!empty($_GET['end_time'])){$_GET['type']='3,8';self::daochu();return;}//导出excel
		ztp::title("提现审核");
		zfun::f_setadminurl();	
		$where="type IN(3,8)";
		self::tx_comm_where($where);
		if(!empty($_POST['page'])){
          $_GET['p']=$_POST['page'];  
        }
        $GLOBALS['ztp']['jump']="{link:member_manage-agency_audit}";
        ztp::addjs("comm/foot_jump.js");
        ztp::addjs("member_manage/daochu.js");
		$interal=zfun::f_goods("Authentication",$where,NULL,"time DESC",NULL,12);  
		$statusarr=array(0=>"审核中",1=>"审核通过",2=>"审核不通过",); 
		$edit_url=urlencode(self::getUrl("member_manage","audit_edit"));//编辑的链接
		$del_url=urlencode(self::getUrl("member_manage","audit_del"));//删除的链接
		foreach($interal as $k=>$v){
			$data[$k]['data']=json_decode($v['data'],true);
			foreach($data[$k]['data'] as $k1=>$v1)$interal[$k][$k1]=$v1;	
			$interal[$k]['status']=$statusarr[$v['audit_status']];
			$interal[$k]['time']=date("Y-m-d H:i:s",$v['time']);
			$interal[$k]['caozuo']=ztp::cstrarr("button=编辑|{$edit_url}&id-".$v['id']." ");	
		}
		/*html*/
		/**************************/
        $GLOBALS['ztp']['daochu']="{link:member_manage-agency_audit}";
		ztp::addtop('a',"导出","javascript:void(0)","name=daochu");
		ztp::addtop('text',"开始时间","","time=on get=on name=start_time placeholder='开始时间'");
		ztp::addtop('text',"结束时间","","time=on get=on name=end_time placeholder='结束时间'");
		ztp::addtop("text","用户的id","","name=uid get=on");
		ztp::addtop("text","手机号","","name=phone get=on");
		ztp::addtop("submit","查询");
		ztp::settableaction(self::getUrl("member_manage","audit_del"));//设置表单action链接
		$str="ID`=id/width-50,用户id=uid/width-50,提现事件=info/width-200,提现金额=money/width-100,审核状态=status/width-100,申请时间=time/width-150,";
		$str.="操作=caozuo/width-200";
		ztp::addtable($str,$interal);
        ztp::play();
    }
    /**
     * 导出提现审核
     * @return [type] [description]
     */
    public function dc_sh(){
    	$sort="time desc";
    	$data=zfun::f_select("Authentication",$where,null,null,null,$sort);
    	$statusarr=array(0=>"审核中",1=>"审核通过",2=>"审核不通过",); 
        foreach($data as $k=>$v){
            $data[$k]['info']=$v['info'];
            $data[$k]['money']=$v['money'];
            $data[$k]['status']=$statusarr[$v['audit_status']];
            $data[$k]['time']=date("Y-m-d H-i-s",$v['time']);
        }
        $namearr=array(
            "info"=>"提现事件",
            "money"=>"提现金额",
            "status"=>"审核状态",
            "time"=>"申请时间",
        );
        fun("xls");
        xls::export($data,$namearr,"提现审核");    
    }
	/*提现删除*/
	public function audit_del(){
		$ids=$_POST['id'];
		if(!empty($_GET['id'])){
			$ids=intval($_GET['id']);	
		}
		if(!empty($_POST['ch'])){
			$ids=implode(",",$_POST['ch']);	
		}
		$where="id IN($ids)";
		$data=zfun::f_select("Authentication",$where);
		foreach($data as $k=>$v){
			rz::add("删除提现记录".$v['info']." 用户id ".$v['uid'],3);	
		}
		
		$result=zfun::f_delete("Authentication","id IN($ids)");
		admin::getdel($result);
	}
	/*提现审核*/
	public function audit_edit(){
		$id=intval($_GET['id']);
		$data=zfun::f_row("Authentication","id='$id'");
		$data['data']=json_decode($data['data'],true);
		foreach($data['data'] as $k1=>$v1)$data[$k1]=$v1;
		$user=zfun::f_row("User","id=".intval($data['uid']));
		/*html*/
		/**************************/
		ztp::title("提现审核");
		ztp::setaction(self::getUrl("member_manage","audit_save",array("id"=>$id)));
		ztp::add("text","提现事件",$data['info'],"disabled style='width:400px;'");
		ztp::add("text","用户id",$data['uid'],"disabled");
		ztp::add("text","用户昵称",$user['nickname'],"disabled");
		ztp::add("text","提现金额",$data['money'],"disabled");
		ztp::add("text","冻结金额",$user['freeze_integral'],"disabled");
		ztp::add("text","支付宝账号",$user['alipay'],"disabled");
		ztp::add("text","真实姓名",$user['realname'],"disabled");
		ztp::add("text","时间",date("Y-m-d H:i:s",$data['time']),"disabled");
		ztp::add("radio","审核状态","审核中=0,审核通过=1,审核失败=2","name=audit_status val=".intval($data['audit_status']));
		if($data['audit_status']<>1)ztp::add("mtext","审核失败原因",$data['failMsg'],"name=failMsg");
		if($data['audit_status']<>1)ztp::add("submit","审核");
		ztp::play();
	}
	/*提现审核操作*/
	public function audit_save(){
		$id=intval($_GET['id']);
		$status=intval($_POST['audit_status']);
		$data=zfun::f_row("Authentication","id='$id'");
		$data['data']=json_decode($data['data'],true);
		foreach($data['data'] as $k1=>$v1)$data[$k1]=$v1;
		$user=zfun::f_row("User","id=".intval($data['uid']));
		
		//jj explosion
		$set=zfun::f_getset("alipay_auto_onoff,tixian_auto_onoff,tixian_auto_max_money");
		$set['alipay_auto_onoff']=floatval($set['alipay_auto_onoff']);
		$set['tixian_auto_onoff']=floatval($set['tixian_auto_onoff']);
		$set['tixian_auto_max_money']=floatval($set['tixian_auto_max_money']);
		foreach($set as $k=>$v)setconst($k,$v);
		
		$statusarr=array(0=>"审核中",1=>"审核通过",2=>"审核不通过",);
		
		if($data['audit_status']==2)zfun::fecho(1,"审核失败不能改状态",0);
		if($data['audit_status']==$status)zfun::fecho(1,$statusarr[$data['audit_status']]."`",1);
		if($status==1){
			zfun::add_f("admin");
			self::alipay_auto($user,abs($data['money']));
		}
		if($status==2){
			$arr=array();
			switch($data['type']){
				case 3:
					$commission=$user['commission']+$data['money'];
					$arr["commission"]=$commission;
					break;
				case 8:
					$commission=$user['money']+$data['money'];
					$arr["money"]=$commission;
					break;
			}
			
			zfun::f_update("User","id=".intval($user['id']),$arr);
			//admin::getadd($result);
		}
		// $tmp['info']=filter_check($_POST['failMsg']);
		$tmp=array();
		$tmp['audit_status']=$status;
		$tmp['failMsg']=filter_check($_POST['failMsg']);
		$result1=zfun::f_update("Authentication","id='$id'",$tmp);
		admin::getadd($result1);
		// zfun::f_fmsg($statusarr[$status]);
	}
	//会员中心
    public function member_list(){
		//zheli
		ztp::title("会员中心");//网站标题
		$Tgidkey=self::getApp("Tgidkey");
		zfun::f_setadminurl();
		if(!empty($_GET['start_time'])&&!empty($_GET['end_time'])){self::daochu_user();return;}//导出excel

		//会员等级
		$set_str="fxdl_lv,fxdl_name1,fxdl_name2,fxdl_name3,fxdl_name4,fxdl_name5,fxdl_name6,fxdl_name7,fxdl_name8,fxdl_name9,fxdl_name10";
		$set_str.=",operator_name,operator_name_2";
		$set=zfun::f_getset($set_str);
		$fxdl_lv=intval($set['fxdl_lv']);
		if(empty($fxdl_lv))$fxdl_lv=1;
		$str_user="全部=0";
		for($i=1;$i<=$fxdl_lv;$i++){
			$lv=$i-1;
			$arr[$lv]['count']=zfun::f_count("User","is_sqdl='$lv'");
			$arr[$lv]['name']=$set['fxdl_name'.$i];
			$str_user.=",".$arr[$lv]['name']."=".$i;
		}
		
		ztp::addjs("member_manage/daochu.js");
		$GLOBALS['ztp']['user_data']=$arr;
		
		//运营商人数
		$GLOBALS['ztp']['operator_num']=zfun::f_count("User","operator_lv > 0");
		
		ztp::addjs("website/mem_user.js");
		//查询语句
		$where="id>0";
		if(!empty($_GET['mid'])){
			$where.=" AND id=".intval($_GET['mid']);
		}
		if(!empty($_GET['is_sqdl'])&&$_GET['is_sqdl']!='operator'){
			$lv1=intval($_GET['is_sqdl'])-1;
			$where.=" AND is_sqdl='$lv1'";
		}
		
		//运营商
		if(!empty($_GET['is_sqdl'])&&$_GET['is_sqdl']=='operator'){
			$where.=" and operator_lv >0";	
		}
		
		if(!empty($_GET['ename'])){
			$esuser=zfun::f_select("User","nickname LIKE '%".filter_check($_GET['ename'])."%' or phone LIKE '%".filter_check($_GET['ename'])."%'");
			$ids=zfun::f_kstr($esuser);
			$where.=" AND extend_id IN($ids)";
		}
		if(!empty($_GET['phone'])){
			$where.=" AND phone LIKE '%".filter_check($_GET['phone'])."%'";
		}
		if(!empty($_GET['nickname'])){
			$where.=" AND nickname LIKE '%".filter_check($_GET['nickname'])."%'";
		}
		if(!empty($_GET['agent'])){
			$where.=" and (dllv1>0 or dllv2>0 or dllv3 >0 or dllv4>0)";	
		}
		if(!empty($_GET['tg_code'])){
			$where.=" AND tg_code LIKE '%".filter_check($_GET['tg_code'])."%'";
		}
		if(!empty($_GET['tgid'])){
			
			$tgid=$Tgidkey->Decodekey($_GET['tgid']);
			$where.=" and id='{$tgid}'";	
		}
		
		if(!empty($_POST['page'])){
          $_GET['p']=$_POST['page'];  
        }
		
		//推广位
		if(!empty($_GET['pid'])){
			$pid=$_GET['pid'];
			if(strstr($pid,"_")){
				$pid=explode("_",$pid);
				$pid=end($pid);	
			}
			$where.=" and (tg_pid='{$pid}' or tb_app_pid='{$pid}' or ios_tb_app_pid='{$pid}')";
		}
		
        $GLOBALS['ztp']['jump']="{link:member_manage-member_list}";
        ztp::addjs("comm/foot_jump.js");
		
		$user=zfun::f_goods("User",$where,NULL,"id DESC",filter_check($_GET),20);
		$edit_url=urlencode(self::getUrl("member_manage","manage_add"));//编辑的链接
		$tz_url=urlencode(self::getUrl("stationmsg","manage_tz"));//通知的链接
		$del_url=urlencode(self::getUrl("member_manage","manage_del"));//删除的链接
		$euser=zfun::f_kdata("User",$user,"extend_id","id");
		foreach($euser as $k=>$v){
			if(!empty($v['nickname']))$euser[$k]['name']=$v['nickname'];
			elseif(!empty($v['phone']))$euser[$k]['name']=$v['phone'];	
			
		}
		$dlarr=array("","省级","市级","县级","网点");$dltxarr=array("否","是");
		$strstr=array("允许","禁止");
		foreach($user as $k=>$v){
			$GLOBALS['ztp']['user']['_'.$v['id']]=$v;
			$user[$k]['stop_comment']=$strstr[$v['stop_comment']];
			$user[$k]['stop_issue']=$strstr[$v['stop_issue']];
			$user[$k]['tgid']=$Tgidkey->addkey($v['id']);
			$v['vip']=intval($v['vip']);
			if(!empty($v['vip']))$v['is_sqdl']=$v['vip'];
			$user[$k]['ename']=$euser[$v['extend_id']]['name'];
			$user[$k]['vip_name']=self::getSetting("fxdl_name".($v['is_sqdl']+1));
			$user[$k]['caozuo']=ztp::cstrarr("button=返利佣金调整|javascript:void(0);| c-'cfbtz' uid-{$v['id']},button=编辑|{$edit_url}&id-".$v['id'].",button=通知|{$tz_url}&id-".$v['id'].",button=删除|{$del_url}&id-".$v['id']."| msg-'是否删除?' ");	
			$user[$k]['dllv']=$dlarr[$lv];
			$user[$k]['tx_frozen']=$dltxarr[$v['tx_frozen']];
			$n2='';
			if($dllv==1){
				$tmp=zfun::f_row("O2OProvince","ProvinceID='".$v['dllv1']."'");
				$n2=$tmp['ProvinceName'];
			}
			if($dllv==2){
				$tmp=zfun::f_row("O2OCity","CityID='".$v['dllv2']."'");
				$n2=$tmp['CityName'];	
			}
			if($dllv==3){
				$tmp=zfun::f_row("O2ODistrict","DistrictID='".$v['dllv3']."'");
				$n2=$tmp['DistrictName'];	
			}
			if($dllv==4){
				$tmp=zfun::f_row("WD","WdID='".$v['dllv4']."'");	
				$n2=$tmp['WdName'];
			}
			$user[$k]['dlname']=$n2." ".$user[$k]['dllv'];
			$user[$k]['reg_time']=date("Y-m-d H:i:s",$v['reg_time']);
			if(empty($user[$k]['reg_time']))$user[$k]['reg_time']="-";
			if(!empty($v['login_time']))$user[$k]['login_time']=date("Y-m-d H:i:s",$v['login_time']);
			if(empty($user[$k]['login_time']))$user[$k]['login_time']="-";
			$user[$k]['nickname']=str_replace(array('"',"&#34;"),'',$v['nickname']);
			$user[$k]['ename']=str_replace(array('"',"&#34;"),'',$user[$k]['ename']);
			$user[$k]['vip_name']=str_replace(array('"',"&#34;"),'',$user[$k]['vip_name']);
			//jj explosion
			if(intval($v['operator_lv'])>0)$user[$k]['vip_name']=$set['operator_name'];
			if($v['operator_lv']==2)$user[$k]['vip_name']=$set['operator_name_2'];
			
		}
		zfun::isoff($user);
		/*html*/
		//``````````````````````````````````````````````````````````````
		$str="ID=id/width-50,会员ID=id/width-50,邀请ID=tgid,自定义的邀请ID=tg_code,推荐人=ename,会员姓名=nickname,";
		if(empty($_GET['agent'])){
			$str.="会员等级=vip_name,";
		}else{
			$str.="代理等级=dlname,冻结提现=tx_frozen,";
		}
		$str.="手机号码=phone,返利金额=commission,代理返利金额=dlcommission,";
		//jj explosion
		$str.="朋友圈评价权限=stop_comment,朋友圈发布权限=stop_issue,累积直推下级人数=yq_xj_count/width-100,家族累积成员人数=yq_all_count/width-100,累积自购获得返利金额=gm_all_commission/width-100";
		$str.=",代理推广位=tg_pid,安卓推广位=tb_app_pid,苹果推广位=ios_tb_app_pid,注册时间=reg_time,最近登录时间=login_time,操作=caozuo/width-200";//表格头部
		ztp::settableaction(self::getUrl("member_manage","manage_del"));//设置表单action链接
		$GLOBALS['ztp']['daochu']="{link:member_manage-member_list}";
		ztp::addtop('text',"开始时间","","time=on get=on name=start_time placeholder='开始时间'");
		ztp::addtop('text',"结束时间","","time=on get=on name=end_time placeholder='结束时间'");
		ztp::addtop('a',"导出会员手机号","javascript:void(0)","name=daochu id=daochu");
		ztp::addtop("a","添加",self::getUrl("member_manage","manage_add"));//添加头部按钮
		ztp::addtop("p");
		ztp::addtop('text',"会员ID",NULL,"name=mid id=info get=on style='width:70px;'","会员ID");
		ztp::addtop('text',"邀请ID",NULL,"name=tgid get=on style='width:70px;'","邀请ID");
		ztp::addtop('text',"自定义的邀请ID",NULL,"name=tg_code get=on style='width:70px;'","邀请ID");
		ztp::addtop('text',"推广位",NULL,"name=pid get=on style='width:70px;'","推广位");
		ztp::addtop('text',"会员姓名",NULL,"name=nickname get=on","会员  姓名");
		ztp::addtop('text',"手机号码",NULL,"name=phone get=on","手机号码");
		ztp::addtop('text',"推荐人",NULL,"name=ename get=on","推荐人");
		//添加运营商
		$str_user.=",运营商=operator";
		ztp::addtop('select',"会员等级",$str_user,"name=is_sqdl  get=on","我是消息3");
		ztp::addtop("submit","查询");
		ztp::addtable($str,$user);//添加表格
		ztp::addhtml("member_manage/sjf.tpl.html");
		ztp::addjs("member_manage/sjf.js");
		ztp::play();
    }
	//添加会员页面
	public function manage_add(){
		$set=zfun::f_getset("operator_name,operator_name_2,operator_onoff");
		ztp::title("添加/编辑会员");//网站标题
		$id=intval($_GET['id']);
		$user=zfun::f_row("User","id=".intval($_GET['id']));
		//会员等级
		$dl_lv = intval(self::getSetting("fxdl_lv"));
		$str='';
		for($i=1;$i<=($dl_lv);$i++){
			$str.=self::getSetting("fxdl_name".$i)."=".($i-1).",";
		}
		if($set['operator_onoff']=='1'){
			//加一个运营商
			$str.=$set['operator_name']."=operator";
			//联合创始人
			$str.=",".$set['operator_name_2']."=operator_2";
		}
		$user['operator_lv']=intval($user['operator_lv']);
		$user['is_sqdl']=intval($user['is_sqdl']);
		if($user['operator_lv']!='0')$user['is_sqdl']='operator';//我是运营商
		if($user['operator_lv']=='2')$user['is_sqdl']='operator_2';//联合创世人
		zfun::isoff($user,1);
		
		if(empty($user['head_img']))$user['head_img']='default.png';
		if(strstr($user['head_img'],"http")==false)$user['head_img']=UPLOAD_URL."user/".$user['head_img'];
		$isdl=0;
		if(!empty($user['dllv1']))$isdl=1;
		if(!empty($user['dllv2']))$isdl=1;
		if(!empty($user['dllv3']))$isdl=1;
		if(!empty($user['dllv4']))$isdl=1;
		
		//jj explosion
		$user['vip']=intval($user['vip']);
		if(!empty($user['vip']))$user['is_sqdl']=$user['vip'];
		
		$Tgidkey=self::getApp("Tgidkey");
		$user['tgid']=$Tgidkey->addkey($user['id']);
		if(!empty($user['hhr_gq_time']))$user['hhr_gq_time']=date("Y-m-d H:i:s",$user['hhr_gq_time']);
		else $user['hhr_gq_time']='';
		if(!empty($user['operator_time']))$user['operator_time']=date("Y-m-d H:i:s",$user['operator_time']);
		else $user['operator_time']='';

		/*html*/
		//``````````````````````````````````````````````````````````````
		ztp::setaction(self::getUrl("member_manage","manage_save",array("id"=>intval($_GET['id']))));//设置表单action链接
		ztp::add("text","账号",filter_check($user['loginname']),"name=loginname nonullmsg='账号不能为空'");
		ztp::add("text","token",filter_check($user['token']),"");
		ztp::add("text","邀请id",intval($user['tgid']),"style='background:#eee;width:180px;'  readonly ");
		ztp::add("text","推荐人id",intval($user['extend_id']),"name=extend_id");
		ztp::add("text","自定义的邀请id",($user['tg_code']),"id=tg_code disabled style='background: lightgray;' uid='".$id."' tg_code='".$user['tg_code']."'");
		ztp::add("text","会员昵称",filter_check($user['nickname']),"name=nickname");
		ztp::add("radio","朋友圈评价权限","允许=0,禁止=1","name=stop_comment val='".($user['stop_comment'])."'");
		ztp::add("radio","朋友圈发布权限","允许=0,禁止=1","name=stop_issue val='".($user['stop_issue'])."'");
		ztp::add("radio","会员等级",$str,"name=is_sqdl val='".($user['is_sqdl'])."'");
		ztp::add("time","代理过期时间",($user['hhr_gq_time']),"name=hhr_gq_time");
		ztp::add("time","运营商过期时间",($user['operator_time']),"name=operator_time");
		//jj explosion
		//ztp::add("radio",$set['operator_name'],"是=1,否=0","name=operator_lv val=".$user['operator_lv']);
		ztp::add("text","所属".$set['operator_name']."ID",$user['operator_id'],"name=operator_id");
		
		ztp::add("text","代理推广单元ID",filter_check($user['tg_pid']),"name=tg_pid");
		ztp::add("text","安卓APP推广位",filter_check($user['tb_app_pid']),"name=tb_app_pid");
		ztp::add("text","苹果APP推广位",filter_check($user['ios_tb_app_pid']),"name=ios_tb_app_pid");
		ztp::add("text","真实姓名",filter_check($user['realname']),"name=realname");
		ztp::add("removename","alipay");
		ztp::add("text","支付宝账号",filter_check($user['alipay']),"name=alipay");
		ztp::add("password","登录密码",'',"name=password nonullmsg='账号不能为空'");
		ztp::add("text","手机号码",filter_check($user['phone']),"name=phone nonullmsg='账号不能为空'","请输入数字");
		ztp::add("text","电子邮箱",filter_check($user['email']),"name=email");
		ztp::add("text","详细地址",filter_check($user['address']),"name=address");
		ztp::add("text","邮编",filter_check($user['postcode']),"name=postcode");
		ztp::add("text","QQ",filter_check($user['qq']),"name=qq","请输入数字");
		ztp::add("text","账户余额",floatval($user['money']),"name=money","请输入数字");
		ztp::add("text","返利金额",floatval($user['commission']),"name=commission","请输入数字");
		//ztp::add("text","代理返利金额",floatval($user['commission']),"name=dlcommission","请输入数字");
		ztp::add("text","积分",floatval($user['integral']),"name=integral");
		ztp::add("text","资金",floatval($user['zijin']),"name=zijin");
		ztp::add("radio","冻结提现","否=0,是=1","name=tx_frozen val='".intval($user['tx_frozen'])."'");
		ztp::add("text","weixin_au",$user['weixin_au'],"style='width:200px;'");
		//jj explosion 多了一个
		//ztp::add("text","代理推广pid",floatval($user['tg_pid']),"name=tg_pid");
		
		if($user['is_sqdl_time']==0)$user['is_sqdl_time']='';
		else $user['is_sqdl_time']=date("Y-m-d H:i:s",$user['is_sqdl_time']);
		
		ztp::add("time","成为代理的时间",($user['is_sqdl_time']),"name=is_sqdl_time");
		ztp::add("time","支付宝绑定次数",($user['zfb_count']),"name=zfb_count");
		ztp::add("file","会员头像",filter_check($user['head_img']),"name=head_img");//未完善
		
		$path=ROOT_PATH."comm/cwdl_rule.php";
		//这是要把累积的金额计算处理
		if(file_exists($path)==true){
			include_once $path;
			if(empty($user['yq_xj_count']))$user['yq_xj_count']=cwdl_rule::yq_friend($user['id'],1);
			if(empty($user['yq_all_count']))$user['yq_all_count']=cwdl_rule::yq_next_friend($user['id'],1);
			if(empty($user['gm_all_commission']))$user['gm_all_commission']=cwdl_rule::lj_buy($user['id'],1);
		}
		ztp::add("text","累积直推下级人数",intval($user['yq_xj_count']),"name=yq_xj_count","");
		ztp::add("text","家族累积成员人数",intval($user['yq_all_count']),"name=yq_all_count");
		ztp::add("text","累积自购获得返利金额",floatval($user['gm_all_commission']),"name=gm_all_commission","订单总佣金");
		
		ztp::add("select","地区","请选择=","name=dq1 id=dq1 val=".$user['dq1'].",".$user['dq2'].",".$user['dq3'].",".$user['dq4']);
		
		//百里.冻结金额
		ztp::add("text","冻结金额",intval($user['blocking_price']),"style='background:#eee;width:180px;'  readonly ");
		if($user['blocking_price_endtime'] > 0)
		{
			ztp::add("text","冻结金额过期时间",date("Y-m-d H:i:s", $user['blocking_price_endtime']),"style='background:#eee;width:180px;'  readonly ");
		}
		else
		{
			ztp::add("text","冻结金额过期时间","","style='background:#eee;width:180px;'  readonly ");
		}

		if(empty($_GET['id']))ztp::addjs("member_manage/city_add.js");
		else ztp::addjs("member_manage/city_update.js");
		ztp::addhtml("member_manage/tgcode.tpl.html");
		ztp::addjs("member_manage/tg_code.js");
		ztp::add("submit","确认");
		ztp::play();
	}
	
	//保存操作
	public function manage_save(){
		$id=$uid=intval($_GET['id']);
		$set=zfun::f_getset("operator_valid_day,operator_name,fxdl_hyday");
		$user=zfun::f_row("User","id='{$id}'");
		
		$str="stop_comment,stop_issue,loginname,extend_id,nickname,realname,alipay,phone,email,";
		$str.="address,postcode,qq,money,commission,integral,zijin,tx_frozen,dlcommission,is_sqdl,tg_pid";
		
		//支付宝绑定次数
		$str.=",zfb_count";
		//运营商
		$str.=",operator_lv,operator_id";
		//APP推广位
		$str.=",tb_app_pid,ios_tb_app_pid";

		$data=zfun::getpost($str);
		$data['operator_id']=intval($data['operator_id']);
		$data['operator_lv']=0;
		$dl_dj=$data['is_sqdl'];
		if(!empty($_POST['hhr_gq_time']))$data['hhr_gq_time']=strtotime($_POST['hhr_gq_time']);
		if(!empty($_POST['operator_time']))$data['operator_time']=strtotime($_POST['operator_time']);

		//如果等于operator  就是运营商
		if($data['is_sqdl']=='operator'){
			
			$data['operator_lv']=1;
			$data['is_sqdl']='0';
			if(empty($_POST['operator_time']))zfun::fecho(0,"请填写运营商过期时间",0);
		}
		//联合创始人
		if($data['is_sqdl']=='operator_2'){
			
			$data['operator_lv']=2;
			$data['is_sqdl']='0';
			if(empty($_POST['operator_time']))zfun::fecho(0,"请填写运营商过期时间",0);
		}
		
		
		if($data['is_sqdl']>0){//代理的
			if(empty($_POST['hhr_gq_time']))zfun::fecho(0,"请填写代理过期时间",0);
			$data['operator_time']=0;
			//$data['hhr_gq_time']=time()+$set['fxdl_hyday']*86400;
			//if($user['hhr_gq_time']!='0')unset($data['hhr_gq_time']);
		}

		if($data['operator_lv']>0){
			//$data['operator_time']=time()+$set['operator_valid_day']*86400;
			$data['hhr_gq_time']=0;
			//绑定下级 所有非运营商关系
			include_once ROOT_PATH."Action/admin/operator.action.php";
			if(!empty($id))operatorAction::set_lower_operator_id($id);
		}
	
		//判断自定义邀请码的权限
		//self::change_tgid($data);
		
		$data['extend_id']=intval($data['extend_id']);
		
		
		$data['vip']=intval($data['vip']);
		
		$data['yq_xj_count']=intval($_POST['yq_xj_count']);
		$data['yq_all_count']=intval($_POST['yq_all_count']);
		$data['gm_all_commission']=intval($_POST['gm_all_commission']);
		//if(!empty($data['password']))$data['password']=md5($data['password'].'');
		if(!empty($_POST['password']))$data['password']=md5($_POST['password'].'');
		$data['phone']=$data['phone'];
		$data['qq']=intval($data['qq']);
		$data['postcode']=intval($data['postcode']);
		$data["money"]=!empty($data['money'])?$data['money']:0;
		$data["commission"]=!empty($data['commission'])?$data['commission']:0;
		$data["commission"]=!empty($data['commission'])?$data['commission']:0;
		$data["integral"]=!empty($data['integral'])?$data['integral']:0;
		$data["zijin"]=!empty($data['zijin'])?$data['zijin']:0;
		$data["tx_frozen"]=!empty($data['tx_frozen'])?$data['tx_frozen']:0;
		
		$data['dq1']=intval($_POST['dq1']);
		$data['dq2']=intval($_POST['dq2']);
		$data['dq3']=intval($_POST['dq3']);
		$data['dq4']=intval($_POST['dq4']);
		
		//jj explosion
		$data['is_sqdl_time']=0;		
		if(!empty($_POST['is_sqdl_time'])){//修改成为代理时间
			$data['is_sqdl_time']=strtotime($_POST['is_sqdl_time']);	
		}
		else if($data['is_sqdl']>0){
			$data['is_sqdl_time']=time();		
		}
		if(!empty($_POST['alipay']))$data['zfb_au']=$_POST['alipay'];
		
		if(!empty($_FILES['head_img'])){
			$img=zfun::f_simg("head_img","user");
			if(!empty($img['head_img'])){
				$data['head_img']=$img['head_img'];
			}
		}
		
		if(empty($id)){
			//jj explosion
			//$data['reg_time']=strtotime($_POST['reg_time']);
			$data['reg_time']=time();
			$result=zfun::f_insert("User",$data);
			$id=$result;
			 rz::add("添加会员: ".$result.": ".$data['loginname']."手机号:".$data['phone']);
		}else{
			//上级匹配
			self::extend_check($data['extend_id'],$id);
			//下级匹配
			actionfun("comm/order");
			$result=order::get_lower($id);
			$user_arr=$result['user_arr'];
			foreach($user_arr as $k=>$v){
				if($v['id']==$data['extend_id'])zfun::fecho(0,"不能绑定该邀请id",0);
			}
			if($data['extend_id']==$id)zfun::fecho(0,"不能绑定该邀请id",0);
			$money=floatval(self::getSetting("fxdl_money" . ($data['is_sqdl']+1)));
			if($data['is_sqdl']>0||$data['operator_lv']>0){
					
					
					$arr=array("uid"=>$id,"dl_dj"=>$dl_dj,"time"=>time(),"name"=>$data['nickname'],"checks"=>1,"jnMoney"=>0,"is_pay"=>1);
					$count=zfun::f_count("DLList","uid='$id'");
					if(empty($count))zfun::f_insert("DLList",$arr);
					
					//修改可以不填推广位
					if(!empty($_POST['tg_pid'])){
						$tg_pid=$_POST['tg_pid'];
						if(strstr($tg_pid,"_")==true){
							$arr=explode("_",$tg_pid);
							$tg_pid=$arr[3];
						}
					}
					
					if(!empty($tg_pid)){
						$where="tg_pid='$tg_pid' and id<>$uid";
						$count=zfun::f_count("User",$where);
						if(!empty($count))zfun::fecho(0,"该推广位已经被绑定",0);
					}
					
					$data['tg_pid']=$tg_pid;	
				
			}
			$path=ROOT_PATH."comm/cwdl_rule.php";

			$result=zfun::f_update("User","id='$id'",$data);
		}
		
		/*这是代理等级自动升级的*/
		
		/*$path=ROOT_PATH."comm/cwdl_rule.php";
		if(file_exists($path)==true&&$data['operator_lv']==0){
			$lv=$data['is_sqdl'];
			$data=array();
			$data['is_sqdl']=intval($lv);
			include_once $path;
			$lv=cwdl_rule::zdsj_doing($id);
			$lv=intval($lv);
			if($lv>$data['is_sqdl'])$data['is_sqdl']=$lv;
			$result=zfun::f_update("User","id='$id'",$data);
		}*/
		
		admin::getadd($result);
	}
	//获取上级
	public function extend_check($extend_id,$uid){
		if(empty($extend_id))return false;
		if(empty($uid))return false;
		while(true){
			//如果当前用户的id等于推广id，那么就不能继续
			if($uid==$extend_id){zfun::fecho(0,"不能绑定该邀请id",0);break;}
			$user=zfun::f_row("User","id='{$extend_id}'");
			if(empty($user))break;
			if($user['extend_id'].''=='0')break;
			$extend_id=$user['extend_id'].'';	
		};
	}
	//存入自定义邀请码
	public function update_tgcode(){
		$id=$uid=intval($_POST['id']);
		if(empty($id))zfun::fecho("用户不存在不能修改");
		$tg_code=filter_check($_POST['tg_code']);
		$user=zfun::f_row("User","id='$id'");
		$user['tg_code']=$tg_code;
		self::change_tgid($user);
		$tg_code_low=strtolower($tg_code);
		zfun::f_update("User","id='$id'",array("tg_code"=>$tg_code,"tg_code_low"=>$tg_code_low));
		zfun::fecho("保存成功",1,1);
	}
	//自定义邀请码权限
	static function change_tgid($user=array()){
		$str="fxdl_zdytgcode_onoff";
		$str.=",operator_name,operator_name_2,fxdl_name1,fxdl_name2";
		$str.=",fxdl_name3,fxdl_name4,fxdl_name5,fxdl_name6";
		$str.=",fxdl_name7,fxdl_name8,fxdl_name9,fxdl_name10";
		
		$set=zfun::f_getset($str);
		if(empty($user['tg_code']))return;

		if(is_numeric($user['tg_code'])==1)zfun::fecho("不能填写纯数字");
		$user['tg_code']=str_replace(array("。","，","！","%","@","#"),".",$user['tg_code']);
		if(strstr($user['tg_code'],"."))zfun::fecho("邀请码请勿填写非法字符");
		$tg_code_low=strtolower($user['tg_code']);
		$count=zfun::f_count("User","tg_code_low='".$tg_code_low."' and id<>".intval($user['id'])."");
		if($count>0)zfun::fecho("该邀请码已被使用");
		if(is_numeric($user['tg_code'])==1){
			$tmp=$GLOBALS['action']->getApp("Tgidkey"); 
			$result=$tmp->DecodeKey($user['tg_code']);
			if(!empty($result)&&$result>0&&strstr($result,".")==false)zfun::fecho("该邀请码已被使用");
		}
		$lv=($user['is_sqdl']+1);
		$name=$set['fxdl_name'.$lv];
	
		if($user['operator_lv']==1)$name=$set['operator_name'];
		if($user['operator_lv']==2)$name=$set['operator_name_2'];
		if(intval($user['operator_lv'])==0&&$set['fxdl_zdytgcode_onoff']!=''&&is_numeric($set['fxdl_zdytgcode_onoff'])&&$lv<$set['fxdl_zdytgcode_onoff']){
			zfun::fecho($name."不能自定义邀请码");
		}
		if($set['fxdl_zdytgcode_onoff']=='operator_1'){
			if($user['operator_lv']==0)zfun::fecho($name."不能自定义邀请码");
		}
		if($set['fxdl_zdytgcode_onoff']=='operator_2'){
			if($user['operator_lv']<2)zfun::fecho($name."不能自定义邀请码");
		}
	
		
		
	}
	//会员删除
	public function manage_del(){
		$ids=$_POST['id'];
		if(!empty($_GET['id'])){
			$ids=intval($_GET['id']);	
		}
		if(!empty($_POST['ch'])){
			$ids=implode(",",$_POST['ch']);	
		}
		if(empty($ids))zfun::f_fmsg("操作失败!",0);
		$result=zfun::f_delete("User","id IN($ids)");
		admin::getdel($result);
	}
	/*积分操作日志*/
    public function integral_handle_log(){
	   ztp::title("积分操作");
	   zfun::f_setadminurl();
	   $where="id>0";
	   if(!empty($_GET['id'])){
		   $id=intval($_GET['id']);
	   	   $where.=" AND uid='$id'";
	   }
	   //jj explosion
		if(!empty($_GET['keyword'])){
			$where.=" and detail like '%".$_GET['keyword']."%'";
		}
	   
	   if(!empty($_GET['type'])&&intval($_GET['type'])>-1){//笑 jj explosion
		   $id=intval($_GET['type']);
	   	   $where.=" AND type = '$id'";
	   }
		if(!empty($_POST['page'])){
		$_GET['p']=$_POST['page'];  
		}
		if(!empty($_GET['show_where'])){
			echo $where;	
		}
		
		//订单好筛选
		if(!empty($_GET['oid'])){
			$where.=" and oid like '%".$_GET['oid']."%'";	
		}
		
        $GLOBALS['ztp']['jump']="{link:member_manage-integral_handle_log}";
        ztp::addjs("comm/foot_jump.js");
	   $data=zfun::f_goods("Interal",$where,NULL,"time DESC",filter_check($_GET),12);
	   zfun::isoff($data);
	   $user=zfun::f_kdata("User",$data,"uid","id");
	   $del_url=urlencode(self::getUrl("member_manage","integral_del"));
	   foreach($data as $k=>$v){
	   		$data[$k]['interal'] = zfun::dian($v['interal']);
			$data[$k]['nickname']=$user[$v['uid']]['nickname'];
			$data[$k]['time']=date("Y-m-d H:i:s",$v['time']);
			if(strstr($v['detail'],"元"))
	   		//$data[$k]['caozuo']=ztp::cstrarr("button=删除|{$del_url}&id-".$v['id']."| msg-'是否删除?' ");	
			if($v['type']==3||$v['type']==4){
				if(strstr($data[$k]['interal'],"-")==false)$data[$k]['interal']="-".$data[$k]['interal'];
			}else{
				if(strstr($data[$k]['interal'],"+")==false)$data[$k]['interal']="+".$data[$k]['interal'];
			}
			
			$data[$k]['interal']=str_replace("+-","-",$data[$k]['interal']);
			
			$data[$k]['type']=self::integral_type($v['type']);
			if(strstr($v['detail'],"邀请好友注册")){
				if(!empty($v['data']))$data[$k]['oid']="邀请的好友id:  ".$v['data'];
			}
	   }
	   $str="ID`=id,会员ID=uid,会员昵称=nickname,变更日期=time,变更积分=interal,变更类型=type,备注=detail,订单编号=oid";
	   ztp::settableaction(self::getUrl("member_manage","integral_del")); 
	   ztp::addtable($str,$data);
	   ztp::addtop("text","关键词",NULL,"name=keyword get=on");
	   ztp::addtop("text","订单编号",NULL,"name=oid get=on");
	   ztp::addtop("text","会员ID",NULL,"name=id get=on");
	   ztp::addtop("select",'类型',"请选择=-1,分享=0,签到=1,中奖=2,虚拟兑换=3,实物兑换=4,兑换失败=5,注册=6","name=type get=on");
       ztp::addtop("submit","查询");
	   ztp::play();
    }
	/*积分明细删除*/
	public function integral_del(){
		$ids=$_POST['id'];
		if(!empty($_GET['id'])){
			$ids=intval($_GET['id']);	
		}
		if(!empty($_POST['ch'])){
			$ids=implode(",",$_POST['ch']);	
		}
		if(empty($ids))zfun::f_fmsg("操作失败!",0);
		$result=zfun::f_delete("Interal","id IN($ids)");
		admin::getdel($result);
	}
	/*会员认证*/
    public function member_rz(){
		ztp::title("会员认证");
		 zfun::f_setadminurl();
		$where="is_truename<>0";
		if(!empty($_POST['page'])){
          $_GET['p']=$_POST['page'];  
        }
        $GLOBALS['ztp']['jump']="{link:member_manage-member_rz}";
        ztp::addjs("comm/foot_jump.js");
		$data=zfun::f_goods("User",$where,NULL,"rztime DESC",$arr,12); 
		$edit_url=urlencode(self::getUrl("member_manage","rz_edit"));
		$tmparr=array("","认证中","认证通过","认证失败");
		foreach($data as $k=>$v){
			$data[$k]['jd']=$tmparr[$v['is_truename']];
			$data[$k]['rz_time']=date("Y-m-d H:i:s",$v['rztime']);
			$data[$k]['caozuo']=ztp::cstrarr("button=审核|{$edit_url}&id-".$v['id']);	
		}
		$str="ID=id/width-50,会员ID=id,会员真实姓名=realname,会员身份证=ID_,认证进度=jd,申请时间=rz_time,操作=caozuo/width-200";
		ztp::addtable($str,$data);
        ztp::play();
    }
   /*认证审核*/
   public function rz_edit(){
	  
	    $id=intval($_GET['id']);
	    $data=zfun::f_row("User","id='$id'");
		ztp::setaction(self::getUrl("member_manage","rz_save",array("id"=>$id)));
		 
		ztp::add("text","会员真实姓名",$data['realname'],"disabled");
		ztp::add("text","会员身份证号",$data['ID_'],"disabled");
		ztp::add("radio","审核","认证中=1,认证通过=2,认证失败=3","name=is_truename val=".intval($data['is_truename']));
		ztp::add("maxtext","审核失败原因",$data['failMsg'],"name=failMsg");
		ztp::add("submit","审核");
   		ztp::play();
   }
   /*提现审核操作*/
	public function rz_save(){
		$id=intval($_GET['id']);
		$status=intval($_POST['is_truename']);
		$data=zfun::f_row("User","id='$id'");
		$statusarr=array("","认证中","认证通过","认证失败");
		if($data['is_truename']==$status){//如果重复操作 跳过 跳过 跳过
			admin::getmsg(1,$statusarr[$data['is_truename']],1);
		}
		$tmp['is_truename']=$status;
		// $tmp['failMsg']=filter_check($_POST['failMsg']);
		$result=zfun::f_update("User","id=".intval($id),$tmp);
		admin::getadd($result);
		
	}
	/*会员等级*/
	public function member_level(){
		ztp::title("会员等级");
		ztp::addjs("member_manage/level.js");
		zfun::f_setadminurl();
		/*读取等级*/
		$str="vip_lv,vip_name0,vip_price0,vip_growth0,vip_privilege0,vip_bili0,vip_zhe0,vip_tg_vip0";
		$se=zfun::f_getset("vip_lv");
		if($lv<0)$lv=0;else $lv=intval($se['vip_lv'])-1;
		for($i=0;$i<=$lv;$i++){
			$str.=",vip_name".$i;$str.=",vip_price".$i;$str.=",vip_growth".$i;$str.=",vip_privilege".$i;
			$str.=",vip_bili".$i;$str.=",vip_zhe".$i;$str.=",vip_tg_vip".$i;
		}
		$set=zfun::f_getset($str);
		for($i=0;$i<=$lv;$i++){
			$vip_name.=",".$set['vip_name'.$i];
			$vip_price.=",".$set['vip_price'.$i];
			$vip_growth.=",".$set['vip_growth'.$i];
			$vip_privilege.=",".$set['vip_privilege'.$i];
			$vip_bili.=",".$set['vip_bili'.$i];
			$vip_zhe.=",".$set['vip_zhe'.$i];
			$vip_tg_vip.=",".$set['vip_tg_vip'.$i];
		}
		/*HTML*/
		ztp::setaction(self::getUrl("member_manage","level_save"));
		ztp::add("text","VIP等级:",$set['vip_lv'],"name=vip_lv","如需要4个等级,就填 4 ");
		ztp::add("text","VIP等级名称:",substr($vip_name,1),"name=vip_name","内容用逗号隔开，如4个等级 即 1,2,3,4");
		ztp::add("text","VIP升级充值金额:",substr($vip_price,1),"name=vip_price","内容用逗号隔开，如4个等级 即 1,2,3,4");
		ztp::add("text","VIP充值所得成长值:",substr($vip_growth,1),"name=vip_growth","内容用逗号隔开，如4个等级 即 1,2,3,4");
		ztp::add("text","VIP拥有的特权:",substr($vip_privilege,1),"name=vip_privilege","内容用逗号隔开，如4个等级 即 1,2,3,4");
		ztp::add("text","VIP返利比例(%):",substr($vip_bili,1),"name=vip_bili","内容用逗号隔开，如4个等级 即 1,2,3,4");
		ztp::add("text","VIP最低折扣:",substr($vip_zhe,1),"name=vip_zhe","内容用逗号隔开，如4个等级 即 1,2,3,4");
		ztp::add("text","VIP邀请会员成为VIP返利(%):",substr($vip_tg_vip,1),"name=vip_tg_vip","内容用逗号隔开，如4个等级 即 1,2,3,4");
		ztp::add("submit","保存");
		ztp::play();
	}
	/*会员等级修改*/
	public function level_save(){
		$vip_name=explode(",",str_replace("，",",",$_POST['vip_name']));
		$vip_price=explode(",",str_replace("，",",",$_POST['vip_price']));
		$vip_growth=explode(",",str_replace("，",",",$_POST['vip_growth']));
		$vip_privilege=explode(",",str_replace("，",",",$_POST['vip_privilege']));
		$vip_bili=explode(",",str_replace("，",",",$_POST['vip_bili']));
		$vip_zhe=explode(",",str_replace("，",",",$_POST['vip_zhe']));
		$vip_tg_vip=explode(",",str_replace("，",",",$_POST['vip_tg_vip']));
		foreach($vip_name as $k=>$v){
			$str.=",vip_name".$k;
			$_POST['vip_name'.$k]=$v;
		}
		foreach($vip_price as $k=>$v){
			$str.=",vip_price".$k;
			$_POST['vip_price'.$k]=$v;
		}
		foreach($vip_growth as $k=>$v){
			$str.=",vip_growth".$k;
			$_POST['vip_growth'.$k]=$v;
		}
		foreach($vip_privilege as $k=>$v){
			$str.=",vip_privilege".$k;
			$_POST['vip_privilege'.$k]=$v;
		}
		foreach($vip_bili as $k=>$v){
			$str.=",vip_bili".$k;
			$_POST['vip_bili'.$k]=$v;
		}
		foreach($vip_zhe as $k=>$v){
			$str.=",vip_zhe".$k;
			$_POST['vip_zhe'.$k]=$v;
		}
		foreach($vip_tg_vip as $k=>$v){
			$str.=",vip_tg_vip".$k;
			$_POST['vip_tg_vip'.$k]=$v;
		}
			
		zfun::setzset("vip_lv".$str);
		admin::getadd("true");
	}
	/*导出excel*/
	public function daochu(){
		$type=intval($_GET['type']);
		if($type==3){$where="(type=3 or type=8)";$name="会员提现列表";}
		if($type==5){$where="type=5";$name="商家提现列表";}
		if($type==7){$where="type=7";$name="代理提现列表";}
		if(!empty($_GET['start_time'])){
			$where.=" and time > ".intval(strtotime($_GET['start_time']));	
		}
		if(!empty($_GET['end_time'])){
			$where.=" and time < ".intval(strtotime($_GET['end_time']));	
		}
		
		if(empty($where))zfun::fecho("daochu error");
		$where.=" and audit_status=1";
		
		//调用公共where
		self::tx_comm_where($where);
		
		$tmp=strtotime($_GET['end_time']);
		$data=zfun::f_select("Authentication",$where,NULL);
		$user=zfun::f_kdata("User",$data,"uid","id","id,loginname,phone,email,nickname,alipay,zfb_au");
		foreach($user as $k=>$v){
			if(!empty($v['phone'])){
				$user[$k]['loginname']=$v['phone'];	
			}
			elseif(!empty($v['email'])){
				$user[$k]['email']=$v['email'];	
			}
			if(!empty($v['zfb_au']))$user[$k]['alipay']=$v['zfb_au'];
		}
		$moneyarr=array(3=>"money",8=>"money",5=>"txmoney",7=>"txmoney");
		foreach($data as $k=>$v){
			$data[$k]['loginname']=$user[$v['uid']]['loginname'];
			$data[$k]['phone']=$user[$v['uid']]['phone'];
			$data[$k]['email']=$user[$v['uid']]['email'];
			$data[$k]['alipay']=$user[$v['uid']]['alipay'];
			$data[$k]['time']=date("Y-m-d H:i:s",$v['time']);
			$data[$k]['nickname']=$user[$v['uid']]['nickname'];
			$json=json_decode($v['data'],true);
			$data[$k]['txmoney']=floatval($json[$moneyarr[$type]])*100;
		}
		error_reporting(E_ALL);
        date_default_timezone_set('Asia/Shanghai');
        require_once '../phpexcel/Classes/PHPExcel.php';
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
		->setCreator('http://www.jb51.net')
		->setLastModifiedBy('http://www.jb51.net')
		->setTitle('Office 2007 XLSX Document')
		->setSubject('Office 2007 XLSX Document')
		->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
		->setKeywords('office 2007 openxml php')
		->setCategory('Result file');
        $objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', '收款帐号')
		->setCellValue('B1', '发放集分宝数（个）');
        $i = 2;
        foreach ($data as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A' . $i, "'".$v['alipay'])
			->setCellValue('B' . $i, $v['txmoney']);
            $i++;
        }
		
        $objPHPExcel->getActiveSheet()->setTitle("会员提现列表");
        $objPHPExcel->setActiveSheetIndex(0);
        $filename = urlencode($name) . '_' . date('Y-m-dHis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
        $objWriter->save('php://output');
	}
	/*导出excel*/
	public function daochu_user(){
		$where="id>0 and phone<>''";
		if(!empty($_GET['start_time'])){
			$where.=" and reg_time > ".intval(strtotime($_GET['start_time']));	
		}
		if(!empty($_GET['end_time'])){
			$where.=" and reg_time < ".intval(strtotime($_GET['end_time']));	
		}
		
		if(empty($where))zfun::fecho("daochu error");
		
		
		$tmp=strtotime($_GET['end_time']);
		$data=zfun::f_select("User",$where,NULL);
		
		foreach($data as $k=>$v){
			$data[$k]['nickname']=$v['nickname'];
			$data[$k]['phone']=$v['phone'];
			$data[$k]['time']=date("Y-m-d H:i:s",$v['reg_time']);
			
		}
		error_reporting(E_ALL);
        date_default_timezone_set('Asia/Shanghai');
        require_once '../phpexcel/Classes/PHPExcel.php';
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()
		->setCreator('http://www.jb51.net')
		->setLastModifiedBy('http://www.jb51.net')
		->setTitle('Office 2007 XLSX Document')
		->setSubject('Office 2007 XLSX Document')
		->setDescription('Document for Office 2007 XLSX, generated using PHP classes.')
		->setKeywords('office 2007 openxml php')
		->setCategory('Result file');
        $objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A1', 'id')
		->setCellValue('B1', '昵称')
		->setCellValue('C1', '注册时间')
		->setCellValue('D1', '手机号');
        $i = 2;
        foreach ($data as $k => $v) {
            $objPHPExcel->setActiveSheetIndex(0)
			->setCellValue('A' . $i, $v['id'])
			->setCellValue('B' . $i, "'".$v['nickname'])
			->setCellValue('C' . $i, "'".$v['time'])
			->setCellValue('D' . $i, "'".$v['phone']);
            $i++;
        }
		$name="会员手机号列表";
        $objPHPExcel->getActiveSheet()->setTitle("会员手机号列表");
        $objPHPExcel->setActiveSheetIndex(0);
        $filename = urlencode($name) . '_' . date('Y-m-dHis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
        header('Cache-Control: max-age=0');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'CSV');
        $objWriter->save('php://output');
	}
	/*变更类型*/
	public static function integral_type($type){
		switch($type) {
			case 0 :
				$data = "";
				break;
			case 1 :
				$data= "签到";
				break;
			case 2 :
				$data= "中奖";
				break;
			case 3 :
				$data= "虚拟兑换";
				break;
			case 4 :
				$data= "实物兑换";
				break;
			case 5 :
				$data= "兑换失败";
				break;
			case 6 :
				$data= "注册";
				break;
		}
		return $data;
	}
	
	
	//jj explosion
	
	//支付宝转账
	public static function alipay_auto($user=array(),$money=0){
		$set=zfun::f_getset("alipay_auto_onoff");
		if($set['alipay_auto_onoff']!=1)return;
		if(empty($user)||empty($money))return;
		$money=zfun::dian($money);
		fun("alipay_sdk/alipay_ta");
		$arr=array(
			"uid"=>$user['id'],
			"realname"=>$user['realname'],

			"alipay"=>$user['alipay'],
			"money"=>$money,
			"info"=>"提现".$data['money']."元",
		);
		$result=alipay_ta::run($arr);
		if(empty($result))zfun::fecho("调用转账接口出错");
		if($result['success']!=1)zfun::fecho("转账失败 ".$result['sub_msg']);
	}

	//会员加佣金
	function addcfb(){
		if(empty($_POST['uid']))zfun::fecho("值为空");
		$commissionval=doubleval($_POST['commissionval']);
		$uid=intval($_POST['uid']);
		$arr=array();
		if(!empty($commissionval))$arr['commission']=$commissionval;
		if(empty($arr))zfun::fecho("值为空");
		zfun::f_adddetail($_POST['info'],$uid,0,0,$commissionval);
		zfun::addval("User","id='{$uid}'",$arr);
		zfun::fecho("提交成功",1,1);
	}
	
}
?>
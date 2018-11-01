<?php
include_once ROOT_PATH."comm/zfun.php";
class indexAction extends Action{
	//首页
	function index(){
		self::iswxlogin();
		$set=zfun::f_getset("is_wap_dow");
		if(empty($set['is_wap_dow'])){
			$this->runplay("wap", "downloadapp", "index");
			if(empty($_COOKIE['jxfw']))$this->play();
		}
		$this->runplay("wap", "comm", "comm");

		$foot=zfun::f_select("WapFootNav","hide=0",0,0,"sort desc");
		$shouye=0;
		foreach($foot as $k=>$v){
			if($v['SkipUIIdentifier']!='pub_shouye'&&!empty($v['url'])&&!empty($v['is_check'])){
				$url=str_replace("{INDEX_WEB_URL}",INDEX_WEB_URL,$v['url']);
				zfun::jsjump($url);
			}
			if($v['SkipUIIdentifier']=='pub_shouye')$shouye=1;
		}

		if($shouye==0){//没有默认选中时
			$url=str_replace("{INDEX_WEB_URL}",INDEX_WEB_URL,$foot[0]['url']);
			if(!empty($url))zfun::jsjump($url);
		}
		$this->runplay("wap", "comm", "head");
		$data=zfun::f_select("WapIndexModel","is_show=1 and web_type='index'",'',0,0,"sort desc");

		foreach($data as $k=>$v){
			$json=json_decode(urldecode($v['data']),true);
			$mac=explode(" ",$v['mac']);

			$this->runplay($mac[0],$mac[1],$mac[2],$json);
		}


		//百里.如果是通过邀请码过来的，先绑定邀请码，再跳转到下载APP页面
		if($_GET['registered'] == 1 && !empty($_GET['tgid']))
		{
			//获取会员unionid
			$unionid = $_SESSION['wxuser']['unionid'];
			//查询会员信息
			$user = zfun::f_select("User","wx_openid='{$unionid}'",'',0,0,"");
			$user = $user[0];
			//如果邀请人为空,同时存在邀请人id
			if(intval($user['extend_id']) <= 0 && $_GET['tgid'] > 0)
			{
				//查询邀请人id
				$Decodekey = $this -> getApp('Tgidkey');
				$extend_id = $Decodekey -> Decodekey($_GET['tgid']);;
				//绑定邀请人
				zfun::f_update("User", "id='{$user['id']}'", array("extend_id" => $extend_id,"token"=>md5($user['id'])));
			}
			//跳转到下载APP页
			header("location:http://app.juhuivip.com/?mod=appapi&act=down&ctrl=supdownload&registered=2&tgid=".strval($_GET['tgid'])."&uid=".$user['id']."&mobile=".$user['phone']);
		}

        $this->runplay("wap", "comm", "foot");
		$this->play();
	}
	//搜索
	public function search(){
		$this->runplay("wap", "comm", "comm");
		$uid=$this->getUserId();
		$diplay='on'; if(empty($uid))$diplay='';
		$model=self::model();
		$tmp=array();
		foreach($model as $k=>$v){
			$tmp[$v['SkipUIIdentifier']]=$v;
			if($v['SkipUIIdentifier']=='buy_shangcheng')unset($model[$k]);
		}
		//历史搜索
		$where="userid='$uid' and userid<>0";
		$keyword=zfun::f_select("LastSearch",$where,'',10,0,"time desc");
		$json=zfun::f_json_encode(array("data"=>$tmp));
		$this->assign("buy_taobao",$tmp['buy_taobao']);
		$this->assign("json",$json);
		$this->assign("model",$model);
		$this->assign("keyword",$keyword);
		$this->assign("diplay",$diplay);
		$this->display();
		$this->play();
	}
	//删除历史记录
	public function del_keyword(){
		$uid=$this->getUserId();
		$where="userid='$uid' and userid<>0";
		$result=zfun::f_delete("LastSearch",$where);
		if(empty($result))zfun::fecho("删除失败");
		zfun::fecho("删除成功",1,1);
	}

	//模块
	static function model(){
		$data=zfun::f_select("WapGetModels","is_onoff=1");
		foreach($data  as $k=>$v){
			$v['courses']=str_replace("&#34;",'"',$v['courses']);
			$courses=json_decode($v['courses'],true);
			foreach($courses as $k1=>$v1)$data[$k][$k1]=$v1;

			$data[$k]['courses_img']='';
			if(!empty($courses['courses_img']))$data[$k]['courses_img']=UPLOAD_URL."getmodel/".$courses['courses_img'];
			$v['resou_msg']=str_replace("&#34;",'"',$v['resou_msg']);
	   		$resou_msg=json_decode($v['resou_msg'],true);
			foreach($resou_msg as $k1=>$v1)$data[$k][$k1]=$v1;

			$data[$k]['resou_img']='';
			if(!empty($resou_msg['resou_img']))$data[$k]['resou_img']=UPLOAD_URL."getmodel/".$resou_msg['resou_img'];
			$data[$k]['courses_jumpurl']=str_replace("{INDEX_WEB_URL}",INDEX_WEB_URL,$data[$k]['courses_jumpurl']);
			if(empty($data[$k]['courses_jumpurl']))$data[$k]['courses_jumpurl']='javascript:void(0)';
		}
		return $data;
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
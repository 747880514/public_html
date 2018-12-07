<?php

include_once ROOT_PATH."comm/zfun.php";

class callbackAction extends Action {

	public function isphone(){

		$MobileCheackModel=new MobileCheack();

		return $MobileCheackModel->isMobile();

	}

	public function register($arr=array()){

		if(empty($arr))zfun::fecho("error");

		$arr['reg_time']=time();

		$arr['login_time']=time();

		if(!empty($_COOKIE['tgid'])){

			$tgid = intval($_COOKIE['tgid']);

			$Decodekey = $this -> getApp('Tgidkey');

			$tgid = intval($Decodekey -> Decodekey($tgid));	

			$tmp=zfun::f_count("User","id=$tgid");

			if(!empty($tmp))$arr['extend_id']=$tgid;

		}

		//百里
		$commission_reg=floatval(self::getSetting("commission_reg"));

		$blocking_price_endtime = self::getSetting('blocking_price_endday') > 0 ? self::getSetting('blocking_price_endday') : 3;
		$blocking_price_endtime = time() + $blocking_price_endtime * 24 * 3600;

		$arr["blocking_price"] = $commission_reg;
		$arr["blocking_price_endtime"] = $blocking_price_endtime;	//3天内

		$arr["commission"] = 0;

		$uid=zfun::f_insert("User",$arr);


		$jf_reg = floatval($this -> getSetting('jf_reg'));

		// $commission_reg=floatval(self::getSetting("commission_reg"));

		$jfname=self::getSetting("jf_name");

		$arr['comission']=$commission_reg;

		$arr['integral']=$jf_reg;

		

		if($jf_reg>0)zfun::f_adddetail('注册送' . $jf_reg . $jfname,$uid,6,0,$jf_reg);	

		if($commission_reg>0)zfun::f_adddetail('注册送' . $commission_reg . '佣金',$uid,6,0,$commission_reg);

		$this -> setSessionUser($uid, $arr['nickname']);

		$this -> setCookieUser($uid, $arr['nickname'], 14 * 86400);

		self::imgoto();

	}

	public function imgoto(){

		if(!empty($_COOKIE['reurl'])){

			setcookie("reurl",0,0,"/");

			zfun::jsjump($_COOKIE['reurl']);	

		}

		if (isset($_SESSION["url"]))zfun::jsjump($_SESSION["url"]);

		if(self::isphone()==1)zfun::jsjump($this->getUrl("my","my_home",array(),"wap"));

		zfun::jsjump($this->getUrl("ucenter","index",array(),"default"));

	}

	public function qq() {

		$state = filter_check($_GET['state']);

		if ($_GET['state'] != $_SESSION['qq_state']) {

			$this -> promptMsg($this -> getUrl('login', 'login'), '授权验证不通过！请重新登录', 0, 3);

			exit ;

		}

		$code = $_GET['code'];

		//QQ登录成功后的回调地址,主要保存access token

		$access_token = $this -> qq_callback($code);

		

		//获取用户标示id

		$openid = $this -> qq_get_openid($access_token);

		

		//获取用户信息

		$arr = $this -> qq_get_user_info($access_token, $openid);

		if(empty($arr['nickname']))die(zfun::f_json_encode($arr));

		$user = $this -> getDatabase('User');

		if (!empty($access_token)) {

			$_SESSION["openid"] = $userqid = trim($openid);

			//$_SESSION['type']='qq_au';

			$check = $user -> selectRow("qq_au='$userqid'");

			if (!empty($check)) {

				$_SESSION['typelogin'] = 'qq_au';

				$this -> setSessionUser($check['id'], $check['nickname']);

				self::imgoto();

			} else {

				$_SESSION['userbinding'] = 'qq_au';

				$_SESSION['nickname'] = $arr['nickname'];

				$_SESSION['figureurl_qq_2'] = empty($arr['figureurl_qq_2']) ? $arr['figureurl_2'] : $arr['figureurl_qq_2'];

				$_SESSION['user_sex'] = trim($arr['gender']) == '男' ? 1 : 0;

				$_SESSION['user_address'] = $arr['province'] . $arr['city'];

				

				$arr=array(

					"qq_au"=>$userqid,

					"nickname"=>$_SESSION['nickname'],

					"head_img"=>$_SESSION['figureurl_qq_2'],

					"sex"=>$_SESSION['user_sex'],

				);

				self::register($arr);

			}

		} else {

			$this -> promptMsg($this -> getUrl('index'), 'QQ登录遇到错误！', 0, 3);

			exit ;

		}

	}



	public function sina() {



		require (API_PATH . 'saetv2.ex.class.php');

		$o = new SaeTOAuthV2($_SESSION["sid"], $_SESSION["skey"]);



		$keys = array();



		$keys['code'] = $_GET['code'];

		$keys['redirect_uri'] = urldecode($_SESSION["scallback"]);

		try {

			$token = $o -> getAccessToken('code', $keys);

		} catch (OAuthException $e) {

		}

		$accesstoken = $token['access_token'];

		$_SESSION['weiboid'] = $token['uid'];

		$openid = $_SESSION['weiboid'];

		$user = $this -> getDatabase('User');

		if (!empty($accesstoken)) {

			$check = $user -> selectRow("sina_au = '$openid'");

			if (!empty($check)) {

				$_SESSION['typelogin'] = 'weibo_au';

				$this -> setSessionUser($check['id'], $check['nickname']);

				self::imgoto();

			} else {

				$newUrl = 'https://api.weibo.com/2/users/show.json?source=' . $_SESSION["sid"] . '&access_token=' . $accesstoken . '&uid=' . $openid;

				$req = curl_get($newUrl);

				$req = json_decode($req, true);

				$_SESSION['weibo_screen_name'] = $req['screen_name'];

				$_SESSION['weibo_avatar_hd'] = empty($req['avatar_hd']) ? $req['profile_image_url'] : $req['avatar_hd'];

				$_SESSION['weibo_sex'] = $req['gender'] == "m" ? '1' : 0;

				$_SESSION['userbinding'] = $req['id'];

				

				$arr=array(

					"sina_au"=>$_SESSION['userbinding'],

					"nickname"=>$_SESSION['weibo_screen_name'],

					"head_img"=>$_SESSION['weibo_avatar_hd'],

					"sex"=>$_SESSION['weibo_sex'],

				);

				self::register($arr);

				

			}

		} else {

			$this -> promptMsg($this -> getUrl('index'), '新浪登录遇到错误！', 0, 3);

			exit ;

		}

	}





	public function taobao() {

		$code = $_GET['code'];

		$user = $this -> getDatabase('User');

		if ($code == '') {

			if (isset($_GET['error'])) {

				if ($_GET['error_description'] == 'authorize reject') {

					$this -> promptMsg($this -> getUrl('index', 'index'), '淘宝登录错误，请重试！', 0, 3);

					exit ;

				}

			}

		} else {

			$postfields = array('grant_type' => 'authorization_code', 'client_id' => $_SESSION["tbid"], 'client_secret' => $_SESSION["tbkey"], 'code' => $code, 'redirect_uri' => $_SESSION["tbcallback"]);

			$taobaotoken = $this -> curl('https://oauth.taobao.com/token', $postfields);

			$taobaotoken = urldecode($taobaotoken);

			$tbtoken = json_decode($taobaotoken, 1);

			if (!empty($tbtoken['access_token'])) {

				$_SESSION['type'] = 'taobao_au';

				$token = $tbtoken['access_token'];

				$_SESSION['taobaoid'] = $tbtoken['taobao_user_id'];

				$openid = $_SESSION['taobaoid'];

				$check = $user -> selectRow("taobao_au = '$openid'");

				if (!empty($check)) {

					$_SESSION['typelogin'] = 'taobao_au';

					$this -> setSessionUser($check['id'], $check['nickname']);

					if (isset($_SESSION['url'])) {

						header("Location: " . $_SESSION['url']);

						unset($_SESSION['url']);

						exit ;

					} else {

						$this -> link('ucenter', 'index', NULL, 'base');

						exit ;

					}

				} else {

					$_SESSION['userbinding'] = 'taobao_au';

					$_SESSION['user_nick_name_taobao'] = $tbtoken['taobao_user_nick'];

					$this -> link('user', 'threelogin', NULL, 'base');

					exit ;

				}

				//require($this->getTpl('callback','index'));

			} else {

				$this -> promptMsg($this -> getUrl('index'), '淘宝登录遇到错误！', 0, 3);

				exit ;

			}

		}



	}



	public function binding() {

		$user = $this -> getDatabase('User');

		$type = $_POST['type'];

		if ($_POST['chacktype'] == 1) {

			$checkUser = $user -> selectRow("loginname = '" . trim($_POST['username']) . "'", 'id');



			$url = $this -> getUrl('callback', 'index');

			$token = $_POST['token'];

			$openid = $_POST['openid'];

			if ($checkUser) {

				echo "<script>alert('用户名重复！请重试！');</script>";

				require ($this -> getTpl('callback', 'index'));

				exit ;

			} else {

				$pid = isset($_SESSION['pid']) ? $_SESSION['pid'] : '';

				$loginname = trim($_POST['username']);

				$password = trim($_POST['password']);

				$time = time();

				$suc = $user -> insertId(array('loginname' => $loginname, 'password' => md5($password), 'nickname' => $arr['nickname'], 'head_img' => $arr['figureurl_2'], 'reg_time' => $time, 'login_time' => $time, 'extension_id' => $pid, "$type" => $openid));

				if ($suc > 0) {

					unset($_SESSION['pid']);

					$_SESSION['uid'] = $suc;

					$_SESSION['username'] = $loginname;

					$_SESSION['nickname'] = $arr['nickname'];

					$this -> promptMsg($this -> getUrl('person', 'userinfo'), '登录绑定成功！', 1, 3);

					exit ;

				} else {

					$this -> promptMsg($this -> getUrl('index'), '登录绑定失败！', 0, 3);

					exit ;

				}

			}

		} else {

			$check = $user -> selectRow("loginname = '" . trim($_POST['username']) . "'", 'id,loginname,nickname,password');

			//print_r($check);die;

			if ($check['password'] == md5(trim($_POST['password'])) && empty($check["$type"])) {

				$udata["$type"] = $_POST['openid'];

				if ($user -> update("loginname = '$_POST[username]'", $udata)) {

					//print_r($user);die;

					$_SESSION['uid'] = $check['id'];

					$_SESSION['username'] = $check['loginname'];

					$_SESSION['nickname'] = $check['nickname'];

					$this -> promptMsg($this -> getUrl('person', 'userinfo'), '登录绑定成功！', 1, 3);

					exit ;

				} else {

					$this -> promptMsg($this -> getUrl('index'), '登录绑定失败！', 0, 3);

					exit ;

				}

			} else {

				echo "<script>alert('用户名密码错误或该用户已被绑定！请换另一个账号测试！');</script>";

				$type = $type;

				$token = $_POST['token'];

				$openid = $_POST['openid'];

				require ($this -> getTpl('callback', 'index'));

				exit ;

			}

		}

	}



	public function curl($url, $postFields = null) {

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_FAILONERROR, false);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		//if ($this->readTimeout) {

		curl_setopt($ch, CURLOPT_TIMEOUT, 60);

		//}

		//if ($this->connectTimeout) {

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

		//}

		//https 请求

		if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {

			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		}

		if (is_array($postFields) && 0 < count($postFields)) {

			$postBodyString = "";

			$postMultipart = false;

			foreach ($postFields as $k => $v) {

				if ("@" != substr($v, 0, 1)) {//判断是不是文件上传

					$postBodyString .= "$k=" . urlencode($v) . "&";

				} else {//文件上传用multipart/form-data，否则用www-form-urlencoded

					$postMultipart = true;

				}

			}

			unset($k, $v);

			curl_setopt($ch, CURLOPT_POST, true);

			if ($postMultipart) {

				curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

			} else {

				curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));

			}

		}

		$reponse = curl_exec($ch);

		if (curl_errno($ch)) {

			throw new Exception(curl_error($ch), 0);

		} else {

			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if (200 !== $httpStatusCode) {

				throw new Exception($reponse, $httpStatusCode);

			}

		}

		curl_close($ch);

		return $reponse;

	}



	public function get_url_contents($url) {

		$ch = curl_init();

		$timeout = 5;

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

		$contents = curl_exec($ch);

		curl_close($ch);

		return $contents;

	}



	public function qq_callback($code) {

		$apis = $this -> getDatabase('Api');

		$api = $apis -> selectRow("code='qq'", NULL, NULL, NULL, "sort desc");



		$host = parse_url($this -> getUrl('login', 'login'));

		$callback = urlencode('http://' . $host['host'] . '/qqcallback.php');



		$token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&" . "client_id=" . $api['key'] . "&redirect_uri=" . $callback . "&client_secret=" . $api['secret'] . "&code=" . $code;



		//$response = $this->get_url_contents($token_url);

		$response = curl_get($token_url);

		if (strpos($response, "callback") !== false) {

			$lpos = strpos($response, "(");

			$rpos = strrpos($response, ")");

			$response = substr($response, $lpos + 1, $rpos - $lpos - 1);

			$msg = json_decode($response);

			if (isset($msg -> error)) {

				$this -> promptMsg($this -> getUrl('login', 'login'), 'Q_Q登录出错了！错误代码：' . $msg -> error . '；msg：' . $msg -> error_description, 0, 10);

				exit ;

			}

		}



		$params = array();

		parse_str($response, $params);

		if (!empty($params["access_token"])) {

			return $params["access_token"];

		} else {

			return NULL;

		}

	}



	public function qq_get_openid($access_token) {

		$graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . $access_token;



		//$str  = $this->get_url_contents($graph_url);

		$str = curl_get($graph_url);

		if (strpos($str, "callback") !== false) {

			$lpos = strpos($str, "(");

			$rpos = strrpos($str, ")");

			$str = substr($str, $lpos + 1, $rpos - $lpos - 1);

		}

		$user = json_decode($str);

		if (isset($user -> error)) {

			$this -> promptMsg($this -> getUrl('login', 'login'), 'Q_Q登录出错了！错误代码：' . $msg -> error . '；msg：' . $msg -> error_description, 0, 10);

			exit ;

		}

		if (!empty($user -> openid)) {

			return $user -> openid;

		} else {

			return NULL;

		}

	}



	public function qq_get_user_info($access_token, $openid) {

		$apis = $this -> getDatabase('Api');

		$api = $apis -> selectRow("code='qq'", NULL, NULL, NULL, "sort desc");

		$get_user_info = "https://graph.qq.com/user/get_user_info?" . "access_token=" . $access_token . "&oauth_consumer_key=" . $api['key'] . "&openid=" . $openid . "&format=json";

		//$info = $this->get_url_contents($get_user_info);

		$info = curl_get($get_user_info);

		$arr = json_decode($info, true);

		return $arr;

	}



}

?>
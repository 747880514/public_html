<?php
class helpAction extends Action {
	//使用帮助
	public function commproblem() {
		$HelperArticleModel = $this -> getDatabase('HelperArticle');
		$HelperArticle = $HelperArticleModel -> select('type="apphelper" AND hide=0', 'title,content', null, null, 'sort desc');
		$this -> assign('article', $HelperArticle);
		$this -> display();
		$this -> play();
	}

	//积分规则
	public function integralrule() {
		$HelperArticleModel = $this -> getDatabase('HelperArticle');
		$HelperArticle = $HelperArticleModel -> select('type="apparticle" AND hide=0', 'title,content', null, null, 'sort desc');
		$this -> assign('article', $HelperArticle);
		$this -> display();
		$this -> play();
	}

	//常见问题
	public function usehelp() {
		$HelperArticleModel = $this -> getDatabase('HelperArticle');
		$HelperArticle = $HelperArticleModel -> select('type="appquestion" AND hide=0', 'title,content', null, null, 'sort desc');
		$this -> assign('article', $HelperArticle);
		//zheliwo
		$this->assign("is_show",self::getSetting("question_show"));
		$this->assign("phone",self::getSetting("ContactPhone"));
		$this -> display();
		$this -> play();
	}

	public function service() {
		$this -> display();
		$this -> play();
	}
    public function helper() {
		$apphelp = $this->getSetting('apphelp');
		$this -> assign('apphelp', $apphelp);
		$this -> display();
		$this -> play();
	}
	public function course() {

		$guanggaoModel = $this -> getDatabase('Guanggao');
		$guanggao = $guanggaoModel -> selectRow('type="returnrules" AND hide=0');

		//资源
		$source = array(
			1 => array('title'=>'花蒜APP如何邀请好友注册下载', 'img'=>'one.jpg', 'video'=>'one.mp4'),
			2 => array('title'=>'花蒜APP新用户如何下载登录注册', 'img'=>'two.jpg', 'video'=>'two.MP4'),
			3 => array('title'=>'花蒜APP的大牌超级入口介绍', 'img'=>'three.jpg', 'video'=>'three.mp4'),
			4 => array('title'=>'花蒜老用户如何登录超级APP', 'img'=>'for.jpg', 'video'=>'for.mp4'),
			5 => array('title'=>'花蒜超级APP如何分享赚取佣金', 'img'=>'five.jpg', 'video'=>'five.MP4'),
			6 => array('title'=>'花蒜APP疑问帮助指南', 'img'=>'six.jpg', 'video'=>'six.MP4'),
			7 => array('title'=>'花蒜APP如何通过花蒜领取大额优惠券', 'img'=>'every.jpg', 'video'=>'every.mp4'),
			8 => array('title'=>'一分钟了解花蒜', 'img'=>'eight.jpg', 'video'=>'eight.mp4'),
			9 => array('title'=>'如何升级店主权益', 'img'=>'6646780135942441269.png', 'video'=>'6646780135942441269.MP4'),
			10 => array('title'=>'花蒜下单 VS 淘宝下单', 'img'=>'6646780135942441271.png', 'video'=>'6646780135942441271.MP4'),
			11 => array('title'=>'花蒜超级APP如何分享赚佣金', 'img'=>'6646780135942441273.png', 'video'=>'6646780135942441273.MP4'),
			12 => array('title'=>'花蒜超级APP如何邀请好友', 'img'=>'6646780135942441275.png', 'video'=>'6646780135942441275.MP4'),
			13 => array('title'=>'花蒜超级APP疑问帮助指南', 'img'=>'6646780135942441277.png', 'video'=>'6646780135942441277.MP4'),
			14 => array('title'=>'如何找回淘宝订单', 'img'=>'6646780135942441279.png', 'video'=>'6646780135942441279.MP4'),
		);

		$this->assign('img', str_replace("slide/","",reset(explode(",",$guanggao['img']))));
		$this->assign('source', $source);
		$this->display();
		$this->play();
	}
	public function fenxiao(){
		$HelperArticleModel = $this -> getDatabase('HelperArticle');
		$HelperArticle = $HelperArticleModel -> select('type="appfenxiao" AND hide=0', 'title,content', null, null, 'sort desc');
		$this -> assign('article', $HelperArticle);
		$this -> display();
		$this -> play();	
	}
	//
	//商城详情
	public function shangcheng() {
		$id=intval($_GET['id']);
		$data=zfun::f_row("Shoppingmall","type=1 and id='$id'");
		$this->assign("data",$data);
		$this -> display();
		$this -> play();
	}
	public function buy_course() {
		$guanggao = zfun::f_row("Guanggao",'(type="returnrules") AND hide=0');
		$guanggao1 = zfun::f_row("Guanggao",'(type="screturnrules" ) AND hide=0');
		if(!empty($guanggao['img'])){
			$arr=explode(",",$guanggao['img']);
			if(!empty($arr))$guanggao['img']=UPLOAD_URL."slide/".$arr[0];
		}
		if(!empty($guanggao1['img'])){
			$arr=explode(",",$guanggao1['img']);
			if(!empty($arr))$guanggao1['img']=UPLOAD_URL."slide/".$arr[0];
		}
	
		$this -> assign('img', $guanggao['img']);
		$this -> assign('img1', $guanggao1['img']);
		$this -> display();
		$this -> play();
	}

	/**
	 * 百里
	 * [banner_video1 banner视频]
	 * @Author   Baili
	 * @Email    baili@juhuivip.com
	 * @DateTime 2019-01-04T09:34:48+0800
	 * @return   [type]                   [description]
	 */
	public function banner_video()
	{
		$number = $_REQUEST['number'];
		switch ($number) {
			case '1':
				$video = "one.mp4";
				$jpg = "one.jpg";
				$title = "花蒜APP如何邀请好友注册下载";
				break;
			case '2':
				$video = "two.MP4";
				$jpg = "two.jpg";
				$title = "花蒜APP新用户如何下载登录注册";
				break;
			case '3':
				$video = "three.mp4";
				$jpg = "three.jpg";
				$title = "花蒜APP的大牌超级入口介绍";
				break;
			case '4':
				$video = "for.mp4";
				$jpg = "for.jpg";
				$title = "花蒜老用户如何登录超级APP";
				break;
			case '5':
				$video = "five.MP4";
				$jpg = "five.jpg";
				$title = "花蒜超级APP如何分享赚取佣金";
				break;
			case '6':
				$video = "six.MP4";
				$jpg = "six.jpg";
				$title = "花蒜APP疑问帮助指南";
				break;
			case '7':
				$video = "every.mp4";
				$jpg = "every.jpg";
				$title = "花蒜APP如何通过花蒜领取大额优惠券";
				break;
			case '8':
				$video = "eight.mp4";
				$jpg = "eight.jpg";
				$title = "一分钟了解花蒜";
				break;
		}

		$data['video'] = $video;
		$data['jpg'] = $jpg;
		$data['title'] = $title;
		$this -> assign('data', $data);
		$this -> display();
		$this -> play();
	}
}
?>

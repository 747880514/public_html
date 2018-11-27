<?php
actionfun("appapi/dgappcomm");
class goods_cateAction extends Action{
	public static function setArr(){
		$str="dtk_sxtj_data,app_yhq_zhanwai_onoff,app_high_zhanwai_onoff,app_9_zhanwai_onoff,app_20_zhanwai_onoff";
		$str.=",app_tgphb_zhanwai_onoff,app_ssxlb_zhanwai_onoff,app_qtxlb_zhanwai_onoff";
		$str.=",app_ddq_zhanwai_onoff,app_jpmj_zhanwai_onoff,app_ht_zhanwai_onoff,app_tqg_zhanwai_onoff,app_jhs_zhanwai_onoff";
		$str.=",app_yhq_dtk_type,app_high_dtk_type,app_9_dtk_type,app_20_dtk_type";
		$str.=",app_tgphb_dtk_type,app_ssxlb_dtk_type,app_qtxlb_dtk_type";
		$str.=",app_ddq_dtk_type,app_jpmj_dtk_type,app_ht_dtk_type,app_tqg_dtk_type,app_jhs_dtk_type";
		$str.=",app_double11two_zhanwai_onoff,app_double11twenty4_zhanwai_onoff,app_double11ys_zhanwai_onoff";
		$str.=",app_double11two_dtk_type,app_double11twenty4_dtk_type,app_double11ys_dtk_type";

		$set=zfun::f_getset($str);
		$data=zfun::arr64_decode($set['dtk_sxtj_data']);
		unset($set['dtk_sxtj_data']);
		foreach($data as $k=>$v){
			$set[$k]=$v;
		}
		return $set;
	}
	public function getCates() {
		appcomm::signcheck();appcomm::read_app_cookie();
		$field = "id,category_name,catename";
		
		$att=array("6","1","3","7","31","32","33","34","35","36","37","38","double11two","double11twenty4","double11ys");
		$key=array("app_high_zhanwai_onoff","app_yhq_zhanwai_onoff","app_9_zhanwai_onoff","app_20_zhanwai_onoff","app_tgphb_zhanwai_onoff","app_ssxlb_zhanwai_onoff","app_qtxlb_zhanwai_onoff","app_ddq_zhanwai_onoff","app_tqg_zhanwai_onoff","app_jhs_zhanwai_onoff","app_jpmj_zhanwai_onoff","app_ht_zhanwai_onoff","app_double11two_zhanwai_onoff","app_double11twenty4_zhanwai_onoff","app_double11ys_zhanwai_onoff");
		$bom=array();$bom1=array();$bom2=array();
		$setArr=self::setArr();
		$type=intval($_POST['type']);
		foreach($att as $k=>$v){
			$bom[$v]=$key[$k];
			$bom1[$v]=$key1[$k];
			$bom2[$v]=$dtktype[$k];
		}
		$type_onoff=intval($setArr[$bom[$type]]);
			
		if($type_onoff==2){
			$category=self::cate();
		}else{
			$category = zfun::f_select("Category","pid=0 and id<>5000 AND show_type=1", $field, null, null, "show_sort desc");
			foreach ($category as $k => $v) {
				if(!empty($v['catename']))$category[$k]['category_name']=$v['catename'];
				unset($category[$k]['catename']);
			}
		}
		$arr=array();
		$arr[]=array(
			"id"=>0,
			"category_name"=>"全部",
			
		);
		foreach($category as $k=>$v){
			$arr[]=$v;	
		}
		$category=$arr;
		zfun::fecho("分类",$category,1);
	}
	//咚咚抢时间
	public function dd_time(){
		appcomm::signcheck();
		actionfun("comm/dtk");
		$type=filter_check($_POST['type']);
		if(empty($type)||$type=='34'||strstr(",大淘客双十一2小时定金榜,大淘客双十一24小时定金榜,双11预售精品库,",','.$_POST['type'].','))$data=dtk::dongdongqiang_date();
		else if($type=='35'||$type=='pub_taoqianggou'){
			actionfun("appapi/appCate");
			$data=appCateAction::tqg_time();
		}
		$set=zfun::f_getset("tqg_times_color,tqg_times_checkcolor");
		$set['tqg_times_color']=str_replace("#",$set['tqg_times_color']);
		if(empty($set['tqg_times_color']))$set['tqg_times_color']='FFFFFF';
		$set['tqg_times_checkcolor']=str_replace("#",$set['tqg_times_checkcolor']);
		if(empty($set['tqg_times_checkcolor']))$set['tqg_times_checkcolor']='ED685A';
		foreach($data as $k=>$v){

			//百里
			if($v['date'] == '00:00')
			{
				$data[$k]['date'] = '全部';
			}

			$data[$k]['bj_img']=INDEX_WEB_URL."View/index/img/appapi/comm/taoqianggou_time_img1.png";
			$data[$k]['check_color']=$set['tqg_times_checkcolor'];
			$data[$k]['color']=$set['tqg_times_color'];
		}

		zfun::fecho("咚咚抢时间",$data,1);
	}
	public static function times(){
		$data=array(
			array(
				"date"=>"00:00",
				"time"=>0,
				"str"=>'已开抢',
				"check"=>1,
				"status"=>1,
			),
			
			array(
				"date"=>"08:00",
				"time"=>1,
				"str"=>'已开抢',
				"check"=>0,
				"status"=>1,
			),
			
			array(
				"date"=>"12:00",
				"time"=>2,
				"str"=>'已开抢',
				"check"=>0,
				"status"=>1,
			),
		
			array(
				"date"=>"15:00",
				"time"=>3,
				"str"=>'已开抢',
				"check"=>0,
				"status"=>1,
			),
		);
		$time=time();
		$date=date("Y-m-d",strtotime("today"));
		$count=count($data)-1;
		foreach($data as $k=>$v){
			$date_time=strtotime($date." ".$v['date']);
			if($date_time>$time){
				$data[$k]['status']=0;
				$data[$k]['str']="即将开抢";
			}
			$next='';
			if($k<=$count)$next=strtotime($date." ".$data[$k+1]['date']);
			//如果小于当前时间 且当前时间小于下一个时间
			if($date_time<$time&&!empty($next)&&$time<$next){
			
				$data[$k]['check']=1;
			}
			//如果是最后一个 且小于当前时间
			if($k>=$count&&$date_time<$time){
				
				$data[$k]['check']=1;
			}
			
			$data[$k]['date']="今日 ".$v['date'];
		}
		return $data;
	}
	public static function cate(){
		$arr=array(
			array(
				"id"=>1,
				"category_name"=>"女装",
			),
			array(
				"id"=>9,
				"category_name"=>"男装",
			),
			array(
				"id"=>10,
				"category_name"=>"内衣",
			),
			array(
				"id"=>2,
				"category_name"=>"母婴",
			),
			array(
				"id"=>3,
				"category_name"=>"化妆品",
			),
			array(
				"id"=>4,
				"category_name"=>"居家",
			),
			array(
				"id"=>5,
				"category_name"=>"鞋包配饰",
			),
			array(
				"id"=>6,
				"category_name"=>"美食",
			),
			array(
				"id"=>7,
				"category_name"=>"文体车品",
			),
			array(
				"id"=>8,
				"category_name"=>"数码家电",
			),
			array(
				"id"=>12,
				"category_name"=>"预告",
			),
		);
		return $arr;
	}
	
}
?>
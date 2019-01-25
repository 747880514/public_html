<?php

actionfun("appapi/dgappcomm");

actionfun("appapi/convert_goods");

class convert_orderAction extends Action{

	//订单

	static function order_list($user=array()){

		$uid=$user['id'];

		$where="uid='$uid' and type in (1,2)";

		switch($_POST['status'].''){

			case "wait_check"://待审核

				$where.=" and audit_status=0";

				break;

			case "success_check"://已审核

				$where.=" and audit_status=1";

				break;

			case "fail_check"://已失效

				$where.=" and audit_status=2 ";

				break;

		

        }

        if(!empty($_POST['keyword'])){

            $where.=" and data LIKE '%".$_POST['keyword']."%'";

		}

		//订单开始结束时间

		if(!empty($_POST['start_time'])){$start_time=strtotime($_POST['start_time']);$where.=" and time>=$start_time";}

		if(!empty($_POST['end_time'])){$end_time=strtotime($_POST['end_time']);$where.=" and time<=$end_time";}

		$data=appcomm::f_goods("Authentication",$where,"data,audit_status,time,wl_company,wl_id","time desc",null,20);	//百里追加 wl_company,wl_id

		$status=array("0"=>'待审核',"1"=>'已审核',"2"=>'已失效');

		$color=array("0"=>'FF5A5A',"1"=>'60BBFF',"2"=>'DFDFDF');

		foreach($data as $k=>$v){

			$arr=json_decode($v['data'],true);unset($data[$k]['data']);

			$data[$k]['orderId']=$arr['oid'];

			$data[$k]['order_str']='';

			if(!empty($arr['oid']))$data[$k]['order_str']='订单号:'.$data[$k]['orderId'];

			$data[$k]['fnuo_id']='';

			$data[$k]['goodsInfo']=$arr['goods_title'];

			$data[$k]['goods_img']=$arr['img'];

			if(empty($data[$k]['goodsInfo'])){

				$goods=zfun::f_row("ExchangeRes","id='".$arr['gid']."'");

				$data[$k]['goodsInfo']=$goods['title'];

				if(!empty($goods['img']))$data[$k]['goods_img']=UPLOAD_URL."integral/".$goods['img'];

			}

			$data[$k]['SkipUIIdentifier']='buy_integral';

			$data[$k]['fcommission']='';$data[$k]['fan_all_str']='';

			$data[$k]['time_str']="创建时间:".date("Y-m-d H:i:s",$v['time']);

			//百里.如果存在物流信息
			if(!empty($data[$k]['wl_id']))
			{
				$data[$k]['time_str'] = $data[$k]['wl_company']."：".$data[$k]['wl_id'];
			}

			$data[$k]['shop_type']='积分兑换';

			if(floatval($arr['price'])>0)$data[$k]['shop_type']='现金兑换';

			$data[$k]['shop_type_color']='FFFFFF';

			$data[$k]['label_str']='';

			$data[$k]['payment']=$arr['price'];

			$data[$k]['status']=$status[$v['audit_status']];

			$data[$k]['status_fontcolor']='FFFFFF';

			$data[$k]['fan_all_str']='佣金';

			$data[$k]['fcommission']='0';

			$data[$k]['status_color']=$color[$v['audit_status']];

			

		}

		  /****************订单处理**********************/

		  $order=array();$m=date("m",time());

		 

		  $data=array_values($data);

		  $count=zfun::f_count("Authentication",$where);

		  $order['str']="订单数:".$count."笔";

		  if(empty($data))$data=array();

		  $order['list']=$data;

		  zfun::fecho("积分商城订单",$order,1);

	}

	

}

?>
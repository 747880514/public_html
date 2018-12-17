<?php
class register_commissionAction extends Action{
    static $set=array();
    static function init(){
        $set=zfun::f_getset("commission_reg_order_val,commission_reg,commission_reg_start_time");
        $set['commission_reg_order_val']=doubleval($set['commission_reg_order_val']);
        $set['commission_reg']=doubleval($set['commission_reg']);
        $set['commission_reg_start_time']=intval($set['commission_reg_start_time']);
        if(empty($set['commission_reg_start_time'])){
            $set['commission_reg_start_time']=time();
            $_POST['commission_reg_start_time']=time();
            zfun::setzset("commission_reg_start_time");
        }
        self::$set=$set;
    }
    function index(){
        echo "<title>注册佣金定时器</title>";
        $set=self::$set;
        if(empty($set['commission_reg']))return;//判断是否已开启
        $last_day=strtotime("today")-86400;
        $where="reg_time > $last_day and reg_time > ".$set['commission_reg_start_time'];
        $user=zfun::f_select("User",$where,"id,reg_time",NULL,NULL,"id desc");
        foreach($user as $k=>$v){
            $check=zfun::f_count("Registercommission","uid='".$v['id']."'");
            if(!empty($check))continue;
            $arr=array(
                "uid"=>$v['id'],
                "reg_time"=>intval($v['reg_time']),
                "is_run"=>0,
                "update_time"=>0,
                "time"=>time(),
            );
            zfun::f_insert("Registercommission",$arr);
        }
        //fpre($user);
        fpre("会员数量 ".count($user));
        self::commission_list();
    }
    function commission_list(){
        //只处理 两个月内的会员
        $today=strtotime("today");
        $t1=$today-86400*30*2;
        $where="is_run=0 and update_time < $today and reg_time > $t1";
        $data=zfun::f_select("Registercommission",$where,"",1000,0);
        foreach($data as $k=>$v){
            self::one($v['uid']);
        }
        fpre("处理数量 ".count($data));
    }

    function one($uid=''){
        if(empty($uid))return;
        $set=self::$set;
        if(empty($set['commission_reg']))return;
        $rc=zfun::f_row("Registercommission","uid='{$uid}'");
        if(empty($rc))return;
        if($rc['is_run'].''=='1')return;
        //检测是否 购买满
        $payment=zfun::f_sum("Order","uid='{$uid}' and status IN('订单结算')","payment");
        if($set['commission_reg_order_val']>$payment){//条件不满足
            zfun::f_update("Registercommission","uid='{$uid}'",array("update_time"=>time()));
            return;
        }
        zfun::f_adddetail("注册送 ".$set['commission_reg'] ." 佣金",$uid,0,0,$set['commission_reg']);
        // 百里.注释.注册佣金放在冻结佣金里
        // zfun::addval("User","id='{$uid}'",array("commission"=>$set['commission_reg']));
        zfun::f_update("Registercommission","uid='{$uid}'",array("update_time"=>time(),"is_run"=>1));
    }
}
register_commissionAction::init();
?>
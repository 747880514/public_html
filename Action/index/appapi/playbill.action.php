<?php

actionfun("appapi/dgappcomm");

actionfun("appapi/appHhr");

class playbillAction extends action{

    //海报页面

    public function playbill_msg(){

        $user=appcomm::signcheck(1);$uid=$user['id'];

        

        $tgidkey = $this->getApp('Tgidkey');

		$tid = $tgidkey -> addkey($user['id']);

		$user['tid'] = $tid;if(!empty($user['tg_code']))$user['tid']=$user['tg_code'];

        $set=self::getsets();

        $url1=self::getUrl('invite_friend', 'new_packet', array('tgid' => $user['tid']),'new_share');

		$url2 = (self::getUrl('down', 'supdownload', array('tgid' => $user['tid']),'appapi'));

		if(!empty($set['share_host'])){

			$url1=str_replace(HTTP_HOST,$set['share_host'],$url1);$url2=str_replace(HTTP_HOST,$set['share_host'],$url2);

		}

		$url4=$set['android_url'];

        if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";

        

        if(intval($set['tg_durl'])==1){

            $arrulr=appHhrAction::bdurl($url1,$url2);//0.13

			if(!empty($arrulr[0]))$url1=$arrulr[0];

			if(!empty($arrulr[1]))$url2=$arrulr[1];

		}

        $set['haibao_share_wenan']=str_replace("{下载链接}",$url2,$set['haibao_share_wenan']);

        $set['haibao_share_wenan']=str_replace("{邀请注册链接}",$url1,$set['haibao_share_wenan']);

        $set['haibao_share_wenan']=str_replace("{应用宝下载链接}",$url4,$set['haibao_share_wenan']);

        $set['haibao_share_wenan']=str_replace("{邀请码}",$user['tid'],$set['haibao_share_wenan']);

        unset($set['share_host'],$set['android_url'],$set['tg_durl']);

        zfun::fecho("海报页面信息",$set,1);

    }

    

    //

    static function getsets(){

        $set=zfun::f_getset("haibao_left_bordercolor,haibao_right_bordercolor,haibao_tip_str,haibao_left_bordercolor,haibao_right_bordercolor,share_host,android_url,tg_durl,haibao_left_str,haibao_left_strcolor,haibao_left_btncolor,haibao_right_str,haibao_right_strcolor,haibao_right_btncolor,haibao_share_wenan");

        if(empty($set['haibao_share_wenan'])){$set['haibao_share_wenan']='邀请您加入APP，下载链接：{下载链接}';}

        if(empty($set['haibao_left_str'])){$set['haibao_left_str']='复制邀请链接';}

        if(empty($set['haibao_left_strcolor'])){$set['haibao_left_strcolor']='F43E79';}

        if(empty($set['haibao_left_bordercolor'])){$set['haibao_left_bordercolor']='F43E79';}

        if(empty($set['haibao_left_btncolor'])){$set['haibao_left_btncolor']='00000000';}

        if(empty($set['haibao_right_str'])){$set['haibao_right_str']='分享邀请海报';}

        if(empty($set['haibao_right_strcolor'])){$set['haibao_right_strcolor']='FFFFFF';}

        if(empty($set['haibao_right_btncolor'])){$set['haibao_right_btncolor']='F43E79';}

        if(empty($set['haibao_right_bordercolor'])){$set['haibao_right_bordercolor']='F43E79';}

        if(empty($set['haibao_tip_str']))$set['haibao_tip_str']='注：由于新版微信调整了分享策略，如遇到多图无法分享至朋友圈，请先保存图片再打开微信分享。';

        return $set;

    }

    //集成二维码

    public function getcode(){

		$id=intval($_GET['id']);

		$token=filter_check($_GET['token']);

		$set=zfun::f_getset("AppDisplayName");

		$user=zfun::f_row("User","token='$token'");$uid=$user['id'];

		$model=zfun::f_row("DgAppModel","id='$id'");

		if(empty($model['content_first']))$model['content_first']='扫一扫注册下载'.$set['AppDisplayName'];

		self::qrcode2($model,$user,1);

    }

    //百里修改

    public static function qrcode2($arr,$user,$new=0){//生成二维码

        $user=appcomm::getheadimg(array($user));$user=reset($user);

		$tgidkey = self::getApp('Tgidkey');

		$tid = $tgidkey -> addkey($user['id']);

		$user['tid'] = $tid;

        if(!empty($user['tg_code']))$user['tid']=$user['tg_code'];

        $str="share_host,haibao_share_onoff,android_url,AppLogo,AppDisplayName,is_show_codelogo";

        $str.=",code_rgb_color1,code_rgb_color2,code_rgb_color3,code_rgb_color4,code_rgb_str1,code_rgb_str2,pincode_data";

        $set=zfun::f_getset($str);

        if(!empty($set['AppLogo']))$set['AppLogo']=UPLOAD_URL."slide/".$set['AppLogo'];

		$url=self::getUrl('invite_friend', 'new_packet', array('tgid' => $user['tid']),'new_share');

		if($set['haibao_share_onoff']==1)$url = (self::getUrl('down', 'supdownload', array('tgid' => $user['tid']),'appapi'));

		if(!empty($set['share_host'])){

			$url=str_replace(HTTP_HOST,$set['share_host'],$url);

		}

		$url4=$set['android_url'];

		if(empty($url4))$url4=INDEX_WEB_URL."?act=api&ctrl=downloadfile";

        if($set['haibao_share_onoff']==2)$url=$url4;

        $data = array();

        $set['pincode_data']='eyJmb250Ijp7IngiOiIxODAiLCJ5IjoiMTAwIiwid2lkdGgiOiI0MyIsImhlaWdodCI6IjI1Iiwic2l6ZSI6IjIyIn0sImNvZGV0b3Bmb250Ijp7IngiOiIyNTEiLCJ5IjoiNjM1Iiwid2lkdGgiOiIxMjQiLCJoZWlnaHQiOiIyNSIsInNpemUiOiIyNSJ9LCJjb2RlYnRtZm9udCI6eyJ4IjoiMjc4IiwieSI6IjEwMjkiLCJ3aWR0aCI6Ijk3IiwiaGVpZ2h0IjoiMjIiLCJzaXplIjoiMjUifSwiY29kZXRnaWRmb250Ijp7IngiOiIzMTUiLCJ5IjoiMTA4MCIsIndpZHRoIjoiNjAiLCJoZWlnaHQiOiIzMCIsInNpemUiOiIyOCJ9LCJsb2dvIjp7IngiOiI1MiIsInkiOiI1MiIsIndpZHRoIjoiODAiLCJoZWlnaHQiOiI4MCJ9LCJjb2RlX2xvZ28iOnsieCI6IjIxNSIsInkiOiI2NjEiLCJ3aWR0aCI6IjMyMCIsImhlaWdodCI6IjMyMCJ9LCJjb2RlaGVhZF9pbWdfbG9nbyI6eyJ4IjoiMzQxIiwieSI6Ijc4NyIsIndpZHRoIjoiNjgiLCJoZWlnaHQiOiI2OCJ9fQ==';

        $pincode_data=zfun::arr64_decode($set['pincode_data']);

		$data['width']=750;

		$data['height']=1334;

        $data['list'][0] = array(//背景图

            "url" => UPLOAD_URL."model/".$arr['img_max'],

            "x" => 0,

            "y" => 0,

            "width" => 750,

            "height" => 1334,

			"type"=>"png"

        );

        //百里.替换域名生成二维码
        $url = "http://".$set['share_host']."/?mod=appapi&act=down&ctrl=get_unionid&tgid=".$tid;

		$data['list'][1] = array(//二维码

            "url" => INDEX_WEB_URL."comm/qrcode/?url=".urlencode($url)."&size=15&codeKB=1",

            "x" =>$pincode_data['code_logo']['x'],

            "y" => $pincode_data['code_logo']['y'] + 300,

            "width" => $pincode_data['code_logo']['width'],

            "height" => $pincode_data['code_logo']['height'],

			"type"=>"png"

        );

        $data['list'][2] = array(//头像

            // "url" => $user['head_img'],

            // "x" =>$pincode_data['codehead_img_logo']['x'],

            // "y" => $pincode_data['codehead_img_logo']['y'],

            // "width" => $pincode_data['codehead_img_logo']['width'],

            // "height" => $pincode_data['codehead_img_logo']['height'],

            // "type"=>"png"

        );

        // if($set['is_show_codelogo']!=1){
        //     $data['list'][3] = array(//appLOGO

        //         "url" => $set['AppLogo'],

        //         "x" =>$pincode_data['logo']['x'],

        //         "y" => $pincode_data['logo']['y'],

        //         "width" => $pincode_data['logo']['width'],

        //         "height" => $pincode_data['logo']['height'],

        //         "type"=>"png"

        //     );

        // }

         // $text=$set['code_rgb_str1'];

         // $text_width=intval(mb_strlen($text,"utf-8")*25/2)+12;

         $data['text'][0]=array(//二维码上方图片

			"size"=>$pincode_data['codetopfont']['size'],

			"x" =>$pincode_data['codetopfont']['x'],

            "y" => $pincode_data['codetopfont']['y'],

            "width" => $pincode_data['codetopfont']['width'],

            "height" => $pincode_data['codetopfont']['height'],

			"val"=>$text,

            "color"=>3,

            "rgb"=>$set['code_rgb_color1'],

        );

       

        // $text1=$set['code_rgb_str2'];

        // $text1_width=intval(mb_strlen($text1,"utf-8")*25/2)+10;

        $data['text'][1]=array(//二维码下方图片

			"size"=>$pincode_data['codebtmfont']['size'],

			"x" =>$pincode_data['codebtmfont']['x'],

            "y" => $pincode_data['codebtmfont']['y'],

            "width" => $pincode_data['codebtmfont']['width'],

            "height" => $pincode_data['codebtmfont']['height'],

			"val"=>$text1,

            "color"=>3,

            "rgb"=>$set['code_rgb_color2'],

        );

        $tgid_width=intval(mb_strlen($user['tid'],"utf-8")*25/2)-2;

        //百里.邀请码位置调整（不同位数）
        $leftpx = 375 - ( strlen($user['tid']) * 22 ) / 2;

        $data['text'][2]=array(

			"size"=>$pincode_data['codetgidfont']['size'],

			"x" => $leftpx,  //$pincode_data['codetgidfont']['x'],

            "y" => $pincode_data['codetgidfont']['y'] - 130,

            "width" => $pincode_data['codetgidfont']['width'],

            "height" => $pincode_data['codetgidfont']['height'],

			"val"=>$user['tid'],

            "color"=>3,

            "rgb"=>$set['code_rgb_color3'],

        );

        // $text2=$set['AppDisplayName'];

        // $name_width=intval(mb_strlen($text2,"utf-8")*22/2)+10;

        if($set['is_show_codelogo']!=1){

            $data['text'][3]=array(

                "size"=>$pincode_data['font']['size'],

                "x" =>$pincode_data['font']['x'],

                "y" => $pincode_data['font']['y'],

                "width" => $pincode_data['font']['width'],

                "height" => $pincode_data['font']['height'],

                "val"=>$text2,

                "color"=>3,

                "rgb"=>$set['code_rgb_color4'],

            );

        }

       

        fun("pic");

        return pic::getpic($data);

    }

}

?>
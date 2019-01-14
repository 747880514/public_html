<?php

/**

 * 积分管理

 * integral_shop_list 积分商品列表

 * integral_goods_class 积分商品分类

 * exchange_audit 兑换审核

 */

fun("ztp");

fun("admin");

include_once ROOT_PATH."comm/zfun.php";

class integral_manageAction extends Action{

    /**

     * 积分商城列表

     * 

     */

    public function integral_shop_list(){

		

        ztp::title("积分商品列表");

        ztp::addjs("comm/addtop.js");

        $edit_url=urlencode(self::getUrl("integral_manage","integral_shop_list_edit"));

        $del_url=urlencode(self::getUrl("integral_manage","integral_shop_list_del"));

        $add_url=self::getUrl("integral_manage","integral_shop_list_add");

        ztp::addtop("text","商品名称",$_GET['goods_name'],"name=goods_name");

        ztp::addtop("submit","搜索");

        ztp::addtop("a","添加",$add_url,"id='add'");





        if(!empty($_GET['goods_name']))$goods_name=$_GET['goods_name'];

        $where="title like '%{$goods_name}%'";



        if(!empty($_POST['page'])){

          $_GET['p']=$_POST['page'];  

        }

        $GLOBALS['ztp']['jump']="{link:integral_manage-integral_shop_list}";

        ztp::addjs("comm/foot_jump.js");

        $GLOBALS['ztp']['is_join']="{link:integral_manage-is_join}";

        $integral=zfun::f_goods("ExchangeRes",$where,$filed,"sort desc",null,12);

        $cid_name=zfun::f_kdata("Exchangerclass",$integral,"cid","id","id ,name");

        foreach ($integral as $k => $v) {

            $integral[$k]['caozuo']=ztp::cstrarr("button=查看|{$edit_url}&id-".$v['id'].",button=删除|{$del_url}&id-".$v['id']."| msg-'是否删除' ");

          

            if(!empty($v['startTime'])) $integral[$k]['startTime']=date("Y-m-d",$v['startTime']);

            else  $integral[$k]['startTime']='';

            if(!empty($v['endTime'])) $integral[$k]['endTime']=date("Y-m-d",$v['endTime']);

            else $integral[$k]['endTime']='';

            $integral[$k]['cid']=$cid_name[$v['cid']]['name'];

            $integral[$k]['img']="<a href='#' class='view'>". "查看". "</a>";

            switch ($v['hide']) {

                case 1:

                    $integral[$k]['hide']="隐藏";

                    break;

                case 0:

                    $integral[$k]['hide']="显示";

                    break;

            }

			$data=json_decode($v['goods_type'],true);

            unset($v['goods_type']);

            $arr=array();

            foreach($data as $kk=>$vv){

                if(empty($vv))continue;

                $arr[$vv]['name']=$vv;

                $arr[$vv]['checked']="checked='checked'";

            }



			$integral[$k]['app1']=self::app1($integral[$k],$arr,$v);

			unset($integral[$k]['content'],$integral[$k]['introduce']);

			

        }

        ztp::addjs("integral/join.js");

        $str="ID=id/width-50,商品名称=title,图片=img,兑换积分=nowIntegral,所属分类=cid,显示=hide,库存=counts,结束时间=endTime,app栏目=app1/width-280,管理=caozuo/width-300";

		

		zfun::isoff($integral);

		

        ztp::settableaction(self::getUrl("integral_manage","integral_shop_list_del"));

        ztp::addtable($str,$integral);

        zfun::f_setadminurl();

        ztp::play();

    }

    public static function app1($goods_list,$arr,$v){

        $app="<p style='padding:3px 50px;display: block; text-align: left;'><input type='checkbox' style='margin-top:4px;'  gid=".$v['id']." is_join='pub_integral_newgoods' ".filter_check($arr['pub_integral_newgoods']['checked'])." onclick='is_join(this)'> 新品推荐 </p>";

        $app.="<p style='padding:3px 50px;display: block; text-align: left; '><input type='checkbox' style='margin-top:4px;'  gid=".$v['id']." is_join='pub_integral_hotgoods' ".filter_check($arr['pub_integral_hotgoods']['checked'])." onclick='is_join(this)'> 热门推荐 </p>";

        $app.="<p style='padding:3px 50px;display: block; text-align: left;'><input type='checkbox' style='margin-top:4px;'  gid=".$v['id']." is_join='pub_integral_moneychangegoods' ".filter_check($arr['pub_integral_moneychangegoods']['checked'])." onclick='is_join(this)'> 现金兑换 </p>";

        $app.="<p style='padding:3px 50px;display: block; text-align: left; '><input type='checkbox' style='margin-top:4px;'  gid=".$v['id']." is_join='pub_integral_changegoods' ".filter_check($arr['pub_integral_changegoods']['checked'])." onclick='is_join(this)'> 积分兑换 </p>";

        return $app;

    }

    // 新家

    public function is_join(){

        $data=zfun::getpost("is_join,id");

		$goods=zfun::f_row("ExchangeRes","id='".intval($data['id'])."'","goods_type");

        $arr=json_decode($goods['goods_type'],true);

        

		$i=0;

		$tmp=array();

		foreach($arr as $k=>$v){

			$tmp[$v]=$v;

		}

		if(!empty($arr)){

			if(in_array($data['is_join'],$arr)==false){

				$arr[]=$data['is_join'];

			}else{

				unset($tmp[$data['is_join']]);

				$arr=array_values($tmp);

			}

		}else $arr[]=$data['is_join'];

		$arr=json_encode($arr);

        $result=zfun::f_update("ExchangeRes","id=".intval($data['id']),array("goods_type"=>$arr));

        if($result>0){

            $flag=1;

        }else{

            $flag=0;

        }

        echo json_encode(array("flag"=>$flag),true);

    }

    /**

     * 添加积分商品

     */

    public function integral_shop_list_add(){

        ztp::title("积分商品添加");

        $return_url=self::getUrl("integral_manage","integral_shop_list");

        $data=array();

        if(!empty($_POST['submit'])){

           

            $data['price']=floatval($_POST['price']);

            $data['postage']=floatval($_POST['postage']);

            $data['cid']=$_POST['cid'];

            $data['label']=$_POST['label'];

            $data['label_color']=str_replace("#","",$_POST['label_color']);

            $data['detail_label']=$_POST['label1'].",".$_POST['label2'].",".$_POST['label3'];

            $data['title']=$_POST['title'];

            $data['nowIntegral']=$_POST['nowIntegral'];

            $data['oldIntegral']=$_POST['oldIntegral'];

            $data['sales']=intval($_POST['sales']);

            $data['counts']=intval($_POST['counts']);

            $data['content']=addslashes($_POST['content']);

            $data['introduce']=addslashes($_POST['introduce']);

            $data['exchangType']=$_POST['exchangType'];

            $data['startTime']=strtotime($_POST['startTime']);

            $data['endTime']=strtotime($_POST['endTime']);

            $data['sort']=intval($_POST['sort']);

            $data['hide']=$_POST['hide'];

            if(!empty($_POST['detail_getimg']))$data['detail_getimg']=$_POST['detail_getimg'];

            if(!empty($_POST['banner_img'])){

                $data['banner_img']=$_POST['banner_img'];

                $img=explode(",",$data['banner_img']);

                $data['img']=$img[0];

            }

           

             //属性处理

             $tmp=self::getattr();

             $data['attr_data']=zfun::f_json_encode($tmp);

            $result=zfun::f_insert("ExchangeRes",$data);

            admin::getadd($result);

        }



        $category=zfun::f_goods("Exchangerclass",$where,$filed,null,null,null);

        $cate="请选择分类=0";

        foreach ($category as $k => $v) {

            $cate.=",".$v['name'].'='.$v['id'];

        }

        $GLOBALS['ztp']['getimg']="{link:integral_manage-addimg}";

        $type=intval($_POST['exchangType']) <1 ? 0: 1;

        $hide=intval($_POST['hide']) <1 ? 0 : 1;

       

        ztp::add("select","物品类目",$cate,"name=cid val=".$_POST['cid']);

        ztp::add("text","商品名称",$_POST['title'],"name=title");

        ztp::add('texts',"积分与金额","jj=".$integral['nowIntegral']."/name-nowIntegral,ll=".$integral['price']."/name-price","","左  积分  右 金额");    

       

        ztp::add("text","原价",$_POST['oldIntegral'],"name=oldIntegral","商品人民币的价格");

        ztp::add("text","邮费",$_POST['postage'],"name=postage","请输入数字");    

        ztp::add('texts',"商品列表标签","jj=".$integral['label']."/name-label,ll=".$integral['label_color']."/name-label_color","","左  文字  右 颜色");            

        ztp::add('texts',"商品详情标签","jj=".$integral['label1']."/name-label1,ll=".$integral['label2']."/name-label2,ll=".$integral['label3']."/name-label3","","");            

     

        ztp::add("text","销量",$_POST['sales'],"name=sales","请输入数字");

        ztp::add("text","库存",$_POST['counts'],"name=counts","请输入数字");

        ztp::add("file","物品图片",""," multiple id=img","可一次上传多张 (375*375) ");

        ztp::add('hidden',"","","name=banner_img id=goods_img");

        ztp::add("file","详情图片",""," multiple id=detail_img","可一次上传多张 ");

        ztp::add('hidden',"","","name=detail_getimg id=detail_imgs");

        ztp::add("time","开始时间",$_POST['startTime'],"name=startTime","格式:2013-1-1");

        ztp::add("time","结束时间",$_POST['endTime'],"name=endTime","格式:2013-12-31");

        ztp::add("text","添加商品属性","","name=attr_num id=attr_num","请选择数量");



        ztp::add("text","排序",$_POST['sort'],"name=sort","请输入纯数字，值越大月靠前(0为默认排序)");

        ztp::add("radio","是否显示","显示=0,隐藏=1","name=hide val=".$hide,"默认隐藏");

        ztp::add("radio","分类","虚拟物品=0, 实物物品=1","name=exchangType val=".$type,"默认实物物品");

       // ztp::add("maxtext","兑换规则",$_POST['content'],"name=content");

     //   ztp::add("maxtext","商品详情",$_POST['introduce'],"name=introduce");

        ztp::add("submit","保存");

        $GLOBALS['ztp']['attr_data']=array();

        ztp::addjs("integral/attr.js");

        ztp::addjs("integral/img.js");

        ztp::play();

    }

    public function addimg(){

        //图片处理



        $str='-1';



        foreach($_FILES as $k=>$v)$str.=",".$k;



        $str=substr($str,3);



        if(!empty($str))$img=zfun::f_simg($str,"integral");



        $imgarr='-1';



        foreach($img as $k=>$v){if(!empty($v))$imgarr.=",".$v;}



        $imgarr=substr($imgarr,3);

        zfun::fecho("图片",array("img"=>$imgarr),1);

    }

    /**

     * 删除积分商品

     */

    public function integral_shop_list_del(){

        $ids=$_POST['id'];

        if(!empty($_GET['id'])){

            $ids=intval($_GET['id']);   

        }

        if(!empty($_POST['ch'])){

            $ids=implode(",",$_POST['ch']); 

        }

        

        $result=zfun::f_delete("ExchangeRes","id IN($ids)");

       admin::getdel($result);

  

    }

     /**

     * 编辑积分商品

     */

    public function integral_shop_list_edit(){

        ztp::title("查看积分商品");

        $return_url=self::getUrl("integral_manage","integral_shop_list");

        $id=$_GET['id'];

        if(empty($id))zfun::alert("操作错误");

        $where="id = {$id}";

        $data=array();

        if(!empty($_POST['submit'])){

              

            $data['price']=floatval($_POST['price']);

            $data['postage']=floatval($_POST['postage']);

            $data['cid']=$_POST['cid'];

            $data['label']=$_POST['label'];

            $data['label_color']=str_replace("#","",$_POST['label_color']);

            $data['detail_label']=$_POST['label1'].",".$_POST['label2'].",".$_POST['label3'];

            $data['title']=$_POST['title'];

            $data['nowIntegral']=$_POST['nowIntegral'];

            $data['oldIntegral']=$_POST['oldIntegral'];

            $data['sales']=$_POST['sales'];

            $data['counts']=$_POST['counts'];

            $data['content']=addslashes($_POST['content']);

            $data['introduce']=addslashes($_POST['introduce']);

            $data['exchangType']=$_POST['exchangType'];

            $data['startTime']=strtotime($_POST['startTime']);

            $data['endTime']=strtotime($_POST['endTime']);

            $data['sort']=intval($_POST['sort']);

            $data['hide']=$_POST['hide'];

            if(!empty($_POST['detail_getimg']))$data['detail_getimg']=$_POST['detail_getimg'];

            if(!empty($_POST['img'])){

                $data['banner_img']=$_POST['img'];

                $img=explode(",",$data['banner_img']);

                $data['img']=$img[0];

            }

            

            //属性处理

            $tmp=self::getattr();

            $data['attr_data']=zfun::f_json_encode($tmp);

            $result=zfun::f_update("ExchangeRes",$where,$data);

            admin::getadd($result);

        }



        $integral=zfun::f_row("ExchangeRes",$where);

        if(!empty($integral['startTime']))$integral['startTime']=date("Y-m-d",$integral['startTime']);

        else $integral['startTime']='';

        if(!empty($integral['endTime']))$integral['endTime']=date("Y-m-d",$integral['endTime']);

        else $integral['endTime']='';

        $category=zfun::f_goods("Exchangerclass");

        $cate="请选择分类=0";

        foreach ($category as $k => $v) {

            $cate.=",".$v['name'].'='.$v['id'];

        }

        $attr_data=json_decode($integral['attr_data'],true);

        if(empty($attr_data))$attr_data=array();

        $count=count($attr_data);

        $GLOBALS['ztp']['attr_count']=$count;

        foreach($attr_data as $k=>$v){

            $attr_data[$k]['num']=count($v['attr_val']);

        }

        $GLOBALS['ztp']['attr_data']=$attr_data;

        $label=explode(",",$integral['detail_label']);

        $GLOBALS['ztp']['getimg']="{link:integral_manage-addimg}";

        ztp::add("select","物品类目",$cate,"name=cid val=".$integral['cid']);

        ztp::add("text","商品名称",$integral['title'],"name=title");

        ztp::add('texts',"积分与金额","jj=".$integral['nowIntegral']."/name-nowIntegral,ll=".$integral['price']."/name-price","","左  积分  右 金额");

        ztp::add("text","原价",$integral['oldIntegral'],"name=oldIntegral","商品人民币的价格");

        ztp::add("text","邮费",$integral['postage'],"name=postage","请输入数字");    

        ztp::add('texts',"商品列表标签","jj=".$integral['label']."/name-label,ll=".$integral['label_color']."/name-label_color","","左  文字  右 颜色");            

        ztp::add('texts',"商品详情标签","jj=".$label[0]."/name-label1,ll=".$label[1]."/name-label2,ll=".$label[2]."/name-label3","","");            

        ztp::add("text","销量",$integral['sales'],"name=sales","请输入数字");

        ztp::add("text","库存",$integral['counts'],"name=counts","请输入数字");

       

        ztp::add("file","物品图片",$integral['img']," multiple id=img","可一次上传多张  (375*375) ");

        ztp::add('hidden',"","","name=img id=goods_img");

        ztp::add("file","详情图片",$integral['detail_getimg']," multiple id=detail_img","可一次上传多张 ");

        ztp::add('hidden',"","","name=detail_getimg id=detail_imgs");

        ztp::add("time","开始时间",$integral['startTime'],"name=startTime","格式:2013-1-1");

        ztp::add("time","结束时间",$integral['endTime'],"name=endTime","格式:2013-12-31");

		ztp::add("text","添加商品属性",$count,"name=attr_num id=attr_num","请选择数量");



        ztp::add("text","排序",$integral['sort'],"name=sort","请输入纯数字，值越大月靠前(0为默认排序)");

        ztp::add("radio","是否显示","显示=0,隐藏=1","name=hide val=".$integral['hide'],"默认隐藏");

        ztp::add("radio","分类","虚拟物品=0, 实物物品=1","name=exchangType val=".$integral['exchangType'],"默认实物物品");

        //ztp::add("maxtext","兑换规则",urlencode($integral['content']),"name=content isurlencode='on'");

       // ztp::add("maxtext","商品详情",urlencode($integral['introduce']),"name=introduce isurlencode='on'");

        ztp::add("submit","保存");

        ztp::addjs("integral/attr.js");

        ztp::addjs("integral/img.js");

        ztp::play();

    }

    //属性处理

	static function getattr(){

		//属性处理

		$attr_cate_name=$_POST['attr_cate_name'];

		$attr_cate_num=$_POST['attr_cate_num'];

		$attr_val=$_POST['attr_val'];

		$tmp=array();

		foreach($attr_cate_name as $k=>$v){

			$tmp[$k]['name']=$v;

			$tmp[$k]['attr_val']=array();

			if($k==0)$start=0;

			else $start=$attr_cate_num[$k-1];

			$end=$start+$attr_cate_num[$k];

			for($i=$start;$i<$end;$i++){

				$tmp[$k]['attr_val'][$i]['name']=$attr_val[$i];

				$tmp[$k]['attr_val'][$i]['id']=$k."_".($i-$start);

			}

			$tmp[$k]['attr_val']=array_values($tmp[$k]['attr_val']);

		}

		return $tmp;

	}

    /**

     * 积分商品分类**

     * 

     */

    public function integral_goods_class(){

        ztp::title("积分商品分类");

        ztp::addtop("text","类目名称",$_GET['name'],"name=name");

        ztp::addtop("submit","搜索");

        $add_url=self::getUrl("integral_manage","integral_goods_class_add");

        $edit_url=urlencode(self::getUrl("integral_manage","integral_goods_class_edit"));

        $del_url=urlencode(self::getUrl("integral_manage","integral_goods_class_del"));

        ztp::addjs("comm/addtop.js");

        ztp::addtop('a',"+添加",$add_url,"id='add'");

        $where='Type in(0,1)';

        if(!empty($_GET['name']))$name=$_GET['name'];

        $where.=" AND name like '%{$name}%'";



        if(!empty($_POST['page'])){

          $_GET['p']=$_POST['page'];  

        }

        $GLOBALS['ztp']['jump']="{link:integral_manage-integral_goods_class}";

        ztp::addjs("comm/foot_jump.js");

        $class=zfun::f_goods("Exchangerclass",$where,$filed,"sort desc",null,12);

        foreach ($class as $k => $v) {

        $class[$k]['caozuo']=ztp::cstrarr("button=查看|{$edit_url}&id-".$v['id'].",button=删除|{$del_url}&id-".$v['id']."| msg-'是否删除' ");

        $class[$k]['imgPic']="<a href='#' class='view'>"."查看"."</a>".'<img style="display:none;" src='.$v['img'].'>';

        switch ($v['hide']) {

            case 1:

               $class[$k]['hide']="隐藏";

                break;

            

            case 0:

               $class[$k]['hide']="显示";

                break;

            }

        }



        $str="ID=id/width-50,类目名称=name,图片=imgPic,是否显示=hide,排序=sort,管理=caozuo/width-200";

        ztp::settableaction(self::getUrl("integral_manage","integral_goods_class_del"));

        ztp::addtable($str,$class);

        zfun::f_setadminurl();

        ztp::play();

    }



    /**

     * 积分商品分类添加

     */

    public function integral_goods_class_add(){

        ztp::title("积分商品分类添加");

        $data=array();



        if(!empty($_POST)){

            $data['name']=$_POST['name'];

            $data['sort']=intval($_POST['sort']);

            $data['hide']=$_POST['hide'];

            $data['exchangType']=$_POST['exchangType'];

           

            // 处理上传图片

            if (!empty($_FILES['imgPic']['name'])) {

                $photo_file = $_FILES['imgPic']['name'];

                $upload_dir = ROOT_PATH . 'Upload/integral/';

                $position = strrpos($photo_file, ".");

                $suffix = substr($photo_file, $position + 1, strlen($photo_file) - $position);

                if(!($suffix == "png" || $suffix == "jpg" || $suffix == "gif" || $suffix == "jpeg")) {

                    admin::getmsg(0,"请上传png,jpg,gif,jpeg类型的图片",0);

                }

                if($_FILES['imgPic']['size'] > 512000) {

                    admin::getmsg(0,"图片不能超过500K！",0);

                }

                $imgName = time() . '.' . $suffix;

                $uploadfile = $upload_dir . $imgName;

                if (move_uploaded_file($_FILES['imgPic']['tmp_name'], $uploadfile)) {

                    $data['imgPic'] = $imgName;

                }

                $_SESSION['imgPic']=$imgName;

            }



            if(empty($data['imgPic'])){$data['imgPic']=$img= $_SESSION['imgPic'];}



            //兑换数值类型 

            if($_POST['exchangType']==1){

                $data['var'] = $_POST['var01'] . ',' . $_POST['var02'] . ',' . $_POST['var03'] . ',' . $_POST['var04'] . ',' . $_POST['var05'] . ',' . $_POST['var06'];

                $data['val'] = $_POST['val01'] . ',' . $_POST['val02'] . ',' . $_POST['val03'] . ',' . $_POST['val04'] . ',' . $_POST['val05'] . ',' . $_POST['val06'];

                $data['content']=$_POST['content'];

            }

        

            $result=zfun::f_insert("Exchangerclass",$data);

            admin::getadd($result);



        }



        $return_url=self::getUrl("integral_manage","integral_goods_class");

        $exchangType=intval($_POST['exchangType'])<1 ? 0: 1;

        $hide=intval($_POST['hide'])<1 ? 0 :1;

        // js

        ztp::addjs("comm/jquery.min.js");

        ztp::addjs("integral_manage/integral_goods_class.js");



        // ztp::setaction(self::getUrl('integral_manage',"chuli"));



        ztp::add("text","类目名称",$_POST['name'],"name=name");

        ztp::add("file","修改图片","","name=imgPic","请上传尺寸不超过140×95，大小不超过500k的图片");

        ztp::add("text","排序",$_POST['sort'],"name=sort","请输入纯数字，值越大越靠前(0为默认排序)");

        ztp::add("radio","兑换换类目类型","兑换物品类型=0,兑换数值类型=1","id=type name=exchangType val=".$exchangType);

        

       // 选中兑换数值类型，否则不显示

        ztp::add("texts","兑换数值及对应积分","ll=".$_POST['var01']."/name-var01,ll=".$_POST['val01']."/name-val01,ll=".$_POST['var02']."/name-var02,ll=".$_POST['val02']."/name-val02,ll=".$_POST['var03']."/name-var03,ll=".$_POST['val03']."/name-val03,ll=".$_POST['var04']."/name-var04,ll=".$_POST['val04']."/name-val04,ll=".$_POST['var05']."/name-var05,ll=".$_POST['val05']."/name-val05,ll=".$_POST['var06']."/name-var06,ll=".$_POST['val06']."/name-val06"," style='width:50px' id=va style='display:none'");

        ztp::add("text","兑换数值说明",$_POST['content'],"name=content id=content   ");

        

        ztp::add("radio","是否显示","显示=0,隐藏=1","name=hide val=".$hide);



        ztp::add("submit","保存");

        ztp::add("hidden","name=submit val=1");



        ztp::play();

    }



    public function chuli(){

        print_r(123);exit;

    }

    /**

     * 删除积分商品分类

     * 

     */

    public function integral_goods_class_del(){

        $ids=$_POST['id'];

        if(!empty($_GET['id'])){

            $ids=intval($_GET['id']);   

        }

        if(!empty($_POST['ch'])){

            $ids=implode(",",$_POST['ch']); 

        }

  

        $result=zfun::f_delete("Exchangerclass","id IN($ids)");

        admin::getdel($result);



    }



    /**

     * 积分商品分类查看编辑

     * @return 类目名称 name

     * @return 修改图片 img

     * @return 排序   sort

     * @return 是否显示 hide

     */

    public function integral_goods_class_edit(){

        ztp::title("积分商品分类编辑");

        $id=$_GET['id'];

        if(empty($id))zfun::alert("操作错误");

        $where="id = {$id}";

    

        $data=array();



        if(!empty($_POST)){ 

        $data['name']=$_POST['name'];

        $data['sort']=intval($_POST['sort']);

        $data['hide']=$_POST['hide'];

        $data['exchangType']=$_POST['exchangType'];

        if (!empty($_FILES['imgPic']['name'])) {

        $photo_file = $_FILES['imgPic']['name'];

        $upload_dir = ROOT_PATH . 'Upload/integral/';

        $position = strrpos($photo_file, ".");

        $suffix = substr($photo_file, $position + 1, strlen($photo_file) - $position);

        if (!($suffix == "png" || $suffix == "jpg" || $suffix == "gif" || $suffix == "jpeg")) {

           admin::getmsg(0,"请上传png,jpg,gif,jpeg类型的图片",0);

        }

        if ($_FILES['imgPic']['size'] > 512000) {

             admin::getmsg(0,"图片不能超过500K！",0);

        }

        $imgName = time() . '.' . $suffix;

        $uploadfile = $upload_dir . $imgName;

        if (move_uploaded_file($_FILES['imgPic']['tmp_name'], $uploadfile)) {

            $data['imgPic'] = $imgName;

        }

        $_SESSION['imgPic']=$imgName;

        }



        if(empty($data['imgPic'])){$data['imgPic']=$img= $_SESSION['imgPic'];}

        if($_POST['exchangType']==1){

        $data['var'] = $_POST['var01'] . ',' . $_POST['var02'] . ',' . $_POST['var03'] . ',' . $_POST['var04'] . ',' . $_POST['var05'] . ',' . $_POST['var06'];

        $data['val'] = $_POST['val01'] . ',' . $_POST['val02'] . ',' . $_POST['val03'] . ',' . $_POST['val04'] . ',' . $_POST['val05'] . ',' . $_POST['val06'];

        $data['content']=$_POST['content'];

        }

        $result=zfun::f_update("Exchangerclass",$where,$data);

        admin::getadd($result);

        }

        $integral=zfun::f_row("Exchangerclass",$where);

        $return_url=self::getUrl("integral_manage","integral_goods_class");

        $hide=intval($integral['hide']) ?$integral['hide'] :0;

        $exchangType=intval($integral['exchangType'])==1 ? $integral['exchangType']:0;

     

        ztp::addjs("comm/jquery.min.js");

        ztp::addjs("integral_manage/integral_goods_class.js");

  

        ztp::add("text","类目名称",$integral['name'],"name=name");

        ztp::add("file","修改图片","","name=imgPic","请上传尺寸不超过140×95，大小不超过500k的图片");

        ztp::add("text","排序",$integral['sort'],"name=sort");

        ztp::add("radio","兑换换类目类型","兑换物品类型=0,兑换数值类型=1","name=exchangType id=type val=".$exchangType);



        // 当是兑换数值类型时显示 

    

        $var=explode(",",$integral['var']);

        $val=explode(",",$integral['val']);

        ztp::add("texts","兑换数值及对应积分","ll=".$var[0]."/name-var01,ll=".$val[0]."/name-val01,ll=".$var[1]."/name-var02,ll=".$val[1]."/name-val02,ll=".$var[2]."/name-var03,ll=".$val[2]."/name-val03,ll=".$var[3]."/name-var04,ll=".$val[3]."/name-val04,ll=".$var[4]."/name-var05,ll=".$val[4]."/name-val05,ll=".$var[5]."/name-var06,ll=".$val[5]."/name-val06"," style='width:50px' id=va","5Q币:50(金额:积分)，图为前台显示效果的例子");

        ztp::add("text","兑换数值说明",$integral['content'],"name=content id=content  ");





        ztp::add("radio","是否显示","显示=0,隐藏=1","name=hide val=".$hide);

        ztp::add("submit","保存");

        ztp::play();

    }



    /**

     * 兑换审核

     * @return [type] [description]

     */

    public function exchange_audit(){

        ztp::title("兑换审核");

        $del_url=urlencode(self::getUrl("integral_manage","exchange_audit_del"));

        $sh_url=urlencode(self::getUrl("integral_manage","exchange_audit_sh"));

        $dc_url=self::getUrl("integral_manage","daochu");

        

        $sort="time desc";

        $where="type in (1,2)";

        if(!empty($_GET)){

            //搜索关键字 

            if(!empty($_GET['keyword'])){

            $keyword=filter_check($_GET['keyword']);

            $where.=" and info like '%$keyword%'";

            }

            //搜索时间 

            $start=strtotime($_GET['startTime']);

            $end=strtotime($_GET['endTime']);

            if(!empty($start)&&!empty($end)){

                $where.=" and time between  {$start} and {$end}";

            }else if(!empty($start)){

                $where.=" and time >=  {$start}";

            }else if(!empty($end)){

                $where.=" and time <=  {$end}";

            }

        }

        ztp::addtop("a","导出",$dc_url);

        ztp::addtop("time","开始时间",$_GET['startTime'],"name=startTime placeholder='开始时间'");

        ztp::addtop("time","结束时间",$_GET['endTime'],"name=endTime placeholder='结束时间'");

        ztp::addtop("p");

        ztp::addtop("p");

        ztp::addtop("text","兑换物品",$_GET['keyword'],"name=keyword");

        ztp::addtop("submit","搜索");

        if(!empty($_POST['page'])){

          $_GET['p']=$_POST['page'];  

        }

        $GLOBALS['ztp']['jump']="{link:integral_manage-exchange_audit}";

        ztp::addjs("comm/foot_jump.js");

        $action=zfun::f_goods("Authentication",$where,null,$sort,null,12);

        foreach ($action as $k => $v) {

            // $action[$k]['caozuo']=ztp::cstrarr("button=审核|{$sh_url}&id-".$v['id'].",button=删除|{$del_url}"."|ids-".$v['id']);

            $action[$k]['caozuo']=ztp::cstrarr("button=审核|{$sh_url}&id-".$v['id'].",button=删除|{$del_url}&id-".$v['id']."| msg-'是否删除' ");

            $action[$k]['time']=date("Y-m-d H:i:s",$v['time']);

            switch ($v['audit_status']) {

                case 0:

                $action[$k]['audit_status']="审核中";

                break;

                case 1:

                $action[$k]['audit_status']="审核通过";

                break;

                case 2:

                $action[$k]['audit_status']="审核不通过";

                break;

         

            }

        }

        $str="ID=id/widtn-50,兑换物品=info,时间=time,审核状态=audit_status,操作=caozuo/width-300";



        ztp::settableaction(self::getUrl("integral_manage","exchange_audit_del"));

        ztp::addtable($str,$action);

        zfun::f_setadminurl();

        ztp::play();

    }

    /**

     * 删除积分商品审核

     */

    public function exchange_audit_del(){

        $ids=$_POST['id'];

		if(!empty($_GET['id']))$ids=intval($_GET['id']);

        

        $result=zfun::f_delete("Authentication","id IN($ids)");

        admin::getdel($result);

    

    }

    /**

     * 审核积分商品

     */

    public function exchange_audit_sh(){

        $id=intval($_GET['id']);

        if(empty($id))zfun::f_fmsg("操作失败!",0);



        if(!empty($_POST['submit'])){

            $where="id = {$id}";

			$inter=zfun::f_row("Authentication",$where);

			$inter['data']=json_decode($inter['data'],true);

         	 foreach($inter['data'] as $k=>$v)$inter[$k]=$v; 

			if($_POST['audit_status']==$inter['audit_status'])admin::getmsg(0,"您已经审核过",0);

			if($_POST['audit_status']!=$inter['audit_status']&&$inter['audit_status']>1)admin::getmsg(0,"您已经审核过",0);

			

			$arr=array();

			$arr['audit_status']=$_POST['audit_status'];

			if(intval($inter['is_kc'])==1&&intval($inter['is_add'])==0){

				if($arr['audit_status']==2){

					zfun::addval("User","id='".$inter['uid']."'",array("integral"=>abs($inter['jf'])));

					$inter['data']['is_add']=1;

					$arr['data']=json_encode($inter['data']);

				}

			}elseif(intval($inter['is_kc'])==0){

				if($arr['audit_status']==1){

					zfun::addval("User","id='".$inter['uid']."'",array("integral"=>-abs($inter['jf'])));

					$inter['data']['is_kc']=1;

					$arr['data']=json_encode($inter['data']);

				}

			}

            

            

            $result=zfun::f_update("Authentication",$where,$arr);

            admin::getadd($result);

        }

        $retuen_url=self::getUrl("integral_manage","exchange_audit");

        $data=zfun::f_row("Authentication","id=$id");

         // $data=zfun::f_select("Authentication");

        

      zfun::isoff($data);

        if(empty($data))zfun::f_fmsg("操作失败!",0);

        $data['data']=json_decode($data['data'],true);

         

        foreach($data['data'] as $k=>$v)$data[$k]=$v; 



        unset($data['data']);

       

        switch($data['type']){

            case 1:

                $data['typename']="实物兑换";

            break;

            case 2:

                $data['typename']="虚拟兑换";

            break;  

            case 3:

            case 8:

                $data['typename']="提现";

            break;

            case 6:

                $data['typename']="申请代理";

            break;

              case "xfb":

                $data['typename']="幸福宝";

            break;

              case "yhb":

                $data['typename']="易货宝";

            break;

              case "yue":

                $data['typename']="余额";

            break;





        }

        

        $uid=$data['uid'];

        $user=zfun::f_row("User","id=$uid");

        $receiptaddress =zfun::f_row("ReceiptAddress",'user_id=' . $uid . ' AND id="'.$data['addressID'].'"');

		$city = zfun::f_row("City","CityID=" . intval($receiptaddress['city']));

		$district = zfun::f_row("District","DistrictID=" . intval($receiptaddress['district']));

		$province = zfun::f_row("Province","ProvinceID=" . intval($receiptaddress['province']));

		$receiptaddress['new_address'] = $province['ProvinceName'] . " " . $city['CityName'] . " " . $district['DistrictName'] . " " . $receiptaddress['address'];



       

        if(!empty($user['loginname'])){

            $user['name']=$user['loginname'];   

        }

        elseif(!empty($user['email'])){

            $user['name']=$user['email'];   

        }

        elseif(!empty($user['phone'])){

            $user['name']=$user['phone'];   

        }



        $fun="show".$data['type'];

        // $data=$this->$fun($data,$user);

     

        ztp::title("积分商品审核");

        

        ztp::add("text","事件",$data['info'],"name=info disabled");

        ztp::add("text","兑换类型",$data['typename'],"name=info disabled");

        ztp::add("text","时间",date("Y-m-d H:i:s",$data['time']),"name=info disabled");

        ztp::add("text","用户id",$user['id'],"name=info disabled");

        ztp::add("text","用户名",$user['name'],"name=info disabled");

		ztp::add("text","收货人",$receiptaddress['name'],"disabled");

		ztp::add("text","收货电话",$receiptaddress['phone'],"disabled");

		ztp::add("text","收货地址",$receiptaddress['new_address'],"disabled");



        ztp::add("text","用户剩余积分",$user['integral'],"name=info disabled");

        ztp::add("text","兑换所需积分",abs($data['jf']),"name=info disabled");

        ztp::add("text","兑换账号",$data['name'],"name=info disabled");

        ztp::add("select","类型","审核中=0,审核通过=1,审核不通过=2","name=audit_status  val=".$data['audit_status']);

        ztp::add("submit","保存");

        ztp::play();

    }

    public function Sedit1($data,$user,$status){//实物兑换

        if($status!=1)return false;

        $integral=$user['integral']-abs($data['jf']);

        if($integral<0)zfun::f_fmsg("积分不够,兑换失败!",0);

        $result=zfun::f_update("User","id=".intval($user['id']),array("integral"=>$integral));

        admin::getadd($result);

    }

    public function Sedit2($data,$user,$status){//虚拟兑换

        $this->Sedit1($data,$user,$status); 

    }





    /**

     * 导出表单

     * @return [type] [description]

     */

    public function daochu(){

        $sort="time desc";

        $data=zfun::f_select("Authentication",$where,null,null,null,$sort);

        $user=zfun::f_kdata("User",$data,"uid","id","id,loginname,phone,email");

        foreach ($user as $key => $value) {

            if(!empty($value['loginname']))$user[$key]['username']=$value['loginname'];

            else if(!empty($value['phone']))$user[$key]['username']=$value['phone'];

            else if(!empty($value['email']))$user[$key]['username']=$value['email'];

            else $user[$key]['username']=0;

        }

        $type=array("虚拟兑换","实物兑换");

        $shenhe=array("审核中","审核通过","审核不通过");



        foreach($data as $k=>$v){
            //百里
            $obj = json_decode($v['data']);
            $receiptaddress =zfun::f_row("ReceiptAddress",'user_id=' . $v['uid'] . ' AND id="'.$obj->addressID.'"');



            $data[$k]['info']=$v['info'];

            $data[$k]['type']=$type[$v['info']];

            $data[$k]['time']=date("Y-m-d H:i:s",$v['time']);

            $data[$k]['uid']=$v['uid'];

            $data[$k]['username']="'".$user[$v['uid']]['username'];

            $data[$k]['jf']=$user[$v['uid']]['integral'];

            $data[$k]['shenhe']=$shenhe[$v['info']];

            //百里
            $data[$k]['realname']   =   $receiptaddress['name'];
            $data[$k]['phone']      =   $receiptaddress['phone'];
            $data[$k]['address']    =   $receiptaddress['province'].$receiptaddress['city'].$receiptaddress['district'].$receiptaddress['area'].$receiptaddress['address'];

        }



        $namearr=array(

            "info"=>"事件",

            "type"=>"兑换类型",

            "time"=>"时间",

            "uid"=>"用户id",

            "username"=>"用户名",

            "jf"=>"用户剩余积分",

            "shenhe"=>"类型",


            "realname" => "真实姓名",   //百里

            "phone" => "电话",    //百里

            "address" => "地址",  //百里

        );



        fun("xls");

        xls::export($data,$namearr,"商品订单");    

    }



}

?>
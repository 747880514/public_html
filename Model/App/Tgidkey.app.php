<?php   

class TgidkeyApp extends Action{     

	public function addkey($tgid){

		//查询是否有自定义id
		//如果没有，则写入
		$userModel=$this->getDatabase('User');

		$tg_code = str_pad($tgid, 6, "0", STR_PAD_LEFT);

		$userModel->update("id='$tgid'", array("tg_code" => $tg_code));

		return $tg_code;



	    // $pwdtgid=$tgid*12+8*2+99;

	    // return $pwdtgid;

	}

	public function Decodekey($person_id){

	   $Decodetgid=($person_id-99-8*2)/12;

	   return $Decodetgid;

	}

}

?>
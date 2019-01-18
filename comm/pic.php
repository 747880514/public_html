<?php
defined('ROOT_PATH') 	or define('ROOT_PATH', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);
include_once ROOT_PATH."comm/zfun.php";
class pic {
	static $set=array();
	public static function index() {
		echo 'hello boy';
	}
	public static function getpic($data = array(),$filename=NULL) {
		if (empty($data) && empty($_GET['data'])) {
			echo 'error';
			return false;
		}
		if (empty($data)) $data = json_decode((base64_decode($_GET['data'])), true);
		if (empty($data)) return false;
		$pic_w = $data['width']; // 宽度
		$pic_h = $data['height']; // 高度
		$pic_list = array();
		$pic_list = $data['list'];
		$text = $data['text'];
		//$pic_list = array_slice($pic_list, 0, 3); // 只操作前2个图片
		$bg_w = $data['width']; // 背景图片宽度
		$bg_h = $data['height']; // 背景图片高度
		$background = imagecreatetruecolor($bg_w, $bg_h); // 背景图片
		$color = imagecolorallocate($background, 255, 255, 255); // 为真彩色画布创建白色背景，再设置为透明
		imagefill($background, 0, 0, $color);
		imageColorTransparent($background, $color);
		$pic_count = count($pic_list);
		$lineArr = array(); // 需要换行的位置
		$line_x = 0;
		$start_x = 0; // 开始位置X
		$start_y = 0; // 开始位置Y
		
		//zfun::pre($pic_list);
		//exit;
		foreach ($pic_list as $k => $v) {
			//$pathInfo = pathinfo($data['url']);
			switch ($v['type']) {
				case 'jpg':
				case 'jpeg':
					$imagecreatefromjpeg = 'imagecreatefromjpeg';
				break;
				case 'png':
					$imagecreatefromjpeg = 'imagecreatefrompng';
				break;
				case 'gif':
				default:
					
					
				break;
			}
			$imagecreatefromjpeg = 'imagecreatefromstring';
			$v['url']=str_replace(array("\r","\n"),"",$v['url']);
			if(strstr($v['url'],"http:")||strstr($v['url'],"https:")){
				$oid_url=$v['url'];
				self::$set['error']=0;
				while(1){
					if(self::$set['error']>=5)zfun::fecho("pic 远程图片获取失败 {$oid_url}");
					$v['url'] = zfun::curl_get($oid_url);
					if(!empty($v['url']))break;
					self::$set['error']++;
					sleep(1);
				}
			}
			else{
				$v['url'] = zfun::get($v['url']);	
			}
			
			$resource = $imagecreatefromjpeg($v['url']);
			imagecopyresized($background, $resource, $v['x'], $v['y'], 0, 0, $v['width'], $v['height'], imagesx($resource), imagesy($resource));
		}
		
		$path = dirname(dirname(__FILE__));
		$fontfile = $path . "/comm/wximg/PingFang.ttf";
		if(file_exists($fontfile)==false)$fontfile = $path . "/comm/wximg/font.ttf";
		if (empty($text)) $text = array();

		
		foreach ($text as $k => $v) {
			if(($v['color'])=='white')$color = imagecolorallocatealpha($background, 255, 255, 255, 0);
			else if(($v['color'])=='red')$color = imagecolorallocatealpha($background, 255, 0, 0, 0);
			else $color = imagecolorallocatealpha($background, 0, 0, 0, 0);
			if($v['rgb']){
				$rgb_arr=explode(",",$v['rgb']);
				$color = imagecolorallocatealpha($background, intval($rgb_arr[0]), intval($rgb_arr[1]), intval($rgb_arr[2]), intval($rgb_arr[3]));
			}

			//百里
			if($v['font'])
			{
				$fontfile2 = $path . "/comm/wximg/".$v['font'];
				imagettftext($background, $v['size'], 0, $v['x'], $v['y'], $color, $fontfile2, $v['val']);
			}
			else
			{
				imagettftext($background, $v['size'], 0, $v['x'], $v['y'], $color, $fontfile, $v['val']);
			}
			
		}
			
		if(empty($filename)){
			ob_end_clean();
			header("Content-type: image/jpeg");
			imagejpeg($background);
		}
		else{
			imagejpeg($background,ROOT_PATH.$filename);
			return INDEX_WEB_URL.$filename;	
		}
	}
}
if (!empty($_GET['pic_ctrl']) && method_exists(new pic(), $_GET['pic_ctrl'])) pic::$_GET['pic_ctrl']();
?>
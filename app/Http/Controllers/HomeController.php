<?php namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use DB;

class HomeController extends Controller {

    /**
     * 显示所给定的用户个人数据。
     *
     * @param  int  $id
     * @return Response
     */
    public function index()
    {
        $count = DB::table('daily_stat')
            ->where('day','=',date('Y-m-d',strtotime('-1 day')))
            ->first();

        return view('home',compact('count'));
    }

    public function image()
    {
        //创建画布
        $im = imagecreatetruecolor(618, 1000);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);

        //商品图片
        list($g_w,$g_h) = getimagesize(public_path('unsplash/804X5loicV0.jpg'));
        $goodImg = $this->_createImageFromFile(public_path('unsplash/804X5loicV0.jpg'));
        imagecopyresized($im, $goodImg, 0, 0, 0, 0, 618, 400, $g_w, $g_h);

        //字体文件
        $font_file = public_path('fonts/yahei.ttf');
        $font_file_bold = public_path('fonts/yahei.ttf');

        //设定字体的颜色
        $font_color_1 = ImageColorAllocate ($im, 140, 140, 140);
        $font_color_2 = ImageColorAllocate ($im, 28, 28, 28);
        $font_color_3 = ImageColorAllocate ($im, 129, 129, 129);
        $font_color_red = ImageColorAllocate ($im, 217, 45, 32);

        $fang_bg_color = ImageColorAllocate ($im, 254, 216, 217);

        //Logo
        list($l_w,$l_h) = getimagesize(public_path('img/qrcode.png'));
        $logoImg = @imagecreatefrompng(public_path('img/qrcode.png'));

        imagecopyresized($im, $logoImg, 480, 850, 0, 0, 100, 100, $l_w, $l_h);

        list($l_w,$l_h) = getimagesize(public_path('img/avatar5.png'));
        $avatarImg = @imagecreatefrompng(public_path('img/avatar5.png'));

//        imagecopyresized($im, $avatarImg, (618-40)/2, 450, 0, 0, 80, 80, $l_w, $l_h);


        $text2 = "Jason.z";

        imagettftext($im, 20,0, $this->_getFontCenterX($text2,$font_file_bold,20,618), 450, $font_color_2 ,$font_file_bold, $text2);

        $text1 = "坚持每天读一本书";

        imagettftext($im, 20,0, $this->_getFontCenterX($text1,$font_file_bold,20,618), 500, $font_color_2 ,$font_file_bold, $text1);

        imagettftext($im, 14,0, $this->_getFontCenterX('打卡天数',$font_file_bold,14,618/2), 600, $font_color_2 ,$font_file_bold, '打卡天数');

        imagettftext($im, 60,0, $this->_getFontCenterX('23',$font_file_bold,60,618/2), 700, $font_color_2 ,$font_file_bold, '23');


        imageline($im,309,600,309,700,$font_color_1);


        imagettftext($im, 14,0, $this->_getFontCenterX('打卡次数',$font_file_bold,14,618/2)+618/2, 600, $font_color_2 ,$font_file_bold, '打卡次数');

        imagettftext($im, 60,0, $this->_getFontCenterX('543',$font_file_bold,60,618/2)+618/2, 700, $font_color_2 ,$font_file_bold, '543');


        imageline($im,50,820,550,820,$font_color_1);


        //温馨提示
        imagettftext($im, 16,0, 80, 870, $font_color_2 ,$font_file_bold, '水滴打卡');

        imagettftext($im, 14,0, 80, 920, $font_color_1 ,$font_file, '见证持之以恒的力量');

        Header("Content-Type: image/png");

        $file_name = time().'.png';

        imagepng ($im,public_path('share/'.$file_name));

        echo "https://drip.growu.me/share/".$file_name;



        //释放空间
        imagedestroy($im);
        imagedestroy($goodImg);
        imagedestroy($logoImg);
    }

    private function _getFontCenterX($text,$font_file,$font_size,$width)
    {
        $result = imagettfbbox($font_size,0,$font_file, $text);

        $textWidth = $result[2]-$result[0];

        $x= ceil(($width - $textWidth) / 2);

        return $x;
    }

    /**
     * 从图片文件创建Image资源
     * @param $file 图片文件，支持url
     * @return bool|resource    成功返回图片image资源，失败返回false
     */
    private function _createImageFromFile($file){
        if(preg_match('/http(s)?:\/\//',$file)){
            $fileSuffix = $this->_getNetworkImgType($file);
            echo $fileSuffix;
        }else{
            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        }

        if(!$fileSuffix) return false;

        switch ($fileSuffix){
            case 'jpeg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $theImage = @imagecreatefrompng($file);
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                $theImage = @imagecreatefromstring(file_get_contents($file));
                break;
        }


        return $theImage;
    }

    /**
     * 获取网络图片类型
     * @param $url  网络图片url,支持不带后缀名url
     * @return bool
     */
    private function _getNetworkImgType($url){
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //支持https
        curl_exec($ch);//执行curl会话
        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
        curl_close($ch);//关闭资源连接

        if ($http_code['http_code'] == 200) {
            $theImgType = explode('/',$http_code['content_type']);

            if($theImgType[0] == 'image'){
                return $theImgType[1];
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 分行连续截取字符串
     * @param $str  需要截取的字符串,UTF-8
     * @param int $row  截取的行数
     * @param int $number   每行截取的字数，中文长度
     * @param bool $suffix  最后行是否添加‘...’后缀
     * @return array    返回数组共$row个元素，下标1到$row
     */
    private function _cn_row_substr($str,$row = 1,$number = 10,$suffix = true){
        $result = array();
        for ($r=1;$r<=$row;$r++){
            $result[$r] = '';
        }

        $str = trim($str);
        if(!$str) return $result;

        $theStrlen = strlen($str);

        //每行实际字节长度
        $oneRowNum = $number * 3;
        for($r=1;$r<=$row;$r++){
            if($r == $row and $theStrlen > $r * $oneRowNum and $suffix){
                $result[$r] = $this->_mg_cn_substr($str,$oneRowNum-6,($r-1)* $oneRowNum).'...';
            }else{
                $result[$r] = $this->_mg_cn_substr($str,$oneRowNum,($r-1)* $oneRowNum);
            }
            if($theStrlen < $r * $oneRowNum) break;
        }

        return $result;
    }

    /**
     * 按字节截取utf-8字符串
     * 识别汉字全角符号，全角中文3个字节，半角英文1个字节
     * @param $str  需要切取的字符串
     * @param $len  截取长度[字节]
     * @param int $start    截取开始位置，默认0
     * @return string
     */
    private function _mg_cn_substr($str,$len,$start = 0){
        $q_str = '';
        $q_strlen = ($start + $len)>strlen($str) ? strlen($str) : ($start + $len);

        //如果start不为起始位置，若起始位置为乱码就按照UTF-8编码获取新start
        if($start and json_encode(substr($str,$start,1)) === false){
            for($a=0;$a<3;$a++){
                $new_start = $start + $a;
                $m_str = substr($str,$new_start,3);
                if(json_encode($m_str) !== false) {
                    $start = $new_start;
                    break;
                }
            }
        }

        //切取内容
        for($i=$start;$i<$q_strlen;$i++){
            //ord()函数取得substr()的第一个字符的ASCII码，如果大于0xa0的话则是中文字符
            if(ord(substr($str,$i,1))>0xa0){
                $q_str .= substr($str,$i,3);
                $i+=2;
            }else{
                $q_str .= substr($str,$i,1);
            }
        }
        return $q_str;
    }


}
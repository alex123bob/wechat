<?php
include_once "./citypic.php";
include_once "./libs/btog.php";
include_once "./conn.php";
include_once "./dictionary.php";

define("TOKEN", "alex");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
    private $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";

    private $linkTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[link]]></MsgType>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <Url><![CDATA[%s]]></Url>
                        <MsgId>%s</MsgId>
                        </xml>";

    private $voiceTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[voice]]></MsgType>
                        <Voice>
                        <MediaId><![CDATA[%s]]></MediaId>
                        </Voice>
                        </xml>";

    // defines a string consists of fromUser,toUser,keyword.
    private $basicInfo = array();

    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if ($this->checkSignature()) {
            echo $echoStr;
            // exit;
        }
        else {
            exit;
        }
    }

    public function responseMsg()
    {
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)) {

            $chinese = new utf8_chinese();
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $type = $postObj->MsgType;
            $cus = $postObj->Event;
            // transfer Traditional Chinese to Simplified Chinese.
            $keyword = $chinese->big5_gb2312($keyword);
            array_push($this->basicInfo, $keyword, $fromUsername, $toUsername);

            if ($type == "event" and $cus == "subscribe") {
                $this->output("感谢您的关注\n\n1、输入\"城市+天气\"查天气，例：北京天气\n2、输入\"城市+地图\"查城市地图，例：上海地图\n3、其他输入有专职simsimi陪您解闷\n\n感谢您的支持\n~Alexander.Cornucopia.Li");
            }
            else if ($type == "voice") {
                // echo sprintf($this->voiceTpl, $fromUsername, $toUsername, time(), "SttVCTysij1wRJc5gFXyRIRd61i77LQBfU4GgYjRW9uDBt4kdtQ7GfhQLoaCOSEV");
                $this->output("您的语音已经成功上传。");
                $this->recordVoice($postObj);
            }
            else if ($type == "text") {
                if (!empty($keyword)) {
                    if (preg_match("/^[a-zA-Z\s\-]+$/i", $keyword)) {
                        $this->interpret($keyword);
                    }
                    else if (substr_count($keyword, '天气') != 0 && $keyword != '天气') {
                        $this->getWeather();
                    }
                    else if (substr_count($keyword, '地图') != 0) {
                        $this->generateMap();
                    }
                    else if (substr_count($keyword, '语音') != 0) {
                        preg_match("/\d+/i", $keyword, $matches);
                        if (!count($matches)) {
                            $num = 1;
                        }
                        else {
                            $num = $matches[0];
                        }
                        $this->outputVoice($num);
                    }
                    else if (substr_count($keyword, '聆听') != 0) {
                        $this->outputVoice(0, true);
                    }
                    else {
                        $this->simiweb();   
                    }
                } 
                else {
                    echo "Input something...";
                }
            }

        } 
        else {
            echo "";
            exit;
        }
    }

    private function outputVoice($num, $isRand = false){
        global $mysql;
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];

        if (!$isRand) {
            $ids = $mysql->DBGetSomeRows("wechat_voice_msg", "mediaId", "where `userId` = '$fromUsername'");
            if ($num > count($ids)) {
                $num = count($ids) - 1;
            }
            else {
                $num -= 1;
            }
        }
        else {
            $ids = $mysql->DBGetAllRows("wechat_voice_msg", "mediaId");
            $num = rand(1, count($ids));
            $num -= 1;
        }

        echo sprintf($this->voiceTpl, $fromUsername, $toUsername, time(), $ids[$num]['mediaId']);
    }

    // Records information of voice in database.
    private function recordVoice ($obj){
        global $mysql;
        $sql = "INSERT INTO `wechat_voice_msg` (`userId`, `time`, `mediaId`, `format`, `msgId`, `remark`) VALUES (".
            "'$obj->FromUserName', '$obj->CreateTime', '$obj->MediaId', '$obj->Format', '$obj->MsgId', 'TaDa')";

        $mysql->DBExecute($sql);

        //print_r($mysql->DBGetAllRows('`test`'));

        $mysql->DBClose();
    }

    // Outputs text information.
    private function output ($content){
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];
        echo sprintf($this->textTpl, $fromUsername, $toUsername, time(), "text", $content);
    }

    // Outputs translated information.
    private function interpret($word) {
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];
        echo sprintf($this->textTpl, $fromUsername, $toUsername, time(), "text", translate($word));
    }

    //simsimi
    private function simi(){
        $keyword = $this->basicInfo[0]; 
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];

        $key="df61ba70-27f8-4c1f-b22c-0f81044310ea";
        $url_simsimi="http://sandbox.api.simsimi.com/request.p?key=".$key."&lc=ch&ft=0.0&text=".$keyword;

        $json=file_get_contents($url_simsimi);

        $result=json_decode($json,true);

        //$errorCode=$result['result'];

        $response=$result['response'];

        if (!empty($response)) {
            $this->output($response."\n\n友情提示:\n 输入\"城市+天气\"查天气");
        }
        else {
            $this->output("你说什么？\n\n友情提示:\n 输入\"城市+天气\"查天气");
        }
    }

    // simsimi in web version
    private function simiweb(){
        $keyword = $this->basicInfo[0]; 
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];

        $url = "http://www.niurenqushi.com/app/simsimi/ajax.aspx";
        $post_data = array(
            "txt" => $keyword
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);

        if (!empty($output)) {
            $this->output($output."\n\n友情提示:\n 输入\"城市+天气\"查天气");
        }
        else {
            $this->output("你说什么？\n\n友情提示:\n 输入\"城市+天气\"查天气");
        }
    }

    // generate Baidu map;
    private function generateMap (){

        $keyword = $this->basicInfo[0]; 
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];

        $geshu = substr_count($keyword, '地图');
        $t = explode("地图", $keyword);

        for ($i = 0; $i <= $geshu; $i++) {
            if ($t[$i] != '') {
                $city = $t[$i];
                break;
            }
        }
        $city = $city ? $city : '北京';

        $content = "<a href=\"http://alex123bob.sinaapp.com/wechat/map.php?city=$city\">[$city]地图</a>";
        $this->output($content);
    }

    // query weather
    private function getWeather()
    {
        global $cityPic;
        $keyword = $this->basicInfo[0]; 
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];
        //图文信息2 for 天气【这是微信的图文信息模板】
        $tqTpl = "<xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>
                <MsgType><![CDATA[%s]]></MsgType>
                <ArticleCount>6</ArticleCount>
                <Articles>
                <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>
                <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>
                <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>
                <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>
                <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>
                <item>
                <Title><![CDATA[%s]]></Title>
                <Description><![CDATA[%s]]></Description>
                <PicUrl><![CDATA[%s]]></PicUrl>
                <Url><![CDATA[%s]]></Url>
                </item>
                </Articles>
                </xml> ";

        $geshu = substr_count($keyword, '天气');
        $t = explode("天气", $keyword);

        for ($i = 0; $i <= $geshu; $i++) {
            if ($t[$i] != '') {
                $city = $t[$i];
                break;
            }
        }
        $mykey = "5317a07f6f679290c051680fc0be7cf4";
        $url = "http://api.map.baidu.com/telematics/v3/weather?location=" . $city . "&output=json&ak=" . $mykey;
        $output = file_get_contents($url);
        $contentStr = json_decode($output, true);

        if ($contentStr['status'] == 'success') {

            $T[0]['Title'] = $contentStr['date'] . " " . $contentStr['results'][0]['currentCity'] . "天气";

            if (!$cityPic[$contentStr['results'][0]['currentCity']]) {
                $T[0]['PicUrl'] = $cityPic["others"];
                $T[0]['Url']=$cityPic["others"];
            }
            else {
                $T[0]['PicUrl'] = $cityPic[$contentStr['results'][0]['currentCity']];
                $T[0]['Url']=$cityPic[$contentStr['results'][0]['currentCity']];
            }

            if (is_array($contentStr['results'][0]['index'])) {
                $T[2]['Title'] = "【pm2.5】" . $contentStr['results'][0]['pm25'] . "\n" . "【" . $contentStr['results'][0]['index'][0]['title'] . "】" . "(" . $contentStr['results'][0]['index'][0]['zs'] . ") " . $contentStr['results'][0]['index'][0]['des'];
                //下一行是洗车指数，感觉不对主题还是不要的好。。
                //$T[2]['Title']=$T[2]['Title']."\n"."【".$contentStr['results'][0]['index'][1]['title']."】(".$contentStr['results'][0]['index'][1]['zs'].") ".$contentStr['results'][0]['index'][1]['des'];
                $T[2]['Title'] = $T[2]['Title'] . "\n" . "【" . $contentStr['results'][0]['index'][2]['title'] . "】(" . $contentStr['results'][0]['index'][2]['zs'] . ") " . $contentStr['results'][0]['index'][2]['des'];
            } else {
                $guowai = 1;
            }
            for ($i = 1, $aaa = 0; $i <= 5; $i++) {
                if ($i == 2 && $guowai != 1)
                    continue;
                if ($guowai == 1 && $i == 5)
                    continue;
                $T[$i]['Title'] = $contentStr['results'][0]['weather_data'][$aaa]['date'] . " " . $contentStr['results'][0]['weather_data'][$aaa]['temperature'] . " " . $contentStr['results'][0]['weather_data'][$aaa]['weather'] . " " . $contentStr['results'][0]['weather_data'][$aaa]['wind'];
                $T[$i]['PicUrl'] = $contentStr['results'][0]['weather_data'][$aaa]['dayPictureUrl'];
                $T[$i]['Url'] = $contentStr['results'][0]['weather_data'][$aaa]['dayPictureUrl'];
                $aaa++;
            }

            $tianqi = sprintf($tqTpl, $fromUsername, $toUsername, time(), "news", $T[0]['Title'], $T[0]['Description'], $T[0]['PicUrl'], $T[0]['Url'], $T[1]['Title'], $T[1]['Description'], $T[1]['PicUrl'], $T[1]['Url'], $T[2]['Title'], $T[2]['Description'], $T[2]['PicUrl'], $T[2]['Url'], $T[3]['Title'], $T[3]['Description'], $T[3]['PicUrl'], $T[3]['Url'], $T[4]['Title'], $T[4]['Description'], $T[4]['PicUrl'], $T[4]['Url'], $T[5]['Title'], $T[5]['Description'], $T[5]['PicUrl'], $T[5]['Url']);
            echo $tianqi;
        }
        else {
            echo sprintf($this->textTpl, $fromUsername, $toUsername, time(), "text", "亲亲，你应该输入具体城市哟！O(∩_∩)O~~");
        }
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature) {
            return true;
        } else {
            return false;
        }
    }
}

?>

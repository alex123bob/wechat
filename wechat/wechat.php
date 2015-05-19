<?php
include_once "./citypic.php";
include_once "./conn.php";
include_once "./dictionary.php";
//define your token
define("TOKEN", "jczs");
$wechatObj = new wechatCallbackapiTest();
$wechatObj->valid();
$wechatObj->responseMsg();

class wechatCallbackapiTest
{
	public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
        	echo $echoStr;
        }
    }

    // defines a string consists of fromUser,toUser,keyword.
    private $basicInfo = array();

    private $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[text]]></MsgType>
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

    public function responseMsg()
    {
		//get post data, May be due to the different environments
		$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

      	//extract post data
		if (!empty($postStr)){
                /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
                   the best way is to check the validity of xml by yourself */
                libxml_disable_entity_loader(true);
              	$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                $type = $postObj->MsgType;
                $cus = $postObj->Event;

                array_push($this->basicInfo, $keyword, $fromUsername, $toUsername);

                if ($type == "event") {
                    switch ($cus) {
                        case "subscribe":
                            $this->output("感谢您的关注");
                            break;
                        case "CLICK":
                            switch ($postObj->EventKey) {
                                case "weather_forecast": {
                                    $this->output('查询天气！');
                                }
                                break;
                                case "theme_analysis": {
                                    
                                }
                                break;
                                default:
                                break;
                            }
                            break;
                        default:
                        break;
                    }
                }
                else if ($type == "event" and $key == "weather_forecast") {
                    $this->getWeather();
                }
                else if ($type == "voice") {

                }
                else if ($type == "text") {
                    if(!empty( $keyword )) {
                        $this->output("文字已经输入！");
                    }
                }            
				else{
                	echo "Input something...";
                }

        }
        else {
        	echo "";
        	exit;
        }
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

    // Outputs text information.
    private function output ($content){
        $fromUsername = $this->basicInfo[1];
        $toUsername = $this->basicInfo[2];
        echo sprintf($this->textTpl, $fromUsername, $toUsername, time(), $content);
    }
		
	private function checkSignature()
	{
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
        
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        		
		$token = TOKEN;
		$tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
		sort($tmpArr, SORT_STRING);
		$tmpStr = implode( $tmpArr );
		$tmpStr = sha1( $tmpStr );
		
		if( $tmpStr == $signature ){
			return true;
		}else{
			return false;
		}
	}
}

?>
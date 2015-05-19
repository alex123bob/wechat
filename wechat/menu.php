<?php
header("Content-type: text/html; charset=utf-8");

define("ACCESS_TOKEN", 'KagTs-QJU9kdL-hBhLScZXVE5eXJp0MXL3AiljcQfJv5htCEL7cTpJhWoEVuIRjCCaZb5IUmCw0rlvAOzp9T-2sNe8HvvIqBqrS49NyOs-k');

//创建菜单
function createMenu($data){
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".ACCESS_TOKEN);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  $tmpInfo = curl_exec($ch);
  if (curl_errno($ch)) {
  return curl_error($ch);
  }
  curl_close($ch);
  return $tmpInfo;
}

$data = '{
"button": [
  {
    "name": "生活便民",
    "sub_button": [{
      "type": "click",
      "name": "风格解析",
      "key": "theme_analysis"
    }, {
      "type": "click",
      "name": "单词查询",
      "key": "dictionary"
    }, {
      "type": "click",
      "name": "地图",
      "key": "map"
    }, {
      "type": "click",
      "name": "天气预报",
      "key": "weather_forecast"
    }]
  }, 
  {
    "name": "惊喜活动",
    "sub_button": [{
      "type": "view",
      "name": "抢订方式",
      "url": "http://www.jczs.xyz/"
    }, {
      "type": "view",
      "name": "看活动!!!",
      "url": "http://www.jczs.xyz/"
    }]
  }, 
  {
    "name": "佳诚装饰",
    "sub_button": [{
      "type": "view",
      "name": "联系我们",
      "url": "http://www.index.php/article/index/id/114"
    }, {
      "type": "view",
      "name": "访问官网",
      "url": "http://www.index.php/article/index/id/115"
    }, {
      "type": "view",
      "name": "ERP入口",
      "url": "http://www.jczs.xyz/login.html"
    }]
  }
]
}';


echo createMenu($data);//创建菜单
?>
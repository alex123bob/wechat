<?php
    function translate($word) {
        $json = file_get_contents("http://fanyi.youdao.com/openapi.do?keyfrom=alexander&key=1307421338&type=data&doctype=json&version=1.1&q=".$word);
        $res = json_decode($json, true);
        if (0 == $res['errorCode']) {
            $str = "查询".$res['query']."的结果\n\n";
            foreach($res as $key => $val) {
                if ('translation' == $key) {
                    $str .= "翻译：\n".implode($val, "\n")."\n";
                    $str .= "\n";
                }
                elseif ('basic' == $key) {
                    $str .= "基本释义：\n";
                    foreach($val as $basicTag => $basicVal) {
                        if (is_array($basicVal)) {
                            $str .= implode($basicVal, "\n")."\n";
                        }
                        else {
                            $str .= $basicTag.":[".$basicVal."]\n";
                        }
                    }
                    $str .= "\n";
                }
                elseif ('web' == $key) {
                    $str .= "网络释义：";
                    for ($i = 0; $i < count($val); $i++) {
                        $str .= "\n".$val[$i]['key'].":\n".implode($val[$i]['value'], "\n");
                    }
                    $str .= "\n";
                }
            }
            return $str;
        }
        elseif (20 == $res['errorCode']) {
            return '要翻译的文本过长';
        }
        elseif (30 == $res['errorCode']) {
            return '无法进行有效的翻译';
        }
        elseif (40 == $res['errorCode']) {
            return '不支持的语言类型';
        }
        elseif (50 == $res['errorCode']) {
            return '无效的key';
        }
        elseif (60 == $res['errorCode']) {
            return '无词典结果，仅在获取词典结果生效';
        }
    }
?>
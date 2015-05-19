<?php
	function getFeedback (){
		global $mysql;
		$arr = $mysql->DBGetAllRows("`feedback`");
		$res = "";
		for ($i = 0; $i < count($arr); $i++) {
			$el = $arr[$i];
			$res .= "用户名：".$el["name"]."\n姓名：".$el["realname"]."\n等级：".$el["level"]."\n内容：".$el["content"]."\n\n";
		}
		return $res;
	}
?>
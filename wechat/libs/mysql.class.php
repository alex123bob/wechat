<?php
/*
		类内部的变量成员都以db开头；
		类内部的方法成员都以DB开头；（构造函数等PHP已经定义的除外）
		方法内部的局部变量都以part开头；
		方法的参数都以Value结尾；
		常量都大写；
		变量名都是开头小写，方法名都是开头大写；
*/
	class mysql
	{
		private $dbHost;            //数据库主机

		private $dbUser;           //数据库用户名

		private $dbPassword;      //数据库连接密码

		private $dbConn;          //连接标志

		private $dbSelect;        //所选择连接的数据库

		private $dbSQL;           //所要执行的SQL语句

		private $dbResult;        //执行mysql_query()函数后产生的结果

		private $dbFields;        //数据库中的某个字段

		private $dbRows;          //返回的数据库中某个结果集的总条数

		private $dbTable;         //选择的数据库中的表的名称

		private $dbEncode;        //进行数据库操作选择的编码

		/*数据库编码*/
		const GBK = "GBK";
		const GB2312 = "gb2312";
		const UTF8 = "utf8";
		const UNICODE = "unicode";

		//构造函数，初始化
		public function __construct($hostValue = 'localhost',$userValue = 'root',$passwordValue, $dbValue='', $encodeValue='')
		{
			$this->dbHost = $hostValue;
			$this->dbUser = $userValue;
			$this->dbPassword = $passwordValue;
			$this->dbSelect = $dbValue;
			//编码的选择
			if (strcasecmp($encodeValue,self::GBK) == 0)		//忽略大小写的比较
			{
				$this->dbEncode = self::GBK;
			}
			if (strcasecmp($encodeValue,self::GB2312) == 0)
			{
				$this->dbEncode = self::GB2312;
			}
			if (strcasecmp($encodeValue,self::UTF8) == 0)
			{
				$this->dbEncode = self::UTF8;
			}
			if (strcasecmp($encodeValue,self::UNICODE) == 0)
			{
				$this->dbEncode = self::UNICODE;
			}
			$this->DBConnect();
		}

		//连接数据库函数
		public function DBConnect()
		{
			$this->dbConn = mysql_connect($this->dbHost,$this->dbUser,$this->dbPassword);		//打开一个到 MySQL 服务器的连接

			if (!$this->dbConn)		//如果没有数据库连接的标志
			{
				echo "数据库连接失败！";
			}

			if (!mysql_select_db($this->dbSelect, $this->dbConn))	//选择连接的数据库，如果没有连接的数据库和连接标志
			{
				echo "数据库打开失败！";
			}

			mysql_query("SET NAMES '".$this->dbEncode."'");			//连接数据库的编码方式，mysql_query表示发送一条mysql查询
		}

		//执行数据库语句的基本方法，具体的操作都要调用该基本操作
		public function DBExecute($sqlValue)
		{
			//先判断是否连接，如果没连接先连接
			if (!$this->dbConn)
			{
				$this->DBConnect();
			}

			//将传递进来的SQL语句进行一个赋值
			$this->dbSQL = $sqlValue;

			//然后执行SQL语句
			if (!$this->dbResult = @mysql_query($this->dbSQL,$this->dbConn))
			{
				$this->DBOutputErrorInfo();
			}
            else
            {
                $sqlValue = preg_replace("/[\r\n]/i", " ", $sqlValue);
                echo '<script>console.log("['.$sqlValue.'] succeed")</script>';
            }
		}

		//获取执行数据库基本操作的dbResult
		public function DBGetResult()
		{
			return $this->dbResult;
		}

		//简单的查询功能，不返回任何结果，一般用于方法DBGetTotalNumber获取结果集地总条数
		public function DBSimpleSelect($tableValue)
		{
			$partStr = "SELECT * FROM $tableValue";
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
		}

		//获取数据库某个结果集的总条数
		public function DBGetTotalNumber()
		{
			$this->dbRows = mysql_num_rows($this->dbResult);   		//获取结果中行的数目
			return $this->dbRows;
		}

		//获得第一条数据的方法，返回的是一个数组（查询功能）
		public function DBGetFirstRow($tableValue)
		{
			$partStr = "SELECT * FROM $tableValue";
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
			if (mysql_num_rows($this->dbResult) > 0)
			{
				$partRows = mysql_fetch_array($this->dbResult);		// 从结果集中取得一行作为关联数组，或数字数组，或二者兼有
				return $partRows;
			}
			else
			{
				return false;
			}
		}

		//获取所有的数据的方法，返回的是一个二维数组（查询功能）
		public function DBGetAllRows($tableValue, $fieldsValue = '*')
		{
			$partStr = "SELECT $fieldsValue FROM $tableValue";
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
			if (mysql_num_rows($this->dbResult) > 0)
			{
				while($partRows = mysql_fetch_array($this->dbResult))
				{
					$partAllRows[] = $partRows;
				}
				return $partAllRows;
			}
			else
			{
				return false;
			}
		}

		//获取某一个指定的一行（也可以是某一行的某个字段），默认的是指定行的所有信息
		//一般用于指定的一行的信息，如WHERE id = 1;
		public function DBGetOneRow($tableValue, $fieldsValue = '*', $conditionValue = '')
		{
			$partStr = "SELECT $fieldsValue FROM $tableValue";
			$conditionValue = " WHERE ".$conditionValue;			//加入的条件词语
			$partStr .= $conditionValue;
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);

			if (mysql_num_rows($this->dbResult) > 0)
			{
				$partRows = mysql_fetch_array($this->dbResult);
				return $partRows;
			}
			else
			{
				return false;
			}
		}

		//获取某个范围的信息，一般用于limit语句，$conditionValue是要加入条件词语的（如LIMIT或者WHERE），以字符串的形式传入
		//或者用于WHERE，但返回的是几行的信息。字段可以是全部字段，也可以是某些指定字段，
		//最后返回的是一个二维数组
		public function DBGetSomeRows($tableValue, $fieldsValue = '*', $conditionValue = '')
		{
			$partStr = "SELECT $fieldsValue FROM $tableValue $conditionValue";
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);

			if (mysql_num_rows($this->dbResult) > 0)
			{
				while($partRows = mysql_fetch_array($this->dbResult))
				{
					$partSomeRows[] = $partRows;
				}
				return $partSomeRows;
			}
			else
			{
				return false;
			}
		}

		//创建新的数据库
		public function DBCreateDatabase($databaseValue)
		{
			$partStr = "CREATE DATABASE $databaseValue";
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
		}

		//删除操作
		public function DBDelete($tableValue, $conditionValue)
		{
			$partStr = "DELETE FROM $tableValue WHERE $conditionValue";
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
		}

		//更新操作（参数$updateValue是更新后的值）
		//这个只用于更新一个字段数据，即一列
		public function DBUpdateOneCol($tableValue, $conditionValue, $fieldsValue, $updateValue)
		{
			if ($conditionValue == "")
			{
				$partStr = "UPDATE $tableValue SET $fieldsValue = '".$updateValue."' ";
			}
			else
			{
				$partStr = "UPDATE $tableValue SET $fieldsValue = '".$updateValue."' WHERE $conditionValue";
			}
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
		}


		//更新操作（参数$setValue是更新操作的值，例如"user = 'abc',name='cde'"等样子，中间要以逗号隔开），不是数字类型的字段，更新后的值要加引号
		//这个用于更新多个字段数据，即多列
		public function DBUpdateSomeCols($tableValue, $conditionValue, $setValue)
		{
			if ($conditionValue == "")
			{
				$partStr = "UPDATE $tableValue SET $setValue";
			}
			else
			{
				$partStr = "UPDATE $tableValue SET $setValue WHERE $conditionValue";
			}
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
		}



		//插入操作insert into，其中$fieldsValue（字段）的值用
		//三个参数均以字符串的形式传入，$fieldsValue以逗号隔开，
		//$insertValue也用逗号隔开，碰到其中为字符串的值，要再加\"或者是'来表示。
		public function DBInsert($tableValue, $fieldsValue, $insertValue)		//表名，字段名，内容
		{
			$partStr = "INSERT INTO $tableValue";
			if ($fieldsValue == "")
			{
				$partStr .= " VALUES($insertValue)";
			}
			else
			{
				$partStr .= "($fieldsValue) VALUES($insertValue)";
			}
			$this->dbSQL = $partStr;
			$this->DBExecute($this->dbSQL);
		}

		//利用系统自带的call方法吸收错误的方法，参数$errorMethodValue错误的方法，参数$errorValue是错误的值
		//该方法产生的错误的值是以数组的形式呈现出来的，所以打印错误的值的时候利用print_r()函数
		public function __call($errorMethodValue, $errorValue)
		{
			echo "错误的方法是：".$errorMethodValue;
			echo "错误的值是：".print_r($errorValue);
		}

		/**
		 * 用于处理错误的信息进行一个输出
		 */
		 public function DBOutputErrorInfo()
		 {
			$inputStr = "错误的语句是".preg_replace("/[\r\n]/i", ' ', $this->dbSQL)."-----";
			$inputStr .= "发生的时间是：".date("Y-m-d H:i:s");
            echo '<script>console.log("'.$inputStr.'")</script>';
		 }

		//关闭操作
		public function DBClose()
		{
			mysql_close($this->dbConn);
		}
	}
?>
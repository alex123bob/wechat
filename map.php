<!DOCTYPE html>  
<html>  
<head>  
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />  
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />  
<style type="text/css">  
body, html,#l-map {width: 100%;height: 100%;overflow: hidden;hidden;margin:0;}  
</style>  
<script type="text/javascript" src="http://api.map.baidu.com/api?type=quick&ak=5317a07f6f679290c051680fc0be7cf4&v=1.0"></script>  
<title>显示地图</title>  
</head>  
<body>  
<div id="l-map"></div>  
</body>
</html>
<?php
	$city = '"'.$_GET['city'].'"';
?>
<script type="text/javascript">
	// 定义一个控件类，即function
	function ZoomControl(){
	    // 设置默认停靠位置和偏移量
	    this.defaultAnchor = BMAP_ANCHOR_TOP_LEFT;
	    this.defaultOffset = new BMap.Size(10, 10);
	}
	// 通过JavaScript的prototype属性继承于BMap.Control
	ZoomControl.prototype = new BMap.Control();
	// 自定义控件必须实现initialize方法，并且将控件的DOM元素返回     
	// 在本方法中创建个div元素作为控件的容器，并将其添加到地图容器中     
	ZoomControl.prototype.initialize = function(map){      
		// 创建一个DOM元素     
		var div = document.createElement("div");      
		// 添加文字说明      
		div.appendChild(document.createTextNode("放大2级"));      
	 	// 设置样式      
		div.style.cursor = "pointer";      
		div.style.border = "1px solid gray";      
		div.style.backgroundColor = "white";      
		// 绑定事件，点击一次放大两级      
		div.onclick = function(e){    
	        map.setZoom(map.getZoom() + 2);      
		}
		// 添加DOM元素到地图中     
		map.getContainer().appendChild(div);    
	   	return div;    
	}  

	window.onload = function (){
		var map = new BMap.Map("l-map");
		var point = new BMap.Point(116.404, 39.915);
		map.centerAndZoom(<?php echo $city; ?>, 14);

		var zoomControl = new BMap.ZoomControl();
		map.addControl(zoomControl); //添加缩放控件  
		var scaleControl = new BMap.ScaleControl();
		map.addControl(scaleControl); //添加比例尺控件
		// 创建控件实例      
		var myZoomCtrl = new ZoomControl();      
		// 添加到地图当中      
		map.addControl(myZoomCtrl);

		// 创建标注
		// var marker = new BMap.Marker(new BMap.Point(116.404, 39.915));
		// marker.addEventListener("click", function(){      
		//   alert("您点击了标注");      
		// });
		// map.addOverlay(marker); 
		// var infoWinOpts = {      
		//     width : 100,     // 信息窗口宽度      
		//     height: 50,     // 信息窗口高度      
		//     title : "Hello"  // 信息窗口标题     
		// }      
		// var infoWindow = new BMap.InfoWindow("World", infoWinOpts);  // 创建信息窗口对象      
		// map.openInfoWindow(infoWindow, marker.getPosition());      // 打开信息窗口
		
		// 添加折线
		// var polyline = new BMap.Polyline([new BMap.Point(116.399, 39.910), new BMap.Point(116.405, 39.920)], {
		// 	strokeColor:"blue", 
		// 	strokeWeight:6, 
		// 	strokeOpacity:0.5
		// });
		// map.addOverlay(polyline);
		
		// map.centerAndZoom(point, 15);                 // 初始化地图，设置中心点坐标和地图级别     
		// var traffic = new BMap.TrafficLayer();        // 创建交通流量图层实例      
		// map.addTileLayer(traffic);                    // 将图层添加到地图上
	}
</script>
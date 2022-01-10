<!doctype html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>uniCloud图床上传</title>
    <link rel="stylesheet" href="//cdn.staticfile.org/layui/2.5.6/css/layui.min.css">
	<link rel="stylesheet" href="style.css">
</head>
<body>
<fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
  <legend>uniCloud图床上传</legend>
</fieldset>
<blockquote class="layui-elem-quote layui-quote-nm" style="margin: 0 25px;">
  <span class="layui-badge-dot"></span> 文件格式支持：jpg,jpeg,png,gif<br>
</blockquote>
<div class="Teacher-up">
	<div class="layui-progress layui-progress-big" lay-filter="demo" style="margin-bottom: 20px;" lay-showPercent="true">
		<div class="layui-progress-bar" lay-percent="0%"></div>
	</div>
	<div class="layui-upload-drag" id="multiple">
		<i class="layui-icon"></i>
		<p>点击或者拖拽图片到此处上传</p>
	</div>
	<div style="display:none"><input type="button" id="uploadBtn"></div>

	<div class="layui-row">
		<div class="layui-col-lg12" id="imgshow" style="display:none;">
			<!-- 图片显示区域 -->
			<!-- 显示缩略图 -->
			<div class="layui-col-lg4">
				<div id="img-thumb"><a href="" target="_blank" title="点此查看原图"><img alt="loading"></a></div>
			</div>
			<!-- 显示地址 -->
			<div class="layui-col-lg7 layui-col-md-offset1">
				<div id="links">
					<table class="layui-table" lay-size="sm" lay-skin="nob">
						<tbody>
						<tr>
							<td>URL</td>
							<td><input type="text" class="layui-input" id="url"></td>
							<td><a href="javascript:;" class="layui-btn layui-btn-sm copy-btn" onclick="copyurl('url')">复制</a></td>
						</tr>
						<tr>
							<td>HTML</td>
							<td><input type="text" class="layui-input" id="html"></td>
							<td><a href="javascript:;" class="layui-btn layui-btn-sm copy-btn" onclick="copyurl('html')">复制</a></td>
						</tr>
						<tr>
							<td>Markdown</td>
							<td><input type="text" class="layui-input" id="markdown"></td>
							<td><a href="javascript:;" class="layui-btn layui-btn-sm copy-btn" onclick="copyurl('markdown')">复制</a></td>
						</tr>
						<tr>
							<td>BBCode</td>
							<td><input type="text" class="layui-input" id="bbcode"></td>
							<td><a href="javascript:;" class="layui-btn layui-btn-sm copy-btn" onclick="copyurl('bbcode')">复制</a></td>
						</tr>
						</tbody>
					</table>
				</div>
			</div>
			<!-- 图片显示区域END -->
		</div>
	</div>

</div>
<script src="//cdn.staticfile.org/jquery/2.2.4/jquery.min.js"></script>
<script src="./layui/layui.js"></script>
<script src="//cdn.staticfile.org/clipboard.js/2.0.6/clipboard.min.js"></script>
<script>
function copyurl(node){
	var clipboard = new ClipboardJS(".copy-btn", {
		text: function(trigger) {
			return $("#"+node).val();
		}
	});
	clipboard.on('success', function (e) {
		layer.msg('复制成功！', {icon: 1});
	});
	clipboard.on('error', function (e) {
		layer.msg('复制失败，请长按链接后手动复制', {icon: 2});
	});
}
function getFileName(path){
	var pos1 = path.lastIndexOf('/');
	var pos2 = path.lastIndexOf('\\');
	var pos  = Math.max(pos1, pos2)
	if( pos<0 )
		return path;
	else
		return path.substring(pos+1);
}
layui.use(['form','upload'], function(){
    var form = layui.form;
    var upload = layui.upload;
	var predata;
    form.render();
    upload.render({
        elem: '#multiple'
        ,url: "api.php"
        ,accept: 'images'
        ,acceptMime: 'image/*'
        ,size: 102400
        ,drag: true
		,auto: false
		,data: {}
		,headers: {'X-OSS-server-side-encrpytion': 'AES256'}
		,bindAction: '#uploadBtn'
		,choose: function(obj) {
			var filename = $("input[name=file]").val();
			if(filename == ''){
				layer.alert('请选择文件！', {icon: 2, skin: 'layui-layer-molv', closeBtn: 0});
				throw new Error('upload failed');
			}
			filename = getFileName(filename);
			layer.msg('正在准备文件上传', {icon: 16,time: 10000,shade:[0.3, "#000"]});
			var that = this;
			$.ajax({
				type : "POST",
				url : "api.php?act=pre_upload",
				data : {filename:filename},
				dataType : 'json',
				success : function(data) {
					layer.closeAll();
					if(data.code == 0){
						predata = data.data;
						that.data = {'Cache-Control':'max-age=2592000', 'Content-Disposition':'attachment', 'OSSAccessKeyId':predata.accessKeyId, 'Signature':predata.signature, 'host':predata.host, 'id':predata.id, 'key':predata.ossPath, 'policy':predata.policy, 'success_action_status':'200'};
						that.url = 'https://' + predata.host + '/';
						$('#uploadBtn').click();
					}else{
						layer.alert(data.msg, {icon: 2, skin: 'layui-layer-molv', closeBtn: 0});
						$("input[name=file]").val('')
					}
				},
				error: function () {
					layer.closeAll();
					layer.alert('上传失败！接口错误', {icon: 2});
				}
			});
		}
        ,before: function(obj) {
			layui.element.progress('demo', '0%');
            layer.load();
        }
        ,progress: function(n) {
            var percent = n + '%';
            layui.element.progress('demo', percent);
            if (n==100){
				layer.msg('上传成功，正在保存', {icon: 16,time: 10000,shade:[0.3, "#000"]});
            }
        }
        ,done: function(res){
            layer.closeAll();
			$.ajax({
				type : "POST",
				url : "api.php?act=complete_upload",
				data : {id: predata.id},
				dataType : 'json',
				success : function(data) {
					layer.closeAll();
					if(data.code == 0){
						var imgurl = 'https://' + predata.cdnDomain + '/' + predata.ossPath;
						
						
						$("#img-thumb a").attr('href',imgurl);
						$("#img-thumb img").attr('src',imgurl);
						$("#url").val(imgurl);
						$("#html").val("<img src='" + imgurl + "'/>");
						$("#markdown").val("![](" + imgurl + ")");
						$("#bbcode").val("[img]" + imgurl + "[/img]");
						$("#imgshow").show();
						$("input[name=file]").val('')
					}else{
						layer.alert(data.msg, {icon: 2, skin: 'layui-layer-molv', closeBtn: 0});
					}
				},
				error: function () {
					layer.closeAll();
					layer.alert('上传失败！接口错误', {icon: 2});
				}
			});
			$("input[name=file]").val('')
        }
        ,error: function(){
            layer.closeAll();
            layer.alert("文件上传失败！", {icon: 2, skin: 'layui-layer-molv', closeBtn: 0});
			$("input[name=file]").val('')
        }
    });
	
});
</script>
</body>
</html>
<html>
<head>
	<title>Encoding/Decoding data using Huffman algorithm</title>
	<script src="http://getbootstrap.com/2.3.2/assets/js/jquery.js"></script>
	<script src="http://getbootstrap.com/2.3.2/assets/js/bootstrap.js"></script>
	<link href="http://getbootstrap.com/2.3.2//assets/css/bootstrap.css" rel="stylesheet">
	
	<style type="text/css">
		body {
			padding-top: 40px;
			padding-bottom: 40px;
			background-color: #f5f5f5;
		}
		
		.error {
			color: red;
		}
		
		.form-encode {
			max-width: 300px;
			padding: 19px 29px 29px;
			margin: 0 auto 20px;
			background-color: #fff;
			border: 1px solid #e5e5e5;
			-webkit-border-radius: 5px;
			   -moz-border-radius: 5px;
					border-radius: 5px;
			-webkit-box-shadow: 0 1px 2px rgba(0,0,0,.05);
			   -moz-box-shadow: 0 1px 2px rgba(0,0,0,.05);
					box-shadow: 0 1px 2px rgba(0,0,0,.05);
		}
		.form-encode .form-encode-heading,
		.form-encode .checkbox {
			margin-bottom: 10px;
		}
		.btn-file {
			position: relative;
			overflow: hidden;
		}
		.btn-file input[type=file] {
			position: absolute;
			top: 0;
			right: 0;
			min-width: 100%;
			min-height: 100%;
			font-size: 100px;
			text-align: right;
			filter: alpha(opacity=0);
			opacity: 0;
			outline: none;
			background: white;
			cursor: inherit;
			display: block;
		}
	</style>
</head>
<body>
	<div class="container">
		<h2>Encode and decode files using Huffman coding algoritm</h2>
		<form method="POST" action="encode.php" class="form-encode" enctype="multipart/form-data">
			<input type="hidden" name="operation">
			<div class="btn btn-default btn-file input-block-level">
				Select file 
				<input type="file" name="filename" onchange="showFileName(this)">
			</div>
			
			<div class="form-control" id="filename"></div>
			
			<hr/>
			
			<div class="btn-group input-block-level">
				<button type="submit" class="btn btn-success" style="width:50%" onclick="this.form.operation.value='<?= Huffman::OPERATION_ENCODE?>'; this.form.submit();">Encode</button>
				<button type="submit" class="btn btn-primary" style="width:50%" onclick="this.form.operation.value='<?= Huffman::OPERATION_DECODE?>'; this.form.submit();">Decode</button>
			</div>
			
			<?php if($error) {?>
			<hr/>
			<div class="error"><?php echo $error; ?></div>
			<?php } ?>
		</form>
 		<div id="credentials">katalama, 2015</div>
	</div>
	<script>
		function showFileName(e){
			$('#filename').html( $(e).val() );
		}
	</script>
</body>
</html>


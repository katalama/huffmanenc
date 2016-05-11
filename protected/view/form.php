<html>
<head>
	<title>Encoding/Decoding data using Huffman algorithm</title>
	<link href="http://getbootstrap.com/2.3.2//assets/css/bootstrap.css" rel="stylesheet">
	<link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
	<div class="container">
		<h2>Encode and decode files using Huffman coding algoritm</h2>
		<form method="POST" action="encode.php" class="form-encode" enctype="multipart/form-data">
			<input type="hidden" name="operation">
			<div class="btn btn-default btn-file input-block-level">
				Select file 
				<input type="file" name="filename" onchange="document.getElementById('filename').innerHTML = this.value;">
			</div>
			
			<div class="form-control" id="filename"></div>
			
			<hr/>
			
			<div class="btn-group input-block-level">
				<button type="submit" class="btn btn-success" style="width:50%" onclick="this.form.operation.value='<?= Huffman::OPERATION_ENCODE ?>'; this.form.submit();">Encode</button>
				<button type="submit" class="btn btn-primary" style="width:50%" onclick="this.form.operation.value='<?= Huffman::OPERATION_DECODE ?>'; this.form.submit();">Decode</button>
			</div>
			
			<?php if($error) {?>
			<hr/>
			<div class="error"><?= $error ?></div>
			<?php } ?>
		</form>
 		<div id="credentials"><a href="mailto:katalama@mail.ru">katalama</a>, 2016</div>
	</div>
</body>
</html>


<?php
include "../protected/model/huffman.php";

$max_file_size = 3*1024*1024; // 3 Mb
$filesDir = '../files/';

// trying get downloaded file
if ($_FILES["filename"]["size"] > $max_file_size) {
	$error = "File size exceed limit [3Mb]";
	include "../protected/view/form.php";
	exit;
}

// Проверяем загружен ли файл
if (is_uploaded_file($_FILES["filename"]["tmp_name"])) {
	$uploadedFilePath = $filesDir.$_FILES["filename"]["name"];
	move_uploaded_file($_FILES["filename"]["tmp_name"], $uploadedFilePath);
}
else {
	$error = "File is not uploaded to server";
	include "../protected/view/form.php";
	exit;
}

ob_start();

$huffman = new Huffman($uploadedFilePath);

$operation = isset($_POST['operation']) ? $_POST['operation'] : Huffman::OPERATION_ENCODE;
try {
	switch ( $operation) {
		case Huffman::OPERATION_ENCODE:
			$huffman->encode();
			break;
		case Huffman::OPERATION_DECODE:
			$huffman->decode();
			break;
	}
}
catch (HuffmanException $e) {
	$error = $e->getMessage();
	include "../protected/view/form.php";
	exit;
}
catch (Exception $e) {
	$error = "Error. Try again.";
	include "../protected/view/form.php";
	exit;
}

ob_end_clean();

header('Content-Type: application/octet-stream');
header('Content-Disposition: filename="'.basename($huffman->getOutFilePath()).'"');
readfile($huffman->getOutFilePath());
unlink($huffman->getInFilePath());
unlink($huffman->getOutFilePath());

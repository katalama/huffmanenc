<?php

include 'huff_lib.php';




$source = $_GET['source'];
if (!$source)
	exit();



echo '<pre>';
$freqTable = getFrequencyTable($source);
echo "Frequency table: <br>";
print_r($freqTable);


$codingTree = getCodingTree($freqTable);
//print_r($codingTree);
echo '<div class="clear">'.$codingTree->dump().'</div>';

$codingTable = getCodingTable($codingTree);
echo "Coding table: <br>";
print_r($codingTable);

echo '</pre>';

$encoded = encode($source, $codingTable);

$source_size = strlen($source)*8;
$enc_size = strlen($encoded);


echo "<style>
		.clear {
			clear: both;
		}
		
		.node {
			width:50%;
			height:80%;
			float:left;
		}
		
		.title {
			background:lightgray;
			display:block;
			text-align:center;
			padding:10px;
			border:1px solid;
			border-radius:153px;
		}
		.leaf {
			background: lightblue;
		}
	</style>";

echo '<br/>'.$source.'<br>'.$encoded.'<br>';
echo $source_size.' / '.$enc_size.' = '.$source_size/$enc_size.'<br/>';
echo 'Compression: ' . (100 - 100*$enc_size/$source_size) . '%<br/>';
echo '<hr>';


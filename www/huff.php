<?php

include 'huff_lib.php';

$source = 'to be or not to be - that is the question!';//$_GET['source'];
$source or die();

$freqTable = getFrequencyTable($source);

$codingTree = getCodingTree($freqTable);

$codingTable = getCodingTable($codingTree);

$encoded = encode($source, $codingTable);
echo $encoded."\n";

$decoded = decode($encoded, $codingTable);
echo $decoded;

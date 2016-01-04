<?php

/*
class HuffmanTableNode {
	protected $_isLeaf;
	public $value;
	public $parent;
	public $branch;
	
	public function __construct($value, $isLeaf) {
		$this->value = $value;
		$this->_isLeaf = $isLeaf;
	}
	
	public function combine($anotherNode) {
		$newNode = new HuffmanTableNode($this->value + $anotherNode->value, false);
		$this->parent
	}
}
*/

class Huffman
{
	static function getFrequencyTable($file) {
		if (!$file) {
			echo "file path or file descriptor is not correct\n";
			return false;
		}
		
		if (is_string($file)) {
			$fileHandle = fopen($file, 'rb');
			if (!$fileHandle) {
				echo "File can not be open\n";
				return false;
			}
		}
		else {
			$fileHandle = $file;
			fseek($fileHandle, 0);
		}
		
		$freqTable = [];
		while (!feof($fileHandle)) {
			$c = fgetc($fileHandle);
			$freqTable[$c] = isset($freqTable[$c]) ? $freqTable[$c] + 1 : 1;
		}
		
		return $freqTable;
	}
	
	static function getCodingTable($freqTable) {
		$codingTable = [];
		
		asort($freqTable);
		
		$codingTree = [];
		foreach ($freqTable as $key=>$value) {
			$codingTree[ $key ] = ['value' => $value, 'parent' => null, 'branch' => null, 'isLeaf' => true];
		}
		
		return $codingTable;
	}
}

$filePath = 'huffman_test.txt';
$f = fopen($filePath, 'rb');
$freqTable = Huffman::getFrequencyTable($f, true);

$codingTable = Huffman::getcodingTable($freqTable);


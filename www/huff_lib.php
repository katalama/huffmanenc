<?php

class Node {
	public $left;
	public $right;

	public function __construct($right = null, $left = null) {
		$this->right = $right;
		$this->left = $left;
	}
}

trait HuffmanTreeDump {
	public function dump($nodeClass = 'node', $titleClass = 'title', $leafClass = 'leaf', $endClass = 'end') {
		return 
		"<div class=\"$nodeClass\">
			<span class=\"$titleClass ".($this->token ? $leafClass : '')."\">{$this->token}&nbsp;</span>".
			($this->left ? $this->left->dump() : "<div class=\"$endClass\"></div>").
			($this->right ? $this->right->dump() : "<div class=\"$endClass\"></div>").
		"</div>";
	}
}

class HuffmanNode extends Node{
	use HuffmanTreeDump;
	
	public $token;
	public $value;
	
	public function __construct($token = null, $value = null, $right = null, $left = null) {
		parent::__construct($right, $left);

		$this->token = $token;
		$this->value = $value;
	}
	
	public function combine($node) {
		return new self(null, $this->value + $node->value, $this, $node);
	}
}

function getFrequencyTable( $source ) {
	return array_count_values(str_split($source));
}

function getLeast($list1, $list2){
	$item1 = $list1->current(); 
	$item2 = $list2->current();
	
	if (!$item1) 
		$source = $list2;
	elseif (!$item2) 
		$source = $list1;
	else 
		$source = ($item1->value <= $item2->value) ? $list1 : $list2;

	$least = $source->current();
	$source->next();

	return $least;
}

function getCodingTree( $frequencyTable ) {
	asort($frequencyTable);
	
	$nodes = new ArrayIterator();
	$leaves = new ArrayIterator();
	
	foreach ($frequencyTable as $key=>$value)
		$leaves->append(new HuffmanNode($key, $value));
	
	while (true) {
		$firstNode = getLeast($nodes, $leaves);
		$secondNode = getLeast($nodes, $leaves);

		if ($secondNode) 
			$nodes->append($firstNode->combine($secondNode));
		else 
			return $firstNode;
	}
}
    
function getCodingTable($root, $code = '') {
	if (!$root->left && !$root->right)
		return [$root->token => [
			'code' => ($code===''? 1 : bindec($code)), 
			'bits'=>strlen($code)?:1]
		];
	
	return getCodingTable($root->left, $code.'1') + getCodingTable($root->right, $code.'0');
}

function encode($data, $codingTable){
	foreach ($codingTable as &$item) 
		$item = sprintf("%0{$item['bits']}b", $item['code']);

	return str_replace(
		array_keys($codingTable), 
		array_values($codingTable), 
		$data
	);
}

function encodeString($string) {
	return encode($string, getCodingTable(getCodingTree(getFrequencyTable($string))));
}

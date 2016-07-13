<?php

function getFrequencyTable( $source ) {
	return array_count_values(str_split($source));
}

class Node {
	public $left = null;
	public $right = null;
	public $token = null;
	public $value = null;
	
	public function __construct($token = null, $value = null) {
		$this->token = $token;
		$this->value = $value;
	}
	
	public function combine($node) {
		$newNode = new Node(null, $this->value + $node->value);
		$newNode->right = $this;
		$newNode->left = $node;
		
		return $newNode;
	}
	
	public function dump() {
		return '
		<style>
			.node {
				width:50%;
				height:80%;
				float:left;
			}
			
			.title {
				background:lightgray;
				display:block;
				text-align:center;
			}
		</style>
		<div class="node">
			<span class="title">'.$this->value.'['.$this->token.']</span>'.
			($this->left ? $this->left->dump() : '<div class="end"></div>').
			($this->right ? $this->right->dump() : '<div class="end"></div>').
		'</div>';
	}
}

function getCodingTree( $frequencyTable ) {
	asort($frequencyTable);
	
	$nodes = new ArrayIterator();
	$leaves = new ArrayIterator();
	
	foreach ($frequencyTable as $key=>$value)
		$leaves->append(new Node($key, $value));
	
	while (true) {
		$firstNode = getLeast($nodes, $leaves);
		$secondNode = getLeast($nodes, $leaves);

		if ($firstNode && $secondNode) 
			$nodes->append($firstNode->combine($secondNode));
		else 
			return $firstNode ?: $secondNode;
	}
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

function getCodingTable($root, $code = '') {
	if (!$root->left && !$root->right)
		return [$root->token => $code];
	
	return array_merge(
		getCodingTable($root->left, $code.'1'), 
		getCodingTable($root->right, $code.'0')
	);
}

function encode($data, $codingTable){
	return str_replace(
		array_keys($codingTable), 
		array_values($codingTable), 
		$data
	);
}

$source = 'abracadabra';

$freqTable = getFrequencyTable($source);

$codingTree = getCodingTree($freqTable);
echo $codingTree->dump();


$codingTable = getCodingTable($codingTree);

$encoded = encode($source, $codingTable);

var_dump($encoded);

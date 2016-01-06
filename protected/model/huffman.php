<?php

class HuffmanCodingTreeNode {
    public $code;
    public $freq = null;
    public $leftChild = null;
    public $rightChild = null;
    public $parent = null;
    public $bit;
    
    public function __construct($code, $freq) {
        $this->code = $code;
        $this->freq = $freq;
    }
    
    public function combine($anotherNode) {
        $newNode = new HuffmanCodingTreeNode(null, $this->freq + $anotherNode->freq);
        $newNode->leftChild = $this;
        $newNode->rightChild = $anotherNode;
        $this->parent = $anotherNode->parent = $newNode;
        $this->bit = !$anotherNode->bit = true;
        return $newNode;
    }
}


class Huffman
{
    const OPERATION_ENCODE = 1;
    const OPERATION_DECODE = 2;
    const FILE_PREFIX = 'ktlm';

    public $percentComplete;
    public $operation;
    
    private $_tokenLength;
    private $_inFilePath = null;
    private $_outFilePath = null;
    private $_fileHandle = null;
    private $_leaves = null;
    private $_rootNode = null;
    private $_codingTable = null;
    
    public function __construct($filePath, $tokenLength = 1){
        $this->_inFilePath = $filePath;
        $this->_tokenLength = $tokenLength;
        
        if (file_exists($filePath)) {
            $fileHandle = fopen($filePath, 'rb');
            if (!$fileHandle) 
                throw new Exception('File can not be open');

            $this->_fileHandle = $fileHandle;
        }
        else 
            throw new Exception("File [$filePath] is not exists");
    }

	public function getOutFilePath(){
		return $this->_outFilePath;
	}

	public function getInFilePath(){
		return $this->_inFilePath;
	}

    private function _getFrequencyTable() {
        $freqTable = [];
        while (true) {
            $token = fread($this->_fileHandle, $this->_tokenLength);
            if ( !feof($this->_fileHandle) || $token )
                $freqTable[$token] = isset($freqTable[$token]) ? $freqTable[$token] + 1 : 1;
            else 
                break;
        }
        asort($freqTable);

        $this->_leaves = new ArrayIterator();
        foreach ($freqTable as $code=>$freq) {
            $leaf = new HuffmanCodingTreeNode($code, $freq);
            $this->_leaves->append($leaf);
        }
        $this->_leaves->rewind();
    }
    
    public function _buildCodingTree() {
        if(!$this->_leaves)
            $this->_getFrequencyTable();

        $nodes = new ArrayIterator();

        while (!$this->_rootNode) {
            $firstLeaf = $this->_getLeast($this->_leaves, $nodes);
            $secondLeaf = $this->_getLeast($this->_leaves, $nodes);

            if ($firstLeaf && $secondLeaf) 
                $nodes->append($firstLeaf->combine($secondLeaf));
            else 
                $this->_rootNode = $firstLeaf ?: $secondLeaf;
        }
    }
    
    private function _getLeast($leaves, $nodes){
        $leaf = $leaves->current(); // can be null
        $node = $nodes->current(); // is not null
        
        if (!$leaf) 
            $source = $nodes;
        elseif (!$node) 
            $source = $leaves;
        else 
            $source = ($leaf->freq <= $node->freq) ? $leaves : $nodes;

        $least = $source->current();
        $source->next();

        return $least;
    }
    
    public function getCodingTable( $asArray=false ){
        if (!$this->_rootNode) 
            $this->_buildCodingTree();

        $codingTable = [];
        
        foreach($this->_leaves as $leaf){
            $codingTable[$leaf->code] = ['code' => 0, 'bits' => 0];
            
            $entity = $leaf;
            while ($entity->parent) {
                $codingTable[$leaf->code]['code'] = $codingTable[$leaf->code]['code'] | (($entity->bit ? 0x1 : 0x0)<<$codingTable[$leaf->code]['bits']);
                $codingTable[$leaf->code]['bits'] += 1;
                $entity = $entity->parent;
            }
        }

        if ($asArray)
            return $codingTable;
        
        $codingTableStr = '';
        foreach ($codingTable as $key=>$value) {
            $codingTableStr .= $key.pack("NC", $value['code'], $value['bits']);
        }

        return $codingTableStr;
    }
        
    private function _encodeData($outFileHandle){
        $codingTable = $this->getCodingTable(true);
        fseek($this->_fileHandle, 0);
        
        $currentWord = 0;
        $bitsRemain = 32;
        
        $positionForTrailinZeroBitsCount = ftell($outFileHandle);
        fwrite($outFileHandle, pack('c',0));

        while (true) {
            $token = fread($this->_fileHandle, $this->_tokenLength);
            
            if ( feof($this->_fileHandle) && !$token )
                break;
                
            if (isset($codingTable[$token])) {
                $entity = $codingTable[$token];
                
                while($bitsRemain<=$entity['bits']){
                    $entity['bits'] -= $bitsRemain;
                    $currentWord = ($currentWord<<$bitsRemain) | ($entity['code'] >> ($entity['bits']));
                    $entity['code'] &= (1<<$entity['bits']) - 1;
                    
                    // packs a long (32 bits) into string in format N (big endian)
                    fwrite($outFileHandle, pack('N',$currentWord));

                    $bitsRemain = 32;
                    $currentWord = 0;
                }
                
                $currentWord <<= $entity['bits'];
                $currentWord |= ($entity['code'] & (1<<$entity['bits'])-1);
                $bitsRemain -= $entity['bits'];
            }
            else {
                throw new Exception("Coding table is not correct [$c]");
            }
        }
        $currentWord <<= $bitsRemain;
        fwrite($outFileHandle, pack('N',$currentWord));
        
        fseek($outFileHandle, $positionForTrailinZeroBitsCount);
        fwrite($outFileHandle, pack('c', $bitsRemain));
        fseek($outFileHandle, 0, SEEK_END);
    }
    
    public function encode(){
        $this->operation = Huffman::OPERATION_ENCODE;

        $this->_outFilePath = $this->_inFilePath.'.encoded';
        $outFileHandle = fopen( $this->_outFilePath, 'wb');
        
        fwrite($outFileHandle, Huffman::FILE_PREFIX);
        fwrite($outFileHandle, chr($this->_tokenLength));
        
        $codingTable = $this->getCodingTable(false);
        fwrite($outFileHandle, pack('N',strlen($codingTable)) . $codingTable);
        
        // TODO: encode data in a child process to be able to get the complete percent
        $this->_encodeData($outFileHandle);
        
        fclose($outFileHandle);
    }

    private function _buildDecodingTree( $codingTable, $depth=1 ){
        $rootNode = new HuffmanCodingTreeNode(null, 0);
        if (count($codingTable)==1){
            $rootNode->code = array_keys($codingTable)[0];
            return $rootNode;
        }
        
        // determine left  and right parts of tree
        $leftPart = $rightPart = [];
        foreach ($codingTable as $key=>$entity) {
            
            if ( ($entity['code'] >> ($entity['bits']-$depth)) & 0x1 )
                $leftPart[$key] = $entity;
            else
                $rightPart[$key] = $entity;
        }

        $rootNode->leftChild = $this->_buildDecodingTree($leftPart, $depth + 1);
        $rootNode->rightChild = $this->_buildDecodingTree($rightPart, $depth + 1);
        
        return $rootNode;
    }

    private function _extractCodingTable($asArray = false){
        // 1 byte for token length
        $tokenLength = ord(fread($this->_fileHandle, 1));
        // 4 bytes for coding table size
        $codingTableSize = unpack('N', fread($this->_fileHandle, 4))[1];
        
        $entitySize = ($tokenLength + 4 + 1);
        $entityCount = (int)($codingTableSize/$entitySize);
        $codingTable = [];
        for($i=0; $i<$entityCount; $i++){
            $key = fread($this->_fileHandle, $tokenLength);
            $codingTable[$key] = unpack('N1code/C1bits', fread($this->_fileHandle, 4 + 1));
        }
        
        return $codingTable;
    }
    
    public function decode(){
        $this->operation = Huffman::OPERATION_DECODE;
        
        fseek($this->_fileHandle, 0, SEEK_SET);
        
        if (fread($this->_fileHandle, strlen(Huffman::FILE_PREFIX)) != Huffman::FILE_PREFIX){
            throw new Exception("File is not a valid KTLM Huffman encoded file.");
        }
        
        $this->_outFilePath = $this->_inFilePath.'.decoded';
        $outFileHandle = fopen($this->_outFilePath, 'wb');
        if ($outFileHandle) 
            $this->_decodeData($outFileHandle);
        else
            throw new Exception("Can not open file [{$this->_inFilePath}.decoded] for write.");
        
        fclose($outFileHandle);
    }
    
    private function _decodeData($outFileHandle) {
        $codingTable = $this->_extractCodingTable();
        
        $decodingTree = $this->_buildDecodingTree($codingTable);

        // 1 byte for trailing zero bits
        $trailingBits = ord(fread($this->_fileHandle, 1));
        
        $encodedStartPos = ftell($this->_fileHandle);
        fseek($this->_fileHandle, 0, SEEK_END);
        $encodedSize = ftell($this->_fileHandle) - $encodedStartPos;
        fseek($this->_fileHandle, $encodedStartPos, SEEK_SET);
        
        $currentNode = $decodingTree;
        $currentOffset = 0;
        $nextWord = unpack('N', fread($this->_fileHandle, 4))[1];
        $encodedLeft = $encodedSize - 4;

$nextShowedPercent = 0;

        while (true) {
            if ( feof($this->_fileHandle) || $encodedLeft<0 && ($currentOffset<=$trailingBits) )
                break;
            
            if ($currentOffset == 0) {
                $currentWord = $nextWord;
                $currentOffset = 32;
                $this->percentComplete = 100-100*$encodedLeft/$encodedSize;

if($this->percentComplete >= $nextShowedPercent) {
    $nextShowedPercent += 10;
    echo round($this->percentComplete,2)." percent completed\n";
}

                if($encodedLeft > 0)
                    $nextWord = unpack('N', fread($this->_fileHandle, 4))[1];
                $encodedLeft -= 4;
            }
            
            $currentBit = ($currentWord & (1<<($currentOffset - 1))) >> ($currentOffset - 1);
            $currentNode = $currentBit ? $currentNode->leftChild : $currentNode->rightChild;
            $currentOffset -= 1;
            
            if ($currentNode->code !== null) {
                fwrite($outFileHandle, $currentNode->code);
                $currentNode = $decodingTree;
            }
            
        }
    }
}

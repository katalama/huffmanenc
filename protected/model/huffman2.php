<?php

/**
 * 
 */
class HuffmanCodingTreeNode {
    /**
     * code - кодируемая последовательность
     */
    public $code;
    /**
     * freq - Частота комбинации code во входном файле
     */
    public $freq = null;
    
    /**
     * leftChild/rightChild - левое/правое поддерево
     */
    public $leftChild = null;
    public $rightChild = null;
    
    /**
     * parent - родительский узел
     */
    public $parent = null;
    
    /**
     * bit - направление спуска от родительского узла 1/0
     */
    public $bit;
    
    public function __construct($code, $freq) {
        $this->code = $code;
        $this->freq = $freq;
    }
    
    /**
     * Комбинирует узел с другим
     * Результат - родительский узел для обоих
     */
    public function combine($anotherNode) {
        $newNode = new HuffmanCodingTreeNode(null, $this->freq + $anotherNode->freq);
        $newNode->leftChild = $this;
        $newNode->rightChild = $anotherNode;
        $this->parent = $anotherNode->parent = $newNode;
        $this->bit = !$anotherNode->bit = true;
        return $newNode;
    }
}

class Huffman2 {
    
    public $_freqTable;
    public $_tokenLength;
    public $_fileHandle;
    public $_leaves;
    public $_rootNode;
    public $_codingTable;
    
    public function __construct( $freqTable = null, $tokenLength = 1 ){
        if ($this->_freqTable) 
            $this->setFreqTable($freqTable);
        else 
            $this->setTokenLength($tokenLength);
    }
    
    public function setFreqTable($freqTable){
        // checks
        if (is_array($freqTable)) {
            $this->_freqTable = $freqTable;
            $keys = array_keys($this->_freqTable);
            $this->_tokenLength = strlen($keys[0]);
        }
    }
    
    public function setTokenLength($tokenLength){
        // checks
        if (intval($tokenLength)){
            $this->_tokenLength = intval($tokenLength);
        }
    }
    
    public function setSource($source) {
        $this->_sourceType = gettype($source);
        
        if ($this->_sourceType == 'resource'){
            $this->_fileHandle = $source;
        }
        elseif ($this->_sourceType == 'string') {
            $this->_fileHandle = tmpfile();
            fwrite($this->_fileHandle, $source);
            fseek($this->_fileHandle, 0);
        }
        else {
            throw new Exception('Error! Source must be a file or a string.');
        }
        
        if ($this->_fileHandle && !$this->_freqTable){
            $this->getFreqTable();
        }
    }
    
    public function getFreqTable(){
        if (!$this->_freqTable) {
            if ($this->_fileHandle) {
                $this->_freqTable = [];
                while (true) {
                    $token = fread($this->_fileHandle, $this->_tokenLength);
                    if ( !feof($this->_fileHandle) || $token )
                        $this->_freqTable[$token] = isset($this->_freqTable[$token]) ? $this->_freqTable[$token] + 1 : 1;
                    else 
                        break;
                }
                asort($this->_freqTable);
            }
            else {
                throw new Exception('Can\'t get frequency table');
            }
        }
        
        return $this->_freqTable;
    }

    public function _getLeaves(){
        if (!$this->_leaves) {
            $freqTable = $this->getFreqTable();
            $this->_leaves = new ArrayIterator();

            foreach ($freqTable as $code=>$freq) {
                $leaf = new HuffmanCodingTreeNode($code, $freq);
                $this->_leaves->append($leaf);
            }
        }
        
        return $this->_leaves;
    }

    /**
     * Строит кодовое дерево по массиву листьев
     */
    public function _buildCodingTree() {
    
        $nodes = new ArrayIterator();
        $leaves = $this->_getLeaves();

        while (!$this->_rootNode) {
            $firstLeaf = $this->_getLeast($leaves, $nodes);
            $secondLeaf = $this->_getLeast($leaves, $nodes);

            if ($firstLeaf && $secondLeaf) 
                $nodes->append($firstLeaf->combine($secondLeaf));
            else 
                $this->_rootNode = $firstLeaf ?: $secondLeaf;
        }
    }
    
    /**
     * Выбирает наименьший (по частоте) элемент из массивов листьев и узлов
     */
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
    
    /**
     * По кодовому дереву определяет кодирующие последовательности 
     * для кодируемых последовательностей
     */
    public function getCodingTable(){
        if (!$this->_codingTable) {
            $this->_codingTable = [];
            
            $this->_buildCodingTree();
            
            foreach($this->_leaves as $leaf){
                $this->_codingTable[$leaf->code] = ['code' => 0, 'bits' => 0];
                
                $entity = $leaf;
                while ($entity->parent) {
                    $this->_codingTable[$leaf->code]['code'] = $this->_codingTable[$leaf->code]['code'] | (($entity->bit ? 0x1 : 0x0)<<$this->_codingTable[$leaf->code]['bits']);
                    $this->_codingTable[$leaf->code]['bits'] += 1;
                    $entity = $entity->parent;
                }
            }
        }
        
        return $this->_codingTable;
    }

    // return [data, trailing_zero_bits]
    private function _encode(){
        $outFileHandle = tmpfile();
        
        $codingTable = $this->getCodingTable();
        fseek($this->_fileHandle, 0);
        
        $currentWord = 0;
        $bitsRemain = 32;

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
        
        // Last word would be filled by zero bits for alignment
        $currentWord <<= $bitsRemain;
        fwrite($outFileHandle, pack('N',$currentWord));
        
        // Need to save count of trailing zero bits
        fseek($outFileHandle, 0);
        
        return ['data' => $outFileHandle, 'trailingBits' => $bitsRemain];
    }
    
    public function encode($source = null){
        if ($source)
            $this->setSource($source);
        
        $encoded = $this->_encode();
        if ($this->_sourceType == 'string') {
            $meta = stream_get_meta_data($encoded['data']);
            $encoded['data'] = file_get_contents($meta['uri']);
        }

        return $encoded;
    }

    public function decode($source = null, $trailingBits){
        if ($source)
            $this->setSource($source);
        
        $encoded = $this->_decode($trailingBits);
        if ($this->_sourceType == 'string') {
            $meta = stream_get_meta_data($encoded);
            $encoded = file_get_contents($meta['uri']);
        }

        return $encoded;
    }
    
    private function _decode($trailingBits) {
        $outFileHandle = tmpfile();
        fseek($this->_fileHandle, 0);
        
        $currentNode = $this->_rootNode;
        $currentOffset = 0;
        $nextWord = unpack('N', fread($this->_fileHandle, 4))[1];

        while (true) {
            if ( feof($this->_fileHandle) && $currentOffset==$trailingBits )
                break;
            
            if ($currentOffset == 0) {
                $currentWord = $nextWord;
                $currentOffset = 32;

                $nextWord = @unpack('N', fread($this->_fileHandle, 4))[1];
            }
            
            $currentBit = ($currentWord & (1<<($currentOffset - 1))) >> ($currentOffset - 1);
            // Move down about coding tree untill reach a leaf
            $currentNode = $currentBit ? $currentNode->rightChild : $currentNode->leftChild;
            $currentOffset -= 1;
            
            // Leaf is reached
            if ($currentNode->code !== null) {
                fwrite($outFileHandle, $currentNode->code);
                $currentNode = $this->_rootNode;
            }
        }
        return $outFileHandle;
    }
}

// test
$h = new huffman2();

$dec = 'to be or not to be - that is the question!';
var_dump($dec);

$encoded = $h->encode($dec);

$dec = $h->decode($encoded['data'], $encoded['trailingBits']);
var_dump($dec);

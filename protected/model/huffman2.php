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
    
    protected $_freqTable;
    protected $_tokenLength;
    protected $_srcFileHandle;
    protected $_leaves;
    protected $_rootNode;
    protected $_codingTable;
    
    const OPERATION_ENCODE = 1;
    const OPERATION_DECODE = 2;
    
    public function __construct( $freqTable = null, $tokenLength = 1 ){
        if ($freqTable) 
            $this->setFreqTable($freqTable);
        else 
            $this->_setTokenLength($tokenLength);
    }
    
    public function getFreqTable(){
        if (!$this->_freqTable) {
            if ($this->_srcFileHandle) {
                $this->_freqTable = [];
                while (true) {
                    $token = fread($this->_srcFileHandle, $this->_tokenLength);
                    if ( !feof($this->_srcFileHandle) || $token )
                        $this->_freqTable[$token] = isset($this->_freqTable[$token]) ? $this->_freqTable[$token] + 1 : 1;
                    else 
                        break;
                }
                asort($this->_freqTable);
            }
            else {
                throw new Exception('Frequency table is not set and also source is not defined');
            }
        }
        
        return $this->_freqTable;
    }

    public function setFreqTable($freqTable){
        // checks
        if (is_array($freqTable)) {
            $this->_freqTable = $freqTable;
            $keys = array_keys($this->_freqTable);
            $this->_tokenLength = strlen($keys[0]);
        }
    }
    
    protected function setOperation( $operation ){
        if ($operation != self::OPERATION_ENCODE && $operation != self::OPERATION_DECODE){
            return false;
        }
        
        $this->_operation = $operation;
        return true;
    }

    public function getOperation(){
        return $this->_operation;
    }
    
    private function _setTokenLength($tokenLength){
        // checks
        if (intval($tokenLength)){
            $this->_tokenLength = intval($tokenLength);
        }
    }
    
    public function setSource($source) {
        $this->_sourceType = gettype($source);
        
        if ($this->_sourceType == 'resource'){
            $this->_srcFileHandle = $source;
        }
        elseif ($this->_sourceType == 'string') {
            $this->_srcFileHandle = tmpfile();
            fwrite($this->_srcFileHandle, $source);
            fseek($this->_srcFileHandle, 0);
        }
        else {
            throw new Exception('Error! Source must be a file or a string.');
        }
        
        if ($this->_srcFileHandle && !$this->_freqTable){
            $this->getFreqTable();
        }
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

    // return [data, trailingBits]
    private function _encode(){
        $outFileHandle = tmpfile();
        
        $codingTable = $this->getCodingTable();
        fseek($this->_srcFileHandle, 0);
        
        $currentWord = 0;
        $bitsRemain = 32;

        while (true) {
            $token = fread($this->_srcFileHandle, $this->_tokenLength);
            if ( feof($this->_srcFileHandle) && !$token )
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
                throw new Exception("Coding table is not correct: there is no code for ['$token']");
            }
        }
        
        // Last word would be filled by zero bits for alignment
        $currentWord <<= $bitsRemain;
        fwrite($outFileHandle, pack('N',$currentWord));
        fseek($outFileHandle, 0);
        
        return ['data' => $outFileHandle, 'trailingBits' => $bitsRemain];
    }

    // return [data]
    private function _decode($trailingBits) {
        $outFileHandle = tmpfile();
        fseek($this->_srcFileHandle, 0);
        
        $currentNode = $this->_rootNode;
        $currentOffset = 0;
        $nextWord = unpack('N', fread($this->_srcFileHandle, 4))[1];

        while (true) {
            if ( feof($this->_srcFileHandle) && $currentOffset==$trailingBits )
                break;
            
            if ($currentOffset == 0) {
                $currentWord = $nextWord;
                $currentOffset = 32;

                $nextWord = @unpack('N', fread($this->_srcFileHandle, 4))[1];
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
        return ['data' => $outFileHandle];
    }

    private function _process($source = null, $trailingBits = null) {
        if (empty($this->_operation))
            throw new Exception('Operation (encode/decode) is undefined');
        
        if ($source)
            $this->setSource($source);
        
        if (!$this->_srcFileHandle)
            throw new Exception('Source is undefined');
        
        if (!$this->getCodingTable())
            throw new Exception('Can\'t get coding table');
        
        if ($this->_operation == self::OPERATION_ENCODE)
            $processed = $this->_encode();
        else 
            $processed = $this->_decode($trailingBits);
        
        if ($this->_sourceType == 'string') {
            $meta = stream_get_meta_data($processed['data']);
            $processed['data'] = file_get_contents($meta['uri']);
        }

        return $processed;
    }
    
    public function encode($source = null){
        $this->setOperation(self::OPERATION_ENCODE);
        try {
            $processed = $this->_process($source);
            return $processed;
        } catch (Exception $e) {
            echo "Error: ".$e->getMessage().PHP_EOL;
        }
        
        return false;
    }
    
    public function decode($source = null, $trailingBits = null){
        $this->setOperation(self::OPERATION_DECODE);
        try {
            if ($trailingBits===null)
                throw new Exception('Trailing bits count must be defined for decode procedure');
            
            $processed = $this->_process($source, $trailingBits);
            return $processed['data'];
        } catch (Exception $e) {
            echo "Error: ".$e->getMessage().PHP_EOL;
        }
        
        return false;
    }
}


// test
$h = new huffman2(); 
$src = 'To be or not to be - that is the question!To be or not to be - that is the question!To be or not to be - that is the question!';

$enc = $h->encode($src);
$dec = $h->decode($enc['data'], $enc['trailingBits']);

var_dump($src);
var_dump($dec);
var_dump($dec == $src);


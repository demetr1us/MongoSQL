<?php
namespace MEV {
require_once('Parser.class.php');

class MongoBuilder {

    protected $sql_parts;
    private $client;
    private $db='db';
    private $client_db;
    private $from;

    public function __construct()
    {
        $this->client = new \MongoClient();
    }


    /**
     * @param $sql string
     * @return \MongoCursor
     */
    public function query($sql){
        $parser = new Parser($sql);
        $parser->process();
        $this->sql_parts  = $parser->getSQLParts();
        $this->processFrom();
        return $this->run();

    }

    /**
     * @param $cursor \MongoCursor
     */
    public function showResult($cursor){
        foreach ($cursor as $row){
            unset($row['_id']);
            echo json_encode($row)."\n";
        }
    }

    private function run(){

        $cursor = $this->client_db->{$this->from}->find($this->processWhere(), $this->processSelect());
        if (!empty($this->sql_parts['order_by'])){
            $cursor = $cursor->sort($this->processOrderBy());
        }
        if (!empty($this->sql_parts['skip'])){
            $cursor = $cursor->skip(intval($this->sql_parts['skip']));
        }
        if (!empty($this->sql_parts['limit'])){
            $cursor = $cursor->limit(intval($this->sql_parts['limit']));
        }

        return $cursor;
    }

    private function  processOrderBy(){
        $orders = explode(',', $this->sql_parts['order_by']);
        $mongo_order = array();
        foreach ($orders as $order){
            $order_field = explode(" ", $order);
            if (!isset($order_field[1])) {
                $mongo_order[$order_field[0]] =  1;
            }
            else{
                if (trim($order_field[1]) == 'asc') {
                    $mongo_order[$order_field[0]] = 1;
                }else{
                    $mongo_order[$order_field[0]] = -1;
                }
            }
        }
        return $mongo_order;
    }

    private function processSelect(){
        $select = $this->sql_parts['select'];
        $fields = explode(',', $select);
        $projection = array();
        foreach ($fields as $field){
            $proj = $this->fieldToProjection($field);
            if (!empty($proj)) {
                $projection[] = $proj;
            }
        }
        $this->projection = $projection;
        return $projection;
    }

    private function fieldToProjection($field){
        $parts = explode('.', $field);
        if (count($parts)==1){
            return trim(str_replace('*', '', $field));
        }
        $projection = array();
        foreach ($parts as $part) {
            $projection[] = trim(str_replace('*', '', $part));
        }
        return implode('.', $projection);
    }

    private function processFrom(){
        $from = $this->sql_parts['from'];
        $from_parts = explode('.', $from);
        if (count($from_parts)>1){
            $this->db = trim($from_parts[0]);
            $this->from = trim($from_parts[1]);
        }else{
            $this->from = trim($from_parts[0]);
        }
        $this->client_db = $this->client->{$this->db};
    }

    private function processWhere(){
        if (!isset($this->sql_parts['where'])) {
            return array();
        }
        $criteria = array();
        foreach ($this->sql_parts['where'] as $or) {
            $element = array();
            if (is_array($or) && count($or)>1) {
                foreach ($or as $and) {
                    $element[] = $this->conditionToCriteria($and);
                }
                $element = array('$and'=>$element);
            }
            else{
                    $element = $this->conditionToCriteria($or);
                }
                $criteria[] = $element;
            }


            if (count($criteria) > 1) {
                return array('$or'=>$criteria);
            } else {
                return $criteria[0];
            }
    }

    private function conditionToCriteria($condition){
        $opers = array();
        if (is_array($condition)) $condition = array_pop($condition);
        if (strpos($condition, '>=')!==false){
            $opers = explode('>=', $condition);
            return array($this->sanitizeOperator($opers[0]) => array('$gte' => $this->sanitizeOperator($opers[1])));
        }elseif (strpos($condition, '<=')!==false){
            $opers = explode('<=', $condition);
            return array($this->sanitizeOperator($opers[0]) => array('$lte' => $this->sanitizeOperator($opers[1])));
        }elseif (strpos($condition, '<>')!==false){
            $opers = explode('<>', $condition);
            return array($this->sanitizeOperator($opers[0]) => array('$ne' => $this->sanitizeOperator($opers[1])));
        }elseif (strpos($condition, '>')!==false){
            $opers = explode('>', $condition);
            return array($this->sanitizeOperator($opers[0]) => array('$gt' => $this->sanitizeOperator($opers[1])));
        }elseif (strpos($condition, '<')!==false){
            $opers = explode('<', $condition);
            return array($this->sanitizeOperator($opers[0]) => array('$lt' => $this->sanitizeOperator($opers[1])));
        }elseif (strpos($condition, '=')!==false){
            $opers = explode('=', $condition);
            $name = $this->sanitizeOperator($opers[0]);
            $value = $this->sanitizeOperator($opers[1]);
            return array($name => $value);
        }
    }

    private function sanitizeOperator($operator){
        $operator = trim($operator);
        if (is_numeric($operator)) return intval($operator);
        return preg_replace('/^(\'(.*)\'|"(.*)")$/', '$2$3', $operator);
    }
}
}

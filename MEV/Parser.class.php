<?php
namespace MEV {

    require_once('WhereParser.php');

    class  Parser
    {

        protected $query;

        public $select;
        public $from;
        public $where;
        public $order_by;
        public $skip;
        public $limit;


        public function __construct($query)
        {
            $this->query = $query;
        }


        protected function normalize()
        {
            $this->query = str_replace("\n", ' ', $this->query);
            $this->query = preg_replace('/\s+/', ' ', strtolower($this->query));
            $this->query = preg_replace(
                array('/(select )/', '/(from )/', '/(where )/', '/(order by )/', '/(skip )/', '/(limit )/'),
                "\n" . '$0' . 'DDD',
                $this->query
            );
            return $this->query;
        }

        protected function processRow($row)
        {
            $keyword = strtok($row, ' ');
            if ($keyword == 'where') {
                $whereParser = new WhereParser(explode('DDD', $row)[1]);
                $this->where = $whereParser->getDecomposedString();
            }elseif ($keyword == 'order'){
                $this->order_by = explode('DDD', $row)[1];
            } else {
                $this->$keyword = explode('DDD', $row)[1];
            }
        }

        protected function process_order($row){

        }

        public function process()
        {
            $this->normalize();
            $rows = explode("\n", $this->query);

            foreach ($rows as $row) {
                if (empty($row)) {
                    continue;
                }
                $this->processRow($row);
            }
        }

        public function getSQLParts()
        {
            return array(
                'select' => $this->select,
                'from' => $this->from,
                'where' => $this->where,
                'order_by' => $this->order_by,
                'skip' => $this->skip,
                'limit' => $this->limit
            );
        }

    }
}


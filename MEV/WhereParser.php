<?php

namespace MEV {

    class WhereParser
    {

        protected $where_str;
        protected $where_parts = array();

        public function __construct($where_str)
        {
            $this->where_str = $where_str;
        }

        protected function decompose()
        {
            $ors = explode(' or ', $this->where_str);
            $or_blocks = array();
            foreach ($ors as $key => $or) {
                $ands = explode(' and ', $or);
                $or_blocks[$key] = $ands;
            }
            $this->where_parts = $or_blocks;
        }

        public function getDecomposedString()
        {
            $this->decompose();
            return $this->where_parts;
        }


    }
}
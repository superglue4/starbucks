<?php

/**
 * 쏘는 사람
 * Class Buyer_model
 */
class Buyer_model extends CI_Model
{
    /**
     * Buyer_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 조회
     * @param $param
     * @return array
     */
    public function select($param)
    {
        $escape = $this->db->escape($param);
        $arr = array();
        if (isset($param['now']) && $param['now'] === true) {
            $arr[] = 'NOW() between `start` and `end`';
        }

        if (isset($param['member_name'])) {
            $arr[] = sprintf('member_name = %s', $escape['member_name']);
        }
        if (isset($param['ordnum'])) {
            $arr[] = sprintf('ordnum = %s', $escape['ordnum']);
        }

        $where = '';
        if (count($arr) > 0) {
            $where = 'WHERE ' . join(' AND ', $arr);
        } else {
            return array();
        }

        $sql = <<<SQL
SELECT ordnum, member_name, start, `end`, comment, regdate 
FROM buyer
{$where}
SQL;
//        echo $sql;
        $query = $this->db->query($sql);
        return $query->result_array();
    }

    /**
     * 입력
     * @param $param
     * @return bool
     */
    public function insert($param)
    {
        if (empty($param['ordnum'])) {
            return false;
        }
        if (empty($param['member_name'])) {
            return false;
        }
        if (empty($param['start'])) {
            return false;
        }
        if (empty($param['end'])) {
            return false;
        }
        if (empty($param['comment'])) {
            return false;
        }

        $escape = $this->db->escape($param);
        $sql = <<<SQL
INSERT INTO buyer SET 
ordnum = {$escape['ordnum']},
member_name = {$escape['member_name']},
start = {$escape['start']},
`end` = {$escape['end']},
comment = {$escape['comment']},    
regdate = now()     
SQL;
        $this->db->query($sql);
        if ($this->db->affected_rows()) {
            return true;
        }
        return false;
    }

}
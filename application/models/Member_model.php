<?php

/**
 * 회원
 * Class Member_model
 */
class Member_model extends CI_Model
{
	/**
	 * Member_model constructor.
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Total Count
	 * @param $param
	 * @return int
	 */
	public function total_rows($param = array())
	{
		$escape = $this->db->escape($param);
		$arr = array();
		if (!empty($param['name'])) {
			$arr[] = sprintf('name = %s', $escape['name']);
		}
		if (!empty($param['pos'])) {
			$arr[] = sprintf('pos = %s', $escape['pos']);
		}
		if (!empty($param['dept'])) {
			$arr[] = sprintf('dept = %s', $escape['dept']);
		}
		if (!empty($param['team'])) {
			$arr[] = sprintf('team = %s', $escape['team']);
		}
		if (!empty($param['part'])) {
			$arr[] = sprintf('part = %s', $escape['part']);
		}

		$where = '';
		if (count($arr) > 0) {
			$where = 'WHERE ' . join(' AND ', $arr);
		}

		$sql = <<<SQL
SELECT count(*) as count FROM member {$where}
SQL;
		$query = $this->db->query($sql);
		$count = $query->row_array();
		return $count['count'];
	}

	/**
	 * 삭제
	 * @param $param
	 * @return bool
	 */
	public function delete($param)
	{
		if (empty($param['name'])) {
			return false;
		}
		$escape = $this->db->escape($param);
		$sql = <<<SQL
DELETE FROM member WHERE name = {$escape['name']}
SQL;
		$this->db->query($sql);
		if ($this->db->affected_rows()) {
			return true;
		}
		return false;
	}

	/**
	 * 조회
	 * @param $param
	 * @return array()
	 */
	public function select($param = array())
	{

		$escape = $this->db->escape($param);
		$arr = array();
		if (!empty($param['name'])) {
			$arr[] = sprintf('name = %s', $escape['name']);
		}
		if (!empty($param['pos'])) {
			$arr[] = sprintf('pos = %s', $escape['pos']);
		}
		if (!empty($param['dept'])) {
			$arr[] = sprintf('dept = %s', $escape['dept']);
		}
		if (!empty($param['team'])) {
			$arr[] = sprintf('team = %s', $escape['team']);
		}
		if (!empty($param['part'])) {
			$arr[] = sprintf('part = %s', $escape['part']);
		}

		$where = '';
		if (count($arr) > 0) {
			$where = 'WHERE ' . join(' AND ', $arr);
		}

		$limit = '';
		if (isset($param['limit']) && isset($param['start'])) {
			$limit = sprintf('LIMIT %d, %d', $param['start'], $param['limit']);
		}

		$sql = <<<SQL
SELECT `name`, pos, dept, team, part 
FROM member
{$where}
{$limit}
SQL;
//        echo $sql;
		$query = $this->db->query($sql);
		return $query->result_array();
	}

	/**
	 * 팀 조회
	 * @param $param
	 * @return array()
	 */
	public function team()
	{
		$sql = <<<SQL
SELECT distinct(team) FROM member
SQL;
//        echo $sql;
		$query = $this->db->query($sql);
		$team = array();
		foreach ($query->result_array() as $row) {
			if (!empty($row['team'])) $team[] = $row['team'];
		}
		return $team;
	}

	/**
	 * 파트 조회
	 * @param $param
	 * @return array()
	 */
	public function part()
	{
		$sql = <<<SQL
SELECT distinct(part) FROM member
SQL;
//        echo $sql;
		$query = $this->db->query($sql);
		$part = array();
		foreach ($query->result_array() as $row) {
			if (!empty($row['part'])) $part[] = $row['part'];
		}
		return $part;
	}


	/**
	 * 회원 긁어오기
	 * @return int
	 */
	public function fetch()
	{
		$success = 0;
		$file_name = '/tmp/member.log';
		$handle = fopen($file_name, 'w');
		file_put_contents($file_name, strtotime("now"));
		fclose($handle);
		//테이블 만들기
		$this->create();
		//삭제
		$this->table_delete();

		$contents = file_get_contents('http://test.jasongroup.co.kr/main/jasonCafe.html', false, stream_context_create(array('http' => array(
			'method' => 'POST',
			'header' => 'Content-Type: application/x-www-form-urlencoded',
			'content' => array()
		))));

		$member = json_decode($contents, true);
		foreach ($member as $row) {
			if ($this->insert($row)) {
				$success++;
			}
		}
		return $success;
	}

	/**
	 * 테이블 생성
	 * @return bool
	 */
	public function create()
	{
		$sql = <<<SQL
CREATE TABLE `member` (
  `name` VARCHAR(50) NOT NULL,
  `pos` VARCHAR(50) NOT NULL,
  `dept` VARCHAR(50) NOT NULL,
  `team` VARCHAR(50) NULL DEFAULT '',
  `part` VARCHAR(50) NULL DEFAULT '',
  PRIMARY KEY (`name`),
  INDEX `dept` (`dept` ASC),
  INDEX `team` (`team` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
		if ($this->db->simple_query($sql)) {
			return true;
		}
		return false;
	}


	/**
	 * 테이블 삭제
	 * @return bool
	 */
	public function table_delete()
	{
		$sql = <<<SQL
DELETE FROM member
SQL;
		$this->db->query($sql);
		if ($this->db->affected_rows()) {
			return true;
		}
		return false;
	}

	/**
	 * 입력
	 * @param $param
	 * @return bool
	 */
	public function insert($param)
	{
		if (empty($param['name'])) {
			return false;
		}
		if (empty($param['pos'])) {
			return false;
		}
		if (empty($param['dept'])) {
			return false;
		}
		if (empty($param['team'])) {
			$param['team'] = '';
		}
		if (empty($param['part'])) {
			$param['part'] = '';
		}

		$check = $this->select(array('name' => $param['name']));

		if (count($check) > 0) {
			return false;
		}

		$escape = $this->db->escape($param);
		$sql = <<<SQL
INSERT INTO member SET 
name = {$escape['name']},
pos = {$escape['pos']},
dept = {$escape['dept']},
team = {$escape['team']},
part = {$escape['part']}     
SQL;
		$this->db->query($sql);
		if ($this->db->affected_rows()) {
			return true;
		}
		return false;
	}

}

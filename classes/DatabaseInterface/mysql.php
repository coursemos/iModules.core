<?php
/**
 * 이 파일은 모임즈툴즈의 일부입니다. (https://www.moimz.tools)
 *
 * MySQL 인터페이스를 정의한다.
 *
 * @file /classes/Databases/mysql.class.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @version 2.0.0
 * @modified 2021. 7. 22.
 */
class mysql extends DatabaseInterface {
	/**
	 * mysqli 객체
	 */
	private mysqli $_mysqli;
	
	/**
	 * MySQL 서버의 기본 캐릭터셋 및 엔진 설정
	 */
	private string $_charset = 'utf8mb4';
	private string $_collation = 'utf8mb4_unicode_ci';
	private string $_engine = 'InnoDatabase';
	
	private ?string $_query = null;
	private ?string $_lastQuery = null;
	
	/**
	 * 쿼리빌더 내부변수
	 */
	private bool $_is_builder_stated = false;
	private ?string $_startQuery = null;
	private ?string $_from_table = null;
	private ?DatabaseInterface $_from_query = null;
	private ?string $_from_alias = null;
	
	private array $_columns = [];
	private array $_join = [];
	private array $_where = [];
	private array $_having = [];
	private array $_orderBy = [];
	private array $_groupBy = [];
	private array $_limit = [];
	private string $_bindTypes = '';
	private array $_bindParams = [];
	private array $_tableDatas = [];
	private string $_tableLockMethod = 'READ';
	private int $_count = 0;
	
	/**
	 * MySQLi 클래스를 가져온다.
	 *
	 * @return mysqli $mysqli
	 */
	public function mysqli():mysqli {
		return $this->_mysqli;
	}
	
	/**
	 * 데이터베이스에 접속한다.
	 *
	 * @param object $connector 데이터베이스정보
	 * @return mysqli $mysqli mysqli 클래스객체
	 */
	public function connect(object $connector):mysqli {
		set_error_handler(null);
		mysqli_report(MYSQLI_REPORT_OFF);
		$this->_mysqli = new mysqli($connector->host,$connector->username,$connector->password,$connector->database,$connector->port);
		if ($this->_mysqli->connect_error) {
			ErrorHandler::print('DATABASE_CONNECT_ERROR','(HY000/'.$this->_mysqli->connect_errno.') '.$this->_mysqli->connect_error,$connector);
		}
		restore_error_handler();
		
		$this->_mysqli->set_charset($this->_charset);
		$this->_mysqli->report_mode = MYSQLI_REPORT_OFF;
		
		return $this->_mysqli;
	}
	
	/**
	 * 커넥션을 설정한다.
	 *
	 * @param mysqli $mysqli
	 * @return bool $success
	 */
	public function setConnection(&$mysqli):bool {
		// todo: MySQL 서버와 접속이 유지되어 있는지 확인
		$this->_mysqli = $mysqli;
		return true;
	}
	
	/**
	 * 데이터베이스에 접속이 가능한지 확인한다.
	 *
	 * @param object $connector 데이터베이스정보
	 * @return bool $success 접속성공여부
	 */
	public function checkConnector(object $connector):bool {
		if (isset($connector->port) == false) $connector->port = 3306;
		$mysqli = @new mysqli($connector->host,$connector->username,$connector->password,$connector->database,$connector->port);
		if ($mysqli->connect_errno) return false;
		if (isset($connector->charset) == true) $this->_charset = $connector->charset;
		if (isset($connector->collation) == true) $this->_collation = $connector->collation;
		
		$this->_mysqli->set_charset($this->_charset);
		return true;
	}
	
	/**
	 * 데이터베이스 서버에 ping 을 보낸다.
	 *
	 * @return bool $pong
	 */
	public function ping():bool {
		if (isset($this->_mysqli) == false) return false;
		return $this->_mysqli->ping();
	}
	
	/**
	 * 진행중인 쿼리빌더를 초기화한다.
	 */
	public function reset():void {
		$this->_is_builder_stated = false;
		$this->_startQuery = null;
		
		$this->_query = null;
		
		$this->_from_table = null;
		$this->_from_query = null;
		$this->_from_alias = null;
		
		$this->_columns = [];
		$this->_join = [];
		$this->_where = [];
		$this->_having = [];
		$this->_orderBy = [];
		$this->_groupBy = []; 
		$this->_limit = [];
		$this->_bindTypes = '';
		$this->_bindParams = [];
		$this->_tableDatas = [];
		$this->_tableLockMethod = 'READ';
		$this->_count = 0;
	}
	
	/**
	 * 데이터베이스의 전체 테이블목록을 가져온다.
	 *
	 * @param bool $include_desc 테이블구조 포함여부
	 * @return array $tables
	 */
	public function tables(bool $include_desc=false):array {
		return [];
	}
	
	/**
	 * 테이블명이 존재하는지 확인한다.
	 *
	 * @param string $table 테이블명
	 * @param bool $exists
	 */
	public function exists(string $table):bool {
		return true;
	}
	
	/**
	 * 테이블의 용량을 가져온다.
	 *
	 * @param string $table 테이블명
	 * @return int $size
	 */
	public function size(string $table):int {
		return 0;
	}
	
	/**
	 * 테이블의 구조를 가져온다.
	 *
	 * @param string $table 테이블명
	 * @return array $desc
	 */
	public function desc(string $table):array {
		return [];
	}
	
	/**
	 * 테이블의 구조를 비교한다.
	 *
	 * @param string $table 테이블명
	 * @param array $schema 테이블구조
	 * @return bool $is_coincidence
	 */
	public function compare(string $table,array $schema):bool {
		return true;
	}
	
	/**
	 * 테이블을 생성한다.
	 *
	 * @param string $table 테이블명
	 * @paran object $schema 테이블구조
	 * @return bool $success
	 */
	public function create(string $table,array $schema):bool {
		return true;
	}
	
	/**
	 * 테이블을 삭제한다.
	 *
	 * @param string $table 테이블명
	 * @return bool $success
	 */
	public function drop(string $table):bool {
		return true;
	}
	
	/**
	 * 테이블을 비운다.
	 *
	 * @param string $table 테이블명
	 * @return bool $success
	 */
	public function truncate(string $table):bool {
		return true;
	}
	
	/**
	 * 테이블의 이름을 변경한다.
	 *
	 * @param string $table 변경전 테이블명
	 * @param string $newname 변경할 테이블명
	 * @return bool $success
	 */
	public function rename(string $table,string $newname):bool {
		return true;
	}
	
	/**
	 * 백업테이블을 생성한다.
	 *
	 * @param string $table 백업할 테이블명
	 * @return bool $success
	 */
	public function backup(string $table):bool {
		return true;
	}
	
	/**
	 * 컬럼을 추가한다.
	 *
	 * @param string $table 컬럼을 추가할 테이블명
	 * @param object $column 추가할컬럼구조
	 * @param ?string $after 컬럼을 추가할 위치
	 * @return bool $success
	 */
	public function alterAdd(string $table,object $column,?string $after=null):bool {
		return true;
	}
	
	/**
	 * 컬럼을 수정한다.
	 *
	 * @param string $table 컬럼을 변경할 테이블명
	 * @param string $target 변경할 컬럼명
	 * @param object $column 변경할 컬럼구조
	 * @return bool $success
	 */
	public function alterChange(string $table,string $target,object $column):bool {
		return true;
	}
	
	/**
	 * 컬럼을 삭제한다.
	 *
	 * @param string $table 컬럼을 삭제할 테이블명
	 * @param string $target 삭제할 컬럼명
	 * @return bool $success
	 */
	public function alterDrop(string $table,string $target):bool {
		return true;
	}
	
	/**
	 * LOCK 방법을 설정한다.
	 *
	 * @param string $method LOCK METHOD (READ, WRITE)
	 * @return DatabaseInterface $this
	 */
	public function setLockMethod(string $method):DatabaseInterface {
		switch(strtoupper($method)) {
			case 'READ' || 'WRITE' :
				$this->_tableLockMethod = $method;
				break;
			default:
				$this->_error('Bad lock type: Can be either READ or WRITE');
				break;
		}
		return $this;
	}
	
	/**
	 * 단일 테이블을 설정된 LOCK 방법에 따라 LOCK 한다.
	 *
	 * @param string $table LOCK할 테이블
	 * @param ?string $method LOCK METHOD (READ, WRITE)
	 * @return bool $success
	 */
	public function lock(string $table,?string $method=null):bool {
		return $this->locks([$table],$method);
	}
	
	/**
	 * 복수 테이블을 설정된 LOCK 방법에 따라 LOCK 한다.
	 *
	 * @param array $tables LOCK할 테이블
	 * @param ?string $method LOCK METHOD (READ, WRITE)
	 * @return bool $success
	 */
	public function locks(array $tables,?string $method=null):bool {
		return true;
	}
	
	/**
	 * 현재 LOCK 중인 테이블을 UNLOCK 한다.
	 *
	 * @return bool $success
	 */
	public function unlock():bool {
		return true;
	}
	
	/**
	 * 쿼리빌더 없이 RAW 쿼리를 실행한다.
	 *
	 * @param string $query 쿼리문
	 * @param array $bindParams 바인딩할 변수
	 * @return DatabaseInterface $this
	 */
	function query(string $query,?array $bindParams=null):DatabaseInterface {
		$this->_start('RAW');
		$this->_query = trim($query);
		if ($bindParams !== null) {
			$this->_bindParams($bindParams);
		}
		
		return $this;
	}
	
	/**
	 * SELECT 쿼리빌더를 시작한다.
	 *
	 * @param array $columns 가져올 컬럼명
	 * @return DatabaseInterface $this
	 */
	public function select(array $columns=[]):DatabaseInterface {
		$this->_start('SELECT');
		
		$this->_columns = $columns;
		$this->_query = 'SELECT ';
		
		return $this;
	}
	
	/**
	 * INSERT 쿼리빌더를 시작한다.
	 *
	 * @param string $table 테이블명
	 * @param array $data 저장할 데이터 ([컬럼명=>값])
	 * @return DatabaseInterface $this
	 */
	public function insert(string $table,array $data):DatabaseInterface {
		$this->_start('INSERT');
		
		$this->_query = 'INSERT INTO '.$table;
		$this->_tableDatas = $data;
		
		return $this;
	}
	
	/**
	 * REPLACE 쿼리빌더를 시작한다.
	 *
	 * @param string $table 테이블명
	 * @param array $data 저장할 데이터 ([컬럼명=>값])
	 * @return DatabaseInterface $this
	 */
	public function replace(string $table,array $data):DatabaseInterface {
		$this->_start('REPLACE');
		
		$this->_query = 'REPLACE INTO '.$table;
		$this->_tableDatas = $data;
		
		return $this;
	}
	
	/**
	 * UPDATE 쿼리빌더를 시작한다.
	 *
	 * @param string $table 테이블명
	 * @param array $data 변경할 데이터 ([컬럼명=>값])
	 * @return DatabaseInterface $this
	 */
	public function update(string $table,array $data):DatabaseInterface {
		$this->_start('UPDATE');
		
		$this->_query = 'UPDATE '.$table.' SET ';
		$this->_tableDatas = $data;
		
		return $this;
	}
	
	/**
	 * DELETE 쿼리빌더를 시작한다.
	 *
	 * @param string $table 테이블명
	 * @return DatabaseInterface $this
	 */
	public function delete(string $table):DatabaseInterface {
		$this->_start('DELETE');
		
		$this->_query = 'DELETE FROM '.$table;
		
		return $this;
	}
	
	/**
	 * FROM 절을 정의한다.
	 *
	 * @param string $table 테이블명
	 * @param ?string $alias 테이블별칭
	 */
	public function from(string $table,?string $alias=null):DatabaseInterface {
		$this->_from_table = $table;
		$this->_from_alias = $alias;
		
		return $this;
	}
	
	/**
	 * WHERE 절을 정의한다. (AND조건)
	 *
	 * @param string $whereProp WHERE 조건절 (컬럼명 또는 WHERE 조건문)
	 * @param mixed $whereValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
	 * @param ?string $operator 조건 (=, IN, NOT IN, LIKE 등)
	 * @return DatabaseInterface $this
	 */
	public function where(string $whereProp,$whereValue=null,?string $operator=null):DatabaseInterface {
		if ($operator) $whereValue = [$operator=>$whereValue];
		$this->_where[] = ['AND',$whereValue,$whereProp];
		
		return $this;
	}
	
	/**
	 * WHERE 절을 정의한다. (OR조건)
	 *
	 * @param string $whereProp WHERE 조건절 (컬럼명 또는 WHERE 조건문)
	 * @param mixed $whereValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
	 * @param ?string $operator 조건 (=, IN, NOT IN, LIKE 등)
	 * @return DatabaseInterface $this
	 */
	public function orWhere(string $whereProp,$whereValue=null,?string $operator=null):DatabaseInterface {
		if ($operator) $whereValue = [$operator=>$whereValue];
		$this->_where[] = ['OR',$whereValue,$whereProp];
		
		return $this;
	}
	
	/**
	 * HAVING 절을 정의한다. (AND조건)
	 *
	 * @param string $havingProp HAVING 조건절 (컬럼명 또는 WHERE 조건문)
	 * @param mixed $havingValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
	 * @param ?string $operator 조건 (=, IN, NOT IN, LIKE 등)
	 * @return DatabaseInterface $this
	 */
	public function having($havingProp,$havingValue=null,?string $operator=null):DatabaseInterface {
		if ($operator) $havingValue = [$operator=>$havingValue];
		$this->_having[] = ['AND',$havingValue,$havingProp];
		
		return $this;
	}
	
	/**
	 * HAVING 절을 정의한다. (OR조건)
	 *
	 * @param string $havingProp HAVING 조건절 (컬럼명 또는 WHERE 조건문)
	 * @param mixed $havingValue 검색할 조건값 (컬럼데이터 또는 WHERE 조건문에 바인딩할 값의 배열)
	 * @param ?string $operator 조건 (=, IN, NOT IN, LIKE 등)
	 * @return DatabaseInterface $this
	 */
	public function orHaving(string $havingProp,$havingValue=null,?string $operator=null):DatabaseInterface {
		if ($operator) $havingValue = [$operator=>$havingValue];
		$this->_having[] = ['OR',$havingValue,$havingProp];
		
		return $this;
	}
	
	/**
	 * JOIN 절을 정의한다. (AND조건)
	 *
	 * @param string $joinTable JOIN 할 prefix 가 포함되지 않은 테이블명
	 * @param string $joinCondition JOIN 조건
	 * @param string $joinType 조인형태 (LEFT, RIGHT, OUTER, INNER, LEFT OUTER, RIGHT OUTER)
	 * @return DatabaseInterface $this
	 */
	public function join(string $joinTable,string $joinCondition,string $joinType=''):DatabaseInterface {
		$allowedTypes = ['LEFT','RIGHT','OUTER','INNER','LEFT OUTER','RIGHT OUTER'];
		$joinType = strtoupper(trim($joinType));
		if ($joinType && in_array($joinType,$allowedTypes) == false) {
			$this->_error('Wrong JOIN type: '.$joinType);
		}
		
		$this->_join[] = [$joinType,$joinTable,$joinCondition];
		
		return $this;
	}
	
	/**
	 * ORDER 절을 정의한다.
	 *
	 * @param string $orderByField 정렬할 필드명
	 * @param string $orderbyDirection 정렬순서 (ASC, DESC)
	 * @param ?array $customFields 커스덤필드배열 (테이블에 정의된 컬럼이 아닌 경우)
	 * @return DatabaseInterface $this
	 */
	public function orderBy(string $orderByField,string $orderbyDirection='DESC',?array $customFields=null):DatabaseInterface {
		$allowedDirection = ['ASC','DESC'];
		$orderbyDirection = strtoupper(trim($orderbyDirection));
		$orderByField = preg_replace('/[^-a-z0-9\.\(\),_\* <>=!"\']+/i','',$orderByField);
		if (empty($orderbyDirection) == true || in_array($orderbyDirection,$allowedDirection) == false) $this->_error('Wrong order direction: '.$orderbyDirection);
		
		if ($customFields !== null) {
			foreach ($customFields as $key=>$value) {
				$customFields[$key] = preg_replace('/[^-a-z0-9\.\(\),_\* <>=!"\']+/i','',$value);
			}
			$orderByField = 'FIELD ('.$orderByField.',"'.implode('","',$customFields).'")';
		}
		$this->_orderBy[$orderByField] = $orderbyDirection;
		
		return $this;
	}
	
	/**
	 * GROUP 절을 정의한다.
	 *
	 * @param string $groupByField GROUP 할 컬럼명
	 * @return DatabaseInterface $this
	 */
	public function groupBy(string $groupByField):DatabaseInterface {
		$groupByField = preg_replace('/[^-a-z0-9\.\(\),_]+/i','',$groupByField);
		$this->_groupBy[] = $groupByField;
		
		return $this;
	}
	
	/**
	 * LIMIT 절을 정의한다.
	 *
	 * @param int $start 시작점
	 * @param ?int $limit 가져올 갯수 ($limit 이 정의되지 않은 경우, 0번째 부터 $start 갯수만큼 가져온다.)
	 * @return DatabaseInterface $this
	 */
	public function limit(int $start,?int $limit=null):DatabaseInterface {
		if ($limit !== null) {
			$this->_limit = [$start,$limit];
		} else {
			$this->_limit = [0,$start];
		}
		return $this;
	}
	
	/**
	 * 쿼리를 실행한다.
	 *
	 * @return object $results 실행결과
	 */
	public function execute():object {
		if ($this->_is_builder_stated === false) {
			$this->_error('No execute query');
		}
		
		echo PHP_EOL.PHP_EOL.$this->_query.PHP_EOL;
		
		$this->_buildQuery();
		$results = $this->_execute();
		
		$this->_end();
		
		var_dump($results);
		
		return $results;
	}
	
	/**
	 * SELECT 쿼리문에 의해 선택된 데이터의 갯수를 가져온다.
	 *
	 * @return int $count
	 */
	public function count():int {
		if ($this->_is_builder_stated === false) {
			$this->_error('No execute query');
		}
		
		/**
		 * GROUP 절을 사용하지 않았을 경우 COUNT(*) 함수를 사용하여 갯수만 가져온다.
		 * GROUP 절이 있는 경우, 쿼리를 실행시킨 뒤 갯수를 가져온다.
		 */
		if (count($this->_groupBy) == 0) {
			$this->_columns = ['COUNT(*) AS ROW_COUNT'];
			$this->_buildQuery();
			$results = $this->_execute();
			$this->_end();
			
			return count($results->datas) == 1 && isset($results->datas[0]->ROW_COUNT) == true ? $results->datas[0]->ROW_COUNT : 0;
		} else {
			$this->_buildQuery();
			$results = $this->_execute(false);
			$this->_end();
			
			return $results->num_rows;
		}
	}
	
	/**
	 * SELECT 쿼리문에 의해 선택된 데이터가 존재하는지 확인한다.
	 *
	 * @return boolean $has
	 */
	public function has():bool {
		return $this->count() > 0;
	}
	
	/**
	 * SELECT 쿼리문에 의해 선택된 데이터를 가져온다.
	 *
	 * @param ?string $field 필드명 (필드명을 지정할 경우, 컬럼명->컬럼값이 아닌 해당 필드명의 값만 배열로 반환한다.)
	 * @return array $items
	 */
	public function get(?string $field=null):array {
		if ($this->_is_builder_stated === false) {
			$this->_error('No execute query');
		}
		
		$this->_buildQuery();
		$results = $this->_execute();
		$this->_end();
		
		$datas = $results->datas;
		if ($field !== null) {
			array_walk($datas,function(&$item,$key,$field) {
				$item = isset($item->{$field}) == true ? $item->{$field} : null;
			},$field);
		} else {
			$datas = $results->datas;
		}
		
		return $datas;
	}
	
	/**
	 * SELECT 쿼리문에 의해 선택된 데이터중 한개만 가져온다.
	 *
	 * @param ?string $field 필드명 (필드명을 지정할 경우, 컬럼명->컬럼값이 아닌 해당 필드명의 값만 반환한다.)
	 * @return mixed $item
	 */
	public function getOne(?string $field=null):mixed {
		$result = $this->get($field);
		
		$item = null;
		if (is_object($result) == true) $item = $result;
		if (isset($result[0]) == true) $item = $result[0];
		
		if ($field != null) {
			return $item != null && isset($item->{$field}) == true ? $item->{$field} : null;
		} else {
			return $item;
		}
	}
	
	/**
	 * 현재까지 쿼리빌더에 의해 생성된 쿼리를 복제한다.
	 *
	 * @return DatabaseInterface $copy 복제된 쿼리빌더 클래스
	 */
	public function copy():DatabaseInterface {
		$copy = unserialize(serialize($this));
		$copy->_mysqli = $this->_mysqli;
		return $copy;
	}
	
	/**
	 * 마지막에 실행된 쿼리문을 가져온다.
	 *
	 * @return string $query
	 */
	public function getLastQuery():?string {
		return $this->_lastQuery;
	}
	
	/**
	 * escape 한 문자열을 가져온다.
	 * 예 : iModule's class -> iModule\'s class
	 *
	 * @param string $str
	 * @return string $escaped_str
	 */
	public function escape($str) {
		return $this->_mysqli->real_escape_string($str);
	}
	
	/**
	 * 쿼리빌더를 시작한다.
	 *
	 * @param ?string $startQuery 시작쿼리
	 */
	private function _start(?string $startQuery):void {
		if ($this->_is_builder_stated === true) {
			$this->_error('Previous Query is not finished.');
		} else {
			$this->_startQuery = $startQuery;
			$this->_is_builder_stated = true;
		}
	}
	
	/**
	 * 쿼리빌더를 종료한다.
	 */
	private function _end():void {
		$this->reset();
	}
	
	/**
	 * 쿼리빌더로 정의된 설정값을 이용하여 실제 쿼리문을 생성한다.
	 */
	private function _buildQuery():void {
		$this->_buildColumn();
		$this->_buildFrom();
		
//		$this->_buildTableData();
//		$this->_buildJoin();
//		if (empty($this->_tableDatas) == false) $this->_buildTableData($this->_tableDatas);
		$this->_buildWhere();
//		$this->_buildGroupBy();
//		$this->_buildHaving();
		$this->_buildOrderBy();
		$this->_buildLimit();
		$this->_lastQuery = $this->_replacePlaceHolders($this->_query,$this->_bindParams);
	}
	
	/**
	 * 바인딩되는 데이터를 추가하고 prepared 쿼리의 대치문자열을 반환한다.
	 *
	 * @param string $operator
	 * @param mixed $value
	 * @return string $query
	 */
	private function _buildPair(string $operator,$value):string {
		if (is_object($value) == true) return $this->_error('OBJECT_PAIR');
		$this->_bindParam($value);
		return ' '.$operator.' ? ';
	}
	
	/**
	 * SELECT 절의 컬럼을 생성한다.
	 */
	private function _buildColumn():void {
		if ($this->_startQuery !== 'SELECT') return;
		if (count($this->_columns) == 0) {
			$this->_query.= '*';
		} else {
			// todo: 서브쿼리
			$this->_query.= implode(', ',$this->_columns);
		}
	}
	
	/**
	 * FROM 절을 생성한다.
	 */
	private function _buildFrom():void {
		if ($this->_startQuery === null) return;
		
		if ($this->_from_table !== null) {
			$this->_query.= ' FROM `'.$this->_from_table.'` ';
		} elseif ($this->_from_query !== null) {
			// todo: 서브쿼리
		}
		
		if ($this->_from_alias !== null) {
			$this->_query.= $this->_from_alias.' ';
		}
	}
	
	/**
	 * WHERE 절을 생성한다.
	 */
	private function _buildWhere():void {
		if (empty($this->_where) == true) return;
		$this->_query.= ' WHERE ';
		$this->_where[0][0] = '';
		
		foreach ($this->_where as $index=>&$cond) {
			list($concat,$wValue,$wKey) = $cond;
			
			if ($wKey == '(') {
				$this->_query.= ' '.$concat.' ';
				if (isset($this->_where[$index+1]) == true) $this->_where[$index+1][0] = '';
			} elseif ($wKey != ')') {
				$this->_query.= ' '.$concat.' ';
			}
			if (is_array($wValue) == false || (strtolower(key($wValue)) != 'inset' && strtolower(key($wValue)) != 'fulltext')) $this->_query.= $wKey;
			
			if ($wValue === null) continue;
			
			if (is_array($wValue) == false) $wValue = ['='=>$wValue];
			
			$key = key($wValue);
			$val = $wValue[$key];
			switch (strtolower($key)) {
				case '0':
					$this->_bindParams($wValue);
					break;
				case 'not in':
				case 'in':
					$comparison = ' '.$key.' (';
					if (is_object($val) == true) {
						$comparison.= $this->_buildPair('',$val);
					} else {
						foreach ($val as $v) {
							$comparison.= ' ?,';
							$this->_bindParam($v);
						}
					}
					$this->_query.= rtrim($comparison,',').' ) ';
					break;
				case 'inset' :
					$comparison = ' FIND_IN_SET (?,'.$wKey.')';
					$this->_bindParam($val);
					
					$this->_query.= $comparison;
					break;
				case 'is not':
					$this->_query.= ' IS NOT NULL';
					break;
				case 'is':
					$this->_query.= ' IS NULL';
					break;
				case 'not between':
				case 'between':
					$this->_query.= " $key ? AND ? ";
					$this->_bindParams($val);
					break;
				case 'not exists':
				case 'exists':
					$this->_query.= $key.$this->_buildPair('',$val);
					break;
				case 'not like':
				case 'like':
					$this->_query .= " $key ? ";
					$this->_bindParam($val);
					break;
				case 'fulltext':
					$comparison = ' MATCH ('.$wKey.') AGAINST (? IN BOOLEAN MODE)';
					
					$keylist = explode(' ',$val);
					for ($i=0, $loop=count($keylist);$i<$loop;$i++) {
						$keylist[$i] = '\'+'.$keylist[$i].'*\'';
					}
					$keylist = implode(' ',$keylist);
					
					$this->_bindParam($keylist);
					$this->_query.= $comparison;
					
					break;
				default:
					$this->_query.= $this->_buildPair($key,$val);
			}
		}
	}
	
	/**
	 * ORDER 절을 생성한다.
	 */
	private function _buildOrderBy() {
		if (empty($this->_orderBy) == true) return;
		
		$this->_query.= ' ORDER BY ';
		foreach ($this->_orderBy as $prop=>$value) {
			if (strtolower(str_replace(' ','',$prop)) == 'rand()') $this->_query.= 'rand(),';
			else $this->_query.= $prop.' '.$value.',';
		}
		$this->_query = rtrim($this->_query,',').' ';
	}
	
	/**
	 * LIMIT 절을 생성한다.
	 */
	private function _buildLimit():void {
		if (empty($this->_limit) == true) return;
		
		$this->_query.= ' LIMIT '.$this->_limit[0].','.$this->_limit[1];
	}
	
	/**
	 * 바인딩되는 변수의 타입을 반환한다.
	 *
	 * @param mixed $item 변수형태를 파악하기 위한 변수
	 * @return string $type
	 */
	private function _determineType($item):string {
		switch (gettype($item)) {
			case 'NULL' :
			case 'string' :
				return 's';
				
			case 'boolean' :
			case 'integer' :
				return 'i';
				
			case 'blob' :
				return 'b';
				
			case 'double' :
				return 'd';
				
			default :
				return '';
		}
	}
	
	/**
	 * 바인딩 데이터를 처리한다.
	 *
	 * @param mixed $value
	 */
	private function _bindParam($value):void {
		$this->_bindTypes.= $this->_determineType($value);
		array_push($this->_bindParams,$value);
	}
	
	/**
	 * 바인딩 데이터를 처리한다.
	 */
	private function _bindParams(array $values):void {
		foreach ($values as $value) {
			$this->_bindParam($value);
		}
	}
	
	/**
	 * 쿼리문을 실행한다.
	 *
	 * @param bool $is_datas 쿼리실행결과 데이터를 반환할지 여부(기본값 : true)
	 * @return object $results 실행결과
	 */
	private function _execute(bool $is_datas=true):object {
		$results = new stdClass();
		$results->success = false;
		$results->affected_rows = 0;
		$results->insert_id = 0;
		$results->num_rows = 0;
		$results->datas = [];
		
		$stmt = $this->_mysqli->prepare($this->_query);
		if (!$stmt) {
			$this->_error($this->_mysqli->error);
			return $results;
		}
		
		if (count($this->_bindParams) > 0) {
			$params = [$this->_bindTypes];
			foreach ($this->_bindParams as &$value) {
				$params[] = &$value;
			}
			$stmt->bind_param(...$params);
			//call_user_func_array([$stmt,'bind_param'],$params);
		}
		
		$success = $stmt->execute();
		if ($success === false) {
			$this->_error($stmt->error);
			return $results;
		}
		
		$stmt->store_result();
		$results->success = $stmt->sqlstate === '00000';
		$results->affected_rows = $stmt->affected_rows;
		$results->insert_id = $stmt->insert_id;
		$results->num_rows = $stmt->num_rows;
		
		/**
		 * 쿼리실행결과 데이터가 있는 경우
		 */
		if ($is_datas == true) {
			$metadata = $stmt->result_metadata();
			if ($metadata !== false) {
				$results->datas = $this->_dynamicBindResults($stmt,$metadata);
			}
		}
		
		$stmt->free_result();
		
		return $results;
	}
	
	/**
	 * 쿼리에러를 처리한다.
	 *
	 * @param string $message 에러메시지
	 */
	private function _error(string $message):void {
		$details = new stdClass();
		$details->error = $this->_mysqli->error;
		$details->query = $this->_query;
		
		$this->reset();
		ErrorHandler::print('DATABASE_ERROR',$message,$details);
	}
	
	/**
	 * 바인딩 된 변수를 쿼리에서 치환한다.
	 *
	 * @param string $query 쿼리
	 * @return array $values 바인딩되는 데이터
	 */
	private function _replacePlaceHolders(string $query,array $values):string {
		$i = 0;
		$replaced = '';
		while ($position = strpos($query,'?')) {
			$value = $values[$i++];
			if (is_object($value) == true) $value = '[object]';
			if (is_numeric($value) == false) $value = '\''.$value.'\'';
			$replaced.= substr($query,0,$position).$value;
			$query = substr($query,$position + 1);
		}
		$replaced.= $query;
		return $replaced;
	}
	
	/**
	 * 쿼리 실행결과 반환된 결과값을 정리한다.
	 *
	 * @param mysqli_stmt $stmt
	 * @param mysqli_result $metadata
	 * @return array $results
	 */
	private function _dynamicBindResults(mysqli_stmt &$stmt,mysqli_result &$metadata):array {
		$parameters = [];
		$results = [];
		$row = [];
		while ($field = $metadata->fetch_field()) {
			$row[$field->name] = null;
			$parameters[] = &$row[$field->name];
		}
		$stmt->store_result();
		$stmt->bind_result(...$parameters);
		//call_user_func_array([$stmt,'bind_result'],$parameters);
		
		$this->_count = 0;
		while ($stmt->fetch()) {
			$result = [];
			foreach ($row as $key=>$val) {
				$result[$key] = isset($val) == false || $val === null ? '' : $val;
			}
			array_push($results,(object)$result);
			$this->_count++;
		}
		$stmt->free_result();
		
		return $results;
	}
}
?>
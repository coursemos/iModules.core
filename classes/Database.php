<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터베이스 클래스를 정의한다.
 *
 * @file /classes/Database.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 1. 19.
 */
class Database {
	/**
	 * 데이터베이스 커넥션 정보
	 */
	private array $_connections = [];
	
	/**
	 * 데이터베이스 인터페이스 정보
	 */
	private array $_interfaces = [];
	
	/**
	 * 싱글톤 방식으로 데이터베이스클래스를 선언한다.
	 */
	private static Database $_instance;
	public static function &getInstance():Database {
		if (empty(self::$_instance) == true) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * 데이터베이스 인터페이스 클래스를 가져온다.
	 *
	 * @param string $name 커넥션명
	 * @param object $connector 데이터베이스정보
	 * @return DatabaseInterface $interface
	 */
	public function getInterface(string $name,object $connector):DatabaseInterface {
		if (isset($this->_interfaces[$name]) == true) return $this->_interfaces[$name];
		
		/**
		 * 데이터베이스 정보를 이용하여 데이터베이스 서버 고유값을 구한다.
		 */
		$connection = sha1($connector->type.$connector->host.$connector->database);
		$interface = null;
		
		if ($connector->type == 'mysql') {
			$interface = new \Databases\mysql($this);
		}
		
		if ($interface === null) {
			ErrorHandler::view('DATABASE_CONNECT_ERROR',$connector->type.' is not supported.');
		}
		
		/**
		 * 이미 데이터베이스 커넥션정보가 있다면 해당 커넥션을 이용하고, 그렇지 않은 경우 커넥션을 생성한다.
		 */
		if (isset($this->_connections[$connection]) == true) {
			$interface->setConnection($this->_connections[$connection]);
		} else {
			$this->_connections[$connection] = $interface->connect($connector);
		}
		
		$this->_interfaces[$name] = $interface;
		
		return $this->_interfaces[$name];
	}
}
?>
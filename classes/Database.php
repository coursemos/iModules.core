<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터베이스 클래스를 정의한다.
 *
 * @file /classes/Database.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 9.
 */
class Database {
	/**
	 * 데이터베이스 커넥션 정보
	 */
	private static array $_connections = [];
	
	/**
	 * 데이터베이스 인터페이스 정보
	 */
	private static array $_interfaces = [];
	
	/**
	 * 데이터베이스 인터페이스 클래스를 가져온다.
	 *
	 * @param string $name 커넥션명
	 * @param object $connector 데이터베이스정보
	 * @return DatabaseInterface $interface
	 */
	public static function getInterface(string $name,object $connector):DatabaseInterface {
		if (isset(self::$_interfaces[$name]) == true) return self::$_interfaces[$name];
		
		/**
		 * 데이터베이스 정보를 이용하여 데이터베이스 서버 고유값을 구한다.
		 */
		$connection = sha1($connector->type.$connector->host.$connector->database);
		$interface = null;
		
		if ($connector->type == 'mysql') {
			$interface = new \Databases\mysql();
		}
		
		if ($interface === null) {
			ErrorHandler::view('DATABASE_CONNECT_ERROR',$connector->type.' is not supported.');
		}
		
		/**
		 * 이미 데이터베이스 커넥션정보가 있다면 해당 커넥션을 이용하고, 그렇지 않은 경우 커넥션을 생성한다.
		 */
		if (isset(self::$_connections[$connection]) == true) {
			$interface->setConnection(self::$_connections[$connection]);
		} else {
			self::$_connections[$connection] = $interface->connect($connector);
		}
		
		self::$_interfaces[$name] = $interface;
		
		return self::$_interfaces[$name];
	}
}
?>
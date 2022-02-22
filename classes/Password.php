<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 패스워드를 저장하기 위한 SALT 기반 해시를 생성하거나, 패스워드를 검증한다.
 *
 * @file /classes/Password.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 17.
 */
class Password {
	private static string $_algo = 'sha256';
	private static int $_iterations = 12000;
	private static int $_salt_bytes = 24;
	private static int $_hash_bytes = 24;
	
	/**
	 * 패스워드 HASH 를 생성한다.
	 *
	 * @param string $password 패스워드
	 * @return string $hash 해시
	 */
	public static function hash(string $password):string {
		$salt = '';
		for ($i=0;$i<self::$_salt_bytes;$i+=2) {
			$salt.= pack('S',mt_rand(0,65535));
		}
		$salt = base64_encode(substr($salt,0,self::$_salt_bytes));
		
		return $salt.':'.base64_encode(hash_pbkdf2(self::$_algo,$password,$salt,self::$_iterations,self::$_hash_bytes,true));
	}
	
	/**
	 * 패스워드를 검증한다.
	 *
	 * @param string $password 패스워드
	 * @param string $hash 해시
	 * @return bool $success 검증여부
	 */
	public static function verify(string $password,string $hash):bool {
		$params = explode(':',$hash);
		if (count($params) < 2) return false;
		
		$pbkdf2 = base64_decode($params[1]);
		$pbkdf2_check = hash_pbkdf2(self::$_algo,$password,$params[0],self::$_iterations,self::$_hash_bytes,true);
		
		return self::slow_equals($pbkdf2,$pbkdf2_check);
	}

	/**
	 * 두개의 HASH 가 일치하는지 검증한다.
	 *
	 * @param string $hash
	 * @param string $compare
	 * @return bool $success 일치여부
	 */
	private static function slow_equals(string $hash,string $compare):bool {
		$diff = strlen($hash) ^ strlen($compare);
		for($i =0;$i<strlen($hash) && $i<strlen($compare);$i++) {
			$diff |= ord($hash[$i]) ^ ord($compare[$i]);
		}
		return $diff === 0; 
	}
}
?>
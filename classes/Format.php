<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터의 형식을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Format.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 10. 5.
 */
class Format {
	/**
	 * 문자열을 종류에 따라 변환한다.
	 *
	 * @param ?string $str 변환할 문자열
	 * @param string $code 변환종류
	 * @return string $str 변환된 문자열
	 */
	public static function string(?string $str,string $code):string {
		$str ??= '';
		switch ($code) {
			/**
			 * input 태그에 들어갈 수 있도록 <, >, " 문자열을 HTML 엔티티 문자열로 변환하고 ' 에 \ 를 추가한다.
			 */
			case 'input' :
				$str = str_replace('<','&lt;',$str);
				$str = str_replace('>','&gt;',$str);
				$str = str_replace('"','&quot;',$str);
				$str = str_replace("'",'\'',$str);
				
				break;
			
			/**
			 * HTML 태그를 HTML 엔티티 문자열로 변환한다.
			 */
			case 'replace' :
				$str = str_replace('<','&lt;',$str);
				$str = str_replace('>','&gt;',$str);
				$str = str_replace('"','&quot;',$str);
				
				break;
			
			/**
			 * XML 태그에 들어갈 수 있도록 &, <, >, ", ' 문자열을 HTML 엔티티 문자열로 변환한다.
			 */
			case 'xml' :
				$str = str_replace('&','&amp;',$str);
				$str = str_replace('<','&lt;',$str);
				$str = str_replace('>','&gt;',$str);
				$str = str_replace('"','&quot;',$str);
				$str = str_replace("'",'&apos;',$str);
				
				break;
			
			/**
			 * 가장 일반적인 HTML 태그를 제외한 나머지 태그를 제거한다.
			 */
			case 'default' :
				$allow = '<p>,<br>,<b>,<span>,<a>,<img>,<embed>,<i>,<u>,<strike>,<font>,<center>,<ol>,<li>,<ul>,<strong>,<em>,<div>,<table>,<tr>,<td>';
				$str = strip_tags($str, $allow);
				
				break;
	
			/**
			 * \ 및 태그, HTML 엔티티를 제거한다.
			 */
			case 'delete' :
				$str = stripslashes($str);
				$str = strip_tags($str);
				$str = str_replace('&nbsp;','',$str);
				$str = str_replace('"','&quot;',$str);
				
				break;
	
			/**
			 * URL을 인코딩한다.
			 */
			case 'encode' :
				$str = urlencode($str);
				
				break;
			
			/**
			 * 정규식에 들어갈 수 있도록 정규식에 사용되는 문자열을 치환한다.
			 */
			case 'reg' :
				$str = str_replace('\\','\\\\',$str);
				$str = str_replace('[','\[',$str);
				$str = str_replace(']','\]',$str);
				$str = str_replace('(','\(',$str);
				$str = str_replace(')','\)',$str);
				$str = str_replace('?','\?',$str);
				$str = str_replace('.','\.',$str);
				$str = str_replace('*','\*',$str);
				$str = str_replace('-','\-',$str);
				$str = str_replace('+','\+',$str);
				$str = str_replace('^','\^',$str);
				$str = str_replace('$','\$',$str);
				$str = str_replace('/','\/',$str);
				
				break;
			
			/**
			 * 데이터베이스 인덱스에 사용할 수 있게 HTML태그 및 HTML엔티티, 그리고 불필요한 공백문자를 제거한다.
			 */
			case 'index' :
				$str = preg_replace('/<(P|p)>/','',$str);
				$str = preg_replace('/<\/(P|p)>/',"\n",$str);
				$str = preg_replace('/<br(.*?)>/',"\n",$str);
				$str = preg_replace('/\r\n/',"\n",$str);
				$str = preg_replace('/[\n]+/',"\n",$str);
				$str = strip_tags($str);
				$str = preg_replace('/&[a-z]+;/',' ',$str);
				$str = str_replace("\t",' ',$str);
				$str = preg_replace('/[ ]+/',' ',$str);
				
				break;
		}
		
		return trim($str);
	}
	
	/**
	 * UNIXTIMESTAMP 를 주어진 포맷에 따라 변환한다.
	 *
	 * @param string $format 변환할 포맷 (@see http://php.net/manual/en/function.date.php)
	 * @param int $time UNIXTIMESTAMP (없을 경우 현재시각)
	 * @param bool $is_moment momentjs 용 태그를 생성할 지 여부 (@see http://momentjs.com)
	 * @return string $time 변환된 시각
	 */
	public static function time(string $format,?int $time=null,bool $is_moment=true):string {
		$time = $time === null ? time() : $time;
		
		/**
		 * PHP date 함수 포맷텍스트를 momentjs 포맷텍스트로 치환하기 위한 배열정의
		 */
		$replacements = array(
			'd' => 'DD',
			'D' => 'ddd',
			'j' => 'D',
			'l' => 'dddd',
			'N' => 'E',
			'S' => 'o',
			'w' => 'e',
			'z' => 'DDD',
			'W' => 'W',
			'F' => 'MMMM',
			'm' => 'MM',
			'M' => 'MMM',
			'n' => 'M',
			't' => '', // no equivalent
			'L' => '', // no equivalent
			'o' => 'YYYY',
			'Y' => 'YYYY',
			'y' => 'YY',
			'a' => 'a',
			'A' => 'A',
			'B' => '', // no equivalent
			'g' => 'h',
			'G' => 'H',
			'h' => 'hh',
			'H' => 'HH',
			'i' => 'mm',
			's' => 'ss',
			'u' => 'SSS',
			'e' => 'zz', // deprecated since version 1.6.0 of moment.js
			'I' => '', // no equivalent
			'O' => '', // no equivalent
			'P' => '', // no equivalent
			'T' => '', // no equivalent
			'Z' => '', // no equivalent
			'c' => '', // no equivalent
			'r' => '', // no equivalent
			'U' => 'X'
		);
		$momentFormat = strtr($format,$replacements);
		
		if ($is_moment == true) return '<time datetime="'.date('c',$time).'" data-time="'.$time.'" data-format="'.$format.'" data-moment="'.$momentFormat.'">'.date($format,$time).'</time>';
		else return date($format,$time);
	}
	
	/**
	 * 복호화가 가능한 방식(AES-256-CBC)으로 문자열을 암호화한다.
	 *
	 * @param string $value 암호화할 문자열
	 * @param ?string $key 암호화키 (NULL인 경우 환경설정의 암호화키)
	 * @param string $mode 암호화된 문자열 인코딩방식 (base64 또는 hex)
	 * @return string $ciphertext
	 */
	public static function encoder(string $value,?string $key=null,string $mode='base64') {
		$key = $key !== null ? md5($key) : md5(Configs::get('key'));
		$padSize = 16 - (strlen($value) % 16);
		$value = $value.str_repeat(chr($padSize),$padSize);
		
		$output = openssl_encrypt($value,'AES-256-CBC',$key,OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,str_repeat(chr(0),16));
		
		return $mode == 'base64' ? base64_encode($output) : bin2hex($output);
	}
	
	/**
	 * 복호화가 가능한 방식(AES-256-CBC)으로 암호화된 문자열을 복호화한다.
	 *
	 * @param string $value 암호화된 문자열
	 * @param ?string $key 암호화키 (NULL인 경우 환경설정의 암호화키)
	 * @param string $mode 암호화된 문자열 인코딩방식 (base64 또는 hex)
	 * @return string $plaintext
	 */
	public static function decoder($value,$key=null,$mode='base64') {
		$key = $key !== null ? md5($key) : md5(Configs::get('key'));
		$value = $mode == 'base64' ? base64_decode(str_replace(' ','+',$value)) : hex2bin($value);
		
		$output = openssl_decrypt($value,'AES-256-CBC',$key,OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,str_repeat(chr(0),16));
		if ($output === false) return false;
		
		$valueLen = strlen($output);
		if ($valueLen % 16 > 0) return false;
	
		$padSize = ord($output[$valueLen - 1]);
		if (($padSize < 1) || ($padSize > 16)) return false;
	
		for ($i=0;$i<$padSize;$i++) {
			if (ord($output[$valueLen - $i - 1]) != $padSize) return false;
		}
		
		return substr($output,0,$valueLen-$padSize);
	}
}
?>
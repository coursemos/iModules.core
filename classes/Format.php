<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 데이터의 형식을 관리하는 클래스를 정의한다.
 *
 * @file /classes/Format.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 17.
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
}
?>
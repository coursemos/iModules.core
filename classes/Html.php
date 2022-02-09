<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * HTML 출력을 위한 클래스를 정의한다.
 *
 * @file /classes/Html.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 9.
 */
class Html {
	/**
	 * <HEAD> 태그내에 포함될 태그를 정의한다.
	 */
	private static array $_heads = [];
	
	/**
	 * 호출되는 스크립트의 우선순위를 정의한다.
	 */
	private static array $_scripts = [];
	
	/**
	 * 호출되는 스타일시트의 우선순위를 정의한다.
	 */
	private static array $_styles = [];
	
	/**
	 * <TITLE> 태그에 들어갈 문서제목을 정의한다.
	 */
	private static ?string $_title = null;
	
	/**
	 * <META NAME="DESCRIPTION"> 태그에 들어갈 문서설명을 정의한다.
	 */
	private static ?string $_description = null;
	
	/**
	 * <BODY> 태그의  attribute 를 정의한다.
	 */
	private static array $_attributes = [];
	
	/**
	 * HTML 엘리먼트를 생성한다.
	 *
	 * @param string $name 태그명
	 * @param array $attributes 태그속성
	 * @param string $content 태그콘텐츠
	 * @return string $element 태그요소
	 */
	public static function element(string $name,?array $attributes=null,?string $content=null):string {
		$element = '<';
		$element.= $name;
		if ($attributes !== null) {
			foreach ($attributes as $key=>$value) {
				$element.= ' '.$key;
				if ($value !== null) $element.= '="'.$value.'"';
			}
		}
		$element.= '>';
		if ($content !== null) $element.= $content;
		if ($content !== null) $element.= '</'.$name.'>';
		
		return $element;
	}
	
	/**
	 * <HEAD> 태그 내부의 요소를 추가한다.
	 *
	 * @param string $name 태그명
	 * @param array $attributes 태그속성
	 * @param int $priority 우선순위 (0 ~ 10, 우선순위가 낮을수록 먼저 출력된다.)
	 */
	public static function head(string $name,array $attributes,int $priority=10):void {
		$priority = min(max(-1,$priority),10);
		$element = self::element($name,$attributes,null);
		
		self::_head($element,$priority + 100);
	}
	
	/**
	 * <HEAD> 태그 내부의 요소를 우선순위 가중치에 따라 추가한다.
	 *
	 * @param string $element 태그요소
	 * @param int $priority 우선순위
	 */
	private static function _head(string $element,int $priority):void {
		self::$_heads[$element] = $priority;
	}
	
	/**
	 * HTML 문서제목을 정의한다.
	 *
	 * @param string $title
	 */
	public static function title(string $title):void {
		self::$_title = $title;
	}
	
	/**
	 * HTML 문서설명을 정의한다.
	 *
	 * @param string $description
	 */
	public static function description(string $description):void {
		self::$_description = addslashes(preg_replace('/(\r|\n)/',' ',$description));
	}
	
	/**
	 * 스타일시트를 추가한다.
	 *
	 * @param string $path 스타일시트 경로
	 * @param int $priority 우선순위 (-1 ~ 10, 우선순위가 낮을수록 먼저 호출된다. -1 일 경우 해당 스크립트는 제거된다.)
	 */
	public static function style(string $path,int $priority=10):void {
		$priority = min(max(-1,$priority),10);
		if ($priority == -1 && isset(self::$_styles[$path]) == true) {
			unset(self::$_styles[$path]);
		} else {
			self::$_styles[$path] = $priority;
		}
	}
	
	/**
	 * 자바스크립트 추가한다.
	 *
	 * @param string $path 자바스크립트 경로
	 * @param int $priority 우선순위 (-1 ~ 10, 우선순위가 낮을수록 먼저 호출된다. -1 일 경우 해당 스크립트는 제거된다.)
	 */
	public static function script(string $path,int $priority=10):void {
		$priority = min(max(-1,$priority),10);
		if ($priority == -1 && isset(self::$_scripts[$path]) == true) {
			unset(self::$_scripts[$path]);
		} else {
			self::$_scripts[$path] = $priority;
		}
	}
	
	/**
	 * <BODY> attribute 를 추가한다.
	 *
	 * @param string $attribute attribute 명 (class, style 등)
	 * @param ?string $value attribute 값 (NULL 인 경우 빈 attribute 를 추가한다.)
	 */
	public static function body(string $attribute,?string $value=null):void {
		self::$_attributes[$attribute] = $value;
	}
	
	/**
	 * 함수 매개변수로 들어온 모든 문자열을 줄바꿈하여 문자열로 반환한다.
	 *
	 * @param string ...$tags
	 * @return string $html
	 */
	public static function tag(string ...$tags):string {
		return implode("\n",$tags);
	}
	
	/**
	 * HTML 기본 헤더를 가져온다.
	 *
	 * @return string $header
	 */
	public static function header():string {
		$header = self::tag(
			'<!DOCTYPE HTML>',
			'<html lang="ko">',
			'<head>',
			''
		);
		
		/**
		 * 기본 <HEAD> 태그요소를 추가한다.
		 */
		self::_head(self::element('meta',['charset'=>'utf-8']),0);
		self::_head(self::element('title',null,self::$_title ?? 'NONAME'),1);
		self::_head(self::element('meta',['name'=>'description','content'=>self::$_description]),2);
		self::_head(self::element('meta',['name'=>'viewport','content'=>'user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width']),3);
		
		/**
		 * 스크립트 경로를 <HEAD>에 추가한다.
		 */
		foreach (self::$_scripts as $path=>$priority) {
			self::_head(self::element('script',['src'=>$path],''),1000 + $priority);
		}
		
		/**
		 * 스타일시트 경로를 <HEAD>에 추가한다.
		 */
		foreach (self::$_styles as $path=>$priority) {
			self::_head(self::element('link',['rel'=>'stylesheet','href'=>$path,'type'=>'text/css']),2000 + $priority);
		}
		
		/**
		 * <HEAD> 요소를 우선순위에 따라 정렬한 뒤, $header 에 추가한다.
		 */
		uasort(self::$_heads,function($left,$right) {
			return $left <=> $right;
		});
		$header.= self::tag(...array_keys(self::$_heads));
		
		$attributes = '';
		foreach (self::$_attributes as $key=>$value) {
			$attributes.= ' '.$key;
			if ($value !== null) $attributes.= '="'.$value.'"';
		}
		
		$header.= self::tag(
			'',
			'</head>',
			'<body'.$attributes.'>'
		);
		
		return $header;
	}
	
	/**
	 * HTML 기본 푸터를 가져온다.
	 *
	 * @return string $footer
	 */
	public static function footer():string {
		return self::tag(
			'</body>',
			'</html>'
		);
	}
}
?>
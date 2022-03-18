<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 테마 및 템플릿을 화면에 출력하기 위한 템플릿 엔진 클래스를 정의한다.
 *
 * @file /classes/Templet.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 3. 18.
 */
class Templet {
	/**
	 * 템플릿이 처리되는 대상 객체
	 * 모듈 / 플러그인 / 위젯 클래스 등
	 */
	private $_parent = null;
	
	/**
	 * 템플릿 설정을 저장한다.
	 */
	private ?string $_name = null;
	private ?object $_package = null;
	private ?object $_configs = null;
	
	/**
	 * 템플릿 클래스를 선언한다.
	 *
	 * @param mixed $parent 템플릿 클래스를 호출한 클래스
	 */
	public function __construct(mixed $parent=null) {
		$this->_parent = $parent;
	}
	
	/**
	 * 템플릿 설정이 정의되어 있는지 확인한다.
	 *
	 * @return bool $isLoaded
	 */
	private function _isLoaded():bool {
		return $this->_name !== null;
	}
	
	/**
	 * 템플릿을 설정한다.
	 *
	 * @param object $templet 템플릿설정
	 * @return Templet $this
	 */
	public function setTemplet(object $templet):Templet {
		if (strpos($templet->name,'/') === 0) {
			$this->_name = $templet->name;
		} else {
			if ($this->_parent == null) {
				$this->_name = Config::dir().'/themes/'.$templet->name;
			} else {
				$this->_name = $this->_parent->getDir().'/templets/'.$templet->name;
			}
		}
		$this->_configs = $templet->configs;
		
		if (is_dir($this->getPath()) == false || is_file($this->getPath().'/package.json') == false) {
			ErrorHandler::view($this->error('NOT_FOUND_TEMPLET',$this->_name));
		}
		
		return $this;
	}
	
	/**
	 * 현재 템플릿명(템플릿 경로)를 가져온다.
	 *
	 * @return string $name
	 */
	public function getName():string {
		return $this->_name ?? 'undefined';
	}
	
	/**
	 * 현재 템플릿의 절대경로를 가져온다.
	 *
	 * @return string $path
	 */
	public function getPath():string {
		return Config::path().$this->getName();
	}
	
	/**
	 * 현재 템플릿의 상대경로를 가져온다.
	 *
	 * @return string $dir
	 */
	public function getDir():string {
		return Config::dir().$this->getName();
	}
	
	/**
	 * 템플릿의 package.json 정보를 가져온다.
	 *
	 * @return ?object $package package.json 정보
	 */
	public function getPackage():?object {
		if ($this->_isLoaded() === false) return null;
		if ($this->_package !== null) return $this->_package;
		$this->_package = json_decode(file_get_contents($this->getPath().'/package.json'));
		return $this->_package;
	}
	
	/**
	 * 템플릿 파일에서 이용할 수 있는 데이터를 정리한다.
	 *
	 * @param array $values 데이터 배열
	 * @return array $values 정리된 변수
	 */
	function getValues(array $values=[]):array {
		unset($values['this'],$values['IM'],$values['Module'],$values['Widget'],$values['Templet'],$values['file'],$values['header'],$values['footer'],$values['layout']);
		
		$values['IM'] = iModules::getInstance();
		$values['me'] = &$this->_parent;
		$values['templet'] = &$this;
		
		return $values;
	}
	
	/**
	 * 템플릿 헤더를 불러온다.
	 *
	 * @return string $header 헤더 HTML
	 */
	public function getHeader():string {
		/**
		 * 템플릿을 설정되지 않은 경우 에러메시지를 반환한다.
		 */
		if ($this->_isLoaded() === false) return ErrorHandler::get($this->error('NOT_INITIALIZED_TEMPLET'));
		
		/**
		 * 템플릿의 package.json 에 styles 나 scripts 가 설정되어 있다면, 해당 파일을 불러온다.
		 */
		$package = $this->getPackage();
		if (isset($package->styles) == true && is_array($package->styles) == true) {
			foreach ($package->styles as $style) {
				$style = preg_match('/^(http(s)?:\/\//',$style) == true ? $style : $this->getDir().$style;
				Html::style($style);
			}
		}
		
		if (isset($package->scripts) == true && is_array($package->scripts) == true) {
			foreach ($package->scripts as $script) {
				$script = preg_match('/^(http(s)?:\/\//',$style) == true ? $script : $this->getDir().$script;
				Html::script($script);
			}
		}
		
		/**
		 * todo: 이벤트를 발생시킨다.
		 */
		
		$header = '';
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		$values = $this->getValues();
		foreach ($values as $key=>$value) {
			${$key} = $value;
		}
		unset($values);
		
		ob_start();
		include $this->getPath().'/header.php';
		$header.= ob_get_clean();
		
		/**
		 * todo: 이벤트를 발생시킨다.
		 */
		
		/**
		 * 기본 HTML 헤더와 함께 템플릿 헤더를 반환한다.
		 */
		$html = Html::tag(
			Html::header(),
			$header
		);
		
		return $html;
	}
	
	/**
	 * 템플릿 푸터를 불러온다.
	 *
	 * @return string $footer 푸터 HTML
	 */
	public function getFooter():string {
		/**
		 * 템플릿을 설정되지 않은 경우 에러메시지를 반환한다.
		 */
		if ($this->_isLoaded() === false) return ErrorHandler::get($this->error('NOT_INITIALIZED_TEMPLET'));
		
		
		/**
		 * todo: 이벤트를 발생시킨다.
		 */
		
		$footer = '';
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		$values = $this->getValues();
		foreach ($values as $key=>$value) {
			${$key} = $value;
		}
		unset($values);
		
		ob_start();
		include $this->getPath().'/footer.php';
		$footer.= ob_get_clean();
		
		/**
		 * todo: 이벤트를 발생시킨다.
		 */
		
		/**
		 * 기본 HTML 푸터와 함께 템플릿 푸터를 반환한다.
		 */
		$html = Html::tag(
			$footer,
			Html::footer()
		);
		
		return $html;
	}
	
	/**
	 * 모듈에서 컨텍스트를 가져온다.
	 *
	 * @param string $file PHP 확장자를 포함하지 않는 컨텍스트 파일명
	 * @param string $values 템플릿 호출시 넘어온 변수목록 (일반적으로 get_defined_vars() 함수결과가 넘어온다.)
	 * @param string $header(옵션) 컨텍스트 HTML 상단에 포함할 헤더 HTML
	 * @param string $footer(옵션) 컨텍스트 HTML 하단에 포함할 푸더 HTML
	 * @return string $html 컨텍스트 HTML
	 */
	function getContext(string $file,array $values=[],string $header='',string $footer=''):string {
		/**
		 * 템플릿폴더에 파일이 없다면 에러메세지를 출력한다.
		 */
		if (is_file($this->getPath().'/'.$file.'.php') == false) {
			return ErrorHandler::get($this->error('NOT_FOUND_TEMPLET_FILE',$this->getPath().'/'.$file.'.php'));
		}
		
		/**
		 * todo: 이벤트를 발생시킨다.
		 */
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		$values = $this->getValues($values);
		foreach ($values as $key=>$value) {
			${$key} = $value;
		}
		unset($values);
		
		if (is_file($this->getPath().'/'.$file.'.php') == true) {
			ob_start();
			include $this->getPath().'/'.$file.'.php';
			$context = ob_get_clean();
		}
		
		/**
		 * todo: 이벤트를 발생시킨다.
		 */
		$html = Html::tag(
			$header,
			$context,
			$footer
		);
		
		return $html;
	}
	
	/**
	 * 사이트테마에서 문서(HTML)를 가져온다.
	 *
	 * @param string $filename
	 * @return string $document
	 */
	public function getFile(string $filename):string {
		/**
		 * 템플릿 폴더에 해당파일이 없다면 에러메세지를 출력한다.
		 */
		if (is_file($this->getPath().'/includes/'.$filename) === false) {
			return ErrorHandler::get($this->error('NOT_FOUND_TEMPLET_FILE',$this->getPath().'/includes/'.$filename));
		}
		
		// todo: 이벤트 발생
		
		/**
		 * 템플릿파일에서 사용할 변수선언
		 */
		$IM = iModules::getInstance();
		$Templet = $this;
		$me = $this->_parent;
		
		ob_start();
		include $this->getPath().'/includes/'.$filename;
		$html = ob_get_clean();
		
		// todo: 이벤트 발생
		
		return $html;
	}
	
	/**
	 * 특수한 에러코드의 경우 에러데이터를 현재 클래스에서 처리하여 에러클래스로 전달한다.
	 *
	 * @param string $code 에러코드
	 * @param ?string $message 에러메시지
	 * @return object $error
	 */
	public function error(string $code,?string $message=null):object {
		$error = ErrorHandler::data();
		
		switch ($code) {
			case 'NOT_FOUND_TEMPLET' :
				$error->prefix = ErrorHandler::getText('TEMPLET_ERROR');
				$error->message = ErrorHandler::getText('NOT_FOUND_TEMPLET');
				$error->suffix = $message;
				$error->stacktrace = ErrorHandler::trace('Templet');
				
				break;
			
			case 'NOT_FOUND_TEMPLET_FILE' :
				$error->prefix = ErrorHandler::getText('TEMPLET_ERROR');
				$error->message = ErrorHandler::getText('NOT_FOUND_TEMPLET_FILE');
				$error->suffix = $message;
				$error->stacktrace = ErrorHandler::trace('Templet');
				
				break;
				
			default :
				$error->message = ErrorHandler::getText($code);
		}
		
		return $error;
	}
}
?>
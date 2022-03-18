<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 캐시 클래스를 정의한다.
 *
 * @file /classes/Cache.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 3. 18.
 */
class Cache {
	private static array $_scripts = [];
	
	/**
	 * 스크립트 파일에 대해서 캐싱처리할 파일을 추가하고, 캐싱처리된 파일의 경로를 가져온다.
	 * 다수의 스크립트파일을 단일 그룹명으로 캐싱처리하면서 스크립트리 파일을 축소한다.
	 *
	 * @param string $group 캐싱처리를 하는 그룹명
	 * @param string $path 캐싱처리를 할 파일명
	 * @return array|string $scripts 현재 그룹의 전체 파일 경로 ($path 가 NULL 인 경우, 캐싱처리된 파일경로를 반환)
	 */
	public static function script(string $group,string|int $path=null):array|string {
		if (isset(self::$_scripts[$group]) == false) {
			self::$_scripts[$group] = [];
		}
		
		/**
		 * $path 가 NULL 이 아닌 경우, 해당 스크립트파일을 $group 에 추가한다.
		 */
		if ($path !== null) {
			self::$_scripts[$group][] = $path;
			
			return self::$_scripts[$group];
		} else {
			/**
			 * 디버그모드인 경우 캐싱처리하지 않은 전체 스크립트를 반환한다.
			 */
			if (Config::debug() == true) {
				return self::$_scripts[$group];
			} else {
				$refresh = false;
				$cached_time = is_file(Config::cache().'/'.$group.'.js') === true ? filemtime(Config::cache().'/'.$group.'.js') : 0;
				
				/**
				 * 캐시파일이 수정된지 5분 이상된 경우, 해당 그룹의 파일의 수정시간을 계산하여, 다시 캐싱을 해야하는지 여부를 확인한다.
				 */
				if ($cached_time < time() - 300) {
					foreach (self::$_scripts[$group] as $path) {
						if (is_file(Config::path().$path) == false) continue;
						if (filemtime(Config::path().$path) > $cached_time) {
							$refresh = true;
							break;
						}
					}
					
					/**
					 * 다시 캐싱을 해야하는 경우, 캐시파일을 재생성하고, 그렇지 않은 경우 캐시파일의 수정시간을 조절한다.
					 */
					if ($refresh == true) {
						$minifier = new MatthiasMullie\Minify\JS();
						foreach (self::$_scripts[$group] as $path) {
							$minifier->addFile(Config::path().$path);
						}
						$minifier->minify(Config::cache().'/'.$group.'.js');
					} else {
						touch(Config::cache().'/'.$group.'.js',time());
					}
					
					return Config::cache(false).'/'.$group.'.js';
				} else {
					return Config::cache(false).'/'.$group.'.js';
				}
			}
		}
	}
}
?>
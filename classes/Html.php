<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * HTML 출력을 위한 클래스를 정의한다.
 *
 * @file /classes/Html.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 5.
 */
class Html
{
    /**
     * @var string[] $_heads <HEAD> 태그내에 포함될 태그를 정의한다.
     */
    private static array $_heads = [];

    /**
     * @var string $_robots <META NAME="ROBOTS"> 태그에 들어갈 설정을 정의한다.
     */
    private static string $_robots = 'all';

    /**
     * @var string[] $_scripts 호출되는 스크립트의 우선순위를 정의한다.
     */
    private static array $_scripts = [];

    /**
     * @var string[] $_styles 호출되는 스타일시트의 우선순위를 정의한다.
     */
    private static array $_styles = [];

    /**
     * @var string[][] $_listeners HTML 문서 이벤트리스너를 정의한다.
     */
    private static array $_listeners = [];

    /**
     * @var ?string $_title <TITLE> 태그에 들어갈 문서제목을 정의한다.
     */
    private static ?string $_title = null;

    /**
     * @var ?string $_description <META NAME="DESCRIPTION"> 태그에 들어갈 문서설명을 정의한다.
     */
    private static ?string $_description = null;

    /**
     * @var string[] $_attributes <BODY> 태그의 attribute 를 정의한다.
     */
    private static array $_attributes = [];

    /**
     * @var bool[] $_fonts 불러올 웹폰트를 정의한다.
     */
    private static array $_fonts = [];

    /**
     * HTML 엘리먼트를 생성한다.
     *
     * @param string $name 태그명
     * @param array $attributes 태그속성
     * @param string $content 태그콘텐츠
     * @return string $element 태그요소
     */
    public static function element(string $name, ?array $attributes = null, ?string $content = null): string
    {
        $element = '<';
        $element .= $name;
        if ($attributes !== null) {
            foreach ($attributes as $key => $value) {
                $element .= ' ' . $key;
                if ($value !== null) {
                    $element .= '="' . $value . '"';
                }
            }
        }
        $element .= '>';
        if ($content !== null) {
            $element .= $content;
        }
        if ($content !== null) {
            $element .= '</' . $name . '>';
        }

        return $element;
    }

    /**
     * <HEAD> 태그 내부의 요소를 추가한다.
     *
     * @param string $name 태그명
     * @param array $attributes 태그속성
     * @param int $priority 우선순위 (0 ~ 10, 우선순위가 낮을수록 먼저 출력된다.)
     */
    public static function head(string $name, array $attributes, int $priority = 10): void
    {
        $priority = min(max(-1, $priority), 10);
        $element = self::element($name, $attributes, null);

        self::_head($element, $priority + 100);
    }

    /**
     * <HEAD> 태그 내부의 요소를 우선순위 가중치에 따라 추가한다.
     *
     * @param string $element 태그요소
     * @param int $priority 우선순위
     */
    private static function _head(string $element, int $priority): void
    {
        self::$_heads[$element] = $priority;
    }

    /**
     * HTML 문서제목을 정의한다.
     *
     * @param string $title
     */
    public static function title(string $title): void
    {
        self::$_title = $title;
    }

    /**
     * HTML 문서설명을 정의한다.
     *
     * @param string $description
     */
    public static function description(string $description): void
    {
        self::$_description = addslashes(preg_replace('/(\r|\n)/', ' ', $description));
    }

    /**
     * 현재 페이지의 검색로봇 규칙을 설정한다.
     * SEO를 위해 사용된다.
     *
     * @see https://developers.google.com/search/reference/robots_meta_tag?hl=ko
     * @param string $robots
     * @return null
     */
    public static function robots(string $robots): void
    {
        self::$_robots = $robots;
    }

    /**
     * 자바스크립트 추가한다.
     *
     * @param string|array $path 자바스크립트 경로
     * @param int $priority 우선순위 (-1 ~ 10, 우선순위가 낮을수록 먼저 호출된다. -1 일 경우 해당 스크립트는 제거된다.)
     */
    public static function script(string|array $path, int $priority = 10): void
    {
        if (is_array($path) == true) {
            $paths = $path;
            foreach ($paths as $path) {
                self::script($path, $priority);
            }
        } else {
            $priority = min(max(-1, $priority), 10);
            if ($priority == -1 && isset(self::$_scripts[$path]) == true) {
                unset(self::$_scripts[$path]);
            } else {
                self::$_scripts[$path] = $priority;
            }
        }
    }

    /**
     * 스타일시트를 추가한다.
     *
     * @param string|array $path 스타일시트 경로
     * @param int $priority 우선순위 (-1 ~ 10, 우선순위가 낮을수록 먼저 호출된다. -1 일 경우 해당 스크립트는 제거된다.)
     */
    public static function style(string|array $path, int $priority = 10): void
    {
        if (is_array($path) == true) {
            $paths = $path;
            foreach ($paths as $path) {
                self::style($path, $priority);
            }
        } else {
            $priority = min(max(-1, $priority), 10);

            /**
             * scss 파일인 경우 Cache 를 통해 css 파일로 컨버팅한다.
             */
            if (preg_match('/\.scss$/i', $path) == true) {
                $path = Cache::scss($path);
            }

            if ($priority == -1 && isset(self::$_styles[$path]) == true) {
                unset(self::$_styles[$path]);
            } else {
                self::$_styles[$path] = $priority;
            }
        }
    }

    /**
     * HTML onReady 이벤트리스너를 등록한다.
     */
    public static function ready(string $listener): void
    {
        if (isset(self::$_listeners['ready']) == false) {
            self::$_listeners['ready'] = [];
        }

        self::$_listeners['ready'][] = $listener;
    }

    /**
     * <BODY> attribute 를 추가한다.
     *
     * @param string $attribute attribute 명 (class, style 등)
     * @param ?string $value attribute 값 (NULL 인 경우 빈 attribute 를 추가한다.)
     */
    public static function body(string $attribute, ?string $value = null): void
    {
        self::$_attributes[$attribute] = $value;
    }

    /**
     * 웹폰트를 불러온다.
     *
     * @param string $font 폰트명
     * @param bool $is_cache 캐시여부 (항상 사용되는 폰트가 이는 경우 캐시사용시 로드되지 않을 수 있음)
     */
    public static function font(string $font, bool $is_cache = false): void
    {
        if (isset(self::$_fonts[$font]) == false || self::$_fonts[$font] != $is_cache) {
            self::$_fonts[$font] = $is_cache;
        }
    }

    /**
     * 함수 매개변수로 들어온 모든 문자열을 줄바꿈하여 문자열로 반환한다.
     *
     * @param string ...$tags
     * @return string $html
     */
    public static function tag(string ...$tags): string
    {
        return implode("\n", $tags);
    }

    /**
     * 함수 매개변수로 들어온 모든 문자열을 줄바꿈하여 출력한다.
     *
     * @param string ...$tags
     * @return string $html
     */
    public static function print(string ...$tags): void
    {
        echo self::tag(...$tags);
    }

    /**
     * HTML 기본 헤더를 가져온다.
     *
     * @return string $header
     */
    public static function header(): string
    {
        $header = self::tag('<!DOCTYPE HTML>', '<html lang="ko">', '<head>', '');

        /**
         * 기본 <HEAD> 태그요소를 추가한다.
         */
        self::_head(self::element('meta', ['charset' => 'utf-8']), 0);

        $title = self::$_title ?? 'iModules';
        self::_head(self::element('title', null, $title), 1);
        self::_head(self::element('meta', ['name' => 'description', 'content' => self::$_description]), 2);
        self::_head(
            self::element('meta', [
                'name' => 'viewport',
                'content' =>
                    'user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, width=device-width',
            ]),
            3
        );
        self::_head(self::element('meta', ['name' => 'robots', 'content' => self::$_robots]), 4);

        /**
         * 웹폰트를 추가한다.
         */
        if (count(self::$_fonts) > 0) {
            foreach (self::$_fonts as $font => $is_cache) {
                if ($is_cache == true) {
                    Cache::style('font', '/fonts/' . $font . '.css');
                } else {
                    self::style('/fonts/' . $font . '.css');
                }
            }
            self::style(Cache::style('font'));
        }

        /**
         * 스크립트 경로를 <HEAD>에 추가한다.
         */
        foreach (self::$_scripts as $path => $priority) {
            self::_head(self::element('script', ['src' => $path . self::_time($path)], ''), 1000 + $priority);
        }

        /**
         * 스타일시트 경로를 <HEAD>에 추가한다.
         */
        foreach (self::$_styles as $path => $priority) {
            self::_head(
                self::element('link', [
                    'rel' => 'stylesheet',
                    'href' => $path . self::_time($path),
                    'type' => 'text/css',
                ]),
                2000 + $priority
            );
        }

        /**
         * <HEAD> 요소를 우선순위에 따라 정렬한 뒤, $header 에 추가한다.
         */
        uasort(self::$_heads, function ($left, $right) {
            return $left <=> $right;
        });
        $header .= self::tag(...array_keys(self::$_heads));

        $attributes = '';
        foreach (self::$_attributes as $key => $value) {
            $attributes .= ' ' . $key;
            if ($value !== null) {
                $attributes .= '="' . $value . '"';
            }
        }

        $header .= self::tag('', '</head>', '<body' . $attributes . '>');

        return $header;
    }

    /**
     * HTML 기본 푸터를 가져온다.
     *
     * @return string $footer
     */
    public static function footer(): string
    {
        $footer = '';
        if (isset(self::$_listeners['ready']) == true && count(self::$_listeners['ready']) > 0) {
            $footer .= self::tag(
                '<script>',
                '$(document).ready(function() {',
                implode("\n", self::$_listeners['ready']),
                '});',
                '</script>'
            );
        }

        $footer .= self::tag('</body>', '</html>');

        return $footer;
    }

    /**
     * 파일경로에 파일 수정시간을 추가한다.
     *
     * @param string $path 파일경로
     * @return string $time 추가되는 수정시간
     */
    private static function _time(string $path): string
    {
        $time = '';

        if (strpos($path, '/') === 0) {
            if (is_file(Configs::dirToPath($path)) === false) {
                return $time;
            }
            if (strpos($path, 't=') !== false) {
                return $time;
            }
            $time .= strpos($path, '?') === false ? '?t=' : '&t=';
            $time .= filemtime(Configs::dirToPath($path));
        }

        return $time;
    }
}
?>

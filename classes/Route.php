<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 경로 데이터를 처리한다.
 *
 * @file /classes/Route.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Route
{
    /**
     * @var string $_path 경로
     */
    private string $_path;

    /**
     * @var string $_language 언어코드 (# : 언어 구분 없음)
     */
    private string $_language;

    /**
     * @var string $_type 종류(context, html, json, blob)
     */
    private string $_type;

    /**
     * @var Closure $_closure 경로에 해당하는 콘텐츠를 가져오기 위한 Closure
     */
    private Closure $_closure;

    /**
     * 경로를 초기화한다.
     *
     * @param string $path 경로
     * @param string $language 언어코드 (# : 언어 구분 없음)
     * @param string $type 종류(context, html, json, blob)
     * @param callable $closure
     */
    public function __construct(string $path, string $language, string $type, callable $closure)
    {
        $this->_path = $path;
        $this->_language = $language;
        $this->_type = $type;
        $this->_closure = Closure::fromCallable($closure);
    }

    /**
     * 언어코드를 가져온다.
     *
     * @return string $language
     */
    public function getLanguage(): string
    {
        return $this->_language;
    }

    /**
     * 사용자 언어코드를 강제로 지정한다.
     *
     * @paran string $language
     */
    public function setLanguage(string $language): void
    {
        $this->_language = $language;
    }

    /**
     * 경로를 가져온다.
     *
     * @param bool $is_included_subpage 하위 경로 포함 여부 (기본값 : false)
     * @return string $path
     */
    public function getPath($is_included_subpage = false): string
    {
        $path = preg_replace('/\/\*$/', '', $this->_path);
        foreach ($this->getValues() as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }

        if ($is_included_subpage == true) {
            $path .= $this->getSubPath();
        }

        return $path;
    }

    /**
     * 하위 경로를 가져온다.
     *
     * @return string $subpath
     */
    public function getSubPath(): string
    {
        $values = $this->getValues();
        if (isset($values['*']) == true) {
            return $values['*'];
        }
        return '';
    }

    /**
     * URL 변수를 가져온다.
     *
     * @return mixed[] $values
     */
    public function getValues(): array
    {
        $path = preg_replace('/\/\*$/', '{*}', $this->_path);
        $matcher = str_replace('/', '\/', $path);
        $matcher = preg_replace('/{[^}]+}/', '(.*?)', $matcher);

        $keys = [];
        if (preg_match_all('/\{(.*?)\}/', $path, $keys) == true) {
            $keys = $keys[1];
        }

        $values = [];
        if (count($keys) > 0) {
            if (preg_match('/' . $matcher . '$/', Router::getPath(), $values) == true) {
                array_shift($values);
            }
        }

        if (count($keys) == count($values)) {
            $values = array_combine($keys, $values);
        }

        return $values;
    }

    /**
     * 경로 타입을 가져온다.
     *
     * @return string $type
     */
    public function getType(): string
    {
        return $this->_type;
    }

    /**
     * 경로가 포함된 도메인을 가져온다.
     *
     * @return Domain $domain
     */
    public function getDomain(): Domain
    {
        return Domains::get();
    }

    /**
     * 경로가 포함된 사이트를 가져온다.
     *
     * @return Site $site
     */
    public function getSite(): Site
    {
        if (in_array($this->_language, ['*', '#']) == true) {
            return Sites::get($this->getDomain()->getHost(), $this->getDomain()->getLanguage());
        } else {
            return Sites::get($this->getDomain()->getHost(), $this->getLanguage());
        }
    }

    /**
     * 전체 URL 경로를 가져온다.
     *
     * @param bool $is_included_subpage 하위 경로 포함 여부 (기본값 : false)
     * @param bool $is_domain 도메인 포함 여부 (기본값 : false)
     * @return string $url
     */
    public function getUrl(bool $is_included_subpage = false, bool $is_domain = false): string
    {
        if (
            $is_domain == true ||
            $this->getSite()->getHost() != Request::host() ||
            $this->getDomain()->isHttps() != Request::isHttps()
        ) {
            $url = $this->getDomain()->getUrl();
        } else {
            $url = '';
        }
        $url .= Configs::dir();

        $route = '';
        if ($this->getLanguage() != '#') {
            if ($this->getDomain()->isInternationalization() == true) {
                $route .= '/' . $this->getLanguage();
            }

            if ($this->getPath() == '/' && $this->getSubPath() == '') {
                if ($this->getSite()->getLanguage() == $this->getDomain()->getLanguage()) {
                    $route = preg_replace('/' . $this->getSite()->getLanguage() . '$/', '', $route);
                }
            } else {
                $route .= $this->getPath($is_included_subpage);
            }
        } else {
            $route .= $this->getPath($is_included_subpage);
        }

        if ($this->getDomain()->isRewrite() == true) {
            $url .= $route != '/' ? $route : '/';
        } else {
            $url .= '/' . ($route != '/' ? '?route=' . $route : '');
        }

        $values = $this->getValues();
        if (count($values) > 0) {
            $url = preg_replace_callback(
                '/\{(.*?)\}/',
                function ($matches) use ($values) {
                    if (isset($values[$matches[1]]) == true) {
                        return $values[$matches[1]];
                    }
                },
                $url
            );
        }

        return $url;
    }

    /**
     * context, html, json 타입의 경로에 해당하는 콘텐츠를 가져온다.
     *
     * @return string $content
     */
    public function getContent(): string
    {
        $values = array_values($this->getValues());
        array_unshift($values, $this);
        return call_user_func_array($this->_closure, $values);
    }

    /**
     * blob 타입의 경로에 해당하는 콘텐츠를 가져온다.
     */
    public function printContent(): void
    {
        $values = array_values($this->getValues());
        array_unshift($values, $this);
        call_user_func_array($this->_closure, $values);
    }
}

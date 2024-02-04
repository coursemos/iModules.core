<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 캐시 클래스를 정의한다.
 *
 * @file /classes/Cache.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 10.
 */
class Cache
{
    private static array $_scripts = [];
    private static array $_styles = [];
    private static $_used = true;

    /**
     * 캐시 클래스를 초기화한다.
     */
    public static function init(): void
    {
        /**
         * 캐시 라우터를 초기화한다.
         */
        Router::add('/caches/{name}', '#', 'blob', ['Cache', 'doRoute']);
    }

    /**
     * 캐시사용여부를 설정한다.
     *
     * @param bool $used
     */
    public static function use(bool $used): void
    {
        self::$_used = $used;
    }

    /**
     * 캐시쓰기 여부를 확인한다.
     *
     * @param bool $available
     */
    public static function writable(): bool
    {
        return is_dir(Configs::cache()) == true && is_writable(Configs::cache());
    }

    /**
     * 캐시사용가능 여부를 확인한다.
     *
     * @param bool $available
     */
    public static function check(): bool
    {
        return self::$_used == true && self::writable();
    }

    /**
     * 캐시파일의 URL 을 가져온다.
     * 캐시파일에 바로 접근해야하는 자바스크립트 및 스타일시트 캐시파일에만 사용한다.
     */
    public static function url(string $name = '', int $time = 0): string
    {
        $url = Configs::dir();
        if (Domains::has()?->isRewrite() == true) {
            $url .= '/caches';
            if ($name === '') {
                return $url;
            }
            $url .= '/' . $name . ($time > 0 ? '?t=' . $time : '');
        } else {
            $url .= '/';
            if ($name === '') {
                return $url;
            }
            $url .= '?route=/caches/' . $name . ($time > 0 ? '&t=' . $time : '');
        }

        return $url;
    }

    /**
     * 캐시 내용을 업데이트한다.
     *
     * @param string $name 캐시파일명
     * @param mixed $data 캐시데이터
     * @param bool $is_raw RAW 데이터 여부
     * @return bool $success
     */
    public static function store(string $name, mixed $data, bool $is_raw = false): bool
    {
        if ($is_raw === false) {
            $name .= '.cache';
            $data = serialize($data);
        }

        return File::write(Configs::cache() . '/' . $name, $data);
    }

    /**
     * 캐시를 제거한다.
     *
     * @param string $name 캐시파일명
     * @param bool $is_raw RAW 데이터 여부
     * @return bool $success
     */
    public static function remove(string $name, bool $is_raw = false): bool
    {
        if ($is_raw === false) {
            $name .= '.cache';
        }

        return File::remove(Configs::cache() . '/' . $name);
    }

    /**
     * 캐시 데이터를 가져온다.
     *
     * @param string $name 캐시파일명
     * @param int $lifetime 캐시유지시간(초)
     * @param bool $is_raw RAW 데이터 여부
     * @return mixed $data 캐시데이터 (NULL 인 경우 캐시가 존재하지 않음)
     */
    public static function get(string $name, int $lifetime = 0, bool $is_raw = false): mixed
    {
        if (self::has($name, $lifetime, $is_raw) == true) {
            if ($is_raw === false) {
                $name .= '.cache';
            }

            $data = File::read(Configs::cache() . '/' . $name);
            return $is_raw === true ? $data : unserialize($data);
        } else {
            return null;
        }
    }

    /**
     * 유효한 캐시파일이 존재하는지 확인한다.
     *
     * @param string $name 캐시파일명
     * @param int $lifetime 캐시유지시간(초)
     * @param bool $is_raw RAW 데이터 여부
     * @return bool $hasCache 캐시파일 존재여부
     */
    public static function has(string $name, int $lifetime = 0, bool $is_raw = false): bool
    {
        if ($is_raw === false) {
            $name .= '.cache';
        }

        if (self::check() == false) {
            return false;
        }
        if (is_file(Configs::cache() . '/' . $name) == false) {
            return false;
        }
        if ($lifetime > 0 && filemtime(Configs::cache() . '/' . $name) < time() - $lifetime) {
            return false;
        }

        return true;
    }

    /**
     * 자바스크립트 파일에 대해서 캐싱처리할 파일을 추가하고, 캐싱처리된 파일의 경로를 가져온다.
     * 다수의 자바스크립트파일을 단일 그룹명으로 캐싱처리하면서 자바스크립트 파일을 축소한다.
     *
     * @param string $group 캐싱처리를 하는 그룹명
     * @param string $path 캐싱처리를 할 파일명
     * @return array|string $scripts 현재 그룹의 전체 파일 경로 ($path 가 NULL 인 경우, 캐싱처리된 파일경로를 반환)
     */
    public static function script(string $group, string|int $path = null): array|string
    {
        if (isset(self::$_scripts[$group]) == false) {
            self::$_scripts[$group] = [];
        }

        /**
         * $path 가 NULL 이 아닌 경우, 해당 파일을 $group 에 추가한다.
         */
        if ($path !== null) {
            self::$_scripts[$group][] = $path;

            return self::$_scripts[$group];
        } else {
            /**
             * 캐시그룹에 파일이 존재하지 않는 경우 빈 배열을 반환한다.
             */
            if (count(self::$_scripts[$group]) == 0) {
                return [];
            }

            /**
             * 캐시를 사용할 수 없는 경우 전체 파일을 반환한다.
             */
            if (Configs::debug() == true || self::check() == false) {
                return self::pathToUrl(self::$_scripts[$group]);
            } else {
                $refresh = false;
                $cached_time =
                    is_file(Configs::cache() . '/' . $group . '.js') === true
                        ? filemtime(Configs::cache() . '/' . $group . '.js')
                        : 0;

                /**
                 * 캐시파일이 수정된지 5분 이상된 경우, 해당 그룹의 파일의 수정시간을 계산하여, 다시 캐싱을 해야하는지 여부를 확인한다.
                 */
                if ($cached_time < time() - 300) {
                    foreach (self::$_scripts[$group] as $index => $path) {
                        if (preg_match('/^' . Format::reg(Configs::cache()) . '/', $path) == false) {
                            $path = Configs::path() . $path;
                            self::$_scripts[$group][$index] = $path;
                        }
                        if (is_file($path) == false) {
                            continue;
                        }
                        if (filemtime($path) > $cached_time) {
                            $refresh = true;
                        }
                    }

                    /**
                     * 다시 캐싱을 해야하는 경우, 캐시파일을 재생성하고, 그렇지 않은 경우 캐시파일의 수정시간을 조절한다.
                     */
                    if ($refresh == true) {
                        $minifier = new \MatthiasMullie\Minify\JS();
                        foreach (self::$_scripts[$group] as $path) {
                            if (is_file($path) == false) {
                                continue;
                            }
                            $minifier->add('/* @origin ' . self::pathToUrl($path) . ' */');
                            $minifier->add($path);
                        }
                        $source = $minifier->execute();
                        $source = preg_replace('/(\/\*(.*?)\*\/);?/', "\n$1\n", $source);

                        $description = [
                            '/**',
                            ' * 이 파일은 자동으로 생성된 캐시파일의 일부입니다.',
                            ' *',
                            ' * 자바스크립트 캐시파일',
                            ' *',
                            ' * @file ' . self::url($group . '.js'),
                            ' * @include ' . implode("\n * @include ", self::pathToUrl(self::$_scripts[$group])),
                            ' * @cached ' . date('c', time()),
                            ' */',
                        ];

                        $content = implode("\n", $description) . "\n" . $source;
                        File::write(Configs::cache() . '/' . $group . '.js', $content);
                    } else {
                        touch(Configs::cache() . '/' . $group . '.js', time());
                    }

                    return self::url($group . '.js', time());
                } else {
                    return self::url($group . '.js', $cached_time);
                }
            }
        }
    }

    /**
     * 스타일시트 파일에 대해서 캐싱처리할 파일을 추가하고, 캐싱처리된 파일의 경로를 가져온다.
     * 다수의 스타일시트파일을 단일 그룹명으로 캐싱처리하면서 스타일시트 파일을 축소한다.
     *
     * @param string $group 캐싱처리를 하는 그룹명
     * @param string $path 캐싱처리를 할 파일명
     * @return array|string $styles 현재 그룹의 전체 파일 경로 ($path 가 NULL 인 경우, 캐싱처리된 파일경로를 반환)
     */
    public static function style(string $group, string|int $path = null): array|string
    {
        $group = str_replace('/', '.', $group);
        if (isset(self::$_styles[$group]) == false) {
            self::$_styles[$group] = [];
        }

        /**
         * $path 가 NULL 이 아닌 경우, 해당 파일을 $group 에 추가한다.
         */
        if ($path !== null) {
            if (preg_match('/\.scss$/', $path) == true) {
                $path = self::scss($path, false);
                if ($path !== null) {
                    self::$_styles[$group][] = $path;
                }
            } else {
                self::$_styles[$group][] = $path;
            }

            return self::$_styles[$group];
        } else {
            /**
             * 캐시그룹에 파일이 존재하지 않는 경우 빈 배열을 반환한다.
             */
            if (count(self::$_styles[$group]) == 0) {
                return [];
            }

            /**
             * 캐시를 사용할 수 없는 경우 전체 파일을 반환한다.
             */
            if (Configs::debug() == true || self::check() == false) {
                return self::pathToUrl(self::$_styles[$group]);
            } else {
                $refresh = false;
                $cached_time =
                    is_file(Configs::cache() . '/' . $group . '.css') === true
                        ? filemtime(Configs::cache() . '/' . $group . '.css')
                        : 0;

                /**
                 * 캐시파일이 수정된지 5분 이상된 경우, 해당 그룹의 파일의 수정시간을 계산하여, 다시 캐싱을 해야하는지 여부를 확인한다.
                 */
                if ($cached_time < time() - 300) {
                    foreach (self::$_styles[$group] as $index => $path) {
                        if (preg_match('/^' . Format::reg(Configs::cache()) . '/', $path) == false) {
                            $path = Configs::path() . $path;
                            self::$_styles[$group][$index] = $path;
                        }
                        if (is_file($path) == false) {
                            continue;
                        }
                        if (filemtime($path) > $cached_time) {
                            $refresh = true;
                        }
                    }

                    /**
                     * 다시 캐싱을 해야하는 경우, 캐시파일을 재생성하고, 그렇지 않은 경우 캐시파일의 수정시간을 조절한다.
                     */
                    if ($refresh == true) {
                        $minifier = new \MatthiasMullie\Minify\CSS();
                        foreach (self::$_styles[$group] as $path) {
                            if (is_file($path) == false) {
                                continue;
                            }
                            $minifier->add('/* @origin ' . self::pathToUrl($path) . ' */');

                            if (preg_match('/\.scss.css$/', $path) == true) {
                                $minifier->add(File::read($path));
                            } else {
                                $minifier->add($path);
                            }
                        }
                        $source = $minifier->execute(self::url() . $group . '.css');
                        $source = preg_replace('/(\/\*(.*?)\*\/);?/', "\n$1\n", $source);

                        $description = [
                            '/**',
                            ' * 이 파일은 자동으로 생성된 캐시파일의 일부입니다.',
                            ' *',
                            ' * 스타일시트 캐시파일',
                            ' *',
                            ' * @file ' . self::url($group . '.css'),
                            ' * @include ' . implode("\n * @include ", self::pathToUrl(self::$_styles[$group])),
                            ' * @cached ' . date('c', time()),
                            ' */',
                        ];

                        $content = implode("\n", $description) . "\n" . $source;
                        File::write(Configs::cache() . '/' . $group . '.css', $content);
                    } else {
                        touch(Configs::cache() . '/' . $group . '.css', time());
                    }

                    return self::url($group . '.css', time());
                } else {
                    return self::url($group . '.css', $cached_time);
                }
            }
        }
    }

    /**
     * SCSS 파일을 CSS 파일로 컴파일한다.
     *
     * @param string $scss SCSS 파일경로
     * @param bool $is_url CSS 파일 URL 반환 여부(false 인 경우 절대경로 반환)
     * @return ?string $css CSS 파일경로
     */
    public static function scss(string $path, bool $is_url = true): ?string
    {
        if (is_file(Configs::path() . $path) == false) {
            return null;
        }

        $is_convert = Configs::debug() == true || self::writable() == false;
        $cached_file = preg_replace('/^\./', '', str_replace('/', '.', $path));

        if ($is_convert == false && self::writable() == true) {
            $cached_time =
                is_file(Configs::cache() . '/' . $cached_file . '.css') === true
                    ? filemtime(Configs::cache() . '/' . $cached_file . '.css')
                    : 0;
            if ($cached_time < time() - 300) {
                if (filemtime(Configs::path() . $path) > $cached_time) {
                    $is_convert = true;
                } else {
                    touch(Configs::cache() . '/' . $cached_file . '.css', time());
                }
            }
        }

        /**
         * scss 파일의 컨버팅이 필요한 경우
         */
        if ($is_convert == true) {
            $compiler = new \ScssPhp\ScssPhp\Compiler();
            $compiler->setImportPaths(dirname(Configs::path() . $path));
            $content = $compiler->compileString(File::read(Configs::path() . $path))->getCss();

            $converter = new \MatthiasMullie\PathConverter\Converter($path, self::url(), '/');
            $matches = [];

            $relativeRegex = '/url\(\s*(?P<quotes>["\'])?(?P<path>.+?)(?(quotes)(?P=quotes))\s*\)/ix';
            if (preg_match_all($relativeRegex, $content, $regexMatches, PREG_SET_ORDER) == true) {
                $matches = $regexMatches;
            }

            $search = [];
            $replace = [];
            foreach ($matches as $match) {
                $url = $match['path'];
                $params = strrchr($url, '?');
                $url = $params ? substr($url, 0, -strlen($params)) : $url;
                $url = $converter->convert($url);
                $url .= $params;
                $url = trim($url);
                if (preg_match('/[\s\)\'"#\x{7f}-\x{9f}]/u', $url)) {
                    $url = $match['quotes'] . $url . $match['quotes'];
                }

                $search[] = $match[0];
                $replace[] = 'url(' . $url . ')';
            }

            $content = str_replace($search, $replace, $content);
            $content = str_replace('@charset "UTF-8";' . "\n", '', $content);
            if (self::writable() == true) {
                File::write(Configs::cache() . '/' . $cached_file . '.css', $content);
                $cached_time = time();
            } else {
                Html::head('style', [], 100, $content);
                return null;
            }
        }

        return $is_url == true
            ? self::url($cached_file . '.css', $cached_time)
            : Configs::cache() . '/' . $cached_file . '.css';
    }

    /**
     * 절대경로를 상대경로로 변경한다.
     *
     * @param string|array $origin 경로
     * @return string|array $path 절대경로가 숨겨진 경로
     */
    public static function pathToUrl(string|array $origin): string|array
    {
        if (is_array($origin) == true) {
            foreach ($origin as $index => $path) {
                $origin[$index] = self::pathToUrl($path);
            }

            return $origin;
        } else {
            if (preg_match('/^' . Format::reg(Configs::cache()) . '/', $origin) == true) {
                return Cache::url(basename($origin));
            } elseif (preg_match('/^' . Format::reg(Configs::path()) . '/', $origin) == true) {
                return preg_replace('/^' . Format::reg(Configs::path()) . '/', Configs::dir(), $origin);
            }
            return Configs::dir() . $origin;
        }
    }

    /**
     * 캐시파일 라우팅을 처리한다.
     *
     * @param Route $route 현재경로
     * @param string $name 캐시파일명
     */
    public static function doRoute(Route $route, string $name): void
    {
        if (preg_match('/\.(js|css)$/', $name, $match) === false) {
            Header::code(404);
            exit();
        }

        if (is_file(Configs::cache() . '/' . $name) === false) {
            Header::code(404);
            exit();
        }

        iModules::session_stop();

        switch ($match[1]) {
            case 'js':
                \Header::type('javascript');
                break;

            case 'css':
                \Header::type('css');
                break;
        }

        $modified = filemtime(Configs::cache() . '/' . $name);

        Header::cache(3600, $modified);

        readfile(Configs::cache() . '/' . $name);
        exit();
    }
}

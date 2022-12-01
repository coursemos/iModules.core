<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 아이모듈 캐시 클래스를 정의한다.
 *
 * @file /classes/Cache.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
class Cache
{
    private static array $_scripts = [];
    private static array $_styles = [];

    /**
     * 캐시 내용을 업데이트한다.
     *
     * @param string $name 캐시파일명
     * @param string $data 캐시데이터
     * @return bool $success
     */
    public static function store(string $name, string $data): bool
    {
        return file_put_contents(Configs::cache() . '/' . $name, $data) !== false;
    }

    /**
     * 유효한 캐시파일이 존재하는지 확인한다.
     *
     * @param string $name 캐시파일명
     * @param int $lifetime 캐시유지시간(초)
     *
     * @return bool $hasCache 캐시파일 존재여부
     */
    public static function has(string $name, int $lifetime): bool
    {
        if (Configs::debug() == true) {
            return false;
        }
        if (is_file(Configs::cache() . '/' . $name) == false) {
            return false;
        }
        if (filemtime(Configs::cache() . '/' . $name) < time() - $lifetime) {
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
             * 디버그모드인 경우 캐싱처리하지 않은 전체 파일을 반환한다.
             */
            if (Configs::debug() == true) {
                return self::$_scripts[$group];
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
                    foreach (self::$_scripts[$group] as $path) {
                        if (is_file(Configs::path() . $path) == false) {
                            continue;
                        }
                        if (filemtime(Configs::path() . $path) > $cached_time) {
                            $refresh = true;
                            break;
                        }
                    }

                    /**
                     * 다시 캐싱을 해야하는 경우, 캐시파일을 재생성하고, 그렇지 않은 경우 캐시파일의 수정시간을 조절한다.
                     */
                    if ($refresh == true) {
                        $minifier = new \MatthiasMullie\Minify\JS();
                        foreach (self::$_scripts[$group] as $path) {
                            if (is_file(Configs::path() . $path) == false) {
                                continue;
                            }
                            $minifier->add('/*! @origin ' . $path . ' */');
                            $minifier->add(Configs::path() . $path);
                        }
                        $source = $minifier->execute();
                        $source = preg_replace('/(\/\*(.*?)\*\/);?/', "\n$1\n", $source);

                        $description = [
                            '/**',
                            ' * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)',
                            ' *',
                            ' * 자바스크립트 캐시파일',
                            ' *',
                            ' * @file ' . Configs::cache(false) . '/' . $group . '.js',
                            ' * @include ' . implode("\n * @include ", self::$_scripts[$group]),
                            ' * @cached ' . date('c', time()),
                            ' */',
                        ];

                        $content = implode("\n", $description) . "\n" . $source;
                        file_put_contents(Configs::cache() . '/' . $group . '.js', $content);
                    } else {
                        touch(Configs::cache() . '/' . $group . '.js', time());
                    }

                    return Configs::cache(false) . '/' . $group . '.js?t=' . time();
                } else {
                    return Configs::cache(false) . '/' . $group . '.js?t=' . $cached_time;
                }
            }
        }
    }

    /**
     * SCSS 파일을 CSS 파일로 컴파일한다.
     *
     * @param string $scss SCSS 파일경로
     * @param string $css CSS 파일경로
     */
    public static function scss(string $path): string
    {
        if (is_file(Configs::path() . $path) == false) {
            return $path;
        }

        $cached_file = preg_replace('/^\./', '', str_replace('/', '.', $path));
        $cached_time =
            is_file(Configs::cache() . '/' . $cached_file . '.css') === true
                ? filemtime(Configs::cache() . '/' . $cached_file . '.css')
                : 0;

        /**
         * 디버그모드가 아니며 캐시파일이 수정된지 5분 이상된 경우, 해당 그룹의 파일의 수정시간을 계산하여, 다시 캐싱을 해야하는지 여부를 확인한다.
         */
        if (Configs::debug() == true || $cached_time < time() - 300) {
            if (true || filemtime(Configs::path() . $path) > $cached_time) {
                $compiler = new \ScssPhp\ScssPhp\Compiler();
                $compiler->setImportPaths(dirname(Configs::path() . $path));
                $content = $compiler->compileString(file_get_contents(Configs::path() . $path))->getCss();

                $converter = new \MatthiasMullie\PathConverter\Converter($path, Configs::cache(false));
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
                file_put_contents(Configs::cache() . '/' . $cached_file . '.css', $content);
            } else {
                touch(Configs::cache() . '/' . $cached_file . '.css', time());
            }
        }

        return Configs::cache(false) . '/' . $cached_file . '.css';
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
                self::$_styles[$group][] = self::scss($path);
            }
            self::$_styles[$group][] = $path;

            return self::$_styles[$group];
        } else {
            /**
             * 디버그모드인 경우 캐싱처리하지 않은 전체 파일을 반환한다.
             */
            if (Configs::debug() == true) {
                return self::$_styles[$group];
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
                    foreach (self::$_styles[$group] as $path) {
                        if (is_file(Configs::path() . $path) == false) {
                            continue;
                        }
                        if (filemtime(Configs::path() . $path) > $cached_time) {
                            $refresh = true;
                            break;
                        }
                    }

                    /**
                     * 다시 캐싱을 해야하는 경우, 캐시파일을 재생성하고, 그렇지 않은 경우 캐시파일의 수정시간을 조절한다.
                     */
                    if ($refresh == true) {
                        $minifier = new \MatthiasMullie\Minify\CSS();
                        foreach (self::$_styles[$group] as $path) {
                            if (is_file(Configs::path() . $path) == false) {
                                continue;
                            }
                            $minifier->add('/*! @origin ' . $path . ' */');
                            $minifier->add(Configs::path() . $path);
                        }
                        $source = $minifier->execute(Configs::cache(false) . '/' . $group . '.css');
                        $source = preg_replace('/(\/\*(.*?)\*\/);?/', "\n$1\n", $source);

                        $destyleion = [
                            '/**',
                            ' * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)',
                            ' *',
                            ' * 스타일시트 캐시파일',
                            ' *',
                            ' * @file ' . Configs::cache(false) . '/' . $group . '.css',
                            ' * @include ' . implode("\n * @include ", self::$_styles[$group]),
                            ' * @cached ' . date('c', time()),
                            ' */',
                        ];

                        $content = implode("\n", $destyleion) . "\n" . $source;
                        file_put_contents(Configs::cache() . '/' . $group . '.css', $content);
                    } else {
                        touch(Configs::cache() . '/' . $group . '.css', time());
                    }

                    return Configs::cache(false) . '/' . $group . '.css?t=' . time();
                } else {
                    return Configs::cache(false) . '/' . $group . '.css?t=' . $cached_time;
                }
            }
        }
    }
}

<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 전체 컨텍스트 데이터를 처리한다.
 *
 * @file /classes/Contexts.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 2. 25.
 */
class Contexts
{
    /**
     * @var Context[][][] $_contexts 전체 컨텍스트 정보
     */
    private static array $_contexts;

    /**
     * @var Context[] $_matches 컨텍스트 검색결과
     */
    private static array $_matches = [];

    /**
     * 전체 컨텍스트를 초기화한다.
     */
    public static function init(): void
    {
        /**
         * 전체 컨텍스트 정보를 초기화한다.
         */
        if (Cache::has('contexts') === true) {
            self::$_contexts = Cache::get('contexts');
        } else {
            $contexts = iModules::db()
                ->select()
                ->from(iModules::table('contexts'))
                ->orderBy('sort', 'asc')
                ->get();
            foreach ($contexts as $context) {
                self::$_contexts[$context->host] ??= [];
                self::$_contexts[$context->host][$context->language] ??= [];
                self::$_contexts[$context->host][$context->language][$context->path] = new Context($context);
            }

            Cache::store('contexts', self::$_contexts);
        }

        /**
         * 현재 도메인에 해당하는 컨텍스트를 경로에 추가한다.
         */
        $domain = Domains::get();

        /**
         * @var string $language 언어코드
         * @var Context[] $contexts 해당 언어코드의 전체 컨텍스트
         */
        foreach (self::$_contexts[$domain->getHost()] ?? [] as $language => $contexts) {
            foreach ($contexts as $context) {
                Router::add($context->getPath(), $language, 'context', [$context, 'getContent']);
                if ($context->isRouting() == true || $context->getType() == 'MODULE') {
                    Router::add($context->getPath() . '/*', $language, 'context', [$context, 'getContent']);
                }
            }
        }
    }

    /**
     * 특정 사이트의 전체 컨텍스트를 가져온다.
     *
     * @param Site $site
     * @return Context[] $contexts
     */
    public static function all(Site $site): array
    {
        return self::$_contexts[$site->getHost()][$site->getLanguage()] ?? [];
    }

    /**
     * 경로에 따른 컨텍스트를 가져온다.
     *
     * @param ?Route $route 경로 (NULL 인 경우 현재 경로)
     * @return Context $context
     */
    public static function get(?Route $route = null): Context
    {
        $route ??= Router::get();
        if ($route->getType() != 'context') {
            ErrorHandler::print(self::error('NOT_FOUND_CONTEXT', $route->getUrl(true)));
        }

        $contexts = self::all($route->getSite());
        if (isset($contexts[$route->getPath()]) == false) {
            ErrorHandler::print(self::error('NOT_FOUND_CONTEXT', $route->getUrl(true)));
        }

        return $contexts[$route->getPath()];
    }

    /**
     * 경로에 따른 컨텍스트를 가져온다.
     *
     * @param string $host 호스트명
     * @param string $language 언어코드
     * @param string $path 경로
     * @return ?Context $context
     */
    public static function at(string $host, string $language, string $path): ?Context
    {
        if (isset(self::$_contexts[$host][$language][$path]) == true) {
            return self::$_contexts[$host][$language][$path];
        }
        return null;
    }

    /**
     * 특정 컨텍스트를 검색한다.
     *
     * @param string $type 컨텍스트 종류
     * @param string $target 컨텍스트 대상
     * @param string $context 컨텍스트
     * @param string|int[] $requirements 필수로 일치해야하는 컨텍스트설정 ([key=>value])
     * @param string|int[] $options 일부 일치해야하는 컨텍스트설정 (일치하는 항목이 많을수록 우선 검색) ([key=>value])
     * @param bool $is_same_site 동일 사이트내에서만 검색할지 여부
     * @return Context[] $context 가장 일치하는 컨텍스트
     */
    public static function find(
        string $type,
        string $target,
        string $context,
        array $requirements = [],
        array $options = [],
        bool $is_same_site = true
    ): array {
        $site = Sites::get();
        $hash = sha1(json_encode([$type, $target, $context, $requirements, $is_same_site]));
        if (isset(self::$_matches[$hash]) == true) {
            $matches = self::$_matches[$hash];
        } else {
            $contexts = iModules::db()
                ->select(['host', 'language', 'path', 'context_configs'])
                ->from(iModules::table('contexts'))
                ->where('type', $type)
                ->where('target', $target)
                ->where('context', $context);
            if ($is_same_site == true) {
                $contexts->where('host', $site->getHost())->where('language', $site->getLanguage());
            }
            $contexts = $contexts->get();

            $matches = [];
            foreach ($contexts as &$context) {
                $context->sort = 0;
                $context->context_configs = json_decode($context->context_configs);

                $matches[] = $context;

                /**
                 * 컨텍스트 설정 중 필수로 일치해야하는 컨텍스트만 검색한다.
                 */
                foreach ($requirements as $key => $value) {
                    if (
                        isset($context->context_configs?->{$key}) == false ||
                        $context->context_configs?->{$key} != $value
                    ) {
                        array_pop($matches);
                        break;
                    }
                }
            }

            self::$_matches[$hash] = $matches;
        }

        /**
         * 필수가 아닌 설정이 일치하는 경우 검색순위의 가중치를 부여한다.
         */
        foreach ($matches as &$match) {
            /**
             * 같은 도메인 경우 가중치를 100 만큼 부여한다.
             */
            if ($match->host == $site->getHost()) {
                $match->sort += 100;
            }

            /**
             * 같은 언어인 경우 가중치를 10만큼 부여한다.
             */
            if ($match->language == $site->getLanguage()) {
                $match->sort += 10;
            }

            /**
             * 옵션이 일치할 때마다 가중치를 1만큼 부여한다.
             */
            foreach ($options as $key => $value) {
                if (isset($match->context_configs?->{$key}) == true && $match->context_configs?->{$key} == $value) {
                    $match->sort++;
                }
            }
        }

        /**
         * 가중치가 높은 컨텍스트순으로 정렬한다.
         */
        usort($matches, function ($left, $right) {
            return $right->sort <=> $left->sort;
        });

        foreach ($matches as &$match) {
            $match = self::$_contexts[$match->host][$match->language][$match->path];
        }

        return $matches;
    }

    /**
     * 특정 컨텍스트 한개만 검색한다.
     *
     * @param string $type 컨텍스트 종류
     * @param string $target 컨텍스트 대상
     * @param string $context 컨텍스트
     * @param string|int[] $requirements 필수로 일치해야하는 컨텍스트설정 ([key=>value])
     * @param string|int[] $options 일부 일치해야하는 컨텍스트설정 (일치하는 항목이 많을수록 우선 검색) ([key=>value])
     * @param bool $is_same_site 동일 사이트내에서만 검색할지 여부
     * @return Context? $context 가장 일치하는 컨텍스트
     */
    public static function findOne(
        string $type,
        string $target,
        string $context,
        array $requirements = [],
        array $options = [],
        bool $is_same_site = true
    ): ?Context {
        $matches = self::find($type, $target, $context, $requirements, $options, $is_same_site);
        return count($matches) > 0 ? $matches[0] : null;
    }

    /**
     * 컨텍스트 관련 에러를 처리한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public static function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_CONTEXT':
                $error = ErrorHandler::data();
                $error->message = ErrorHandler::getText($code);
                $error->suffix = $message;
                $error->stacktrace = ErrorHandler::trace('Contexts');
                return $error;

            default:
                return iModules::error($code, $message, $details);
        }
    }
}

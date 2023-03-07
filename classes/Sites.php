<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 전체 사이트 데이터를 처리한다.
 *
 * @file /classes/Sites.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 3. 7.
 */
class Sites
{
    /**
     * @var Site[][] $_sites 전체 사이트 정보
     */
    private static array $_sites;

    /**
     * 전체 사이트를 초기화한다.
     */
    public static function init(): void
    {
        /**
         * 사이트 정보를 초기화한다.
         */
        if (Cache::has('sites') === true) {
            self::$_sites = Cache::get('sites');
        } else {
            $sites = iModules::db()
                ->select()
                ->from(iModules::table('sites'))
                ->get();
            foreach ($sites as $site) {
                self::$_sites[$site->host] ??= [];
                self::$_sites[$site->host][$site->language] = new Site($site);
            }

            Cache::store('sites', self::$_sites);
        }
    }

    /**
     * 특정 사이트정보를 가져온다.
     *
     * @param ?string $host 사이트 호스트명 (없을 경우 현재 호스트)
     * @param ?string $language 사이트 언어 (없을 경우 현재 언어)
     * @return Site $site
     */
    public static function get(?string $host = null, ?string $language = null): Site
    {
        $site = self::has($host, $language);
        if ($site === null) {
            ErrorHandler::print(self::error('NOT_FOUND_SITE'), $host . '/' . $language);
        }

        return $site;
    }

    /**
     * 전체 사이트정보를 가져온다.
     *
     * @return Site[] $sites
     */
    public static function all(?string $host = null): array
    {
        if (isset(self::$_sites) == false) {
            self::init();
        }

        if ($host !== null) {
            return isset(self::$_sites[$host]) == true ? array_values(self::$_sites[$host]) : [];
        }

        $sites = [];
        foreach (self::$_sites as $languages) {
            $sites = array_merge($sites, array_values($languages));
        }

        return $sites;
    }

    /**
     * 특정 사이트정보가 존재한다면 가져온다.
     *
     * @param ?string $host 사이트 호스트명 (없을 경우 현재 호스트)
     * @param ?string $language 사이트 언어 (없을 경우 현재 언어)
     * @return ?Site $site
     */
    public static function has(?string $host = null, ?string $language = null): ?Site
    {
        if (isset(self::$_sites) == false) {
            self::init();
        }

        $domain = Domains::get($host);
        $host = $domain->getHost();
        $language ??= Router::getLanguage();

        if (isset(self::$_sites[$host][$language]) == true) {
            return self::$_sites[$host][$language];
        }

        return null;
    }

    /**
     * 사이트 관련 에러를 처리한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public static function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_SITE':
                $error = ErrorHandler::data();
                $error->message = ErrorHandler::getText($code);
                $error->suffix = $message;
                return $error;

            default:
                return iModules::error($code, $message, $details);
        }
    }
}

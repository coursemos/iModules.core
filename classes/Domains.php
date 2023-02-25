<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 전체 도메인 데이터를 처리한다.
 *
 * @file /classes/Domains.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 2. 25.
 */
class Domains
{
    /**
     * @var Domain[] $_domains 전체 도메인 정보
     */
    private static array $_domains;

    /**
     * 전체 도메인을 초기화한다.
     */
    public static function init(): void
    {
        /**
         * 도메인 정보를 초기화한다.
         */
        if (Cache::has('domains') === true) {
            self::$_domains = Cache::get('domains');
        } else {
            $domains = iModules::db()
                ->select()
                ->from(iModules::table('domains'))
                ->orderBy('sort', 'asc')
                ->get();
            foreach ($domains as $domain) {
                self::$_domains[$domain->host] = new Domain($domain);
            }

            Cache::store('domains', self::$_domains);
        }
    }

    /**
     * 전체 도메인정보를 가져온다.
     *
     * @return Domain[] $domains
     */
    public static function all(): array
    {
        if (isset(self::$_domains) == false) {
            self::init();
        }

        return self::$_domains;
    }

    /**
     * 특정 도메인정보를 가져온다.
     *
     * @param ?string $host 도메인 호스트명 (없을 경우 현재 호스트)
     * @return Domain $domain
     */
    public static function get(?string $host = null): Domain
    {
        $domain = self::has($host);
        if ($domain === null) {
            ErrorHandler::print(self::error('NOT_FOUND_DOMAIN', $host));
        }

        return $domain;
    }

    /**
     * 특정 도메인정보가 존재한다면 가져온다.
     *
     * @param ?string $host 도메인 호스트명 (없을 경우 현재 호스트)
     * @return ?Domain $domain
     */
    public static function has(?string $host = null): ?Domain
    {
        if (isset(self::$_domains) == false) {
            self::init();
        }

        $host ??= $_SERVER['HTTP_HOST'];
        if (isset(self::$_domains[$host]) == true) {
            return self::$_domains[$host];
        }

        /**
         * 특정 호스트를 가진 도메인정보가 없는 경우 도메인의 별칭에서 일치하는 도메인이 있는지 검색한다.
         */
        foreach (self::$_domains as $domain) {
            foreach ($domain->getAlias() as $alias) {
                $alias = str_replace('\*', '[^\.]+', Format::reg($alias));
                if (preg_match('/' . $alias . '/i', $host) == true) {
                    return $domain;
                }
            }
        }

        return null;
    }

    /**
     * 도메인 관련 에러를 처리한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public static function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        switch ($code) {
            case 'NOT_FOUND_DOMAIN':
                $error = ErrorHandler::data();
                $error->message = ErrorHandler::getText($code);
                $error->suffix = $message;

                return $error;

            default:
                return iModules::error($code, $message, $details);
        }
    }
}

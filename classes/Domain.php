<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 각 도메인별 데이터 구조체를 정의한다.
 *
 * @file /classes/Domain.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 8. 20.
 */
class Domain
{
    /**
     * @var string $_host 호스트명
     */
    private string $_host;

    /**
     * @var string[] $_alias 별칭호스팅
     */
    private array $_alias;

    /**
     * @var string $_language 기본 언어코드
     */
    private string $_language;

    /**
     * @var Site[] 도메인 하위 사이트
     */
    private array $_sites;

    /**
     * @var bool $_is_https HTTPS 사용여부
     */
    private bool $_is_https;

    /**
     * @var bool $_is_https 짧은 주소 사용여부
     */
    private bool $_is_rewrite;

    /**
     * @var bool $_is_internationalization 다국어 사이트 여부
     */
    private bool $_is_internationalization;

    /**
     * 도메인 데이터 구조체를 정의한다.
     *
     * @param object $domain 도메인정보
     */
    public function __construct(object $domain)
    {
        $this->_host = $domain->host;
        $this->_alias = $domain->alias ? explode(',', $domain->alias) : [];
        $this->_language = $domain->language;
        $this->_is_https = $domain->is_https == 'TRUE';
        $this->_is_rewrite = $domain->is_rewrite == 'TRUE';
        $this->_is_internationalization = $domain->is_internationalization == 'TRUE';
    }

    /**
     * 도메인 호스트를 가져온다.
     *
     * @return string $host
     */
    public function getHost(): string
    {
        return $this->_host;
    }

    /**
     * 도메인 별칭호스트를 가져온다.
     *
     * @return array $alias
     */
    public function getAlias(): array
    {
        return $this->_alias;
    }

    /**
     * 도메인 기본 언어코드를 가져온다.
     *
     * @return string $language
     */
    public function getLanguage(): string
    {
        return $this->_language;
    }

    /**
     * HTTPS 사용여부를 가져온다.
     *
     * @return bool $is_https
     */
    public function isHttps(): bool
    {
        return $this->_is_https;
    }

    /**
     * 짧은주소(rewrite) 사용여부를 가져온다.
     *
     * @return bool $is_rewrite
     */
    public function isRewrite(): bool
    {
        return $this->_is_rewrite;
    }

    /**
     * 다국어 여부를 가져온다.
     *
     * @return bool $is_internationalization
     */
    public function isInternationalization(): bool
    {
        return $this->_is_internationalization;
    }

    /**
     * 도메인 하위 전체 사이트를 가져온다.
     *
     * @return Site[] $sites
     */
    public function getSites(): array
    {
        if (isset($this->_sites) == true) {
            return $this->_sites;
        }

        $this->_sites = [];
        $sites = Sites::all($this->_host);
        foreach ($sites as $site) {
            $this->_sites[$site->getLanguage()] = $site;
        }

        return $this->_sites;
    }

    /**
     * 도메인의 특정 언어에 해당하는 사이트를 가져온다.
     *
     * @param ?string $language 언어코드(NULL 인 경우 도메인의 기본언어 사이트)
     * @return Site $site
     */
    public function getSite(?string $language = null): Site
    {
        $sites = $this->getSites();
        $language ??= $this->_language;
        return isset($sites[$language]) == true ? $sites[$language] : $sites[$this->_language];
    }

    /**
     * 도메인 하위 사이트의 전체 언어코드를 가져온다.
     *
     * @return string[] $languages
     */
    public function getLanguages(): array
    {
        $sites = $this->getSites();
        return array_keys($sites);
    }

    /**
     * 도메인 주소를 가져온다.
     *
     * @return string $url
     */
    public function getUrl(bool $include_dir = false): string
    {
        return ($this->isHttps() == true ? 'https://' : 'http://') .
            $this->getHost() .
            ($include_dir == true ? Configs::dir() : '');
    }
}

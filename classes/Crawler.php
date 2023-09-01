<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 웹 컨텐츠를 크롤링하기 위한 클래스를 정의한다.
 *
 * @file /classes/Crawler.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 8. 21.
 */
class Crawler
{
    /**
     * @var string $_agent 사용자 에이전트
     */
    private string $_agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/66.0.3359.117 Safari/537.36';

    /**
     * @var ?string $_referer HTTP_REFERER 주소
     */
    private ?string $_referer = null;

    /**
     * @var ?string $_cookies 쿠키데이터
     */
    private ?string $_cookies = null;

    /**
     * @var int $_timeout 타임아웃(초)
     */
    private int $_timeout = 60;

    /**
     * HTTP_REFERER 를 설정한다.
     *
     * @param ?string $referer
     * @return Crawler $this
     */
    public function setReferer(?string $referer): Crawler
    {
        $this->_referer = $referer;
        return $this;
    }

    /**
     * USER_AGENT 를 설정한다.
     *
     * @param string $agent
     * @return $this
     */
    public function setAgent(string $agent): Crawler
    {
        $this->_agent = $agent;
        return $this;
    }

    /**
     * 쿠키데이터를 가져온다.
     *
     * @return ?string $cookies
     */
    public function getCookies(): ?string
    {
        return $this->_cookies;
    }

    /**
     * 쿠키데이터를 설정한다.
     *
     * @param ?string $cookies
     * @return $this
     */
    public function setCookies(?string $cookies = null): Crawler
    {
        $this->_cookies = $cookies;
        return $this;
    }

    public function setTimeout(int $timeout = 60): Crawler
    {
        $this->_timeout = $timeout;
        return $this;
    }

    /**
     * 로그인을 처리한다.
     *
     * @param string $url 로그인이 처리되는 주소 (예 : http://domain.com/login.php)
     * @param array $params 로그인에 필요한 변수 (예 : array('user_id'=>'아이디','password'=>'패스워드')
     * @return bool $success
     */
    public function login(string $url, array $params = []): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);

        if (preg_match_all('/Set-Cookie: (.*);/i', $data, $matches) == true) {
            $this->_cookies = implode('; ', $matches[1]);
        } else {
            $this->_cookies = null;
            return false;
        }

        return true;
    }

    /**
     * URL 의 컨텐츠를 가져온다. (GET 방식)
     *
     * @param string $url 컨텐츠를 가져올 URL 주소
     * @return string $content 컨텐츠
     */
    public function getUrl(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);
        if ($this->_referer != null) {
            curl_setopt($ch, CURLOPT_REFERER, $this->_referer);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($this->_cookies !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->_cookies);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $buffer = curl_exec($ch);
        $cinfo = curl_getinfo($ch);
        curl_close($ch);

        if ($cinfo['http_code'] != 200) {
            return null;
        } else {
            return $buffer;
        }
    }

    /**
     * URL 의 컨텐츠를 가져온다. (POST 방식)
     *
     * @param string $url 컨텐츠를 가져올 URL 주소
     * @return string $content 컨텐츠
     */
    public function postUrl(string $url, array $data = []): ?string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);
        if ($this->_referer != null) {
            curl_setopt($ch, CURLOPT_REFERER, $this->_referer);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($this->_cookies !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->_cookies);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $buffer = curl_exec($ch);
        $cinfo = curl_getinfo($ch);
        curl_close($ch);

        if ($cinfo['http_code'] != 200) {
            return null;
        } else {
            return $buffer;
        }
    }

    /**
     * URL 경로상의 내용을 파일로 저장한다.
     *
     * @param string $url 컨텐츠를 가져올 URL 주소
     * @param string $path 저장될 파일 경로
     * @return bool $success
     */
    public function saveFile(string $url, string $path): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->_agent);
        if ($this->_referer != null) {
            curl_setopt($ch, CURLOPT_REFERER, $this->_referer);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 3600);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        if ($this->_cookies !== null) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->_cookies);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $buffer = curl_exec($ch);
        $cinfo = curl_getinfo($ch);
        curl_close($ch);

        if ($cinfo['http_code'] != 200 || preg_match('/text\/html/', $cinfo['content_type']) == true) {
            return false;
        }

        $success = @file_put_contents($path, $buffer);

        if ($success === false) {
            return false;
        }

        if (is_file($path) == false || filesize($path) == 0) {
            @unlink($path);
            return false;
        }

        return true;
    }

    /**
     * 웹페이지 컨텐츠의 캐릭터셋을 UTF-8로 변경한다.
     *
     * @param string $origin 원본 컨텐츠
     * @return string $utf8 UTF-8 컨텐츠
     */
    public function getUTF8(string $origin): string
    {
        /**
         * 메타태그를 찾아 원본 컨텐츠의 캐릭터셋을 파악한다.
         */
        if (preg_match('/<meta(.*?)charset=("|\')?(.*?)("|\')(.*?)>/i', $origin, $match) == true) {
            if (strpos(strtoupper($match[3]), 'UTF') === 0) {
                return $origin;
            }

            $originEncode = strtoupper($match[3]);
        } else {
            /**
             * 메타태그에서 캐릭터셋을 파악하지 못하였을 경우
             */
            if (function_exists('mb_detect_encoding') == false) {
                return $origin;
            }

            $originEncode = mb_detect_encoding($origin, 'EUC-KR,UTF-8,ASCII,EUC-JP,CP949,AUTO');
        }

        if ($originEncode == 'UTF-8' || $originEncode == '') {
            return $origin;
        } else {
            return @iconv($originEncode, 'UTF-8//IGNORE', $origin);
        }
    }
}

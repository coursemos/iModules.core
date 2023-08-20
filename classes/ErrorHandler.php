<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 에러 클래스를 정의한다.
 *
 * @file /classes/ErrorHandler.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 11.
 */
class ErrorHandler
{
    /**
     * 에러 클래스를 정의한다.
     */
    public static function init()
    {
        /**
         * PHP 에러를 처리하기 위한 핸들러를 선언한다.
         */
        error_reporting(E_ALL);
        ini_set('display_errors', true);
        register_shutdown_function(['ErrorHandler', 'shutdownHandler']);
        set_error_handler(['ErrorHandler', 'errorHandler'], E_ALL);
    }

    /**
     * 언어팩 에러코드 문자열을 가져온다.
     *
     * @param string $error 에러코드
     * @param ?array $placeHolder 치환자
     * @return string $message 치환된 메시지
     */
    public static function getText(string $error, ?array $placeHolder = null): string
    {
        return Language::getErrorText($error, $placeHolder, null, Request::languages());
    }

    /**
     * HTTP 에러코드를 지정한다.
     *
     * @param int $code HTTP 에러코드
     */
    public static function code(int $code): void
    {
        Header::code($code);
    }

    /**
     * 에러메시지를 가져온다.
     *
     * @param string|ErrorData $code 에러코드 또는 에러 객체
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return string $html
     */
    public static function get(string|ErrorData $code, ?string $message = null, ?object $details = null): string
    {
        $error = is_string($code) == true ? self::error($code, $message, $details) : $code;
        $error->debugMode = Configs::debug();

        Html::style(Configs::dir() . '/styles/error.scss');

        /**
         * $error->stacktrace 가 NULL 인 경우
         */
        if ($error->stacktrace === null) {
            $error->stacktrace = self::trace();
        }

        if (count($error->stacktrace) > 0 && $error->file === null) {
            $error->file = $error->stacktrace[0]->file;
            $error->line = $error->stacktrace[0]->line;
        }

        /**
         * 디버그모드가 아닌경우 사용자 친화적인 에러메시지로 변경한다.
         */
        if ($error->debugModeOnly == true && $error->debugMode == false) {
            $error->prefix = null;
            $error->message = self::getText('DESCRIPTION');
            $error->suffix = self::getText('DESCRIPTION_FOOTER');
        }

        /**
         * 기본 자바스크립트파일을 불러온다.
         * 사용되는 모든 스크립트 파일을 캐시를 이용해 압축한다.
         */
        Cache::script('common', '/scripts/Html.js');
        Cache::script('common', '/scripts/Dom.js');
        Cache::script('common', '/scripts/DomList.js');
        Html::script(Cache::script('common'), 1);

        Html::font('Pretendard');
        Html::font('moimz');

        ob_start();
        include Configs::path() . '/includes/error.html';
        $html = ob_get_clean();

        return $html;
    }

    /**
     * 모든 작업을 중단하고, 에러메시지를 출력한다.
     *
     * @param string|object $code 에러코드 또는 에러 객체
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     */
    public static function print(string|ErrorData $code, ?string $message = null, ?object $details = null): void
    {
        if (ob_get_length() !== false) {
            ob_end_clean();
        }

        if (Header::type() == 'json') {
            $error = is_string($code) == true ? self::error($code, $message, $details) : $code;

            $json = new stdClass();
            $json->success = false;
            $json->message = [];
            if ($error->prefix) {
                $json->message[] = $error->prefix;
            }
            if ($error->message) {
                $json->message[] = $error->message;
            }
            if ($error->suffix) {
                $json->message[] = $error->suffix;
            }

            $json->message = implode('<br>', $json->message);

            if (Configs::debug() == true) {
                /**
                 * $error->stacktrace 가 NULL 인 경우
                 */
                if ($error->stacktrace === null) {
                    $error->stacktrace = self::trace();
                }

                if (count($error->stacktrace) > 0 && $error->file === null) {
                    $error->file = $error->stacktrace[0]->file;
                    $error->line = $error->stacktrace[0]->line;
                }

                $json->file = $error->file;
                $json->line = $error->line;
                $json->stacktrace = $error->stacktrace;
            } else {
                if ($error->debugModeOnly == true && $error->debugMode == false) {
                    $json->message = self::getText('DESCRIPTION') . '<br>' . self::getText('DESCRIPTION_FOOTER');
                }
            }

            exit(Format::toJson($json));
        } else {
            $error = self::get($code, $message, $details);

            Html::title(self::getText('TITLE'));
            Html::body('data-type', 'error');

            exit(Html::tag(Html::header(), $error, Html::footer()));
        }
    }

    /**
     * debug_backtrace() 의 각 항목의 데이터를 정리한다.
     *
     * @param ?string $endpoint 디버깅을 종료할 클래스명 (없을 경우 ErrorHandler)
     * @param ?array $stacktrace stacktrace 데이터
     * @return array $trace
     */
    public static function trace(?string $endpoint = null, ?array $stacktrace = null): array
    {
        $endpoint ??= 'ErrorHandler';
        $traces = [];

        $is_stacked = $stacktrace !== null;
        $stacktrace ??= debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

        for ($i = 0, $loop = count($stacktrace); $i < $loop; $i++) {
            $trace = new stdClass();
            $trace->caller =
                isset($stacktrace[$i]) == true && isset($stacktrace[$i]['class']) == true
                    ? $stacktrace[$i]['class'] . $stacktrace[$i]['type']
                    : '';
            $trace->caller .=
                isset($stacktrace[$i]) == true && isset($stacktrace[$i]['function']) == true
                    ? $stacktrace[$i]['function'] . '()'
                    : '';
            $trace->method =
                isset($stacktrace[$i + 1]) == true && isset($stacktrace[$i + 1]['class']) == true
                    ? $stacktrace[$i + 1]['class'] . $stacktrace[$i + 1]['type']
                    : '';
            $trace->method .=
                isset($stacktrace[$i + 1]) == true && isset($stacktrace[$i + 1]['function']) == true
                    ? $stacktrace[$i + 1]['function'] . '()'
                    : '';
            $trace->file = isset($stacktrace[$i]['file']) == true ? $stacktrace[$i]['file'] : null;
            $trace->line = isset($stacktrace[$i]['line']) == true ? $stacktrace[$i]['line'] : null;
            $trace->method =
                strlen($trace->method) > 0
                    ? $trace->method
                    : ($trace->file !== null
                        ? basename($trace->file)
                        : 'Unknown');

            if (str_starts_with($trace->method, $endpoint) == true) {
                $is_stacked = true;
                continue;
            }

            if ($endpoint == 'ErrorHandler' && $trace->caller == 'ErrorHandler::trace()') {
                $is_stacked = true;
                continue;
            }

            if ($is_stacked == true) {
                $trace->lines = $trace->file === null ? [] : self::readFileLine($trace->file, $trace->line - 20, 41);
                $traces[] = $trace;
            }
        }

        return $traces;
    }

    /**
     * 빈 에러데이터 객체를 가져온다.
     *
     * @return ErrorData $error
     */
    public static function data(): ErrorData
    {
        $error = new ErrorData(self::getText('TITLE'), self::getText('DESCRIPTION'));
        return $error;
    }

    /**
     * 에러데이터를 이용해 에러페이지를 출력하기 위한 데이터를 가공한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return ErrorData $error
     */
    public static function error(string $code, ?string $message = null, ?object $details = null): ErrorData
    {
        $error = self::data();

        switch ($code) {
            case 'PHP_ERROR':
                $constances = [
                    E_ERROR => 'FATAL ERROR',
                    E_CORE_ERROR => 'FATAL ERROR',
                    E_COMPILE_ERROR => 'FATAL ERROR',
                    E_PARSE => 'FATAL ERROR',
                    E_WARNING => 'WARNING',
                    E_NOTICE => 'NOTICE ERROR',
                ];

                $error->prefix = self::getText('PHP_ERROR');
                $error->message = ($constances[$details->no] ?? 'UNKNOWN ERROR') . ' : ' . nl2br($message);
                $error->suffix = '<u>' . $details?->file . '</u> on line <b>' . $details->line . '</b>';
                $error->file = $details->file;
                $error->line = $details?->line;

                /**
                 * FATAL 에러인 경우, message 에 stack trace 가 존재하는 경우 외에 stacktrace 를 비운다.
                 */
                if (in_array($details->no, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]) == true) {
                    $stacktrace = [];
                    $temp = explode('Stack trace:', $message);
                    if (count($temp) == 2) {
                        if (
                            preg_match_all(
                                '/#([0-9]+) (.*?)\(([0-9]+)\): (.*?)(::|->)+(.*?)\((.*?)\)/',
                                $temp[1],
                                $matches,
                                PREG_SET_ORDER
                            ) == true
                        ) {
                            foreach ($matches as $match) {
                                $item = [];
                                $item['file'] = $match[2];
                                $item['line'] = $match[3];
                                if (count($match) > 4) {
                                    $item['class'] = $match[4];
                                    $item['type'] = $match[5];
                                    $item['function'] = $match[6];
                                } else {
                                    $item['function'] = $match[4];
                                }

                                $stacktrace[] = $item;
                            }
                        }
                    }

                    if (count($stacktrace) > 0) {
                        $error->message =
                            ($constances[$details->no] ?? 'UNKNOWN ERROR') . ' : ' . nl2br(trim($temp[0]));
                    }

                    $item = [];
                    $item['file'] = $details->file;
                    $item['line'] = $details->line;
                    array_unshift($stacktrace, $item);

                    $error->stacktrace = self::trace(null, $stacktrace);
                } else {
                    $error->stacktrace = self::trace();
                }
                $error->debugModeOnly = true;

                break;

            case 'DATABASE_ERROR':
                $error->prefix = self::getText('DATABASE_ERROR');
                $error->message = $message;
                $error->suffix = $details->query;
                $error->stacktrace = self::trace($details->type);
                $error->debugModeOnly = true;

                break;

            case 'DATABASE_CONNECT_ERROR':
                $error->prefix = self::getText('DATABASE_CONNECT_ERROR');
                $error->message = $message;
                $error->stacktrace = self::trace('Database');
                $error->debugModeOnly = true;

                break;

            default:
                $error->prefix = self::getText($code);
                if ($message !== null) {
                    $error->message = $message;
                }
        }

        return $error;
    }

    /**
     * 파일의 내용을 선택한 라인부터 읽어온다.
     *
     * @param string $filename 파일명
     * @param int $start 읽어올 라인 (0일 경우 파일 시작)
     * @param int $limit 읽을 라인수 (0일 경우 파일 끝)
     * @return array $lines
     */
    public static function readFileLine(string $filename, int $start = 0, int $limit = 0): array
    {
        if (is_file($filename) === false) {
            return [];
        }

        $start = max($start, 0);
        $lines = [];
        $file = file($filename);
        while (isset($file[$start]) == true && count($lines) < $limit) {
            $lines[$start] = $file[$start++];
        }

        return $lines;
    }

    /**
     * PHP shutdown_handler 를 정의한다.
     * @see register_shutdown_function
     */
    public static function shutdownHandler(): void
    {
        $error = error_get_last();
        if ($error !== null) {
            self::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
            exit();
        }
    }

    /**
     * PHP error_handler 를 정의한다.
     * @see set_error_handler
     */
    public static function errorHandler(int $errno, string $errstr, ?string $errfile = null, ?int $errline = null): bool
    {
        if (ob_get_length() !== false) {
            ob_end_clean();
        }

        $details = new stdClass();
        $details->no = $errno;
        $details->file = $errfile;
        $details->line = $errline;

        self::print('PHP_ERROR', $errstr, $details);
        return true;
    }
}

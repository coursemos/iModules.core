<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 에러데이터를 정의한다.
 *
 * @file /classes/ErrorData.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 1. 26.
 */
class ErrorData
{
    /**
     * @public string $title 에러명
     */
    public string $title;

    /**
     * @public string $message 에러메시지
     */
    public string $message;

    /**
     * @public ?string $prefix 에러메시지 상단 내용
     */
    public ?string $prefix = null;

    /**
     * @public ?string $suffix 에러메시지 하단 상세내용
     */
    public ?string $suffix = null;

    /**
     * @public ?string $file 에러가 발생한 파일명
     */
    public ?string $file = null;

    /**
     * @public ?int $line 에러가 발생한 LINE
     */
    public ?int $line = null;

    /**
     * @public ?array $stacktrace 에러발생 추적데이터
     */
    public ?array $stacktrace = null;

    /**
     * @public bool $debugModeOnly 디버그모드에서만 상세 에러메시지를 보여줄지 여부
     */
    public bool $debugModeOnly = false;

    /**
     * @public bool $debugMode 현재 디버그모드인지 여부
     */
    public bool $debugMode = false;

    /**
     * 에러데이터를 정의한다.
     *
     * @param string $title 에러명
     * @param ?string $message 에러메시지
     */
    public function __construct(string $title, ?string $message = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->debugMode = Configs::debug();
    }
}

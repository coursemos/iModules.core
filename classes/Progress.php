<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 응답이 오래걸리는 요청을 처리하는 중 프로그래스바를 구현하기 위한 클래스를 정의한다.
 *
 * @file /classes/Progress.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 5. 2.
 */
class Progress
{
    private int $_total;
    private int $_progress;
    private int $_buffer = 10000;
    private ?object $_latest = null;

    /**
     * 프로그래스 클래스를 정의한다.
     *
     * @param int $total 완료갯수
     */
    function __construct(int $total = 100)
    {
        $this->_total = $total;
        $this->_progress = 0;

        iModules::session_stop();

        if (headers_sent() == false) {
            set_time_limit(0);

            @ini_set('memory_limit', -1);
            @ini_set('zlib.output_compression', 'Off');
            @ini_set('output_buffering', 'Off');
            @ini_set('output_handler', '');
            @ini_set('max_execution_time', 0);

            if (function_exists('apache_setenv') == true) {
                @apache_setenv('no-gzip', 1);
            }

            Header::type('text');
            Header::length($this->_buffer * 201);

            header('Content-Encoding: none');
            header('X-Accel-Buffering: no');
            header('X-Progress-Total:' . $this->_total);
        }
    }

    /**
     * 데이터 우측에 버퍼용량 초과를 위한 빈문자열을 포함하여 문자열을 생성한다.
     *
     * @param object $output 변환할 데이터
     * @return string $text
     */
    public function pad(object $output, bool $is_last = false): string
    {
        $json = json_encode($output, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return str_pad($json, $this->_buffer - ($is_last == true ? 2 : 1)) . ($is_last == true ? "\n" . '@' : "\n");
    }

    /**
     * 프로그래스바를 위해 데이터를 출력한다.
     *
     * @param int $current 현재갯수
     * @param array|object $data 전달할 데이터
     */
    public function progress(int $current, array|object $data = null): void
    {
        if ($this->_total == 0) {
            $progress = 200;
        } else {
            $progress = min(200, round(($current / $this->_total) * 200));
        }
        if ($progress != $this->_progress) {
            $output = new stdClass();
            $output->current = $current;
            $output->total = $this->_total;
            $output->data = $data;

            $this->_latest = $output;

            for ($i = $this->_progress; $i < $progress; $i++) {
                echo $this->pad($this->_latest);
                $this->_progress = $i + 1;
                flush();
            }
        }
    }

    /**
     * 프로그래스를 종료한다.
     *
     * @param bool $is_exit PHP 실행도 함께 중단할 지 여부
     */
    public function end(bool $is_exit = false): void
    {
        if (isset($this->_latest) == false) {
            $output = new stdClass();
            $output->current = 0;
            $output->total = $this->_total;
            $output->data = null;
            $this->_latest = $output;
        }

        for ($i = $this->_progress; $i < 200; $i++) {
            echo $this->pad($this->_latest);
            flush();
        }

        sleep(1);

        $this->_progress = 200;

        $end = $this->_latest;
        $end->current = $this->_total;
        $end->end = true;
        echo $this->pad($end, true);
        flush();

        if ($is_exit == true) {
            exit();
        }
    }
}

<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 파일 및 폴더를 관리하는 클래스를 정의한다.
 *
 * @file /classes/File.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 */
class File
{
    /**
     * 파일에 데이터를 기록한다.
     *
     * @param string $path 파일경로
     * @param mixed $data 파일에 기록할 내용
     * @param bool $is_append 기존 내용에 추가할지 여부(FALSE 인 경우 파일을 새로 작성한다.)
     * @return bool $success
     */
    public static function write(string $path, mixed $data = '', bool $is_append = false): bool
    {
        if (is_file($path) == true) {
            if (is_writable($path) == false) {
                return false;
            }
        } else {
            if (is_writable(dirname($path)) == false) {
                return false;
            }
        }

        if ($is_append == true) {
            $result = file_put_contents($path, $data, FILE_APPEND);
        } else {
            $result = file_put_contents($path, $data);
        }

        return $result !== false;
    }

    /**
     * 파일 데이터를 읽는다.
     *
     * @param string $path 파일경로
     * @return mixed $data 파일데이터
     */
    public static function read(string $path): mixed
    {
        return file_get_contents($path);
    }

    /**
     * 파일 해시값을 구한다.
     *
     * @param string $path 파일경로
     * @return string|bool $hash 파일해시
     */
    public static function hash(string $path): string|bool
    {
        return md5_file($path);
    }

    /**
     * 파일을 삭제한다.
     *
     * @param string $path 파일경로
     * @return bool $success
     */
    public static function remove(string $path): bool
    {
        if (is_file($path) == false) {
            return true;
        }

        if (is_writable($path) == true) {
            return unlink($path) !== false;
        }

        return false;
    }

    /**
     * 디렉토리 구성요소를 가져온다.
     *
     * @param string $path 경로
     * @param string $type 가져올 종류 (all : 전체, directory : 디렉토리, file : 파일)
     * @param boolean $is_included_children 내부폴더 탐색여부
     * @return string[] $items
     */
    public static function getDirectoryItems(
        string $path,
        string $type = 'all',
        bool $is_included_children = false
    ): array {
        $path = realpath($path);
        if (is_dir($path) == false) {
            return [];
        }

        $items = [];
        foreach (scandir($path) as $item) {
            if (in_array($item, ['.', '..']) == true) {
                continue;
            }

            $item = $path . '/' . $item;
            if (is_dir($item) == true) {
                if (in_array($type, ['all', 'directory']) == true) {
                    $items[] = $item;
                }

                if ($is_included_children == true) {
                    $items = array_merge($items, self::getDirectoryItems($item, $type, $is_included_children));
                }
            } else {
                if (in_array($type, ['all', 'file']) == true) {
                    $items[] = $item;
                }
            }
        }

        return $items;
    }

    /**
     * 디렉토리내 구성요소의 마지막 수정시간을 가져온다.
     *
     * @param string $path 경로
     * @return int $unixtime 수정시간
     */
    public static function getDirectoryLastModified(string $path): int
    {
        $files = self::getDirectoryItems($path, 'file', true);
        $lastModified = 0;
        foreach ($files as $file) {
            $modified = filemtime($file);
            $lastModified = $lastModified < $modified ? $modified : $lastModified;
        }

        return $lastModified;
    }

    /**
     * 폴더의 용량을 구한다.
     *
     * @param string $path 폴더
     * @return int $size 폴더용량
     */
    public static function getDirectorySize(string $path): int
    {
        $files = self::getDirectoryItems($path, 'file', true);
        $size = 0;
        foreach ($files as $file) {
            $size += filesize($file);
        }
        return $size;
    }

    /**
     * 파일을 특정 변수와 함께 인클루드한다.
     *
     * @param string $__path 파일경로
     * @param array $values 인클루드하는 파일에서 사용할 변수
     */
    public static function include(string $__path, array $values = []): void
    {
        if (is_file($__path) == true) {
            /**
             * 삽입할 파일에서 사용할 변수선언
             */
            extract($values);
            include $__path;
        }
    }
}

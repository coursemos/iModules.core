<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * 권한문자열을 해석하여 권한을 확인한다.
 *
 * @file /classes/Permission.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 10. 21.
 */
class Permission
{
    /**
     * @var Permission[] $_permissions 권한표현식별 권한클래스를 저장한다.
     */
    private static array $_permissions = [];

    /**
     * @var string $_expression 전체권한표현식
     */
    private string $_expression;

    /**
     * @var string[] $_brackets 권한표현식에 괄호가 있다면 각 괄호별 표현식
     */
    private array $_brackets = [];

    /**
     * @var bool|string $_isValid 권한표현식 유효성여부
     */
    private bool|string $_isValid;

    /**
     * @var bool $_permission 계산된 권한여부
     */
    private bool $_permission;

    /**
     * 권한클래스를 정의한다.
     *
     * @param string $expression 권한표현식
     */
    public function __construct(string $expression)
    {
        $this->_expression = $expression;
    }

    /**
     * 권한클래슬르 가져온다.
     *
     * @param string $expression 권한표현식
     * @return Permission $permission
     */
    public static function get(string $expression): Permission
    {
        $expression = trim($expression);
        if (isset(self::$_permissions[$expression]) == false) {
            self::$_permissions[$expression] = new Permission($expression);
        }

        return self::$_permissions[$expression];
    }

    /**
     * 권한표현식이 올바른지 확인한다.
     *
     * @return bool|string $isValid 유효성여부 (true 가 아닌 경우 오류메시지)
     */
    public function isValid(): bool|string
    {
        if (isset($this->_isValid) == false) {
            $in_quotes = null; // 따옴표가 시작되었다면 현재 따옴표 여부 ' 또는 "
            $uuid = null; // 괄호가 시작되었다면, 괄호의 고유값
            $stack = []; // 중첩괄호 처리를 위한 스택

            for ($i = 0; $i < strlen($this->_expression); $i++) {
                $char = $this->_expression[$i];

                // 이스케이프 문자 처리 \ 다음의 문자는 무시
                if ($char == '\\') {
                    if ($i + 1 < strlen($this->_expression)) {
                        $this->_appendBracket($uuid, $char . $this->_expression[$i + 1]);
                        $i++; // 다음 문자를 건너뜀
                        continue;
                    } else {
                        return '이스케이프 문자 뒤에는 항상 문자가 존재하여야 합니다.';
                    }
                }

                // 따옴표가 열리거나 닫힌 경우 따옴표 상태를 토글
                if ($char == "'" || $char == '"') {
                    if ($in_quotes !== null) {
                        if ($char == $in_quotes) {
                            $in_quotes = null; // 따옴표가 닫힘
                        }
                    } else {
                        $in_quotes = $char; // 따옴표가 열림
                    }

                    $this->_appendBracket($uuid, $char);
                    continue;
                }

                // 따옴표 안에서는 괄호 검사를 하지 않음
                if ($in_quotes !== null) {
                    $this->_appendBracket($uuid, $char);
                    continue;
                }

                // 열린 괄호 처리
                if ($char == '(') {
                    if ($uuid === null) {
                        $uuid = UUID::v4();
                    } else {
                        $this->_appendBracket($uuid, $char);
                    }

                    array_push($stack, '(');
                    continue;
                } elseif ($char == ')') {
                    if (empty($stack) == true) {
                        return '괄호쌍이 맞지 않습니다.';
                    }
                    array_pop($stack);
                    if (count($stack) == 0) {
                        $uuid = null;
                    }
                    $this->_appendBracket($uuid, $char);
                    continue;
                }

                $this->_appendBracket($uuid, $char);
            }

            $isValid = empty($stack) && $in_quotes === null;
            if ($isValid == false) {
                if (empty($stack) == true) {
                    $this->_isValid = '따옴표쌍이 맞지 않습니다.';
                } else {
                    $this->_isValid = '괄호쌍이 맞지 않습니다.';
                }
            } else {
                // 내부괄호가 존재한다면 해당 괄호의 유효성을 검증하고, 유효할 경우 괄호부분을 권한여부에 따라 true/false 로 치환한다.
                $isValid = true;
                $expression = $this->_expression;
                foreach ($this->_brackets as $bracket) {
                    $permission = Permission::get($bracket);
                    if ($permission->isValid() !== true) {
                        $this->_isValid = $permission->isValid();
                        return $this->_isValid;
                    } else {
                        $expression = str_replace(
                            '(' . $bracket . ')',
                            $permission->hasPermission() === true ? 'true' : 'false',
                            $expression
                        );
                    }
                }

                $executed = $this->_execute($expression);
                $this->_isValid = is_bool($executed) == true ? true : $executed;
                $this->_permission = $executed === true;
            }
        }

        return $this->_isValid;
    }

    /**
     * 권한표현식을 해석하여 권한이 있는지 여부를 확인한다.
     *
     * @return bool $has_permission
     */
    public function hasPermission(): bool
    {
        if (isset($this->_permission) === true) {
            return $this->_permission;
        }

        if ($this->isValid() === true) {
            return $this->_permission;
        }

        return false;
    }

    /**
     * 괄호가 시작되었다면, 괄호고유값과 함께 괄호내부표현식에 문자열을 추가한다.
     *
     * @param ?string $uuid 괄호고유값
     * @param string $char 문자열
     */
    private function _appendBracket(?string $uuid, string $char): void
    {
        if ($uuid === null) {
            return;
        }

        if (isset($this->_brackets[$uuid]) == false) {
            $this->_brackets[$uuid] = '';
        }
        $this->_brackets[$uuid] .= $char;
    }

    /**
     * 권한표현식에서 논리항을 처리한다.
     *
     * @param string $expression 전체 권한표현식
     * @return bool $result
     */
    private function _execute(string $expression): bool|string
    {
        $expression = trim($expression);

        $in_quotes = null; // 따옴표가 시작되었다면 현재 따옴표 여부 ' 또는 "
        $logic = null;
        $compared = null;

        /**
         * 권한표현식에서 논리연산자로 먼저 구분한다.
         */
        $current = '';
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            // 이스케이프 문자 처리 \ 다음의 문자는 무시
            if ($char == '\\') {
                $current .= $char . $expression[$i + 1];
                $i++; // 다음 문자를 건너뜀
                continue;
            }

            if ($char == "'" || $char == '"') {
                $current .= $char;
                if ($in_quotes !== null) {
                    if ($char == $in_quotes) {
                        $in_quotes = null; // 따옴표가 닫힘
                    }
                } else {
                    $in_quotes = $char; // 따옴표가 열림
                }
                continue;
            }

            // 따옴표 안에서는 괄호 검사를 하지 않음
            if ($in_quotes !== null) {
                $current .= $char;
                continue;
            }

            // 논리연산자가 시작될 경우
            if ($char == '&' || $char == '|') {
                // 다음 문자열이 같은 문자열이 아닌경우
                if ($expression[$i + 1] != $char) {
                    return '논리연산자는 && 또는 || 와 같이 입력하여 주십시오.';
                }

                $logic ??= $char;

                // 하나의 표현식에 AND 및 OR 이 혼용된 경우
                if ($logic != $char) {
                    return '하나의 괄호 또는 하나의 권한표현식에서는 && 및 || 를 혼용할 수 없습니다.';
                }

                // 현재까지의 비교식을 비교한다.
                $compare = $this->_compare(trim($current));
                if (is_bool($compare) == false) {
                    return $compare;
                }

                $compared ??= $compare;
                if ($logic == '&') {
                    $compared = $compared && $compare;
                } else {
                    $compared = $compared || $compare;
                }

                $current = '';
                $i++;
                continue;
            }

            $current .= $char;
        }

        $current = trim($current);
        if (strlen($current) > 0) {
            $compare = $this->_compare($current);
            if (is_bool($compare) == false) {
                return $compare;
            }

            $compared ??= $compare;
            if ($logic == '&') {
                $compared = $compared && $compare;
            } else {
                $compared = $compared || $compare;
            }
        }

        return $compared;
    }

    /**
     * 권한표현식에서 비교식 부분을 비교한다.
     *
     * @param string $expression 비교식
     * @return bool $result
     */
    private function _compare(string $expression): bool|string
    {
        $in_quotes = null; // 따옴표가 시작되었다면 현재 따옴표 여부 ' 또는 "
        $left = '';
        $right = '';
        $operator = null;

        /**
         * 논리연산자로 먼저 구분한다.
         */
        for ($i = 0; $i < strlen($expression); $i++) {
            $char = $expression[$i];

            // 이스케이프 문자 처리 \ 다음의 문자는 무시
            if ($char == '\\') {
                if ($operator === null) {
                    $left .= $char . $expression[$i + 1];
                } else {
                    $right .= $char . $expression[$i + 1];
                }
                $i++; // 다음 문자를 건너뜀
                continue;
            }

            if ($char == "'" || $char == '"') {
                if ($operator === null) {
                    $left .= $char;
                } else {
                    $right .= $char;
                }
                if ($in_quotes !== null) {
                    if ($char == $in_quotes) {
                        $in_quotes = null; // 따옴표가 닫힘
                    }
                } else {
                    $in_quotes = $char; // 따옴표가 열림
                }
                continue;
            }

            // 따옴표 안에서는 괄호 검사를 하지 않음
            if ($in_quotes !== null) {
                if ($operator === null) {
                    $left .= $char;
                } else {
                    $right .= $char;
                }
                continue;
            }

            // 비교연산자가 시작될 경우
            if (in_array($char, ['!', '=', '<', '>']) == true) {
                // 이미 비교연산자가 존재할 경우
                if ($operator !== null) {
                    return '하나의 논리표현식에서 비교연산자는 한개만 존재하여야 합니다.<br>' . $expression;
                }

                // 다음 문자열이 반드시 = 이어야 하는 경우
                if (in_array($char, ['!', '=']) == true) {
                    if ($i + 1 < strlen($expression)) {
                        if ($expression[$i + 1] != '=') {
                            return '비교연산자는 ' . $char . '= 형태로 입력되어야 합니다.<br>' . $expression;
                        }
                    }
                }

                // ==, !=, <=, >= 처리
                if ($i + 1 < strlen($expression) && $expression[$i + 1] == '=') {
                    $operator = $char . '=';
                    $i++;
                } else {
                    $operator = $char;
                }

                continue;
            }

            if ($operator === null) {
                $left .= $char;
            } else {
                $right .= $char;
            }
        }

        $checked = $this->_checkValue($left);
        if ($checked !== true) {
            return $checked;
        }

        $checked = $this->_checkValue($right);
        if ($checked !== true) {
            return $checked;
        }

        $left = trim($left);
        $right = trim($right);

        if ($operator !== null && strlen($right) == 0) {
            return '비교대상이 존재하지 않습니다.' . $expression;
        }

        $left = $this->_getValue($left);
        $right = $this->_getValue($right);

        if ($operator === null) {
            if (is_bool($left) == true) {
                return $left;
            } else {
                return '비교연산자가 존재하지 않을 경우 비교식은 true 이거나 false 이어야 합니다..<br>' . $expression;
            }
        }

        $compared = null;

        switch ($operator) {
            case '==':
                $compared = $left == $right;
                break;

            case '!=':
                $compared = $left != $right;
                break;

            case '<':
                $compared = $left < $right;
                break;

            case '<=':
                $compared = $left <= $right;
                break;

            case '>':
                $compared = $left > $right;
                break;

            case '>=':
                $compared = $left >= $right;
                break;

            default:
                $compared = '알수없는 비교연산자입니다.(' . $operator . ')';
        }

        return $compared;
    }

    /**
     * 권한표현식의 각 항의 값을 확인한다.
     *
     * @param string $string 항
     * @return bool $isValid
     */
    private function _checkValue(string $string): bool|string
    {
        if (strlen($string) == 0) {
            return true;
        }

        $string = trim($string);

        // true, false, null 인 경우
        if (in_array(strtolower($string), ['true', 'false', 'null']) == true) {
            return true;
        }

        // 숫자인 경우
        if (is_numeric($string) === true && (strpos($string, '0') !== 0 || strlen($string) == 1)) {
            return true;
        }

        // 문자열인 경우
        if (preg_match('/^([\'"])(.*?)(?<!\\\\)\1$/', $string) == true) {
            return true;
        } else {
            return '문자열은 따옴표로 감싸야합니다.(' . $string . ')';
        }
    }

    /**
     * 권한표현식의 각 항을 형식에 맞게 변환하여 가져온다.
     *
     * @param string $string 항
     * @return mixed $value
     */
    private function _getValue(string $string): mixed
    {
        $string = trim($string);

        if (strlen($string) == 0) {
            return null;
        }

        // true, false, null 인 경우
        if (in_array(strtolower($string), ['true', 'false', 'null']) == true) {
            return json_decode(strtolower($string));
        }

        // 숫자인 경우
        if (is_numeric($string) === true && (strpos($string, '0') !== 0 || strlen($string) == 1)) {
            return $string + 0;
        }

        if (preg_match('/^([\'"])(.*?)(?<!\\\\)\1$/', $string, $match) == true) {
            return stripslashes($match[2]);
        } else {
            return null;
        }
    }
}

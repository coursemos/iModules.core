<?php
/**
 * 이 파일은 아이모듈의 일부입니다. (https://www.imodules.io)
 *
 * JWT 토큰을 생성하거나 검증한다.
 *
 * @file /classes/JWT.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 1. 7.
 */
class JWT
{
    private string $_alg;
    private string $_secret_key;

    private static function base64Encode(string $str): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($str));
    }

    /**
     * JWT 토큰을 생성한다.
     *
     * @param array $data JWT 토큰 생성시 사용할 데이터
     * @return string|bool $jwt
     */
    public static function create(array $payload = [], string $private_key, string $alg): string|bool
    {
        $header = [
            'alg' => $alg,
            'typ' => 'JWT',
        ];
        $header = self::base64Encode(json_encode($header));

        // 페이로드 - 전달할 데이터
        $payload = self::base64Encode(json_encode($payload));

        switch ($alg) {
            case 'RS256':
                $signature = null;
                openssl_sign($header . '.' . $payload, $signature, $private_key, 'SHA256');
                break;

            default:
                // @todo 다른 알고리즘 추가
                return false;
        }

        return $header . '.' . $payload . '.' . self::base64Encode($signature);
    }
}

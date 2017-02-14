<?php
namespace Org\Com;
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/19
 * Time: 16:39
 */


class Aes
{
    private $_privateKey;
    private $_iv;

    /**
     * 初始化密钥和向量
     */
    public function __construct()
    {
        $this->_privateKey = '!@#$JUJUHUI2015SysTem%^*';
        $this->_iv = '@#jiujuhui.com^*';
    }

    /**
     * 加密方法，供外部调用
     *
     * @param string $text - 需要加密的字符串
     * @param string $type - 加密类型，默认cbc类型
     *
     * @return string
     */
    public function encrypt($text, $type = "cbc")
    {
        switch ($type) {
            case "ecb":
                return $this->EcbEnCrypt($text);
                break;
            case "cbc":
                return $this->CbcEnCrypt($text);
                break;
            default:
                return $this->CbcEnCrypt($text);
                break;
        }
    }

    /**
     * 解密方法，供外部调用
     *
     * @param string $text - 需要解密的字符串
     * @param string $type - 解密类型，默认cbc类型
     *
     * @return string
     */
    public function decrypt($text, $type = 'cbc')
    {
        switch ($type) {
            case "ecb":
                return trim($this->EcbDeCrypt($text));
                break;
            case "cbc":
                return trim($this->CbcDeCrypt($text));
                break;
            default:
                return trim($this->CbcDeCrypt($text));
                break;
        }
    }

    /**
     * 实现 ecb加密算法
     *
     * @param string $text - 需要加密的字符串
     *
     * @return string - 加密以后的字符串
     */
    private function EcbEnCrypt($text)
    {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $text = $this->pkcs5_pad($text, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $this->_privateKey, $iv);
        $data = mcrypt_generic($td, $text);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 实现ecb解密算法
     *
     * @param string $text - 需要解密的字符串
     *
     * @return string - 解密以后的字符串
     */
    private function EcbDeCrypt($text)
    {
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->_privateKey, base64_decode($text), MCRYPT_MODE_ECB);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s - 1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    /**
     * 字符串填充
     *
     * @param string  $text - 需要填充的字符串
     * @param integer $blocksize - 加密算法的分组大小
     *
     * @return string - 填充完毕的字符串
     */
    private function pkcs5_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * 实现 cbc加密算法
     *
     * @param string $text - 需要加密的字符串
     *
     * @return string - 加密以后的字符串
     */
    private function CbcEnCrypt($text)
    {
        return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $this->_privateKey, $text, MCRYPT_MODE_CBC, $this->_iv));
    }

    /**
     * 实现cbc解密算法
     *
     * @param string $text - 需要解密的字符串
     *
     * @return string - 解密以后的字符串
     */
    private function CbcDeCrypt($text)
    {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->_privateKey, base64_decode($text), MCRYPT_MODE_CBC, $this->_iv);
    }
}
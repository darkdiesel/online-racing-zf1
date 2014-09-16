<?php

class Peshkov_View_Helper_GenerateCode extends Zend_View_Helper_Abstract
{
    /**
     *  GenerateCode() Генерериет строку символов заданной длины
     *
     * @param int $length
     * @return strin
     */
    public function GenerateCode($length = 6)
    {
        $chars = 'abdefhiknrstyzABDEFGHKNQRSTYZ23456789';
        $numChars = strlen($chars);
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= substr($chars, rand(1, $numChars) - 1, 1);
        }
        return $string;
    }



}

<?php
/**
 * Class Peshkov_Trait_Translator
 */
trait Peshkov_Trait_Translator
{
    /**
     * @param null|string $text
     * @return null|string
     */
    public function translate($text = null)
    {
        if ($text === null) {
            return '';
        }

        $options = func_get_args();
        array_shift($options);
        if ((count($options) === 1) and (is_array($options[0]) === true)) {
            $options = $options[0];
        }

        if (Zend_Registry::isRegistered('Zend_Translate') && (Zend_Registry::get('Zend_Translate') instanceof Zend_Translate)) {
            $text = Zend_Registry::get('Zend_Translate')->translate($text);
        }

        if (count($options) === 0) {
            return $text;
        }

        return vsprintf($text, $options);
    }

    /**
     * @param string $text
     * @return string
     */
    public function _($text)
    {
        $options = func_get_args();
        array_shift($options);
        return $this->translate($text, $options);
    }

    /**
     * @param string $text
     */
    public function _e($text)
    {
        $options = func_get_args();
        array_shift($options);
        echo $this->translate($text, $options);
    }
}
<?php

class App_View_Helper_Truncate extends Zend_View_Helper_Abstract {

    private $_string;
    private $_length;
    private $_postfix;
    private $_cutatspace = true;

    public function truncate($string) {
        $this->_string = trim($string);
        // Длина строки по умолчанию
        $this->toLength(100);
        // Добавляем в конце три точки - по умолчанию
        $this->withPostfix('&#0133;');
        return $this;
    }

    // На всякий случай предусмотрена возможность обрезать и по словам
    public function midword() {
        $this->_cutatspace = false;
        return $this;
    }

    public function toLength($int) {
        $this->_length = (int) $int;
        return $this;
    }

    public function withPostfix($str) {
        $this->_postfix = $str;
        return $this;
    }

    // Главный метод вывода
    public function render() {
        // Возвращает исходную строку, если требуемая длина равна 0
        if ($this->_length < 1)
            return $this->_string;

        $fullLength = strlen($this->_string);

        // Обрезаем строку, если она больше заданной длины
        if ($fullLength > $this->_length && $this->_cutatspace) {
            $positionOfLastSpace = strrpos(substr($this->_string, 0, $this->_length), ' ', -1);
            if ($positionOfLastSpace === false) {
                $positionOfLastSpace = $fullLength;
            } else {
                $positionOfLastSpace++;
            }
            $this->_string = trim(substr($this->_string, 0, $positionOfLastSpace)) . $this->_postfix;
        } elseif ($fullLength > $this->_length) {
            $this->_string = trim(substr($this->_string, 0, $this->_length)) . $this->_postfix;
        }
        return $this->_string;
    }

    public function __toString() {
        return $this->render();
    }

}
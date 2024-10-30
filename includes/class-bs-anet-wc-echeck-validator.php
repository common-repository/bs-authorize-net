<?php
/**
 * Echeck
 * @author Bipin
 */
class Echeck {

    /**
     * validAccountnumber
     *
     * @param [type] $number
     * @return void
     */
    public static function validAccountnumber($number) {
        return ($number && !empty($number) && strlen($number) <= 17);
    }

    /**
     * validRoutingnumber
     *
     * @param [type] $number
     * @return void
     */
    public static function validRoutingnumber($number) {
        return ($number && !empty($number) && strlen($number) == 9);
    }

    /**
     * validNameonaccount
     *
     * @param [type] $name
     * @return void
     */
    public static function validNameonaccount($name) {
        return ($name && !empty($name) && strlen($name) <= 22);
    }

    /**
     * validBankname
     *
     * @param [type] $name
     * @return void
     */
    public static function validBankname($name) {
        return ($name && !empty($name) && strlen($name) <= 50);
    }

    /**
     * validAccountype
     *
     * @param [type] $type
     * @return void
     */
    public static function validAccountype($type) {
        return ($type && !empty($type) && in_array($type, array('savings', 'checking')));
    }
}

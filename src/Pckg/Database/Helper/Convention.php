<?php namespace Pckg\Database\Helper;

/**
 * Class Convention
 *
 * @package Pckg\Database\Helper
 */
class Convention
{

    /**
     * @param $input
     *
     * @return string
     */
    public static function nameOne($input)
    {
        $i18n = "";
        if (substr($input, -5) == "_i18n") {
            $i18n = "_i18n";
            $input = str_replace("_i18n", "", $input);
        } else if (substr($input, -4) == "i18n") {
            $i18n = "i18n";
            $input = str_replace("i18n", "", $input);
        }

        $id = substr($input, -3) == "_id";
        if ($id) {
            $input = str_replace("_id", "", $input);
        }

        $one = null;
        if (substr($input, -3) == "ies") //categories
        {
            $one = substr($input, 0, -3) . "y";
        } else if (substr($input, -3) == "ses") // statuses
        {
            $one = substr($input, 0, -2);
        } else if (substr($input, -3) == "es") // pages
        {
            $one = substr($input, 0, -1);
        } else if (substr($input, -1) != "s") // users_fb
        {
            $one = $input;
        } else  // users
        {
            $one = substr($input, 0, -1);
        }

        return $one . $i18n;
    }

    /**
     * @param $input
     *
     * @return string
     */
    public static function nameMultiple($input)
    {
        $i18n = substr($input, -5) == "_i18n";
        $id = substr($input, -3) == "_id";

        if ($i18n) {
            $input = str_replace("_i18n", "", $input);
        }
        if ($id) {
            $input = str_replace("_id", "", $input);
        }

        $multiple = null;
        if (substr($input, -2) == "es") //categories
        {
            $multiple = $input;
        } else if (substr($input, -1) == "y") // category
        {
            $multiple = substr($input, 0, -1) . "ies";
        } else if (substr($input, -1) == "s") // news
        {
            $multiple = $input;
        } else {
            $multiple = $input . "s";
        }

        return $multiple . ($i18n ? "_i18n" : "");
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function toPascal($text)
    {
        return ucfirst(static::toCamel($text));
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function toCamel($text)
    {
        $text = str_split($text, 1);

        foreach ($text AS $index => $char) {
            if (($char == "_" && isset($text[$index + 1]))
                || ($char == "\\" && isset($text[$index + 1]))
            ) {
                $text[$index + 1] = mb_strtoupper($text[$index + 1]);
            }
        }

        return str_replace("_", "", implode($text));
    }

    /**
     * @param $text
     *
     * @return string
     */
    public static function fromCamel($text)
    {
        $return = null;
        $text = str_split($text, 1);

        foreach ($text AS $index => $char) {
            if ($char != strtolower(
                    $char
                ) && $index != 0 && (isset($text[$index - 1]) && $text[$index - 1] != "/" && $text[$index - 1] != "\\")
            ) {
                $return .= "_";
            }

            $return .= $char;
        }

        return strtolower($return);
    }

    /**
     * @param $url
     *
     * @return mixed|string
     */
    public static function url($url)
    {
        $url = preg_replace('~[^\\pL0-9_]+~u', '-', $url);
        $url = trim($url, "-");
        $url = iconv("utf-8", "us-ascii//TRANSLIT", $url);
        $url = strtolower($url);
        $url = preg_replace('~[^-a-z0-9_]+~', '', $url);

        return $url;
    }

}
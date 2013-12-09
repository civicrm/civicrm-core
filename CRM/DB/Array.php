<?php

class CRM_DB_Array
{
  static function marshal($array)
  {
    return implode(CRM_Core_DAO::VALUE_SEPARATOR, $array);
  }

  static function unmarshal($string)
  {
    return explode(CRM_Core_DAO::VALUE_SEPARATOR, $string);
  }
}

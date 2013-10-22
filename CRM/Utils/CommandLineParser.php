<?php

class CRM_Utils_CommandLineParser_ParseException extends Exception
{
  public $pear_error;
  function __construct($pear_error)
  {
    $this->pear_error = $pear_error;
    parent::__construct($pear_error->getMessage(), $pear_error->getCode());
  }
}

class CRM_Utils_CommandLineParser_UnrecognizedOption extends CRM_Utils_CommandLineParser_ParseException {};
class CRM_Utils_CommandLineParser_ArgumentRequired extends CRM_Utils_CommandLineParser_ParseException {};

class CRM_Utils_CommandLineParser
{
  const NO_ARG = 'n';
  const REQUIRED_ARG = 'r';
  const OPTIONAL_ARG = 'o';
  public static $argument_info_to_long_getopt_char = array(
    self::NO_ARG => '',
    self::REQUIRED_ARG => '=',
    self::OPTIONAL_ARG => '==',
  );
  public static $argument_info_to_short_getopt_char = array(
    self::NO_ARG => '',
    self::REQUIRED_ARG => ':',
    self::OPTIONAL_ARG => '::',
  );
  public $arguments = array();
  public $command_name;
  public $option_definitions = array();
  public $options = array();
  public $parse_error;
  public static $valid_argument_values = array(
    self::NO_ARG,
    self::REQUIRED_ARG,
    self::OPTIONAL_ARG
  );

  function addOption($short, $long, $argument_info, $help) {
    if (!in_array($argument_info, self::$valid_argument_values)) {
      throw new Exception("The third argument to addOption must be one of (" . implode(', ', self::$valid_argment_values) . "."); 
    }
    $this->option_definitions[] = array($short, $long, $argument_info, $help);
  }

  function buildGetOptArgs() {
    $getopt_short = '';
    $getopt_long = array();
    foreach ($this->option_definitions as $option_definition) {
      list($short, $long, $argument_info, $help) = $option_definition;
      if ($short != NULL and trim($short) != '') {
        $getopt_short .= $short;
        $getopt_short .= self::$argument_info_to_short_getopt_char[$argument_info];
      }
      if ($long != NULL and trim($long) != '') {
        $long_option = $long . self::$argument_info_to_long_getopt_char[$argument_info];
        $getopt_long[] = $long_option;
      }
    }
    return array($getopt_short, $getopt_long);
  }

  function getOptionNameFor($short_option) {
    $option_name = NULL;
    foreach ($this->option_definitions as $option_definition) {
      if ($short_option == $option_definition[0]) {
        $option_name = $option_definition[1];
        break;
      }
    }
    return $option_name;
  }

  function parse($args) {
    list($getopt_short, $getopt_long) = $this->buildGetOptArgs();
    $this->command_name = array_shift($args);
    $console_getopt = new Console_Getopt();
    $result = $console_getopt->getopt2($args, $getopt_short, $getopt_long);
    if (PEAR::isError($result)) {
      $message = $result->getMessage();
      if (preg_match("/^Console_Getopt: unrecognized option.*/", $message)) {
        throw new CRM_Utils_CommandLineParser_UnrecognizedOption($result);
      } elseif (preg_match("/^Console_Getopt: option requires an argument.*/", $message)) {
        throw new CRM_Utils_CommandLineParser_ArgumentRequired($result);
      }
      else {
        throw new Exception($result->getMessage());
      }
    }
    $option_values = $result[0];
    $this->arguments = $result[1];
    foreach ($option_values as $option_value_pair) {
      list($option, $value) = $option_value_pair;
      if (substr($option, 0, 2) == '--') {
        $option_name = substr($option, 2);
      } else {
        $option_name = $this->getOptionNameFor($option);
      }
      if ($value == NULL) {
        $value = TRUE;
      }
      $this->options[$option_name] = $value;
    }
    return $this;
  }

  function getUsage() {
    $result = "Usage: {$this->command_name} [OPTION]...\n";
    $result .= "\n";
    $longest_arg_length = 0;
    foreach ($this->option_definitions as $option_definition) {
      list($short, $long, $argument_info, $help) = $option_definition;
      $arg_length = strlen($long);
      if ($argument_info == self::REQUIRED_ARG) {
        $arg_length += 4;
      } elseif ($argument_info == self::OPTIONAL_ARG) {
        $arg_length += 6;
      }
      if ($arg_length > $longest_arg_length) {
        $longest_arg_length = $arg_length;
      }
    }
    foreach ($this->option_definitions as $option_definition) {
      list($short, $long, $argument_info, $help) = $option_definition;
      $long_part = "$long";
      if ($argument_info == self::REQUIRED_ARG) {
        $long_part .= " ARG";
      } elseif ($argument_info == self::OPTIONAL_ARG) {
        $long_part .= " [ARG]";
      }
      $padding_len = $longest_arg_length - strlen($long_part);
      $padding = str_repeat(" ", $padding_len);
      $result .= "  -$short, --$long_part $padding $help\n";
    }
    return $result;
  }

  function printException($exception, $file = NULL) {
    if ($file == NULL) {
      $file = fopen('php://stderr', 'w');
    }
    fwrite($file, $exception->getMessage() . "\n\n");
  }

  function printUsage($file = NULL) {
    if ($file == NULL) {
      $file = fopen('php://stderr', 'w');
    }
    fwrite($file, $this->getUsage());
  }
}

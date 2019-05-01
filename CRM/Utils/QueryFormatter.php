<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

 /**
  * @package CRM
  * @copyright CiviCRM LLC (c) 2004-2019
  */

/**
 * Class CRM_Utils_QueryFormatter
 *
 * This class is a bad idea. It exists for the unholy reason that a single installation
 * may have up to three query engines (MySQL LIKE, MySQL FTS, Solr) processing the same
 * query-text. It labors* to take the user's search expression and provide similar search
 * semantics in different contexts. It is unknown whether this labor will be fruitful
 * or in vain.
 */
class CRM_Utils_QueryFormatter {
  /**
   * Generate queries using SQL LIKE expressions.
   */
  const LANG_SQL_LIKE = 'like';

  /**
   * Generate queries using MySQL FTS expressions.
   */
  const LANG_SQL_FTS = 'fts';

  /**
   * Generate queries using MySQL's boolean FTS expressions.
   */
  const LANG_SQL_FTSBOOL = 'ftsbool';

  /**
   * Generate queries using Solr expressions.
   */
  const LANG_SOLR = 'solr';

  /**
   * Attempt to leave the text as-is.
   */
  const MODE_NONE = 'simple';

  /**
   * Attempt to treat the input text as a phrase
   */
  const MODE_PHRASE = 'phrase';

  /**
   * Attempt to treat the input text as a phrase with
   * wildcards on each end.
   */
  const MODE_WILDPHRASE = 'wildphrase';

  /**
   * Attempt to treat individual word as if it
   * had wildcards at the start and end.
   */
  const MODE_WILDWORDS = 'wildwords';

  /**
   * Attempt to treat individual word as if it
   * had a wildcard at the end.
   */
  const MODE_WILDWORDS_SUFFIX = 'wildwords-suffix';

  /**
   * @var \CRM_Utils_QueryFormatter|NULL
   */
  static protected $singleton;

  /**
   * @param bool $fresh
   * @return CRM_Utils_QueryFormatter
   */
  public static function singleton($fresh = FALSE) {
    if ($fresh || self::$singleton === NULL) {
      $mode = Civi::settings()->get('fts_query_mode');
      self::$singleton = new CRM_Utils_QueryFormatter($mode);
    }
    return self::$singleton;
  }

  /**
   * @var string
   *   eg MODE_NONE
   */
  protected $mode;

  /**
   * @param string $mode
   *   Eg MODE_NONE.
   */
  public function __construct($mode) {
    $this->mode = $mode;
  }

  /**
   * @param mixed $mode
   */
  public function setMode($mode) {
    $this->mode = $mode;
  }

  /**
   * @return mixed
   */
  public function getMode() {
    return $this->mode;
  }

  /**
   * @param string $text
   * @param string $language
   *   Eg LANG_SQL_LIKE, LANG_SQL_FTS, LANG_SOLR.
   * @throws CRM_Core_Exception
   * @return string
   */
  public function format($text, $language) {
    $text = trim($text);

    switch ($language) {
      case self::LANG_SOLR:
      case self::LANG_SQL_FTS:
        $text = $this->_formatFts($text, $this->mode);
        break;

      case self::LANG_SQL_FTSBOOL:
        $text = $this->_formatFtsBool($text, $this->mode);
        break;

      case self::LANG_SQL_LIKE:
        $text = $this->_formatLike($text, $this->mode);
        break;

      default:
        $text = NULL;
    }

    if ($text === NULL) {
      throw new CRM_Core_Exception("Unrecognized combination: language=[{$language}] mode=[{$this->mode}]");
    }

    return $text;
  }

  /**
   * Create a SQL WHERE expression for matching against a list of
   * text columns.
   *
   * @param string $table
   *   Eg "civicrm_note" or "civicrm_note mynote".
   * @param array|string $columns
   *   List of columns to search against.
   *   Eg "first_name" or "activity_details".
   * @param string $queryText
   * @return string
   *   SQL, eg "MATCH (col1) AGAINST (queryText)" or "col1 LIKE '%queryText%'"
   */
  public function formatSql($table, $columns, $queryText) {
    if ($queryText === '*' || $queryText === '%' || empty($queryText)) {
      return '(1)';
    }

    $strtolower = function_exists('mb_strtolower') ? 'mb_strtolower' : 'strtolower';

    if (strpos($table, ' ') === FALSE) {
      $tableName = $tableAlias = $table;
    }
    else {
      list ($tableName, $tableAlias) = explode(' ', $table);
    }
    if (is_scalar($columns)) {
      $columns = [$columns];
    }

    $clauses = [];
    if (CRM_Core_InnoDBIndexer::singleton()
      ->hasDeclaredIndex($tableName, $columns)
    ) {
      $formattedQuery = $this->format($queryText, CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL);

      $prefixedFieldNames = [];
      foreach ($columns as $fieldName) {
        $prefixedFieldNames[] = "$tableAlias.$fieldName";
      }

      $clauses[] = sprintf("MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)",
        implode(',', $prefixedFieldNames),
        $strtolower(CRM_Core_DAO::escapeString($formattedQuery))
      );
    }
    else {
      //CRM_Core_Session::setStatus(ts('Cannot use FTS for %1 (%2)', array(
      //  1 => $table,
      //  2 => implode(', ', $fullTextFields),
      //)));

      $formattedQuery = $this->format($queryText, CRM_Utils_QueryFormatter::LANG_SQL_LIKE);
      $escapedText = $strtolower(CRM_Core_DAO::escapeString($formattedQuery));
      foreach ($columns as $fieldName) {
        $clauses[] = "$tableAlias.$fieldName LIKE '{$escapedText}'";
      }
    }
    return implode(' OR ', $clauses);
  }

  /**
   * Format Fts.
   *
   * @param string $text
   * @param $mode
   *
   * @return mixed
   */
  protected function _formatFts($text, $mode) {
    $result = NULL;

    // normalize user-inputted wildcards
    $text = str_replace('%', '*', $text);

    if (empty($text)) {
      $result = '*';
    }
    elseif (strpos($text, '*') !== FALSE) {
      // if user supplies their own wildcards, then don't do any sophisticated changes
      $result = $text;
    }
    else {
      switch ($mode) {
        case self::MODE_NONE:
          $result = $text;
          break;

        case self::MODE_PHRASE:
          $result = '"' . $text . '"';
          break;

        case self::MODE_WILDPHRASE:
          $result = '"*' . $text . '*"';
          break;

        case self::MODE_WILDWORDS:
          $result = $this->mapWords($text, '*word*');
          break;

        case self::MODE_WILDWORDS_SUFFIX:
          $result = $this->mapWords($text, 'word*');
          break;

        default:
          $result = NULL;
      }
    }

    return $this->dedupeWildcards($result, '%');
  }

  /**
   * Format FTS.
   *
   * @param string $text
   * @param $mode
   *
   * @return mixed
   */
  protected function _formatFtsBool($text, $mode) {
    $result = NULL;
    $operators = ['+', '-', '~', '(', ')'];
    $wildCards = ['@', '%', '*'];
    $expression = preg_quote(implode('', array_merge($operators, $wildCards)), '/');

    //Return if searched string ends with an unsupported operator.
    //Or if the string contains an invalid joint occurrence of operators.
    foreach ($operators as $val) {
      if ($text == '@' || CRM_Utils_String::endsWith($text, $val) || preg_match("/[{$expression}]{2,}/", $text)) {
        $csid = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionValue', 'CRM_Contact_Form_Search_Custom_FullText', 'value', 'name');
        $url = CRM_Utils_System::url("civicrm/contact/search/custom", "csid={$csid}&reset=1");
        $operators = implode("', '", $operators);
        CRM_Core_Error::statusBounce("Full-Text Search does not support the use of a search with two attached operators or string ending with any of these operators ('{$operators}' or a single '@'). Please adjust your search term and try again.", $url, 'Invalid Search String');
      }
    }

    // normalize user-inputted wildcards
    $text = str_replace('%', '*', $text);

    if (empty($text)) {
      $result = '*';
    }
    elseif (strpos($text, '+') !== FALSE || strpos($text, '-') !== FALSE) {
      // if user supplies their own include/exclude operators, use text as is (with trailing wildcard)
      $result = $this->mapWords($text, 'word*');
    }
    elseif (strpos($text, '*') !== FALSE) {
      // if user supplies their own wildcards, then don't do any sophisticated changes
      $result = $this->mapWords($text, '+word');
    }
    elseif (preg_match('/^(["\']).*\1$/m', $text)) {
      // if surrounded by quotes, use term as is
      $result = $text;
    }
    else {
      switch ($mode) {
        case self::MODE_NONE:
          $result = $this->mapWords($text, '+word', TRUE);
          break;

        case self::MODE_PHRASE:
          $result = '+"' . $text . '"';
          break;

        case self::MODE_WILDPHRASE:
          $result = '+"*' . $text . '*"';
          break;

        case self::MODE_WILDWORDS:
          $result = $this->mapWords($text, '+*word*');
          break;

        case self::MODE_WILDWORDS_SUFFIX:
          $result = $this->mapWords($text, '+word*');
          break;

        default:
          $result = NULL;
      }
    }

    return $this->dedupeWildcards($result, '%');
  }

  /**
   * Format like.
   *
   * @param $text
   * @param $mode
   *
   * @return mixed
   */
  protected function _formatLike($text, $mode) {
    $result = NULL;

    if (empty($text)) {
      $result = '%';
    }
    elseif (strpos($text, '%') !== FALSE) {
      // if user supplies their own wildcards, then don't do any sophisticated changes
      $result = $text;
    }
    else {
      switch ($mode) {
        case self::MODE_NONE:
        case self::MODE_PHRASE:
        case self::MODE_WILDPHRASE:
          $result = "%" . $text . "%";
          break;

        case self::MODE_WILDWORDS:
        case self::MODE_WILDWORDS_SUFFIX:
          $result = "%" . preg_replace('/[ \r\n]+/', '%', $text) . '%';
          break;

        default:
          $result = NULL;
      }
    }

    return $this->dedupeWildcards($result, '%');
  }

  /**
   * @param string $text
   *   User-supplied query string.
   * @param string $template
   *   A prototypical description of each word, eg "word%" or "word*" or "*word*".
   * @param bool $quotes
   *   True if each searched keyword need to be surrounded with quotes.
   * @return string
   */
  protected function mapWords($text, $template, $quotes = FALSE) {
    $result = [];
    foreach ($this->parseWords($text, $quotes) as $word) {
      $result[] = str_replace('word', $word, $template);
    }
    return implode(' ', $result);
  }

  /**
   * @param string $text
   * @param bool $quotes
   * @return array
   */
  protected function parseWords($text, $quotes) {
    //NYSS 9692 special handling for emails
    if (preg_match('/^([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})$/', $text)) {
      $parts = explode('@', $text);
      $parts[1] = stristr($parts[1], '.', TRUE);
      $text = implode(' ', $parts);
    }

    //NYSS also replace other occurrences of @
    $replacedText = preg_replace('/[ \r\n\t\@]+/', ' ', trim($text));
    //filter empty values if any
    $keywords = array_filter(explode(' ', $replacedText));

    //Ensure each searched keywords are wrapped in double quotes.
    if ($quotes) {
      foreach ($keywords as &$val) {
        if (!is_numeric($val)) {
          $val = "\"{$val}\"";
        }
      }
    }
    return $keywords;
  }

  /**
   * @param $text
   * @param $wildcard
   * @return mixed
   */
  protected function dedupeWildcards($text, $wildcard) {
    if ($text === NULL) {
      return NULL;
    }

    // don't use preg_replace because $wildcard might be special char
    while (strpos($text, "{$wildcard}{$wildcard}") !== FALSE) {
      $text = str_replace("{$wildcard}{$wildcard}", "{$wildcard}", $text);
    }
    return $text;
  }

  /**
   * Get modes.
   *
   * @return array
   */
  public static function getModes() {
    return [
      self::MODE_NONE,
      self::MODE_PHRASE,
      self::MODE_WILDPHRASE,
      self::MODE_WILDWORDS,
      self::MODE_WILDWORDS_SUFFIX,
    ];
  }

  /**
   * Get languages.
   *
   * @return array
   */
  public static function getLanguages() {
    return [
      self::LANG_SOLR,
      self::LANG_SQL_FTS,
      self::LANG_SQL_FTSBOOL,
      self::LANG_SQL_LIKE,
    ];
  }

  /**
   * @param $text
   *
   * Ex: drush eval 'civicrm_initialize(); CRM_Utils_QueryFormatter::dumpExampleTable("firstword secondword");'
   */
  public static function dumpExampleTable($text) {
    $width = strlen($text) + 8;
    $buf = '';

    $buf .= sprintf("%-{$width}s", 'mode');
    foreach (self::getLanguages() as $lang) {
      $buf .= sprintf("%-{$width}s", $lang);
    }
    $buf .= "\n";

    foreach (self::getModes() as $mode) {
      $formatter = new CRM_Utils_QueryFormatter($mode);
      $buf .= sprintf("%-{$width}s", $mode);
      foreach (self::getLanguages() as $lang) {
        $buf .= sprintf("%-{$width}s", $formatter->format($text, $lang));
      }
      $buf .= "\n";
    }

    echo $buf;
  }

}

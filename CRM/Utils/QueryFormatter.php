<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
  * @copyright CiviCRM LLC (c) 2004-2015
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
  const LANG_SQL_LIKE = 'like';
  const LANG_SQL_FTS = 'fts';
  const LANG_SQL_FTSBOOL = 'ftsbool';
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
          $result = $this->mapWords($text, '+word');
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
   * @return string
   */
  protected function mapWords($text, $template) {
    $result = array();
    foreach ($this->parseWords($text) as $word) {
      $result[] = str_replace('word', $word, $template);
    }
    return implode(' ', $result);
  }

  /**
   * @param $text
   * @return array
   */
  protected function parseWords($text) {
    return explode(' ', preg_replace('/[ \r\n\t]+/', ' ', trim($text)));
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
    return array(
      self::MODE_NONE,
      self::MODE_PHRASE,
      self::MODE_WILDPHRASE,
      self::MODE_WILDWORDS,
      self::MODE_WILDWORDS_SUFFIX,
    );
  }

  /**
   * Get languages.
   *
   * @return array
   */
  public static function getLanguages() {
    return array(
      self::LANG_SOLR,
      self::LANG_SQL_FTS,
      self::LANG_SQL_FTSBOOL,
      self::LANG_SQL_LIKE,
    );
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

<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * This class is for displaying alphabetical bar
 */
class CRM_Utils_PagerAToZ {

  /**
   * Returns the alphabetic array for sorting by character.
   *
   * @param array $query
   *   The query object.
   * @param string $sortByCharacter
   *   The character that we are potentially sorting on.
   *
   * @param bool $isDAO
   *
   * @return string
   *   The html formatted string
   */
  public static function getAToZBar(&$query, $sortByCharacter, $isDAO = FALSE) {
    $AToZBar = self::createLinks($query, $sortByCharacter, $isDAO);
    return $AToZBar;
  }

  /**
   * Return the all the static characters.
   *
   * @return array
   *   is an array of static characters
   */
  public static function getStaticCharacters() {
    $staticAlphabets = array(
      'A',
      'B',
      'C',
      'D',
      'E',
      'F',
      'G',
      'H',
      'I',
      'J',
      'K',
      'L',
      'M',
      'N',
      'O',
      'P',
      'Q',
      'R',
      'S',
      'T',
      'U',
      'V',
      'W',
      'X',
      'Y',
      'Z',
    );
    return $staticAlphabets;
  }

  /**
   * Return the all the dynamic characters.
   *
   * @param $query
   * @param $isDAO
   *
   * @return array
   *   is an array of dynamic characters
   */
  public static function getDynamicCharacters(&$query, $isDAO) {
    if ($isDAO) {
      $result = $query;
    }
    else {
      $result = $query->alphabetQuery();
    }
    if (!$result) {
      return NULL;
    }

    $dynamicAlphabets = array();
    while ($result->fetch()) {
      $dynamicAlphabets[] = $result->sort_name;
    }
    return $dynamicAlphabets;
  }

  /**
   * Create the links.
   *
   * @param array $query
   *   The form values for search.
   * @param string $sortByCharacter
   *   The character that we are potentially sorting on.
   *
   * @param $isDAO
   *
   * @return array
   *   with links
   */
  public static function createLinks(&$query, $sortByCharacter, $isDAO) {
    $AToZBar = self::getStaticCharacters();
    $dynamicAlphabets = self::getDynamicCharacters($query, $isDAO);

    if (!$dynamicAlphabets) {
      return NULL;
    }

    $AToZBar = array_merge($AToZBar, $dynamicAlphabets);
    sort($AToZBar, SORT_STRING);
    $AToZBar = array_unique($AToZBar);

    // get the current path
    $path = CRM_Utils_System::currentPath();

    $qfKey = NULL;
    if (isset($query->_formValues)) {
      $qfKey = CRM_Utils_Array::value('qfKey', $query->_formValues);
    }
    if (empty($qfKey)) {
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this, FALSE, NULL, $_REQUEST);
    }

    $aToZBar = array();
    foreach ($AToZBar as $key => $link) {
      if ($link === NULL) {
        continue;
      }

      $element = array();
      if (in_array($link, $dynamicAlphabets)) {
        $klass = '';
        if ($link == $sortByCharacter) {
          $element['class'] = "active";
          $klass = 'class="active"';
        }
        $url = CRM_Utils_System::url($path, "force=1&qfKey=$qfKey&sortByCharacter=");
        // we do it this way since we want the url to be encoded but not the link character
        // since that seems to mess up drupal utf-8 encoding etc
        $url .= urlencode($link);
        $element['item'] = sprintf('<a href="%s" %s>%s</a>',
          $url,
          $klass,
          $link
        );
      }
      else {
        $element['item'] = $link;
      }
      $aToZBar[] = $element;
    }

    $url = sprintf(
      '<a href="%s">%s</a>',
      CRM_Utils_System::url(
        $path,
        "force=1&qfKey=$qfKey&sortByCharacter=all"
      ),
      ts('All')
    );
    $aToZBar[] = array('item' => $url);
    return $aToZBar;
  }

}

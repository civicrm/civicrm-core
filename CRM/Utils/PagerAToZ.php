<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
    $staticAlphabets = [
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
    ];
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

    $dynamicAlphabets = [];
    while ($result->fetch()) {
      $dynamicAlphabets[] = strtoupper($result->sort_name ?? '');
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
   * @throws \CRM_Core_Exception
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
      $qfKey = $query->_formValues['qfKey'] ?? NULL;
    }
    if (empty($qfKey)) {
      $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String');
    }

    $aToZBar = [];
    foreach ($AToZBar as $key => $link) {
      if ($link === NULL) {
        continue;
      }

      $element = ['class' => ''];
      if (in_array($link, $dynamicAlphabets)) {
        $klass = '';
        if ($link == $sortByCharacter) {
          $element['class'] = "active";
          $klass = 'class="active"';
        }
        $urlParams = [
          'force' => 1,
          'qfKey' => $qfKey,
        ];
        if (($query->_context ?? '') === 'amtg') {
          // See https://lab.civicrm.org/dev/core/-/issues/2333
          // Seems to be needed in add to group flow.
          $urlParams['_qf_Basic_display'] = 1;
        }
        $urlParams['sortByCharacter'] = '';
        $url = CRM_Utils_System::url($path, $urlParams);
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
    $aToZBar[] = ['item' => $url, 'class' => ''];
    return $aToZBar;
  }

}

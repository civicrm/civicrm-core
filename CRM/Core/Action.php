<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * The core concept of the system is an action performed on an object. Typically this will be a "data model" object
 * as specified in the API specs. We attempt to keep the number and type of actions consistent
 * and similar across all objects (thus providing both reuse and standards)
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Core_Action {

  /**
   * Different possible actions are defined here. Keep in sync with the
   * constant from CRM_Core_Form for various modes.
   *
   * @var integer const
   *
   * @access public
   */
  CONST
    NONE = 0,
    ADD = 1,
    UPDATE = 2,
    VIEW = 4,
    DELETE = 8,
    BROWSE = 16,
    ENABLE = 32,
    DISABLE = 64,
    EXPORT = 128,
    BASIC = 256,
    ADVANCED = 512,
    PREVIEW = 1024,
    FOLLOWUP = 2048,
    MAP = 4096,
    PROFILE = 8192,
    COPY = 16384,
    RENEW = 32768,
    DETACH = 65536,
    REVERT = 131072,
    CLOSE        =  262144,
    REOPEN       =  524288,
    MAX_ACTION   = 1048575;

  //make sure MAX_ACTION = 2^n - 1 ( n = total number of actions )

  /**
   * map the action names to the relevant constant. We perform
   * bit manipulation operations so we can perform multiple
   * actions on the same object if needed
   *
   * @var array  _names  tupe of variable name to action constant
   *
   * @access private
   * @static
   *
   */
  static $_names = array(
    'add' => self::ADD,
    'update' => self::UPDATE,
    'view' => self::VIEW,
    'delete' => self::DELETE,
    'browse' => self::BROWSE,
    'enable' => self::ENABLE,
    'disable' => self::DISABLE,
    'export' => self::EXPORT,
    'preview' => self::PREVIEW,
    'map' => self::MAP,
    'copy' => self::COPY,
    'profile' => self::PROFILE,
    'renew' => self::RENEW,
    'detach' => self::DETACH,
    'revert' => self::REVERT,
                           'close'         => self::CLOSE,
                           'reopen'        => self::REOPEN,
  );

  /**
   * the flipped version of the names array, initialized when used
   *
   * @var array
   * @static
   */
  static $_description;

  /**
   *
   * called by the request object to translate a string into a mask
   *
   * @param $str
   *
   * @internal param string $action the action to be resolved
   *
   * @return int the action mask corresponding to the input string
   * @access public
   * @static
   */
  static function resolve($str) {
    $action = 0;
    if ($str) {
      $items = explode('|', $str);
      $action = self::map($items);
    }
    return $action;
  }

  /**
   * Given a string or an array of strings, determine the bitmask
   * for this set of actions
   *
   * @param mixed either a single string or an array of strings
   *
   * @return int the action mask corresponding to the input args
   * @access public
   * @static
   *
   */
  static function map($item) {
    $mask = 0;

    if (is_array($item)) {
      foreach ($item as $it) {
        $mask |= self::mapItem($it);
      }
      return $mask;
    }
    else {
      return self::mapItem($item);
    }
  }

  /**
   * Given a string determine the bitmask for this specific string
   *
   * @param string the input action to process
   *
   * @return int the action mask corresponding to the input string
   * @access public
   * @static
   *
   */
  static function mapItem($item) {
    $mask = CRM_Utils_Array::value(trim($item), self::$_names);
    return $mask ? $mask : 0;
  }

  /**
   *
   * Given an action mask, find the corresponding description
   *
   * @param int the action mask
   *
   * @return string the corresponding action description
   * @access public
   * @static
   *
   */
  static function description($mask) {
    if (!isset($_description)) {
      self::$_description = array_flip(self::$_names);
    }

    return CRM_Utils_Array::value($mask, self::$_description, 'NO DESCRIPTION SET');
  }

  /**
   * given a set of links and a mask, return the html action string for
   * the links associated with the mask
   *
   * @param array $links the set of link items
   * @param int $mask the mask to be used. a null mask means all items
   * @param array $values the array of values for parameter substitution in the link items
   * @param string $extraULName enclosed extra links in this UL.
   * @param boolean $enclosedAllInSingleUL force to enclosed all links in single UL.
   *
   * @param null $op
   * @param null $objectName
   * @param null $objectId
   *
   * @return string       the html string
   * @access public
   * @static
   */
  static function formLink($links,
    $mask,
    $values,
    $extraULName = 'more',
    $enclosedAllInSingleUL = FALSE,
    $op = NULL,
    $objectName = NULL,
    $objectId = NULL
  ) {
    $config = CRM_Core_Config::singleton();
    if (empty($links)) {
      return NULL;
    }

    // make links indexed sequentially instead of by bitmask
    // otherwise it's next to impossible to reliably add new ones
    $seqLinks = array();
    foreach ($links as $bit => $link) {
      $link['bit'] = $bit;
      $seqLinks[] = $link;
    }

    if ($op && $objectName && $objectId) {
      CRM_Utils_Hook::links($op, $objectName, $objectId, $seqLinks, $mask, $values);
    }

    $url = array();

    foreach ($seqLinks as $i => $link) {
      if (!$mask || !array_key_exists('bit', $link) || ($mask & $link['bit'])) {
        $extra = isset($link['extra']) ? self::replace($link['extra'], $values) : NULL;

        $frontend = (isset($link['fe'])) ? TRUE : FALSE;

        if (isset($link['qs']) && !CRM_Utils_System::isNull($link['qs'])) {
          $urlPath = CRM_Utils_System::url(self::replace($link['url'], $values),
            self::replace($link['qs'], $values), TRUE, NULL, TRUE, $frontend
          );
        }
        else {
          $urlPath = CRM_Utils_Array::value('url', $link, '#');
        }

        $classes = 'action-item crm-hover-button';
        if (isset($link['ref'])) {
          $classes .= ' ' . strtolower($link['ref']);
        }

        //get the user specified classes in.
        if (isset($link['class'])) {
          $className = is_array($link['class']) ? implode(' ', $link['class']) : $link['class'];
          $classes .= ' ' . strtolower($className);
        }

        if ($urlPath !== '#' && $frontend) {
          $extra .= ' target="_blank"';
        }
        // Hack to make delete dialogs smaller
        if (strpos($urlPath, '/delete') || strpos($urlPath, 'action=delete')) {
          $classes .= " small-popup";
        }
        $url[] = sprintf('<a href="%s" class="%s" %s' . $extra . '>%s</a>',
          $urlPath,
          $classes,
          !empty($link['title']) ? "title='{$link['title']}' " : '',
          $link['name']
        );
      }
    }


    $mainLinks = $url;
    if ($enclosedAllInSingleUL) {
      $allLinks = '';
      CRM_Utils_String::append($allLinks, '</li><li>', $mainLinks);
      $allLinks = "{$extraULName}<ul class='panel'><li>{$allLinks}</li></ul>";
      $result = "<span class='btn-slide crm-hover-button'>{$allLinks}</span>";
    }
    else {
      $extra = '';
      $extraLinks = array_splice($url, 2);
      if (count($extraLinks) > 1) {
        $mainLinks = array_slice($url, 0, 2);
        CRM_Utils_String::append($extra, '</li><li>', $extraLinks);
        $extra = "{$extraULName}<ul class='panel'><li>{$extra}</li></ul>";
      }
      $resultLinks = '';
      CRM_Utils_String::append($resultLinks, '', $mainLinks);
      if ($extra) {
        $result = "<span>{$resultLinks}</span><span class='btn-slide crm-hover-button'>{$extra}</span>";
      }
      else {
        $result = "<span>{$resultLinks}</span>";
      }
    }

    return $result;
  }

  /**
   * given a string and an array of values, substitute the real values
   * in the placeholder in the str in the CiviCRM format
   *
   * @param string $str    the string to be replaced
   * @param array  $values the array of values for parameter substitution in the str
   *
   * @return string        the substituted string
   * @access public
   * @static
   */
  static function &replace(&$str, &$values) {
    foreach ($values as $n => $v) {
      $str = str_replace("%%$n%%", $v, $str);
    }
    return $str;
  }

  /**
   * get the mask for a permission (view, edit or null)
   *
   * @param string the permission
   *
   * @return int   the mask for the above permission
   * @static
   * @access public
   */
  static function mask($permissions) {
    $mask = NULL;
    if (!is_array($permissions) || CRM_Utils_System::isNull($permissions)) {
      return $mask;
    }
    //changed structure since we are handling delete separately - CRM-4418
    if (in_array(CRM_Core_Permission::VIEW, $permissions)) {
      $mask |= self::VIEW | self::EXPORT | self::BASIC | self::ADVANCED | self::BROWSE | self::MAP | self::PROFILE;
    }
    if (in_array(CRM_Core_Permission::DELETE, $permissions)) {
      $mask |= self::DELETE;
    }
    if (in_array(CRM_Core_Permission::EDIT, $permissions)) {
      //make sure we make self::MAX_ACTION = 2^n - 1
      //if we add more actions; ( n = total number of actions )
      $mask |= (self::MAX_ACTION & ~self::DELETE);
    }

    return $mask;
  }
}


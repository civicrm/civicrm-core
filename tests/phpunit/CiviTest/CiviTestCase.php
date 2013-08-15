<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CiviTestCase extends PHPUnit_Framework_Testcase {
  function __construct() {
    parent::__construct();

    require_once 'CRM/Core/Config.php';
    $config = CRM_Core_Config::singleton();
  }

  function civiGet($path, $params, $abort = FALSE) {
    $url = CRM_Utils_System::url($path, $params, TRUE, NULL, FALSE);

    return $this->civiGetURL($url, $abort);
  }

  function civiGetURL($url, $abort = FALSE) {
    $html = $this->_browser->get($url);

    if ($this->drupalCheckAuth(TRUE)) {
      $html .= $this->drupalCheckAuth();
    }

    $this->_content = $this->_browser->getContent();

    if ($abort) {
      echo $html;
      exit();
    }

    return $html;
  }

  function getUrlsByLabel($label, $fuzzy = FALSE) {
    if (!$fuzzy) {
      return $this->_browser->_page->getUrlsByLabel($label);
    }

    $matches = array();
    foreach ($this->_browser->_page->_links as $link) {
      $text = $link->getText();
      if ($text == $label ||
        strpos($text, $label) !== FALSE
      ) {
        $matches[] = $this->_browser->_page->_getUrlFromLink($link);
      }
    }
    return $matches;
  }

  function isCiviURL($url, $ignoreVariations = TRUE) {
    static $config = NULL;
    if (!$config) {
      $config = CRM_Core_Config::singleton();
    }

    if (strpos($url, $config->userFrameworkBaseURL . 'civicrm/') === FALSE) {
      return FALSE;
    }

    // ignore all urls with snippet, force, crmSID
    if ($ignoreVariations &&
      (strpos($url, 'snippet=') ||
        strpos($url, 'force=') ||
        strpos($url, 'crmSID=')
      )
    ) {
      return FALSE;
    }

    return TRUE;
  }

  function getUrlsByToken($token, $path = NULL) {
    $matches = array();
    foreach ($this->_browser->_page->_links as $link) {
      $text = $link->getText();
      $url = $this->_browser->_page->_getUrlFromLink($link)->asString();
      if ($this->isCiviURL($url) &&
        (strpos($url, $token) !== FALSE)
      ) {
        if (!$path ||
          strpos($url, $path) !== FALSE
        ) {
          $matches[$text] = $url;
        }
      }
    }
    return $matches;
  }

  function clickLink($label, $index = 0, $fuzzy = FALSE) {
    if (!$fuzzy) {
      return parent::clickLink($label, $index);
    }

    $url_before = str_replace('%', '%%', $this->getUrl());
    $urls = $this->getUrlsByLabel($label, TRUE);
    if (count($urls) < $index + 1) {
      $url_target = 'URL NOT FOUND!';
    }
    else {
      $url_target = str_replace('%', '%%', $urls[$index]->asString());
    }

    $this->_browser->_load($urls[$index], new SimpleGetEncoding());
    $ret = $this->_failOnError($this->_browser->getContent());

    $this->assertTrue($ret, ' [browser] clicked link ' . t($label) . " ($url_target) from $url_before");

    return $ret;
  }

  function allPermissions() {
    return array(
      1 => 'add contacts',
      2 => 'view all contacts',
      3 => 'edit all contacts',
      4 => 'import contacts',
      5 => 'edit groups',
      6 => 'administer CiviCRM',
      7 => 'access uploaded files',
      8 => 'profile listings and forms',
      9 => 'access all custom data',
      10 => 'view all activities',
      11 => 'access CiviCRM',
      12 => 'access Contact Dashboard',
    );
  }

  function errorPage(&$ret, &$url) {
    // check if there is a civicrm error or warning message on the page
    // at a later stage, we should also check for CMS based errors
    $this->assertTrue($ret, ts(' [browser] GET %1"', array('%1' => $url)));

    $this->assertNoText('Sorry. A non-recoverable error has occurred', '[browser] fatal error page?');
    $this->assertNoText('The requested page could not be found', '[browser] page not found?');
    $this->assertNoText('You are not authorized to access this page', '[browser] permission denied?');

    return;
  }
}


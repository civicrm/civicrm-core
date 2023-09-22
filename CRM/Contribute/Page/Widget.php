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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_Page_Widget extends CRM_Core_Page {

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   *
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $config = CRM_Core_Config::singleton();
    $template = CRM_Core_Smarty::singleton();

    $cpageId = CRM_Utils_Request::retrieve('cpageId', 'Positive');
    $widgetId = CRM_Utils_Request::retrieve('widgetId', 'Positive');
    $format = CRM_Utils_Request::retrieve('format', 'Positive');
    $includePending = CRM_Utils_Request::retrieve('includePending', 'Boolean');

    $jsonvar = 'jsondata';
    if (isset($format)) {
      $jsonvar .= $cpageId;
    }

    $data = CRM_Contribute_BAO_Widget::getContributionPageData($cpageId, $widgetId, $includePending);

    $output = '
        var ' . $jsonvar . ' = ' . json_encode($data) . ';
    ';

    CRM_Core_Page_AJAX::setJsHeaders(60);
    echo $output;
    CRM_Utils_System::civiExit();
  }

}

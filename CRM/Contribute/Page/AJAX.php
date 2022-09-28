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

/**
 * This class contains all the function that are called using AJAX (jQuery)
 */
class CRM_Contribute_Page_AJAX {

  /**
   * Get Soft credit to list in DT
   */
  public static function getSoftContributionRows() {
    $requiredParameters = [
      'cid' => 'Integer',
      'context' => 'String',
    ];
    $optionalParameters = [
      'entityID' => 'Integer',
      'isTest' => 'Integer',
    ];

    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams($requiredParameters, $optionalParameters);

    $softCreditList = CRM_Contribute_BAO_ContributionSoft::getSoftContributionSelector($params);

    if (!empty($_GET['is_unit_test'])) {
      return $softCreditList;
    }

    CRM_Utils_JSON::output($softCreditList);
  }

}

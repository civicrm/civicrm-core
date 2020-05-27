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
class CRM_Utils_Check_Component_PriceFields extends CRM_Utils_Check_Component {

  /**
   * Display warning about invalid priceFields
   *
   */
  public function checkPriceFields() {
    $sql = "SELECT DISTINCT ps.title as ps_title, ps.id as ps_id, psf.label as psf_label
      FROM civicrm_price_set ps
      INNER JOIN civicrm_price_field psf ON psf.price_set_id = ps.id
      INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = psf.id
      LEFT JOIN civicrm_financial_type cft ON cft.id = pfv.financial_type_id
      WHERE cft.id IS NULL OR cft.is_active = 0";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $count = 0;
    $html = '';
    $messages = [];
    while ($dao->fetch()) {
      $count++;
      $url = CRM_Utils_System::url('civicrm/admin/price/field', [
        'reset' => 1,
        'action' => 'browse',
        'sid' => $dao->ps_id,
      ]);
      $html .= "<tr><td>$dao->ps_title</td><td>$dao->psf_label</td><td><a href='$url'>View Price Set Fields</a></td></tr>";
    }
    if ($count > 0) {
      $msg = "<p>the following Price Set Fields use disabled or invalid financial types and need to be fixed if they are to still be used.<p>
          <p><table><thead><tr><th>Price Set</th><th>Price Set Field</th><th>Action Link</th>
          </tr></thead><tbody>
          $html
          </tbody></table></p>";
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
       ts($msg),
       ts('Invalid Price Fields'),
       \Psr\Log\LogLevel::WARNING,
       'fa-lock'
      );
    }
    return $messages;
  }

}

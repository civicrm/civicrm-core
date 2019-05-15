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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
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

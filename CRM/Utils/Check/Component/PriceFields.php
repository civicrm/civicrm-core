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

use Psr\Log\LogLevel;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Check_Component_PriceFields extends CRM_Utils_Check_Component {

  /**
   * Display warning about invalid priceFields.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkPriceFields(): array {
    $sql = "SELECT DISTINCT ps.title as ps_title, ps.id as ps_id, psf.label as psf_label
      FROM civicrm_price_set ps
      INNER JOIN civicrm_price_field psf ON psf.price_set_id = ps.id
      INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = psf.id
      LEFT JOIN civicrm_financial_type cft ON cft.id = pfv.financial_type_id
      INNER JOIN civicrm_price_set_entity pse ON entity_table = 'civicrm_contribution_page' AND ps.id = pse.price_set_id
      INNER JOIN civicrm_contribution_page cp ON cp.id = pse.entity_id AND cp.is_active = 1
      WHERE cft.id IS NULL OR cft.is_active = 0
      UNION
      SELECT DISTINCT ps.title as ps_title, ps.id as ps_id, psf.label as psf_label
      FROM civicrm_price_set ps
      INNER JOIN civicrm_price_field psf ON psf.price_set_id = ps.id
      INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = psf.id
      LEFT JOIN civicrm_financial_type cft ON cft.id = pfv.financial_type_id
      INNER JOIN civicrm_price_set_entity pse ON entity_table = 'civicrm_event' AND ps.id = pse.price_set_id
      INNER JOIN civicrm_event ce ON ce.id = pse.entity_id AND ce.is_active = 1
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
      $html .= "<tr><td>$dao->ps_title</td><td>$dao->psf_label</td><td><a href='$url'>" . ts('View Price Set Fields') . '</a></td></tr>';
    }
    if ($count > 0) {
      $msg = '<p>' . ts('The following Price Set Fields use disabled or invalid financial types and need to be fixed if they are to still be used.') . '<p>'
        . '<p><table><thead><tr><th>' . ts('Price Set') . '</th><th>' . ts('Price Set Field') . '</th><th>' . ts('Action') . '</th>'
        . '</tr></thead><tbody>'
        . $html
        . '</tbody></table></p>';
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
       $msg,
       ts('Invalid Price Fields'),
       LogLevel::WARNING,
       'fa-lock'
      );
    }
    return $messages;
  }

  /**
   * Display warning about invalid priceFields.
   *
   * @return CRM_Utils_Check_Message[]
   */
  public function checkPriceFieldDeductibility(): array {
    $sql = "SELECT DISTINCT ps.title as ps_title, ps.id as ps_id, psf.label as psf_label
      FROM civicrm_price_set ps
      INNER JOIN civicrm_price_field psf ON psf.price_set_id = ps.id
      INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = psf.id
      LEFT JOIN civicrm_financial_type cft ON cft.id = pfv.financial_type_id
      INNER JOIN civicrm_price_set_entity pse ON entity_table = 'civicrm_contribution_page' AND ps.id = pse.price_set_id
      INNER JOIN civicrm_contribution_page cp ON cp.id = pse.entity_id AND cp.is_active = 1
      WHERE pfv.non_deductible_amount > 0 AND cft.is_deductible = 0
      UNION
      SELECT DISTINCT ps.title as ps_title, ps.id as ps_id, psf.label as psf_label
      FROM civicrm_price_set ps
      INNER JOIN civicrm_price_field psf ON psf.price_set_id = ps.id
      INNER JOIN civicrm_price_field_value pfv ON pfv.price_field_id = psf.id
      LEFT JOIN civicrm_financial_type cft ON cft.id = pfv.financial_type_id
      INNER JOIN civicrm_price_set_entity pse ON entity_table = 'civicrm_event' AND ps.id = pse.price_set_id
      INNER JOIN civicrm_event ce ON ce.id = pse.entity_id AND ce.is_active = 1
      WHERE pfv.non_deductible_amount > 0 AND cft.is_deductible = 0";
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
      $html .= "<tr><td>$dao->ps_title</td><td>$dao->psf_label</td><td><a href='$url'>" . ts('View Price Set Fields') . '</a></td></tr>';
    }
    if ($count > 0) {
      $msg = '<p>' . ts('The following Price Set Fields have options with non_deductible amounts but financial types that are not configured to be deductible.') . '<p>'
        . '<p><table><thead><tr><th>' . ts('Price Set') . '</th><th>' . ts('Price Set Field') . '</th><th>' . ts('Action') . '</th>'
        . '</tr></thead><tbody>'
        . $html
        . '</tbody></table></p>';
      $messages[] = new CRM_Utils_Check_Message(
        __FUNCTION__,
        $msg,
        ts('Invalid Price Fields'),
        LogLevel::WARNING,
        'fa-lock'
      );
    }
    return $messages;
  }

}

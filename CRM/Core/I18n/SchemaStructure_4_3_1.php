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
 * $Id$
 *
 */
class CRM_Core_I18n_SchemaStructure_4_3_1 {
  /**
   * @return array
   */
  public static function &columns() {
    static $result = NULL;
    if (!$result) {
      $result = [
        'civicrm_location_type' => [
          'display_name' => "varchar(64)",
        ],
        'civicrm_option_group' => [
          'title' => "varchar(255)",
          'description' => "varchar(255)",
        ],
        'civicrm_contact_type' => [
          'label' => "varchar(64)",
          'description' => "text",
        ],
        'civicrm_batch' => [
          'title' => "varchar(64)",
          'description' => "text",
        ],
        'civicrm_premiums' => [
          'premiums_intro_title' => "varchar(255)",
          'premiums_intro_text' => "text",
          'premiums_nothankyou_label' => "varchar(255)",
        ],
        'civicrm_membership_status' => [
          'label' => "varchar(128)",
        ],
        'civicrm_survey' => [
          'thankyou_title' => "varchar(255)",
          'thankyou_text' => "text",
        ],
        'civicrm_participant_status_type' => [
          'label' => "varchar(255)",
        ],
        'civicrm_tell_friend' => [
          'title' => "varchar(255)",
          'intro' => "text",
          'suggested_message' => "text",
          'thankyou_title' => "varchar(255)",
          'thankyou_text' => "text",
        ],
        'civicrm_custom_group' => [
          'title' => "varchar(64)",
          'help_pre' => "text",
          'help_post' => "text",
        ],
        'civicrm_custom_field' => [
          'label' => "varchar(255)",
          'help_pre' => "text",
          'help_post' => "text",
        ],
        'civicrm_option_value' => [
          'label' => "varchar(255)",
          'description' => "text",
        ],
        'civicrm_group' => [
          'title' => "varchar(64)",
        ],
        'civicrm_contribution_page' => [
          'title' => "varchar(255)",
          'intro_text' => "text",
          'pay_later_text' => "text",
          'pay_later_receipt' => "text",
          'initial_amount_label' => "varchar(255)",
          'initial_amount_help_text' => "text",
          'thankyou_title' => "varchar(255)",
          'thankyou_text' => "text",
          'thankyou_footer' => "text",
          'for_organization' => "text",
          'receipt_from_name' => "varchar(255)",
          'receipt_text' => "text",
          'footer_text' => "text",
          'honor_block_title' => "varchar(255)",
          'honor_block_text' => "text",
        ],
        'civicrm_product' => [
          'name' => "varchar(255)",
          'description' => "text",
          'options' => "text",
        ],
        'civicrm_membership_type' => [
          'name' => "varchar(128)",
          'description' => "varchar(255)",
        ],
        'civicrm_membership_block' => [
          'new_title' => "varchar(255)",
          'new_text' => "text",
          'renewal_title' => "varchar(255)",
          'renewal_text' => "text",
        ],
        'civicrm_price_set' => [
          'title' => "varchar(255)",
          'help_pre' => "text",
          'help_post' => "text",
        ],
        'civicrm_dashboard' => [
          'label' => "varchar(255)",
        ],
        'civicrm_uf_group' => [
          'title' => "varchar(64)",
          'help_pre' => "text",
          'help_post' => "text",
        ],
        'civicrm_uf_field' => [
          'help_post' => "text",
          'help_pre' => "text",
          'label' => "varchar(255)",
        ],
        'civicrm_price_field' => [
          'label' => "varchar(255)",
          'help_pre' => "text",
          'help_post' => "text",
        ],
        'civicrm_price_field_value' => [
          'label' => "varchar(255)",
          'description' => "text",
        ],
        'civicrm_pcp_block' => [
          'link_text' => "varchar(255)",
        ],
        'civicrm_event' => [
          'title' => "varchar(255)",
          'summary' => "text",
          'description' => "text",
          'registration_link_text' => "varchar(255)",
          'event_full_text' => "text",
          'fee_label' => "varchar(255)",
          'intro_text' => "text",
          'footer_text' => "text",
          'confirm_title' => "varchar(255)",
          'confirm_text' => "text",
          'confirm_footer_text' => "text",
          'confirm_email_text' => "text",
          'confirm_from_name' => "varchar(255)",
          'thankyou_title' => "varchar(255)",
          'thankyou_text' => "text",
          'thankyou_footer_text' => "text",
          'pay_later_text' => "text",
          'pay_later_receipt' => "text",
          'initial_amount_label' => "varchar(255)",
          'initial_amount_help_text' => "text",
          'waitlist_text' => "text",
          'approval_req_text' => "text",
          'template_title' => "varchar(255)",
        ],
      ];
    }
    return $result;
  }

  /**
   * @return array
   */
  public static function &indices() {
    static $result = NULL;
    if (!$result) {
      $result = [
        'civicrm_custom_group' => [
          'UI_title_extends' => [
            'name' => 'UI_title_extends',
            'field' => [
              'title',
              'extends',
            ],
            'unique' => 1,
          ],
        ],
        'civicrm_custom_field' => [
          'UI_label_custom_group_id' => [
            'name' => 'UI_label_custom_group_id',
            'field' => [
              'label',
              'custom_group_id',
            ],
            'unique' => 1,
          ],
        ],
        'civicrm_group' => [
          'UI_title' => [
            'name' => 'UI_title',
            'field' => [
              'title',
            ],
            'unique' => 1,
          ],
        ],
      ];
    }
    return $result;
  }

  /**
   * @return array
   */
  public static function &tables() {
    static $result = NULL;
    if (!$result) {
      $result = array_keys(self::columns());
    }
    return $result;
  }

}

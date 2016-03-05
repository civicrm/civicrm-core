<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Core_I18n_SchemaStructure_3_4_beta2 {
  /**
   * @return array
   */
  public static function &columns() {
    static $result = NULL;
    if (!$result) {
      $result = array(
        'civicrm_option_group' => array(
          'label' => "varchar(255)",
          'description' => "varchar(255)",
        ),
        'civicrm_contact' => array(
          'sort_name' => "varchar(128)",
          'display_name' => "varchar(128)",
          'first_name' => "varchar(64)",
          'middle_name' => "varchar(64)",
          'last_name' => "varchar(64)",
          'email_greeting_display' => "varchar(255)",
          'postal_greeting_display' => "varchar(255)",
          'addressee_display' => "varchar(255)",
          'household_name' => "varchar(128)",
          'organization_name' => "varchar(128)",
        ),
        'civicrm_contact_type' => array(
          'label' => "varchar(64)",
          'description' => "text",
        ),
        'civicrm_mailing_component' => array(
          'name' => "varchar(64)",
          'subject' => "varchar(255)",
          'body_html' => "text",
          'body_text' => "text",
        ),
        'civicrm_premiums' => array(
          'premiums_intro_title' => "varchar(255)",
          'premiums_intro_text' => "text",
        ),
        'civicrm_product' => array(
          'name' => "varchar(255)",
          'description' => "text",
          'options' => "text",
        ),
        'civicrm_membership_type' => array(
          'name' => "varchar(128)",
          'description' => "varchar(255)",
        ),
        'civicrm_membership_status' => array(
          'label' => "varchar(128)",
        ),
        'civicrm_participant_status_type' => array(
          'label' => "varchar(255)",
        ),
        'civicrm_tell_friend' => array(
          'title' => "varchar(255)",
          'intro' => "text",
          'suggested_message' => "text",
          'thankyou_title' => "varchar(255)",
          'thankyou_text' => "text",
        ),
        'civicrm_price_set' => array(
          'title' => "varchar(255)",
          'help_pre' => "text",
          'help_post' => "text",
        ),
        'civicrm_batch' => array(
          'label' => "varchar(64)",
          'description' => "text",
        ),
        'civicrm_custom_group' => array(
          'title' => "varchar(64)",
          'help_pre' => "text",
          'help_post' => "text",
        ),
        'civicrm_custom_field' => array(
          'label' => "varchar(255)",
          'help_pre' => "text",
          'help_post' => "text",
        ),
        'civicrm_dashboard' => array(
          'label' => "varchar(255)",
        ),
        'civicrm_option_value' => array(
          'label' => "varchar(255)",
          'description' => "text",
        ),
        'civicrm_mailing' => array(
          'name' => "varchar(128)",
          'from_name' => "varchar(128)",
          'subject' => "varchar(128)",
          'body_text' => "longtext",
          'body_html' => "longtext",
        ),
        'civicrm_contribution_page' => array(
          'title' => "varchar(255)",
          'intro_text' => "text",
          'pay_later_text' => "text",
          'pay_later_receipt' => "text",
          'thankyou_title' => "varchar(255)",
          'thankyou_text' => "text",
          'thankyou_footer' => "text",
          'for_organization' => "text",
          'receipt_from_name' => "varchar(255)",
          'receipt_text' => "text",
          'footer_text' => "text",
          'honor_block_title' => "varchar(255)",
          'honor_block_text' => "text",
        ),
        'civicrm_membership_block' => array(
          'new_title' => "varchar(255)",
          'new_text' => "text",
          'renewal_title' => "varchar(255)",
          'renewal_text' => "text",
        ),
        'civicrm_price_field' => array(
          'label' => "varchar(255)",
          'help_pre' => "text",
          'help_post' => "text",
        ),
        'civicrm_price_field_value' => array(
          'label' => "varchar(255)",
          'description' => "text",
        ),
        'civicrm_uf_group' => array(
          'title' => "varchar(64)",
          'help_pre' => "text",
          'help_post' => "text",
        ),
        'civicrm_uf_field' => array(
          'help_post' => "text",
          'help_pre' => "text",
          'label' => "varchar(255)",
        ),
        'civicrm_address' => array(
          'street_address' => "varchar(96)",
          'supplemental_address_1' => "varchar(96)",
          'supplemental_address_2' => "varchar(96)",
          'supplemental_address_3' => "varchar(96)",
          'city' => "varchar(64)",
          'name' => "varchar(255)",
        ),
        'civicrm_event' => array(
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
          'waitlist_text' => "text",
          'approval_req_text' => "text",
          'template_title' => "varchar(255)",
        ),
      );
    }
    return $result;
  }

  /**
   * @return array
   */
  public static function &indices() {
    static $result = NULL;
    if (!$result) {
      $result = array(
        'civicrm_contact' => array(
          'index_sort_name' => array(
            'name' => 'index_sort_name',
            'field' => array(
              'sort_name',
            ),
          ),
          'index_first_name' => array(
            'name' => 'index_first_name',
            'field' => array(
              'first_name',
            ),
          ),
          'index_last_name' => array(
            'name' => 'index_last_name',
            'field' => array(
              'last_name',
            ),
          ),
          'index_household_name' => array(
            'name' => 'index_household_name',
            'field' => array(
              'household_name',
            ),
          ),
          'index_organization_name' => array(
            'name' => 'index_organization_name',
            'field' => array(
              'organization_name',
            ),
          ),
        ),
        'civicrm_price_set' => array(
          'UI_title' => array(
            'name' => 'UI_title',
            'field' => array(
              'title',
            ),
            'unique' => 1,
          ),
        ),
        'civicrm_custom_group' => array(
          'UI_title_extends' => array(
            'name' => 'UI_title_extends',
            'field' => array(
              'title',
              'extends',
            ),
            'unique' => 1,
          ),
        ),
        'civicrm_custom_field' => array(
          'UI_label_custom_group_id' => array(
            'name' => 'UI_label_custom_group_id',
            'field' => array(
              'label',
              'custom_group_id',
            ),
            'unique' => 1,
          ),
        ),
        'civicrm_address' => array(
          'index_city' => array(
            'name' => 'index_city',
            'field' => array(
              'city',
            ),
          ),
        ),
      );
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

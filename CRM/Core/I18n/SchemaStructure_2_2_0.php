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
class CRM_Core_I18n_SchemaStructure_2_2_0 {
  /**
   * @return mixed
   */
  public static function &columns() {
    static $result = NULL;
    if (!$result) {
      $result = unserialize('a:17:{s:20:"civicrm_custom_group";a:3:{s:5:"title";s:11:"varchar(64)";s:8:"help_pre";s:4:"text";s:9:"help_post";s:4:"text";}s:20:"civicrm_custom_field";a:3:{s:5:"label";s:12:"varchar(255)";s:8:"help_pre";s:4:"text";s:9:"help_post";s:4:"text";}s:20:"civicrm_option_group";a:2:{s:5:"label";s:12:"varchar(255)";s:11:"description";s:12:"varchar(255)";}s:17:"civicrm_price_set";a:3:{s:5:"title";s:12:"varchar(255)";s:8:"help_pre";s:4:"text";s:9:"help_post";s:4:"text";}s:15:"civicrm_contact";a:7:{s:9:"sort_name";s:12:"varchar(128)";s:12:"display_name";s:12:"varchar(128)";s:10:"first_name";s:11:"varchar(64)";s:11:"middle_name";s:11:"varchar(64)";s:9:"last_name";s:11:"varchar(64)";s:14:"household_name";s:12:"varchar(128)";s:17:"organization_name";s:12:"varchar(128)";}s:16:"civicrm_premiums";a:2:{s:20:"premiums_intro_title";s:12:"varchar(255)";s:19:"premiums_intro_text";s:4:"text";}s:15:"civicrm_product";a:3:{s:4:"name";s:12:"varchar(255)";s:11:"description";s:4:"text";s:7:"options";s:4:"text";}s:23:"civicrm_membership_type";a:2:{s:4:"name";s:12:"varchar(128)";s:11:"description";s:12:"varchar(255)";}s:25:"civicrm_membership_status";a:1:{s:4:"name";s:12:"varchar(128)";}s:19:"civicrm_tell_friend";a:5:{s:5:"title";s:12:"varchar(255)";s:5:"intro";s:4:"text";s:17:"suggested_message";s:4:"text";s:14:"thankyou_title";s:12:"varchar(255)";s:13:"thankyou_text";s:4:"text";}s:20:"civicrm_option_value";a:2:{s:5:"label";s:12:"varchar(255)";s:11:"description";s:12:"varchar(255)";}s:19:"civicrm_price_field";a:3:{s:5:"label";s:12:"varchar(255)";s:8:"help_pre";s:4:"text";s:9:"help_post";s:4:"text";}s:25:"civicrm_contribution_page";a:13:{s:5:"title";s:12:"varchar(255)";s:10:"intro_text";s:4:"text";s:14:"pay_later_text";s:4:"text";s:17:"pay_later_receipt";s:4:"text";s:14:"thankyou_title";s:12:"varchar(255)";s:13:"thankyou_text";s:4:"text";s:15:"thankyou_footer";s:4:"text";s:16:"for_organization";s:4:"text";s:17:"receipt_from_name";s:12:"varchar(255)";s:12:"receipt_text";s:4:"text";s:11:"footer_text";s:4:"text";s:17:"honor_block_title";s:12:"varchar(255)";s:16:"honor_block_text";s:4:"text";}s:24:"civicrm_membership_block";a:4:{s:9:"new_title";s:12:"varchar(255)";s:8:"new_text";s:4:"text";s:13:"renewal_title";s:12:"varchar(255)";s:12:"renewal_text";s:4:"text";}s:16:"civicrm_uf_group";a:3:{s:5:"title";s:11:"varchar(64)";s:8:"help_pre";s:4:"text";s:9:"help_post";s:4:"text";}s:16:"civicrm_uf_field";a:2:{s:9:"help_post";s:4:"text";s:5:"label";s:12:"varchar(255)";}s:13:"civicrm_event";a:18:{s:5:"title";s:12:"varchar(255)";s:7:"summary";s:4:"text";s:11:"description";s:4:"text";s:22:"registration_link_text";s:12:"varchar(255)";s:15:"event_full_text";s:4:"text";s:9:"fee_label";s:12:"varchar(255)";s:10:"intro_text";s:4:"text";s:11:"footer_text";s:4:"text";s:13:"confirm_title";s:12:"varchar(255)";s:12:"confirm_text";s:4:"text";s:19:"confirm_footer_text";s:4:"text";s:18:"confirm_email_text";s:4:"text";s:17:"confirm_from_name";s:12:"varchar(255)";s:14:"thankyou_title";s:12:"varchar(255)";s:13:"thankyou_text";s:4:"text";s:20:"thankyou_footer_text";s:4:"text";s:14:"pay_later_text";s:4:"text";s:17:"pay_later_receipt";s:4:"text";}}');
    }
    return $result;
  }

  /**
   * @return mixed
   */
  public static function &indices() {
    static $result = NULL;
    if (!$result) {
      $result = unserialize('a:4:{s:20:"civicrm_custom_group";a:1:{s:16:"UI_title_extends";a:4:{s:4:"name";s:16:"UI_title_extends";s:5:"field";a:2:{i:0;s:5:"title";i:1;s:7:"extends";}s:11:"localizable";b:1;s:6:"unique";b:1;}}s:20:"civicrm_custom_field";a:1:{s:24:"UI_label_custom_group_id";a:4:{s:4:"name";s:24:"UI_label_custom_group_id";s:5:"field";a:2:{i:0;s:5:"label";i:1;s:15:"custom_group_id";}s:11:"localizable";b:1;s:6:"unique";b:1;}}s:17:"civicrm_price_set";a:1:{s:8:"UI_title";a:4:{s:4:"name";s:8:"UI_title";s:5:"field";a:1:{i:0;s:5:"title";}s:11:"localizable";b:1;s:6:"unique";b:1;}}s:15:"civicrm_contact";a:5:{s:15:"index_sort_name";a:3:{s:4:"name";s:15:"index_sort_name";s:5:"field";a:1:{i:0;s:9:"sort_name";}s:11:"localizable";b:1;}s:16:"index_first_name";a:3:{s:4:"name";s:16:"index_first_name";s:5:"field";a:1:{i:0;s:10:"first_name";}s:11:"localizable";b:1;}s:15:"index_last_name";a:3:{s:4:"name";s:15:"index_last_name";s:5:"field";a:1:{i:0;s:9:"last_name";}s:11:"localizable";b:1;}s:20:"index_household_name";a:3:{s:4:"name";s:20:"index_household_name";s:5:"field";a:1:{i:0;s:14:"household_name";}s:11:"localizable";b:1;}s:23:"index_organization_name";a:3:{s:4:"name";s:23:"index_organization_name";s:5:"field";a:1:{i:0;s:17:"organization_name";}s:11:"localizable";b:1;}}}');
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

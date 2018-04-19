<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 * @copyright CiviCRM LLC (c) 2004-2017
 * $Id$
 *
 */
/*
 * Settings metadata file
 */

return array(
  'cvv_backoffice_required' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'cvv_backoffice_required',
    'type' => 'Boolean',
    'quick_form_type' => 'YesNo',
    'default' => '1',
    'add' => '4.1',
    'title' => 'CVV required for backoffice?',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Is the CVV code required for back office credit card transactions',
    'help_text' => 'If set it back-office credit card transactions will required a cvv code. Leave as required unless you have a very strong reason to change',
  ),
  'contribution_invoice_settings' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'contribution_invoice_settings',
    'type' => 'Array',
    'default' => array(
      'invoice_prefix' => 'INV_',
      'credit_notes_prefix' => 'CN_',
      'due_date' => '10',
      'due_date_period' => 'days',
      'notes' => '',
      'tax_term' => 'Sales Tax',
      'tax_display_settings' => 'Inclusive',
    ),
    'add' => '4.7',
    'title' => 'Contribution Invoice Settings',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'invoicing' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'invoicing',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'quick_form_type' => 'Element',
    'default' => 0,
    'add' => '4.7',
    'title' => 'Enable Tax and Invoicing',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'acl_financial_type' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'acl_financial_type',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'quick_form_type' => 'Element',
    'default' => 0,
    'add' => '4.7',
    'title' => 'Enable Access Control by Financial Type',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'deferred_revenue_enabled' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'deferred_revenue_enabled',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'quick_form_type' => 'Element',
    'default' => 0,
    'add' => '4.7',
    'title' => 'Enable Deferred Revenue',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'default_invoice_page' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'default_invoice_page',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'pseudoconstant' => array(
      'name' => 'contributionPage',
    ),
    'html_type' => 'select',
    'add' => '4.7',
    'title' => 'Default invoice payment page',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'always_post_to_accounts_receivable' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'always_post_to_accounts_receivable',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'quick_form_type' => 'Element',
    'default' => 0,
    'add' => '4.7',
    'title' => 'Always post to Accounts Receivable?',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
  ),
  'update_contribution_on_membership_type_change' => array(
    'group_name' => 'Contribute Preferences',
    'group' => 'contribute',
    'name' => 'update_contribution_on_membership_type_change',
    'type' => 'Integer',
    'html_type' => 'checkbox',
    'quick_form_type' => 'Element',
    'default' => 0,
    'add' => '4.7',
    'title' => 'Automatically update related contributions when Membership Type is changed',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => 'Enabling this setting will update related contribution of membership(s) except if the membership is paid for with a recurring contribution.',
    'help_text' => NULL,
  ),
);

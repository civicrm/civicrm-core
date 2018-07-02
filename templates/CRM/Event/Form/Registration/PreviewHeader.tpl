{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
*}
{* Displays Test-drive mode header for Event Registration pages. *}
<div class="messages status section test_drive-section">
  <i class="crm-i fa-cogs"></i>
     <strong>{ts}Test-drive Your Event Registration Page{/ts}</strong>
         {ts}This page is currently running in <strong>test-drive mode</strong>. If this is a paid event, transactions will be sent to your payment processor's test server. <strong>No live financial transactions will be submitted. However, a contact record will be created or updated and test event registration and contribution records will be saved to the database. Use obvious test contact names so you can review and delete these records as needed. </strong> Refer to your payment processor's documentation for information on values to use for test credit card number, security code, postal code, etc.{/ts}
</div>

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays Test-drive mode header for Event Registration pages. *}
<div class="messages status section test_drive-section">
  <i class="crm-i fa-cogs" role="img" aria-hidden="true"></i>
     <strong>{ts}Test-drive Your Event Registration Page{/ts}</strong>
         {ts}This page is currently running in <strong>test-drive mode</strong>. If this is a paid event, transactions will be sent to your payment processor's test server. <strong>No live financial transactions will be submitted. However, a contact record will be created or updated and test event registration and contribution records will be saved to the database. Use obvious test contact names so you can review and delete these records as needed. </strong> Refer to your payment processor's documentation for information on values to use for test credit card number, security code, postal code, etc.{/ts}
</div>

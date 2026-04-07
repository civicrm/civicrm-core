{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Displays Test-drive mode header for Contribution pages. *}
{if $action & 1024}
  <div class="messages status no-popup">
    <i class="crm-i fa-cogs" role="img" aria-hidden="true"></i>
    <strong>{ts}Test-drive Your Contribution Page{/ts}</strong>
    <p>{ts}This page is currently running in <strong>test-drive mode</strong>. Transactions will be sent to your payment processor's test server. <strong>No live financial transactions will be submitted. However, a contact record will be created or updated and a test contribution record will be saved to the database. Use obvious test contact names so you can review and delete these records as needed. Test contributions are not visible on the Contributions tab, but can be viewed by searching for 'Test Contributions' in the CiviContribute search form.</strong> Refer to your payment processor's documentation for information on values to use for test credit card number, security code, postal code, etc.{/ts}</p>
  </div>
{else}
  <div class="messages status no-popup">
    <i class="crm-i fa-cogs" role="img" aria-hidden="true"></i>
    <strong>{ts}Test payment processor on Your Contribution Page{/ts}</strong>
    <p>{ts 1=$dummyTitle|escape}This page is currently configured to use the <strong>%1 payment processor</strong>. <strong>No live financial transactions will be submitted if %1 payment processor is selected. However, a contact record will be created or updated and a live contribution record will be saved to the database with no actual financial transaction. Remove %1 payment processor before going live.</strong>{/ts}</p>
  </div>
{/if}
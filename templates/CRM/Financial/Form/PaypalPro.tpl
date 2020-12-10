{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $form.$expressButtonName}
  <div class="crm-section no-label paypal_button_info-section">
    <div class="content description">
      {ts}If you have a PayPal account, you can click the PayPal button to continue. Otherwise, fill in the credit card and billing information on this form and click <strong>Continue</strong> at the bottom of the page.{/ts}
    </div>
  </div>
  <div class="crm-section no-label {$form.$expressButtonName.name}-section">
    <div class="content description">
      {$form.$expressButtonName.html}
      <div class="description">{ts}Checkout securely. Pay without sharing your financial information.{/ts}</div>
    </div>
  </div>
{/if}

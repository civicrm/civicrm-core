{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

  <div id="paypalExpress">
    <fieldset class="crm-group paypal_checkout-group">
      <legend>{ts}Checkout with PayPal{/ts}</legend>
      <div class="section">
        <div class="crm-section paypalButtonInfo-section">
          <div class="content">
            <span class="description">{ts}Click the PayPal button to continue.{/ts}</span>
          </div>
          <div class="clear"></div>
        </div>
        <div class="crm-section {$expressButtonName}-section">
          <div class="content">
            {$form.$expressButtonName.html} <span class="description">{ts}Checkout securely. Pay without sharing your financial information.{/ts}</span>
          </div>
          <div class="clear"></div>
        </div>
      </div>
    </fieldset>
  </div>

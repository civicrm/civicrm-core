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

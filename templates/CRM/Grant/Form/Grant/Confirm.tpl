{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
{if $action & 1024}
    {include file="CRM/Contribute/Form/Contribution/PreviewHeader.tpl"}
{/if}

{include file="CRM/common/TrackingFields.tpl"}

<div class="crm-block crm-contribution-confirm-form-block">
    <div id="help">
        <p>{ts}Please verify the information below carefully. Click <strong>Go Back</strong> if you need to make changes.{/ts}
               {ts 1=$button}To complete this grant application, click the <strong>%1</strong> button below.{/ts}
        </p>
    </div>
    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="top"}
    </div>
{if $default_amount_hidden and !$amount_requested}
    <div class="crm-group amount_display-group">
        <div class="header-dark">
            {ts}Grant Requested Amount{/ts}
        </div>
        <div class="display-block">
             {ts}Requested Amount{/ts}: <strong>{$default_amount_hidden|crmMoney}</strong>
        </div>
    </div>
{/if}

    {if $customPre}
            <fieldset class="label-left">
                {include file="CRM/UF/Form/Block.tpl" fields=$customPre}
            </fieldset>
    {/if}
        {if $email}
            <div class="crm-group contributor_email-group">
                <div class="header-dark">
                    {ts}Your Email{/ts}
                </div>
                <div class="crm-section no-label contributor_email-section">
                	<div class="content">{$email}</div>
                	<div class="clear"></div>
                </div>
            </div>
        {/if}
   	{if $customPost}
            <fieldset class="label-left">
                {include file="CRM/UF/Form/Block.tpl" fields=$customPost}
            </fieldset>
    {/if}

    {if $contributeMode eq 'direct' and $paymentProcessor.payment_type & 2}
    <div class="crm-group debit_agreement-group">
        <div class="header-dark">
            {ts}Agreement{/ts}
        </div>
        <div class="display-block">
            {ts}Your account data will be used to charge your bank account via direct debit. While submitting this form you agree to the charging of your bank account via direct debit.{/ts}
        </div>
    </div>
    {/if}

    {if $contributeMode NEQ 'notify' and $is_monetary and ( $amount GT 0 OR $minimum_fee GT 0 ) } {* In 'notify mode, contributor is taken to processor payment forms next *}
    <div class="messages status continue_instructions-section">
        <p>
        {if $is_pay_later OR $amount LE 0.0}
            {ts 1=$button}Your transaction will not be completed until you click the <strong>%1</strong> button. Please click the button one time only.{/ts}
        {else}
            {ts 1=$button}Your contribution will not be completed until you click the <strong>%1</strong> button. Please click the button one time only.{/ts}
        {/if}
        </p>
    </div>
    {/if}

    {if $paymentProcessor.payment_processor_type EQ 'Google_Checkout' and $is_monetary and ( $amount GT 0 OR $minimum_fee GT 0 ) and ! $is_pay_later}
        <fieldset class="crm-group google_checkout-group"><legend>{ts}Checkout with Google{/ts}</legend>
        <table class="form-layout-compressed">
            <tr>
                <td class="description">{ts}Click the Google Checkout button to continue.{/ts}</td>
            </tr>
            <tr>
                <td>{$form._qf_Confirm_next_checkout.html} <span style="font-size:11px; font-family: Arial, Verdana;">Checkout securely.  Pay without sharing your financial information. </span></td>
            </tr>
        </table>
        </fieldset>
    {/if}

    <div id="crm-submit-buttons" class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
    </div>
</div>

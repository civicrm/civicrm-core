{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
{* This template is used for adding/configuring Payment Processors used by a particular site/domain.  *}
<h3>{if $action eq 1}{ts}New Payment Processor{/ts}{elseif $action eq 2}{ts}Edit Payment Processor{/ts}{else}{ts}Delete Payment Processor{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-paymentProcessor-form-block">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}WARNING: Deleting this Payment Processor may result in some transaction pages being rendered inactive.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{else}
  <table class="form-layout-compressed">
    <tr class="crm-paymentProcessor-form-block-payment_processor_type">
        <td class="label">{$form.payment_processor_type_id.label}</td><td>{$form.payment_processor_type_id.html} {help id='proc-type'}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-name">
        <td class="label">{$form.name.label}</td><td>{$form.name.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-description">
        <td class="label">{$form.description.label}</td><td>{$form.description.html}</td>
    </tr>

    <tr class="crm-paymentProcessor-form-block-financial_account">
      <td class="label">{$form.financial_account_id.label}</td>
      <td>
  {if $financialAccount}
    {$form.financial_account_id.html}
      {else}
      {capture assign=ftUrl}{crmURL p='civicrm/admin/financial/financialAccount' q="reset=1"}{/capture}
    {ts 1=$ftUrl}There are no financial accounts configured with Financial Account Type 'Asset' Type. <a href='%1'>Click here</a> if you want to configure financial accounts for your site.{/ts}
        {/if}
      </td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-payment-instrument-id">
      <td class="label">{$form.payment_instrument_id.label}</td><td>{$form.payment_instrument_id.html}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-is_active">
        <td></td><td>{$form.is_active.html}&nbsp;{$form.is_active.label}</td>
    </tr>
    <tr class="crm-paymentProcessor-form-block-is_default">
        <td></td><td>{$form.is_default.html}&nbsp;{$form.is_default.label}</td>
    </tr>
  </table>
<fieldset>
<legend>{ts}Processor Details for Live Payments{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-paymentProcessor-form-block-user_name">
            <td class="label">{$form.user_name.label}</td><td>{$form.user_name.html} {help id=$ppTypeName|cat:'-live-user-name' title=$form.user_name.label}</td>
        </tr>
{if $form.password}
        <tr class="crm-paymentProcessor-form-block-password">
            <td class="label">{$form.password.label}</td><td>{$form.password.html} {help id=$ppTypeName|cat:'-live-password' title=$form.password.label}</td>
        </tr>
{/if}
{if $form.signature}
        <tr class="crm-paymentProcessor-form-block-signature">
            <td class="label">{$form.signature.label}</td><td>{$form.signature.html} {help id=$ppTypeName|cat:'-live-signature' title=$form.signature.label}</td>
        </tr>
{/if}
{if $form.subject}
        <tr class="crm-paymentProcessor-form-block-subject">
            <td class="label">{$form.subject.label}</td><td>{$form.subject.html}</td>
        </tr>
{/if}
        <tr class="crm-paymentProcessor-form-block-url_site">
            <td class="label">{$form.url_site.label}</td><td>{$form.url_site.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-live-url-site' title=$form.url_site.label}</td>
        </tr>
{if $form.url_api}
        <tr class="crm-paymentProcessor-form-block-url_api">
            <td class="label">{$form.url_api.label}</td><td>{$form.url_api.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-live-url-api' title=$form.url_api.label}</td>
        </tr>
{/if}
{if $is_recur}
        <tr class="crm-paymentProcessor-form-block-url_recur">
            <td class="label">{$form.url_recur.label}</td><td>{$form.url_recur.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-live-url-recur' title=$form.url_recur.label}</td>
        </tr>
{/if}
{if $form.url_button}
        <tr class="crm-paymentProcessor-form-block-url_button">
            <td class="label">{$form.url_button.label}</td><td>{$form.url_button.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-live-url-button' title=$form.url_button.label}</td>
        </tr>
{/if}
    </table>
</fieldset>

<fieldset>
<legend>{ts}Processor Details for Test Payments{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-paymentProcessor-form-block-test_user_name">
            <td class="label">{$form.test_user_name.label}</td><td>{$form.test_user_name.html} {help id=$ppTypeName|cat:'-test-user-name' title=$form.test_user_name.label}</td></tr>
{if $form.test_password}
        <tr class="crm-paymentProcessor-form-block-test_password">
            <td class="label">{$form.test_password.label}</td><td>{$form.test_password.html} {help id=$ppTypeName|cat:'-test-password' title=$form.test_password.label}</td>
        </tr>
{/if}
{if $form.test_signature}
        <tr class="crm-paymentProcessor-form-block-test_signature">
            <td class="label">{$form.test_signature.label}</td><td>{$form.test_signature.html} {help id=$ppTypeName|cat:'-test-signature' title=$form.test_signature.label}</td>
        </tr>
{/if}
{if $form.test_subject}
        <tr class="crm-paymentProcessor-form-block-test_subject">
            <td class="label">{$form.test_subject.label}</td><td>{$form.test_subject.html}</td>
        </tr>
{/if}
        <tr class="crm-paymentProcessor-form-block-test_url_site">
            <td class="label">{$form.test_url_site.label}</td><td>{$form.test_url_site.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-test-url-site' title=$form.test_url_site.label}</td>
        </tr>
{if $form.test_url_api}
        <tr class="crm-paymentProcessor-form-block-test_url_api">
            <td class="label">{$form.test_url_api.label}</td><td>{$form.test_url_api.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-test-url-api' title=$form.test_url_api.label}</td>
        </tr>
{/if}
{if $is_recur}
        <tr class="crm-paymentProcessor-form-block-test_url_recur">
            <td class="label">{$form.test_url_recur.label}</td><td>{$form.test_url_recur.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-test-url-recur' title=$form.test_url_recur.label}</td>
        </tr>
{/if}
{if $form.test_url_button}
        <tr class="crm-paymentProcessor-form-block-test_url_button">
            <td class="label">{$form.test_url_button.label}</td><td>{$form.test_url_button.html|crmAddClass:huge} {help id=$ppTypeName|cat:'-test-url-button' title=$form.test_url_button.label}</td>
        </tr>
{/if}
{/if}
</table>
       <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </fieldset>
</div>

{if $action eq 1  or $action eq 2}
  <script type="text/javascript">
  {literal}
    function reload(refresh) {
      var paymentProcessorType = cj("#payment_processor_type_id");
      var url = {/literal}"{$refreshURL}"{literal} + "&pp=" + paymentProcessorType.val();
      paymentProcessorType.closest('form').attr('data-warn-changes', 'false');
      window.location.href = url;
    }
  {/literal}
  </script>

{/if}

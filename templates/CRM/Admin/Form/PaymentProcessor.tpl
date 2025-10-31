{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* This template is used for adding/configuring Payment Processors used by a particular site/domain.  *}
<h3>{if $action eq 1}{ts}New Payment Processor{/ts}{elseif $action eq 2}{ts}Edit Payment Processor{/ts}{else}{ts}Delete Payment Processor{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-paymentProcessor-form-block">

{if $action eq 8}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {$deleteMessage|escape}
  </div>
{else}
  <table class="form-layout-compressed">
    {* This works for the fields managed from the EntityFields trait - see RelationshipType.tpl for end goal in this tpl *}
    {foreach from=$entityFields item=fieldSpec}
      {assign var=fieldName value=$fieldSpec.name}
      <tr class="crm-{$entityInClassFormat}-form-block-{$fieldName}">
        {include file="CRM/Core/Form/Field.tpl"}
      </tr>
    {/foreach}

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
    {if !empty($form.accept_credit_cards)}
    <tr class="crm-paymentProcessor-form-block-accept_credit_cards">
        <td class="label">{$form.accept_credit_cards.label}</td><td>{$form.accept_credit_cards.html}<br />
        <span class="description">{ts}Select Credit Card Types that this payment processor can accept{/ts}</span></td>
    </tr>
    {/if}
  </table>
<fieldset>
<legend>{ts}Processor Details for Live Payments{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-paymentProcessor-form-block-user_name">
            <td class="label">{$form.user_name.label}</td><td>{$form.user_name.html} {help id="$ppTypeName-live-user-name" title=$form.user_name.label}</td>
        </tr>
{if !empty($form.password)}
        <tr class="crm-paymentProcessor-form-block-password">
            <td class="label">{$form.password.label}</td><td>{$form.password.html} {help id="$ppTypeName-live-password" title=$form.password.label}</td>
        </tr>
{/if}
{if !empty($form.signature)}
        <tr class="crm-paymentProcessor-form-block-signature">
            <td class="label">{$form.signature.label}</td><td>{$form.signature.html} {help id="$ppTypeName-live-signature" title=$form.signature.label}</td>
        </tr>
{/if}
{if !empty($form.subject)}
        <tr class="crm-paymentProcessor-form-block-subject">
            <td class="label">{$form.subject.label}</td><td>{$form.subject.html} {help id="$ppTypeName-live-subject" title=$form.subject.label}</td>
        </tr>
{/if}
{if !empty($form.url_site)}
        <tr class="crm-paymentProcessor-form-block-url_site">
            <td class="label">{$form.url_site.label}</td><td>{$form.url_site.html|crmAddClass:huge} {help id="$ppTypeName-live-url-site" title=$form.url_site.label}</td>
        </tr>
{/if}
{if !empty($form.url_api)}
        <tr class="crm-paymentProcessor-form-block-url_api">
            <td class="label">{$form.url_api.label}</td><td>{$form.url_api.html|crmAddClass:huge} {help id="$ppTypeName-live-url-api" title=$form.url_api.label}</td>
        </tr>
{/if}
{if !empty($form.url_recur)}
        <tr class="crm-paymentProcessor-form-block-url_recur">
            <td class="label">{$form.url_recur.label}</td><td>{$form.url_recur.html|crmAddClass:huge} {help id="$ppTypeName-live-url-recur" title=$form.url_recur.label}</td>
        </tr>
{/if}
{if !empty($form.url_button)}
        <tr class="crm-paymentProcessor-form-block-url_button">
            <td class="label">{$form.url_button.label}</td><td>{$form.url_button.html|crmAddClass:huge} {help id="$ppTypeName-live-url-button" title=$form.url_button.label}</td>
        </tr>
{/if}
    </table>
</fieldset>

<fieldset>
<legend>{ts}Processor Details for Test Payments{/ts}</legend>
    <table class="form-layout-compressed">
        <tr class="crm-paymentProcessor-form-block-test_user_name">
            <td class="label">{$form.test_user_name.label}</td><td>{$form.test_user_name.html} {help id="$ppTypeName-test-user-name" title=$form.test_user_name.label}</td></tr>
{if !empty($form.test_password)}
        <tr class="crm-paymentProcessor-form-block-test_password">
            <td class="label">{$form.test_password.label}</td><td>{$form.test_password.html} {help id="$ppTypeName-test-password" title=$form.test_password.label}</td>
        </tr>
{/if}
{if !empty($form.test_signature)}
        <tr class="crm-paymentProcessor-form-block-test_signature">
            <td class="label">{$form.test_signature.label}</td><td>{$form.test_signature.html} {help id="$ppTypeName-test-signature" title=$form.test_signature.label}</td>
        </tr>
{/if}
{if !empty($form.test_subject)}
        <tr class="crm-paymentProcessor-form-block-test_subject">
            <td class="label">{$form.test_subject.label}</td><td>{$form.test_subject.html} {help id="$ppTypeName-test-subject" title=$form.test_subject.label}</td>
        </tr>
{/if}
{if !empty($form.test_url_site)}
        <tr class="crm-paymentProcessor-form-block-test_url_site">
            <td class="label">{$form.test_url_site.label}</td><td>{$form.test_url_site.html|crmAddClass:huge} {help id="$ppTypeName-test-url-site" title=$form.test_url_site.label}</td>
        </tr>
{/if}
{if !empty($form.test_url_api)}
        <tr class="crm-paymentProcessor-form-block-test_url_api">
            <td class="label">{$form.test_url_api.label}</td><td>{$form.test_url_api.html|crmAddClass:huge} {help id="$ppTypeName-test-url-api" title=$form.test_url_api.label}</td>
        </tr>
{/if}
{if !empty($form.test_url_recur)}
        <tr class="crm-paymentProcessor-form-block-test_url_recur">
            <td class="label">{$form.test_url_recur.label}</td><td>{$form.test_url_recur.html|crmAddClass:huge} {help id="$ppTypeName-test-url-recur" title=$form.test_url_recur.label}</td>
        </tr>
{/if}
{if !empty($form.test_url_button)}
        <tr class="crm-paymentProcessor-form-block-test_url_button">
            <td class="label">{$form.test_url_button.label}</td><td>{$form.test_url_button.html|crmAddClass:huge} {help id="$ppTypeName-test-url-button" title=$form.test_url_button.label}</td>
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
    CRM.$(function($) {
      const $form = $('form.{/literal}{$form.formClass}{literal}');
      $('#payment_processor_type_id', $form).change(function() {
        var url = {/literal}"{$refreshURL}"{literal} + "&pp=" + $(this).val();
        $form.attr('data-warn-changes', 'false')
          // Ajax refresh (works in a popup or full-screen)
          .closest('.crm-ajax-container, #crm-main-content-wrapper')
          .crmSnippet({url: url}).crmSnippet('refresh');
      });
      const $elements = $('input[name=frontend_title], input[name=title]', $form);
      if ($elements.length === 2) {
        CRM.utils.syncFields($elements.first(), $elements.last());
      }
    });
  {/literal}
  </script>

{/if}

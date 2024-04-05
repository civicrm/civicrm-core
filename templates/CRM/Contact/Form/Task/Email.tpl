{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-contactEmail-form-block">
{if $suppressedEmails > 0}
    <div class="status">
        <p>{ts count=$suppressedEmails plural='Email will NOT be sent to %count contacts - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).'}Email will NOT be sent to %count contact - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).{/ts}</p>
    </div>
{/if}

<table class="form-layout-compressed">
  <tr id="selectEmailFrom" class="crm-contactEmail-form-block-fromEmailAddress crm-email-element">
    <td class="label">{$form.from_email_address.label}</td>
    <td>{$form.from_email_address.html} {help id="id-from_email" file="CRM/Contact/Form/Task/Help/Email/id-from_email.hlp" title=$form.from_email_address.label}</td>
  </tr>
    <tr class="crm-contactEmail-form-block-recipient">
       <td class="label">{if $single eq false}{ts}Recipient(s){/ts}{else}{$form.to.label}{/if}</td>
       <td>
         {$form.to.html} {help id="id-to_email" file="CRM/Contact/Form/Task/Email.hlp"}
       </td>
    </tr>
    <tr class="crm-contactEmail-form-block-cc_id" {if empty($form.cc_id.value)}style="display:none;"{/if}>
      <td class="label">{$form.cc_id.label}</td>
      <td>
        {$form.cc_id.html}
        <a class="crm-hover-button clear-cc-link" rel="cc_id" title="{ts}Clear{/ts}" href="#"><i class="crm-i fa-times" aria-hidden="true"></i></a>
      </td>
    </tr>
    <tr class="crm-contactEmail-form-block-bcc_id" {if empty($form.bcc_id.value)}style="display:none;"{/if}>
      <td class="label">{$form.bcc_id.label}</td>
      <td>
        {$form.bcc_id.html}
        <a class="crm-hover-button clear-cc-link" rel="bcc_id" title="{ts}Clear{/ts}" href="#"><i class="crm-i fa-times" aria-hidden="true"></i></a>
      </td>
    </tr>
    <tr>
      <td></td>
      <td>
        <div>
          <a href="#" rel="cc_id" class="add-cc-link crm-hover-button" {if !empty($form.cc_id.value)}style="display:none;"{/if}>{ts}Add CC{/ts}</a>&nbsp;&nbsp;
          <a href="#" rel="bcc_id" class="add-cc-link crm-hover-button" {if !empty($form.bcc_id.value)}style="display:none;"{/if}>{ts}Add BCC{/ts}</a>
        </div>
      </td>
    </tr>

{if $emailTask}
    <tr class="crm-contactEmail-form-block-template">
        <td class="label">{$form.template.label}</td>
        <td>{$form.template.html}</td>
    </tr>
{/if}
    <tr class="crm-contactEmail-form-block-subject">
       <td class="label">{$form.subject.label}</td>
       <td>
         {$form.subject.html|crmAddClass:huge}&nbsp;
         <input class="crm-token-selector big" data-field="subject" />
         {help id="id-token-subject" tplFile=$tplFile isAdmin=$isAdmin file="CRM/Contact/Form/Task/Email.hlp"}
       </td>
    </tr>
  {* CRM-15984 --add campaign to email activities *}
  {include file="CRM/Campaign/Form/addCampaignToComponent.tpl" campaignTrClass="crm-contactEmail-form-block-campaign_id"}
</table>

{include file="CRM/Contact/Form/Task/EmailCommon.tpl" noAttach=0}
{include file="CRM/Activity/Form/FollowUp.tpl" type='email-'}

<div class="spacer"> </div>

{if $single eq false}
  {include file="CRM/Contact/Form/Task.tpl"}
{/if}
{if $suppressedEmails > 0}
   {ts count=$suppressedEmails plural='Email will NOT be sent to %count contacts.'}Email will NOT be sent to %count contact.{/ts}
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script type="text/javascript">

{literal}
CRM.$(function($) {
  var $form = $("form.{/literal}{$form.formClass}{literal}");

  $('.add-cc-link', $form).click(function(e) {
    e.preventDefault();
    var type = $(this).attr('rel');
    $(this).hide();
    $('.crm-contactEmail-form-block-'+type, $form).show();
  });

  $('.clear-cc-link', $form).click(function(e) {
    e.preventDefault();
    var type = $(this).attr('rel');
    $('.add-cc-link[rel='+type+']', $form).show();
    $('.crm-contactEmail-form-block-'+type, $form).hide().find('input.crm-ajax-select').select2('data', []);
  });

  var sourceDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1' h=0}{literal}";

  function emailSelect(el, prepopulate) {
    $(el, $form).data('api-entity', 'contact').css({width: '40em', 'max-width': '90%'}).crmSelect2({
      minimumInputLength: 1,
      multiple: true,
      ajax: {
        url: sourceDataUrl,
        data: function(term) {
          return {
            name: term
          };
        },
        results: function(response) {
          return {
            results: response
          };
        }
      }
    }).select2('data', prepopulate);
  }

  {/literal}
  var toContact = {if $toContact}{$toContact}{else}''{/if};
  {literal}
  emailSelect('#to', toContact);
});


</script>
{/literal}

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-contactSMS-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $suppressedSms > 0}
    <div class="status">
        <p>{ts count=$suppressedSms plural='SMS will NOT be sent to %count contacts - (no phone number on file, or communication preferences specify DO NOT SMS, or contact is deceased).'}SMS will NOT be sent to %count contact - (no phone number on file, or communication preferences specify DO NOT SMS, or contact is deceased).{/ts}</p>
    </div>
{/if}
{if $extendTargetContacts > 0}
   <div class="status">
        <p>{ts count=$extendTargetContacts plural='SMS will NOT be sent to contacts of %count Activities - (there are more than one Target contact).'}SMS will NOT be sent to contacts of %count Activity - (there are more than one Target contact).{/ts}</p>
   </div>
{/if}
{if $invalidActivity > 0}
    <div class="status"><p>
   {ts count=$invalidActivity plural='SMS will NOT be sent to contacts of %count selected activities as they are invalid for this task action.'}SMS will NOT be sent to contacts of %count selected activity as they are invalid for this task action.{/ts}
</p></div>
{/if}

<table class="form-layout-compressed">
    <tr class="crm-contactProvider-form-block-Provider">
       <td class="label">{$form.sms_provider_id.label}</td>
       <td>{$form.sms_provider_id.html} {help id ="id-provider" file="CRM/Contact/Form/Task/SMS.hlp"}</td>
    </tr>
    <tr class="crm-contactsms-form-block-recipient">
       <td class="label">{if $single eq false}{ts}Recipient(s){/ts}{else}{$form.to.label}{/if}</td>
       <td>{$form.to.html}
    <div class="spacer"></div>
      </td>
     </tr>
   <tr><td class="label">{$form.activity_subject.label}</td>
        <td class="value">{$form.activity_subject.html}</td>
   </tr>


{if $SMSTask}
    <tr class="crm-contactPhone-form-block-template">
        <td class="label">{$form.SMStemplate.label}</td>
        <td>{$form.SMStemplate.html}</td>
    </tr>
{/if}

</table>
{include file="CRM/Contact/Form/Task/SMSCommon.tpl"}
{include file="CRM/Mailing/Form/InsertTokens.tpl"}

<div class="spacer"> </div>

{if $single eq false}
  {include file="CRM/Contact/Form/Task.tpl"}
{/if}
{if $suppressedSms > 0}
   {ts count=$suppressedSms plural='SMS will NOT be sent to %count contacts.'}SMS will NOT be sent to %count contact.{/ts}
{/if}

{if $invalidActivity > 0}
   {ts count=$invalidActivity plural='SMS will NOT be sent to contacts of %count selected activities as they are invalid for this task action.'}SMS will NOT be sent to contacts of %count selected activity as they are invalid for this task action.{/ts}
{/if}

{if $extendTargetContacts > 0}
   {ts count=$extendTargetContacts plural='SMS will NOT be sent to contacts of %count selected activities.'}SMS will NOT be sent to contacts of %count selected activity.{/ts}
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script type="text/javascript">

{if $toContact}
    toContact  = {$toContact};
{/if}

{literal}
CRM.$(function($){
  var sourceDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkphone' h=0 }{literal}";
  function phoneSelect(el){
    $(el).data('api-entity', 'contact').crmSelect2({
      minimumInputLength: 1,
      multiple: true,
      ajax: {
        url: sourceDataUrl,
        data: function(term) {
          return { name: term, id: 1};
        },
        results: function(response) {
          return { results: response };
        }
      }
    }).select2('data', toContact);
  }
  phoneSelect('#to');
});
</script>
{/literal}

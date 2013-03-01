{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
<div class="crm-block crm-form-block crm-contactSMS-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $suppressedSms > 0}
    <div class="status">
        <p>{ts count=$suppressedSms plural='SMS will NOT be sent to %count contacts - (no phone number on file, or communication preferences specify DO NOT SMS, or contact is deceased).'}SMS will NOT be sent to %count contact - (no phone number on file, or communication preferences specify DO NOT SMS, or contact is deceased).{/ts}</p>
    </div>
{/if}
{if $extendTargetContacts > 0}
   <div class="status">
        <p>{ts count=$extendTargetContacts plural='SMS will NOT be sent to contacts of %count Acitivites - (There are more than one Target contacts).'}SMS will NOT be sent to contacts of %count Acitivity - (There are more than one Target contacts).{/ts}</p>
   </div>
{/if}
{if $invalidActivity > 0}
    <div class="status"><p>
   {ts count=$invalidActivity plural='SMS will NOT be sent to contacts of %count selected acitvites as they are of invalid for this task action.'}SMS will NOT be sent to contacts of %count selected acitvity as they are of invalid for this task action.{/ts}
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
        <td class="label">{$form.template.label}</td>
        <td>{$form.template.html}</td>
    </tr>
{/if}
    
</table>
{include file="CRM/Contact/Form/Task/SMSCommon.tpl"}

<div class="spacer"> </div>

{if $single eq false}
  {include file="CRM/Contact/Form/Task.tpl"}
{/if}
{if $suppressedSms > 0}
   {ts count=$suppressedSms plural='SMS will NOT be sent to %count contacts.'}SMS will NOT be sent to %count contact.{/ts}
{/if}

{if $invalidActivity > 0}
   {ts count=$invalidActivity plural='SMS will NOT be sent to contacts of %count selected acitvites as they are invalid for this task action.'}SMS will NOT be sent to contacts of %count selected acitvity as they are invalid for this task action.{/ts}
{/if}

{if $extendTargetContacts > 0}
   {ts count=$extendTargetContacts plural='SMS will NOT be sent to contacts of %count selected acitvites.'}SMS will NOT be sent to contacts of %count selected acitvites.{/ts}
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
<script type="text/javascript">

{if $toContact}
    toContact  = {$toContact};
{/if}

{literal}

var hintText = "{/literal}{ts escape='js'}Type in a partial or complete name or email address of an existing contact.{/ts}{literal}";
var sourceDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkphone' h=0 }{literal}";
var toDataUrl     = "{/literal}{crmURL p='civicrm/ajax/checkphone' q='id=1' h=0 }{literal}";

cj( "#to"     ).tokenInput( toDataUrl,     { prePopulate: toContact,  theme: 'facebook', hintText: hintText });
cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );
</script>
{/literal}
{include file="CRM/common/formNavigate.tpl"}

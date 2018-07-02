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
<div class="crm-block crm-form-block crm-contactEmail-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $suppressedEmails > 0}
    <div class="status">
        <p>{ts count=$suppressedEmails plural='Email will NOT be sent to %count contacts - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).'}Email will NOT be sent to %count contact - (no email address on file, or communication preferences specify DO NOT EMAIL, or contact is deceased).{/ts}</p>
    </div>
{/if}
{crmSetting var="logged_in_email_setting" name="allow_mail_from_logged_in_contact"}
<table class="form-layout-compressed">
  <tr id="selectEmailFrom" class="crm-contactEmail-form-block-fromEmailAddress crm-email-element">
    <td class="label">{$form.from_email_address.label}</td>
    <td>{$form.from_email_address.html} {help id="id-from_email" file="CRM/Contact/Form/Task/Email.hlp" isAdmin=$isAdmin logged_in_email_setting=$logged_in_email_setting}</td>
  </tr>
    <tr class="crm-contactEmail-form-block-recipient">
       <td class="label">{if $single eq false}{ts}Recipient(s){/ts}{else}{$form.to.label}{/if}</td>
       <td>
         {$form.to.html}{if $noEmails eq true}&nbsp;&nbsp;{$form.emailAddress.html}{/if}
       </td>
    </tr>
    <tr class="crm-contactEmail-form-block-cc_id" {if !$form.cc_id.value}style="display:none;"{/if}>
      <td class="label">{$form.cc_id.label}</td>
      <td>
        {$form.cc_id.html}
        <a class="crm-hover-button clear-cc-link" rel="cc_id" title="{ts}Clear{/ts}" href="#"><i class="crm-i fa-times"></i></a>
      </td>
    </tr>
    <tr class="crm-contactEmail-form-block-bcc_id" {if !$form.bcc_id.value}style="display:none;"{/if}>
      <td class="label">{$form.bcc_id.label}</td>
      <td>
        {$form.bcc_id.html}
        <a class="crm-hover-button clear-cc-link" rel="bcc_id" title="{ts}Clear{/ts}" href="#"><i class="crm-i fa-times"></i></a>
      </td>
    </tr>
    <tr>
      <td></td>
      <td>
        <div>
          <a href="#" rel="cc_id" class="add-cc-link crm-hover-button" {if $form.cc_id.value}style="display:none;"{/if}>{ts}Add CC{/ts}</a>&nbsp;&nbsp;
          <a href="#" rel="bcc_id" class="add-cc-link crm-hover-button" {if $form.bcc_id.value}style="display:none;"{/if}>{ts}Add BCC{/ts}</a>
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

{include file="CRM/Contact/Form/Task/EmailCommon.tpl"}
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

  var sourceDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1' h=0 }{literal}";

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
  var toContact = {if $toContact}{$toContact}{else}''{/if},
    ccContact = {if $ccContact}{$ccContact}{else}''{/if},
    bccContact = {if $bccContact}{$bccContact}{else}''{/if};
  {literal}
  emailSelect('#to', toContact);
  emailSelect('#cc_id', ccContact);
  emailSelect('#bcc_id', bccContact);
});


</script>
{/literal}

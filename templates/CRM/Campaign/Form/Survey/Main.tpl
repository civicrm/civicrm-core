{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div class="crm-block crm-form-block crm-campaign-survey-main-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {if $action  eq 1}
    <div class="help">
      {ts}Use this form to Add new Survey. You can create a new Activity type, specific to this Survey or select an existing activity type for this Survey.{/ts}
    </div>
  {/if}
  <table class="form-layout-compressed">
   <tr class="crm-campaign-survey-main-form-block-title">
       <td class="label">{$form.title.label}</td>
       <td class="view-value">{$form.title.html}
         <div class="description">{ts}Title of the survey.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-main-form-block-campaign_id">
     <td class="label">{$form.campaign_id.label}</td>
     <td class="view-value">{$form.campaign_id.html}
        <div class="description">{ts}Select the campaign for which survey is created.{/ts}</div>
      </td>
   </tr>
   <tr class="crm-campaign-survey-main-form-block-activity_type_id">
       <td class="label">{$form.activity_type_id.label}</td>
       <td class="view-value">{$form.activity_type_id.html}
         <div class="description">{ts}Select the Activity Type.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-main-form-block-instructions">
       <td class="label">{$form.instructions.label}</td>
       <td class="view-value">{$form.instructions.html}
   </tr>
   <tr class="crm-campaign-survey-main-form-block-default_number_of_contacts">
       <td class="label">{$form.default_number_of_contacts.label}</td>
       <td class="view-value">{$form.default_number_of_contacts.html}
     <div class="description">{ts}Maximum number of contacts that can be reserved for an interviewer at one time.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-main-form-block-max_number_of_contacts">
       <td class="label">{$form.max_number_of_contacts.label}</td>
       <td class="view-value">{$form.max_number_of_contacts.html}
     <div class="description">{ts}Maximum total number of contacts that can be in a reserved state for an interviewer.{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-main-form-block-release_frequency">
       <td class="label">{$form.release_frequency.label}</td>
       <td class="view-value">{$form.release_frequency.html}
      <div class="description">{ts}Reserved respondents are released if they haven't been surveyed within this number of days. The Respondent Processor script must be run periodically to release respondents.{/ts} {docURL page="user/initial-set-up/scheduled-jobs"}</div> </td>
   </tr>
   <tr class="crm-campaign-survey-main-form-block-is_active">
       <td class="label">{$form.is_active.label}</td>
       <td class="view-value">{$form.is_active.html}
      <div class="description">{ts}Is this survey active?{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-survey-main-form-block-is_default">
       <td class="label">{$form.is_default.label}</td>
       <td class="view-value">{$form.is_default.html}
     <div class="description">{ts}Is this the default survey?{/ts}</div></td>
   </tr>
   <tr class="crm-campaign-form-block-custom_data">
       <td colspan="2">
         {include file="CRM/common/customDataBlock.tpl"}
       </td>
   </tr>
  </table>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{*include profile link function*}
{include file="CRM/common/buildProfileLink.tpl"}

{literal}
<script type="text/javascript">
    //show edit profile field links
    CRM.$(function($) {
        // show edit for profile
        $('select[id="profile_id"]').change( function( ) {
            buildLinks( $(this), $(this).val());
        });

        // show edit links on form loads
        var profileField =  $('select[id="profile_id"]');
        buildLinks( profileField, profileField.val());
    });
</script>
{/literal}

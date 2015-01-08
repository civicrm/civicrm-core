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

{if $cdType }
   {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
<div class="crm-block crm-form-block crm-campaign-survey-form-block">
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{if $action eq 8}
  <table class="form-layout">
    <tr>
      <td colspan="2">
        <div class="status"><div class="icon inform-icon"></div>&nbsp;{ts}Are you sure you want to delete this Survey?{/ts}</div>
      </td>
    </tr>
  </table>
{else}
  {if $action  eq 1}
    <div id="help">
      {ts}Use this form to Add new Survey. You can create a new Activity type, specific to this Survey or select an existing activity type for this Survey.{/ts}
    </div>
  {/if}   
      <table class="form-layout-compressed"> 
       <tr class="crm-campaign-survey-form-block-title">
           <td class="label">{$form.title.label}</td>
           <td class="view-value">{$form.title.html}
	   <div class="description">{ts}Title of the survey.{/ts}</div></td>
       </tr> 
       <tr class="crm-campaign-survey-form-block-campaign_id">
           <td class="label">{$form.campaign_id.label}</td>
           <td class="view-value">{$form.campaign_id.html}
	   <div class="description">{ts}Select the campaign for which survey is created.{/ts}</div></td>
       </tr> 
       <tr class="crm-campaign-survey-form-block-activity_type_id">
           <td class="label">{$form.activity_type_id.label}</td>
           <td class="view-value">{$form.activity_type_id.html}
	   <div class="description">{ts}Select the Activity Type.{/ts}</div></td>
       </tr>
       <tr class="crm-campaign-survey-form-block-profile_id">
           <td class="label">{$form.profile_id.label}</td>
           <td class="view-value">{$form.profile_id.html}&nbsp;<span class="profile-links"></span>
	       <div class="description">{ts}Select the Profile for Survey.{/ts}</div>
           <div class="profile-create">
                <a href="{crmURL p='civicrm/admin/uf/group/add' q='reset=1&action=add'}" target="_blank">{ts}Click here for new profile{/ts}
           </div>
           </td> 
       </tr>
      
       <tr id='showoption'>
           <td colspan="2">
           <table class="form-layout-compressed">
               {* Conditionally show table for setting up selection options - for field types = radio, checkbox or select *}
               {include file="CRM/Campaign/Form/ResultOptions.tpl"}
           </table>
	   </td>
       </tr>

       <tr class="crm-campaign-survey-form-block-instructions">
           <td class="label">{$form.instructions.label}</td>
           <td class="view-value">{$form.instructions.html}
       </tr>
       <tr class="crm-campaign-survey-form-block-default_number_of_contacts">
           <td class="label">{$form.default_number_of_contacts.label}</td>
           <td class="view-value">{$form.default_number_of_contacts.html}
	       <div class="description">{ts}Maximum number of contacts that can be reserved for an interviewer at one time.{/ts}</div></td>
       </tr>	
       <tr class="crm-campaign-survey-form-block-max_number_of_contacts">
           <td class="label">{$form.max_number_of_contacts.label}</td>
           <td class="view-value">{$form.max_number_of_contacts.html}
	       <div class="description">{ts}Maximum total number of contacts that can be in a reserved state for an interviewer.{/ts}</div></td>
       </tr>
       <tr class="crm-campaign-survey-form-block-release_frequency">
           <td class="label">{$form.release_frequency.label}</td>
           <td class="view-value">{$form.release_frequency.html}
 	       <div class="description">{ts}Reserved respondents are released if they haven't been surveyed within this number of days. The Respondent Processor script must be run periodically to release respondents.{/ts} {docURL page="Managing Scheduled Jobs" resource="wiki"}</div> </td>
       </tr>
       <tr class="crm-campaign-survey-form-block-is_active">
           <td class="label">{$form.is_active.label}</td>
           <td class="view-value">{$form.is_active.html}
	   <div class="description">{ts}Is this survey active?{/ts}</div></td>
       </tr>
       <tr class="crm-campaign-survey-form-block-is_default">
           <td class="label">{$form.is_default.label}</td>
           <td class="view-value">{$form.is_default.html}
	       <div class="description">{ts}Is this the default survey?{/ts}</div></td>
       </tr>
       <tr class="crm-campaign-form-block-custom_data">
           <td colspan="2">
               <div id="customData"></div>
           </td>
       </tr>
      </table>
{/if}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{*include profile link function*}
{include file="CRM/common/buildProfileLink.tpl"}

{literal}
<script type="text/javascript">
    //show edit profile field links
    cj(function() {
        // show edit for profile
        cj('select[id="profile_id"]').change( function( ) {
            buildLinks( cj(this), cj(this).val());
        });

        // show edit links on form loads
        var profileField =  cj('select[id="profile_id"]'); 
        buildLinks( profileField, profileField.val()); 
    });
</script>
{/literal}

{include file="CRM/common/customData.tpl"}
{literal}
  <script type="text/javascript">
    cj(document).ready(function() {
      {/literal}
        buildCustomData( 'Survey' );
      {literal}
    });
  </script>
{/literal}

{/if}

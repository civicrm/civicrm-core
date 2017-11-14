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
{* this template is used for adding/editing other (custom) activities. *}
  {if $action eq 4}
    <div class="crm-block crm-content-block crm-activity-view-block">
  {else}
    {if $activityTypeDescription }
      <div class="help">{$activityTypeDescription}</div>
    {/if}
    <div class="crm-block crm-form-block crm-activity-form-block">
  {/if}
  {* added onload javascript for source contact*}
  {include file="CRM/Activity/Form/ActivityJs.tpl" tokenContext="activity"}
  {if !$action or ( $action eq 1 ) or ( $action eq 2 ) }
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
  {/if}

  {if $action eq 8} {* Delete action. *}
  <table class="form-layout">
  <tr>
    <td colspan="2">
      <div class="status">{ts}Are you sure you want to delete {/ts}{if $delName}'{$delName}'{/if}?</div>
    </td>
  </tr>
  {elseif $action eq 1 or $action eq 2  or $action eq 4 or $context eq 'search' or $context eq 'smog'}

  <table class="{if $action eq 4}crm-info-panel{else}form-layout{/if}">

  {if $action eq 4}
    {if $activityTypeDescription }
    <div class="help">{$activityTypeDescription}</div>
    {/if}
  {else}
    {if $context eq 'standalone' or $context eq 'search' or $context eq 'smog'}
    <tr class="crm-activity-form-block-activity_type_id">
      <td class="label">{$form.activity_type_id.label}</td><td class="view-value">{$form.activity_type_id.html}</td>
    </tr>
    {/if}
  {/if}

  {if $surveyActivity}
  <tr class="crm-activity-form-block-survey">
    <td class="label">{ts}Survey Title{/ts}</td><td class="view-value">{$surveyTitle}</td>
  </tr>
  {/if}

  <tr class="crm-activity-form-block-source_contact_id">
    <td class="label">{$form.source_contact_id.label}</td>
    <td class="view-value">
      {$form.source_contact_id.html}
    </td>
  </tr>

  <tr class="crm-activity-form-block-target_contact_id">
  <td class="label">{$form.target_contact_id.label}</td>
    <td class="view-value">
      {$form.target_contact_id.html}
    </td>
  </tr>

  {if $form.separation }
    <tr class="crm-activity-form-block-separation crm-is-multi-activity-wrapper">
      <td class="label">{$form.separation.label}</td>
      <td>{$form.separation.html} {help id="separation"}</td>
    </tr>
  {/if}

  <tr class="crm-activity-form-block-assignee_contact_id">
      <td class="label">
        {$form.assignee_contact_id.label}
        {edit}{help id="assignee_contact_id" title=$form.assignee_contact_id.label}{/edit}
      </td>
      <td>
        {$form.assignee_contact_id.html}
        {if $action neq 4}
          {if !$form.target_contact_id.frozen}
            <a href="#" class="crm-hover-button" id="swap_target_assignee" title="{ts}Swap Target and Assignee Contacts{/ts}" style="position:relative; bottom: 1em;">
              <i class="crm-i fa-random"></i>
            </a>
          {/if}
          {if $activityAssigneeNotification}
            <br />
            <span id="notify_assignee_msg" class="description"><i class="crm-i fa-paper-plane"></i> {ts}A copy of this activity will be emailed to each Assignee.{/ts} {help id="sent_copy_email"}</span>
          {/if}
        {/if}
      </td>
  </tr>

  {if $activityTypeFile}
  {include file="CRM/$crmDir/Form/Activity/$activityTypeFile.tpl"}
  {/if}

  <tr class="crm-activity-form-block-subject">
    <td class="label">{$form.subject.label}</td><td class="view-value">{$form.subject.html|crmAddClass:huge}</td>
  </tr>

  {* CRM-7362 --add campaign to activities *}
  {include file="CRM/Campaign/Form/addCampaignToComponent.tpl"
  campaignTrClass="crm-activity-form-block-campaign_id"}

  {* build engagement level CRM-7775 *}
  {if $buildEngagementLevel}
  <tr class="crm-activity-form-block-engagement_level">
    <td class="label">{$form.engagement_level.label}</td>
    <td class="view-value">{$form.engagement_level.html}</td>
  </tr>
  {/if}

  <tr class="crm-activity-form-block-location">
    <td class="label">{$form.location.label}</td><td class="view-value">{$form.location.html|crmAddClass:huge}</td>
  </tr>
  <tr class="crm-activity-form-block-activity_date_time">
    <td class="label">{$form.activity_date_time.label}</td>
    {if $action neq 4}
      <td class="view-value">{include file="CRM/common/jcalendar.tpl" elementName=activity_date_time}</td>
      {else}
      <td class="view-value">{$form.activity_date_time.value|crmDate}</td>
    {/if}
  </tr>
  <tr class="crm-activity-form-block-duration">
    <td class="label">{$form.duration.label}</td>
    <td class="view-value">
      {$form.duration.html}
      {if $action neq 4}<span class="description">{ts}minutes{/ts}{/if}
    </td>
  </tr>
  <tr class="crm-activity-form-block-status_id">
    <td class="label">{$form.status_id.label}</td><td class="view-value">{$form.status_id.html}</td>
  </tr>
  <tr class="crm-activity-form-block-details">
    <td class="label">{$form.details.label}</td>
    {if $activityTypeName eq "Print PDF Letter"}
      <td class="view-value">
      {$form.details.html}
      </td>
      {else}
      <td class="view-value">
       {$form.details.html|crmStripAlternatives|nl2br}
      </td>
    {/if}
  </tr>
  <tr class="crm-activity-form-block-priority_id">
    <td class="label">{$form.priority_id.label}</td><td class="view-value">{$form.priority_id.html}</td>
  </tr>
  {if $surveyActivity }
  <tr class="crm-activity-form-block-result">
    <td class="label">{$form.result.label}</td><td class="view-value">{$form.result.html}</td>
  </tr>
  {/if}
  {if $form.tag.html}
  <tr class="crm-activity-form-block-tag">
    <td class="label">{$form.tag.label}</td>
    <td class="view-value">
      <div class="crm-select-container">{$form.tag.html}</div>
    </td>
  </tr>
  {/if}

  {if $tagsetInfo.activity}
  <tr class="crm-activity-form-block-tag_set">{include file="CRM/common/Tagset.tpl" tagsetType='activity' tableLayout=true}</tr>
  {/if}

  {if $action neq 4 OR $viewCustomData}
  <tr class="crm-activity-form-block-custom_data">
    <td colspan="2">
      {if $action eq 4}
      {include file="CRM/Custom/Page/CustomDataView.tpl"}
        {else}
        <div id="customData"></div>
      {/if}
    </td>
  </tr>
  {/if}

  {if $action eq 4 AND $currentAttachmentInfo}
    {include file="CRM/Form/attachment.tpl"}{* For view action the include provides the row and cells. *}
  {elseif $action eq 1 OR $action eq 2}
    <tr class="crm-activity-form-block-attachment">
      <td colspan="2">
      {include file="CRM/Form/attachment.tpl"}
      </td>
    </tr>
  {/if}

  {if $action eq 2 OR $action eq 1}
    <tr class="crm-activity-form-block-recurring_activity">
      <td colspan="2">
        {include file="CRM/Core/Form/RecurringEntity.tpl" recurringFormIsEmbedded=true}
      </td>
    </tr>
  {/if}

  {if $action neq 4} {* Don't include "Schedule Follow-up" section in View mode. *}
  <tr class="crm-activity-form-block-schedule_followup">
    <td colspan="2">
      {include file="CRM/Activity/Form/FollowUp.tpl" type=""}
      {literal}
        <script type="text/javascript">
          CRM.$(function($) {
            var $form = $('form.{/literal}{$form.formClass}{literal}');
            $('.crm-accordion-body', $form).each( function() {
              //open tab if form rule throws error
              if ( $(this).children( ).find('span.crm-error').text( ).length > 0 ) {
                $(this).parent('.collapsed').crmAccordionToggle();
              }
            });
            function toggleMultiActivityCheckbox() {
              $('.crm-is-multi-activity-wrapper').toggle(!!($(this).val() && $(this).val().indexOf(',') > 0));
            }
            $('[name=target_contact_id]', $form).each(toggleMultiActivityCheckbox).change(toggleMultiActivityCheckbox);
            $('#swap_target_assignee').click(function(e) {
              e.preventDefault();
              var assignees = $('#assignee_contact_id', $form).select2("data");
              var targets = $('#target_contact_id', $form).select2("data");
              $('#assignee_contact_id', $form).select2("data", targets);
              $('#target_contact_id', $form).select2("data", assignees).change();
            });
          });
        </script>
      {/literal}
    </td>
  </tr>
  {/if}
  {/if} {* End Delete vs. Add / Edit action *}
  </table>
  <div class="crm-submit-buttons">
  {if $action eq 4 && ($activityTName neq 'Inbound Email' || $allow_edit_inbound_emails == 1)}
    {if !$context }
      {assign var="context" value='activity'}
    {/if}
    {if $permission EQ 'edit'}
      {assign var='urlParams' value="reset=1&atype=$atype&action=update&reset=1&id=$entityID&cid=$contactId&context=$context"}
      {if ($context eq 'fulltext' || $context eq 'search') && $searchKey}
        {assign var='urlParams' value="reset=1&atype=$atype&action=update&reset=1&id=$entityID&cid=$contactId&context=$context&key=$searchKey"}
      {/if}
      <a href="{crmURL p='civicrm/activity/add' q=$urlParams}" class="edit button" title="{ts}Edit{/ts}"><span><i class="crm-i fa-pencil"></i> {ts}Edit{/ts}</span></a>
    {/if}

    {if call_user_func(array('CRM_Core_Permission','check'), 'delete activities')}
      {assign var='urlParams' value="reset=1&atype=$atype&action=delete&reset=1&id=$entityID&cid=$contactId&context=$context"}
      {if ($context eq 'fulltext' || $context eq 'search') && $searchKey}
        {assign var='urlParams' value="reset=1&atype=$atype&action=delete&reset=1&id=$entityID&cid=$contactId&context=$context&key=$searchKey"}
      {/if}
      <a href="{crmURL p='civicrm/contact/view/activity' q=$urlParams}" class="delete button" title="{ts}Delete{/ts}"><span><i class="crm-i fa-trash"></i> {ts}Delete{/ts}</span></a>
    {/if}
  {/if}
  {if $action eq 4 and $context != 'case' and call_user_func(array('CRM_Case_BAO_Case','checkPermission'), $activityId, 'File On Case', $atype)}
    <a href="#" onclick="fileOnCase('file', {$activityId}, null, this); return false;" class="cancel button" title="{ts}File On Case{/ts}"><span><i class="crm-i fa-clipboard"></i> {ts}File on Case{/ts}</span></a>
    {include file="CRM/Case/Form/ActivityToCase.tpl"}
  {/if}
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>


  {if $action eq 1 or $action eq 2 or $context eq 'search' or $context eq 'smog'}
    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}
    {literal}
    <script type="text/javascript">
    CRM.$(function($) {
      var doNotNotifyAssigneeFor = {/literal}{$doNotNotifyAssigneeFor|@json_encode}{literal};
      $('#activity_type_id').change(function() {
        if ($.inArray($(this).val(), doNotNotifyAssigneeFor) != -1) {
          $('#notify_assignee_msg').hide();
        }
        else {
          $('#notify_assignee_msg').show();
        }
      });

      {/literal}
      {if $customDataSubType}
        CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
        {else}
        CRM.buildCustomData( '{$customDataType}' );
      {/if}
      {literal}
    });
    </script>
    {/literal}
  {/if}
  </div>{* end of form block*}

{include file="CRM/Event/Form/ManageEvent/ConfirmRepeatMode.tpl" entityID=$activityId entityTable="civicrm_activity"}

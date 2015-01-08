{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
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
      <div class="status">{ts 1=$delName}Are you sure you want to delete '%1'?{/ts}</div>
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
      {if $action eq 1 or $single eq false}
      <br/>
      {$form.is_multi_activity.html}&nbsp;{$form.is_multi_activity.label} {help id="id-is_multi_activity"}
      {/if}
    </td>
  </tr>

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
              <span class="icon ui-icon-shuffle"></span>
            </a>
          {/if}
          {if $activityAssigneeNotification}
            <br />
            <span class="description"><span class="icon email-icon"></span>{ts}A copy of this activity will be emailed to each Assignee.{/ts}</span>
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
      {* If using plain textarea, assign class=huge to make input large enough. *}
      {if $defaultWysiwygEditor eq 0}{$form.details.html|crmAddClass:huge}{else}{$form.details.html}{/if}
      </td>
      {else}
      <td class="view-value">
      {* If using plain textarea, assign class=huge to make input large enough. *}
       {if $defaultWysiwygEditor eq 0}{$form.details.html|crmStripAlternatives|crmAddClass:huge}{else}{$form.details.html|crmStripAlternatives}{/if}
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

  {if $action neq 4} {* Don't include "Schedule Follow-up" section in View mode. *}
  <tr class="crm-activity-form-block-schedule_followup">
    <td colspan="2">
      <div class="crm-accordion-wrapper collapsed">
        <div class="crm-accordion-header">
          {ts}Schedule Follow-up{/ts}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">
          <table class="form-layout-compressed">
            <tr><td class="label">{ts}Schedule Follow-up Activity{/ts}</td>
              <td>{$form.followup_activity_type_id.html}&nbsp;&nbsp;{ts}on{/ts}
              {include file="CRM/common/jcalendar.tpl" elementName=followup_date}
              </td>
            </tr>
            <tr>
              <td class="label">{$form.followup_activity_subject.label}</td>
              <td>{$form.followup_activity_subject.html|crmAddClass:huge}</td>
            </tr>
              <tr>
                  <td class="label">
                    {$form.followup_assignee_contact_id.label}
                    {edit}
                    {/edit}
                  </td>
                  <td>
                    {$form.followup_assignee_contact_id.html}
                  </td>
              </tr>
          </table>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->
      {literal}
        <script type="text/javascript">
          CRM.$(function($) {
            $('.crm-accordion-body').each( function() {
              //open tab if form rule throws error
              if ( $(this).children( ).find('span.crm-error').text( ).length > 0 ) {
                $(this).parent('.collapsed').crmAccordionToggle();
              }
            });
            $('#swap_target_assignee').click(function() {
              var assignees = $('#assignee_contact_id').select2("data");
              var targets = $('#target_contact_id').select2("data");
              $('#assignee_contact_id').select2("data", targets);
              $('#target_contact_id').select2("data", assignees);
              return false;
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
  {if $action eq 4 && $activityTName neq 'Inbound Email'}
    {if !$context }
      {assign var="context" value='activity'}
    {/if}
    {if $permission EQ 'edit'}
      {assign var='urlParams' value="reset=1&atype=$atype&action=update&reset=1&id=$entityID&cid=$contactId&context=$context"}
      {if ($context eq 'fulltext' || $context eq 'search') && $searchKey}
        {assign var='urlParams' value="reset=1&atype=$atype&action=update&reset=1&id=$entityID&cid=$contactId&context=$context&key=$searchKey"}
      {/if}
      <a href="{crmURL p='civicrm/activity/add' q=$urlParams}" class="edit button" title="{ts}Edit{/ts}"><span><div class="icon edit-icon"></div>{ts}Edit{/ts}</span></a>
    {/if}

    {if call_user_func(array('CRM_Core_Permission','check'), 'delete activities')}
      {assign var='urlParams' value="reset=1&atype=$atype&action=delete&reset=1&id=$entityID&cid=$contactId&context=$context"}
      {if ($context eq 'fulltext' || $context eq 'search') && $searchKey}
        {assign var='urlParams' value="reset=1&atype=$atype&action=delete&reset=1&id=$entityID&cid=$contactId&context=$context&key=$searchKey"}
      {/if}
      <a href="{crmURL p='civicrm/contact/view/activity' q=$urlParams}" class="delete button" title="{ts}Delete{/ts}"><span><div class="icon delete-icon"></div>{ts}Delete{/ts}</span></a>
    {/if}
  {/if}
  {if $action eq 4 and call_user_func(array('CRM_Case_BAO_Case','checkPermission'), $activityId, 'File On Case', $atype)}
    <a href="#" onclick="fileOnCase('file', {$activityId}, null, this); return false;" class="cancel button" title="{ts}File On Case{/ts}"><span><div class="icon ui-icon-clipboard"></div>{ts}File On Case{/ts}</span></a>
  {/if}
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

  {include file="CRM/Case/Form/ActivityToCase.tpl"}

  {if $action eq 1 or $action eq 2 or $context eq 'search' or $context eq 'smog'}
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
    {literal}
    <script type="text/javascript">
    CRM.$(function($) {
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
{/if} {* end of snippet if*}

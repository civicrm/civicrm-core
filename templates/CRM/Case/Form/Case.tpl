{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Base template for Open Case. May be used for other special activity types at some point ..
   Note: 1. We will include all the activity fields here however each activity type file may build (via php) only those required by them.
         2. Each activity type file can include its case fields in its own template, so that they will be included during activity edit.
*}
<div class="crm-block crm-form-block crm-case-form-block">

<h3>{if $action eq 8}{ts}Delete Case{/ts}{elseif $action eq 32768}{ts}Restore Case{/ts}{/if}</h3>
{if $action eq 8 or $action eq 32768}
      <div class="messages status no-popup">
        {icon icon="fa-info-circle"}{/icon}
          {if $action eq 8}
            {ts}Click Delete to move this case and all associated activities to the Trash.{/ts}
          {else}
            {ts}Click Restore to retrieve this case and all associated activities from the Trash.{/ts}
          {/if}
      </div>
{else}
<table class="form-layout">
    {if $activityTypeDescription}
        <tr>
            <div class="help">{$activityTypeDescription|purify}</div>
        </tr>
    {/if}
{if $clientName}
    <tr class="crm-case-form-block-clientName">
      <td class="label">{ts}Client{/ts}</td>
      <td class="bold view-value">{$clientName}</td>
    </tr>
{elseif $action eq 1}
    {if $context eq 'standalone'}
      <td class="label">{$form.client_id.label}</td>
      <td class="view-value">{$form.client_id.html}</td>
    {/if}
{/if}
{* activity fields *}
{if $form.medium_id.html and $form.activity_location.html}
    <tr class="crm-case-form-block-medium_id">
        <td class="label">{$form.medium_id.label}</td>
        <td class="view-value">{$form.medium_id.html}&nbsp;&nbsp;&nbsp;{$form.activity_location.label} &nbsp;{$form.activity_location.html}</td>
    </tr>
{/if}

{if !empty($form.activity_details.html)}
    <tr class="crm-case-form-block-activity_details">
        <td class="label">{$form.activity_details.label}{help id="activity_details" activityTypeFile=$activityTypeFile file="CRM/Case/Form/Case.hlp"}</td>
        <td class="view-value">{$form.activity_details.html|crmStripAlternatives}</td>
    </tr>
{/if}

{* custom data group *}
{* This shows ACTIVITY custom fields, as opposed to CASE custom fields, so is not a duplicate of the other custom data block below. *}
<tr class="crm-activity-form-block-custom_data">
  <td colspan="2">
    {include file="CRM/common/customDataBlock.tpl" customDataType='Activity' customDataSubType=$activityTypeID cid=false}
  </td>
</tr>

{if !empty($form.activity_subject.html)}
    <tr class="crm-case-form-block-activity_subject">
       <td class="label">{$form.activity_subject.label}{help id="activity_subject" activityTypeFile=$activityTypeFile file="CRM/Case/Form/Case.hlp"}</td>
       <td>{$form.activity_subject.html|crmAddClass:huge}</td>
    </tr>
{/if}

{* inject activity type-specific form fields *}
{if $activityTypeFile}
    {include file="CRM/Case/Form/Activity/$activityTypeFile.tpl"}
{/if}

{if !empty($form.duration.html)}
    <tr class="crm-case-form-block-duration">
      <td class="label">{$form.duration.label}</td>
      <td class="view-value">
        {$form.duration.html}
         <span class="description">{ts}minutes{/ts}</span>
      </td>
    </tr>
{/if}

{if !empty($form.tag.html)}
    <tr class="crm-case-form-block-tag">
      <td class="label">{$form.tag.label}</td>
      <td class="view-value"><div class="crm-select-container">{$form.tag.html}</div>
      </td>
    </tr>
{/if}

{* This shows CASE custom fields, as opposed to ACTIVITY custom fields, so is not a duplicate of the other custom data block above. *}
<tr class="crm-case-form-block-custom_data">
    <td colspan="2">
      {include file="CRM/common/customDataBlock.tpl" customDataType='Case' customDataSubType=$caseTypeID cid=false}
    </td>
</tr>

{if $isTagset}
<tr class="crm-case-form-block-tag_set">
    {include file="CRM/common/Tagset.tpl" tagsetType='case' tableLayout=true}
</tr>
{/if}

</table>
{/if}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>

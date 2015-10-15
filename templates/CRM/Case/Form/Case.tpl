{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* Base template for Open Case. May be used for other special activity types at some point ..
   Note: 1. We will include all the activity fields here however each activity type file may build (via php) only those required by them.
         2. Each activity type file can include its case fields in its own template, so that they will be included during activity edit.
*}
<div class="crm-block crm-form-block crm-case-form-block">

{if $action neq 8 && $action neq 32768}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>
{/if}

<h3>{if $action eq 8}{ts}Delete Case{/ts}{elseif $action eq 32768}{ts}Restore Case{/ts}{/if}</h3>
{if $action eq 8 or $action eq 32768 }
      <div class="messages status no-popup">
        <div class="icon inform-icon"></div>
          {if $action eq 8}
            {ts}Click Delete to move this case and all associated activities to the Trash.{/ts}
          {else}
            {ts}Click Restore to retrieve this case and all associated activities from the Trash.{/ts}
          {/if}
      </div>
{else}
<table class="form-layout">
    {if $activityTypeDescription }
        <tr>
            <div class="help">{$activityTypeDescription}</div>
        </tr>
    {/if}
{if $clientName}
    <tr class="crm-case-form-block-clientName">
      <td class="label font-size12pt">{ts}Client{/ts}</td>
      <td class="font-size12pt bold view-value">{$clientName}</td>
    </tr>
{elseif !$clientName and $action eq 1}
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

{if $form.activity_details.html}
    <tr class="crm-case-form-block-activity_details">
        <td class="label">{$form.activity_details.label}{help id="id-details" activityTypeFile=$activityTypeFile file="CRM/Case/Form/Case.hlp"}</td>
        <td class="view-value">{$form.activity_details.html|crmStripAlternatives}</td>
    </tr>
{/if}

{* custom data group *}
{if $groupTree}
    <tr>
       <td colspan="2">{include file="CRM/Custom/Form/CustomData.tpl"}</td>
    </tr>
{/if}

{if $form.activity_subject.html}
    <tr class="crm-case-form-block-activity_subject">
       <td class="label">{$form.activity_subject.label}{help id="id-activity_subject" activityTypeFile=$activityTypeFile file="CRM/Case/Form/Case.hlp"}</td>
       <td>{$form.activity_subject.html|crmAddClass:huge}</td>
    </tr>
{/if}

{* inject activity type-specific form fields *}
{if $activityTypeFile}
    {include file="CRM/Case/Form/Activity/$activityTypeFile.tpl"}
{/if}

{if $form.duration.html}
    <tr class="crm-case-form-block-duration">
      <td class="label">{$form.duration.label}</td>
      <td class="view-value">
        {$form.duration.html}
         <span class="description">{ts}minutes{/ts}</span>
      </td>
    </tr>
{/if}

{if $form.tag.html}
    <tr class="crm-case-form-block-tag">
      <td class="label">{$form.tag.label}</td>
      <td class="view-value"><div class="crm-select-container">{$form.tag.html}</div>
      </td>
    </tr>
{/if}

<tr class="crm-case-form-block-custom_data">
    <td colspan="2">
        <div id="customData"></div>
    </td>
</tr>

<tr class="crm-case-form-block-tag_set"><td colspan="2">{include file="CRM/common/Tagset.tpl" tagsetType='case'}</td></tr>

</table>
{/if}

{if $action eq 1}
    {*include custom data js file*}
    {include file="CRM/common/customData.tpl"}
    {literal}
      <script type="text/javascript">
      CRM.$(function($) {
           var customDataSubType = $('#case_type_id').val();
           if ( customDataSubType ) {
              CRM.buildCustomData( {/literal}'{$customDataType}'{literal}, customDataSubType );
           } else {
              CRM.buildCustomData( {/literal}'{$customDataType}'{literal} );
           }
       });
       </script>
     {/literal}
{/if}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>

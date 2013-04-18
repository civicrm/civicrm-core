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

{* this template is used for adding/editing activities for a case. *}
{if $cdType }
  {include file="CRM/Custom/Form/CustomData.tpl"}
{else}
<h3>{if $action eq 1 or $action eq 1024}
  {ts 1=$activityTypeName}New activity: %1{/ts}
  {elseif $action eq 8}{ts 1=$activityTypeName}Delete %1{/ts}
  {elseif $action eq 32768}{ts 1=$activityTypeName}Restore %1{/ts}
  {else}{ts 1=$activityTypeName}Edit %1{/ts}{/if}
</h3>
<div class="crm-block crm-form-block crm-case-activity-form-block">

  {if $action neq 8 and $action  neq 32768 }
  {* Include form buttons on top for new and edit modes. *}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

    {* added onload javascript for source contact*}
    {literal}
    <script type="text/javascript">
    var assignee_contact = '';

    {/literal}
    {if $assignee_contact}
    var assignee_contact = {$assignee_contact};
    {/if}

    {literal}
    var assignee_contact_id = null;
    //loop to set the value of cc and bcc if form rule.
    var toDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1&noemail=1' h=0 }{literal}"; {/literal}
    {foreach from=","|explode:"assignee" key=key item=element}
      {assign var=currentElement value=`$element`_contact_id}
      {if $form.$currentElement.value}
        {literal} var {/literal}{$currentElement}{literal} = cj.ajax({ url: toDataUrl + "&cid={/literal}{$form.$currentElement.value}{literal}", async: false }).responseText;{/literal}
      {/if}
    {/foreach}
    {literal}

    if ( assignee_contact_id ) {
      eval( 'assignee_contact = ' + assignee_contact_id );
    }

    cj(function( ) {
      {/literal}
      {if $source_contact and $admin and $action neq 4}
        {literal} cj( '#source_contact_id' ).val( "{/literal}{$source_contact}{literal}");{/literal}
      {/if}
      {literal}

      var sourceDataUrl = "{/literal}{$dataUrl}{literal}";
      var tokenDataUrl_assignee  = "{/literal}{$tokenUrl}&context=case_activity_assignee{literal}";

      var hintText = "{/literal}{ts escape='js'}Type in a partial or complete name or email address of an existing contact.{/ts}{literal}";
      cj( "#assignee_contact_id").tokenInput( tokenDataUrl_assignee, { prePopulate: assignee_contact, theme: 'facebook', hintText: hintText });
      cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );
      cj( "#source_contact_id").autocomplete( sourceDataUrl, { width : 180, selectFirst : false, matchContains:true
      }).result( function(event, data, formatted) { cj( "#source_contact_qid" ).val( data[1] );
        }).bind( 'click', function( ) { cj( "#source_contact_qid" ).val(''); });
    });
  </script>
  {/literal}

  {/if}

  {if $action eq 8 or $action eq 32768 }
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div> &nbsp;
    {if $action eq 8}
      {ts 1=$activityTypeName}Click Delete to move this &quot;%1&quot; activity to the Trash.{/ts}
    {else}
      {ts 1=$activityTypeName}Click Restore to retrieve this &quot;%1&quot; activity from the Trash.{/ts}
    {/if}
  </div><br />
  {else}
  <table class="form-layout">
    {if $activityTypeDescription }
      <tr>
        <div id="help">{$activityTypeDescription}</div>
      </tr>
    {/if}
    <tr id="with-clients" class="crm-case-activity-form-block-client_name">
      <td class="label font-size12pt">{ts}Client{/ts}</td>
      <td class="view-value">
        <span class="font-size12pt">
          {foreach from=$client_names item=client name=clients}
            {$client.display_name}{if not $smarty.foreach.clients.last}; &nbsp; {/if}
          {/foreach}
        </span>

        {if $action eq 1 or $action eq 2}
          <br />
          <a href="#" class="crm-with-contact">&raquo; {ts}With other contact(s){/ts}</a>
        {/if}
      </td>
    </tr>

    {if $action eq 1 or $action eq 2}
      <tr class="crm-case-activity-form-block-target_contact_id hide-block" id="with-contacts-widget">
        <td class="label font-size10pt">{ts}With Contact{/ts}</td>
        <td class="view-value">
          {include file="CRM/Contact/Form/NewContact.tpl" noLabel=true skipBreak=true multiClient=true}
          <br/>
          <a href="#" class="crm-with-contact">
            &raquo; {if not $multiClient}{ts}With client{/ts}{else}{ts}With client(s){/ts}{/if}
          </a>
        </td>
      </tr>
    {/if}

    <tr class="crm-case-activity-form-block-activityTypeName">
      <td class="label">{ts}Activity Type{/ts}</td>
      <td class="view-value bold">{$activityTypeName|escape}</td>
    </tr>
    <tr class="crm-case-activity-form-block-source_contact_id">
      <td class="label">{$form.source_contact_id.label}</td>
      <td class="view-value"> {if $admin}{$form.source_contact_id.html}{/if}</td>
    </tr>
    <tr class="crm-case-activity-form-block-assignee_contact_id">
      <td class="label">{ts}Assigned To{/ts}</td>
      <td>{$form.assignee_contact_id.html}
        {edit}
          <span class="description">
          {ts}You can optionally assign this activity to someone.{/ts}
          {if $activityAssigneeNotification}
            <br />{ts}A copy of this activity will be emailed to each Assignee.{/ts}
          {/if}
          </span>
        {/edit}
      </td>
    </tr>

  {* Include special processing fields if any are defined for this activity type (e.g. Change Case Status / Change Case Type). *}
    {if $activityTypeFile}
    {include file="CRM/Case/Form/Activity/$activityTypeFile.tpl"}
    {/if}
    {if $activityTypeFile neq 'ChangeCaseStartDate'}
      <tr class="crm-case-activity-form-block-subject">
        <td class="label">{$form.subject.label}</td><td class="view-value">{$form.subject.html|crmAddClass:huge}</td>
      </tr>
    {/if}
    <tr class="crm-case-activity-form-block-medium_id">
      <td class="label">{$form.medium_id.label}</td>
      <td class="view-value">{$form.medium_id.html}&nbsp;&nbsp;&nbsp;{$form.location.label} &nbsp;{$form.location.html|crmAddClass:huge}</td>
    </tr>
    <tr class="crm-case-activity-form-block-activity_date_time">
      <td class="label">{$form.activity_date_time.label}</td>
      {if $action eq 2 && $activityTypeFile eq 'OpenCase'}
        <td class="view-value">{$current_activity_date_time|crmDate}
          <div class="description">Use a <a href="{$changeStartURL}">Change Start Date</a> activity to change the date</div>
        {* avoid errors about missing field *}
          <div style="display: none;">{include file="CRM/common/jcalendar.tpl" elementName=activity_date_time}</div>
        </td>
        {else}
        <td class="view-value">{include file="CRM/common/jcalendar.tpl" elementName=activity_date_time}</td>
      {/if}
    </tr>
    <tr>
      <td colspan="2"><div id="customData"></div></td>
    </tr>
    <tr class="crm-case-activity-form-block-details">
      <td class="label">{$form.details.label}</td>
      <td class="view-value">
      {* If using plain textarea, assign class=huge to make input large enough. *}
        {if $defaultWysiwygEditor eq 0}{$form.details.html|crmAddClass:huge}{else}{$form.details.html}{/if}
      </td>
    </tr>
    <tr class="crm-case-activity-form-block-attachment">
      <td colspan="2">{include file="CRM/Form/attachment.tpl"}</td>
    </tr>
    {if $searchRows} {* We have got case role rows to display for "Send Copy To" feature *}
      <tr class="crm-case-activity-form-block-send_copy">
        <td colspan="2">
          <div id="sendcopy" class="crm-accordion-wrapper collapsed">
            <div class="crm-accordion-header">
              {ts}Send a Copy{/ts}
            </div><!-- /.crm-accordion-header -->
            <div id="sendcopy-body" class="crm-accordion-body">

              <div class="description">{ts}Email a complete copy of this activity record to other people involved with the case. Click the top left box to select all.{/ts}</div>
              {strip}
                <table>
                  <tr class="columnheader">
                    <th>{$form.toggleSelect.html}&nbsp;</th>
                    <th>{ts}Case Role{/ts}</th>
                    <th>{ts}Name{/ts}</th>
                    <th>{ts}Email{/ts}</th>
                  </tr>
                  {foreach from=$searchRows item=row key=id}
                    <tr class="{cycle values="odd-row,even-row"}">
                      <td class="crm-case-activity-form-block-contact_{$id}">{$form.contact_check[$id].html}</td>
                      <td class="crm-case-activity-form-block-role">{$row.role}</td>
                      <td class="crm-case-activity-form-block-display_name">{$row.display_name}</td>
                      <td class="crm-case-activity-form-block-email">{$row.email}</td>
                    </tr>
                  {/foreach}
                </table>
              {/strip}
            </div><!-- /.crm-accordion-body -->
          </div><!-- /.crm-accordion-wrapper -->
        </td>
      </tr>
    {/if}
  <tr class="crm-case-activity-form-block-schedule_followup">
    <td colspan="2">

      <div id="follow-up" class="crm-accordion-wrapper collapsed">
        <div class="crm-accordion-header">
          {ts}Schedule Follow-up{/ts}
        </div><!-- /.crm-accordion-header -->
        <div class="crm-accordion-body">

          <table class="form-layout-compressed">
            <tr class="crm-case-activity-form-block-followup_activity_type_id">
              <td class="label">{ts}Schedule Follow-up Activity{/ts}</td>
              <td>{$form.followup_activity_type_id.html}&nbsp;&nbsp;{ts}on{/ts}
              {include file="CRM/common/jcalendar.tpl" elementName=followup_date}
              </td>
            </tr>
            <tr class="crm-case-activity-form-block-followup_activity_subject">
              <td class="label">{$form.followup_activity_subject.label}</td>
              <td>{$form.followup_activity_subject.html|crmAddClass:huge}</td>
            </tr>
          </table>
        </div><!-- /.crm-accordion-body -->
      </div><!-- /.crm-accordion-wrapper -->
    </td>
  </tr>
  <tr class="crm-case-activity-form-block-duration">
    <td class="label">{$form.duration.label}</td>
    <td class="view-value">
      {$form.duration.html}
      <span class="description">{ts}Total time spent on this activity (in minutes).{/ts}
    </td>
  </tr>
  {* Suppress activity status and priority for changes to status, case type and start date. PostProc will force status to completed. *}
    {if $activityTypeFile NEQ 'ChangeCaseStatus'
    && $activityTypeFile NEQ 'ChangeCaseType'
    && $activityTypeFile NEQ 'ChangeCaseStartDate'}
    <tr class="crm-case-activity-form-block-status_id">
      <td class="label">{$form.status_id.label}</td><td class="view-value">{$form.status_id.html}</td>
    </tr>
    <tr class="crm-case-activity-form-block-priority_id">
      <td class="label">{$form.priority_id.label}</td><td class="view-value">{$form.priority_id.html}</td>
    </tr>
    {/if}
    {if $form.tag.html}
    <tr class="crm-case-activity-form-block-tag">
      <td class="label">{$form.tag.label}</td>
      <td class="view-value"><div class="crm-select-container">{$form.tag.html}</div>
        {literal}
          <script type="text/javascript">
            cj(".crm-case-activity-form-block-tag select[multiple]").crmasmSelect({
              addItemTarget: 'bottom',
              animate: true,
              highlight: true,
              sortable: true,
              respectParents: true
            });
          </script>
        {/literal}

      </td>
    </tr>
    {/if}
  <tr class="crm-case-activity-form-block-tag_set"><td colspan="2">{include file="CRM/common/Tag.tpl" tagsetType='activity'}</td></tr>
  </table>

  {/if}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

  {if $action eq 1 or $action eq 2}
  {*include custom data js file*}
  {include file="CRM/common/customData.tpl"}
    {literal}
    <script type="text/javascript">
      cj(document).ready(function() {
    {/literal}
    CRM.buildCustomData( '{$customDataType}' );
    {if $customDataSubType}
      CRM.buildCustomData( '{$customDataType}', {$customDataSubType} );
    {/if}
    {literal}
    });
    </script>
    {/literal}
  {/if}

  {if $action neq 8 and $action neq 32768}
  <script type="text/javascript">
    {if $searchRows}
      cj('#sendcopy').crmAccordionToggle();
    {/if}

    cj('#follow-up').crmAccordionToggle();
  </script>
  {/if}

  {* include jscript to warn if unsaved form field changes *}
  {include file="CRM/common/formNavigate.tpl"}

  {if $action eq 2 or $action eq 1}
    {literal}
    <script type="text/javascript">
      cj(function( ) {
        cj('.crm-with-contact').click(function(){
          cj('#with-contacts-widget').toggle();
          cj('#with-clients').toggle();
        });
      });
    </script>
    {/literal}
  {/if}
</div>
{/if} {* end of main if block*}

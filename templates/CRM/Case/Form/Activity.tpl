{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{* this template is used for adding/editing activities for a case. *}
<div class="crm-block crm-form-block crm-case-activity-form-block">
  {if $action eq 8 or $action eq 32768}
  <div class="messages status no-popup">
    <i class="crm-i fa-info-circle" role="img" aria-hidden="true"></i> &nbsp;
    {if $action eq 8}
      {ts 1=$activityTypeNameAndLabel.displayLabel|escape}Click Delete to move this &quot;%1&quot; activity to the Trash.{/ts}
    {else}
      {ts 1=$activityTypeNameAndLabel.displayLabel|escape}Click Restore to retrieve this &quot;%1&quot; activity from the Trash.{/ts}
    {/if}
  </div><br />
  {else}
  <table class="form-layout">
    {if $activityTypeDescription}
      <tr>
        <div class="help">{$activityTypeDescription|purify}</div>
      </tr>
    {/if}
    {* Block for change status, case type and start date. *}
    {if $activityTypeFile EQ 'ChangeCaseStatus'
    || $activityTypeFile EQ 'ChangeCaseType'
    || $activityTypeFile EQ 'LinkCases'
    || $activityTypeFile EQ 'ChangeCaseStartDate'}
      {include file="CRM/Case/Form/Activity/$activityTypeFile.tpl"}
      <tr class="crm-case-activity-form-block-details">
        <td class="label">{ts}Details{/ts}</td>
        <td class="view-value">
          {$form.details.html}
        </td>
      </tr>
      {* Added Activity Details accordion tab *}
      <tr class="crm-case-activity-form-block-activity-details">
        <td colspan="2">
          <details id="activity-details" class="crm-accordion-bold">
            <summary>
              {ts}Activity Details{/ts}
            </summary>
            <div class="crm-accordion-body">
    {else}
      <tr class="crm-case-activity-form-block-activity-details">
        <td colspan="2">
    {/if}
    {* End block for change status, case type and start date. *}
            <table class="form-layout-compressed">
              <tbody>
                <tr id="with-clients" class="crm-case-activity-form-block-client_name">
                  <td class="label">{ts}Client{/ts}</td>
                  <td class="view-value">
                    <span>
                      {foreach from=$client_names item=client name=clients key=id}
                        {foreach from=$client_names.$id item=client1}
                          {$client1.display_name}
                        {/foreach}
                        {if not $smarty.foreach.clients.last}; &nbsp; {/if}
                      {/foreach}
                    </span>

                    {if $action eq 1 or $action eq 2}
                      <br />
                      <a href="#" class="crm-with-contact"><i class="crm-i fa-user-plus" role="img" aria-hidden="true"></i> {ts}With other contact(s){/ts}</a>
                    {/if}
                  </td>
                </tr>

                {if $action eq 1 or $action eq 2}
                  <tr class="crm-case-activity-form-block-target_contact_id hiddenElement" id="with-contacts-widget">
                    <td class="label font-size10pt">{ts}With Contact{/ts}</td>
                    <td class="view-value">
                      {$form.target_contact_id.html}
                      <br/>
                      <a href="#" class="crm-with-contact">
                        <i class="crm-i fa-user" role="img" aria-hidden="true"></i> {if not $multiClient}{ts}With client{/ts}{else}{ts}With client(s){/ts}{/if}
                      </a>
                    </td>
                  </tr>
                {/if}

                <tr class="crm-case-activity-form-block-activityTypeName">
                  <td class="label">{ts}Activity Type{/ts}</td>
                  <td class="view-value bold">{$activityTypeNameAndLabel.displayLabel|escape}</td>
                </tr>
                <tr class="crm-case-activity-form-block-source_contact_id">
                  <td class="label">{$form.source_contact_id.label}</td>
                  <td class="view-value">{$form.source_contact_id.html}</td>
                </tr>
                <tr class="crm-case-activity-form-block-assignee_contact_id">
                  <td class="label">
                    {$form.assignee_contact_id.label}
                    {edit}{help id="assignee_contact_id" file="CRM/Activity/Form/Activity"}{/edit}
                  </td>
                  <td>{$form.assignee_contact_id.html}
                    {if $activityAssigneeNotification}
                      <br />
                      <span id="notify_assignee_msg" class="description"><i class="crm-i fa-paper-plane" role="img" aria-hidden="true"></i> {ts}A copy of this activity will be emailed to each Assignee.{/ts}</span>
                    {/if}
                  </td>
                </tr>

              {* Include special processing fields if any are defined for this activity type (e.g. Change Case Status / Change Case Type). *}

              {if $activityTypeFile neq 'ChangeCaseStartDate'}
                <tr class="crm-case-activity-form-block-subject">
                  <td class="label">{$form.subject.label}</td><td class="view-value">{$form.subject.html|crmAddClass:huge}</td>
                </tr>
              {/if}
              <tr class="crm-case-activity-form-block-medium_id">
                <td class="label">{$form.medium_id.label}</td>
                <td class="view-value">{$form.medium_id.html}</td>
              </tr>
              <tr class="crm-case-activity-form-block-location">
                <td class="label">{$form.location.label}</td>
                <td class="view-value">{$form.location.html|crmAddClass:huge}</td>
              </tr>
              <tr class="crm-case-activity-form-block-activity_date_time">
                <td class="label">{$form.activity_date_time.label}</td>
                <td class="view-value">
                  {$form.activity_date_time.html}
                  {if $action eq 2 && $activityTypeFile eq 'OpenCase'}
                    <div class="description">{ts}Use a <a class="open-inline" href="{$changeStartURL}">Change Start Date</a> activity to change the date{/ts}</div>
                  {/if}
                </td>
              </tr>
              {if $action eq 2 && $activityTypeFile eq 'OpenCase'}
              <tr class="crm-case-activity-form-block-details">
                <td class="label">{ts}Notes{/ts}</td>
                <td class="view-value">
                  {$form.details.html}
                </td>
              </tr>
              {/if}
              <tr>
                <td colspan="2">{include file="CRM/common/customDataBlock.tpl" groupID='' customDataType='Activity'}</td>
              </tr>
              {if NOT $activityTypeFile}
                <tr class="crm-case-activity-form-block-details">
                  <td class="label">{$form.details.label}</td>
                  <td class="view-value">
                    {$form.details.html}
                  </td>
                </tr>
              {/if}
              <tr class="crm-case-activity-form-block-duration">
                <td class="label">{$form.duration.label}</td>
                <td class="view-value">
                  {$form.duration.html}
                  <span class="description">{ts}minutes{/ts}</span>
                </td>
              </tr>
            </table>
        {if $activityTypeFile EQ 'ChangeCaseStatus'
        || $activityTypeFile EQ 'ChangeCaseType'
        || $activityTypeFile EQ 'ChangeCaseStartDate'}
          </div>
        </details>
        {* End of Activity Details accordion tab *}
      {/if}
      </td>
    </tr>
    <tr class="crm-case-activity-form-block-attachment">
      <td colspan="2">{include file="CRM/Form/attachment.tpl"}</td>
    </tr>
    {if $searchRows} {* We have got case role rows to display for "Send Copy To" feature *}
      <tr class="crm-case-activity-form-block-send_copy">
        <td colspan="2">
          <details id="sendcopy" class="crm-accordion-bold">
            <summary>
              {ts}Send a Copy{/ts}
            </summary>
            <div id="sendcopy-body" class="crm-accordion-body">

              <div class="description">{ts}Email a complete copy of this activity record to other people involved with the case. Click the top left box to select all.{/ts}</div>
              {strip}
                <table class="row-highlight">
                  <tr class="columnheader">
                    <th>{$form.toggleSelect.html}&nbsp;</th>
                    <th>{ts}Case Role{/ts}</th>
                    <th>{ts}Name{/ts}</th>
                    <th>{ts}Email{/ts}</th>
                    {if $countId gt 1}<th>{ts}Target Contact{/ts}</th>{/if}
                  </tr>
                  {foreach from=$searchRows item=row key=id}
                    {foreach from=$searchRows.$id item=row1 key=id1}
                      <tr class="{cycle values="odd-row,even-row"}">
                        <td class="crm-case-activity-form-block-contact_{$id1}">{$form.contact_check[$id1].html}</td>
                        <td class="crm-case-activity-form-block-role">{$row1.role}</td>
                        <td class="crm-case-activity-form-block-display_name">{$row1.display_name}</td>
                        <td class="crm-case-activity-form-block-email">{$row1.email}</td>
                        {if $countId gt 1}<td class="crm-case-activity-form-block-display_name">{$row1.managerOf}</td>{/if}
                      </tr>
                    {/foreach}
                  {/foreach}
                </table>
              {/strip}
            </div>
          </details>
        </td>
      </tr>
    {/if}
  <tr class="crm-case-activity-form-block-schedule_followup">
    <td colspan="2">
    {include file="CRM/Activity/Form/FollowUp.tpl" type="case-"}
    </td>
  </tr>
  {* Suppress activity status and priority for changes to status, case type and start date. PostProc will force status to completed. *}
    {if $activityTypeFile NEQ 'ChangeCaseStatus'
    && $activityTypeFile NEQ 'ChangeCaseType'
    && $activityTypeFile NEQ 'ChangeCaseStartDate'}
      <tr>
        <td colspan="2">
          <table class="form-layout-compressed">
            <tr class="crm-case-activity-form-block-status_id">
              <td class="label">{$form.status_id.label}</td><td class="view-value">{$form.status_id.html}</td>
            </tr>
            <tr class="crm-case-activity-form-block-priority_id">
              <td class="label">{$form.priority_id.label}</td><td class="view-value">{$form.priority_id.html}</td>
            </tr>
          </table>
        </td>
      </tr>
    {/if}
    {if !empty($form.tag.html)}
    <tr class="crm-case-activity-form-block-tag">
      <td class="label">{$form.tag.label}</td>
      <td class="view-value">
        <div class="crm-select-container">{$form.tag.html}</div>
      </td>
    </tr>
    {/if}
{if $isTagset}
  <tr class="crm-case-activity-form-block-tag_set"><td colspan="2">{include file="CRM/common/Tagset.tpl" tagsetType='activity'}</td></tr>
{/if}
  </table>

  {/if}

{crmRegion name='case-activity-form'}{/crmRegion}

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

  {if $action eq 1 or $action eq 2}
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
      });
    </script>
    {/literal}
  {/if}

  {if $action neq 8 and $action neq 32768 and empty($activityTypeFile)}
  <script type="text/javascript">
    {if $searchRows}
      {literal}
      cj('#sendcopy').prop('open', function(i, val) {return !val;});
      {/literal}
    {/if}

    {literal}
    cj('#follow-up').prop('open', function(i, val) {return !val;});
    {/literal}
  </script>
  {/if}

  {if $action eq 2 or $action eq 1}
    {literal}
    <script type="text/javascript">
      CRM.$(function($) {
        $('.crm-with-contact').click(function() {
          $('#with-contacts-widget').toggle();
          $('#with-clients').toggle();
          return false;
        });
      });
    </script>
    {/literal}
  {/if}
</div>

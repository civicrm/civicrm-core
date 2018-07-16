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
<div class="batch-update crm-block crm-form-block crm-event-batch-form-block">
  <div class="help">
    {if $context EQ 'statusChange'} {* Update Participant Status task *}
      {ts}Update the status for each participant individually below, or change all statuses to:{/ts}
      {$form.status_change.html}  {help id="id-status_change"}
      {if $status}
        <div class="status">
          <p>{ts}This form <strong>will send email</strong> to contacts only in certain circumstances:{/ts}</p>
          <ul>
            <li>{ts}<strong>Resolving "Pay Later" registrations for online registrations:</strong> Participants who registered online whose status is changed from <em>Pending Pay Later</em> to <em>Registered</em> or <em>Attended</em> will receive a confirmation email and their payment status will be set to completed. If this is not you want to do, you can change their participant status by editing their event registration record directly.{/ts}</li>
          {if $notifyingStatuses}
            <li>{ts 1=$notifyingStatuses}<strong>Special statuses:</strong> Participants whose status is changed to any of the following will be automatically notified via email: %1{/ts}</li>
          {/if}
          </ul>
        </div>
      {/if}
    {else}
      {if $statusProfile EQ 1} {* Update Participant Status in batch task *}
        <div class="status">{$status}</div>
      {/if}
      {ts}Update field values for each participant as needed. To set a field to the same value for ALL rows, enter that value for the first participation and then click the
        <strong>Copy icon</strong>
        (next to the column title).{/ts}
    {/if}
    <p>{ts}Click <strong>Update Participant(s)</strong> below to save all your changes.{/ts}</p>
  </div>
        <table class="crm-copy-fields">
       <thead class="sticky">
            <tr class="columnheader">
             {foreach from=$readOnlyFields item=fTitle key=fName}
              <td>{$fTitle}</td>
           {/foreach}

             <td>{ts}Event{/ts}</td>
             {foreach from=$fields item=field key=fieldName}
                <td><img  src="{$config->resourceBase}i/copy.png" alt="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}" fname="{$field.name}" class="action-icon" title="{ts}Click here to copy the value in row one to ALL rows.{/ts}" />{$field.title}</td>
             {/foreach}

         </tr>
         </thead>
            {foreach from=$componentIds item=pid}
             <tr class="{cycle values="odd-row,even-row"}" entity_id="{$pid}">
        {foreach from=$readOnlyFields item=fTitle key=fName}
           <td>{$contactDetails.$pid.$fName}</td>
        {/foreach}

              <td class="crm-event-title">{$details.$pid.title}</td>
              {foreach from=$fields item=field key=fieldName}
                {assign var=n value=$field.name}

                {* CRM-19860 Copied from templates/CRM/Contact/Form/Task/Batch.tpl *}
                {if $field.options_per_line}
                  <td class="compressed">
                    {assign var="count" value="1"}
                    {strip}
                      <table class="form-layout-compressed">
                      <tr>
                        {* sort by fails for option per line. Added a variable to iterate through the element array*}
                        {assign var="index" value="1"}
                        {foreach name=optionOuter key=optionKey item=optionItem from=$form.field.$pid.$n}
                          {if $index < 10}
                            {assign var="index" value=`$index+1`}
                          {else}
                            <td class="labels font-light">{$form.field.$pid.$n.$optionKey.html}</td>
                            {if $count == $field.options_per_line}
                            </tr>
                            <tr>
                              {assign var="count" value="1"}
                              {else}
                              {assign var="count" value=`$count+1`}
                            {/if}
                          {/if}
                        {/foreach}
                      </tr>
                      </table>
                    {/strip}
                  </td>
                {else}
                  <td class="compressed">{$form.field.$pid.$n.html}</td>
                {/if}
              {/foreach}
             </tr>
            {/foreach}
           </tr>
         </table>
<div class="crm-submit-buttons">
{if $fields}{$form._qf_Batch_refresh.html}{/if}{include file="CRM/common/formButtons.tpl"}
</div>
</div>

{if $context EQ 'statusChange'} {* Update Participant Status task *}
{literal}
<script type="text/javascript">
/**
 * Function to update participant status
 */
CRM.$(function($) {
   $('#status_change').change( function() {
      if ( $(this).val() ) {
        $('.crm-copy-fields [name^="field["][name*="[participant_status]"]').val( $(this).val() );
      }
   });

});
</script>
{/literal}
{/if}

{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}

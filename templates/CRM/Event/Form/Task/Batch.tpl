{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
                <td>{copyIcon name=$field.name title=$field.title}{$field.title}</td>
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
                    {assign var="count" value=1}
                    {strip}
                      <table class="form-layout-compressed">
                      <tr>
                        {* sort by fails for option per line. Added a variable to iterate through the element array*}
                        {foreach name=optionOuter key=optionKey item=optionItem from=$form.field.$pid.$n}
                          {* There are both numeric and non-numeric keys mixed in here, where the non-numeric are metadata that aren't arrays with html members. *}
                          {if is_array($optionItem) && array_key_exists('html', $optionItem)}
                            <td class="labels font-light">{$form.field.$pid.$n.$optionKey.html}</td>
                            {if $count == $field.options_per_line}
                            </tr>
                            <tr>
                              {assign var="count" value=1}
                              {else}
                              {assign var="count" value=$count+1}
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
{if $fields}{$form._qf_Batch_refresh.html}{/if}{include file="CRM/common/formButtons.tpl" location=''}
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

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-activity-task-batch-form-block">
<fieldset>
<div class="help">
    {ts}Update field values for each Activities as needed. Click <strong>Update Activities</strong> below to save all your changes. To set a field to the same value for ALL rows, enter that value for the first Activity and then click the <strong>Copy icon</strong> (next to the column title).{/ts}
</div>
    <div class="crm-submit-buttons">{if $fields}{$form._qf_Batch_refresh.html}{/if}{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
    <table class="crm-copy-fields">
    <thead class="sticky">
            <tr class="columnheader">
             {foreach from=$readOnlyFields item=fTitle key=fName}
              <th>{$fTitle}</th>
           {/foreach}

             {foreach from=$fields item=field key=fieldName}
                <td>{copyIcon name=$field.name title=$field.title}{$field.title}</td>
             {/foreach}
            </tr>
          </thead>
            {foreach from=$componentIds item=activityId}
             <tr class="{cycle values="odd-row,even-row"}" entity_id="{$activityId}">
        {foreach from=$readOnlyFields item=fTitle key=fName}
           <td>{$contactDetails.$activityId.$fName}</td>
        {/foreach}
                {foreach from=$fields item=field key=fieldName}
                  {assign var=n value=$field.name}
                   <td class="compressed">{$form.field.$activityId.$n.html}</td>
              {/foreach}
             </tr>
              {/foreach}
           </tr>
         </table>
         <div class="crm-submit-buttons">{if $fields}{$form._qf_Batch_refresh.html}{/if}{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</fieldset>
</div>

{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}

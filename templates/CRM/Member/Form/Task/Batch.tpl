{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-member-task-batch-form-block">
<div class="help">
    {ts}Update field values for each member as needed. Click <strong>Update Memberships</strong> below to save all your changes. To set a field to the same value for ALL rows, enter that value for the first member and then click the <strong>Copy icon</strong> (next to the column title).{/ts}
</div>
         <div class="crm-submit-buttons">
            {if $fields}{$form._qf_Batch_refresh.html}{/if} &nbsp;{include file="CRM/common/formButtons.tpl" location="top"}
         </div>
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
            {foreach from=$componentIds item=mid}
             <tr class="{cycle values="odd-row,even-row"}" entity_id="{$mid}">

        {foreach from=$readOnlyFields item=fTitle key=fName}
           <td>{$contactDetails.$mid.$fName}</td>
        {/foreach}

              {foreach from=$fields item=field key=fieldName}
                {assign var=n value=$field.name}
                <td class="compressed">{$form.field.$mid.$n.html}</td>
              {/foreach}
             </tr>
            {/foreach}
           </tr>
         </table>
         <div class="crm-submit-buttons">
            {if $fields}{$form._qf_Batch_refresh.html}{/if} &nbsp;{include file="CRM/common/formButtons.tpl" location="bottom"}
         </div>
</div>

{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}

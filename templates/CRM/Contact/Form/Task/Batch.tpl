{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="batch-update crm-form-block crm-contact-task-batch-form-block">
  <div class="help">
  {ts}Update field values for each contact as needed. Click <strong>Update Contacts</strong> below to save all your changes. To set a field to the same value for ALL rows, enter that value for the first contact and then click the <strong>Copy icon</strong> (next to the column title).{/ts}
  </div>
  <table class="crm-copy-fields">
    <thead class="sticky">
    <tr class="columnheader">
      <td>{ts}Name{/ts}</td>
    {foreach from=$fields item=field key=fieldName}
      {if $field.skipDisplay}
        {continue}
      {/if}
      <td>
      {if !$field.is_view}
        {copyIcon name=$field.name title=$field.title}
      {/if}
        {$field.title}
      </td>
    {/foreach}
    </tr>
    </thead>
  {foreach from=$componentIds item=cid}
  <tr class="{cycle values="odd-row,even-row"}" entity_id="{$cid}">
    <td>{$sortName.$cid}</td>
    {foreach from=$fields item=field key=fieldName}
      {if $field.skipDisplay}
        {continue}
      {/if}
      {assign var=n value=$field.name}
      {if $field.options_per_line}
        <td class="compressed">
          {assign var="count" value=1}
          {strip}
            <table class="form-layout-compressed">
            <tr>
            {* sort by fails for option per line. Added a variable to iterate through the element array*}
              {foreach name=optionOuter key=optionKey item=optionItem from=$form.field.$cid.$n}
                {* There are both numeric and non-numeric keys mixed in here, where the non-numeric are metadata that aren't arrays with html members. *}
                {if is_array($optionItem) && array_key_exists('html', $optionItem)}
                  <td class="labels font-light">{$form.field.$cid.$n.$optionKey.html}</td>
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
      {elseif $n|substr:0:5 eq 'phone'}
        <td class="compressed">
          {assign var="phone_ext_field" value=$n|replace:'phone':'phone_ext'}
          {$form.field.$cid.$n.html}
          {if $form.field.$cid.$phone_ext_field.html}
            &nbsp;{$form.field.$cid.$phone_ext_field.html}
          {/if}
        </td>
      {else}
        <td class="compressed">{$form.field.$cid.$n.html}</td>
      {/if}
    {/foreach}
  {/foreach}
  </tr>
  </table>
{if $fields}{$form._qf_BatchUpdateProfile_refresh.html}{/if} &nbsp;<div class="crm-submit-buttons">{$form.buttons.html}</div>

</div>

{*include batch copy js js file*}
{include file="CRM/common/batchCopy.tpl"}

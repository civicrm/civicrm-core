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
      <td><img  src="{$config->resourceBase}i/copy.png" alt="{ts 1=$field.title}Click to copy %1 from row one to all rows.{/ts}" fname="{$field.name}" class="action-icon" title="{ts}Click here to copy the value in row one to ALL rows.{/ts}" />{$field.title}</td>
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
          {assign var="count" value="1"}
          {strip}
            <table class="form-layout-compressed">
            <tr>
            {* sort by fails for option per line. Added a variable to iterate through the element array*}
              {assign var="index" value="1"}
              {foreach name=optionOuter key=optionKey item=optionItem from=$form.field.$cid.$n}
                {if $index < 10}
                  {assign var="index" value=`$index+1`}
                {else}
                  <td class="labels font-light">{$form.field.$cid.$n.$optionKey.html}</td>
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
      {elseif ( $fields.$n.data_type eq 'Date') or ( $n eq 'birth_date' ) or ( $n eq 'deceased_date' ) }
        <td class="compressed">{include file="CRM/common/jcalendar.tpl" elementName=$n elementIndex=$cid batchUpdate=1}</td>
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


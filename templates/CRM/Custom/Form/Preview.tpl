{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=infoTitle}{ts}Preview Mode{/ts}{/capture}
{assign var="infoType" value="info"}
{if $preview_type eq 'group'}
    {capture assign=infoMessage}{ts}Showing the custom data group (fieldset) as it will be displayed within an edit form.{/ts}{/capture}
    {capture name=legend}
        {foreach from=$groupTree item=fieldName}
          {$fieldName.title}
        {/foreach}
    {/capture}
{else}
    {capture assign=infoMessage}{ts}Showing this field as it will be displayed in an edit form.{/ts}{/capture}
{/if}
{include file="CRM/common/info.tpl"}
<div class="crm-block crm-form-block crm-custom-preview-form-block">
{strip}

{foreach from=$groupTree item=cd_edit key=group_id}
    <p></p>
    <fieldset>{if $preview_type eq 'group'}<legend>{$smarty.capture.legend}</legend>{/if}
    {if $cd_edit.help_pre}<div class="messages help">{$cd_edit.help_pre}</div><br />{/if}
    <table class="form-layout-compressed">
    {foreach from=$cd_edit.fields item=element key=field_id}
      {if $element.is_view eq 0}{* fix for CRM-2699 *}
        {if $element.help_pre}
            <tr><td class="label"></td><td class="description">{$element.help_pre}</td></tr>
        {/if}
  {if $element.options_per_line }
        {*assign var="element_name" value=$element.custom_group_id|cat:_|cat:$field_id|cat:_|cat:$element.name*}
        {assign var="element_name" value=$element.element_name}
        <tr>
         <td class="label">{$form.$element_name.label}{if $element.help_post}{help id=$element.id file="CRM/Custom/Form/CustomField.hlp" title=$form.$element_name.label}{/if}</td>
         <td>
            {assign var="count" value="1"}
                <table class="form-layout-compressed">
                 <tr>
                   {* sort by fails for option per line. Added a variable to iterate through the element array*}
                   {assign var="index" value="1"}
                   {foreach name=outer key=key item=item from=$form.$element_name}
                        {if $index < 10}
                            {assign var="index" value=`$index+1`}
                        {else}
                          <td class="labels font-light">{$form.$element_name.$key.html}</td>
                              {if $count == $element.options_per_line}
                                {assign var="count" value="1"}
                           </tr>
                            {else}
                                {assign var="count" value=`$count+1`}
                            {/if}
                         {/if}
                    {/foreach}
                 </tr>
                </table>
         </td>
        </tr>
  {else}
        {assign var="name" value=`$element.name`}
        {*assign var="element_name" value=$group_id|cat:_|cat:$field_id|cat:_|cat:$element.name*}
        {assign var="element_name" value=$element.element_name}
        <tr>
          <td class="label">{$form.$element_name.label}{if $element.help_post}{help id=$element.id file="CRM/Custom/Form/CustomField.hlp" title=$form.$element_name.label}{/if}</td>
        <td>
          {$form.$element_name.html}&nbsp;
      {if $element.html_type eq 'Autocomplete-Select'}
          {if $element.data_type eq 'ContactReference'}
                  {include file="CRM/Custom/Form/ContactReference.tpl"}
                {/if}
        {/if}
          </td>
  {/if}
     {/if}
    {/foreach}
    </table>
    {if $cd_edit.help_post}<br /><div class="messages help">{$cd_edit.help_post}</div>{/if}
    </fieldset>
{/foreach}
{/strip}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>


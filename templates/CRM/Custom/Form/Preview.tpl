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
    {if !empty($cd_edit.help_pre)}<div class="messages help">{$cd_edit.help_pre}</div><br />{/if}
    <table class="form-layout-compressed">
    {foreach from=$cd_edit.fields item=element key=field_id}
      {if $element.is_view eq 0}{* fix for CRM-2699 *}
        {if !empty($element.help_pre)}
            <tr><td class="label"></td><td class="description">{$element.help_pre}</td></tr>
        {/if}
  {if !empty($element.options_per_line)}
        {assign var="element_name" value=$element.element_name}
        <tr class="custom-field-row {$element_name}-row" {if $element.html_type === "Radio"}role="radiogroup" aria-labelledby="{$element_name}_group"{/if}>
         <td class="label"{if $element.html_type === "Radio"} id="{$element.element_name}_group">{$formElement.label|regex_replace:"/\<(\/|)label\>/":""}{else}>{$formElement.label}{/if}{if !empty($element.help_post)}{help id=$element.id file="CRM/Custom/Form/CustomField.hlp" title=$form.$element_name.textLabel}{/if}</td>
         <td>
            {assign var="count" value=1}
                <table class="form-layout-compressed">
                 <tr>
                   {* sort by fails for option per line. Added a variable to iterate through the element array*}
                   {foreach name=outer key=key item=item from=$form.$element_name}
                     {* There are both numeric and non-numeric keys mixed in here, where the non-numeric are metadata that aren't arrays with html members. *}
                     {if is_array($item) && array_key_exists('html', $item)}
                          <td class="labels font-light">{$form.$element_name.$key.html}</td>
                              {if $count == $element.options_per_line}
                                {assign var="count" value=1}
                           </tr>
                            {else}
                                {assign var="count" value=$count+1}
                            {/if}
                         {/if}
                    {/foreach}
                 </tr>
                </table>
         </td>
        </tr>
  {else}
        {capture assign="name"}{if !empty($element.name)}{$element.name}{/if}{/capture}
        {capture assign="element_name"}{if !empty($element.element_name)}{$element.element_name}{/if}{/capture}
        <tr class="custom-field-row {$element_name}-row"  {if $element.html_type === "Radio"}role="radiogroup" aria-labelledby="{$element_name}_group"{/if}>
          <td class="label">{if $element.html_type === "Radio"}<span id="{$element_name}_group">{$element.label}</span>{else}{$form.$element_name.label}{/if}{if !empty($element.help_post)}{help id=$element.id file="CRM/Custom/Form/CustomField.hlp" title=$form.$element_name.textLabel}{/if}</td>
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
    {if !empty($cd_edit.help_post)}<br /><div class="messages help">{$cd_edit.help_post}</div>{/if}
    </fieldset>
{/foreach}
{/strip}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>


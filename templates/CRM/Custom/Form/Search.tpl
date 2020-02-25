{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $groupTree}
{foreach from=$groupTree item=cd_edit key=group_id}

  <div class="crm-accordion-wrapper crm-contactDetails-accordion {if $form.formName eq 'Advanced' AND $cd_edit.collapse_adv_display eq 1}collapsed{/if}" id="{$cd_edit.name}" >
    <div class="crm-accordion-header">
        {$cd_edit.title}
    </div>
    <div class="crm-accordion-body">
    <table class="form-layout-compressed">
    {foreach from=$cd_edit.fields item=element key=field_id}
            {assign var="type" value=`$element.html_type`}
            {assign var="element_name" value='custom_'|cat:$field_id}
            {assign var="operator_name" value='custom_'|cat:$field_id|cat:'_operator'}
            {if $element.is_search_range}
                {assign var="element_name_from" value=$element_name|cat:"_from"}
                {assign var="element_name_to" value=$element_name|cat:"_to"}
                <tr>
                  {if $element.data_type neq 'Date'}
                    <td class="label">{$form.$element_name_from.label}</td><td>
                    {$form.$element_name_from.html|crmAddClass:six}
                    &nbsp;&nbsp;{$form.$element_name_to.label}&nbsp;&nbsp;{$form.$element_name_to.html|crmAddClass:six}
                  {elseif $element.skip_calendar NEQ true }
                    {include file="CRM/Core/DatePickerRangeCustomField.tpl" fieldName=$element_name hideRelativeLabel=0}<td>
                  {/if}
            {else}
                <td class="label">{$form.$element_name.label}</td><td>
                  {$form.$element_name.html}
                {if !empty($form.$operator_name)}
                  <span class="crm-multivalue-search-op" for="{$element_name}">{$form.$operator_name.html}</span>
                  {assign var="add_multivalue_js" value=true}
                {/if}
            {/if}
            {if $element.html_type eq 'Autocomplete-Select'}
                {if $element.data_type eq 'ContactReference'}
                    {include file="CRM/Custom/Form/ContactReference.tpl"}
                {/if}
            {/if}
            </td>
          </tr>
      {/foreach}
     </table>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->

{/foreach}
  {if !empty($add_multivalue_js)}
    {include file="CRM/Custom/Form/MultiValueSearch.js.tpl"}
  {/if}
{/if}


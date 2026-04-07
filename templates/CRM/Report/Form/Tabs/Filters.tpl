{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

<div id="report-tab-set-filters" role="tabpanel" class="civireport-criteria">
  <div class="report-layout">
      {assign var="counter" value=1}
      {foreach from=$filterGroups item=filterGroup}
        {* Wrap custom field sets in collapsed accordion pane. *}
        {if $filterGroup.use_accordion_for_field_selection}
          <details class="crm-accordion-bold crm-accordion">
            <summary>
              {$filterGroup.group_title}
            </summary>
            <div class="crm-accordion-body">
        {/if}
        <table class="report-layout">
        {foreach from=$filterGroup.tables item=table key=tableName}
          {foreach from=$table item=field key=fieldName}
                {assign var=fieldOp     value=$fieldName|cat:"_op"}
                {assign var=filterVal   value=$fieldName|cat:"_value"}
                {assign var=filterMin   value=$fieldName|cat:"_min"}
                {assign var=filterMax   value=$fieldName|cat:"_max"}
                {if !empty($field.operatorType) && $field.operatorType & 4}
                  <tr class="report-contents crm-report crm-report-criteria-filter crm-report-criteria-filter-{$tableName}">
                    <td class="label report-contents">{if !empty($field.title)}{$field.title}{/if}</td>
                      {include file="CRM/Core/DatePickerRangeWrapper.tpl" fieldName=$fieldName hideRelativeLabel=1 from='_from' to='_to' class='' colspan=''}
                  </tr>
                {elseif array_key_exists($fieldOp, $form) && $form.$fieldOp.html}
                  <tr class="report-contents crm-report crm-report-criteria-filter crm-report-criteria-filter-{$tableName}" {if array_key_exists('no_display', $field) && $field.no_display} style="display: none;"{/if}>
                    <td class="label report-contents">{if !empty($field.title)}{$field.title}{/if}</td>
                    <td class="report-contents">{$form.$fieldOp.html}</td>
                    <td>
                      <span id="{$filterVal}_cell">
                        <label class="sr-only" for="{$form.$filterVal.id}">
                          {if !empty($field.title)}{$field.title}{else}{$field.name}{/if} filter value
                        </label>
                        {$form.$filterVal.label}&nbsp;{$form.$filterVal.html}
                      </span>
                      <span id="{$filterMin}_max_cell">
                        {if array_key_exists($filterMin, $form) && $form.$filterMin}{$form.$filterMin.label}&nbsp;{$form.$filterMin.html}&nbsp;&nbsp;{/if}
                        {if array_key_exists($filterMax, $form) && $form.$filterMax}{$form.$filterMax.label}&nbsp;{$form.$filterMax.html}{/if}
                      </span>
                    </td>
                  </tr>
                {/if}
          {/foreach}
        {/foreach}
        </table>
        {if $filterGroup.use_accordion_for_field_selection}
            </div>
          </details>
        {/if}
      {/foreach}
  </div>
</div>

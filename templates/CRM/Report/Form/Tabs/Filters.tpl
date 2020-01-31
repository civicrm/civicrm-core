{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

  <div id="report-tab-set-filters" class="civireport-criteria">
    <table class="report-layout">
      {assign var="counter" value=1}
      {foreach from=$filters item=table key=tableName}
        {assign  var="filterCount" value=$table|@count}
        {* Wrap custom field sets in collapsed accordion pane. *}
        {if $filterGroups.$tableName.group_title and $filterCount gte 1}
          {* we should close table that contains other filter elements before we start building custom group accordion
           *}
          {if $counter eq 1}
    </table>
            {assign var="counter" value=0}
          {/if}
          <div class="crm-accordion-wrapper crm-accordion collapsed">
            <div class="crm-accordion-header">
              {$filterGroups.$tableName.group_title}
            </div><!-- /.crm-accordion-header -->
            <div class="crm-accordion-body">
              <table class="report-layout">
        {/if}
        {foreach from=$table item=field key=fieldName}
                {assign var=fieldOp     value=$fieldName|cat:"_op"}
                {assign var=filterVal   value=$fieldName|cat:"_value"}
                {assign var=filterMin   value=$fieldName|cat:"_min"}
                {assign var=filterMax   value=$fieldName|cat:"_max"}
                {if $field.operatorType & 4}
                  <tr class="report-contents crm-report crm-report-criteria-filter crm-report-criteria-filter-{$tableName}">
                    <td class="label report-contents">{$field.title}</td>
                      {include file="CRM/Core/DateRange.tpl" fieldName=$fieldName from='_from' to='_to'}
                  </tr>
                {elseif $form.$fieldOp.html}
                  <tr class="report-contents crm-report crm-report-criteria-filter crm-report-criteria-filter-{$tableName}" {if $field.no_display} style="display: none;"{/if}>
                    <td class="label report-contents">{$field.title}</td>
                    <td class="report-contents">{$form.$fieldOp.html}</td>
                    <td>
                      <span id="{$filterVal}_cell">{$form.$filterVal.label}&nbsp;{$form.$filterVal.html}</span>
                      <span id="{$filterMin}_max_cell">{$form.$filterMin.label}&nbsp;{$form.$filterMin.html}&nbsp;&nbsp;{$form.$filterMax.label}&nbsp;{$form.$filterMax.html}</span>
                    </td>
                  </tr>
                {/if}
        {/foreach}
        {if $filterGroups.$tableName.group_title}
              </table>
            </div><!-- /.crm-accordion-body -->
          </div><!-- /.crm-accordion-wrapper -->
          {assign var=closed value="1"} {*-- ie table tags are closed-- *}
        {else}
          {assign var=closed     value="0"} {*-- ie table tags are not closed-- *}
        {/if}
      {/foreach}
    {if $closed eq 0 }
      </table>
    {/if}
  </div>

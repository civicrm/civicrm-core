{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{* Report form criteria section *}
    {if $colGroups}
      <div id="col-groups" class="civireport-criteria" >
        {if $componentName eq 'Grant'}
            <h3>{ts}Include these Statistics{/ts}</h3>
        {else}
            <h3>{ts}Display Columns{/ts}</h3>
        {/if}
        {foreach from=$colGroups item=grpFields key=dnc}
            {assign  var="count" value="0"}
            {* Wrap custom field sets in collapsed accordion pane. *}
            {if $grpFields.group_title}
                <div class="crm-accordion-wrapper crm-accordion collapsed">
                    <div class="crm-accordion-header">
                                    {$grpFields.group_title}
                    </div><!-- /.crm-accordion-header -->
                    <div class="crm-accordion-body">
            {/if}
            <table class="criteria-group">
                <tr class="crm-report crm-report-criteria-field crm-report-criteria-field-{$dnc}">
                    {foreach from=$grpFields.fields item=title key=field}
                        {assign var="count" value=`$count+1`}
                        <td width="25%">{$form.fields.$field.html}</td>
                        {if $count is div by 4}
                            </tr><tr class="crm-report crm-report-criteria-field crm-report-criteria-field_{$dnc}">
                        {/if}
                    {/foreach}
                    {if $count is not div by 4}
                        <td colspan="4 - ($count % 4)"></td>
                    {/if}
                </tr>
            </table>
            {if $grpFields.group_title}
                    </div><!-- /.crm-accordion-body -->
                </div><!-- /.crm-accordion-wrapper -->
            {/if}
        {/foreach}
        </div>
    {/if}

    {if $groupByElements}
        <div id="group-by-elements" class="civireport-criteria" >
        <h3>{ts}Group by Columns{/ts}</h3>
        {assign  var="count" value="0"}
        <table class="report-layout">
            <tr class="crm-report crm-report-criteria-groupby">
                {foreach from=$groupByElements item=gbElem key=dnc}
                    {assign var="count" value=`$count+1`}
                    <td width="25%" {if $form.fields.$gbElem}"{/if}>
                        {$form.group_bys[$gbElem].html}
                        {if $form.group_bys_freq[$gbElem].html}:<br>
                            &nbsp;&nbsp;{$form.group_bys_freq[$gbElem].label}&nbsp;{$form.group_bys_freq[$gbElem].html}
                        {/if}
                    </td>
                    {if $count is div by 4}
                        </tr><tr class="crm-report crm-report-criteria-groupby">
                    {/if}
                {/foreach}
                {if $count is not div by 4}
                    <td colspan="4 - ($count % 4)"></td>
                {/if}
            </tr>
        </table>
     </div>
    {/if}

    {if $orderByOptions}
      <div id="order-by-elements" class="civireport-criteria" >
        <h3>{ts}Order by Columns{/ts}</h3>

  <table id="optionField">
        <tr>
        <th>&nbsp;</th>
        <th> {ts}Column{/ts}</th>
        <th> {ts}Order{/ts}</th>
        <th> {ts}Section Header / Group By{/ts}</th>
        <th> {ts}Page Break{/ts}</th>
        </tr>

  {section name=rowLoop start=1 loop=6}
  {assign var=index value=$smarty.section.rowLoop.index}
  <tr id="optionField_{$index}" class="form-item {cycle values="odd-row,even-row"}">
        <td>
        {if $index GT 1}
            <a onclick="hideRow({$index}); return false;" name="orderBy_{$index}" href="#" class="form-link"><img src="{$config->resourceBase}i/TreeMinus.gif" class="action-icon" alt="{ts}hide field or section{/ts}"/></a>
        {/if}
        </td>
        <td> {$form.order_bys.$index.column.html}</td>
        <td> {$form.order_bys.$index.order.html}</td>
        <td> {$form.order_bys.$index.section.html}</td>
        <td> {$form.order_bys.$index.pageBreak.html}</td>
  </tr>
        {/section}
        </table>
            <div id="optionFieldLink" class="add-remove-link">
            <a onclick="showHideRow(); return false;" name="optionFieldLink" href="#" class="form-link"><img src="{$config->resourceBase}i/TreePlus.gif" class="action-icon" alt="{ts}show field or section{/ts}"/>{ts}another column{/ts}</a>
        </div>
        <script type="text/javascript">
            var showRows   = new Array({$showBlocks});
            var hideBlocks = new Array({$hideBlocks});
            var rowcounter = 0;
            {literal}
            if (navigator.appName == "Microsoft Internet Explorer") {
                for ( var count = 0; count < hideBlocks.length; count++ ) {
                    var r = document.getElementById(hideBlocks[count]);
                    r.style.display = 'none';
                }
            }

            // hide and display the appropriate blocks as directed by the php code
            on_load_init_blocks( showRows, hideBlocks, '' );
            
            cj('input[id^="order_by_section_"]').click(disPageBreak).each(disPageBreak);
            
            function disPageBreak() {
              if (!cj(this).prop('checked')) {
                cj(this).parent('td').next('td').children('input[id^="order_by_pagebreak_"]').prop({checked: false, disabled: true});
              }
              else {
                cj(this).parent('td').next('td').children('input[id^="order_by_pagebreak_"]').prop({disabled: false});
              }
            }

            function hideRow(i) {
                showHideRow(i);
                // clear values on hidden field, so they're not saved
                cj('select#order_by_column_'+ i).val('');
                cj('select#order_by_order_'+ i).val('ASC');
                cj('input#order_by_section_'+ i).prop('checked', false);
                cj('input#order_by_pagebreak_'+ i).prop('checked', false);
            }

            {/literal}
        </script>
      </div>
    {/if}

    {if $otherOptions}
        <div id="other-options" class="civireport-criteria" >
        <h3>{ts}Other Options{/ts}</h3>
        <table class="report-layout">
          {assign var="optionCount" value=0}
          <tr class="crm-report crm-report-criteria-field">
          {foreach from=$otherOptions item=optionField key=optionName}
            {assign var="optionCount" value=`$optionCount+1`}
            <td>{if $form.$optionName.label}{$form.$optionName.label}&nbsp;{/if}{$form.$optionName.html}</td>
            {if $optionCount is div by 2}
              </tr><tr class="crm-report crm-report-criteria-field">
            {/if}
          {/foreach}
          {if $optionCount is not div by 2}
            <td colspan="2 - ($count % 2)"></td>
          {/if}
          </tr>
        </table>
        </div>
    {/if}

    {if $filters}
  <div id="set-filters" class="civireport-criteria" >
        <h3>{ts}Set Filters{/ts}</h3>
        <table class="report-layout">
      {assign var="counter" value=1}
            {foreach from=$filters     item=table key=tableName}
           {assign  var="filterCount" value=$table|@count}
            {* Wrap custom field sets in collapsed accordion pane. *}
          {if $colGroups.$tableName.group_title and $filterCount gte 1}
        {* we should close table that contains other filter elements before we start building custom group accordian  *}
        {if $counter eq 1}
                </table>
      {assign var="counter" value=0}
        {/if}
                    <div class="crm-accordion-wrapper crm-accordion collapsed">
                    <div class="crm-accordion-header">
                                    {$colGroups.$tableName.group_title}
                    </div><!-- /.crm-accordion-header -->
                    <div class="crm-accordion-body">
                    <table class="report-layout">
               {/if}
                {foreach from=$table       item=field key=fieldName}
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
                {if $colGroups.$tableName.group_title}
                        </table>
                        </div><!-- /.crm-accordion-body -->
                    </div><!-- /.crm-accordion-wrapper -->
                    {assign var=closed     value=1"} {*-- ie table tags are closed-- *}
                {else}
                     {assign var=closed     value=0"} {*-- ie table tags are not closed-- *}
                {/if}

            {/foreach}
            {if $closed eq 0 }</table>{/if}
        </div>
    {/if}

    {literal}
    <script type="text/javascript">
    {/literal}
        {foreach from=$filters item=table key=tableName}
            {foreach from=$table item=field key=fieldName}
    {literal}var val = "dnc";{/literal}
                {assign var=fieldOp     value=$fieldName|cat:"_op"}
                {if !($field.operatorType & 4) && !$field.no_display && $form.$fieldOp.html}
                    {literal}var val = document.getElementById("{/literal}{$fieldOp}{literal}").value;{/literal}
    {/if}
                {literal}showHideMaxMinVal( "{/literal}{$fieldName}{literal}", val );{/literal}
            {/foreach}
        {/foreach}

        {literal}
        function showHideMaxMinVal( field, val ) {
            var fldVal    = field + "_value_cell";
            var fldMinMax = field + "_min_max_cell";
            if ( val == "bw" || val == "nbw" ) {
                cj('#' + fldVal ).hide();
                cj('#' + fldMinMax ).show();
            } else if (val =="nll" || val == "nnll") {
                cj('#' + fldVal).hide() ;
                cj('#' + field + '_value').val('');
                cj('#' + fldMinMax ).hide();
            } else {
                cj('#' + fldVal ).show();
                cj('#' + fldMinMax ).hide();
            }
        }

    CRM.$(function($) {
      $('.crm-report-criteria-groupby input:checkbox').click(function() {
        $('#fields_' + this.id.substr(10)).prop('checked', this.checked);
      });
      {/literal}{if $displayToggleGroupByFields}{literal}
      $('.crm-report-criteria-field input:checkbox').click(function() {
        $('#group_bys_' + this.id.substr(7)).prop('checked', this.checked);
      });
      {/literal}{/if}{literal}
    });
    </script>
    {/literal}

    <div>{$form.buttons.html}</div>

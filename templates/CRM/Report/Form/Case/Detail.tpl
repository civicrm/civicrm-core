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
{if $section eq 1}
    <div class="crm-block crm-content-block crm-report-layoutGraph-form-block">
        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}
    </div>
    {elseif $section eq 2}
    <div class="crm-block crm-content-block crm-report-layoutTable-form-block">
        {*include the table layout*}
        {include file="CRM/Report/Form/Layout/Table.tpl"}
  </div>
    {else}
    <div class="crm-block crm-form-block crm-report-field-form-block">

    {if !$printOnly} {* NO print section starts *}
    <div {if !$criteriaForm}style="display: none;"{/if}> {* criteria section starts *}
    <div class="crm-accordion-wrapper crm-report_criteria-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header">
    {ts}Report Criteria{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div id="id_{$formTpl}"> {* search section starts *}

    {if $colGroups}
        {if $componentName eq 'Grant'}
            <h3>{ts}Include these Statistics{/ts}</h3>
        {else}
      <h3>Display Columns</h3>
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

  <table class="criteria-group">
  {foreach from=$caseDetailExtra key=field item=fieldDetail}
      <tr class="crm-report crm-report-criteria-field crm-report-criteria-field-{$field}">
          <td width="25%">{$fieldDetail.title}</td><td> {$form.case_detail_extra.$field.html}</td>
      </tr>
        {/foreach}
  </table>
    {/if}

    {if $groupByElements}
        <h3>Group by Columns</h3>
        {assign  var="count" value="0"}
        <table class="report-layout">
            <tr class="crm-report crm-report-criteria-groupby">
                {foreach from=$groupByElements item=gbElem key=dnc}
                    {assign var="count" value=`$count+1`}
                    <td width="25%" {if $form.fields.$gbElem} onClick="selectGroupByFields('{$gbElem}');"{/if}>
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
    {/if}

    {if $form.options.html || $form.options.html}
        <h3>Other Options</h3>
        <table class="report-layout">
            <tr class="crm-report crm-report-criteria-groupby">
          <td>{$form.options.html}</td>
          {if $form.blank_column_end}
              <td>{$form.blank_column_end.label}&nbsp;&nbsp;{$form.blank_column_end.html}</td>
                {/if}
            </tr>
        </table>
    {/if}

    {if $filters}
        <h3>Set Filters</h3>
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
    {/if}

    {literal}
    <script type="text/javascript">
    {/literal}
        {foreach from=$filters item=table key=tableName}
            {foreach from=$table item=field key=fieldName}
    {literal}var val = "dnc";{/literal}
    {if !($field.operatorType == 4) && !$field.no_display}
                    {literal}var val = document.getElementById("{/literal}{$fieldName}_op{literal}").value;{/literal}
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

  function selectGroupByFields(id) {
      var field = 'fields\['+ id+'\]';
      var group = 'group_bys\['+ id+'\]';
      var groups = document.getElementById( group ).checked;
      if ( groups == 1 ) {
          document.getElementById( field ).checked = true;
      } else {
          document.getElementById( field ).checked = false;
      }
  }
    </script>
    {/literal}

    <div>{$form.buttons.html}</div>
    </div> {* search div section ends *}
    </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->
    </div> {* criteria section ends *}

    {if $instanceForm OR $instanceFormError} {* settings section starts *}
    <div class="crm-accordion-wrapper crm-report_setting-accordion {if $rows}collapsed{/if}">
    <div class="crm-accordion-header" {if $updateReportButton} onclick="cj('#update-button').hide(); return false;" {/if} >
    {if $mode eq 'template'}{ts}Create Report{/ts}{else}{ts}Report Settings{/ts}{/if}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
        <div id="id_{$instanceForm}">
                <div id="instanceForm">
                    {include file="CRM/Report/Form/Instance.tpl"}
                    {assign var=save value="_qf_"|cat:$form.formName|cat:"_submit_save"}
                        <div class="crm-submit-buttons">
                            {$form.$save.html}
                        </div>
                </div>
        </div>
    </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->
    {if $updateReportButton}
      <div id='update-button' class="crm-submit-buttons">
    {$form.$save.html}
    </div>
    {/if}
    {/if} {* settings section ends *}

    {/if} {* NO print section ends *}


    </div>

    <div class="crm-block crm-content-block crm-report-form-block">
        {*include actions*}
        {include file="CRM/Report/Form/Actions.tpl"}

        {*Statistics at the Top of the page*}
        {include file="CRM/Report/Form/Statistics.tpl" top=true}

        {*include the graph*}
        {include file="CRM/Report/Form/Layout/Graph.tpl"}

        {*include the table layout*}
        {include file="CRM/Report/Form/Layout/Table.tpl"}
      <br />
        {*Statistics at the bottom of the page*}
        {include file="CRM/Report/Form/Statistics.tpl" bottom=true}

        {include file="CRM/Report/Form/ErrorMessage.tpl"}
    </div>
{/if}

<div id="casedetails"></div>
{literal}
<script type="text/javascript">
function viewCase( caseId ,contactId ) {
   cj("#casedetails").dialog({
        title: "Case Details",
        modal: true,
        width : 700,
  height: 400,
        open:function() {
       var dataUrl = {/literal}"{crmURL p='civicrm/case/ajax/details' h=0 q="snippet=4" }"{literal};
      dataUrl     = dataUrl + '&caseId=' +caseId + '&contactId=' +contactId ;
    cj.ajax({
                         url     : dataUrl,
                         dataType: "html",
                         timeout : 5000, //Time in milliseconds
                         success : function( data ){
                             cj( "#casedetails").html( data ).trigger('crmLoad');
                       },
                   });
    },

               buttons: { "Done": function() { cj(this).dialog("destroy"); }}
    });
}
</script>
{/literal}

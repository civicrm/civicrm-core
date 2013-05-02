{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* CiviContribute DashBoard (launch page) *}
{if $buildTabularView}
<table class="report">
<tr class="columnheader-dark">
    <th scope="col">{ts}Period{/ts}</th>
    <th scope="col">{ts}Total Amount{/ts}</th>
    <th scope="col" title="Contribution Count"><strong>#</strong></th><th></th></tr>
<tr>
    <td><strong>{ts}Current Month-To-Date{/ts}</strong></td>
    <td class="label">{if NOT $monthToDate.Valid.amount}{ts}(n/a){/ts}{else}{$monthToDate.Valid.amount}{/if}</td>
    <td class="label">{$monthToDate.Valid.count}</td>
    <td><a href="{$monthToDate.Valid.url}">{ts}view details{/ts}...</a></td>
</tr>
<tr>
    <td><strong>{ts}Current Fiscal Year-To-Date{/ts}</strong></td>
    <td class="label">{if NOT $yearToDate.Valid.amount}{ts}(n/a){/ts}{else}{$yearToDate.Valid.amount}{/if}</td>
    <td class="label">{$yearToDate.Valid.count}</td>
    <td><a href="{$yearToDate.Valid.url}">{ts}view details{/ts}...</a></td>
</tr>
<tr>
    <td><strong>{ts}Cumulative{/ts}</strong><br />{ts}(since inception){/ts}</td>
    <td class="label">{if NOT $startToDate.Valid.amount}{ts}(n/a){/ts}{else}{$startToDate.Valid.amount}{/if}</td>
    <td class="label">{$startToDate.Valid.count}</td>
    <td><a href="{$startToDate.Valid.url}">{ts}view details{/ts}...</a></td>
</tr>
</table>
{elseif $buildChart}
  {include file = "CRM/Contribute/Form/ContributionCharts.tpl"}
{else}
  <h3>{ts}Contribution Summary{/ts} {help id="id-contribute-intro"}</h3>
      <div id="mainTabContainer" class="ui-tabs ui-widget ui-widget-content ui-corner-all">
        <ul class="ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all">
           <li id="chart_view"   class="crm-tab-button ui-state-active ui-corner-top ui-corner-bottom ui-tabs-selected" >
             <a href="#chart_layout"><span>&nbsp;</span>&nbsp;{ts}Chart Layout{/ts}&nbsp;</a> </li>&nbsp;
           <li id ="table_view"  class="crm-tab-button ui-corner-top ui-corner-bottom ui-state-default" >
             <a href="#table_layout"><span>&nbsp;</span>&nbsp;{ts}Table Layout{/ts}&nbsp;</a>
           </li>
{if $isAdmin}
 {capture assign=newPageURL}{crmURL p="civicrm/admin/contribute/add" q="action=add&reset=1"}{/capture}
 {capture assign=configPagesURL}{crmURL p="civicrm/admin/contribute" q="reset=1"}{/capture}

<div class="float-right">
<table class="form-layout-compressed">
<tr>
    <td>
     <a href="{$configPagesURL}" class="button"><span>{ts}Manage Contribution Pages{/ts}
       </span></a>
    </td>
    <td><a href="{$newPageURL}" class="button"><span><div class="icon add-icon"></div>{ts}Add Contribution Page{/ts}
        </span></a>
    </td>
</tr>
</table>
</div>
{/if}
</ul>
<div id="chartData"></div>
<div id="tableData"></div></div>
<div class="spacer"></div>

{if $pager->_totalItems}
    <h3>{ts}Recent Contributions{/ts}</h3>
    <div>
        {include file="CRM/Contribute/Form/Selector.tpl" context="dashboard"}
    </div>
{/if}{literal}
<script type="text/javascript">

cj(document).ready( function( ) {
    getChart( );
    cj('#chart_view').click(function( ) {
        if ( cj('#chart_view').hasClass('ui-state-default') ) {
            cj('#chart_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
            cj('#table_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
            getChart( );
            cj('#tableData').children().html('');
        }
    });
    cj('#table_view').click(function( ) {
        if ( cj('#table_view').hasClass('ui-state-default') ) {
            cj('#table_view').removeClass('ui-state-default').addClass('ui-state-active ui-tabs-selected');
            cj('#chart_view').removeClass('ui-state-active ui-tabs-selected').addClass('ui-state-default');
            buildTabularView();
            cj('#chartData').children().html('');
        }
    });
});

function getChart( ) {
   var year        = cj('#select_year').val( );
   var charttype   = cj('#chart_type').val( );
   var date        = new Date()
   var currentYear = date.getFullYear( );
   if ( !charttype ) charttype = 'bvg';
   if ( !year ) year           = currentYear;

   var chartUrl = {/literal}"{crmURL p='civicrm/ajax/chart' q='snippet=4' h=0}"{literal};
   chartUrl    += "&year=" + year + "&type=" + charttype;
   cj.ajax({
       url     : chartUrl,
       async    : false,
       success  : function(html){
           cj( "#chartData" ).html( html );
       }
   });

}

function buildTabularView( ) {
    var tableUrl = {/literal}"{crmURL p='civicrm/contribute/ajax/tableview' q='showtable=1&snippet=4' h=0}"{literal};
    cj.ajax({
        url      : tableUrl,
        async    : false,
        success  : function(html){
            cj( "#tableData" ).html( html );
        }
    });
}

</script>
{/literal}

{/if}
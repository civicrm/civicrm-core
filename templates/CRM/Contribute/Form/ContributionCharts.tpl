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
{* Display monthly and yearly contributions using Google charts (Bar and Pie) *}
{if $hasContributions}
<div id="chartData">
<table class="chart">
  <tr class="crm-contribution-form-block-open_flash_chart">
     <td>
         {if $hasByMonthChart}
             {* display monthly chart *}
             <div id="open_flash_chart_by_month"></div>
         {else}
       {ts}There were no contributions during the selected year.{/ts}
         {/if}
     </td>
     <td>
          {* display yearly chart *}
         <div id="open_flash_chart_by_year"></div>
     </td>
  </tr>
</table>
<div class="form-layout-compressed" >
<table >
      <td class="label">{$form.select_year.label}</td><td>{$form.select_year.html}</td>
      <td class="label">{$form.chart_type.label}</td><td>{$form.chart_type.html}</td>
</table>
</div>
{else}
 <div class="messages status no-popup">
    {ts}There are no live contribution records to display.{/ts}
 </div>
{/if}

{if $hasOpenFlashChart}
{include file="CRM/common/openFlashChart.tpl" contriChart=true}

{literal}
<script type="text/javascript">

  CRM.$(function($) {
      buildChart( );
  });

  function buildChart( ) {
     var chartData = {/literal}{$openFlashChartData}{literal};
     cj.each( chartData, function( chartID, chartValues ) {

   var xSize   = eval( "chartValues.size.xSize" );
   var ySize   = eval( "chartValues.size.ySize" );
   var divName = eval( "chartValues.divName" );

   createSWFObject( chartID, divName, xSize, ySize, 'loadData' );
     });
  }

  function loadData( chartID ) {
     var allData = {/literal}{$openFlashChartData}{literal};
     var data    = eval( "allData." + chartID + ".object" );
     return JSON.stringify( data );
  }

  function byMonthOnClick( barIndex ) {
     var allData = {/literal}{$openFlashChartData}{literal};
     var url     = eval( "allData.by_month.on_click_urls.url_" + barIndex );
     if ( url ) window.location.href = url;
  }

  function byYearOnClick( barIndex ) {
     var allData = {/literal}{$openFlashChartData}{literal};
     var url     = eval( "allData.by_year.on_click_urls.url_" + barIndex );
     if ( url ) window.location.href = url;
  }

 </script>
{/literal}
{/if}

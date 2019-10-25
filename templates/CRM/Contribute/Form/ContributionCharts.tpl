{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
<table >
  <tr class="crm-contribution-form-block-chart">
     <td width="50%">
         {if $hasByMonthChart}
             {* display monthly chart *}
             <div id="chart_by_month"></div>
         {else}
       {ts}There were no contributions during the selected year.{/ts}
         {/if}
     </td>
     <td width="50%">
          {* display yearly chart *}
         <div id="chart_by_year"></div>
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

{if $hasChart}
{include file="CRM/common/chart.tpl" contriChart=true}

{literal}
<script type="text/javascript">

  CRM.$(function($) {
    var allData = {/literal}{$chartData}{literal};

    $.each( allData, function( chartID, chartValues ) {
        var divName = "chart_" + chartID;
        createChart( chartID, divName, 300, 300, allData[chartID].object );
        });

    function byMonthOnClick( barIndex ) {
       var url = allData.by_month.on_click_urls['url_' + barIndex];
       if ( url ) window.location.href = url;
    }

    function byYearOnClick( barIndex ) {
       var url = allData.by_year.on_click_urls['url_' + barIndex];
       if ( url ) window.location.href = url;
    }

  });
</script>
{/literal}
{/if}

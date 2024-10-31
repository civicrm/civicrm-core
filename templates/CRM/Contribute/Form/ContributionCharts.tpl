{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
{literal}
<script type="text/javascript">

  CRM.$(function($) {
    var allData = {/literal}{$chartData}{literal};

    $.each( allData, function( chartID, chartValues ) {
        var divName = "chart_" + chartID;
        CRM.visual.createChart( chartID, divName, allData[chartID].object );
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

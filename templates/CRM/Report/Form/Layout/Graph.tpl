{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{assign var=uploadURL value=$config->imageUploadURL|replace:'/persist/contribute/':'/persist/'}
{* Display weekly,Quarterly,monthly and yearly contributions using pChart (Bar and Pie) *}
{if $chartEnabled and $chartSupported}
  <div class='crm-chart'>
    {if $outputMode eq 'print' OR $outputMode eq 'pdf'}
      <img src="{$uploadURL|cat:$chartId}.png" />
    {else}
      <div id="chart_{$uniqueId}"></div>
    {/if}
  </div>
{/if}

{if !$printOnly} {* NO print section starts *}
  {if !$section}
    {include file="CRM/common/chart.tpl" divId="chart_$uniqueId"}
  {/if}
  {if $chartData}
    {literal}
    <script type="text/javascript">
       CRM.$(function($) {
         // Build all charts.
         var allData = {/literal}{$chartData}{literal};

         $.each( allData, function( chartID, chartValues ) {
           var divName = {/literal}"chart_{$uniqueId}"{literal};
           createChart( chartID, divName, chartValues.size.xSize, chartValues.size.ySize, allData[chartID].object );
         });

         $("input[id$='submit_print'],input[id$='submit_pdf']").bind('click', function(e){
           // image creator php file path and append image name
           var url = CRM.url('civicrm/report/chart', 'name=' + '{/literal}{$chartId}{literal}' + '.png');

           //fetch object and 'POST' image
           swfobject.getObjectById("chart_{/literal}{$uniqueId}{literal}").post_image(url, true, false);
         });
       });

    </script>
    {/literal}
  {/if}
{/if}

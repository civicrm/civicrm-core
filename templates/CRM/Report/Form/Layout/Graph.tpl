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
{if $chartEnabled and !empty($chartSupported)}
  <div class='crm-chart'>
    {if $outputMode eq 'print' OR $outputMode eq 'pdf'}
      <img src="{$uploadURL|cat:$chartId}.png" />
    {else}
      <div id="chart_{$uniqueId}"></div>
    {/if}
  </div>
{/if}

{if empty($printOnly)} {* NO print section starts *}
  {if !empty($chartData)}
    {literal}
    <script type="text/javascript">
       CRM.$(function($) {
         // Build all charts.
         var allData = {/literal}{$chartData}{literal};

         $.each( allData, function( chartID, chartValues ) {
           var divName = {/literal}"chart_{$uniqueId}"{literal};
           CRM.visual.createChart( chartID, divName, allData[chartID].object );
         });
       });

    </script>
    {/literal}
  {/if}
{/if}

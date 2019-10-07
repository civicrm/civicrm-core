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

  {literal}
  <script type="text/javascript">
     CRM.$(function($) {
      var allData = {/literal}{$chartData}{literal};
       buildChart( );

       $("input[id$='submit_print'],input[id$='submit_pdf']").bind('click', function(e){
         // image creator php file path and append image name
         var url = CRM.url('civicrm/report/chart', 'name=' + '{/literal}{$chartId}{literal}' + '.png');

         //fetch object and 'POST' image
         swfobject.getObjectById("chart_{/literal}{$uniqueId}{literal}").post_image(url, true, false);
       });

       function buildChart( ) {
         $.each( allData, function( chartID, chartValues ) {
           var divName = {/literal}"chart_{$uniqueId}"{literal};
           createChart( chartID, divName, chartValues.size.xSize, chartValues.size.ySize, allData[chartID].object );
         });
       }
     });

  </script>
  {/literal}
{/if}

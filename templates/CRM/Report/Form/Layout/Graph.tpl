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
{assign var=uploadURL value=$config->imageUploadURL|replace:'/persist/contribute/':'/persist/'|cat:'openFlashChart/'}
{* Display weekly,Quarterly,monthly and yearly contributions using pChart (Bar and Pie) *}
{if $chartEnabled and $chartSupported}
<div class='crm-flashchart'>
<table class="chart">
        <tr>
            <td>
                {if $outputMode eq 'print' OR $outputMode eq 'pdf'}
                    <img src="{$uploadURL|cat:$chartId}.png" />
                {else}
              <div id="open_flash_chart_{$uniqueId}"></div>
                {/if}
            </td>
        </tr>
</table>
</div>

{if !$printOnly} {* NO print section starts *}
{if !$section}
        {include file="CRM/common/openFlashChart.tpl" divId="open_flash_chart_$uniqueId"}
{/if}

{literal}
<script type="text/javascript">
   cj( function( ) {
      buildChart( );

      var resourceURL = "{/literal}{$config->userFrameworkResourceURL}{literal}";
      var uploadURL   = "{/literal}{$uploadURL|cat:$chartId}{literal}.png";
      var uploadDir   = "{/literal}{$config->imageUploadDir|replace:'/persist/contribute/':'/persist/'|cat:'openFlashChart/'}{literal}";

      cj("input[id$='submit_print'],input[id$='submit_pdf']").bind('click', function(){
        var url = resourceURL +'packages/OpenFlashChart/php-ofc-library/ofc_upload_image.php';  // image creator php file path
           url += '?name={/literal}{$chartId}{literal}.png';                                    // append image name
           url += '&defaultPath=' + uploadDir;                                                  // append directory path

        //fetch object
        swfobject.getObjectById("open_flash_chart_{/literal}{$uniqueId}{literal}").post_image( url, true, false );
        });
    });

  function buildChart( ) {
     var chartData = {/literal}{$openFlashChartData}{literal};
     cj.each( chartData, function( chartID, chartValues ) {
       var xSize   = eval( "chartValues.size.xSize" );
       var ySize   = eval( "chartValues.size.ySize" );
       var divName = {/literal}"open_flash_chart_{$uniqueId}"{literal};

       var loadDataFunction  = {/literal}"loadData{$uniqueId}"{literal};
       createSWFObject( chartID, divName, xSize, ySize, loadDataFunction );
     });
  }

  function loadData{/literal}{$uniqueId}{literal}( chartID ) {
      var allData = {/literal}{$openFlashChartData}{literal};
      var data    = eval( "allData." + chartID + ".object" );
      return JSON.stringify( data );
  }
</script>
{/literal}
{/if}
{/if}

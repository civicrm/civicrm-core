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
<script type="text/javascript" src="{$config->resourceBase}packages/OpenFlashChart/js/json/openflashchart.packed.js"></script>
<script type="text/javascript" src="{$config->resourceBase}packages/OpenFlashChart/js/swfobject.js"></script>
{literal}
<script type="text/javascript">
    function createSWFObject( chartID, divName, xSize, ySize, loadDataFunction ) {
       var flashFilePath = {/literal}"{$config->resourceBase}packages/OpenFlashChart/open-flash-chart.swf"{literal};

       //create object.
       swfobject.embedSWF( flashFilePath, divName,
                         xSize, ySize, "9.0.0",
                         "expressInstall.swf",
                         {"get-data":loadDataFunction, "id":chartID},
                         null,
                         {"wmode": 'transparent'}
                        );
    }
  OFC = {};
  OFC.jquery = {
           name: "jQuery",
             image: function(src) { return "<img src='data:image/png;base64," + $('#'+src)[0].get_img_binary() + "' />"},
             popup: function(src) {
             var img_win = window.open('', 'Save Chart as Image');
           img_win.document.write('<html><head><title>Save Chart as Image<\/title><\/head><body>' + OFC.jquery.image(src) + ' <\/body><\/html>');
           img_win.document.close();
                       }
                 }

function save_image( divName ) {
      var divId = {/literal}"{$contriChart}"{literal} ? 'open_flash_chart_'+divName : {/literal}"{$divId}"{literal};
          if( !divId ) {
               divId = 'open_flash_'+divName;
        }
      OFC.jquery.popup( divId );
}

</script>
{/literal}
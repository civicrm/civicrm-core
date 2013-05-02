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
<div class="crm-block crm-form-block">

<h3>Mapper - 4 Selectors</h3>

<div class="form-item">
   <dl>
     {section name=count start=1 loop=`$maxMapper`}
     {assign var='i' value=$smarty.section.count.index}
       <dt>{$form.mapper[$i].label}</dt><dd>{$form.mapper[$i].html}<span class="tundra" id="id_map_mapper[{$i}]_1"><span id="id_mapper[{$i}]_1"></span></span><span class="tundra" id="id_map_mapper[{$i}]_2"><span id="id_mapper[{$i}]_2"></span></span><span class="tundra" id="id_map_mapper[{$i}]_3"><span id="id_mapper[{$i}]_3"></span></span></dd>

       {literal}
        <script type="text/javascript">
              var selId = "id_map_" + {/literal}"mapper[{$i}]"{literal} + "_1";
              document.getElementById(selId).style.display = "none";
              var selId = "id_map_" + {/literal}"mapper[{$i}]"{literal} + "_2";
              document.getElementById(selId).style.display = "none";
              var selId = "id_map_" + {/literal}"mapper[{$i}]"{literal} + "_3";
              document.getElementById(selId).style.display = "none";
        </script>
       {/literal}

     {/section}
   </dl>    
</div>
    
<div id="crm-submit-buttons" class="form-item">
<dl>
   <dt>&nbsp;</dt><dd>{$form.buttons.html}</dd>
</dl>
</div>


{literal}
<script type="text/javascript">
      function showHideSelector2( sel1Name, sel1Val ) {
            if (sel1Val) {
                document.getElementById("id_map_" + sel1Name + "_1").style.display = "inline";
            } else {
                document.getElementById("id_map_" + sel1Name + "_1").style.display = "none";
                document.getElementById("id_map_" + sel1Name + "_2").style.display = "none";
                document.getElementById("id_map_" + sel1Name + "_3").style.display = "none";
            }
      }
      function showHideSelector3( sel1Name, sel1Val ) {
            if (sel1Val) {
                document.getElementById("id_map_" + sel1Name + "_2").style.display = "inline";
            } else {
                document.getElementById("id_map_" + sel1Name + "_2").style.display = "none";
                document.getElementById("id_map_" + sel1Name + "_3").style.display = "none";
            }
      }
      function showHideSelector4( sel1Name, sel1Val ) {
            if (sel1Val) {
                document.getElementById("id_map_" + sel1Name + "_3").style.display = "inline";
            } else {
                document.getElementById("id_map_" + sel1Name + "_3").style.display = "none";
            }
      }
</script>
{/literal}
</div>
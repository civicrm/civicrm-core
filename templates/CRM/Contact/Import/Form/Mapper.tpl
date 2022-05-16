{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block">

<h3>{ts}Mapper - 4 Selectors{/ts}</h3>

<div class="form-item">
   <dl>
     {section name=count start=1 loop=`$maxMapper`}
     {assign var='i' value=$smarty.section.count.index}
       <dt>{$form.mapper[$i].label}</dt><dd>{$form.mapper[$i].html|smarty:nodefaults}<span class="tundra" id="id_map_mapper[{$i}]_1"><span id="id_mapper[{$i}]_1"></span></span><span class="tundra" id="id_map_mapper[{$i}]_2"><span id="id_mapper[{$i}]_2"></span></span><span class="tundra" id="id_map_mapper[{$i}]_3"><span id="id_mapper[{$i}]_3"></span></span></dd>

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

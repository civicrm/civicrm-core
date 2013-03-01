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
{literal}
//Highlight the required field during import
paramsArray = new Array();

//build the an array of highlighted elements
{/literal}
{foreach from=$highlightedFields item=paramName}
    paramsArray["{$paramName}"] = "1";
{/foreach}
{literal}

//get select object of first element
selObj = document.getElementById("mapper\[0\]\[0\]");

for ( i = 0; i < selObj.options.length; i++ ) {
    //check value is exist in array
    if (selObj.options[i].value in paramsArray) {
        //change background Color of all element whose ids start with mapper and end with [0];
        cj('select[id^="mapper"][id$="[0]"]').each( function( ) {
            cj(this.options[i]).append(' *').css({"color":"#FF0000"});
            });
    }
}

{/literal}{if $relationship}{literal}

    //Highlight the required field during import (Relationship fields*)
    paramsArrayRel = new Array();

    //build the an array of highlighted elements
    {/literal}
    {foreach from=$highlightedRelFields key=relId item=paramsRel}
        {literal}
        paramsArrayRel["{/literal}{$relId}{literal}"] = new Array();
        {/literal}
        {foreach from=$paramsRel item=paramNameRel}
            paramsArrayRel["{$relId}"]["{$paramNameRel}"] = "1";
        {/foreach}
    {/foreach}
    {literal}

    var object = 'select[id^="mapper"][id$="[0]"]';
    cj(object).bind( 'change', function(){highlight(this);});
    cj('div#map-field').one( 'mouseenter', function(){highlight(object);});

    function highlight(obj){
        cj(obj).each(function(){
            // get selected element id
            var currentId = this.id;

            // create relationship related field ID ( replace last [0] with [1] )
            var newId     = currentId.replace(/\[0\]$/, "\[1\]");

            // get the option value
            var selected  = cj(this).val();

            // get obeject of select field
            selObjRel = document.getElementById(newId);

            if ( paramsArrayRel[selected] != undefined ) {
                for ( i = 0; i < selObjRel.options.length; i++ ) {
                    //check value is exist in array
                    if (selObjRel.options[i].value in paramsArrayRel[selected]) {
                        cj(selObjRel).each( function( ) {
                            cj(selObjRel.options[i]).append(' *').css({"color":"#FF0000"});
                        });
                    }
                }
            }
        });
    }
{/literal}
{/if}
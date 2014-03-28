{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{assign var="customGreeting" value=$n|cat:"_custom"}
<span>{$form.$n.html|crmAddClass:big}</span>&nbsp;<span id="{$customGreeting}_html" class="hiddenElement">{$form.$customGreeting.html|crmAddClass:big}</span>

<script type="text/javascript">
var fieldName = '{$n}';
{literal}
cj( "#" + fieldName ).change( function( ) {
    var fldName = cj(this).attr( 'id' );
    showCustom( fldName, cj(this).val( ) );
});

showCustom( fieldName, cj( "#" + fieldName ).val( ) );
function showCustom( fldName, value ) {
    if ( value == 4 ) {
        cj( "#" + fldName + "_custom_html").show( );
    } else {
        cj( "#" + fldName + "_custom_html").hide( );
        cj( "#" + fldName + "_custom" ).val('');
    }
}
{/literal}
</script>

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{assign var="customGreeting" value=$n|cat:"_custom"}
<span>{$form.$n.html|crmAddClass:big}</span>&nbsp;<span id="{$customGreeting}_html" class="hiddenElement">{$form.$customGreeting.html|crmAddClass:big}</span>

<script type="text/javascript">
var fieldName = {$n|@json_encode};
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

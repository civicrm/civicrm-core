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
{* This file provides the plugin for the communication preferences in all the three types of contact *}
{* @var $form Contains the array for the form elements and other form associated information assigned to the template by the controller *}

<div class="crm-accordion-wrapper crm-commPrefs-accordion collapsed">
 <div class="crm-accordion-header">
    {$title}
  </div><!-- /.crm-accordion-header -->
<div id="commPrefs" class="crm-accordion-body">
    <table class="form-layout-compressed" >
        {if !empty($form.communication_style_id)}
            <tr><td colspan='4'>
                <span class="label">{$form.communication_style_id.label} {help id="id-communication_style" file="CRM/Contact/Form/Contact.hlp"}</span>
                <span class="value">{$form.communication_style_id.html}</span>
            </td><tr>
        {/if}
        <tr>
            {if !empty($form.email_greeting_id)}
                <td>{$form.email_greeting_id.label}</td>
            {/if}
            {if !empty($form.postal_greeting_id)}
                <td>{$form.postal_greeting_id.label}</td>
            {/if}
            {if !empty($form.addressee_id)}
                <td>{$form.addressee_id.label}</td>
            {/if}
      {if !empty($form.email_greeting_id) OR !empty($form.postal_greeting_id) OR !empty($form.addressee_id)}
                <td>&nbsp;&nbsp;{help id="id-greeting" file="CRM/Contact/Form/Contact.hlp"}</td>
      {/if}
        </tr>
        <tr>
            {if !empty($form.email_greeting_id)}
                <td>
                    <span id="email_greeting" {if !empty($email_greeting_display) and $action eq 2} class="hiddenElement"{/if}>{$form.email_greeting_id.html|crmAddClass:big}</span>
                    {if !empty($email_greeting_display) and $action eq 2}
                        <div id="email_greeting_display" class="view-data">{$email_greeting_display}&nbsp;&nbsp;<a href="#" onclick="showGreeting('email_greeting');return false;"><img src="{$config->resourceBase}i/edit.png" border="0" title="{ts}Edit{/ts}"></a></div>
                    {/if}
                </td>
            {/if}
            {if !empty($form.postal_greeting_id)}
                <td>
                    <span id="postal_greeting" {if !empty($postal_greeting_display) and $action eq 2} class="hiddenElement"{/if}>{$form.postal_greeting_id.html|crmAddClass:big}</span>
                    {if !empty($postal_greeting_display) and $action eq 2}
                        <div id="postal_greeting_display" class="view-data">{$postal_greeting_display}&nbsp;&nbsp;<a href="#" onclick="showGreeting('postal_greeting');return false;"><img src="{$config->resourceBase}i/edit.png" border="0" title="{ts}Edit{/ts}"></a></div>
                    {/if}
                </td>
            {/if}
            {if !empty($form.addressee_id)}
                <td>
                    <span id="addressee" {if !empty($addressee_display) and $action eq 2} class="hiddenElement"{/if}>{$form.addressee_id.html|crmAddClass:big}</span>
                    {if !empty($addressee_display) and $action eq 2}
                        <div id="addressee_display" class="view-data">{$addressee_display}&nbsp;&nbsp;<a href="#" onclick="showGreeting('addressee');return false;"><img src="{$config->resourceBase}i/edit.png" border="0" title="{ts}Edit{/ts}"></a></div>
                    {/if}
                </td>
            {/if}
        </tr>
        <tr id="greetings1" class="hiddenElement">
            {if !empty($form.email_greeting_custom)}
                <td><span id="email_greeting_id_label" class="hiddenElement">{$form.email_greeting_custom.label}</span></td>
            {/if}
            {if !empty($form.postal_greeting_custom)}
                <td><span id="postal_greeting_id_label" class="hiddenElement">{$form.postal_greeting_custom.label}</span></td>
            {/if}
            {if !empty($form.addressee_custom)}
                <td><span id="addressee_id_label" class="hiddenElement">{$form.addressee_custom.label}</span></td>
            {/if}
        </tr>
        <tr id="greetings2" class="hiddenElement">
            {if !empty($form.email_greeting_custom)}
                <td><span id="email_greeting_id_html" class="hiddenElement">{$form.email_greeting_custom.html|crmAddClass:big}</span></td>
            {/if}
             {if !empty($form.postal_greeting_custom)}
                <td><span id="postal_greeting_id_html" class="hiddenElement">{$form.postal_greeting_custom.html|crmAddClass:big}</span></td>
            {/if}
            {if !empty($form.addressee_custom)}
                <td><span id="addressee_id_html" class="hiddenElement">{$form.addressee_custom.html|crmAddClass:big}</span></td>
            {/if}
        </tr>
        <tr>
            {foreach key=key item=item from=$commPreference}
                <td>
                    <br /><span class="label">{$form.$key.label}</span> {help id="id-$key" file="CRM/Contact/Form/Contact.hlp"}
                    {foreach key=k item=i from=$item}
                     <br />{$form.$key.$k.html}
                    {/foreach}
                </td>
            {/foreach}
                 <td>
                     <br /><span class="label">{$form.preferred_language.label}</span>
                     <br />{$form.preferred_language.html}
                </td>
        </tr>
        <tr>
            <td>{$form.is_opt_out.html} {$form.is_opt_out.label} {help id="id-optOut" file="CRM/Contact/Form/Contact.hlp"}</td>
            {if !empty($form.preferred_mail_format)}
                <td>{$form.preferred_mail_format.label} &nbsp;
                    {$form.preferred_mail_format.html} {help id="id-emailFormat" file="CRM/Contact/Form/Contact.hlp"}
                </td>
            {/if}
        </tr>
    </table>
 </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->


{literal}
<script type="text/javascript">
CRM.$(function($) {
    var fields = new Array( 'postal_greeting', 'addressee', 'email_greeting');
    for ( var i = 0; i < 3; i++ ) {
        $( "#" + fields[i] + "_id").change( function( ) {
            var fldName = $(this).attr( 'id' );
            if ( $(this).val( ) == 4 ) {
                $("#greetings1").show( );
                $("#greetings2").show( );
                $( "#" + fldName + "_html").show( );
                $( "#" + fldName + "_label").show( );
            } else {
                $( "#" + fldName + "_html").hide( );
                $( "#" + fldName + "_label").hide( );
                $( "#" + fldName.slice(0, -3) + "_custom" ).val('');
            }
        });
    }
});

function showGreeting( element ) {
    cj("#" + element ).show( );
    cj("#" + element + '_display' ).hide( );

    // TO DO fix for custom greeting
    var fldName = '#' + element + '_id';
    if ( cj( fldName ).val( ) == 4 ) {
        cj("#greetings1").show( );
        cj("#greetings2").show( );
        cj( fldName + "_html").show( );
        cj( fldName + "_label").show( );
    }
}


</script>
{/literal}

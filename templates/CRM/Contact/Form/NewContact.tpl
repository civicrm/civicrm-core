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
{* template for adding form elements for selecting existing or creating new contact*}
{if !in_array($context, array('search','advanced', 'builder')) }
  {assign var='fldName' value=$prefix|cat:'contact'}
  {assign var='profSelect' value=$prefix|cat:'profiles'}

  {if $noLabel}
    <div>
      {if !$skipBreak}
        {$form.$fldName.$blockNo.html} <br/>
        {if $form.$profSelect}
          {ts}OR{/ts}<br/>{$form.$profSelect.$blockNo.html}<div id="contact-dialog-{$prefix}{$blockNo}" class="hiddenElement"></div>
        {/if}
      {else}
        {$form.$fldName.$blockNo.html}
        {if $form.$profSelect}
          &nbsp;&nbsp;{ts}OR{/ts}&nbsp;&nbsp;{$form.$profSelect.$blockNo.html}<div id="contact-dialog-{$prefix}{$blockNo}" class="hiddenElement"></div>
        {/if}
      {/if}
    </div>
  {else}
    <tr class="crm-new-contact-form-block-contact crm-new-contact-form-block-contact-{$blockNo}">
      <td class="label">{$form.$fldName.$blockNo.label}</td>
      <td>{$form.$fldName.$blockNo.html}
        {if $form.$profSelect}
          &nbsp;&nbsp;{ts}OR{/ts}&nbsp;&nbsp;{$form.$profSelect.$blockNo.html}<div id="contact-dialog-{$prefix}{$blockNo}" class="hiddenElement"></div>
        {/if}
      </td>
    </tr>
  {/if}

{literal}
<script type="text/javascript">
  var allowMultiClient = Boolean({/literal}{if !empty($multiClient)}1{else}0{/if}{literal});

  {/literal}
  var prePopulateData = '';
  {if $prePopulateData}
      prePopulateData = {$prePopulateData};
  {/if}
  {literal}

  var existingTokens = '';
  cj( function( ) {
    // add multiple client option if configured
    if ( allowMultiClient ) {
      addMultiClientOption{/literal}{$prefix}{$blockNo}{literal}( prePopulateData, {/literal}{$blockNo},"{$prefix}"{literal} );
    } else {
      addSingleClientOption{/literal}{$prefix}{$blockNo}{literal}( {/literal}{$blockNo},"{$prefix}"{literal} );
    }
  });

  function newContact{/literal}{$prefix}{$blockNo}{literal}( gid, blockNo, prefix ) {
    var dataURL = {/literal}"{crmURL p='civicrm/profile/create' q="reset=1&snippet=5&context=dialog&blockNo=$blockNo&prefix=$prefix" h=0 }"{literal};
    dataURL = dataURL + '&gid=' + gid;
    {/literal}{if $profileCreateCallback}{literal}
    dataURL = dataURL + '&createCallback=1';
    {/literal}{/if}{literal}
    cj.ajax({
      url: dataURL,
      success: function( content ) {
        cj( '#contact-dialog-'+ prefix + blockNo ).show( ).html( content ).dialog({
          title: "{/literal}{ts escape='js'}Create New Contact{/ts}{literal}",
          modal: true,
          width: 680,
          overlay: {
            opacity: 0.5,
            background: "black"
          },

          close: function(event, ui) {
            cj('#' + prefix + 'profiles_' + blockNo).val('');
            {/literal}
            {if $newContactCallback}
              eval("{$newContactCallback}");
            {/if}
            {literal}
          }
        });
      }
    });
  }

  function addMultiClientOption{/literal}{$prefix}{$blockNo}{literal}( prePopulateData, blockNo, prefix ) {
    var hintText = "{/literal}{ts escape='js'}Type in a partial or complete name of an existing contact.{/ts}{literal}";
    var contactUrl = {/literal}"{crmURL p='civicrm/ajax/checkemail' q='id=1&noemail=1' h=0 }"{literal};

    // setdefaults incase of formRule
    {/literal}
    {if $selectedContacts}
      {literal} var prePopulateData = cj.ajax({ url: contactUrl + "&cid={/literal}{$selectedContacts}{literal}", async: false }).responseText;{/literal}
    {/if}
    {literal}

    cj('#' + prefix + 'contact_' + blockNo).tokenInput( contactUrl, { prePopulate:prePopulateData, theme: 'facebook', hintText: hintText });
    cj('ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px');
  }

  function addSingleClientOption{/literal}{$prefix}{$blockNo}{literal}( blockNo, prefix ) {
    var contactUrl = {/literal}"{crmURL p='civicrm/ajax/rest' q='className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=newcontact' h=0 }"{literal};
    {/literal}{if $action eq 2}{literal}
    contactUrl = contactUrl + '&cid=' + {/literal}{$contactId}{literal};
    {/literal}{/if}{literal}

    var contactElement = '#' + prefix + 'contact_' + blockNo;
    var contactHiddenElement = 'input[name="{/literal}{$prefix}{literal}contact_select_id[' + blockNo +']"]';
    cj( contactElement ).autocomplete( contactUrl, {
      selectFirst : false, matchContains: true, minChars: 1
    }).result( function(event, data, formatted) {
      cj( contactHiddenElement ).val(data[1]);
      {/literal}
      {if $newContactCallback}
        eval("{$newContactCallback}");
      {/if}
      {literal}
    }).focus( );

    cj( contactElement ).click( function( ) {
      cj( contactHiddenElement ).val('');
    });

    cj( contactElement ).bind("keypress keyup", function(e) {
      if ( e.keyCode == 13 ) {
        return false;
      }
    });
  }
</script>
{/literal}
{/if}{* end of search if *}

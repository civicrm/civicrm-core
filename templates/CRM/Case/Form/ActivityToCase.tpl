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
{* CiviCase -  build activity to a case*}
<div id="fileOnCaseDialog"></div>

{if !empty($buildCaseActivityForm)}
<div class="crm-block crm-form-block crm-case-activitytocase-form-block">
<table class="form-layout">
     <tr class="crm-case-activitytocase-form-block-unclosed_cases">
      <td class="label">{$form.unclosed_cases.label}</td>
       <td>{$form.unclosed_cases.html}<br />
           <span class="description">{ts}Begin typing client name for a list of open cases.{/ts}</span>
       </td>
     </tr>
     <tr class="crm-case-activitytocase-form-block-target_contact_id">
      <td class="label">{$form.target_contact_id.label}</td>
      <td>{$form.target_contact_id.html}</td>
     </tr>
     <tr class="crm-case-activitytocase-form-block-case_activity_subject">
       <td class="label">{$form.case_activity_subject.label}</td>
      <td>{$form.case_activity_subject.html}<br />
          <span class="description">{ts}You can modify the activity subject before filing.{/ts}</span>
      </td>
     </tr>
</table>
</div>
{literal}
<script type="text/javascript">
var target_contact = '';
var target_contact_id = '';
var selectedCaseId = '';
var contactId = '';

var unclosedCaseUrl = {/literal}"{crmURL p='civicrm/case/ajax/unclosed' h=0 q='excludeCaseIds='}{$currentCaseId}"{literal};
cj( "#unclosed_cases" ).autocomplete( unclosedCaseUrl, { width : 250, selectFirst : false, matchContains:true
                                    }).result( function(event, data, formatted) {
                                cj( "#unclosed_case_id" ).val( data[1] );
                          contactId = data[2];
                          selectedCaseId = data[1];
                                              }).bind( 'click', function( ) {
                                cj( "#unclosed_case_id" ).val('');
              contactId = selectedCaseId = '';
                            });
{/literal}
{if $targetContactValues}
{foreach from=$targetContactValues key=id item=name}
   {literal}
   target_contact += '{"name":"'+{/literal}"{$name}"{literal}+'","id":"'+{/literal}"{$id}"{literal}+'"},';{/literal}
{/foreach}
   {literal}
   eval( 'target_contact = [' + target_contact + ']');
   {/literal}
{/if}

{if $form.target_contact_id.value}
     {literal}
     var toDataUrl = "{/literal}{crmURL p='civicrm/ajax/checkemail' q='id=1&noemail=1' h=0 }{literal}";
     var target_contact_id = cj.ajax({ url: toDataUrl + "&cid={/literal}{$form.$currentElement.value}{literal}", async: false }).responseText;
     {/literal}
{/if}

{literal}
if ( target_contact_id ) {
  eval( 'target_contact = ' + target_contact_id );
}

var tokenDataUrl  = "{/literal}{$tokenUrl}{literal}";
var hintText = "{/literal}{ts escape='js'}Type in a partial or complete name or email address of an existing contact.{/ts}{literal}";
cj( "#target_contact_id" ).tokenInput(tokenDataUrl,{prePopulate: target_contact, theme: 'facebook', hintText: hintText });
cj( 'ul.token-input-list-facebook, div.token-input-dropdown-facebook' ).css( 'width', '450px' );

cj( "#fileOnCaseDialog" ).hide( );

</script>
{/literal}

{/if} {* main form if end *}

{literal}
<script type="text/javascript">
function fileOnCase( action, activityID, currentCaseId ) {
    if ( action == "move" ) {
        dialogTitle = "Move to Case";
    } else if ( action == "copy" ) {
        dialogTitle = "Copy to Case";
    } else if ( action == "file" ) {
        dialogTitle = "File On Case";
    }

    var dataUrl = {/literal}"{crmURL p='civicrm/case/addToCase' q='reset=1&snippet=4' h=0}"{literal};
    dataUrl = dataUrl + '&activityId=' + activityID + '&caseId=' + currentCaseId + '&cid=' + {/literal}"{$contactID}"{literal};

    cj.ajax({
              url     : dataUrl,
        success : function ( content ) {
                   cj("#fileOnCaseDialog").show( ).html( content ).dialog({
                 title       : dialogTitle,
                 modal       : true,
           bgiframe    : true,
                     width       : 600,
                 height      : 270,
           close       : function( event, ui ) { cj( "#unclosed_cases" ).unautocomplete( ); },
                 overlay     : { opacity: 0.5, background: "black" },
                 beforeclose : function( event, ui ) {
                                     cj(this).dialog("destroy");
                                   },
                   open        : function() {  },

        buttons : {
      "Ok": function() {
        var subject         = cj("#case_activity_subject").val( );
        var targetContactId = cj("#target_contact_id").val( );

          if ( !cj("#unclosed_cases").val( )  ) {
             cj("#unclosed_cases").crmError('{/literal}{ts escape="js"}Please select a case from the list{/ts}{literal}.');
           return false;
        }

        cj(this).dialog("destroy");

        var postUrl = {/literal}"{crmURL p='civicrm/ajax/activity/convert' h=0 }"{literal};
              cj.post( postUrl, { activityID: activityID, caseID: selectedCaseId, contactID: contactId, newSubject: subject, targetContactIds: targetContactId, mode: action, key: {/literal}"{crmKey name='civicrm/ajax/activity/convert'}"{literal} },
           function( values ) {
                if ( values.error_msg ) {
                    cj().crmError(values.error_msg, "{/literal}{ts escape='js'}Unable to file on case{/ts}{literal}.");
                 return false;
                          } else {
                    var destUrl = {/literal}"{crmURL p='civicrm/contact/view/case' q='reset=1&action=view&id=' h=0 }"{literal};
                  var context = '';
                  {/literal}{if !empty($fulltext)}{literal}
                    context = '&context={/literal}{$fulltext}{literal}';
                  {/literal}{/if}{literal}
                  var caseUrl = destUrl + selectedCaseId + '&cid=' + contactId + context;
                  var redirectToCase = false;
                  var reloadWindow = false;
                  if ( action == 'move' ) redirectToCase = true;
                  if ( action == 'file' ) {
                     var curPath = document.location.href;
                      if ( curPath.indexOf( 'civicrm/contact/view' ) != -1 ) {
                                    //hide current activity row.
                                    cj( "#crm-activity_" + activityID ).hide( );
                      var visibleRowCount = 0;
                      cj('[id^="'+ 'crm-activity' +'"]::visible').each(function() {
                            visibleRowCount++;
                      } );
                      if ( visibleRowCount < 1 ) {
                         reloadWindow = true;
                      }
                                 }
                                 if ( ( curPath.indexOf( 'civicrm/contact/view/activity' ) != -1 ) ||
                                      ( curPath.indexOf( 'civicrm/activity' ) != -1 ) ) {
                                     redirectToCase = true;
                                 }
                              }

                  if ( redirectToCase ) {
                                 window.location.href = caseUrl;
                  } else if ( reloadWindow ) {
                      window.location.reload( );
                  } else {
                      var activitySubject = cj("#case_activity_subject").val( );
                      var statusMsg = activitySubject + '" has been filed to selected case: <a href="' + caseUrl + '">' + cj("#unclosed_cases").val( ) + '</a>.';
                      CRM.alert(statusMsg, '{/literal}{ts escape="js"}Activity Filed{/ts}{literal}', 'success');

                             }
                   }
                    }
              );
      },

      "Cancel": function() {
        cj(this).dialog("close");
        cj(this).dialog("destroy");
      }
    }

     });
       }
  });
}
</script>
{/literal}

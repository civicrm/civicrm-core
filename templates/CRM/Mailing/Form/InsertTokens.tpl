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

{* Include a modified version of jquery-fieldselection
 * FIXME: we use this plugin for so little it's hard to justify having it at all *}
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery-fieldselection.js"></script>

<script type="text/javascript">
cj('form.{$form.formClass}').data('tokens', {$tokens|@json_encode});
var text_message = null;
var html_message = null;
var prefix = '';
var isPDF        = false;
var isMailing    = false;

{if $form.formName eq 'MessageTemplates'}
  {literal}
  text_message = "msg_text";
  html_message = "msg_html";
  {/literal}
  {elseif $form.formName eq 'Address'}
  {literal}
  text_message = "mailing_format";
  isMailing = false;
  {/literal}
  {elseif $form.formClass eq 'CRM_SMS_Form_Upload' || $form.formClass eq 'CRM_Contact_Form_Task_SMS'}
  {literal}
  prefix = "SMS";
  text_message = "sms_text_message";
  isMailing = true;
  {/literal}
  {else}
  {literal}
  text_message = "text_message";
  html_message = (cj("#edit-html-message-value").length > 0) ? "edit-html-message-value" : "html_message";
  isMailing    = true;
  {/literal}
{/if}

{if $form.formName eq 'PDF'}
  {literal}
  isPDF = true;
  {/literal}
{/if}

{if $templateSelected}
  {literal}
  if ( document.getElementsByName(prefix + "saveTemplate")[0].checked ) {
    document.getElementById(prefix + "template").selectedIndex = {/literal}{$templateSelected}{literal};
  }
{/literal}
{/if}
{literal}

var editor = {/literal}"{$editor}"{literal};
function showSaveUpdateChkBox(prefix) {
  prefix = prefix || '';
  if (document.getElementById(prefix + "template") == null) {
    if (document.getElementsByName(prefix + "saveTemplate")[0].checked){
      document.getElementById(prefix + "saveDetails").style.display = "block";
      document.getElementById(prefix + "editMessageDetails").style.display = "block";
    }
    else {
      document.getElementById(prefix + "saveDetails").style.display = "none";
      document.getElementById(prefix + "updateDetails").style.display = "none";
    }
    return;
  }

  if (document.getElementsByName(prefix + "saveTemplate")[0].checked &&
    document.getElementsByName(prefix + "updateTemplate")[0].checked == false) {
    document.getElementById(prefix + "updateDetails").style.display = "none";
  }
  else if ( document.getElementsByName(prefix + "saveTemplate")[0].checked &&
    document.getElementsByName(prefix + "updateTemplate")[0].checked ){
    document.getElementById(prefix + "editMessageDetails").style.display = "block";
    document.getElementById(pefix + "saveDetails").style.display = "block";
  }
  else if ( document.getElementsByName(prefix + "saveTemplate")[0].checked == false &&
      document.getElementsByName(prefix + "updateTemplate")[0].checked ) {
    document.getElementById(prefix + "saveDetails").style.display = "none";
    document.getElementById(prefix + "editMessageDetails").style.display = "block";
  }
  else {
    document.getElementById(prefix + "saveDetails").style.display = "none";
    document.getElementById(prefix + "updateDetails").style.display = "none";
  }
}

function selectValue( val, prefix) {
  document.getElementsByName(prefix + "saveTemplate")[0].checked = false;
  document.getElementsByName(prefix + "updateTemplate")[0].checked = false;
  showSaveUpdateChkBox(prefix);
  if ( !val ) {
    if (document.getElementById("subject").length) {
      document.getElementById("subject").value ="";
    }
    if ( !isPDF ) {
      if (prefix == 'SMS') {
        document.getElementById("sms_text_message").value ="";
        return;
      }
      else {
        document.getElementById("text_message").value ="";
      }
    }
    if ( editor == "ckeditor" ) {
      oEditor = CKEDITOR.instances[html_message];
      oEditor.setData('');
    }
    else if ( editor == "tinymce" ) {
      tinyMCE.getInstanceById(html_message).setContent( html_body );
    }
    else if ( editor == "joomlaeditor" ) {
      document.getElementById(html_message).value = '' ;
      tinyMCE.execCommand('mceSetContent',false, '');
    }
    else if ( editor =="drupalwysiwyg" ) {
      if (Drupal.wysiwyg.instances[html_message].setContent) {
        Drupal.wysiwyg.instances[html_message].setContent(html_body);
      }
      // @TODO: Remove this when http://drupal.org/node/614146 drops
      else if (Drupal.wysiwyg.instances[html_message].insert) {
        alert("Please note your editor doesn't completely support this function. You may need to clear the contents of the editor prior to choosing a new template.");
        Drupal.wysiwyg.instances[html_message].insert(html_body);
      }
      else {
        alert("Sorry, your editor doesn't support this function yet.");
      }
    }
    else {
      document.getElementById(html_message).value = '' ;
    }
    if ( isPDF ) {
      showBindFormatChkBox();
    }
    return;
  }

  var dataUrl = {/literal}"{crmURL p='civicrm/ajax/template' h=0 }"{literal};

  cj.post( dataUrl, {tid: val}, function( data ) {
    if ( !isPDF ) {
      if (prefix == "SMS") {
          text_message = "sms_text_message";
      }
      else {
        cj("#subject").val( data.subject );
      }
      if ( data.msg_text ) {
        cj("#"+text_message).val( data.msg_text );
        cj("div.text").show();
        cj(".head").find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-s');
        cj("#helptext").show();
      }
      else {
        cj("#"+text_message).val("");
      }
    }

    if (prefix == "SMS") {
      return;
    }
    var html_body  = "";
    if (  data.msg_html ) {
      html_body = data.msg_html;
    }

    if (editor == "ckeditor") {
      oEditor = CKEDITOR.instances[html_message];
      oEditor.setData( html_body );
    }
    else if (editor == "tinymce") {
      tinyMCE.execInstanceCommand('html_message',"mceInsertContent",false, html_body );
    }
    else if (editor == "joomlaeditor") {
      cj("#"+ html_message).val( html_body );
      tinyMCE.execCommand('mceSetContent',false, html_body);
    }
    else if ( editor =="drupalwysiwyg") {
      if (Drupal.wysiwyg.instances[html_message].setContent) {
        Drupal.wysiwyg.instances[html_message].setContent(html_body);
      }
      // @TODO: Remove this when http://drupal.org/node/614146 drops
      else if (Drupal.wysiwyg.instances[html_message].insert) {
        alert("Please note your editor doesn't completely support this function. You may need to clear the contents of the editor prior to choosing a new template.");
        Drupal.wysiwyg.instances[html_message].insert(html_body);
      }
      else {
        alert("Sorry, your editor doesn't support this function yet.");
      }
    }
    else {
      cj("#"+ html_message).val( html_body );
    }
    if (isPDF) {
      var bind = data.pdf_format_id ? true : false ;
      selectFormat( data.pdf_format_id, bind );
      if (!bind) {
        document.getElementById("bindFormat").style.display = "none";
      }
    }
  }, 'json');
}

if ( isMailing ) {
  document.getElementById(prefix + "editMessageDetails").style.display = "block";

  function verify(select, prefix) {
    prefix = prefix || '';
    if (document.getElementsByName(prefix + "saveTemplate")[0].checked  == false) {
      document.getElementById(prefix + "saveDetails").style.display = "none";
    }
    document.getElementById(prefix + "editMessageDetails").style.display = "block";

    var templateExists = true;
    if (document.getElementById(prefix + "template") == null) {
      templateExists = false;
    }

    if (templateExists && document.getElementById(prefix + "template").value) {
      document.getElementById(prefix + "updateDetails").style.display = '';
    }
    else {
      document.getElementById(prefix + "updateDetails").style.display = 'none';
    }

    document.getElementById(prefix + "saveTemplateName").disabled = false;
  }

  function showSaveDetails(chkbox, prefix) {
    prefix = prefix || '';
    if (chkbox.checked) {
      document.getElementById(prefix + "saveDetails").style.display = "block";
      document.getElementById(prefix + "saveTemplateName").disabled = false;
    }
    else {
      document.getElementById(prefix + "saveDetails").style.display = "none";
      document.getElementById(prefix + "saveTemplateName").disabled = true;
    }
  }

  if (cj("#sms_text_message").length) {
    showSaveUpdateChkBox('SMS');
  }
  if (cj("#text_message").length) {
    showSaveUpdateChkBox();
  }

  {/literal}
  {if $editor eq "ckeditor"}
  {literal}
    CRM.$(function($) {
      oEditor = CKEDITOR.instances['html_message'];
      oEditor.BaseHref = '' ;
      oEditor.UserFilesPath = '' ;
      oEditor.on( 'focus', verify );
    });
  {/literal}
  {elseif $editor eq "tinymce"}
  {literal}
    CRM.$(function($) {
      if ( isMailing ) {
        $('div.html').hover(
          function( ) {
            if ( tinyMCE.get(html_message) ) {
              tinyMCE.get(html_message).onKeyUp.add(function() {
                verify( );
              });
            }
          },
          function( ) {
            if ( tinyMCE.get(html_message) ) {
              if ( tinyMCE.get(html_message).getContent() ) {
                verify( );
              }
            }
          }
        );
      }
    });
  {/literal}
  {elseif $editor eq "drupalwysiwyg"}
  {literal}
    CRM.$(function($) {
      if ( isMailing ) {
        $('div.html').hover(
          verify,
          verify
        );
      }
    });
  {/literal}
  {/if}
  {literal}
}

CRM.$(function($) {
  function insertToken() {
    var
      token = $(this).val(),
      field = $(this).data('field');
    if (field === 'html_message') {
      tokenReplHtml(token);
    } else {
      field = textMsgID($(this));
      $('#' + field).replaceSelection(token);
    }
    $(this).select2('val', '');
    if (isMailing) {
      verify();
    }
  }

  function textMsgID(obj) {
    if (obj.parents().is("#sms")) {
      field = 'sms #' + obj.data('field');
    }
    else if(obj.parents().is("#email")) {
      field = 'email #' + obj.data('field');
    }
    else {
      field = obj.data('field');
    }

    return field;
  }

  function tokenReplHtml(token) {
    var editor     = {/literal}"{$editor}"{literal};
    if ( editor == "tinymce" ) {
      tinyMCE.execInstanceCommand('html_message',"mceInsertContent",false, token );
    }
    else if ( editor == "joomlaeditor" ) {
      tinyMCE.execCommand('mceInsertContent',false, token);
      var msg       = document.getElementById(html_message).value;
      var cursorlen = document.getElementById(html_message).selectionStart;
      var textlen   = msg.length;
      document.getElementById(html_message).value = msg.substring(0, cursorlen) + token + msg.substring(cursorlen, textlen);
      var cursorPos = (cursorlen + token.length);
      document.getElementById(html_message).selectionStart = cursorPos;
      document.getElementById(html_message).selectionEnd   = cursorPos;
      document.getElementById(html_message).focus();
    }
    else if ( editor == "ckeditor" ) {
      oEditor = CKEDITOR.instances[html_message];
      oEditor.insertHtml(token.toString() );
    }
    else if ( editor == "drupalwysiwyg" ) {
      if (Drupal.wysiwyg.instances[html_message].insert) {
        Drupal.wysiwyg.instances[html_message].insert(token.toString() );
      }
      else {
        alert("Sorry, your editor doesn't support this function yet.");
      }
    }
    else {
      $( "#"+ html_message ).replaceSelection( token );
    }
  }

  // Initialize token selector widgets
  var form = $('form.{/literal}{$form.formClass}{literal}');
  $('input.crm-token-selector', form)
    .addClass('crm-action-menu')
    .change(insertToken)
    .crmSelect2({
      data: form.data('tokens'),
      placeholder: '{/literal}{ts escape='js'}Insert Token{/ts}{literal}'
    });

  $('.accordion .head').addClass( "ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ");
  $('.resizable-textarea textarea').css( 'width', '99%' );
  $('.grippie').css( 'margin-right', '3px');
  $('.accordion .head').hover( function() { $(this).addClass( "ui-state-hover");
  }, function() { $(this).removeClass( "ui-state-hover");
  }).bind('click', function() {
    var checkClass = $(this).find('span').attr( 'class' );
    var len        = checkClass.length;
    if ( checkClass.substring( len - 1, len ) == 's' ) {
      $(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-e');
      $("span#help"+$(this).find('span').attr('id')).hide();
    }
    else {
      $(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-s');
      $("span#help"+$(this).find('span').attr('id')).show();
    }
    $(this).next().toggle(); return false;
  }).next().hide();
  $('span#html').removeClass().addClass('ui-icon ui-icon-triangle-1-s');
  $("div.html").show();

  if ( !isMailing ) {
    $("div.text").show();
  }

  function setSignature() {
    var emailID = $("#fromEmailAddress").val( );
    if ( !isNaN( emailID ) ) {
      var dataUrl = {/literal}"{crmURL p='civicrm/ajax/signature' h=0 }"{literal};
      $.post( dataUrl, {emailID: emailID}, function( data ) {
        var editor     = {/literal}"{$editor}"{literal};

        if (data.signature_text) {
          // get existing text & html and append signatue
          var textMessage =  $("#"+ text_message).val( ) + '\n\n--\n' + data.signature_text;

          // append signature
          $("#"+ text_message).val( textMessage );
        }

        if ( data.signature_html ) {
          var htmlMessage =  $("#"+ html_message).val( ) + '<br/><br/>--<br/>' + data.signature_html;

          // set wysiwg editor
          if ( editor == "ckeditor" ) {
            oEditor = CKEDITOR.instances[html_message];
            var htmlMessage = oEditor.getData( ) + '<br/><br/>--' + data.signature_html;
            oEditor.setData( htmlMessage  );
          }
          else if ( editor == "tinymce" ) {
            tinyMCE.execInstanceCommand('html_message',"mceInsertContent",false, htmlMessage);
          }
          else if ( editor == "drupalwysiwyg" ) {
            if (Drupal.wysiwyg.instances[html_message].setContent) {
              Drupal.wysiwyg.instances[html_message].setContent(htmlMessage);
            }
            // @TODO: Remove this when http://drupal.org/node/614146 drops
            else if (Drupal.wysiwyg.instances[html_message].insert) {
              alert("Please note your editor doesn't completely support this function. You may need to clear the contents of the editor prior to choosing a new template.");
              Drupal.wysiwyg.instances[html_message].insert(htmlMessage);
            }
            else {
              alert("Sorry, your editor doesn't support this function yet.");
            }
          }
          else {
            $("#"+ html_message).val(htmlMessage);
          }
        }
      }, 'json');
    }
  }
  $("#fromEmailAddress", form).change(setSignature);
});

</script>
{/literal}

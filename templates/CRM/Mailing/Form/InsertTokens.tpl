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

{* Include a modified version of jquery-fieldselection
 * FIXME: we use this plugin for so little it's hard to justify having it at all *}
<script type="text/javascript" src="{$config->resourceBase}packages/jquery/plugins/jquery-fieldselection.js"></script>

<script type="text/javascript">
var text_message = null;
var html_message = null;
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
  if ( document.getElementsByName("saveTemplate")[0].checked ) {
    document.getElementById('template').selectedIndex = {/literal}{$templateSelected}{literal};
  }
{/literal}
{/if}
{literal}

var editor = {/literal}"{$editor}"{literal};
function showSaveUpdateChkBox() {
  if (document.getElementById('template') == null) {
    if (document.getElementsByName("saveTemplate")[0].checked){
      document.getElementById("saveDetails").style.display = "block";
      document.getElementById("editMessageDetails").style.display = "block";
    }
    else {
      document.getElementById("saveDetails").style.display = "none";
      document.getElementById("editMessageDetails").style.display = "none";
    }
    return;
  }

  if (document.getElementsByName("saveTemplate")[0].checked &&
    document.getElementsByName("updateTemplate")[0].checked == false) {
    document.getElementById("updateDetails").style.display = "none";
  }
  else if ( document.getElementsByName("saveTemplate")[0].checked &&
    document.getElementsByName("updateTemplate")[0].checked ){
    document.getElementById("editMessageDetails").style.display = "block";
    document.getElementById("saveDetails").style.display = "block";
  }
  else if ( document.getElementsByName("saveTemplate")[0].checked == false &&
      document.getElementsByName("updateTemplate")[0].checked ) {
    document.getElementById("saveDetails").style.display = "none";
    document.getElementById("editMessageDetails").style.display = "block";
  }
  else {
    document.getElementById("saveDetails").style.display = "none";
    document.getElementById("editMessageDetails").style.display = "none";
  }
}

function selectValue( val ) {
  document.getElementsByName("saveTemplate")[0].checked = false;
  document.getElementsByName("updateTemplate")[0].checked = false;
  showSaveUpdateChkBox();
  if ( !val ) {
    if ( !isPDF ) {
      document.getElementById(text_message).value ="";
      document.getElementById("subject").value ="";
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
      cj("#subject").val( data.subject );

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
  document.getElementById("editMessageDetails").style.display = "block";

  function verify(select) {
    if (document.getElementsByName("saveTemplate")[0].checked  == false) {
      document.getElementById("saveDetails").style.display = "none";
    }
    document.getElementById("editMessageDetails").style.display = "block";

    var templateExists = true;
    if (document.getElementById('template') == null) {
      templateExists = false;
    }

    if (templateExists && document.getElementById('template').value) {
      document.getElementById("updateDetails").style.display = '';
    }
    else {
      document.getElementById("updateDetails").style.display = 'none';
    }

    document.getElementById("saveTemplateName").disabled = false;
  }

  function showSaveDetails(chkbox) {
    if (chkbox.checked) {
      document.getElementById("saveDetails").style.display = "block";
      document.getElementById("saveTemplateName").disabled = false;
    }
    else {
      document.getElementById("saveDetails").style.display = "none";
      document.getElementById("saveTemplateName").disabled = true;
    }
  }

  showSaveUpdateChkBox();

  {/literal}
  {if $editor eq "ckeditor"}
  {literal}
    cj( function() {
      oEditor = CKEDITOR.instances['html_message'];
      oEditor.BaseHref = '' ;
      oEditor.UserFilesPath = '' ;
      oEditor.on( 'focus', verify );
    });
  {/literal}
  {elseif $editor eq "tinymce"}
  {literal}
    cj( function( ) {
      if ( isMailing ) {
        cj('div.html').hover(
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
    cj( function( ) {
      if ( isMailing ) {
        cj('div.html').hover(
          verify,
          verify
        );
      }
    });
  {/literal}
  {/if}
  {literal}
}

function tokenReplText(element) {
  var token     = cj("#"+element.id).val( )[0];
  if ( element.id == 'token3' ) {
    ( isMailing ) ? text_message = "subject" : text_message = "msg_subject";
  }

  cj( "#"+ text_message ).replaceSelection( token );

  if ( isMailing ) {
    verify();
  }
}

function tokenReplHtml() {
  var token2     = cj("#token2").val( )[0];
  var editor     = {/literal}"{$editor}"{literal};
  if ( editor == "tinymce" ) {
    tinyMCE.execInstanceCommand('html_message',"mceInsertContent",false, token2 );
  }
  else if ( editor == "joomlaeditor" ) {
    tinyMCE.execCommand('mceInsertContent',false, token2);
    var msg       = document.getElementById(html_message).value;
    var cursorlen = document.getElementById(html_message).selectionStart;
    var textlen   = msg.length;
    document.getElementById(html_message).value = msg.substring(0, cursorlen) + token2 + msg.substring(cursorlen, textlen);
    var cursorPos = (cursorlen + token2.length);
    document.getElementById(html_message).selectionStart = cursorPos;
    document.getElementById(html_message).selectionEnd   = cursorPos;
    document.getElementById(html_message).focus();
  }
  else if ( editor == "ckeditor" ) {
    oEditor = CKEDITOR.instances[html_message];
    oEditor.insertHtml(token2.toString() );
  }
  else if ( editor == "drupalwysiwyg" ) {
    if (Drupal.wysiwyg.instances[html_message].insert) {
      Drupal.wysiwyg.instances[html_message].insert(token2.toString() );
    }
    else {
      alert("Sorry, your editor doesn't support this function yet.");
    }
  }
  else {
    cj( "#"+ html_message ).replaceSelection( token2 );
  }

  if ( isMailing ) {
    verify();
  }
}

cj(function() {
  cj('.accordion .head').addClass( "ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ");
  cj('.resizable-textarea textarea').css( 'width', '99%' );
  cj('.grippie').css( 'margin-right', '3px');
  cj('.accordion .head').hover( function() { cj(this).addClass( "ui-state-hover");
  }, function() { cj(this).removeClass( "ui-state-hover");
  }).bind('click', function() {
    var checkClass = cj(this).find('span').attr( 'class' );
    var len        = checkClass.length;
    if ( checkClass.substring( len - 1, len ) == 's' ) {
      cj(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-e');
      cj("span#help"+cj(this).find('span').attr('id')).hide();
    }
    else {
      cj(this).find('span').removeClass().addClass('ui-icon ui-icon-triangle-1-s');
      cj("span#help"+cj(this).find('span').attr('id')).show();
    }
    cj(this).next().toggle(); return false;
  }).next().hide();
  cj('span#html').removeClass().addClass('ui-icon ui-icon-triangle-1-s');
  cj("div.html").show();

  if ( !isMailing ) {
    cj("div.text").show();
  }
});

{/literal}{include file="CRM/common/Filter.tpl"}{literal}
function showToken(element, id ) {
  initFilter(id);
  cj("#token"+id).css({"width":"290px", "size":"8"});
  var tokenTitle = {/literal}'{ts escape="js"}Select Token{/ts}'{literal};
  cj("#token"+element ).show( ).dialog({
    title       : tokenTitle,
    modal       : true,
    width       : '310px',
    resizable   : false,
    bgiframe    : false,
    overlay     : { opacity: 0.5, background: "black" },
    beforeclose : function(event, ui) { cj(this).dialog("destroy"); },
    buttons     : {
      "Done": function() {
        cj(this).dialog("close");
        //focus on editor/textarea after token selection
        if (element == 'Text') {
          cj('#' + text_message).focus();
        }
        else if (element == 'Html' ) {
          switch ({/literal}"{$editor}"{literal}) {
            case 'ckeditor': { oEditor = CKEDITOR.instances[html_message]; oEditor.focus(); break;}
            case 'tinymce'  : { tinyMCE.get(html_message).focus(); break; }
            case 'joomlaeditor' : { tinyMCE.get(html_message).focus(); break; }
            default         : { cj("#"+ html_message).focus(); break; }
          }
        }
        else if (element == 'Subject') {
          var subject = null;
          ( isMailing ) ? subject = "subject" : subject = "msg_subject";
          cj('#'+subject).focus();
        }
      }
    }
  });
  return false;
}

cj(function() {
  if (!cj().find('div.crm-error').text()) {
    cj(window).load(function () {
      setSignature();
    });
  }

  cj("#fromEmailAddress").change( function( ) {
    setSignature( );
  });
});

function setSignature( ) {
  var emailID = cj("#fromEmailAddress").val( );
  if ( !isNaN( emailID ) ) {
    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/signature' h=0 }"{literal};
    cj.post( dataUrl, {emailID: emailID}, function( data ) {
      var editor     = {/literal}"{$editor}"{literal};

      if (data.signature_text) {
        // get existing text & html and append signatue
        var textMessage =  cj("#"+ text_message).val( ) + '\n\n--\n' + data.signature_text;

        // append signature
        cj("#"+ text_message).val( textMessage );
      }

      if ( data.signature_html ) {
        var htmlMessage =  cj("#"+ html_message).val( ) + '<br/><br/>--<br/>' + data.signature_html;

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
          cj("#"+ html_message).val(htmlMessage);
        }
      }
    }, 'json');
  }
}
</script>
{/literal}

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
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
{elseif $form.formClass eq 'CRM_Contact_Form_Task_SMS'}
  {literal}
    prefix = "SMS";
    text_message = "sms_text_message";
    isMailing = true;
  {/literal}
{elseif $form.formClass eq 'CRM_SMS_Form_Upload'}
  {literal}
  prefix = "SMS";
  text_message = "sms_text_message";
  isMailing = true;
  {/literal}
  {if $templateSelected}
    {literal}
      if ( document.getElementsByName(prefix + "saveTemplate")[0].checked ) {
        document.getElementById(prefix + "template").selectedIndex = {/literal}{$templateSelected}{literal};
      }
    {/literal}
  {/if}
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

{literal}

/**
 * Checks if both the Save Template and Update Template fields exist.
 * These fields will not exist if user does not have the edit message
 * templates permission.
 *
 * @param {String} prefix
 */
function manageTemplateFieldsExists(prefix) {
  var saveTemplate = document.getElementsByName(prefix + "saveTemplate");
  var updateTemplate = document.getElementsByName(prefix + "updateTemplate");

  return saveTemplate.length > 0 && updateTemplate.length > 0;
}

function showSaveUpdateChkBox(prefix) {
  prefix = prefix || '';
  if (!manageTemplateFieldsExists(prefix)) {
    document.getElementById(prefix + "saveDetails").style.display = "none";
    return;
  }

  if (cj('#' + prefix + "template").val() === '') {
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
    document.getElementById(prefix + "saveDetails").style.display = "block";
  }
  else if ( document.getElementsByName(prefix + "saveTemplate")[0].checked == false &&
      document.getElementsByName(prefix + "updateTemplate")[0].checked ) {
    document.getElementById(prefix + "saveDetails").style.display = "none";
    document.getElementById(prefix + "editMessageDetails").style.display = "block";
  }
  else {
    document.getElementById(prefix + "saveDetails").style.display = "none";
    if (cj('#' + prefix + "template").val() === '') {
      document.getElementById(prefix + "updateDetails").style.display = "none";
    }
    else {
      document.getElementById(prefix + "updateDetails").style.display = "block";
    }
  }
}

function selectValue( val, prefix) {
  if (manageTemplateFieldsExists(prefix)) {
    document.getElementsByName(prefix + "saveTemplate")[0].checked = false;
    document.getElementsByName(prefix + "updateTemplate")[0].checked = false;
    showSaveUpdateChkBox(prefix);
  }
  if ( !val ) {
    // The SMS form has activity_subject not subject which is not
    // cleared by this as subject is not present. It's unclear if that
    // is deliberate or not but this does not error if the field is not present.
    cj('#subject').val('');
    if (prefix === 'SMS') {
      document.getElementById("sms_text_message").value ="";
      return;
    }

    if ( !isPDF ) {
      document.getElementById("text_message").value ="";
    }
    else {
      cj('.crm-html_email-accordion').show();
      cj('.crm-document-accordion').hide();
      cj('#document_type').closest('tr').show();
    }

    CRM.wysiwyg.setVal('#' + html_message, '');
    if ( isPDF ) {
      showBindFormatChkBox();
    }
    return;
  }

  var dataUrl = {/literal}"{crmURL p='civicrm/ajax/template' h=0}"{literal};

  cj.post( dataUrl, {tid: val}, function( data ) {
    var hide = (data.document_body && isPDF) ? false : true;
    cj('.crm-html_email-accordion, .crm-pdf-format-accordion').toggle(hide);
    cj('.crm-document-accordion').toggle(!hide);

    cj('#document_type').closest('tr').toggle(hide);

    // Unset any uploaded document when any template is chosen
    if (cj('#document.file').length) {
      cj('#document_file').val('');
    }

    if (!hide) {
      cj("#subject").val( data.subject );
      cj("#document-preview").html(data.document_body).parent().css({'background': 'white'});
      return;
    }

    if ( !isPDF ) {
      if (prefix == "SMS") {
          text_message = "sms_text_message";
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
    else {
      cj("#subject").val( data.subject );
    }

    CRM.wysiwyg.setVal('#' + html_message, data.msg_html || '');

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
    if (!manageTemplateFieldsExists(prefix)) {
      return;
    }
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

  cj('#' + html_message).on('focus change', verify);
}

CRM.$(function($) {
  function insertToken() {
    var
      token = $(this).val(),
      field = $(this).data('field');
    if (field.indexOf('html') < 0) {
      field = textMsgID($(this));
    }
    CRM.wysiwyg.insert('#' + field, token);
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

  // Initialize token selector widgets
  var form = $('form.{/literal}{$form.formClass}{literal}');
  $('input.crm-token-selector', form)
    .addClass('crm-action-menu fa-code')
    .change(insertToken)
    .crmSelect2({
      data: form.data('tokens'),
      placeholder: '{/literal}{ts escape='js'}Tokens{/ts}{literal}'
    });

  $('.accordion .head').addClass( "ui-accordion-header ui-helper-reset ui-state-default ui-corner-all ")
    .hover( function() { $(this).addClass( "ui-state-hover");
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
      var dataUrl = {/literal}"{crmURL p='civicrm/ajax/signature' h=0}"{literal};
      $.post( dataUrl, {emailID: emailID}, function( data ) {

        if (data.signature_text) {
          var textMessage =  $("#"+ text_message).val( ) + '\n\n--\n' + data.signature_text;
          $("#"+ text_message).val( textMessage );
        }

        if (data.signature_html) {
          var htmlMessage = CRM.wysiwyg.getVal("#" + html_message) + '<br/><br/>--<br/>' + data.signature_html;
          CRM.wysiwyg.setVal("#" + html_message, htmlMessage);
        }
      }, 'json');
    }
  }
  $("#fromEmailAddress", form).change(setSignature);
});

</script>
{/literal}

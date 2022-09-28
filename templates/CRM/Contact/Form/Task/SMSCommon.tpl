{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*common template for compose sms*}

<div class="crm-accordion-wrapper crm-plaint_text_sms-accordion ">
<div class="crm-accordion-header">
  {$form.sms_text_message.label}
  </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
 <div><span id="char-count-message"></span> <span id="char-count-help">{help id="id-count-text" tplFile=$tplFile file="CRM/Contact/Form/Task/SMS.hlp"}</span></div>
   <div class="helpIcon" id="helptext">
     <input class="crm-token-selector big" data-field="sms_text_message" />
     {help id="id-token-text" tplFile=$tplFile file="CRM/Contact/Form/Task/SMS.hlp"}
   </div>
    <div class='text'>
  {$form.sms_text_message.html}<br />
    </div>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
<div id="SMSeditMessageDetails" class="section">
    <div id="SMSupdateDetails" class="section" >
  {$form.SMSupdateTemplate.html}&nbsp;{$form.SMSupdateTemplate.label}
    </div>
    <div class="section">
  {$form.SMSsaveTemplate.html}&nbsp;{$form.SMSsaveTemplate.label}
    </div>
</div>

<div id="SMSsaveDetails" class="section">
   <div class="label">{$form.SMSsaveTemplateName.label}</div>
   <div class="content">{$form.SMSsaveTemplateName.html|crmAddClass:huge}</div>
</div>

{capture assign="char_count_message"}
{ts}You can insert up to %1 characters. You have entered %2 characters, requiring %3 segments.{/ts}
{/capture}

{literal}
<script type="text/javascript">
{/literal}{if $max_sms_length}{literal}
CRM.loadScript(CRM.config.resourceBase + 'bower_components/sms-counter/sms_counter.min.js').done(function () {
  maxCharInfoDisplay();

  CRM.$('#sms_text_message').bind({
    change: function() {
    maxLengthMessage();
    },
    keyup:  function() {
    maxCharInfoDisplay();
    }
  });

  function maxLengthMessage()
  {
    var len = CRM.$('#sms_text_message').val().length;
    var maxLength = {/literal}{$max_sms_length}{literal};
    if (len > maxLength) {
        CRM.$('#sms_text_message').crmError({/literal}'{ts escape="js" 1=$max_sms_length}SMS body exceeding limit of %1 characters{/ts}'{literal});
        return false;
    }
  return true;
  }

  function maxCharInfoDisplay(){
    var maxLength = {/literal}{$max_sms_length}{literal};
    var enteredText = SmsCounter.count(CRM.$('#sms_text_message').val());
    var count = enteredText.length;
    var segments = enteredText.messages;

    if( count < 0 ) {
        CRM.$('#sms_text_message').val(CRM.$('#sms_text_message').val().substring(0, maxLength));
        count = 0;
    }
    var message = "{/literal}{$char_count_message}{literal}"
    CRM.$('#char-count-message').text(message.replace('%1', maxLength).replace('%2', count).replace('%3', segments));
  }
});
{/literal}{/if}{literal}

</script>
{/literal}

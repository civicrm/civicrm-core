{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
{*common template for compose sms*}

<div class="crm-accordion-wrapper crm-plaint_text_sms-accordion ">
<div class="crm-accordion-header">
  {$form.sms_text_message.label}
  </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
 <div id='char-count-message'></div>
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

{literal}
<script type="text/javascript">

{/literal}{if $max_sms_length}{literal}
maxCharInfoDisplay();

cj('#sms_text_message').bind({
  change: function() {
   maxLengthMessage();
  },
  keyup:  function() {
   maxCharInfoDisplay();
  }
});

function maxLengthMessage()
{
   var len = cj('#sms_text_message').val().length;
   var maxLength = {/literal}{$max_sms_length}{literal};
   if (len > maxLength) {
      cj('#sms_text_message').crmError({/literal}'{ts escape="js"}SMS body exceeding limit of 160 characters{/ts}'{literal});
      return false;
   }
return true;
}

function maxCharInfoDisplay(){
   var maxLength = {/literal}{$max_sms_length}{literal};
   var enteredCharLength = cj('#sms_text_message').val().length;
   var count = enteredCharLength;

   if( count < 0 ) {
      cj('#sms_text_message').val(cj('#sms_text_message').val().substring(0, maxLength));
      count = 0;
   }
   cj('#char-count-message').text( "You can insert up to " + maxLength + " characters. You have entered " + count + " characters." );
}
{/literal}{/if}{literal}

</script>
{/literal}

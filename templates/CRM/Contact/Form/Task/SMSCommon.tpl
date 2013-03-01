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
{*common template for compose sms*}

<div class="crm-accordion-wrapper crm-plaint_text_sms-accordion ">
<div class="crm-accordion-header">
  {ts}Plain-Text Format{/ts}
  </div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
 <div id='char-count-message'></div>
 <span class="helpIcon" id="helptext">
  <a href="#" onClick="return showToken('Text', 1);">{$form.token1.label}</a>
  {help id="id-token-text" file="CRM/Contact/Form/Task/SMS.hlp"}
  <div id='tokenText' style="display:none">
      <input  style="border:1px solid #999999;" type="text" id="filter1" size="20" name="filter1" onkeyup="filter(this, 1)"/><br />
      <span class="description">{ts}Begin typing to filter list of tokens{/ts}</span><br/>
      {$form.token1.html}
  </div>
    </span>
    <div class="clear"></div>
    <div class='text'>
  {$form.text_message.html}<br />
    </div>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
<div id="editMessageDetails" class="section">
    <div id="updateDetails" class="section" >
  {$form.updateTemplate.html}&nbsp;{$form.updateTemplate.label}
    </div>
    <div class="section">
  {$form.saveTemplate.html}&nbsp;{$form.saveTemplate.label}
    </div>
</div>

<div id="saveDetails" class="section">
   <div class="label">{$form.saveTemplateName.label}</div>
   <div class="content">{$form.saveTemplateName.html|crmAddClass:huge}</div>
</div>

{include file="CRM/Mailing/Form/InsertTokens.tpl"}
{literal}
<script type="text/javascript">
cj(function() {
   cj().crmAccordions();
});

{/literal}{if $max_sms_length}{literal}
maxCharInfoDisplay();

cj('#text_message').bind({
  change: function() {
   maxLengthMessage();
  },
  keyup:  function() {
   maxCharInfoDisplay();
  }
});

function maxLengthMessage()
{
   var len = cj('#text_message').val().length;
   var maxLength = {/literal}{$max_sms_length}{literal};
   if (len > maxLength) {
      cj('#text_message').crmError({/literal}'{ts escape="js"}SMS body exceeding limit of 160 characters{/ts}'{literal});
      return false;
   }
return true;
}

function maxCharInfoDisplay(){
   var maxLength = {/literal}{$max_sms_length}{literal};
   var enteredCharLength = cj('#text_message').val().length;
   var count = maxLength - enteredCharLength;

   if( count < 0 ) {
      cj('#text_message').val(cj('#text_message').val().substring(0, maxLength));
      count = 0;
   }
   cj('#char-count-message').text( "You can insert upto " + count + " characters" );
}
{/literal}{/if}{literal}

</script>
{/literal}

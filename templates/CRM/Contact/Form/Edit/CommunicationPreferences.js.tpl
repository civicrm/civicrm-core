{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var $form = $('form.{/literal}{$form.formClass}{literal}');
      function triggerCustomValueCommsFields() {
        var fldName = $(this).attr('id');
        if ($(this).val() == 4) {
          $("#greetings1, #greetings2", $form).show();
          $("#" + fldName + "_html, #" + fldName + "_label", $form).show();
        } else {
          $("#" + fldName + "_html, #" + fldName + "_label", $form).hide();
          $("#" + fldName.slice(0, -3) + "_custom", $form).val('');
        }
      }
      $('#postal_greeting_id, #addressee_id, #email_greeting_id', $form)
        .each(triggerCustomValueCommsFields)
        .on('change', triggerCustomValueCommsFields);

      $('.replace-plain[data-id]', $form).click(function() {
        var element = $(this).data('id');
        $(this).hide();
        $('#' + element, $form).show();
        var fldName = '#' + element + '_id';
        if ($(fldName, $form).val() == 4) {
          $("#greetings1, #greetings2", $form).show();
          $(fldName + "_html, " + fldName + "_label", $form).show();
        }
      });
    });
  </script>
{/literal}

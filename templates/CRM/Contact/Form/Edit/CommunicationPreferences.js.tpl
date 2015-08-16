{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var $form = $('form.{/literal}{$form.formClass}{literal}');
      $('#postal_greeting_id, #addressee_id, #email_greeting_id', $form).change(function() {
        var fldName = $(this).attr('id');
        if ($(this).val() == 4) {
          $("#greetings1, #greetings2", $form).show();
          $("#" + fldName + "_html, #" + fldName + "_label", $form).show();
        } else {
          $("#" + fldName + "_html, #" + fldName + "_label", $form).hide();
          $("#" + fldName.slice(0, -3) + "_custom", $form).val('');
        }
      });
      
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

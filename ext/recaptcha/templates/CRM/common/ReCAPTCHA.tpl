{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $recaptchaHTML}
  <div class="crm-section recaptcha-section" style="display:none">
    <table class="form-layout-compressed">
      <tr>
        <td class="recaptcha_label">&nbsp;</td>
        <td>{$recaptchaHTML}</td>
      </tr>
    </table>
  </div>
{literal}
  <script type="text/javascript">
  (function($) {
    document.addEventListener('DOMContentLoaded', function() {
      var submitButtons = $('div.crm-submit-buttons').last();
      var recaptchaSection = $('div.recaptcha-section');
      submitButtons.before(recaptchaSection);
      recaptchaSection.show();
    });
  }(CRM.$));
  </script>
{/literal}
{/if}

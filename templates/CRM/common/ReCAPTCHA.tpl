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
{literal}
<script type="text/javascript">
var RecaptchaOptions = {{/literal}{$recaptchaOptions}{literal}};
</script>
{/literal}
<div class="crm-section recaptcha-section">
    <table class="form-layout-compressed">
        <tr>
          <td class="recaptcha_label">&nbsp;</td>
          <td>{$recaptchaHTML}</td>
       </tr>
    </table>
</div>
{/if}

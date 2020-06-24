{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="form-item">
<fieldset>
<legend>{ts}Sent Email Message{/ts}</legend>
<dl>
<dt>{ts}Date Sent{/ts}</dt><dd>{$sentDate|crmDate}</dd>
<dt>{ts}From{/ts}</dt><dd>{if $fromName}{$fromName|escape}{else}{ts}(display name not available){/ts}{/if}</dd>
<dt>{ts}To{/ts}</dt><dd>{$toName|escape}</dd>
<dt>{ts}Subject{/ts}</dt><dd>{$subject}</dd>
<dt>{ts}Message{/ts}</dt><dd>{$message}</dd>
<dt>&nbsp;</dt><dd>{crmButton class="cancel" icon="times" p='civicrm/contact/view' q="history=1&show=1&selectedChild=activity"}">{ts}Done{/ts}{/crmButton}</dd>
</dl>
</fieldset>
</div>

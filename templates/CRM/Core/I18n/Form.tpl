{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<fieldset>
  <dl>
    {foreach from=$locales item=locale}
      {assign var='elem' value="$field $locale"|replace:' ':'_'}
      <dt>{$form.$elem.label}</dt><dd>{$form.$elem.html}</dd>
    {/foreach}
  </dl>
</fieldset>
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

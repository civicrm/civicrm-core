{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $langSwitch|@count > 1}
  <form action="#">
    <select name="lcMessages" onchange="window.location='{crmURL q="$queryString"}'+this.value">
      {foreach from=$langSwitch item=language key=locale}
        <option value="{$locale}" {if $locale == $tsLocale}selected="selected"{/if}>{$language}</option>
      {/foreach}
    </select>
  </form>
{/if}

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div id='crm-mail' class="crm-container">
{foreach from=$shortCuts item=short}
  <a href="{$short.url}">{$short.title}</a><br />
{/foreach}
</div>

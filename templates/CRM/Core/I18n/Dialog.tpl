{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $config->languageLimit && $config->languageLimit|@count >= 2 and $translatePermission}
  <a href="{crmURL p='civicrm/i18n' q="reset=1&table=$table&field=$field&id=$id"}" data-field="{$field}" class="crm-hover-button crm-multilingual-edit-button" title="{ts}Languages{/ts}">
    <i class="crm-i fa-language fa-lg" aria-hidden="true"></i>
  </a>
{/if}

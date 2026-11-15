{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Show link to Tell a Friend (CRM-2153) *}
<div id="tell-a-friend" class="crm-section friend_link-section">
  <a href="{$friendURL}" title="{$friendText|escape:'html'}" class="button"><span><i class="crm-i fa-chevron-right" role="img" aria-hidden="true"></i> {$friendText}</span></a>
</div>{if !$linkText}<br /><br />{/if}

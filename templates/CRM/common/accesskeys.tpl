{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if empty($urlIsPublic)}
  <div class="footer" id="access">
    {capture assign='accessKeysHelpTitle'}{ts}Access Keys{/ts}{/capture}
    {ts}Access Keys:{/ts}
    {help id='accesskeys' file='CRM/common/accesskeys' title=$accessKeysHelpTitle}
  </div>
{/if}

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $validCiviMember}
    <div id="memberForm" class="form-item">
    <table class="form-layout">
          {include file="CRM/Member/Form/Search/Common.tpl"}
    </table>
    </div>
{/if}

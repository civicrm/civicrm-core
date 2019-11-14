{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Check for Status message for the page (stored in session->getStatus). Status is cleared on retrieval. *}

{if $session->getStatus(false)}
  {assign var="status" value=$session->getStatus(true)}
  {foreach name=statLoop item=statItem from=$status}
    {if $urlIsPublic}
      {assign var="infoType" value="no-popup"}
    {else}
      {assign var="infoType" value=$statItem.type}
    {/if}
    {include file="CRM/common/info.tpl" infoTitle=$statItem.title infoMessage=$statItem.text infoOptions=$statItem.options|@json_encode}
  {/foreach}
{/if}

{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="contact-summary-image"}
  <div class="crm-contact_image crm-contact_image-block">
    {$imageURL}
  </div>
  {if $action eq 0 or $action neq 1 and $deleteURL}
    <div class='crm-contact_image-block crm-contact_image crm-contact_image-delete'>{$deleteURL}</div>
  {/if}
{/crmRegion}

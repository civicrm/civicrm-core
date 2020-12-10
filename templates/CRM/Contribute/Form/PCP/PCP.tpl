{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 8}
  {include file="CRM/Contribute/Form/PCP/Delete.tpl"}
{else}
    <div id="pcp" class="crm-block crm-form-block crm-pcp-search-form-block">
        <h3>{ts}Find Campaign Pages{/ts}</h3>
        <table class="form-layout-compressed">
      <tr>
        <td>{$form.status_id.label}<br />{$form.status_id.html}</td>
        <td>{$form.contibution_page_id.label}<br />{$form.contibution_page_id.html}</td>
        <td>{$form.event_id.label}<br />{$form.event_id.html}</td>
      </tr>
        </table>
        <div class="crm-submit-buttons">{$form.buttons.html}</div>
    </div>
{/if}

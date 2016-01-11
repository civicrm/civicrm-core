{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<table class="form-layout">
  <tr>
    <td><label>{ts}Complete OR Partial Name{/ts}</label><br />
      {$form.sort_name.html}
    </td>
    <td>
      <label>{ts}Complete OR Partial Email{/ts}</label><br />
      {$form.email.html}
    </td>
    {if $form.contact_type}
      <td><label>{ts}Contact Type(s){/ts}</label><br />
        {$form.contact_type.html}
      </td>
    {else}
      <td>&nbsp;</td>
    {/if}
  </tr>
  <tr>
  {if $form.group}
    <td>
      <div id='groupselect'><label>{ts}Group(s){/ts} <span class="description">(<a href="#" id='searchbygrouptype'>{ts}search by group type{/ts}</a>)</span></label>
        <br />
        {$form.group.html}
    </div>
    <div id='grouptypeselect'>
      <label>{ts}Group Type(s){/ts} <span class="description"> (<a href="#" id='searchbygroup'>{ts}search by group{/ts}</a>)</span></label>
      <br />
      {$form.group_type.html}
        {literal}
        <script type="text/javascript">
        CRM.$(function($) {
          function showGroupSearch() {
            $('#grouptypeselect').hide();
            $('#groupselect').show();
            $('#group_type').select2('val', '');
            return false;
          }
          function showGroupTypeSearch() {
            $('#groupselect').hide();
            $('#grouptypeselect').show();
            $('#group').select2('val', '');
            return false;
          }
          $('#searchbygrouptype').click(showGroupTypeSearch);
          $('#searchbygroup').click(showGroupSearch);

          if ($('#group_type').val() ) {
            showGroupTypeSearch();
          }
          else {
            showGroupSearch();
          }

        });
        </script>
        {/literal}
    </div>
    </td>
  {else}
    <td>&nbsp;</td>
  {/if}
    {if $form.contact_tags}
      <td><label>{ts}Select Tag(s){/ts}</label>
        {$form.contact_tags.html}
      </td>
    {else}
      <td>&nbsp;</td>
    {/if}
    {if $isTagset}
      <td colspan="2">{include file="CRM/common/Tagset.tpl"}</td>
    {/if}
    <td>{$form.tag_search.label}  {help id="id-all-tags"}<br />{$form.tag_search.html}</td>
    {if ! $isTagset}
      <td colspan="2">&nbsp;</td>
    {/if}
    <td>&nbsp;</td>
  </tr>
  {if $form.all_tag_types}
    <tr>
      <td colspan="5">
          {$form.all_tag_types.html} {$form.all_tag_types.label} {help id="id-all-tag-types"}
      </td>
    </tr>
  {/if}
  <tr>
    <td>
      <div>
        {$form.phone_numeric.label}<br />{$form.phone_numeric.html}
      </div>
      <div class="description font-italic">
        {ts}Punctuation and spaces are ignored.{/ts}
      </div>
    </td>
    <td>{$form.phone_location_type_id.label}<br />{$form.phone_location_type_id.html}</td>
    <td>{$form.phone_phone_type_id.label}<br />{$form.phone_phone_type_id.html}</td>
  </tr>
  <tr>
    <td colspan="2">
      <table class="form-layout-compressed">
      <tr>
        <td colspan="2">
            {$form.privacy_toggle.html} {help id="id-privacy"}
        </td>
      </tr>
      <tr>
        <td>
            {$form.privacy_options.html}
        </td>
        <td style="vertical-align:middle">
            <div id="privacy-operator-wrapper">{$form.privacy_operator.html} {help id="privacy-operator"}</div>
        </td>
      </tr>
      </table>
      {literal}
        <script type="text/javascript">
          cj("select#privacy_options").change(function() {
            if (cj(this).val() && cj(this).val().length > 1) {
              cj('#privacy-operator-wrapper').show();
            } else {
              cj('#privacy-operator-wrapper').hide();
            }
          }).change();
        </script>
      {/literal}
    </td>
    <td colspan="3">
      {$form.preferred_communication_method.label}<br />
      {$form.preferred_communication_method.html}<br />
      <div class="spacer"></div>
      {$form.email_on_hold.html} {$form.email_on_hold.label}
    </td>
  </tr>
  <tr>
    <td>
      {$form.contact_source.label} {help id="id-source" file="CRM/Contact/Form/Contact"}<br />
      {$form.contact_source.html}
    </td>
    <td>
      {$form.job_title.label}<br />
      {$form.job_title.html}
    </td>
    <td colspan="3">
      {$form.preferred_language.label}<br />
      {$form.preferred_language.html}
    </td>
  </tr>
  <tr>
    <td>
       {$form.contact_id.label} {help id="id-internal-id" file="CRM/Contact/Form/Contact"}<br />
       {$form.contact_id.html}
    </td>
    <td>
       {$form.external_identifier.label} {help id="id-external-id" file="CRM/Contact/Form/Contact"}<br />
       {$form.external_identifier.html}
    </td>
    <td>
      {if $form.uf_user}
        {$form.uf_user.label} {$form.uf_user.html}
        <div class="description font-italic">
          {ts 1=$config->userFramework}Does the contact have a %1 Account?{/ts}
        </div>
      {else}
        &nbsp;
      {/if}
    </td>
  </tr>
</table>

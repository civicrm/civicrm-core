{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="contribute-form-contributionpage-widget-main"}
<h3>{ts}Configure Widget{/ts}</h3>
{if $showStatus}
<div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}It looks like you may have posted and / or distributed the flash version of the Contribution widget. We won't be supporting the flash version in next release. You should try and get all sites using the flash widget to update to the improved HTML widget code below as soon as possible.{/ts}
</div>
{/if}
<div id="form" class="crm-block crm-form-block crm-contribution-contributionpage-widget-form-block">
    <div class="help">
        {ts}CiviContribute widgets allow you and your supporters to easily promote this fund-raising campaign. Widget code can be added to any web page.  It will provide a real-time display of current contribution results and a direct link to this contribution page.{/ts} {help id="id-intro"}
    </div>
    <table class="form-layout-compressed">
      <tr class="crm-contribution-contributionpage-widget-form-block-is_active"><td style="width: 12em;">&nbsp;</td><td style="font-size: 10pt;">{$form.is_active.html}&nbsp;{$form.is_active.label}</td></tr>
    </table>
    <div class="spacer"></div>

    <div id="widgetFields">
        <table class="form-layout-compressed">
            <tr class="crm-contribution-contributionpage-widget-form-block-title">
                <td class="label">{$form.title.label}<span class="crm-marker"> *</span></td>
                <td>{$form.title.html}</td>
            </tr>
            <tr class="crm-contribution-form-block-url_logo">
                <td class="label">{$form.url_logo.label}</span></td>
                <td>{$form.url_logo.html}</td>
            </tr>
            <tr class="crm-contribution-contributionpage-widget-form-block-button_title">
                <td class="label">{$form.button_title.label}</td>
                <td>{$form.button_title.html}</td>
            </tr>
            <tr class="crm-contribution-contributionpage-widget-form-block-about">
                <td class="label">{$form.about.label}<span class="crm-marker"> *</span></td>
                <td>{$form.about.html}</td>
            </tr>
        </table>

        <div id="id-get_code">
            <fieldset>
            <legend>{ts}Preview Widget and Get Code{/ts}</legend>
            <div class="col1">
                {if $widget_id}
                    <div class="description">
                        {ts}Click <strong>Save & Preview</strong> to save any changes to your settings, and preview the widget again on this page.{/ts}
                    </div>
                    {include file="CRM/Contribute/Page/Widget.tpl" widgetId=$widget_id cpageId=$cpageId}<br />
                {else}
                    <div class="description">
                        {ts}Click <strong>Save & Preview</strong> to save your settings and preview the widget on this page.{/ts}<br />
                    </div>
                {/if}
                <div>{$form._qf_Widget_refresh.html}</div>
            </div>
            <div class="col2">
                {* Include "get widget code" section if widget has been created for this page and is_active. *}
                {if $widget_id}
                    <div class="description">
                        {ts}Add this widget to any web page by copying and pasting the code below.{/ts}
                    </div>
                    <textarea rows="8" cols="50" name="widget_code" id="widget_code">{include file="CRM/Contribute/Page/Widget.tpl" widgetId=$widget_id cpageId=$cpageId}</textarea>
                    <br />
                    <strong><a href="#" onclick="Widget.widget_code.select(); return false;"><i class="crm-i fa-code" aria-hidden="true"></i> {ts}Select Code{/ts}</a></strong>
                {else}
                    <div class="description">
                        {ts}The code for adding this widget to web pages will be displayed here after you click <strong>Save and Preview</strong>.{/ts}
                    </div>
                {/if}
            </div>
            </fieldset>
        </div>


        <details class="crm-accordion-bold crm-case-roles-block">
         <summary>
          {ts}Edit Widget Colors{/ts}
         </summary>
         <div class="crm-accordion-body">
            <table class="form-layout-compressed">
            {foreach from=$colorFields item=field key=fieldName}
              <tr><td class="label">{$form.$fieldName.label}<span class="crm-marker"> *</span></td><td>{$form.$fieldName.html}</td></tr>
            {/foreach}
            </table>
         </div>
        </details>

    </div>

    <div id="crm-submit-buttons">
        <table id="preview" class"form-layout-compressed">
     <tr>
        <td>{$form._qf_Widget_refresh.html}</td>
        </td>
     </tr>
  </table>
    </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

</div>

{literal}
<script type="text/javascript">
  var is_act = document.getElementsByName('is_active');
    if ( ! is_act[0].checked) {
           cj('#widgetFields').hide();
     cj('#preview').hide();
  }
    function widgetBlock(chkbox) {
        if (chkbox.checked) {
        cj('#widgetFields').show();
        cj('#preview').show();
        return;
        } else {
        cj('#widgetFields').hide();
        cj('#preview').hide();
              return;
     }
    }
</script>
{/literal}
{/crmRegion}
{crmRegion name="contribute-form-contributionpage-widget-post"}
{/crmRegion}

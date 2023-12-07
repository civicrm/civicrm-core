{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
{* This template is used for adding/configuring Label Formats.  *}
<div class="crm-block crm-form-block crm-labelFormat-form-block">
  {if $action eq 8}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts 1=$formatName}WARNING: You are about to delete the Label Format titled <strong>%1</strong>.{/ts} {ts}Do you want to continue?{/ts}
    </div>
  {elseif $action eq 16384}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts 1=$formatName}Are you sure you would like to make a copy of the Label Format titled <strong>%1</strong>?{/ts}
    </div>
  {else}
    <table class="form-layout-compressed">
      <tr class="crm-labelFormat-form-block-name">
        <td class="right">{$form.label.label}</td>
        <td colspan="3">{$form.label.html}</td>
      </tr>
      <tr class="crm-labelFormat-form-block-description">
        <td class="right">{$form.description.label}</td>
        <td colspan="3">{$form.description.html}</td>
      </tr>
      <tr class="crm-labelFormat-form-block-is_default">
        <td>&nbsp;</td>
        <td colspan="3">{$form.is_default.html}&nbsp;{$form.is_default.label}</td>
      </tr>
      <!--tr class="crm-labelFormat-form-block-label_type">
        <td class="right">{$form.label_type.label}</td>
        <td colspan="3">{$form.label_type.html}</td>
      </tr-->
      <tr>
        <td class="right">{$form.paper_size.label}</td>
        <td>{$form.paper_size.html}</td>
        <td class="right">{$form.font_name.label}</td>
        <td>{$form.font_name.html}</td>
      </tr>
      <tr>
        <td class="right">{$form.orientation.label}</td>
        <td>{$form.orientation.html}</td>
        <td class="right">{$form.font_size.label}</td>
        <td>{$form.font_size.html}</td>
      </tr>
      <tr>
        <td class="right">{$form.metric.label}</td>
        <td>{$form.metric.html}</td>
        <td class="right">{$form.font_style.label}</td>
        <td>{$form.bold.html}&nbsp;{$form.bold.label}&nbsp;&nbsp;{$form.italic.html}&nbsp;{$form.italic.label}</td>
      </tr>
      <tr>
        <td class="right">{$form.paper_dimensions.html}</td>
        <td colspan="3" id="paper_dimensions">&nbsp;</td>
      </tr>
      <tr>
        <td class="right">{$form.NX.label}</td>
        <td>{$form.NX.html}</td>
        <td class="right">{$form.NY.label}</td>
        <td>{$form.NY.html}</td>
      </tr>
      <tr>
        <td class="right">{$form.lMargin.label}</td>
        <td>{$form.lMargin.html}</td>
        <td class="right">{$form.tMargin.label}</td>
        <td>{$form.tMargin.html}</td>
      </tr>
      <tr>
        <td class="right">{$form.width.label}</td>
        <td>{$form.width.html}</td>
        <td class="right">{$form.height.label}</td>
        <td>{$form.height.html}</td>
      </tr>
      <tr>
        <td class="right">{$form.SpaceX.label}</td>
        <td>{$form.SpaceX.html}<br/><span class="description">{ts}Space between labels.{/ts}</span></td>
        <td class="right">{$form.SpaceY.label}</td>
        <td>{$form.SpaceY.html}<br/><span class="description">{ts}Space between labels.{/ts}</span></td>
      </tr>
      <tr>
        <td class="right">{$form.lPadding.label}</td>
        <td>{$form.lPadding.html}<br/><span class="description">{ts}Pad inside each label.{/ts}</span></td>
        <td class="right">{$form.tPadding.label}</td>
        <td>{$form.tPadding.html}<br/><span class="description">{ts}Pad inside each label.{/ts}</span></td>
      </tr>
      <tr class="crm-labelFormat-form-block-weight">
        <td class="right">{$form.weight.label}</td>
        <td colspan="3">{$form.weight.html}
          <div class="description">{ts}Weight controls the order in which Label Formats are displayed in selection lists. Enter a positive or negative integer. Lower numbers are displayed ahead of higher numbers.{/ts}</div>
      </tr>
    </table>
{literal}
  <script type="text/javascript">
    var currentWidth;
    var currentHeight;
    var currentMetric = document.getElementById('metric').value;
    selectPaper(document.getElementById('paper_size').value);

    function selectPaper(val) {
      dataUrl = {/literal}"{crmURL p='civicrm/ajax/paperSize' h=0}"{literal};
      cj.post(dataUrl, {paperSizeName: val}, function (data) {
        cj("#paper_size").val(data.name);
        metric = document.getElementById('metric').value;
        currentWidth = convertMetric(data.width, data.metric, metric);
        currentHeight = convertMetric(data.height, data.metric, metric);
        updatePaperDimensions();
      }, 'json');
    }

    function selectMetric(metric) {
      convertField('tMargin', currentMetric, metric);
      convertField('lMargin', currentMetric, metric);
      convertField('width', currentMetric, metric);
      convertField('height', currentMetric, metric);
      convertField('SpaceX', currentMetric, metric);
      convertField('SpaceY', currentMetric, metric);
      convertField('lPadding', currentMetric, metric);
      convertField('tPadding', currentMetric, metric);
      currentWidth = convertMetric(currentWidth, currentMetric, metric);
      currentHeight = convertMetric(currentHeight, currentMetric, metric);
      updatePaperDimensions();
    }

    function updatePaperDimensions() {
      metric = document.getElementById('metric').value;
      width = new String(currentWidth.toFixed(2));
      height = new String(currentHeight.toFixed(2));
      if (document.getElementById('orientation').value == 'landscape') {
        width = new String(currentHeight.toFixed(2));
        height = new String(currentWidth.toFixed(2));
      }
      document.getElementById('paper_dimensions').innerHTML = parseFloat(width) + ' ' + metric + ' x ' + parseFloat(height) + ' ' + metric;
      currentMetric = metric;
    }

    function convertField(id, from, to) {
      val = document.getElementById(id).value;
      if (val == '' || isNaN(val)) {
        return;
      }
      val = convertMetric(val, from, to);
      val = new String(val.toFixed(3));
      document.getElementById(id).value = parseFloat(val);
    }

    function convertMetric(value, from, to) {
      switch (from + to) {
        case 'incm':
          return value * 2.54;
        case 'inmm':
          return value * 25.4;
        case 'inpt':
          return value * 72;
        case 'cmin':
          return value / 2.54;
        case 'cmmm':
          return value * 10;
        case 'cmpt':
          return value * 72 / 2.54;
        case 'mmin':
          return value / 25.4;
        case 'mmcm':
          return value / 10;
        case 'mmpt':
          return value * 72 / 25.4;
        case 'ptin':
          return value / 72;
        case 'ptcm':
          return value * 2.54 / 72;
        case 'ptmm':
          return value * 25.4 / 72;
      }
      return value;
    }

  </script>
{/literal}
  {/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

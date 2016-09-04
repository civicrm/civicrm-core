{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
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
{* This template is used for adding/configuring PDF Page Formats.  *}
<div class="crm-block crm-form-block crm-pdfFormat-form-block">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts 1=$formatName}WARNING: You are about to delete the PDF Page Format titled <strong>%1</strong>.{/ts}<p>{ts}This will remove the format from all Message Templates that use it. Do you want to continue?{/ts}</p>
  </div>
{else}
  <table class="form-layout-compressed">
    <tr class="crm-pdfFormat-form-block-name">
        <td class="right">{$form.name.label}</td><td colspan="3">{$form.name.html}</td>
    </tr>
    <tr class="crm-pdfFormat-form-block-description">
        <td class="right">{$form.description.label}</td><td colspan="3">{$form.description.html}</td>
    </tr>
    <tr class="crm-pdfFormat-form-block-is_default">
        <td></td><td colspan="3">{$form.is_default.html}&nbsp;{$form.is_default.label}</td>
    </tr>
    <tr>
        <td class="right">{$form.paper_size.label}</td><td>{$form.paper_size.html}</td>
        <td class="right">{$form.orientation.label}</td><td>{$form.orientation.html}</td>
    </tr>
    <tr>
        <td class="right">{$form.paper_dimensions.html}</td><td id="paper_dimensions">&nbsp;</td>
        <td class="right">{$form.metric.label}</td><td>{$form.metric.html}</td>
    </tr>
    <tr>
        <td class="right">{$form.margin_top.label}</td><td>{$form.margin_top.html}</td>
        <td class="right">{$form.margin_bottom.label}</td><td>{$form.margin_bottom.html}</td>
    </tr>
    <tr>
        <td class="right">{$form.margin_left.label}</td><td>{$form.margin_left.html}</td>
        <td class="right">{$form.margin_right.label}</td><td>{$form.margin_right.html}</td>
    </tr>
    <tr class="crm-pdfFormat-form-block-weight">
        <td class="right">{$form.weight.label}</td><td colspan="3">{$form.weight.html}<br />
        <span class="description">{ts}Weight controls the order in which PDF Page Formats are displayed <br />in selection lists. Enter a positive or negative integer. Lower numbers <br />are displayed ahead of higher numbers.{/ts}</span>
        </td>
    </tr>
  </table>
{literal}
<script type="text/javascript" >

var currentWidth;
var currentHeight;
var currentMetric = document.getElementById('metric').value;
selectPaper( document.getElementById('paper_size').value );

function selectPaper( val )
{
    dataUrl = {/literal}"{crmURL p='civicrm/ajax/paperSize' h=0 }"{literal};
    cj.post( dataUrl, {paperSizeName: val}, function( data ) {
        cj("#paper_size").val( data.name );
        metric = document.getElementById('metric').value;
        currentWidth = convertMetric( data.width, data.metric, metric );
        currentHeight = convertMetric( data.height, data.metric, metric );
        updatePaperDimensions( );
    }, 'json');
}

function selectMetric( metric )
{
    convertField( 'margin_top', currentMetric, metric );
    convertField( 'margin_bottom', currentMetric, metric );
    convertField( 'margin_left', currentMetric, metric );
    convertField( 'margin_right', currentMetric, metric );
    currentWidth = convertMetric( currentWidth, currentMetric, metric );
    currentHeight = convertMetric( currentHeight, currentMetric, metric );
    updatePaperDimensions( );
}

function updatePaperDimensions( )
{
    metric = document.getElementById('metric').value;
    width = new String( currentWidth.toFixed( 2 ) );
    height = new String( currentHeight.toFixed( 2 ) );
    if ( document.getElementById('orientation').value == 'landscape' ) {
        width = new String( currentHeight.toFixed( 2 ) );
        height = new String( currentWidth.toFixed( 2 ) );
    }
    document.getElementById('paper_dimensions').innerHTML = parseFloat( width ) + ' ' + metric + ' x ' + parseFloat( height ) + ' ' + metric;
    currentMetric = metric;
}

function convertField( id, from, to )
{
    val = document.getElementById( id ).value;
    if ( val == '' || isNaN( val ) ) return;
    val = convertMetric( val, from, to );
    val = new String( val.toFixed( 3 ) );
    document.getElementById( id ).value = parseFloat( val );
}

function convertMetric( value, from, to ) {
    switch( from + to ) {
        case 'incm': return value * 2.54;
        case 'inmm': return value * 25.4;
        case 'inpt': return value * 72;
        case 'cmin': return value / 2.54;
        case 'cmmm': return value * 10;
        case 'cmpt': return value * 72 / 2.54;
        case 'mmin': return value / 25.4;
        case 'mmcm': return value / 10;
        case 'mmpt': return value * 72 / 25.4;
        case 'ptin': return value / 72;
        case 'ptcm': return value * 2.54 / 72;
        case 'ptmm': return value * 25.4 / 72;
    }
    return value;
}

</script>
{/literal}

{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{*common template for compose PDF letters*}
{if $form.template.html}
<table class="form-layout-compressed">
    <tr>
      <td class="label-left">{$form.template.label}</td>
      <td>{$form.template.html}</td>
    </tr>
    <tr>
      <td class="label-left">{$form.subject.label}</td>
      <td>{$form.subject.html}</td>
    </tr>
    <tr>
      <td class="label-left">{$form.campaign_id.label}</td>
      <td>{$form.campaign_id.html}</td>
    </tr>
</table>
{/if}

<div class="crm-accordion-wrapper collapsed">
    <div class="crm-accordion-header">
        {$form.pdf_format_header.html}
    </div>
    <div class="crm-accordion-body">
      <div class="crm-block crm-form-block crm-pdf-format-form-block">
    <table class="form-layout-compressed">
      <tr>
        <td class="label-left">{$form.format_id.label} {help id="id-pdf-format" file="CRM/Contact/Form/Task/PDFLetterCommon.hlp"}</td>
        <td>{$form.format_id.html}</td>
      </tr>
      <tr>
        <td class="label-left">{$form.paper_size.label}</td><td>{$form.paper_size.html}</td>
        <td class="label-left">{$form.orientation.label}</td><td>{$form.orientation.html}</td>
      </tr>
      <tr>
        <td class="label-left">{$form.metric.label}</td><td>{$form.metric.html}</td>
        <td colspan="2">&nbsp;</td>
      </tr>
      <tr>
        <td>{$form.paper_dimensions.html}</td><td id="paper_dimensions">&nbsp;</td>
        <td colspan="2">&nbsp;</td>
      </tr>
      <tr>
        <td class="label-left">{$form.margin_top.label}</td><td>{$form.margin_top.html}</td>
        <td class="label-left">{$form.margin_bottom.label}</td><td>{$form.margin_bottom.html}</td>
      </tr>
      <tr>
        <td class="label-left">{$form.margin_left.label}</td><td>{$form.margin_left.html}</td>
        <td class="label-left">{$form.margin_right.label}</td><td>{$form.margin_right.html}</td>
      </tr>
      <tr>
        <td class="label-left">{$form.stationery.label}</td><td>{$form.stationery.html}</td>
        <td colspan="2">&nbsp;</td>
      </tr>
    </table>
        <div id="bindFormat">{$form.bind_format.html}&nbsp;{$form.bind_format.label}</div>
        <div id="updateFormat" style="display: none">{$form.update_format.html}&nbsp;{$form.update_format.label}</div>
      </div>
  </div>
</div>

<div class="crm-accordion-wrapper crm-html_email-accordion ">
<div class="crm-accordion-header">
    {$form.html_message.label}
</div><!-- /.crm-accordion-header -->
 <div class="crm-accordion-body">
   <div class="helpIcon" id="helphtml">
     <input class="crm-token-selector big" data-field="html_message" />
     {help id="id-token-html" tplFile=$tplFile isAdmin=$isAdmin editor=$editor file="CRM/Contact/Form/Task/Email.hlp"}
   </div>
    <div class="clear"></div>
    <div class='html'>
  {if $editor EQ 'textarea'}
      <div class="help description">{ts}NOTE: If you are composing HTML-formatted messages, you may want to enable a Rich Text (WYSIWYG) editor (Administer &raquo; Customize Data & Screens &raquo; Display Preferences).{/ts}</div>
  {/if}
  {$form.html_message.html}<br />
    </div>

<div id="editMessageDetails">
    <div id="updateDetails" >
        {$form.updateTemplate.html}&nbsp;{$form.updateTemplate.label}
    </div>
    <div>
        {$form.saveTemplate.html}&nbsp;{$form.saveTemplate.label}
    </div>
</div>

<div id="saveDetails" class="section">
    <div class="label">{$form.saveTemplateName.label}</div>
    <div class="content">{$form.saveTemplateName.html|crmAddClass:huge}</div>
</div>

  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->

{include file="CRM/Mailing/Form/InsertTokens.tpl"}

{literal}
<script type="text/javascript">
CRM.$(function($) {
  var $form = $('form.{/literal}{$form.formClass}{literal}');
  $('#format_id', $form).on('change', function() {
    selectFormat($(this).val());
  });
});

var currentWidth;
var currentHeight;
var currentMetric = document.getElementById('metric').value;
showBindFormatChkBox();
selectPaper( document.getElementById('paper_size').value );

function showBindFormatChkBox()
{
    var templateExists = true;
    if ( document.getElementById('template') == null || document.getElementById('template').value == '' ) {
        templateExists = false;
    }
    var formatExists = !!cj('#format_id').val();
    if ( templateExists && formatExists ) {
        document.getElementById("bindFormat").style.display = "block";
    } else if ( formatExists && document.getElementById("saveTemplate") != null && document.getElementById("saveTemplate").checked ) {
        document.getElementById("bindFormat").style.display = "block";
        var yes = confirm( '{/literal}{$useThisPageFormat}{literal}' );
        if ( yes ) {
            document.getElementById("bind_format").checked = true;
        }
    } else {
        document.getElementById("bindFormat").style.display = "none";
        document.getElementById("bind_format").checked = false;
    }
}

function showUpdateFormatChkBox()
{
    if (cj('#format_id').val()) {
      cj("#updateFormat").show();
    }
}

function updateFormatLabel() {
  cj('.pdf-format-header-label').html(cj('#format_id option:selected').text() || cj('#format_id').attr('placeholder'));
}

updateFormatLabel();

function selectFormat( val, bind )
{
  updateFormatLabel();
    if (!val) {
        val = 0;
        bind = false;
    }
    var dataUrl = {/literal}"{crmURL p='civicrm/ajax/pdfFormat' h=0 }"{literal};
    cj.post( dataUrl, {formatId: val}, function( data ) {
        cj("#format_id").val( data.id );
        cj("#paper_size").val( data.paper_size );
        cj("#orientation").val( data.orientation );
        cj("#metric").val( data.metric );
        cj("#margin_top").val( data.margin_top );
        cj("#margin_bottom").val( data.margin_bottom );
        cj("#margin_left").val( data.margin_left );
        cj("#margin_right").val( data.margin_right );
        selectPaper( data.paper_size );
        cj("#update_format").prop({checked: false}).parent().hide();
        document.getElementById('bind_format').checked = bind;
        showBindFormatChkBox();
    }, 'json');
}

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

function showSaveDetails(chkbox)  {
    var formatSelected = ( document.getElementById('format_id').value > 0 );
    var templateSelected = ( document.getElementById('template') != null && document.getElementById('template').value > 0 );
    if (chkbox.checked) {
        document.getElementById("saveDetails").style.display = "block";
        document.getElementById("saveTemplateName").disabled = false;
        if ( formatSelected && ! templateSelected ) {
            document.getElementById("bindFormat").style.display = "block";
            var yes = confirm( '{/literal}{$useSelectedPageFormat}{literal}' );
            if ( yes ) {
                document.getElementById("bind_format").checked = true;
            }
        }
    } else {
        document.getElementById("saveDetails").style.display = "none";
        document.getElementById("saveTemplateName").disabled = true;
        if ( ! templateSelected ) {
            document.getElementById("bindFormat").style.display = "none";
            document.getElementById("bind_format").checked = false;
        }
    }
}

</script>
{/literal}


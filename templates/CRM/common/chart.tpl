{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<script src="{$config->resourceBase}/bower_components/d3/d3.min.js"></script>
<script src="{$config->resourceBase}/bower_components/crossfilter2/crossfilter.min.js"></script>
<script src="{$config->resourceBase}/bower_components/dc-2.1.x/dc.min.js"></script>
<style src="{$config->resourceBase}/bower_components/dc-2.1.x/dc.min.css"></style>
{literal}
<style>
  .dc-chart path.domain {
    fill: none;
    stroke: black;
  }
</style>
<script type="text/javascript">
function createChart( chartID, divName, xSize, ySize, data ) {

  var div = document.getElementById(divName);
  if (!div) {
    console.log("no element found for chart id ", divName);
    return;
  }

  // Figure out suitable size based on container size.
  // In some cases the containing element has no size. We should insist on a minimum size.
  var w = Math.max(Math.min(div.clientWidth - 32, 800), 316);
  var h = Math.min(400, parseInt(w / 2));

  var chartNode = document.createElement('div');
  var heading = document.createElement('h2');
  heading.textContent = data.title;
  heading.style.marginBottom = '1rem';
  heading.style.textAlign = 'center';
  div.style.width = w + 'px';
  div.style.marginLeft = 'auto';
  div.style.marginRight = 'auto';

  var links = document.createElement('div');
  links.style.textAlign = 'center';
  links.style.marginBottom = '1rem';
  var linkSVG = document.createElement('a');
  linkSVG.href = '#';
  linkSVG.textContent = 'Download chart (SVG)';
  linkSVG.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();
    // Create an image.
    var svg = div.querySelector('svg');
    var xml = new XMLSerializer().serializeToString(svg);
    var image64 =  'data:image/svg+xml;base64,' + btoa(xml);

    downloadImageUrl('image/svg+xml', image64, data.title.replace(/[^a-zA-Z0-9-]+/g, '') + '.svg');
  });
  function downloadImageUrl(mime, url, filename) {
    var downloadLink = document.createElement('a');
    downloadLink.download = filename;
    downloadLink.href = url;
    downloadLink.downloadurl = [mime, downloadLink.download, url].join(':');
    document.body.append(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
  }
  var linkPNG = document.createElement('a');
  linkPNG.href = '#';
  linkPNG.textContent = 'Download chart (PNG)';
  linkPNG.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();
    // Create an image.

    var canvas = document.createElement('canvas');
    canvas.width = w;
    canvas.height = h;
    div.appendChild(canvas);

    var svg = div.querySelector('svg');
    var xml = new XMLSerializer().serializeToString(svg);
    var svg64 = btoa(xml);
    var b64Start = 'data:image/svg+xml;base64,';
    var image64 = b64Start + svg64;

    var img = document.createElement('img');
    img.onload = function() {
      canvas.getContext('2d').drawImage(img, 0, 0);
      // canvas.style.display = 'block';
      var imgURL = canvas.toDataURL('image/png');
      downloadImageUrl('image/png', imgURL, data.title.replace(/[^a-zA-Z0-9-]+/g, '') + '.png');
      div.removeChild(canvas);
    };
    img.src = image64;
  });

  links.appendChild(linkSVG);
  links.appendChild(document.createTextNode(' | '));
  links.appendChild(linkPNG);

  var crossfilterData, ndx, dataDimension, dataGroup, chart;
  ndx = crossfilter(data.values[0]);
  dataDimension = ndx.dimension(d => d.label);
  dataGroup = dataDimension.group().reduceSum(d => d.value);
  var ordinals = data.values[0].map(d => d.label);

  if (data.type === 'barchart') {
    chart = dc.barChart(chartNode)
      .width(w)
      .height(h)
      .dimension(dataDimension)
      .group(dataGroup)
      .gap(4) // px
      .x(d3.scale.ordinal(ordinals).domain(ordinals))
      .xUnits(dc.units.ordinal)
      .margins({top: 10, right: 30, bottom: 30, left: 90})
      .elasticY(true)
      .renderLabel(false)
      .renderHorizontalGridLines(true)
      .title(item=> item.key + ': ' + item.value)
      //.turnOnControls(true)
      .renderTitle(true);
  }
  else if (data.type === 'piechart') {
    chart = dc.pieChart(chartNode)
      .width(w)
      .height(h)
      .radius(parseInt(h / 2) - 5) // define pie radius
      .innerRadius(parseInt(h / 4) - 5) // optional
      .externalRadiusPadding(5)
      .legend(dc.legend().legendText(d => d.name).y(5))
      .dimension(dataDimension)
      .group(dataGroup)
      .renderLabel(false)
      .title(item=> item.key + ': ' + item.value)
      .turnOnControls(true)
      .renderTitle(true);
  }
  // Delay rendering so that animation looks good.
  window.setTimeout(() => {
    div.appendChild(heading);
    div.appendChild(chartNode);
    div.appendChild(links);

    dc.renderAll();
  }, 1500);
}
</script>
{/literal}

<?php
function compare($query) { $compares = array();
$result = mysql_query($query);
while ($row = mysql_fetch_object($result)) {
  if (!$_GET['current'] and $row->year == date('Y') and $row->month == date('m')) {
    continue;
    }$data[$row->month . '’' . substr($row->year, 2)][$row->compare] = $row->data;
    $compares[] = $row->compare;
  }
  $compares = array_unique($compares);
  sort($compares);

  $keys   = array_keys($data);
  $key    = $keys[count($data) - 1];
  $recent = array_pop($data);
  $data   = array_merge($data, array($key => $recent));

  foreach ($data as $label => $values) {
    foreach ($values as $compare => $value) {
      $data[$label][$compare] = round($value / array_sum($values) * 100, 1);
    }
  }

  $lines = array();
  foreach (array_keys($data) as $label) {
    $rolling = 0;
    foreach ($compares as $compare) {
      $rolling += $data[$label][$compare];
      $lines[$compare][$label] = $rolling;
    }
  }

  foreach ($lines as $line => $values) $lines[$line] = implode(',', $values);

  $colours = array();
  while (count($colours) < count($compares)) {
    $colours[] = str_pad(dechex(rand(0, 16777215)), 6, '0', STR_PAD_LEFT);
  }

  $fill = array();
  foreach ($colours as $i => $colour) {
    $j = $i + 1;
    $fill[] = "b,$colour,$i,$j,0";
  }

  $params = array(
    'chs' => '500x200',
    'cht' => 'lc',
    'chd' => 't:0,0|' . implode('|', $lines),
    'chm' => implode('|', $fill),
    'chco' => '00000000',
    'chxt' => 'x,y',
    'chxl' => '0:|' . implode('|', array_keys($data)) . '|1:|0%|25%|50%|75%|100%',
  );

  $trend = 'http://chart.apis.google.com/chart?';
  foreach ($params as $key => $value) $trend .= "&amp;{$key}={$value}";

  $last = array();
  $tail = array_pop($data);
  foreach ($compares as $compare) {
    $last[$compare] = $tail[$compare] ? $tail[$compare] : 0;
  }

  $labels = array();
  foreach ($last as $label => $value) {
    $lab      = $label ? $label : 'unknown';
    $num      = $recent[$label] ? $recent[$label] : 0;
    $labels[] = "$lab ($num, {$value}%)";
  }

  $params = array(
    'chs' => '450x200',
    'cht' => 'p',
    'chd' => 't:' . implode(',', $last),
    'chl' => implode('|', $labels),
    'chco' => implode(',', $colours),
  );

  $pie = 'http://chart.apis.google.com/chart?';
  foreach ($params as $key => $value) $pie .= "&amp;{$key}={$value}";

  return array('url' => $trend, 'last' => $pie);
}

function trend($query) {
  $data = array();
  $result = mysql_query($query);
  while ($row = mysql_fetch_object($result)) {
    if (!$_GET['current'] and $row->year == date('Y') and $row->month == date('m')) {
      continue;
    }
    $data[$row->month . '’' . substr($row->year, 2)] = $row->data;
  }

  $max = max($data);

  $params = array(
    'chs' => '500x200',
    'cht' => 'lc',
    'chd' => 't:' . implode(',', $data),
    'chds' => '0,' . $max,
    'chxt' => 'x,y',
    'chxl' => '0:|' . implode('|', array_keys($data)) . '|1:|0|' . implode('|', array($max * 0.25, $max * 0.5, $max * 0.75, $max)),
  );

  $url = 'http://chart.apis.google.com/chart?';
  foreach ($params as $key => $value) $url .= "&amp;{$key}={$value}";
  return array('url' => $url, 'last' => array_pop($data));
}


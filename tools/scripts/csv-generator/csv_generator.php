<?php

/**
 * This file generates sample csv fil for testing large imports.
 * Run following command: php csv_gen.php
 * It will generate contacts.csv file with  $totalRows contacts
 */
$data = "First Name, Last Name, Email, Street Address 1, Street Address 2, Phone, IM \r\n";

$totalRows = 80000;
$count = 1;
while ($count <= $totalRows) {
  $data .= "first{$count}, last{$count}, email{$count}@email.com, street1{$count}, street2{$count}, 9888909{$count}, im{$count} \r\n";
  $count++;
}

$filename = 'contacts.csv';

if (!$handle = fopen($filename, 'w')) {
  echo "Cannot open file ($filename)";
  exit;
}

if (fwrite($handle, $data) === FALSE) {
  echo "Cannot write to file ($filename)";
  exit;
}

echo "Successfully created csv for $totalRows contacts and wrote  to file ($filename)\n\r";

fclose($handle);


<?php

printf("<ul>\n");
foreach ($extensions as $extension) {
  $file = check_plain($extension->field_extension_fq_name_value . ".xml");
  printf("<li><a href=\"%s\">%s</a></li>\n",
    $file,
    $file
  );
}
printf("</ul>\n");

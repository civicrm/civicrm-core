<?php

require_once 'civi_contribute.civix.php';

function _civi_contribute_afform_clear() {
  \Civi::cache('metadata')->clear();
}

<?php

// This file was provided in previous builds of `civicrm-setup`. It served two purposes:
//
// 1. Defining the classloader for `civicrm-setup/src/**.php`
// 2. Defining a flag to help search for a copy of `civicrm-setup`.
//    (To wit: If a folder has this file, then it must be a valid copy of `civicrm-setup`.
//    Otherwise, move on and look for civicrm-setup in another location.)
//
// The extant consumers enable both the `civicrm-setup` and `civicrm-core` classloaders. But
// now that civicrm-setup is in core, this classloader is redundant. Thus, an empty file.
//
// However, we still need the file to exist for the second purpose (as a flag).

## Upgrade Steps: Naming Conventions

```
## Formula (New Code; v4.7+)
CRM/Upgrade/Steps/{MAJOR}/{COUNTER}_{DescriptiveName}.up.php

## Formula  (Legacy Code)
CRM/Upgrade/Steps/{MAJOR}_All.up.php

## Examples
CRM/Upgrade/Steps/47/100_FixFoo.up.php (CRM_Upgrade_Steps_47_100_FixFoo)
CRM/Upgrade/Steps/47/100_UpdateBar.up.php (CRM_Upgrade_Steps_47_100_UpdateBar)
CRM/Upgrade/Steps/47/205_TwiddleBaz.up.php (CRM_Upgrade_Steps_47_205_TwiddleBaz)
CRM/Upgrade/Steps/46_All.up.php (CRM_Upgrade_Steps_46_All)
CRM/Upgrade/Steps/45_All.up.php (CRM_Upgrade_Steps_45_All)
```

## Important Notes

 * Recognized file extensions: `*.up.php`
 * The file name must be a number followed by a camelcase word (eg `100_FixFoo.up.php`).
 * For *old* upgrades, all incremental changes were originally written as one big file (eg `FourFive.php` aka `45_All.up.php`). These files extend `RevisionBase`.
 * For *new* upgrades, all incremental changes must be standalone files in a subfolder (eg `47/100_FixFoo.up.php`). These files extend `SimpleBase`.

## Execution

The files in this folder are executed by the following process:

 1. Find all files named `CRM/Upgrade/Steps/**.up.php`.
 2. Convert file names to class names.
 3. Sort class names (dictionary order).
 4. Identify classes which have been executed already. Skip.
 5. Execute all other classes.

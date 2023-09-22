<?php

// The interface `Civi\Test\HookInterface` was originally written for use in unit-tests.
// It got promoted for use in more cases, but then the name became misleading.
// Leave behind alias for backward compatibility.
class_alias('Civi\Core\HookInterface', 'Civi\Test\HookInterface');

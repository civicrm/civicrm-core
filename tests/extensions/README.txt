Extensions in the "test.*" namespace are used for testing the extension
system.  All "test.*" extensions are automatically uninstalled in between
test runs.

Note: For simplicity and performance, the automatic uninstallation may
bypass the normal lifecycle events (hook_install, hook_enable, hook_disable,
hook_uninstall); therefore, "test.*" extension should not rely on lifecycle
hooks.

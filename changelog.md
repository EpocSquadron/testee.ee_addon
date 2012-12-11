### Version 2.3
- Added Qunit JavaScript unit testing support for addons.

### Version 2.2.4

- Fixed a bug where 'Testee Test Results' title was not being pulled from the lang file.
- Fixed a bug where themes were not checking for EE's new movable theme folder ability.
- Added static 'callMethod', to the unit test base class, which uses PHP 5.3's reflection class to allow testing of protected methods. Uses fallback if the class doesn't exist.
- Added the 'exportVar' function, to the unit test base class, to help create string output of test variables for error messages.
- Added the ability to use {exp:testee:run_tests addon="addon_name|other_addon"} in templates in addition to the action URL ability.
- Added module menu and header view for menu highlighting and link to docs.
- Changed link to docs to point to github wiki.
- Added unprefixed css border-radius for ie9+ support
- Added preferences page
- Added ability to set custom test folder locations per addon via preferences
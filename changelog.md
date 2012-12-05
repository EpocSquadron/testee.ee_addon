### Version 2.2.4

- Fixed a bug where 'Testee Test Results' title was not being pulled from the lang file.
- Fixed a bug where themes were not checking for EE's new movable theme folder ability.
- Added static 'callMethod', to the unit test base class, which uses PHP 5.3's reflection class to allow testing of protected methods. Uses fallback if the class doesn't exist.
- Added the 'exportVar' function, to the unit test base class, to help create string output of test variables for error messages.
- Added the ability to use {exp:testee:run_tests addon="addon_name|other_addon"} in templates in addition to the action URL ability.
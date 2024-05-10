# ECM Custom Profile Page

This WordPress plugin, developed by Cristian Ibanez, enhances user profiles by adding custom profile pages. It integrates deeply with WordPress to allow users to manage their profiles with added custom fields.

## Features

### Custom Profile Management
- Allows users to update their profile with custom fields such as first name, last name, nickname, bio, and more.
- Users can update their password directly from their profile page.
- Additional custom fields include gamertag and favorite game.

### Profile Sharing Preferences
- Users can decide if they want to share their profile with others by toggling the profile sharing option.

### Admin Integration
- Adds new scripts and styles to enhance the admin interface and user interaction.
- Provides a backend option page under the ECM UpVote plugin to manage dependencies.

### Security
- Implements nonce verification for secure form submissions to prevent CSRF attacks.

### AJAX Functionality
- Uses AJAX for smooth interactions without needing to reload the page, particularly in the profile update process.

## Installation

1. Download the plugin from the repository.
2. Upload it to your WordPress website via the WordPress admin panel.
3. Activate the plugin through the 'Plugins' menu in WordPress.

## Usage

- Navigate to your profile page where you will see new fields added.
- Update your profile by filling out the new fields such as gamertag and favorite game.
- You can also update your profile sharing preferences.

## Dependencies

- This plugin depends on the ECM UpVote plugin for added functionality.

## Hooks and Actions

### Activation Hook
- On activation, the plugin checks if a specific profile page exists, if not, it creates one.

### Deactivation Hook
- On deactivation, it cleans up by removing the created profile page and its associated settings from the database.

### Shortcodes
- Implements shortcodes for adding custom profile forms and other features directly into posts and pages.

## License

This plugin is licensed under the GPL-3.0 license.

## Additional Notes

- Ensure that the ToyBeast UpVote plugin is installed and activated for this plugin to function correctly.
- This plugin is designed to work with WordPress 5.0 and above.

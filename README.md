# CustomTheme

**A live theme editor for Matomo. Control colours, typography, background image, and UI roundness — no code required.**

> This plugin is a community contribution and is not officially supported by Matomo GmbH.
> It is provided under the GPL v3 license without any warranty of fitness for a particular purpose.

## Description

CustomTheme gives Matomo super-administrators a complete visual editor built directly into the administration panel. Adjust your Matomo instance's look and feel to match your organisation's brand in minutes — without editing any files or writing any CSS.

All changes are applied live across the entire Matomo interface: reports, menus, widgets, dialogs, and data tables.

### Features

- **34 colour controls** — brand colour, header, text, backgrounds, menus, widgets, focus rings, code blocks, links, and more
- **Automatic palette generation** — pick one primary colour and instantly generate a full harmonious colour palette using HSL colour theory
- **Background image** — upload a PNG, JPG, GIF, or WebP image; control display mode (cover / contain / repeat), overlay opacity, and blur intensity
- **Typography** — choose from 11 curated font stacks or upload your own custom font (WOFF2, WOFF, TTF, or OTF)
- **Shape roundness** — five presets from sharp corners (0 px) to pill-shaped (999 px), applied consistently to all UI elements
- **Live preview** — colour changes are reflected immediately in the admin interface before saving
- **One-click reset** — restore all Matomo defaults at any time

### Security

- All theme editor endpoints require super-administrator access
- Colour values are validated against a strict hex colour pattern before saving
- File uploads are validated by MIME type and magic bytes; SVG is intentionally blocked to prevent stored XSS
- Uploaded assets are served through an authenticated PHP proxy — files are never directly accessible from the webroot
- CSRF protection via Matomo nonce on all mutating actions
- Font-family input blocks remote loading patterns (`url()`, `@import`, `https://`)

## Requirements

- Matomo >= 5.0
- PHP >= 8.1

## Installation

### From the Matomo Marketplace

1. Go to **Administration → Marketplace**.
2. Search for **CustomTheme**.
3. Click **Install** and then **Activate**.

### Manual Installation

1. Download the latest release archive from the [GitHub repository](https://github.com/Chardonneaur/CustomTheme/releases).
2. Extract it into your `matomo/plugins/` directory so that the path `matomo/plugins/CustomTheme/plugin.json` exists.
3. Go to **Administration → Plugins** and activate **CustomTheme**.

## Usage

After activation, go to **Administration → System → Custom Theme**.

The editor is organised into four tabs:

| Tab | What you can control |
|---|---|
| **Colours** | All 34 colour variables, plus one-click palette generation from a primary colour |
| **Background** | Upload an image, set display mode, overlay opacity, and blur |
| **Typography** | Font stack selection or custom font upload |
| **Shape** | UI roundness preset (Sharp / Slightly rounded / Medium / Rounded / Pill) |

Click **Save** on any tab to apply changes. Use **Reset to defaults** to restore the original Matomo theme.

## FAQ

**Does this affect all users?**
Yes — CustomTheme applies a global theme to the entire Matomo instance. All users see the same theme. Individual users cannot override it.

**Will this conflict with other theme plugins?**
CustomTheme uses Matomo's `Theme.configureThemeVariables` event and injects a small `<style>` block in the page header. It should coexist with other themes, but the last plugin to apply a variable wins.

**What happens if I deactivate the plugin?**
Matomo immediately returns to its default theme. Settings are preserved in the database — reactivating the plugin restores your custom theme.

**What happens if I uninstall the plugin?**
All saved settings are removed along with any uploaded font or background image files.

**Is the uploaded background image or font file accessible without authentication?**
No. Files are stored outside the webroot in the plugin's `data/` directory, which is protected by an `.htaccess` rule. All file serving goes through an authenticated PHP proxy action.

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for the full version history.

## License

GPL v3+. See [LICENSE](LICENSE) for details.

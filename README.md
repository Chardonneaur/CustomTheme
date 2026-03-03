# CustomTheme

Build a custom visual theme with colour controls, typography, and background image settings.

> **Warning**
>
> This plugin is experimental and was coded using [Claude Code](https://claude.ai).
> It is provided without any warranty regarding quality, stability, or performance.
> This is a community project and is not officially supported by Matomo.

## Description

CustomTheme gives Matomo super-administrators a live theme editor without touching any code:

- **34 colour controls** — brand, header, text, backgrounds, menus, widgets, focus rings, code blocks, and more
- **Automatic palette generation** — pick one primary colour and generate a full harmonious palette using HSL colour theory
- **Background image** — upload PNG, JPG, GIF, or WebP; control display style (cover/contain/repeat), overlay opacity, and blur
- **Typography** — choose from 11 curated font stacks or upload a custom WOFF2/WOFF/TTF/OTF font
- **Shape roundness** — five presets from sharp (0 px) to pill (999 px) applied consistently across all UI elements
- **Live preview** — colour changes are reflected instantly in the admin UI before saving
- **Security-hardened** — uploaded files served through an authenticated PHP proxy; direct file access blocked via `.htaccess`

## Requirements

- Matomo >= 5.0
- PHP >= 8.1

## Installation

### From Matomo Marketplace
1. Go to **Administration → Marketplace**
2. Search for **CustomTheme**
3. Click **Install**

### Manual Installation
1. Download the latest release from GitHub
2. Extract to your `matomo/plugins/` directory
3. Activate the plugin in **Administration → Plugins**

## Configuration

Go to **Administration → System → Custom Theme** to access the theme editor.

All settings are stored in Matomo's system settings — no file editing required.

## Security notes

- All endpoints require super-administrator access
- File uploads are validated by MIME type (images) and magic bytes (fonts)
- SVG uploads are intentionally blocked to prevent stored XSS
- Uploaded assets are served via an authenticated PHP proxy (`serveBackground` / `serveFont`), never directly from the webroot
- CSRF protection via Matomo nonce on all mutating controller actions

## License

GPL v3+. See [LICENSE](LICENSE) for details.

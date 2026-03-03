<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTheme;

use Piwik\Plugin;
use Piwik\Plugin\ThemeStyles;

class CustomTheme extends Plugin
{
    public function registerEvents(): array
    {
        return [
            'Theme.configureThemeVariables'      => 'onConfigureTheme',
            'Template.header'                    => 'onTemplateHeader',
            'AssetManager.getStylesheetFiles'    => 'getStylesheets',
            'AssetManager.getJavaScriptFiles'    => 'getJsFiles',
        ];
    }

    public function install(): void
    {
        // Ensure data directories exist
        $dirs = [
            PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data',
            PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data/background',
            PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data/fonts',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * Apply stored colours to Matomo's theme variables.
     */
    public function onConfigureTheme(ThemeStyles $themeStyles): void
    {
        $settings = new SystemSettings();
        foreach ($settings->getStoredColors() as $prop => $value) {
            if (property_exists($themeStyles, $prop) && $value !== '') {
                $themeStyles->$prop = $value;
            }
        }

        $fontFamilyBase = $this->normalizeFontFamily((string) $settings->fontFamilyBase->getValue());
        $localFontName  = trim((string) $settings->localFontName->getValue());
        $localFontPath  = trim((string) $settings->localFontPath->getValue());

        if ($localFontName !== '' && $localFontPath !== '') {
            $fallback = $fontFamilyBase !== '' ? $fontFamilyBase : $themeStyles->fontFamilyBase;
            $themeStyles->fontFamilyBase = "'" . addslashes($localFontName) . "', " . $fallback;
        } elseif ($fontFamilyBase !== '') {
            $themeStyles->fontFamilyBase = $fontFamilyBase;
        }
    }

    /**
     * Inject background image CSS into the page <head>.
     */
    public function onTemplateHeader(string &$output): void
    {
        $settings = new SystemSettings();
        $css       = '';

        $localFontName = trim((string) $settings->localFontName->getValue());
        $localFontPath = trim((string) $settings->localFontPath->getValue());
        $roundnessKey  = (string) $settings->shapeRoundness->getValue();
        $roundness     = SystemSettings::getShapeRoundnessOptions()[$roundnessKey] ?? '8px';
        if ($localFontName !== '' && $localFontPath !== '') {
            // Use proxy URL — direct file path never exposed to the browser
            $fontProxyUrl = 'index.php?module=CustomTheme&action=serveFont';
            $fontFmt  = $this->fontFormatFromPath($localFontPath); // extension still used for format hint
            $safeName = addslashes($localFontName);
            $css .= "@font-face{font-family:'" . $safeName . "';src:url('" . $fontProxyUrl . "') format('" . $fontFmt . "');font-style:normal;font-weight:100 900;font-display:swap;}";
        }
        $css .= ":root{--customtheme-shape-roundness:" . $roundness . ";}";
        $css .= ".btn,button,input,select,textarea,.card,.card-content,.widget,.widgetpreview,.ui-dialog,.alert,.notification,.entityTable,.dataTable,.dataTable th,.dataTable td,.dataTableWrapper,.reportDocumentation,.form-group .browser-default,.dashboardWidget,.widgetTop,.widgetBody{border-radius:var(--customtheme-shape-roundness)!important;}";
        $css .= ".ui-dialog .ui-dialog-titlebar,.widget .widgetTop,.dataTableWrapper table thead tr,.dataTableWrapper table tbody tr:last-child td:first-child{border-top-left-radius:var(--customtheme-shape-roundness)!important;}";
        $css .= ".ui-dialog .ui-dialog-titlebar,.widget .widgetTop,.dataTableWrapper table thead tr,.dataTableWrapper table tbody tr:last-child td:last-child{border-top-right-radius:var(--customtheme-shape-roundness)!important;}";
        $css .= "#secondNavBar,.Menu .navbar > li > .item,.Menu .navbar > li > ul li > .item,.Menu .menuDropdown .items,.Menu .menuDropdown .title,.Menu .collapsible-header,.Menu .collapsible-body{border-radius:var(--customtheme-shape-roundness)!important;}";

        $bgPath = (string) $settings->backgroundImagePath->getValue();
        if ($bgPath !== '') {
            $style   = (string) $settings->backgroundStyle->getValue();
            $opacity = (float)  $settings->backgroundOverlayOpacity->getValue();
            $blur    = (int)    $settings->backgroundBlur->getValue();
            $overlayTint = (string) $settings->colorBackgroundOverlayTint->getValue();
            $opacity = max(0, min(0.9, $opacity));
            $blur    = max(0, min(20, $blur));
            $overlayRgb = $this->hexToRgb($overlayTint) ?? [255, 255, 255];

            $bgSize = 'cover';
            $bgRepeat = 'no-repeat';
            if ($style === 'contain') {
                $bgSize = 'contain';
            } elseif ($style === 'repeat') {
                $bgSize   = 'auto';
                $bgRepeat = 'repeat';
            }

            // Use proxy URL — direct file path never exposed to the browser
            $bgProxyUrl = 'index.php?module=CustomTheme&action=serveBackground';
            $css .= "body{background-image:url('" . $bgProxyUrl . "');background-size:" . $bgSize . ";background-repeat:" . $bgRepeat . ";background-attachment:fixed;background-position:center;}";
            $css .= "body::before{content:'';position:fixed;inset:0;z-index:0;pointer-events:none;background:rgba(" . $overlayRgb[0] . "," . $overlayRgb[1] . "," . $overlayRgb[2] . "," . $opacity . ");";
            if ($blur > 0) {
                $css .= "backdrop-filter:blur(" . $blur . "px);-webkit-backdrop-filter:blur(" . $blur . "px);";
            }
            $css .= "}";
        }

        $linkHover = trim((string) $settings->colorLinkHover->getValue());
        if ($linkHover !== '') {
            $safeLinkHover = htmlspecialchars($linkHover, ENT_QUOTES, 'UTF-8');
            $css .= "a:hover,a:focus{color:" . $safeLinkHover . ";}";
        }

        $headerHover = trim((string) $settings->colorHeaderHoverBackground->getValue());
        if ($headerHover !== '') {
            $safeHeaderHover = htmlspecialchars($headerHover, ENT_QUOTES, 'UTF-8');
            $css .= "#topmenu>li>a:hover,#topmenu>li>a:focus,.top_controls>li>a:hover,.top_controls>li>a:focus{background-color:" . $safeHeaderHover . ";}";
        }

        if ($css !== '') {
            $output .= '<style id="customtheme-styles">' . $css . '</style>';
        }
    }

    /**
     * @return int[]|null
     */
    private function hexToRgb(string $hex): ?array
    {
        $hex = trim($hex);
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex)) {
            return null;
        }

        $h = ltrim($hex, '#');
        if (strlen($h) === 3) {
            $h = $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }

        return [
            hexdec(substr($h, 0, 2)),
            hexdec(substr($h, 2, 2)),
            hexdec(substr($h, 4, 2)),
        ];
    }

    private function fontFormatFromPath(string $path): string
    {
        return match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
            'woff2' => 'woff2',
            'woff'  => 'woff',
            'ttf'   => 'truetype',
            'otf'   => 'opentype',
            default => 'woff2',
        };
    }

    private function normalizeFontFamily(string $fontFamilyBase): string
    {
        $fontFamilyBase = html_entity_decode($fontFamilyBase, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip characters that could break out of a CSS property value or rule
        $fontFamilyBase = str_replace(["\n", "\r", ';', '{', '}', '(', ')', '<', '>'], ' ', $fontFamilyBase);
        $fontFamilyBase = trim(preg_replace('/\s+/', ' ', $fontFamilyBase) ?: '');
        return $fontFamilyBase;
    }

    public function getStylesheets(array &$stylesheets): void
    {
        $stylesheets[] = 'plugins/CustomTheme/stylesheets/admin.css';
    }

    public function getJsFiles(array &$jsFiles): void
    {
        $jsFiles[] = 'plugins/CustomTheme/javascripts/CustomTheme.js';
    }
}

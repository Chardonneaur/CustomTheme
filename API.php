<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTheme;

use Piwik\AssetManager;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugin\API as BaseAPI;

class API extends BaseAPI
{
    /**
     * Generate a palette from a primary colour without saving to DB.
     *
     * @param string $primaryColor  hex colour, e.g. "#3450A3"
     * @return array<string, string>
     */
    public function generatePalette(string $primaryColor): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $harmony = new ColorHarmony();
        return $harmony->generateFromPrimary($primaryColor);
    }

    /**
     * Return all currently stored theme settings.
     *
     * @return array
     */
    public function getTheme(): array
    {
        Piwik::checkUserHasSuperUserAccess();

        $settings = new SystemSettings();

        return [
            'colors'                    => $settings->getStoredColors(),
            'backgroundImagePath'       => (string) $settings->backgroundImagePath->getValue(),
            'backgroundStyle'           => (string) $settings->backgroundStyle->getValue(),
            'backgroundOverlayOpacity'  => (string) $settings->backgroundOverlayOpacity->getValue(),
            'backgroundBlur'            => (string) $settings->backgroundBlur->getValue(),
            'fontFamilyBase'            => (string) $settings->fontFamilyBase->getValue(),
            'shapeRoundness'            => (string) $settings->shapeRoundness->getValue(),
            'localFontName'             => (string) $settings->localFontName->getValue(),
            'localFontPath'             => (string) $settings->localFontPath->getValue(),
        ];
    }

    /**
     * Save colour properties. Each colour is passed as its own request parameter
     * (e.g. colorBrand=#3450A3). Hex values are safe through Matomo's sanitization
     * because htmlspecialchars() does not touch #, digits, or a-f letters.
     *
     * @return bool
     */
    public function saveColors(): bool
    {
        Piwik::checkUserHasSuperUserAccess();

        $settings = new SystemSettings();

        foreach (SystemSettings::getAllColorProperties() as $prop) {
            // Only update properties that were actually submitted
            if (!isset($_POST[$prop]) && !isset($_GET[$prop])) {
                continue;
            }
            $value = trim(Common::getRequestVar($prop, '', 'string'));
            // Accept valid hex colours or empty string (clears the override)
            if ($value !== '' && !preg_match('/^#[0-9a-fA-F]{3,6}$/', $value)) {
                continue;
            }
            $settings->$prop->setValue($value);
        }

        $settings->save();
        AssetManager::getInstance()->removeMergedAssets();

        return true;
    }

    /**
     * Save typography settings.
     */
    public function saveTypographySettings(string $fontFamilyBase = '', string $shapeRoundness = 'medium'): bool
    {
        Piwik::checkUserHasSuperUserAccess();

        $fontFamilyBase = $this->normalizeFontFamily($fontFamilyBase);
        if (strlen($fontFamilyBase) > 500) {
            $fontFamilyBase = substr($fontFamilyBase, 0, 500);
        }
        // Local-only policy: block remote font loading patterns in custom font-family input.
        if (preg_match('/url\s*\(|@import|https?:\/\//i', $fontFamilyBase)) {
            $fontFamilyBase = '';
        }

        $settings = new SystemSettings();
        if (!isset(SystemSettings::getShapeRoundnessOptions()[$shapeRoundness])) {
            $shapeRoundness = 'medium';
        }
        $settings->fontFamilyBase->setValue($fontFamilyBase);
        $settings->shapeRoundness->setValue($shapeRoundness);
        $settings->save();

        AssetManager::getInstance()->removeMergedAssets();
        return true;
    }

    private function normalizeFontFamily(string $fontFamilyBase): string
    {
        $fontFamilyBase = html_entity_decode($fontFamilyBase, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip characters that could break out of a CSS property value or rule
        $fontFamilyBase = str_replace(["\n", "\r", ';', '{', '}', '(', ')', '<', '>'], ' ', $fontFamilyBase);
        $fontFamilyBase = trim(preg_replace('/\s+/', ' ', $fontFamilyBase) ?: '');
        return $fontFamilyBase;
    }

    /**
     * Save background display settings (not the image itself — that is uploaded via Controller).
     *
     * @param string $style    cover|contain|repeat
     * @param string $opacity  float 0–0.9 as a string
     * @param string $blur     int 0–20 as a string
     * @return bool
     */
    public function saveBackgroundSettings(string $style = 'cover', string $opacity = '0.3', string $blur = '0'): bool
    {
        Piwik::checkUserHasSuperUserAccess();

        $allowedStyles = ['cover', 'contain', 'repeat'];
        if (!in_array($style, $allowedStyles, true)) {
            $style = 'cover';
        }
        $opacityVal = max(0, min(0.9, (float) $opacity));
        $blurVal    = max(0, min(20, (int) $blur));

        $settings = new SystemSettings();
        $settings->backgroundStyle->setValue($style);
        $settings->backgroundOverlayOpacity->setValue((string) $opacityVal);
        $settings->backgroundBlur->setValue((string) $blurVal);
        $settings->save();

        return true;
    }

    /**
     * Reset all theme settings to Matomo defaults.
     *
     * @return bool
     */
    public function resetTheme(): bool
    {
        Piwik::checkUserHasSuperUserAccess();

        $settings = new SystemSettings();

        foreach (SystemSettings::getAllColorProperties() as $prop) {
            $settings->$prop->setValue('');
        }

        $settings->backgroundImagePath->setValue('');
        $settings->backgroundStyle->setValue('cover');
        $settings->backgroundOverlayOpacity->setValue('0.3');
        $settings->backgroundBlur->setValue('0');
        $settings->fontFamilyBase->setValue('');
        $settings->shapeRoundness->setValue('medium');
        $settings->localFontName->setValue('');
        $settings->localFontPath->setValue('');
        $settings->save();

        // Remove uploaded files
        $bgDir = PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data/background/';
        $fontDir = PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data/fonts/';

        foreach (glob($bgDir . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        foreach (glob($fontDir . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        AssetManager::getInstance()->removeMergedAssets();

        return true;
    }
}

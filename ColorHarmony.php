<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTheme;

class ColorHarmony
{
    /**
     * Generate a full semantic colour palette from a single primary hex colour.
     *
     * @param string $primaryHex  e.g. "#3450A3"
     * @return array<string, string>  map of ThemeStyles property name => hex colour
     */
    public function generateFromPrimary(string $primaryHex): array
    {
        $primaryHex = ltrim(trim($primaryHex), '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $primaryHex)) {
            $primaryHex = '3450A3';
        }

        [$h, $s, $l] = $this->hexToHsl($primaryHex);

        $compH = fmod($h + 180, 360);

        return [
            'colorBrand'                       => '#' . $primaryHex,
            'colorBrandContrast'               => $l < 0.55 ? '#ffffff' : '#212121',
            'colorHeaderBackground'            => $this->hslToHex($h, min(1, $s * 1.1), $l * 0.55),
            'colorHeaderText'                  => '#ffffff',
            'colorLink'                        => $this->hslToHex($h, min(1, $s * 1.1), max(0.40, min(0.55, $l))),
            'colorLinkHover'                   => $this->hslToHex($h, min(1, $s * 1.15), max(0.28, min(0.46, $l * 0.88))),
            'colorText'                        => $this->hslToHex($h, 0.10, 0.13),
            'colorTextLight'                   => $this->hslToHex($h, 0.08, 0.27),
            'colorTextLighter'                 => $this->hslToHex($h, 0.06, 0.40),
            'colorTextContrast'                => $this->hslToHex($h, 0.12, 0.22),
            'colorBackgroundBase'              => $this->hslToHex($h, 0.12, 0.94),
            'colorBackgroundTinyContrast'      => $this->hslToHex($h, 0.08, 0.96),
            'colorBackgroundLowContrast'       => $this->hslToHex($h, 0.15, 0.86),
            'colorBackgroundContrast'          => '#ffffff',
            'colorBackgroundOverlayTint'       => $this->hslToHex($h, 0.18, 0.98),
            'colorBackgroundHighContrast'      => $this->hslToHex($h, $s * 0.8, $l * 0.25),
            'colorBorder'                      => $this->hslToHex($h, 0.15, 0.80),
            'colorHeadlineAlternative'         => $this->hslToHex($h, $s * 0.7, min(1, $l * 0.65 + 0.05)),
            'colorBaseSeries'                  => $this->hslToHex($compH, 0.75, 0.45),
            'colorMenuContrastText'            => $this->hslToHex($h, 0.10, 0.13),
            'colorMenuContrastTextSelected'    => $this->hslToHex($h, $s, $l * 0.80),
            'colorMenuContrastTextActive'      => '#' . $primaryHex,
            'colorMenuContrastBackground'      => '#ffffff',
            'colorHeaderHoverBackground'       => $this->hslToHex($h, min(1, $s * 1.05), max(0.22, min(0.44, $l * 0.48))),
            'colorWidgetBackground'            => '#ffffff',
            'colorWidgetBorder'                => $this->hslToHex($h, 0.12, 0.88),
            'colorWidgetTitleText'             => $this->hslToHex($h, 0.10, 0.13),
            'colorWidgetTitleBackground'       => '#ffffff',
            'colorWidgetExportedBackgroundBase' => '#ffffff',
            'colorFocusRing'                   => '#' . $primaryHex,
            'colorFocusRingAlternative'        => $this->hslToHex($compH, 0.75, max(0.35, min(0.60, 0.45))),
            'colorCode'                        => $this->hslToHex($h, 0.10, 0.13),
            'colorCodeBackground'              => $this->hslToHex($h, 0.12, 0.94),
        ];
    }

    /**
     * Convert a 6-char hex string (no #) to [hue, saturation, lightness].
     * Hue: 0–360, S and L: 0.0–1.0
     *
     * @return array{float, float, float}
     */
    private function hexToHsl(string $hex): array
    {
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $delta = $max - $min;

        $l = ($max + $min) / 2;

        if ($delta == 0) {
            return [0.0, 0.0, $l];
        }

        $s = $delta / (1 - abs(2 * $l - 1));

        if ($max == $r) {
            $h = fmod(($g - $b) / $delta, 6);
        } elseif ($max == $g) {
            $h = ($b - $r) / $delta + 2;
        } else {
            $h = ($r - $g) / $delta + 4;
        }

        $h = fmod($h * 60 + 360, 360);

        return [$h, $s, $l];
    }

    /**
     * Convert HSL (hue 0–360, sat 0–1, light 0–1) to 6-char hex string (no #).
     */
    private function hslToHex(float $h, float $s, float $l): string
    {
        $s = max(0, min(1, $s));
        $l = max(0, min(1, $l));
        $h = fmod($h + 360, 360);

        $c = (1 - abs(2 * $l - 1)) * $s;
        $x = $c * (1 - abs(fmod($h / 60, 2) - 1));
        $m = $l - $c / 2;

        if ($h < 60) {
            [$r, $g, $b] = [$c, $x, 0];
        } elseif ($h < 120) {
            [$r, $g, $b] = [$x, $c, 0];
        } elseif ($h < 180) {
            [$r, $g, $b] = [0, $c, $x];
        } elseif ($h < 240) {
            [$r, $g, $b] = [0, $x, $c];
        } elseif ($h < 300) {
            [$r, $g, $b] = [$x, 0, $c];
        } else {
            [$r, $g, $b] = [$c, 0, $x];
        }

        return sprintf(
            '#%02x%02x%02x',
            (int) round(($r + $m) * 255),
            (int) round(($g + $m) * 255),
            (int) round(($b + $m) * 255)
        );
    }
}

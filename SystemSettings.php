<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTheme;

use Piwik\Settings\FieldConfig;
use Piwik\Settings\Plugin\SystemSetting;

class SystemSettings extends \Piwik\Settings\Plugin\SystemSettings
{
    /** Colour settings — one per ThemeStyles property */
    public SystemSetting $colorBrand;
    public SystemSetting $colorBrandContrast;
    public SystemSetting $colorHeaderBackground;
    public SystemSetting $colorHeaderText;
    public SystemSetting $colorLink;
    public SystemSetting $colorText;
    public SystemSetting $colorTextLight;
    public SystemSetting $colorTextLighter;
    public SystemSetting $colorTextContrast;
    public SystemSetting $colorBackgroundBase;
    public SystemSetting $colorBackgroundTinyContrast;
    public SystemSetting $colorBackgroundLowContrast;
    public SystemSetting $colorBackgroundContrast;
    public SystemSetting $colorBackgroundHighContrast;
    public SystemSetting $colorBorder;
    public SystemSetting $colorHeadlineAlternative;
    public SystemSetting $colorBaseSeries;
    public SystemSetting $colorMenuContrastText;
    public SystemSetting $colorMenuContrastTextSelected;
    public SystemSetting $colorMenuContrastTextActive;
    public SystemSetting $colorMenuContrastBackground;
    public SystemSetting $colorWidgetBackground;
    public SystemSetting $colorWidgetBorder;
    public SystemSetting $colorWidgetTitleText;
    public SystemSetting $colorWidgetTitleBackground;
    public SystemSetting $colorWidgetExportedBackgroundBase;
    public SystemSetting $colorFocusRing;
    public SystemSetting $colorFocusRingAlternative;
    public SystemSetting $colorCode;
    public SystemSetting $colorCodeBackground;
    public SystemSetting $colorLinkHover;
    public SystemSetting $colorHeaderHoverBackground;

    public SystemSetting $fontFamilyBase;
    public SystemSetting $shapeRoundness;
    public SystemSetting $localFontName;
    public SystemSetting $localFontPath;

    /** The colour properties that map to ThemeStyles */
    public static array $colorProperties = [
        'colorBrand',
        'colorBrandContrast',
        'colorHeaderBackground',
        'colorHeaderText',
        'colorLink',
        'colorText',
        'colorTextLight',
        'colorTextLighter',
        'colorTextContrast',
        'colorBackgroundBase',
        'colorBackgroundTinyContrast',
        'colorBackgroundLowContrast',
        'colorBackgroundContrast',
        'colorBackgroundHighContrast',
        'colorBorder',
        'colorHeadlineAlternative',
        'colorBaseSeries',
        'colorMenuContrastText',
        'colorMenuContrastTextSelected',
        'colorMenuContrastTextActive',
        'colorMenuContrastBackground',
        'colorWidgetBackground',
        'colorWidgetBorder',
        'colorWidgetTitleText',
        'colorWidgetTitleBackground',
        'colorWidgetExportedBackgroundBase',
        'colorFocusRing',
        'colorFocusRingAlternative',
        'colorCode',
        'colorCodeBackground',
    ];

    /** Additional colour overrides handled by custom CSS (not ThemeStyles core vars). */
    public static array $advancedColorProperties = [
        'colorLinkHover',
        'colorHeaderHoverBackground',
    ];

    /**
     * Human-readable labels and explanations for each configurable colour.
     *
     * @return array<string, array{label: string, help: string}>
     */
    public static function getColorFieldDetails(): array
    {
        return [
            'colorBrand' => [
                'label' => 'Brand / accent color',
                'help' => 'Main accent used for primary actions and highlighted interface elements.',
            ],
            'colorBrandContrast' => [
                'label' => 'Accent text contrast',
                'help' => 'Text/icon color shown on top of the brand/accent background (for readability).',
            ],
            'colorHeaderBackground' => [
                'label' => 'Top header background',
                'help' => 'Background color of the main top bar across Matomo pages.',
            ],
            'colorHeaderText' => [
                'label' => 'Top header text',
                'help' => 'Text and icon color displayed inside the top header bar.',
            ],
            'colorLink' => [
                'label' => 'Link color',
                'help' => 'Default color for clickable links in reports and admin pages.',
            ],
            'colorText' => [
                'label' => 'Primary text',
                'help' => 'Default body text color used throughout Matomo.',
            ],
            'colorTextLight' => [
                'label' => 'Secondary text',
                'help' => 'Softer text color for labels and less prominent information.',
            ],
            'colorTextLighter' => [
                'label' => 'Muted text',
                'help' => 'Very subtle text color for helper text and minor UI details.',
            ],
            'colorTextContrast' => [
                'label' => 'Text on dark surfaces',
                'help' => 'Text color used when a dark or high-contrast background is applied.',
            ],
            'colorBackgroundBase' => [
                'label' => 'Page background',
                'help' => 'Base background color behind most page content.',
            ],
            'colorBackgroundTinyContrast' => [
                'label' => 'Very subtle surface',
                'help' => 'Light contrast background for delicate separators and micro-surfaces.',
            ],
            'colorBackgroundLowContrast' => [
                'label' => 'Low-contrast surface',
                'help' => 'Background color for gently separated blocks and zones.',
            ],
            'colorBackgroundContrast' => [
                'label' => 'Medium-contrast surface',
                'help' => 'Background for areas that should stand out from the base page.',
            ],
            'colorBackgroundHighContrast' => [
                'label' => 'High-contrast surface',
                'help' => 'Strong contrast background for emphasized panels and states.',
            ],
            'colorBorder' => [
                'label' => 'Borders and dividers',
                'help' => 'Default color for borders, table lines, and separators.',
            ],
            'colorHeadlineAlternative' => [
                'label' => 'Alternative headings',
                'help' => 'Heading color used in specific reports/widgets where alternate title styling applies.',
            ],
            'colorBaseSeries' => [
                'label' => 'Chart primary series',
                'help' => 'Base color used for chart data series and analytics visuals.',
            ],
            'colorMenuContrastText' => [
                'label' => 'Sidebar menu text',
                'help' => 'Default text/icon color for items in the left navigation menu.',
            ],
            'colorMenuContrastTextSelected' => [
                'label' => 'Sidebar selected item text',
                'help' => 'Text/icon color for the currently selected left-menu item.',
            ],
            'colorMenuContrastTextActive' => [
                'label' => 'Sidebar active/hover text',
                'help' => 'Text/icon color for active or hovered left-menu entries.',
            ],
            'colorMenuContrastBackground' => [
                'label' => 'Sidebar menu background',
                'help' => 'Background color of the left navigation sidebar.',
            ],
            'colorWidgetBackground' => [
                'label' => 'Widget background',
                'help' => 'Background color inside dashboard widgets and report blocks.',
            ],
            'colorWidgetBorder' => [
                'label' => 'Widget border',
                'help' => 'Border color around widgets and report containers.',
            ],
            'colorWidgetTitleText' => [
                'label' => 'Widget title text',
                'help' => 'Text color for widget/report header titles.',
            ],
            'colorWidgetTitleBackground' => [
                'label' => 'Widget title background',
                'help' => 'Background color of widget/report header bars.',
            ],
            'colorWidgetExportedBackgroundBase' => [
                'label' => 'Exported widget background',
                'help' => 'Base background color for exported report/widget rendering.',
            ],
            'colorFocusRing' => [
                'label' => 'Focus ring',
                'help' => 'Outline color shown when elements are keyboard-focused.',
            ],
            'colorFocusRingAlternative' => [
                'label' => 'Alternative focus ring',
                'help' => 'Secondary focus outline color used in alternate contexts.',
            ],
            'colorCode' => [
                'label' => 'Code text',
                'help' => 'Text color for inline code and code blocks.',
            ],
            'colorCodeBackground' => [
                'label' => 'Code background',
                'help' => 'Background color behind inline code and preformatted code sections.',
            ],
        ];
    }

    /**
     * @return array<string, array{label: string, help: string}>
     */
    public static function getAdvancedColorFieldDetails(): array
    {
        return [
            'colorLinkHover' => [
                'label' => 'Link hover color',
                'help' => 'Color used when links are hovered or keyboard-focused.',
            ],
            'colorHeaderHoverBackground' => [
                'label' => 'Top header hover background',
                'help' => 'Background color shown behind top-header links while hovered.',
            ],
            'colorBackgroundOverlayTint' => [
                'label' => 'Background overlay tint',
                'help' => 'Tint color applied over the background image before opacity is applied.',
            ],
        ];
    }

    /**
     * @return string[]
     */
    public static function getAllColorProperties(): array
    {
        return array_merge(self::$colorProperties, self::$advancedColorProperties);
    }

    /**
     * @return array<string, string>
     */
    public static function getShapeRoundnessOptions(): array
    {
        return [
            'sharp'  => '0px',
            'small'  => '4px',
            'medium' => '8px',
            'large'  => '12px',
            'pill'   => '999px',
        ];
    }

    protected function init(): void
    {
        foreach (self::getAllColorProperties() as $prop) {
            $this->$prop = $this->makeSetting(
                $prop,
                '',
                FieldConfig::TYPE_STRING,
                function (FieldConfig $field) use ($prop) {
                    $field->title     = $prop;
                    $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
                }
            );
        }

        $this->fontFamilyBase = $this->makeSetting(
            'fontFamilyBase',
            '',
            FieldConfig::TYPE_STRING,
            function (FieldConfig $field) {
                $field->title     = 'Base font family';
                $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            }
        );

        $this->shapeRoundness = $this->makeSetting(
            'shapeRoundness',
            'medium',
            FieldConfig::TYPE_STRING,
            function (FieldConfig $field) {
                $field->title     = 'Shape roundness';
                $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            }
        );

        $this->localFontName = $this->makeSetting(
            'localFontName',
            '',
            FieldConfig::TYPE_STRING,
            function (FieldConfig $field) {
                $field->title     = 'Local font name';
                $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            }
        );

        $this->localFontPath = $this->makeSetting(
            'localFontPath',
            '',
            FieldConfig::TYPE_STRING,
            function (FieldConfig $field) {
                $field->title     = 'Local font path';
                $field->uiControl = FieldConfig::UI_CONTROL_TEXT;
            }
        );

    }

    /**
     * Return all stored colour values as a map { propertyName => value }.
     * Only non-empty values are returned.
     */
    public function getStoredColors(): array
    {
        $result = [];
        foreach (self::getAllColorProperties() as $prop) {
            $val = (string) $this->$prop->getValue();
            if ($val !== '') {
                $result[$prop] = $val;
            }
        }
        return $result;
    }

}

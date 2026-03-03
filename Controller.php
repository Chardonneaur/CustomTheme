<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomTheme;

use Piwik\AssetManager;
use Piwik\Nonce;
use Piwik\Piwik;
use Piwik\Plugin\ControllerAdmin;
use Piwik\View;

class Controller extends ControllerAdmin
{
    private const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5 MB
    private const NONCE_NAME    = 'CustomTheme.upload';
    private const ALLOWED_FONT_EXTENSIONS = ['woff2', 'woff', 'ttf', 'otf'];

    public function index(): string
    {
        Piwik::checkUserHasSuperUserAccess();

        $settings = new SystemSettings();

        $storedColors = [];
        foreach (SystemSettings::getAllColorProperties() as $prop) {
            $storedColors[$prop] = (string) $settings->$prop->getValue();
        }

        $view = new View('@CustomTheme/index');
        $this->setBasicVariablesView($view);
        $view->colorProperties           = SystemSettings::$colorProperties;
        $view->advancedColorProperties   = SystemSettings::$advancedColorProperties;
        $view->storedColors              = $storedColors;
        $view->colorFieldDetails         = SystemSettings::getColorFieldDetails();
        $view->advancedColorFieldDetails = SystemSettings::getAdvancedColorFieldDetails();
        $view->fontFamilyBase            = (string) $settings->fontFamilyBase->getValue();
        $view->fontFamilyOptions         = $this->getFontFamilyOptions((string) $settings->fontFamilyBase->getValue());
        $view->shapeRoundness            = (string) $settings->shapeRoundness->getValue();
        $view->shapeRoundnessOptions     = SystemSettings::getShapeRoundnessOptions();
        $view->localFontName             = (string) $settings->localFontName->getValue();
        $view->localFontPath             = (string) $settings->localFontPath->getValue();
        $view->uploadNonce               = Nonce::getNonce(self::NONCE_NAME);

        return $view->render();
    }

    /**
     * Serve the uploaded font file through an authenticated proxy.
     */
    public function serveFont(): void
    {
        Piwik::checkUserHasSomeViewAccess();

        $settings    = new SystemSettings();
        $storedPath  = (string) $settings->localFontPath->getValue();

        if ($storedPath === '') {
            http_response_code(404);
            exit;
        }

        $expectedDir  = realpath(PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data/fonts/');
        $absolutePath = realpath(PIWIK_INCLUDE_PATH . '/' . $storedPath);

        if (
            $absolutePath === false
            || $expectedDir === false
            || strncmp($absolutePath, $expectedDir . DIRECTORY_SEPARATOR, strlen($expectedDir) + 1) !== 0
            || !is_file($absolutePath)
        ) {
            http_response_code(404);
            exit;
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $ext  = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'woff2' => 'font/woff2',
            'woff'  => 'font/woff',
            'ttf'   => 'font/ttf',
            'otf'   => 'font/otf',
            default => 'application/octet-stream',
        };
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($absolutePath));
        header('Cache-Control: private, max-age=86400');
        header('X-Content-Type-Options: nosniff');
        readfile($absolutePath);
        exit;
    }

    /**
     * Upload a local font file (woff2/woff/ttf/otf) and save metadata.
     */
    public function uploadFont(): void
    {
        Piwik::checkUserHasSuperUserAccess();
        Nonce::checkNonce(self::NONCE_NAME, $_POST['nonce'] ?? '');

        if (empty($_FILES['font'])) {
            $this->sendJson(['success' => false, 'error' => 'No font file received.']);
            return;
        }

        $uploadError = $_FILES['font']['error'];
        if ($uploadError !== UPLOAD_ERR_OK) {
            $this->sendJson(['success' => false, 'error' => $this->uploadErrorMessage($uploadError)]);
            return;
        }

        $file = $_FILES['font'];
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $this->sendJson(['success' => false, 'error' => 'File too large (max 5 MB).']);
            return;
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_FONT_EXTENSIONS, true)) {
            $this->sendJson(['success' => false, 'error' => 'Invalid font type. Accepted: WOFF2, WOFF, TTF, OTF.']);
            return;
        }

        if (!$this->validateFontMagicBytes((string) $file['tmp_name'], $ext)) {
            $this->sendJson(['success' => false, 'error' => 'File content does not match the declared font format.']);
            return;
        }

        $destDir = PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data/fonts/';
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }
        foreach (glob($destDir . 'custom-font.*') ?: [] as $old) {
            if (is_file($old)) {
                @unlink($old);
            }
        }

        $destFile = $destDir . 'custom-font.' . $ext;
        if (!move_uploaded_file((string) $file['tmp_name'], $destFile)) {
            $this->sendJson(['success' => false, 'error' => 'Could not save the font file. Check directory permissions.']);
            return;
        }

        $fontName = trim((string) ($_POST['fontName'] ?? ''));
        if ($fontName === '') {
            $fontName = 'Custom Theme Font';
        }
        if (strlen($fontName) > 80) {
            $fontName = substr($fontName, 0, 80);
        }
        // Keep font-family name safe for CSS injection
        $fontName = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $fontName) ?: 'Custom Theme Font';

        $webPath = 'plugins/CustomTheme/data/fonts/custom-font.' . $ext;
        $settings = new SystemSettings();
        $settings->localFontName->setValue($fontName);
        $settings->localFontPath->setValue($webPath);
        $settings->save();
        AssetManager::getInstance()->removeMergedAssets();

        $this->sendJson(['success' => true, 'path' => $webPath, 'fontName' => $fontName]);
    }

    public function removeFont(): void
    {
        Piwik::checkUserHasSuperUserAccess();
        Nonce::checkNonce(self::NONCE_NAME, $_POST['nonce'] ?? '');

        $destDir = PIWIK_INCLUDE_PATH . '/plugins/CustomTheme/data/fonts/';
        foreach (glob($destDir . 'custom-font.*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $settings = new SystemSettings();
        $settings->localFontName->setValue('');
        $settings->localFontPath->setValue('');
        $settings->save();
        AssetManager::getInstance()->removeMergedAssets();

        $this->sendJson(['success' => true]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Convert a PHP ini size string (e.g. "2M", "512K") to bytes.
     */
    private function parseIniBytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1] ?? '');
        $num  = (int) $val;
        return match ($last) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => $num,
        };
    }

    /**
     * Output JSON and exit, bypassing Matomo's HTML template rendering.
     */
    private function sendJson(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }

    /**
     * Validate a font file's first bytes against known font format signatures.
     *
     * WOFF:  'wOFF' (0x774F4646)
     * WOFF2: 'wOF2' (0x774F4632)
     * TTF:   0x00010000 or 'true' (0x74727565)
     * OTF:   'OTTO' (0x4F54544F)
     */
    private function validateFontMagicBytes(string $path, string $ext): bool
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return false;
        }
        $magic = fread($handle, 4);
        fclose($handle);
        if ($magic === false || strlen($magic) < 4) {
            return false;
        }

        return match ($ext) {
            'woff'  => $magic === 'wOFF',
            'woff2' => $magic === 'wOF2',
            'ttf'   => $magic === "\x00\x01\x00\x00" || $magic === 'true',
            'otf'   => $magic === 'OTTO',
            default => false,
        };
    }

    /**
     * Translate PHP's upload error code into a human-readable message.
     */
    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the maximum allowed size.',
            UPLOAD_ERR_PARTIAL  => 'File was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE  => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server has no temporary folder configured.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension blocked the upload.',
            default => 'Upload error (code ' . $code . ').',
        };
    }

    /**
     * Build a curated dropdown list for font stack selection.
     *
     * @return array<string, string>
     */
    private function getFontFamilyOptions(string $current): array
    {
        $options = [
            '-apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen-Sans, Cantarell, \'Helvetica Neue\', sans-serif'
                => 'System UI (default)',
            'Arial, Helvetica, sans-serif' => 'Arial / Helvetica',
            'Verdana, Geneva, sans-serif' => 'Verdana',
            '\'Trebuchet MS\', Helvetica, sans-serif' => 'Trebuchet',
            'Tahoma, Geneva, sans-serif' => 'Tahoma',
            '\'Segoe UI\', Tahoma, Geneva, sans-serif' => 'Segoe UI',
            '\'Noto Sans\', \'Liberation Sans\', Arial, sans-serif' => 'Noto Sans',
            'Georgia, \'Times New Roman\', serif' => 'Georgia / Times',
            '\'Merriweather\', Georgia, serif' => 'Merriweather-style serif',
            '\'Courier New\', \'Liberation Mono\', monospace' => 'Courier / Mono',
            '\'Fira Code\', \'JetBrains Mono\', \'Courier New\', monospace' => 'Fira/JetBrains Mono',
        ];

        if ($current !== '' && !isset($options[$current])) {
            $options = [$current => 'Current custom value'] + $options;
        }

        return $options;
    }
}

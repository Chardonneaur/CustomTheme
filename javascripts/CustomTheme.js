/**
 * CustomTheme admin UI
 *
 * Only initialises on the CustomTheme admin page.
 * Uses Matomo's `ajaxHelper` for all API calls (handles session auth automatically).
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        if (!document.getElementById('customThemeAdmin')) {
            return;
        }
        CustomThemeAdmin.init();
    });

    var CustomThemeAdmin = {

        cfg: window.customThemeConfig || {},

        init: function () {
            this.bindPaletteGenerate();
            this.bindSaveColors();
            this.bindReset();
            this.bindLivePreview();
            this.bindBackgroundUpload();
            this.bindRemoveBackground();
            this.bindBackgroundSettings();
            this.bindTypographySave();
            this.bindFontUpload();
            this.bindRemoveFont();
            this.bindRangeLabels();
        },

        // ─── Helpers ─────────────────────────────────────────────────────────

        strings: function () {
            return (window.customThemeConfig || {}).strings || {};
        },

        setStatus: function ($el, message, isError) {
            $el.text(message).removeClass('ct-ok ct-err').addClass(isError ? 'ct-err' : 'ct-ok');
            if (!isError) {
                setTimeout(function () { $el.text('').removeClass('ct-ok ct-err'); }, 4000);
            }
        },

        /**
         * Call a Matomo API method.
         * Uses ajaxHelper (preferred) with jQuery fallback.
         * Automatically unwraps Matomo's associative-array wrapping (outer []).
         */
        api: function (method, getParams, postParams, onDone, onFail) {
            var baseGet = $.extend({ module: 'API', format: 'json', method: method }, getParams || {});

            function handleResponse(response) {
                // Matomo wraps associative arrays with scalar values in an outer array
                if (Array.isArray(response) && response.length === 1 && typeof response[0] === 'object' && response[0] !== null) {
                    response = response[0];
                }
                if (response && response.result === 'error') {
                    if (onFail) onFail(response.message || 'API error');
                    return;
                }
                if (onDone) onDone(response);
            }

            if (typeof ajaxHelper !== 'undefined') {
                var ajax = new ajaxHelper();
                ajax.addParams(baseGet, 'GET');
                if (postParams && Object.keys(postParams).length) {
                    ajax.addParams(postParams, 'POST');
                }
                ajax.useCallbackInCaseOfError();
                ajax.setCallback(handleResponse);
                ajax.setErrorCallback(function () { if (onFail) onFail('Request failed'); });
                ajax.send();
                return;
            }

            // jQuery fallback
            $.ajax({
                url: 'index.php',
                method: 'POST',
                dataType: 'json',
                data: $.extend(baseGet, postParams || {})
            }).done(handleResponse).fail(function () {
                if (onFail) onFail('Request failed');
            });
        },

        // ─── 1. Palette generation ────────────────────────────────────────────

        bindPaletteGenerate: function () {
            var self = this;
            $('#ct-generate-btn').on('click', function () {
                var primary = $('#ct-primary-color').val();
                var $btn    = $(this);
                var $status = $('#ct-status-generate');

                $btn.prop('disabled', true);
                $status.text(self.strings().saving || 'Generating\u2026').removeClass('ct-ok ct-err');

                self.api(
                    'CustomTheme.generatePalette',
                    { primaryColor: primary },
                    null,
                    function (palette) {
                        self.applyPaletteToInputs(palette);
                        $btn.prop('disabled', false);
                        $status.text('').removeClass('ct-ok ct-err');
                    },
                    function (msg) {
                        self.setStatus($status, msg || self.strings().saveError, true);
                        $btn.prop('disabled', false);
                    }
                );
            });
        },

        applyPaletteToInputs: function (palette) {
            $('.ct-color-input').each(function () {
                var prop  = $(this).data('prop');
                var value = palette[prop];
                if (value) {
                    $(this).val(value);
                    CustomThemeAdmin.updateLivePreview(prop, value);
                }
            });
        },

        // ─── 2. Save colours ──────────────────────────────────────────────────

        bindSaveColors: function () {
            var self = this;
            $('#ct-save-colors-btn').on('click', function () {
                // Collect each colour as its own parameter.
                // Hex values (#rrggbb) survive Matomo's htmlspecialchars sanitization intact.
                var colors = {};
                $('.ct-color-input').each(function () {
                    colors[$(this).data('prop')] = $(this).val();
                });

                var $status = $('#ct-status-colors');
                $status.text(self.strings().saving || 'Saving\u2026').removeClass('ct-ok ct-err');

                self.api(
                    'CustomTheme.saveColors',
                    null,
                    colors,
                    function () {
                        self.setStatus($status, self.strings().saved || 'Saved.', false);
                    },
                    function (msg) {
                        self.setStatus($status, (self.strings().saveError || 'Error') + ': ' + msg, true);
                    }
                );
            });
        },

        // ─── 3. Reset ─────────────────────────────────────────────────────────

        bindReset: function () {
            var self = this;
            $('#ct-reset-btn').on('click', function () {
                if (!confirm(self.strings().resetConfirm || 'Reset theme to defaults?')) {
                    return;
                }
                self.api(
                    'CustomTheme.resetTheme',
                    null,
                    null,
                    function () {
                        alert(self.strings().resetSuccess || 'Reset done.');
                        window.location.reload();
                    },
                    function (msg) {
                        alert('Reset error: ' + msg);
                    }
                );
            });
        },

        // ─── 4. Live preview ──────────────────────────────────────────────────

        bindLivePreview: function () {
            $('.ct-color-input').on('input', function () {
                CustomThemeAdmin.updateLivePreview($(this).data('prop'), $(this).val());
            });
        },

        updateLivePreview: function (prop, value) {
            // Map ThemeStyles property names to Matomo CSS custom properties
            var cssVarMap = {
                colorBrand:                        '--color-brand',
                colorBrandContrast:                '--color-brand-contrast',
                colorHeaderBackground:             '--color-header-background',
                colorHeaderText:                   '--color-header-text',
                colorLink:                         '--color-link',
                colorText:                         '--color-text',
                colorTextLight:                    '--color-text-light',
                colorTextLighter:                  '--color-text-lighter',
                colorTextContrast:                 '--color-text-contrast',
                colorBackgroundBase:               '--color-background-base',
                colorBackgroundTinyContrast:       '--color-background-tinyContrast',
                colorBackgroundLowContrast:        '--color-background-lowContrast',
                colorBackgroundContrast:           '--color-background-contrast',
                colorBackgroundHighContrast:       '--color-background-highContrast',
                colorBorder:                       '--color-border',
                colorHeadlineAlternative:          '--color-headline-alternative',
                colorBaseSeries:                   '--color-base-series',
                colorMenuContrastText:             '--color-menu-contrast-text',
                colorMenuContrastTextSelected:     '--color-menu-contrast-textSelected',
                colorMenuContrastTextActive:       '--color-menu-contrast-textActive',
                colorMenuContrastBackground:       '--color-menu-contrast-background',
                colorWidgetBackground:             '--color-widget-background',
                colorWidgetBorder:                 '--color-widget-border',
                colorWidgetTitleText:              '--color-widget-title-text',
                colorWidgetTitleBackground:        '--color-widget-title-background',
                colorWidgetExportedBackgroundBase: '--color-widget-exported-background-base',
                colorFocusRing:                    '--color-focus-ring',
                colorFocusRingAlternative:         '--color-focus-ring-alternative',
                colorCode:                         '--color-code',
                colorCodeBackground:               '--color-code-background',
            };
            var cssVar = cssVarMap[prop];
            if (cssVar) {
                document.documentElement.style.setProperty(cssVar, value);
            }
        },

        // ─── 5. Background upload ─────────────────────────────────────────────

        bindBackgroundUpload: function () {
            var self = this;
            $('#ct-bg-upload-form').on('submit', function (e) {
                e.preventDefault();
                var $form   = this;
                var $status = $('#ct-status-bg-upload');
                var file    = document.getElementById('ct-bg-file').files[0];

                if (!file) {
                    self.setStatus($status, 'Please select a file first.', true);
                    return;
                }

                var phpLimit = (self.cfg || {}).phpUploadMaxBytes || (2 * 1024 * 1024);

                $status.text('Preparing image\u2026').removeClass('ct-ok ct-err');

                // Compress raster images to fit under the server's upload limit
                CustomThemeAdmin.compressToLimit(file, phpLimit * 0.92).then(function (ready) {
                    $status.text(self.strings().uploading || 'Uploading\u2026');

                    var formData = new FormData($form);
                    // Replace the original file entry with the (possibly compressed) one
                    formData.set('background', ready, ready.name);
                    formData.append('module', 'CustomTheme');
                    formData.append('action', 'uploadBackground');
                    formData.append('nonce', (self.cfg || {}).uploadNonce || '');

                    $.ajax({
                        url: 'index.php',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        dataType: 'json',
                        success: function (resp) {
                            if (resp && resp.success) {
                                self.setStatus($status, self.strings().uploaded || 'Uploaded.', false);
                                // Reload so the nonce is refreshed for subsequent operations
                                setTimeout(function () { window.location.reload(); }, 400);
                            } else {
                                self.setStatus($status, (resp && resp.error) || self.strings().uploadError, true);
                            }
                        },
                        error: function () {
                            self.setStatus($status, self.strings().uploadError || 'Upload failed.', true);
                        }
                    });
                });
            });
        },

        /**
         * Return a Promise<File> that is guaranteed to be <= maxBytes.
         * SVG files and files already within the limit are passed through unchanged.
         * Raster images are drawn on a canvas and re-encoded as JPEG at decreasing
         * quality until they fit, scaling down the dimensions if needed.
         */
        compressToLimit: function (file, maxBytes) {
            return new Promise(function (resolve) {
                if (file.type === 'image/svg+xml' || file.size <= maxBytes) {
                    resolve(file);
                    return;
                }

                var img = new Image();
                var url = URL.createObjectURL(file);

                img.onerror = function () {
                    URL.revokeObjectURL(url);
                    resolve(file); // pass through; server will give a real error
                };

                img.onload = function () {
                    URL.revokeObjectURL(url);

                    // Scale longest side down to 1920 px at most
                    var w = img.naturalWidth;
                    var h = img.naturalHeight;
                    var maxDim = 1920;
                    if (w > maxDim || h > maxDim) {
                        if (w >= h) { h = Math.round(h * maxDim / w); w = maxDim; }
                        else        { w = Math.round(w * maxDim / h); h = maxDim; }
                    }

                    var canvas = document.createElement('canvas');
                    canvas.width  = w;
                    canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);

                    var baseName = file.name.replace(/\.[^.]+$/, '') + '.jpg';
                    var quality  = 0.88;
                    var attempts = 0;

                    function tryEncode(q) {
                        canvas.toBlob(function (blob) {
                            attempts++;
                            if (!blob) { resolve(file); return; }
                            if (blob.size <= maxBytes || q <= 0.25 || attempts >= 6) {
                                resolve(new File([blob], baseName, { type: 'image/jpeg' }));
                            } else {
                                tryEncode(Math.max(0.25, q - 0.13));
                            }
                        }, 'image/jpeg', q);
                    }
                    tryEncode(quality);
                };

                img.src = url;
            });
        },

        bindRemoveBackground: function () {
            var self = this;
            $(document).on('click', '#ct-remove-bg-btn', function () {
                var $status = $('#ct-status-bg-upload');
                $status.text('Removing\u2026').removeClass('ct-ok ct-err');
                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        module: 'CustomTheme',
                        action: 'removeBackground',
                        nonce: ((window.customThemeConfig || {}).uploadNonce || '')
                    },
                    success: function (resp) {
                        if (resp && resp.success) {
                            self.setStatus($status, self.strings().saved || 'Removed.', false);
                            // Reload so the nonce is refreshed for subsequent operations
                            setTimeout(function () { window.location.reload(); }, 400);
                        } else {
                            self.setStatus($status, (resp && resp.error) || self.strings().saveError, true);
                        }
                    },
                    error: function (xhr) {
                        // Show raw response for diagnosis if it's not JSON
                        var msg = self.strings().saveError || 'Error';
                        try { var r = JSON.parse(xhr.responseText); if (r.error) msg = r.error; } catch (e) {}
                        self.setStatus($status, msg, true);
                    }
                });
            });
        },

        // ─── 6. Background settings ───────────────────────────────────────────

        bindBackgroundSettings: function () {
            var self = this;
            $('#ct-save-bg-btn').on('click', function () {
                var $status = $('#ct-status-bg');
                $status.text(self.strings().saving || 'Saving\u2026').removeClass('ct-ok ct-err');

                self.api(
                    'CustomTheme.saveBackgroundSettings',
                    null,
                    {
                        style:   $('#ct-bg-style').val(),
                        opacity: $('#ct-overlay-opacity').val(),
                        blur:    $('#ct-blur').val()
                    },
                    function () {
                        self.setStatus($status, self.strings().saved || 'Saved.', false);
                    },
                    function (msg) {
                        self.setStatus($status, (self.strings().saveError || 'Error') + ': ' + msg, true);
                    }
                );
            });
        },

        bindRangeLabels: function () {
            $('#ct-overlay-opacity').on('input', function () { $('#ct-opacity-val').text($(this).val()); });
            $('#ct-blur').on('input',            function () { $('#ct-blur-val').text($(this).val()); });
        },

        bindTypographySave: function () {
            var self = this;
            $('#ct-save-typography-btn').on('click', function () {
                var $status = $('#ct-status-typography');
                $status.text(self.strings().saving || 'Saving\u2026').removeClass('ct-ok ct-err');

                self.api(
                    'CustomTheme.saveTypographySettings',
                    null,
                    {
                        fontFamilyBase: $('#ct-font-family').val(),
                        shapeRoundness: $('#ct-shape-roundness').val()
                    },
                    function () {
                        self.setStatus($status, self.strings().saved || 'Saved.', false);
                    },
                    function (msg) {
                        self.setStatus($status, (self.strings().saveError || 'Error') + ': ' + msg, true);
                    }
                );
            });
        },

        bindFontUpload: function () {
            var self = this;
            $('#ct-font-upload-form').on('submit', function (e) {
                e.preventDefault();
                var $status = $('#ct-status-font-upload');
                var file = document.getElementById('ct-font-file').files[0];

                if (!file) {
                    self.setStatus($status, 'Please select a font file first.', true);
                    return;
                }

                var formData = new FormData(this);
                formData.append('module', 'CustomTheme');
                formData.append('action', 'uploadFont');
                formData.append('fontName', $('#ct-font-name').val() || '');
                formData.append('nonce', (self.cfg || {}).uploadNonce || '');

                $status.text(self.strings().uploading || 'Uploading\u2026').removeClass('ct-ok ct-err');

                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (resp) {
                        if (resp && resp.success) {
                            self.setStatus($status, self.strings().uploaded || 'Uploaded.', false);
                            setTimeout(function () { window.location.reload(); }, 400);
                        } else {
                            self.setStatus($status, (resp && resp.error) || self.strings().uploadError, true);
                        }
                    },
                    error: function () {
                        self.setStatus($status, self.strings().uploadError || 'Upload failed.', true);
                    }
                });
            });
        },

        bindRemoveFont: function () {
            var self = this;
            $(document).on('click', '#ct-remove-font-btn', function () {
                var $status = $('#ct-status-font-upload');
                $status.text('Removing\u2026').removeClass('ct-ok ct-err');

                $.ajax({
                    url: 'index.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        module: 'CustomTheme',
                        action: 'removeFont',
                        nonce: ((window.customThemeConfig || {}).uploadNonce || '')
                    },
                    success: function (resp) {
                        if (resp && resp.success) {
                            self.setStatus($status, self.strings().saved || 'Removed.', false);
                            setTimeout(function () { window.location.reload(); }, 400);
                        } else {
                            self.setStatus($status, (resp && resp.error) || self.strings().saveError, true);
                        }
                    },
                    error: function () {
                        self.setStatus($status, self.strings().saveError || 'Error', true);
                    }
                });
            });
        }
    };

})(jQuery);

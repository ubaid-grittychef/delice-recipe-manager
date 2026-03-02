<?php
/**
 * Import/Export Page
 * Allows users to import and export recipes and settings
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die('Unauthorized');
?>

<div class="wrap delice-hybrid-page">
    <div class="delice-hybrid-header">
        <div class="delice-hybrid-header-left">
            <a href="<?php echo admin_url('admin.php?page=delice-recipe-manager'); ?>" class="delice-back-btn">← Dashboard</a>
            <h1 class="delice-hybrid-title">📦 Import / Export</h1>
        </div>
    </div>
    
    <div class="delice-hybrid-container" style="max-width: 900px; margin: 0 auto;">
        
        <!-- EXPORT SECTION -->
        <div class="delice-section">
            <div class="delice-section-header">
                <h2 class="delice-section-title">📤 Export</h2>
                <p class="delice-section-desc">Download your recipes and settings as JSON files for backup or migration</p>
            </div>
            
            <div class="delice-settings-section">
                <!-- Export Recipes -->
                <div class="delice-setting-card">
                    <div class="delice-setting-card-header">
                        <h3>Export Recipes</h3>
                        <p>Download all your recipes or selected recipes</p>
                    </div>
                    <div class="delice-setting-card-body">
                        <button class="delice-btn delice-btn-primary" id="export-recipes-btn">
                            📥 Download Recipes JSON
                        </button>
                    </div>
                </div>
                
                <!-- Export Settings -->
                <div class="delice-setting-card">
                    <div class="delice-setting-card-header">
                        <h3>Export Settings</h3>
                        <p>Download all your plugin settings and translations</p>
                    </div>
                    <div class="delice-setting-card-body">
                        <p class="delice-info-text">This includes all display options, templates, schema settings, language preferences, and translations.</p>
                        <button class="delice-btn delice-btn-primary" id="export-settings-btn">
                            ⚙️ Download Settings JSON
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- IMPORT SECTION -->
        <div class="delice-section">
            <div class="delice-section-header">
                <h2 class="delice-section-title">📥 Import</h2>
                <p class="delice-section-desc">Upload JSON files to import recipes and settings</p>
            </div>
            
            <div class="delice-settings-section">
                <!-- Import Recipes -->
                <div class="delice-setting-card">
                    <div class="delice-setting-card-header">
                        <h3>Import Recipes</h3>
                        <p>Upload a JSON file containing recipes</p>
                    </div>
                    <div class="delice-setting-card-body">
                        <div class="delice-file-upload">
                            <input type="file" id="import-recipes-file" accept=".json" style="display: none;">
                            <button class="delice-btn delice-btn-secondary" onclick="document.getElementById('import-recipes-file').click()">
                                📁 Choose File
                            </button>
                            <span id="import-recipes-filename" class="delice-file-name">No file selected</span>
                        </div>
                        
                        <div class="delice-import-options" style="margin-top: 16px;">
                            <div class="delice-sw-row">
                                <span class="delice-sw-row-label">Skip existing recipes (don't import duplicates)</span>
                                <label class="delice-sw"><input type="checkbox" id="skip-existing" checked><span class="delice-sw-slider"></span></label>
                            </div>
                            <div class="delice-sw-row">
                                <span class="delice-sw-row-label">Update existing recipes if found</span>
                                <label class="delice-sw"><input type="checkbox" id="update-existing"><span class="delice-sw-slider"></span></label>
                            </div>
                            <div class="delice-sw-row">
                                <span class="delice-sw-row-label">Import recipe images</span>
                                <label class="delice-sw"><input type="checkbox" id="import-images" checked><span class="delice-sw-slider"></span></label>
                            </div>
                        </div>
                        
                        <button class="delice-btn delice-btn-primary" id="import-recipes-btn" disabled>
                            📤 Import Recipes
                        </button>
                        
                        <div id="import-recipes-progress" style="display: none; margin-top: 16px;">
                            <div class="delice-progress-bar">
                                <div class="delice-progress-fill" style="width: 0%;"></div>
                            </div>
                            <p class="delice-progress-text">Importing recipes...</p>
                        </div>
                        
                        <div id="import-recipes-results" style="display: none; margin-top: 16px;"></div>
                    </div>
                </div>
                
                <!-- Import Settings -->
                <div class="delice-setting-card">
                    <div class="delice-setting-card-header">
                        <h3>Import Settings</h3>
                        <p>Upload a JSON file containing plugin settings</p>
                    </div>
                    <div class="delice-setting-card-body">
                        <div class="delice-file-upload">
                            <input type="file" id="import-settings-file" accept=".json" style="display: none;">
                            <button class="delice-btn delice-btn-secondary" onclick="document.getElementById('import-settings-file').click()">
                                📁 Choose File
                            </button>
                            <span id="import-settings-filename" class="delice-file-name">No file selected</span>
                        </div>
                        
                        <div class="delice-import-options" style="margin-top: 16px;">
                            <div class="delice-sw-row">
                                <span class="delice-sw-row-label">Merge with existing settings (recommended)</span>
                                <label class="delice-sw"><input type="checkbox" id="merge-settings"><span class="delice-sw-slider"></span></label>
                            </div>
                        </div>
                        
                        <div class="delice-warning-box" style="margin-top: 16px;">
                            <strong>⚠️ Warning:</strong> Importing settings will override your current configuration. Make sure to export your current settings first as a backup.
                        </div>
                        
                        <button class="delice-btn delice-btn-primary" id="import-settings-btn" disabled>
                            ⚙️ Import Settings
                        </button>
                        
                        <div id="import-settings-results" style="display: none; margin-top: 16px;"></div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<style>
.delice-setting-card {
    background: white;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 24px;
    margin-bottom: 20px;
}

.delice-setting-card-header h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
}

.delice-setting-card-header p {
    margin: 0;
    color: #64748B;
    font-size: 14px;
}

.delice-setting-card-body {
    margin-top: 20px;
}

.delice-radio-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.delice-radio-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

/* ── Toggle switch ── */
.delice-sw { position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0; }
.delice-sw input { opacity:0;width:0;height:0;position:absolute; }
.delice-sw-slider { position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#c3c4c7;border-radius:24px;transition:.25s; }
.delice-sw-slider:before { position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,.2);transition:.25s; }
.delice-sw input:checked + .delice-sw-slider { background:#0073aa; }
.delice-sw input:checked + .delice-sw-slider:before { transform:translateX(20px); }
.delice-sw-row { display:flex;align-items:center;justify-content:space-between;gap:16px;padding:10px 14px;background:#f8fafc;border-radius:6px;margin-bottom:8px; }
.delice-sw-row-label { font-size:13px;color:#1d2327;flex:1; }

.delice-file-upload {
    display: flex;
    align-items: center;
    gap: 12px;
}

.delice-file-name {
    color: #64748B;
    font-size: 14px;
}

.delice-info-text {
    color: #64748B;
    font-size: 14px;
    margin-bottom: 16px;
}

.delice-warning-box {
    background: #FFF5E6;
    border: 1px solid #FFB547;
    border-radius: 6px;
    padding: 12px;
    color: #854D0E;
    font-size: 14px;
}

.delice-progress-bar {
    width: 100%;
    height: 8px;
    background: #E2E8F0;
    border-radius: 4px;
    overflow: hidden;
}

.delice-progress-fill {
    height: 100%;
    background: #FF6B35;
    transition: width 0.3s ease;
}

.delice-progress-text {
    margin-top: 8px;
    font-size: 14px;
    color: #64748B;
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Export Recipes
    $('#export-recipes-btn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'delice_export_recipes',
                nonce: '<?php echo wp_create_nonce('delice_recipe_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Create download link
                    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data.data, null, 2));
                    const downloadAnchorNode = document.createElement('a');
                    downloadAnchorNode.setAttribute("href", dataStr);
                    downloadAnchorNode.setAttribute("download", response.data.filename);
                    document.body.appendChild(downloadAnchorNode);
                    downloadAnchorNode.click();
                    downloadAnchorNode.remove();
                    
                    alert('Recipes exported successfully!');
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while exporting recipes.');
            },
            complete: function() {
                btn.prop('disabled', false).text('📥 Download Recipes JSON');
            }
        });
    });
    
    // Export Settings
    $('#export-settings-btn').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).text('Generating...');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'delice_export_settings',
                nonce: '<?php echo wp_create_nonce('delice_recipe_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(response.data.data, null, 2));
                    const downloadAnchorNode = document.createElement('a');
                    downloadAnchorNode.setAttribute("href", dataStr);
                    downloadAnchorNode.setAttribute("download", response.data.filename);
                    document.body.appendChild(downloadAnchorNode);
                    downloadAnchorNode.click();
                    downloadAnchorNode.remove();
                    
                    alert('Settings exported successfully!');
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while exporting settings.');
            },
            complete: function() {
                btn.prop('disabled', false).text('⚙️ Download Settings JSON');
            }
        });
    });
    
    // Import Recipes File Selection
    $('#import-recipes-file').on('change', function() {
        const filename = this.files[0] ? this.files[0].name : 'No file selected';
        $('#import-recipes-filename').text(filename);
        $('#import-recipes-btn').prop('disabled', !this.files[0]);
    });
    
    // Import Settings File Selection
    $('#import-settings-file').on('change', function() {
        const filename = this.files[0] ? this.files[0].name : 'No file selected';
        $('#import-settings-filename').text(filename);
        $('#import-settings-btn').prop('disabled', !this.files[0]);
    });
    
    // Import Recipes
    $('#import-recipes-btn').on('click', function() {
        const fileInput = document.getElementById('import-recipes-file');
        if (!fileInput.files[0]) return;
        
        const btn = $(this);
        btn.prop('disabled', true);
        $('#import-recipes-progress').show();
        $('#import-recipes-results').hide();
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const jsonData = e.target.result;
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'delice_import_recipes',
                    nonce: '<?php echo wp_create_nonce('delice_recipe_nonce'); ?>',
                    json_data: jsonData,
                    skip_existing: $('#skip-existing').is(':checked'),
                    update_existing: $('#update-existing').is(':checked'),
                    import_images: $('#import-images').is(':checked'),
                    match_by: 'title'
                },
                success: function(response) {
                    function escImpHtml(s) {
                        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
                    }
                    if (response.success) {
                        const results = response.data;
                        var errorsHtml = '';
                        if (results.errors && results.errors.length > 0) {
                            errorsHtml = '<p><strong>Errors:</strong><br>' +
                                results.errors.map(function(e) { return escImpHtml(e); }).join('<br>') +
                                '</p>';
                        }
                        $('#import-recipes-results').html(
                            '<div class="delice-success-box">' +
                                '<h4>Import Complete!</h4>' +
                                '<p>Total: ' + parseInt(results.total, 10) +
                                ' | Imported: ' + parseInt(results.imported, 10) +
                                ' | Skipped: ' + parseInt(results.skipped, 10) +
                                ' | Failed: ' + parseInt(results.failed, 10) + '</p>' +
                                errorsHtml +
                            '</div>'
                        ).show();
                    } else {
                        $('#import-recipes-results').html(
                            '<div class="delice-error-box">Error: ' + escImpHtml((response.data && response.data.message) ? response.data.message : 'Unknown error') + '</div>'
                        ).show();
                    }
                },
                error: function() {
                    $('#import-recipes-results').html(`
                        <div class="delice-error-box">An error occurred during import.</div>
                    `).show();
                },
                complete: function() {
                    $('#import-recipes-progress').hide();
                    btn.prop('disabled', false);
                }
            });
        };
        reader.readAsText(fileInput.files[0]);
    });
    
    // Import Settings
    $('#import-settings-btn').on('click', function() {
        if (!confirm('Are you sure you want to import settings? This will modify your current configuration.')) {
            return;
        }
        
        const fileInput = document.getElementById('import-settings-file');
        if (!fileInput.files[0]) return;
        
        const btn = $(this);
        btn.prop('disabled', true).text('Importing...');
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const jsonData = e.target.result;
            
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: {
                    action: 'delice_import_settings',
                    nonce: '<?php echo wp_create_nonce('delice_recipe_nonce'); ?>',
                    json_data: jsonData,
                    merge: $('#merge-settings').is(':checked')
                },
                success: function(response) {
                    if (response.success) {
                        $('#import-settings-results').html(`
                            <div class="delice-success-box">Settings imported successfully! Reload the page to see changes.</div>
                        `).show();
                    } else {
                        $('#import-settings-results').html(`
                            <div class="delice-error-box">Error: ${response.data.message}</div>
                        `).show();
                    }
                },
                error: function() {
                    $('#import-settings-results').html(`
                        <div class="delice-error-box">An error occurred during import.</div>
                    `).show();
                },
                complete: function() {
                    btn.prop('disabled', false).text('⚙️ Import Settings');
                }
            });
        };
        reader.readAsText(fileInput.files[0]);
    });
    
});
</script>

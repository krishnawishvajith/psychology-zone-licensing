<?php
/**
 * Template: Flipbooks Tab in WooCommerce My Account
 * File: templates/flipbooks-tab.php
 */

if (!defined('ABSPATH')) exit;
?>

<div class="pz-flipbooks-tab">
    <h2>Study Materials</h2>
    <p class="pz-flipbooks-intro">Access your HTML5 digital study materials below. Click on any book to read.</p>
    
    <?php if (empty($flipbooks)): ?>
        <div class="pz-no-flipbooks">
            <div style="text-align: center; padding: 60px 20px; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">
                <span style="font-size: 64px;">📚</span>
                <h3 style="color: #666; margin: 20px 0;">No Materials Available Yet</h3>
                <p style="color: #999;">Study materials will appear here once they are added by your administrator.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="pz-flipbooks-grid">
            <?php foreach ($flipbooks as $flipbook): ?>
                <div class="pz-flipbook-card" data-flipbook-id="<?php echo esc_attr($flipbook->id); ?>">
                    <div class="pz-flipbook-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                        </svg>
                    </div>
                    <h3><?php echo esc_html($flipbook->title); ?></h3>
                    <?php if ($flipbook->description): ?>
                        <p><?php echo esc_html($flipbook->description); ?></p>
                    <?php endif; ?>
                    <button class="pz-open-flipbook-btn" data-id="<?php echo esc_attr($flipbook->id); ?>">
                        <svg viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                        </svg>
                        Open Book
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Flipbook Modal -->
<div id="pz-flipbook-modal" class="pz-modal" style="display: none;">
    <div class="pz-modal-overlay"></div>
    <div class="pz-modal-content">
        <div class="pz-modal-header">
            <h3 id="pz-flipbook-modal-title">Loading...</h3>
            <button class="pz-modal-close">&times;</button>
        </div>
        <div class="pz-modal-body">
            <div id="pz-flipbook-loading" style="text-align: center; padding: 60px;">
                <div class="pz-spinner"></div>
                <p>Loading flipbook...</p>
            </div>
            <div id="pz-flipbook-content" style="display: none;"></div>
        </div>
    </div>
</div>
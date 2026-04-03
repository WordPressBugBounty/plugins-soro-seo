<?php
/**
 * Plugin Name: Soro - SEO Autopilot & AI Content Writer
 * Plugin URI: https://trysoro.com/wordpress
 * Description: Connect your WordPress site to Soro for automatic AI-powered article publishing.
 * Version: 1.4.1
 * Author: Soro
 * Author URI: https://trysoro.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: soro-seo
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SORO_CONNECTOR_VERSION', '1.4.1');
define('SORO_CONNECTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SORO_CONNECTOR_PLUGIN_URL', plugin_dir_url(__FILE__));

class SoroConnector {
    
    private $option_name = 'soro_api_key';
    private $nonce_action = 'soro_connector_nonce';
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_regenerate_key'));
        add_action('admin_init', array($this, 'handle_save_author'));
        add_action('admin_init', array($this, 'handle_save_category'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        add_filter('rest_authentication_errors', array($this, 'bypass_rest_restriction_for_soro'), 999);
        add_action('in_admin_header', array($this, 'hide_admin_notices_on_soro_page'));
        
        register_activation_hook(__FILE__, array($this, 'activate'));
    }
    
    /**
     * Some hosting providers and security plugins block the entire WP REST API for
     * unauthenticated requests. This fires before our route-level
     * permission_callback, so Soro's own API key auth never gets a chance to run.
     *
     * This filter only clears the block for soro/v1/* routes — all other routes stay restricted.
     * Security is preserved because every Soro endpoint still requires a valid API key via verify_api_key().
     */
    public function bypass_rest_restriction_for_soro($result) {
        if (!is_wp_error($result)) {
            return $result;
        }
        
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $rest_route  = isset($_GET['rest_route']) ? $_GET['rest_route'] : '';
        
        if (strpos($request_uri, '/wp-json/soro/v1/') !== false ||
            strpos($rest_route, '/soro/v1/') !== false) {
            return true;
        }
        
        return $result;
    }
    
    /**
     * Hide all admin notices (update nags, plugin promos, etc.) on the Soro settings page
     */
    public function hide_admin_notices_on_soro_page() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'settings_page_soro-seo') {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
    }
    
    /**
     * Enqueue admin CSS and JS on plugin settings page only
     */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_soro-seo' !== $hook) {
            return;
        }
        
        $css = '
            .soro-wrap {
                max-width: 520px;
                margin: 40px auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .soro-wrap * {
                box-sizing: border-box;
            }
            .soro-logo {
                text-align: center;
                margin-bottom: 32px;
            }
            .soro-logo img {
                width: 80px;
                height: 80px;
                border-radius: 20px;
            }
            .soro-logo h1 {
                font-size: 24px;
                font-weight: 600;
                color: #1a1a1a;
                margin: 16px 0 4px;
            }
            .soro-logo p {
                color: #666;
                font-size: 14px;
                margin: 0;
            }
            .soro-card {
                background: #fff;
                border: 1px solid #e5e5e5;
                border-radius: 12px;
                padding: 24px;
                margin-bottom: 16px;
            }
            .soro-card-header {
                font-size: 14px;
                font-weight: 600;
                color: #1a1a1a;
                margin: 0 0 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .soro-card-header svg {
                width: 18px;
                height: 18px;
                color: #7C3AED;
            }
            .soro-status {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                background: #f0fdf4;
                color: #15803d;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
            }
            .soro-status-dot {
                width: 8px;
                height: 8px;
                background: #22c55e;
                border-radius: 50%;
            }
            .soro-key-container {
                position: relative;
            }
            .soro-key {
                width: 100%;
                background: #fafafa;
                border: 1px solid #e5e5e5;
                border-radius: 8px;
                padding: 14px 100px 14px 14px;
                font-family: "SF Mono", Monaco, "Cascadia Code", monospace;
                font-size: 13px;
                color: #333;
                word-break: break-all;
            }
            .soro-copy-btn {
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                background: #7C3AED;
                color: #fff;
                border: none;
                border-radius: 6px;
                padding: 8px 14px;
                font-size: 13px;
                font-weight: 500;
                cursor: pointer;
                transition: background 0.15s;
            }
            .soro-copy-btn:hover {
                background: #6D28D9;
            }
            .soro-copy-btn.copied {
                background: #22c55e;
            }
            .soro-steps {
                list-style: none;
                padding: 0;
                margin: 0;
                counter-reset: steps;
            }
            .soro-steps li {
                display: flex;
                align-items: flex-start;
                gap: 12px;
                padding: 10px 0;
                color: #444;
                font-size: 14px;
                border-bottom: 1px solid #f0f0f0;
            }
            .soro-steps li:last-child {
                border-bottom: none;
                padding-bottom: 0;
            }
            .soro-step-num {
                width: 22px;
                height: 22px;
                background: #f3f0ff;
                color: #7C3AED;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                font-weight: 600;
                flex-shrink: 0;
            }
            .soro-steps strong {
                color: #1a1a1a;
            }
            .soro-regen-btn {
                background: #fff;
                border: 1px solid #fecaca;
                color: #dc2626;
                border-radius: 8px;
                padding: 10px 16px;
                font-size: 13px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.15s;
            }
            .soro-regen-btn:hover {
                background: #fef2f2;
            }
            .soro-footer {
                text-align: center;
                padding: 16px;
                color: #999;
                font-size: 13px;
            }
            .soro-footer a {
                color: #7C3AED;
                text-decoration: none;
            }
            .soro-footer a:hover {
                text-decoration: underline;
            }
        ';
        
        wp_register_style('soro-admin', false, array(), SORO_CONNECTOR_VERSION);
        wp_enqueue_style('soro-admin');
        wp_add_inline_style('soro-admin', $css);
        
        wp_register_script('soro-admin', false, array(), SORO_CONNECTOR_VERSION, true);
        wp_enqueue_script('soro-admin');
        
        $js = '
            function soroCopyKey() {
                var keyEl = document.getElementById("soro-api-key");
                var btn = document.getElementById("soro-copy-btn");
                if (!keyEl || !btn) return;
                
                var key = keyEl.innerText.trim();
                var copiedText = btn.getAttribute("data-copied-text");
                var copyText = btn.getAttribute("data-copy-text");
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(key).then(function() {
                        btn.classList.add("copied");
                        btn.innerText = copiedText;
                        setTimeout(function() {
                            btn.classList.remove("copied");
                            btn.innerText = copyText;
                        }, 2000);
                    });
                } else {
                    var textArea = document.createElement("textarea");
                    textArea.value = key;
                    textArea.style.position = "fixed";
                    textArea.style.left = "-9999px";
                    document.body.appendChild(textArea);
                    textArea.select();
                    try {
                        document.execCommand("copy");
                        btn.classList.add("copied");
                        btn.innerText = copiedText;
                        setTimeout(function() {
                            btn.classList.remove("copied");
                            btn.innerText = copyText;
                        }, 2000);
                    } catch (e) {}
                    document.body.removeChild(textArea);
                }
            }
        ';
        
        wp_add_inline_script('soro-admin', $js);
    }
    
    /**
     * Plugin activation - generate API key
     */
    public function activate() {
        if (!get_option($this->option_name)) {
            $api_key = $this->generate_api_key();
            update_option($this->option_name, $api_key);
        }
    }
    
    /**
     * Generate a secure API key
     */
    private function generate_api_key() {
        return 'soro_' . bin2hex(random_bytes(32));
    }
    
    /**
     * Add settings page under Settings menu
     */
    public function add_settings_page() {
        add_options_page(
            __('Soro', 'soro-seo'),
            __('Soro', 'soro-seo'),
            'manage_options',
            'soro-seo',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('soro_settings', $this->option_name, array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ));
    }
    
    /**
     * Handle API key regeneration securely
     */
    public function handle_regenerate_key() {
        if (!isset($_POST['soro_regenerate'])) {
            return;
        }
        
        if (!isset($_POST['soro_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['soro_nonce'])), $this->nonce_action)) {
            add_settings_error('soro_messages', 'soro_error', __('Security check failed.', 'soro-seo'), 'error');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            add_settings_error('soro_messages', 'soro_error', __('You do not have permission to do this.', 'soro-seo'), 'error');
            return;
        }
        
        $new_key = $this->generate_api_key();
        update_option($this->option_name, $new_key);
        
        add_settings_error('soro_messages', 'soro_success', __('API key regenerated successfully.', 'soro-seo'), 'success');
    }
    
    /**
     * Handle post author save
     */
    public function handle_save_author() {
        if (!isset($_POST['soro_save_author'])) {
            return;
        }
        
        if (!isset($_POST['soro_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['soro_nonce'])), $this->nonce_action)) {
            add_settings_error('soro_messages', 'soro_error', __('Security check failed.', 'soro-seo'), 'error');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            add_settings_error('soro_messages', 'soro_error', __('You do not have permission to do this.', 'soro-seo'), 'error');
            return;
        }
        
        $author_id = isset($_POST['soro_post_author']) ? absint($_POST['soro_post_author']) : 0;
        update_option('soro_post_author', $author_id);
        
        add_settings_error('soro_messages', 'soro_success', __('Post author updated.', 'soro-seo'), 'success');
    }
    
    /**
     * Handle post category save
     */
    public function handle_save_category() {
        if (!isset($_POST['soro_save_category'])) {
            return;
        }
        
        if (!isset($_POST['soro_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['soro_nonce'])), $this->nonce_action)) {
            add_settings_error('soro_messages', 'soro_error', __('Security check failed.', 'soro-seo'), 'error');
            return;
        }
        
        if (!current_user_can('manage_options')) {
            add_settings_error('soro_messages', 'soro_error', __('You do not have permission to do this.', 'soro-seo'), 'error');
            return;
        }
        
        $category_id = isset($_POST['soro_post_category']) ? absint($_POST['soro_post_category']) : 0;
        update_option('soro_post_category', $category_id);
        
        add_settings_error('soro_messages', 'soro_success', __('Post category updated.', 'soro-seo'), 'success');
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'soro-seo'));
        }
        
        $api_key = get_option($this->option_name);
        
        if (!$api_key) {
            $api_key = $this->generate_api_key();
            update_option($this->option_name, $api_key);
        }
        
        settings_errors('soro_messages');
        
        ?>
        <div class="wrap">
            <div class="soro-wrap">
                <div class="soro-logo">
                    <?php 
                    $mascot_url = SORO_CONNECTOR_PLUGIN_URL . 'assets/soro-mascot.webp';
                    if (file_exists(SORO_CONNECTOR_PLUGIN_DIR . 'assets/soro-mascot.webp')): 
                    ?>
                        <img src="<?php echo esc_url($mascot_url); ?>" alt="Soro">
                    <?php else: ?>
                        <div style="width:80px;height:80px;background:#7C3AED;border-radius:20px;margin:0 auto;display:flex;align-items:center;justify-content:center;">
                            <span style="color:#fff;font-size:32px;font-weight:700;">S</span>
                        </div>
                    <?php endif; ?>
                    <h1><?php esc_html_e('Soro', 'soro-seo'); ?></h1>
                    <p><?php esc_html_e('AI-powered SEO content automation', 'soro-seo'); ?></p>
                </div>
                
                <div class="soro-card">
                    <div class="soro-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?php esc_html_e('Status', 'soro-seo'); ?>
                    </div>
                    <span class="soro-status">
                        <span class="soro-status-dot"></span>
                        <?php esc_html_e('Ready to connect', 'soro-seo'); ?>
                    </span>
                </div>
                
                <div class="soro-card">
                    <div class="soro-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                        </svg>
                        <?php esc_html_e('API Key', 'soro-seo'); ?>
                    </div>
                    <div class="soro-key-container">
                        <div class="soro-key" id="soro-api-key"><?php echo esc_html($api_key); ?></div>
                        <button type="button" class="soro-copy-btn" id="soro-copy-btn" onclick="soroCopyKey()" data-copy-text="<?php echo esc_attr__('Copy', 'soro-seo'); ?>" data-copied-text="<?php echo esc_attr__('Copied!', 'soro-seo'); ?>">
                            <?php esc_html_e('Copy', 'soro-seo'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="soro-card">
                    <div class="soro-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                        </svg>
                        <?php esc_html_e('Post Author', 'soro-seo'); ?>
                    </div>
                    <p style="color:#666;font-size:13px;margin:0 0 12px;"><?php esc_html_e('Choose which WordPress user will be set as the author for articles published by Soro.', 'soro-seo'); ?></p>
                    <form method="post" action="">
                        <?php wp_nonce_field($this->nonce_action, 'soro_nonce'); ?>
                        <?php
                        $saved_author = get_option('soro_post_author', 0);
                        $users = get_users(array(
                            'capability' => array('edit_posts'),
                            'orderby' => 'display_name',
                            'order' => 'ASC',
                        ));
                        ?>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <select name="soro_post_author" style="flex:1;padding:10px 12px;border:1px solid #e5e5e5;border-radius:8px;font-size:14px;background:#fafafa;">
                                <option value="0"><?php esc_html_e('— Default (site admin) —', 'soro-seo'); ?></option>
                                <?php foreach ($users as $user) : ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($saved_author, $user->ID); ?>>
                                        <?php echo esc_html($user->display_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="soro_save_author" style="background:#7C3AED;color:#fff;border:none;border-radius:8px;padding:10px 16px;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;">
                                <?php esc_html_e('Save', 'soro-seo'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="soro-card">
                    <div class="soro-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                        <?php esc_html_e('Post Category', 'soro-seo'); ?>
                    </div>
                    <p style="color:#666;font-size:13px;margin:0 0 12px;"><?php esc_html_e('Choose the default category for articles published by Soro.', 'soro-seo'); ?></p>
                    <form method="post" action="">
                        <?php wp_nonce_field($this->nonce_action, 'soro_nonce'); ?>
                        <?php
                        $saved_category = get_option('soro_post_category', 0);
                        $categories = get_categories(array(
                            'hide_empty' => false,
                            'orderby' => 'name',
                            'order' => 'ASC',
                        ));
                        ?>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <select name="soro_post_category" style="flex:1;padding:10px 12px;border:1px solid #e5e5e5;border-radius:8px;font-size:14px;background:#fafafa;">
                                <option value="0"><?php esc_html_e('— Default (Uncategorized) —', 'soro-seo'); ?></option>
                                <?php foreach ($categories as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected($saved_category, $cat->term_id); ?>>
                                        <?php echo esc_html($cat->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="soro_save_category" style="background:#7C3AED;color:#fff;border:none;border-radius:8px;padding:10px 16px;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;">
                                <?php esc_html_e('Save', 'soro-seo'); ?>
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="soro-card">
                    <div class="soro-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                        </svg>
                        <?php esc_html_e('Setup', 'soro-seo'); ?>
                    </div>
                    <ol class="soro-steps">
                        <li>
                            <span class="soro-step-num">1</span>
                            <span><?php esc_html_e('Copy your API key above', 'soro-seo'); ?></span>
                        </li>
                        <li>
                            <span class="soro-step-num">2</span>
                            <?php /* translators: %s: Settings menu path */ ?>
                            <span><?php printf(esc_html__('Go to %s in Soro', 'soro-seo'), '<strong>Settings → Integrations</strong>'); ?></span>
                        </li>
                        <li>
                            <span class="soro-step-num">3</span>
                            <?php /* translators: %s: Platform name */ ?>
                            <span><?php printf(esc_html__('Select %s and paste your key', 'soro-seo'), '<strong>WordPress</strong>'); ?></span>
                        </li>
                    </ol>
                </div>
                
                <div class="soro-card">
                    <div class="soro-card-header">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <?php esc_html_e('Regenerate Key', 'soro-seo'); ?>
                    </div>
                    <form method="post" action="">
                        <?php wp_nonce_field($this->nonce_action, 'soro_nonce'); ?>
                        <button type="submit" name="soro_regenerate" class="soro-regen-btn" onclick="return confirm('<?php echo esc_js(__('This will invalidate your current key. Continue?', 'soro-seo')); ?>');">
                            <?php esc_html_e('Generate New Key', 'soro-seo'); ?>
                        </button>
                    </form>
                </div>
                
                <div class="soro-footer">
                    <a href="https://trysoro.com" target="_blank" rel="noopener">trysoro.com</a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('soro/v1', '/publish', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_publish'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));
        
        register_rest_route('soro/v1', '/verify', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_verify'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));
        
        register_rest_route('soro/v1', '/setup-indexnow', array(
            'methods' => 'POST',
            'callback' => array($this, 'setup_indexnow'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));
        
        register_rest_route('soro/v1', '/indexnow-status', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_indexnow_status'),
            'permission_callback' => array($this, 'verify_api_key'),
        ));
    }
    
    /**
     * Verify API key from request - timing-safe comparison
     */
    public function verify_api_key($request) {
        $auth_header = $request->get_header('X-Soro-Key');
        
        if (!$auth_header) {
            $auth_header = $request->get_header('Authorization');
            if ($auth_header && strpos($auth_header, 'Bearer ') === 0) {
                $auth_header = substr($auth_header, 7);
            }
        }
        
        if (!$auth_header) {
            return false;
        }
        
        $stored_key = get_option($this->option_name);
        
        if (!$stored_key) {
            return false;
        }
        
        return hash_equals($stored_key, $auth_header);
    }
    
    /**
     * Handle verify endpoint
     */
    public function handle_verify($request) {
        return new WP_REST_Response(array(
            'success' => true,
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'version' => SORO_CONNECTOR_VERSION,
        ), 200);
    }
    
    /**
     * Handle publish endpoint
     */
    public function handle_publish($request) {
        $body_params = $request->get_body_params();
        $json_params = $request->get_json_params();
        $params = array_merge(
            is_array($body_params) ? $body_params : array(),
            is_array($json_params) ? $json_params : array()
        );
        
        if (empty($params['title']) || empty($params['content'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Title and content are required', 'soro-seo'),
            ), 400);
        }
        
        // Idempotency: if this exact Soro article was already published, return the existing post
        if (!empty($params['soro_article_id'])) {
            $existing = get_posts(array(
                'post_type'   => 'post',
                'post_status' => array('publish', 'draft', 'pending', 'private'),
                'meta_key'    => '_soro_article_id',
                'meta_value'  => sanitize_text_field($params['soro_article_id']),
                'numberposts' => 1,
                'fields'      => 'ids',
            ));
            
            if (!empty($existing)) {
                $existing_id = $existing[0];
                return new WP_REST_Response(array(
                    'success' => true,
                    'post_id' => $existing_id,
                    'post_url' => get_permalink($existing_id),
                    'edit_url' => get_edit_post_link($existing_id, 'raw'),
                    'duplicate' => true,
                ), 200);
            }
        }
        
        // Prevent duplicate slugs: if any post or page with this slug already exists,
        // still create the article as draft (so Soro stops retrying) but don't publish
        // to avoid SEO cannibalization.
        $slug_conflict = false;
        if (!empty($params['slug'])) {
            $existing_by_slug = get_posts(array(
                'post_type'   => array('post', 'page'),
                'post_status' => array('publish', 'draft', 'pending', 'private'),
                'name'        => sanitize_title($params['slug']),
                'numberposts' => 1,
                'fields'      => 'ids',
            ));
            if (!empty($existing_by_slug)) {
                $slug_conflict = true;
            }
        }
        
        $allowed_statuses = array('draft', 'publish', 'pending', 'private');
        $status = isset($params['status']) ? sanitize_text_field($params['status']) : 'draft';
        if (!in_array($status, $allowed_statuses, true)) {
            $status = 'draft';
        }
        
        if ($slug_conflict) {
            $status = 'draft';
        }
        
        // Build meta_input so SEO meta is set DURING wp_insert_post, before
        // Yoast/RankMath/AIOSEO fire their save_post hooks to build indexables.
        $meta_input = array(
            '_soro_published_at' => current_time('mysql'),
        );
        
        if (!empty($params['meta_description'])) {
            $meta_desc = sanitize_textarea_field($params['meta_description']);
            $meta_input['_yoast_wpseo_metadesc'] = $meta_desc;
            $meta_input['rank_math_description'] = $meta_desc;
            $meta_input['_aioseo_description'] = $meta_desc;
        }
        
        if (!empty($params['focus_keyword'])) {
            $focus_kw = sanitize_text_field($params['focus_keyword']);
            $meta_input['_yoast_wpseo_focuskw'] = $focus_kw;
            $meta_input['rank_math_focus_keyword'] = $focus_kw;
            $meta_input['_aioseo_focus_keyword'] = $focus_kw;
        }
        
        if (!empty($params['soro_article_id'])) {
            $meta_input['_soro_article_id'] = sanitize_text_field($params['soro_article_id']);
        }
        
        $post_data = array(
            'post_title'   => sanitize_text_field($params['title']),
            'post_content' => wp_kses_post($params['content']),
            'post_status'  => $status,
            'post_type'    => 'post',
            'meta_input'   => $meta_input,
        );
        
        $post_author = get_option('soro_post_author');
        if ($post_author) {
            $post_data['post_author'] = absint($post_author);
        }
        
        if (!empty($params['slug'])) {
            $post_data['post_name'] = sanitize_title($params['slug']);
        }
        
        if (!empty($params['excerpt'])) {
            $post_data['post_excerpt'] = sanitize_textarea_field($params['excerpt']);
        }
        
        if (!empty($params['category'])) {
            $category = get_cat_ID(sanitize_text_field($params['category']));
            if ($category) {
                $post_data['post_category'] = array($category);
            }
        }
        
        // Fallback to plugin-saved category if none was set by the API request
        if (empty($post_data['post_category'])) {
            $saved_category = get_option('soro_post_category');
            if ($saved_category) {
                $post_data['post_category'] = array(absint($saved_category));
            }
        }
        
        $post_id = wp_insert_post($post_data, true);
        
        if (is_wp_error($post_id)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => $post_id->get_error_message(),
            ), 500);
        }
        
        $this->rebuild_yoast_indexable($post_id);
        
        // Featured image is non-critical — if it crashes, we still return the post_id
        // so Soro records the publish and never retries (which would create duplicates).
        $featured_image_id = null;
        if (!empty($params['featured_image_url'])) {
            try {
                $featured_image_id = $this->attach_featured_image($post_id, $params['featured_image_url'], $params['title']);
            } catch (\Throwable $e) {
                // Log but don't fail — the post is already created
            }
        }
        
        $response_data = array(
            'success' => true,
            'post_id' => $post_id,
            'post_url' => get_permalink($post_id),
            'edit_url' => get_edit_post_link($post_id, 'raw'),
            'featured_image_id' => $featured_image_id,
        );
        
        if ($slug_conflict) {
            $response_data['slug_conflict'] = true;
            $response_data['saved_as_draft'] = true;
        }
        
        return new WP_REST_Response($response_data, 201);
    }
    
    /**
     * Attach featured image to post
     */
    private function attach_featured_image($post_id, $image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $image_url = esc_url_raw($image_url);
        if (empty($image_url)) {
            return null;
        }
        
        $parsed_url = wp_parse_url($image_url);
        if (!isset($parsed_url['scheme']) || !in_array($parsed_url['scheme'], array('http', 'https'), true)) {
            return null;
        }
        
        $tmp_file = download_url($image_url, 30);
        
        if (is_wp_error($tmp_file)) {
            return null;
        }
        
        // Get mime type with fallbacks for hosts without fileinfo extension
        $actual_mime = $this->get_file_mime_type($tmp_file, $image_url);
        
        $image_mimes = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($actual_mime, $image_mimes, true)) {
            wp_delete_file($tmp_file);
            return null;
        }
        
        $mime_to_ext = array(
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        );
        $file_ext = isset($mime_to_ext[$actual_mime]) ? $mime_to_ext[$actual_mime] : 'png';
        
        $filename = sanitize_file_name(substr(sanitize_title($title), 0, 50)) . '-featured.' . $file_ext;
        
        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp_file,
        );
        
        $attachment_id = media_handle_sideload($file_array, $post_id, sanitize_text_field($title));
        
        if (file_exists($tmp_file)) {
            wp_delete_file($tmp_file);
        }
        
        if (is_wp_error($attachment_id)) {
            return null;
        }
        
        set_post_thumbnail($post_id, $attachment_id);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', sanitize_text_field($title));
        update_post_meta($attachment_id, '_soro_featured_image', '1');
        
        return $attachment_id;
    }
    
    /**
     * Get file mime type with fallbacks for hosts without fileinfo extension
     */
    private function get_file_mime_type($file_path, $url = '') {
        // Try mime_content_type first (requires fileinfo extension)
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($file_path);
            if ($mime) {
                return $mime;
            }
        }
        
        // Try finfo_file (another fileinfo method)
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $file_path);
                finfo_close($finfo);
                if ($mime) {
                    return $mime;
                }
            }
        }
        
        // Fallback: detect from URL extension
        if ($url) {
            $path = wp_parse_url($url, PHP_URL_PATH);
            if ($path) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $ext_to_mime = array(
                    'jpg'  => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png'  => 'image/png',
                    'gif'  => 'image/gif',
                    'webp' => 'image/webp',
                );
                if (isset($ext_to_mime[$ext])) {
                    return $ext_to_mime[$ext];
                }
            }
        }
        
        // Last resort: check file magic bytes
        $handle = fopen($file_path, 'rb');
        if ($handle) {
            $bytes = fread($handle, 12);
            fclose($handle);
            
            // Check magic bytes for common image formats
            if (substr($bytes, 0, 3) === "\xFF\xD8\xFF") {
                return 'image/jpeg';
            }
            if (substr($bytes, 0, 8) === "\x89PNG\r\n\x1A\n") {
                return 'image/png';
            }
            if (substr($bytes, 0, 6) === 'GIF87a' || substr($bytes, 0, 6) === 'GIF89a') {
                return 'image/gif';
            }
            if (substr($bytes, 0, 4) === 'RIFF' && substr($bytes, 8, 4) === 'WEBP') {
                return 'image/webp';
            }
        }
        
        // Could not determine mime type
        return 'application/octet-stream';
    }
    
    /**
     * Rebuild Yoast SEO indexable for a post so the focus keyword and meta
     * description are properly reflected in Yoast's internal index/scores.
     */
    private function rebuild_yoast_indexable($post_id) {
        // Method 1: Use Yoast's DI container (Yoast SEO 14.0+)
        if (function_exists('YoastSEO')) {
            try {
                $container = YoastSEO()->classes;
                $repository = $container->get('Yoast\WP\SEO\Repositories\Indexable_Repository');
                $builder = $container->get('Yoast\WP\SEO\Builders\Indexable_Builder');
                
                $indexable = $repository->find_by_id_and_type($post_id, 'post');
                if ($indexable) {
                    $builder->build_for_id_and_type($post_id, 'post', $indexable);
                    $indexable->save();
                }
                return;
            } catch (\Exception $e) {
                // Fall through to method 2
            }
        }
        
        // Method 2: Fire wpseo_save_indexable action (older Yoast versions)
        if (class_exists('WPSEO_Meta')) {
            do_action('wpseo_save_indexable', $post_id, get_post($post_id));
        }
    }
    
    /**
     * Setup IndexNow for instant Bing/search engine indexing
     */
    public function setup_indexnow($request) {
        // Generate a unique IndexNow key (32 char hex)
        $key = bin2hex(random_bytes(16));
        
        // Write key file to site root
        $key_file_path = ABSPATH . $key . '.txt';
        $result = file_put_contents($key_file_path, $key);
        
        if ($result === false) {
            return new WP_REST_Response(array(
                'success' => false,
                'error' => __('Could not write IndexNow key file to site root. Check file permissions.', 'soro-seo'),
            ), 500);
        }
        
        // Delete old key file if exists
        $old_key = get_option('soro_indexnow_key');
        if ($old_key && $old_key !== $key) {
            $old_file = ABSPATH . $old_key . '.txt';
            if (file_exists($old_file)) {
                wp_delete_file($old_file);
            }
        }
        
        // Store the new key
        update_option('soro_indexnow_key', $key);
        
        $key_location = trailingslashit(get_site_url()) . $key . '.txt';
        
        return new WP_REST_Response(array(
            'success' => true,
            'key' => $key,
            'key_location' => $key_location,
            'host' => wp_parse_url(get_site_url(), PHP_URL_HOST),
        ), 200);
    }
    
    /**
     * Get IndexNow status/key if already set up
     */
    public function get_indexnow_status($request) {
        $key = get_option('soro_indexnow_key');
        
        if (!$key) {
            return new WP_REST_Response(array(
                'success' => true,
                'configured' => false,
            ), 200);
        }
        
        // Verify key file still exists
        $key_file_path = ABSPATH . $key . '.txt';
        $file_exists = file_exists($key_file_path);
        
        return new WP_REST_Response(array(
            'success' => true,
            'configured' => $file_exists,
            'key' => $file_exists ? $key : null,
            'key_location' => $file_exists ? trailingslashit(get_site_url()) . $key . '.txt' : null,
            'host' => wp_parse_url(get_site_url(), PHP_URL_HOST),
        ), 200);
    }
}

new SoroConnector();

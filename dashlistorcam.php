<?php
/**
 * Plugin Name: Dashlistorc
 * Plugin URI:  
 * Description: Plugin Painel Lista de Orçamentos
 * Version: 1.0.1
 * Author: Ricardo Silvand
 * Text Domain: dashlistorcam
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// PARTE 00 - Prevenção de acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// PARTE 01 - Definição de constantes do plugin
define('DASHLISTORCAM_VERSION', '1.0.1');
define('DASHLISTORCAM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DASHLISTORCAM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('DASHLISTORCAM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('DASHLISTORCAM_ICON_URL', DASHLISTORCAM_PLUGIN_URL . 'assets/images/dashb.svg');

// PARTE 02 - Carregar classes necessárias
require_once DASHLISTORCAM_PLUGIN_PATH . 'includes/class-dashlistorcam-admin.php';
require_once DASHLISTORCAM_PLUGIN_PATH . 'includes/class-dashlistorcam-dashboard.php';
require_once DASHLISTORCAM_PLUGIN_PATH . 'includes/class-dashlistorcam-login.php';
require_once DASHLISTORCAM_PLUGIN_PATH . 'includes/class-dashlistorcam-ajax.php';

// PARTE 03 - Classe principal do plugin
class Dashlistorcam_Plugin {
    
    // PARTE 04 - Instância única (Singleton)
    private static $instance = null;
    
    // PARTE 05 - Instâncias das classes
    private $admin;
    private $dashboard;
    private $login;
    private $ajax;
    
    // PARTE 06 - Construtor privado
    private function __construct() {
        $this->init_components();
        $this->init_hooks();
    }
    
    // PARTE 07 - Obter instância única
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // PARTE 08 - Inicializar componentes
    private function init_components() {
        $this->admin = new Dashlistorcam_Admin();
        $this->dashboard = new Dashlistorcam_Dashboard();
        $this->login = new Dashlistorcam_Login();
        $this->ajax = new Dashlistorcam_Ajax();
    }
    
    // PARTE 09 - Inicializar hooks e ações
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init_plugin'));
    }
    
    // PARTE 10 - Ativação do plugin
    public function activate() {
        // Criar tabelas ou opções necessárias
        if (!get_option('dashlistorcam_settings')) {
            $default_settings = array(
                'plugin_active' => true,
                'products_page_url' => '',
                'allowed_users' => array(),
                'dashboard_logo' => '',
                'menu_bg_color' => '#2c5aa0',
                'menu_text_color' => '#ffffff',
                'menu_hover_bg_color' => '#1e4080',
                'menu_hover_text_color' => '#ffffff',
                'icon_color' => '#ffffff',
                'icon_bg_color' => 'transparent',
                'login_logo' => '',
                'login_bg_color' => '#f8f9fa',
                'login_box_bg_color' => '#ffffff',
                'login_box_text_color' => '#2c5aa0',
                'login_field_text_color' => '#495057',
                'login_btn_bg_color' => '#2c5aa0',
                'login_btn_text_color' => '#ffffff',
                'login_btn_hover_bg_color' => '#1e4080',
                'login_btn_hover_text_color' => '#ffffff',
            );
            update_option('dashlistorcam_settings', $default_settings);
        }
        flush_rewrite_rules();
    }
    
    // PARTE 11 - Desativação do plugin
    public function deactivate() {
        // Limpar agendamentos ou caches temporários
        flush_rewrite_rules();
    }
    
    // PARTE 12 - Carregar traduções
    public function load_textdomain() {
        load_plugin_textdomain(
            'dashlistorcam',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    // PARTE 13 - Inicializar componentes do plugin
    public function init_plugin() {
        // Inicializar todos os componentes
        $this->admin->init();
        $this->dashboard->init();
        $this->login->init();
        $this->ajax->init();
    }
    
    // PARTE 14 - Obter configurações do plugin
    public static function get_plugin_settings() {
        $default_settings = array(
            'plugin_active' => true,
            'products_page_url' => '',
            'allowed_users' => array(),
            'dashboard_logo' => '',
            'menu_bg_color' => '#2c5aa0',
            'menu_text_color' => '#ffffff',
            'menu_hover_bg_color' => '#1e4080',
            'menu_hover_text_color' => '#ffffff',
            'icon_color' => '#ffffff',
            'icon_bg_color' => 'transparent',
            'login_logo' => '',
            'login_bg_color' => '#f8f9fa',
            'login_box_bg_color' => '#ffffff',
            'login_box_text_color' => '#2c5aa0',
            'login_field_text_color' => '#495057',
            'login_btn_bg_color' => '#2c5aa0',
            'login_btn_text_color' => '#ffffff',
            'login_btn_hover_bg_color' => '#1e4080',
            'login_btn_hover_text_color' => '#ffffff',
        );
        
        $saved_settings = get_option('dashlistorcam_settings', array());
        
        // Garantir que todas as chaves existam
        return array_merge($default_settings, $saved_settings);
    }
}

// PARTE 15 - Inicializar o plugin
function dashlistorcam_init() {
    return Dashlistorcam_Plugin::get_instance();
}

// PARTE 16 - Executar o plugin
add_action('plugins_loaded', 'dashlistorcam_init');

?>
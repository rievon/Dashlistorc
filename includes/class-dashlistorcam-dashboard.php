<?php
// PARTE 00 - Prevenção de acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// PARTE 01 - Classe do Dashboard Frontend
class Dashlistorcam_Dashboard {
    
    // PARTE 02 - Construtor
    public function __construct() {
        // Inicialização básica
    }
    
    // PARTE 03 - Inicializar hooks do dashboard
    public function init() {
        // Registrar shortcodes
        add_shortcode('dashlistorc_dashboard', array($this, 'display_frontend_dashboard'));
        
        // Carregar scripts no frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    // PARTE 04 - Carregar scripts no frontend para o dashboard
    public function enqueue_frontend_scripts() {
        global $post;
        
        // Método robusto para detectar o shortcode
        $has_shortcode = false;
        
        if (is_a($post, 'WP_Post')) {
            // Verificar no conteúdo principal
            if (has_shortcode($post->post_content, 'dashlistorc_dashboard')) {
                $has_shortcode = true;
            }
            
            // Verificar via busca direta no conteúdo
            if (strpos($post->post_content, '[dashlistorc_dashboard]') !== false) {
                $has_shortcode = true;
            }
            
            // Verificar em blocos Gutenberg
            if (function_exists('has_blocks') && has_blocks($post->post_content)) {
                if (strpos($post->post_content, 'dashlistorc_dashboard') !== false) {
                    $has_shortcode = true;
                }
            }
        }
        
        if ($has_shortcode) {
            // CSS do Dashboard
            wp_enqueue_style(
                'dashlistorcam-dashboard-css',
                DASHLISTORCAM_PLUGIN_URL . 'assets/css/dashboard.css',
                array(),
                DASHLISTORCAM_VERSION
            );
            
            // JavaScript do Dashboard
            wp_enqueue_script(
                'dashlistorcam-dashboard-js',
                DASHLISTORCAM_PLUGIN_URL . 'assets/js/dashboard.js',
                array('jquery'),
                DASHLISTORCAM_VERSION,
                true
            );
            
            // Passar variáveis para o JavaScript do frontend
            wp_localize_script('dashlistorcam-dashboard-js', 'dashlistorcVars', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dashlistorcam_frontend_nonce'),
                'placeholderImage' => WC_PLUGIN_URL . '/assets/images/placeholder.png'
            ));
        }
    }
    
    // PARTE 05 - Funções auxiliares para o dashboard
    private function get_current_username() {
        $current_user = wp_get_current_user();
        return $current_user->display_name ?: $current_user->user_login;
    }

    // PARTE 06 - Obter avatar do usuário
    private function get_user_avatar() {
        $current_user = wp_get_current_user();
        $avatar_size = 32;
        
        if (function_exists('get_avatar')) {
            return get_avatar($current_user->ID, $avatar_size);
        }
        
        // Fallback para avatar simples
        return '<div class="dashlistorc-default-avatar" style="width: '.$avatar_size.'px; height: '.$avatar_size.'px; background: #2c5aa0; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">'
               . strtoupper(substr($this->get_current_username(), 0, 1))
               . '</div>';
    }

    // PARTE 07 - Verificar se usuário atual tem acesso ao dashboard
    private function current_user_has_access() {
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        $current_user_id = get_current_user_id();
        
        // Se plugin está inativo, negar acesso
        if (!$settings['plugin_active']) {
            return false;
        }
        
        // Se não há usuários selecionados, permitir acesso a todos os admins
        if (empty($settings['allowed_users'])) {
            return current_user_can('manage_options');
        }
        
        // Verificar se o usuário atual está na lista de permitidos
        return in_array($current_user_id, $settings['allowed_users']);
    }

    // PARTE 08 - Exibir dashboard no frontend
    public function display_frontend_dashboard($atts = array()) {
        // PARTE 08.1 - Verificar se plugin está ativo e usuário tem acesso
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        
        if (!$settings['plugin_active']) {
            return '<div class="dashlistorc-access-denied"><p>' . __('O plugin Dashlistorc está temporariamente desativado.', 'dashlistorcam') . '</p></div>';
        }
        
        // Verificar se usuário atual tem acesso
        if (!$this->current_user_has_access()) {
            return '<div class="dashlistorc-access-denied"><p>' . __('Você não tem permissão para acessar este dashboard.', 'dashlistorcam') . '</p></div>';
        }
        
        // Verificar se usuário está logado
        if (!is_user_logged_in()) {
            return '<div class="dashlistorc-access-denied"><p>' . __('Você precisa estar logado para acessar o dashboard.', 'dashlistorcam') . '</p></div>';
        }
        
        // Iniciar buffer de output
        ob_start();
        ?>
        
        <!-- PARTE 08.2 - Container principal do dashboard -->
        <div class="dashlistorc-frontend-dashboard" id="dashlistorc-frontend-dashboard">
            
            <!-- PARTE 08.3 - Menu Lateral -->
            <div class="dashlistorc-sidebar" style="background: <?php echo esc_attr($settings['menu_bg_color']); ?>; color: <?php echo esc_attr($settings['menu_text_color']); ?>;">
                
                <!-- PARTE 08.4 - TEXTO: Painel Vetmat -->
                <div class="dashlistorc-sidebar-title">
                    <div class="dashlistorc-title-text">Painel Vetmat</div>
                </div>
                
                <!-- PARTE 08.5 - DIVISÓRIA 1 -->
                <div class="dashlistorc-sidebar-divider"></div>
                
                <!-- PARTE 08.6 - LOGO PRINCIPAL -->
                <div class="dashlistorc-sidebar-logo">
                    <?php if ($settings['dashboard_logo']) : ?>
                        <img src="<?php echo esc_url($settings['dashboard_logo']); ?>" alt="Dashlistorc Logo" class="dashlistorc-logo">
                    <?php else : ?>
                        <div class="dashlistorc-logo-placeholder">Logo</div>
                    <?php endif; ?>
                </div>
                
                <!-- PARTE 08.7 - DIVISÓRIA 2 -->
                <div class="dashlistorc-sidebar-divider"></div>
                
                <!-- PARTE 08.8 - Menu de Navegação -->
                <nav class="dashlistorc-sidebar-nav">
                    <ul class="dashlistorc-nav-list">
                        <!-- PARTE 08.9 - Item Dashboard -->
                        <li class="dashlistorc-nav-item dashlistorc-nav-active">
                            <a href="#dashboard" class="dashlistorc-nav-link" data-tab="dashboard">
                                <span class="dashlistorc-nav-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                </span>
                                <span class="dashlistorc-nav-text"><?php _e('Dashboard', 'dashlistorcam'); ?></span>
                            </a>
                        </li>
                        
                        <!-- PARTE 08.10 - Item Lista de Produtos -->
                        <li class="dashlistorc-nav-item">
                            <a href="#produtos" class="dashlistorc-nav-link" data-tab="produtos">
                                <span class="dashlistorc-nav-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="3" width="7" height="7"></rect>
                                        <rect x="14" y="14" width="7" height="7"></rect>
                                        <rect x="3" y="14" width="7" height="7"></rect>
                                    </svg>
                                </span>
                                <span class="dashlistorc-nav-text"><?php _e('Lista de Produtos', 'dashlistorcam'); ?></span>
                            </a>
                        </li>
                        
                        <!-- PARTE 08.11 - Item Adicionar Produto -->
                        <li class="dashlistorc-nav-item">
                            <a href="#adicionar" class="dashlistorc-nav-link" data-tab="adicionar">
                                <span class="dashlistorc-nav-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <line x1="12" y1="5" x2="12" y2="19"></line>
                                        <line x1="5" y1="12" x2="19" y2="12"></line>
                                    </svg>
                                </span>
                                <span class="dashlistorc-nav-text"><?php _e('Adicionar Produto', 'dashlistorcam'); ?></span>
                            </a>
                        </li>
                        
                        <!-- PARTE 08.12 - Item Relatório -->
                        <li class="dashlistorc-nav-item">
                            <a href="#relatorio" class="dashlistorc-nav-link" data-tab="relatorio">
                                <span class="dashlistorc-nav-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"></path>
                                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                        <line x1="12" y1="22.08" x2="12" y2="12"></line>
                                    </svg>
                                </span>
                                <span class="dashlistorc-nav-text"><?php _e('Relatório', 'dashlistorcam'); ?></span>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <!-- PARTE 08.13 - DIVISÓRIA 3 -->
                <div class="dashlistorc-sidebar-divider"></div>
                
                <!-- PARTE 08.14 - Rodapé do Menu COM BOTÃO SAIR -->
                <div class="dashlistorc-sidebar-footer">
                    <button class="dashlistorc-logout-btn" id="sidebar-logout-btn">
                        <span class="dashlistorc-logout-icon">
                            <img src="<?php echo DASHLISTORCAM_PLUGIN_URL . 'assets/images/iconsairbranco.svg'; ?>" alt="<?php _e('Sair', 'dashlistorcam'); ?>" class="dashlistorc-logout-svg">
                        </span>
                        <span class="dashlistorc-logout-text"><?php _e('Sair', 'dashlistorcam'); ?></span>
                    </button>
                </div>
            </div>

            <!-- PARTE 08.15 - TOGGLE SETA FLUTUANTE ENTRE MENUS -->
            <button class="dashlistorc-floating-toggle" id="floating-toggle">
                <img src="<?php echo DASHLISTORCAM_PLUGIN_URL . 'assets/images/setatoglerecolher.svg'; ?>" alt="<?php _e('Recolher Menu', 'dashlistorcam'); ?>" class="dashlistorc-toggle-icon" id="toggle-icon">
            </button>
            
            <!-- PARTE 08.16 - Área de Conteúdo Principal -->
            <div class="dashlistorc-main-content">
                
                <!-- PARTE 08.17 - Header do Conteúdo SUPERIOR -->
                <header class="dashlistorc-content-header">
                    <div class="dashlistorc-header-left">
                        <h1 class="dashlistorc-page-title"><?php _e('Dashboard', 'dashlistorcam'); ?></h1>
                    </div>
                    <div class="dashlistorc-header-right">
                        <div class="dashlistorc-user-info-top">
                            <div class="dashlistorc-user-avatar-top">
                                <?php echo $this->get_user_avatar(); ?>
                            </div>
                            <div class="dashlistorc-user-details-top">
                                <span class="dashlistorc-user-greeting-top"><?php _e('Olá,', 'dashlistorcam'); ?> <?php echo esc_html($this->get_current_username()); ?></span>
                                <span class="dashlistorc-user-welcome-top"><?php _e('Bem-vindo!', 'dashlistorcam'); ?></span>
                            </div>
                            <button class="dashlistorc-logout-btn-top" id="top-logout-btn">
                                <img src="<?php echo DASHLISTORCAM_PLUGIN_URL . 'assets/images/iconsairbranco.svg'; ?>" alt="<?php _e('Sair', 'dashlistorcam'); ?>" class="dashlistorc-logout-svg-top">
                            </button>
                        </div>
                    </div>
                </header>
                
                <!-- PARTE 08.18 - Conteúdo das Abas -->
                <div class="dashlistorc-content-area">
                    
                    <!-- PARTE 08.19 - Aba Dashboard (Ativa por padrão) -->
                    <div class="dashlistorc-tab-content active" id="tab-content-dashboard">
                        
                        <!-- PARTE 08.20 - CONTEÚDO CENTRALIZADO EM BRANCO -->
                        <div class="dashlistorc-content-placeholder">
                            <div class="dashlistorc-placeholder-content">
                                <h2><?php _e('AQUI SERÁ O CONTEÚDO', 'dashlistorcam'); ?></h2>
                                <p><?php _e('Área principal do dashboard onde o conteúdo será exibido', 'dashlistorcam'); ?></p>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- PARTE 08.21 - Aba Lista de Produtos -->
                    <div class="dashlistorc-tab-content" id="tab-content-produtos">
                        <div class="dashlistorc-products-container">
                            <div class="dashlistorc-products-header">
                                <h2 class="dashlistorc-products-title"><?php _e('Lista de Produtos', 'dashlistorcam'); ?></h2>
                                <div class="dashlistorc-search-container">
                                    <input type="text" 
                                           class="dashlistorc-search-input" 
                                           id="products-search" 
                                           placeholder="<?php _e('Buscar por ID ou nome...', 'dashlistorcam'); ?>">
                                    <div class="dashlistorc-search-icon">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <path d="m21 21-4.3-4.3"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dashlistorc-products-content" id="products-content">
                                <div class="dashlistorc-loading-state">
                                    <div class="dashlistorc-loading-spinner"></div>
                                    <?php _e('Carregando produtos...', 'dashlistorcam'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PARTE 08.22 - Aba Adicionar Produto -->
                    <div class="dashlistorc-tab-content" id="tab-content-adicionar">
                        <div class="dashlistorc-content-placeholder">
                            <div class="dashlistorc-placeholder-content">
                                <h2><?php _e('ADICIONAR PRODUTO', 'dashlistorcam'); ?></h2>
                                <p><?php _e('Formulário para adicionar novos produtos será exibido aqui', 'dashlistorcam'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PARTE 08.23 - Aba Relatório -->
                    <div class="dashlistorc-tab-content" id="tab-content-relatorio">
                        <div class="dashlistorc-content-placeholder">
                            <div class="dashlistorc-placeholder-content">
                                <h2><?php _e('RELATÓRIO', 'dashlistorcam'); ?></h2>
                                <p><?php _e('Relatórios e análises serão exibidos aqui', 'dashlistorcam'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        // Retornar o conteúdo
        return ob_get_clean();
    }
}
?>
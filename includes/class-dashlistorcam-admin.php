<?php
// PARTE 00 - Prevenção de acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// PARTE 01 - Classe do Painel Administrativo
class Dashlistorcam_Admin {
    
    // PARTE 02 - Construtor
    public function __construct() {
        // Inicialização básica
    }
    
    // PARTE 03 - Inicializar hooks do admin
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        
        // PARTE 03.1 - Adicionar link de configurações na lista de plugins
        add_filter('plugin_action_links_' . DASHLISTORCAM_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }
    
    // PARTE 04 - Carregar estilos e scripts administrativos
    public function enqueue_admin_styles($hook) {
        // Carrega apenas nas páginas do plugin
        if (strpos($hook, 'dashlistorcam') !== false) {
            wp_enqueue_style(
                'dashlistorcam-admin-css',
                DASHLISTORCAM_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                DASHLISTORCAM_VERSION
            );
            
            wp_enqueue_script(
                'dashlistorcam-admin-js',
                DASHLISTORCAM_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                DASHLISTORCAM_VERSION,
                true
            );
            
            // Passar variáveis para o JavaScript
            wp_localize_script('dashlistorcam-admin-js', 'dashlistorcamAdminVars', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dashlistorcam_admin_nonce'),
                'upload_nonce' => wp_create_nonce('dashlistorcam_upload_nonce'),
                'i18n' => array(
                    'uploadSuccess' => __('Logo enviada com sucesso!', 'dashlistorcam'),
                    'uploadError' => __('Erro ao enviar logo.', 'dashlistorcam'),
                    'saveSuccess' => __('Configurações salvas com sucesso!', 'dashlistorcam'),
                    'saveError' => __('Erro ao salvar configurações.', 'dashlistorcam'),
                    'securityError' => __('Erro de segurança. Atualize a página e tente novamente.', 'dashlistorcam')
                )
            ));
            
            // Garantir que a biblioteca de mídia está disponível
            wp_enqueue_media();
        }
        
        // PARTE 04.1 - Carrega o CSS para ajuste do ícone em todo o admin
        wp_add_inline_style('admin-menu', $this->get_menu_icon_css());
    }
    
    // PARTE 05 - CSS inline para o ícone do menu
    private function get_menu_icon_css() {
        return "
        #adminmenu .toplevel_page_dashlistorcam-dashboard .wp-menu-image img {
            width: 20px !important;
            height: 20px !important;
            max-width: 20px !important;
            max-height: 20px !important;
            opacity: 0.6;
            transition: opacity 0.3s;
        }
        #adminmenu .toplevel_page_dashlistorcam-dashboard:hover .wp-menu-image img,
        #adminmenu .toplevel_page_dashlistorcam-dashboard.current .wp-menu-image img {
            opacity: 1;
        }
        ";
    }
    
    // PARTE 06 - Adicionar menu administrativo e submenus
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            __('Dashlistorc - Lista de Orçamentos', 'dashlistorcam'),
            __('Dashlistorc', 'dashlistorcam'),
            'manage_options',
            'dashlistorcam-dashboard',
            array($this, 'display_dashboard_page'),
            DASHLISTORCAM_ICON_URL,
            30
        );
        
        // PARTE 06.1 - Submenu Dashboard
        add_submenu_page(
            'dashlistorcam-dashboard',
            __('Dashboard - Dashlistorc', 'dashlistorcam'),
            __('Dashboard', 'dashlistorcam'),
            'manage_options',
            'dashlistorcam-dashboard',
            array($this, 'display_dashboard_page')
        );
        
        // PARTE 06.2 - Submenu Configurações
        add_submenu_page(
            'dashlistorcam-dashboard',
            __('Configurações - Dashlistorc', 'dashlistorcam'),
            __('Configurações', 'dashlistorcam'),
            'manage_options',
            'dashlistorcam-settings',
            array($this, 'display_settings_page')
        );
    }
    
    // PARTE 07 - Exibir página do dashboard administrativo
    public function display_dashboard_page() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'dashlistorcam'));
        }
        ?>
        <div class="wrap dashlistorcam-admin-wrap">
            <div class="dashlistorcam-header">
                <h1><?php _e('Dashlistorc - Lista de Orçamentos', 'dashlistorcam'); ?></h1>
                <p><?php _e('Gerencie seus orçamentos de forma eficiente.', 'dashlistorcam'); ?></p>
            </div>
            <div class="dashlistorcam-container">
                <div class="dashlistorcam-card">
                    <h3><?php _e('Bem-vindo ao Dashlistorc!', 'dashlistorcam'); ?></h3>
                    <p><?php _e('Use o menu lateral para acessar as configurações do plugin.', 'dashlistorcam'); ?></p>
                    
                    <div class="dashlistorcam-quick-stats">
                        <div class="dashlistorcam-stat-item">
                            <span class="dashlistorcam-stat-number">1.0.1</span>
                            <span class="dashlistorcam-stat-label"><?php _e('Versão do Plugin', 'dashlistorcam'); ?></span>
                        </div>
                        <div class="dashlistorcam-stat-item">
                            <span class="dashlistorcam-stat-number"><?php echo $this->count_allowed_users(); ?></span>
                            <span class="dashlistorcam-stat-label"><?php _e('Usuários com Acesso', 'dashlistorcam'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // PARTE 07.1 - Contar usuários com acesso
    private function count_allowed_users() {
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        return count($settings['allowed_users']);
    }
    
    // PARTE 08 - Exibir página de configurações com sistema de abas
    public function display_settings_page() {
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_die(__('Você não tem permissão para acessar esta página.', 'dashlistorcam'));
        }
        
        // URLs das imagens das abas
        $icon_geral = DASHLISTORCAM_PLUGIN_URL . 'assets/images/configdashgeral.svg';
        $icon_dashboard = DASHLISTORCAM_PLUGIN_URL . 'assets/images/dashconfig.svg';
        $icon_login = DASHLISTORCAM_PLUGIN_URL . 'assets/images/configlogin.svg';
        ?>
        <div class="wrap dashlistorcam-admin-wrap">
            <div class="dashlistorcam-header">
                <h1><?php _e('Configurações - Dashlistorc', 'dashlistorcam'); ?></h1>
                <p><?php _e('Configure seu plugin com segurança e precisão', 'dashlistorcam'); ?></p>
            </div>
            
            <div class="security-badge">
                <?php _e('Sistema Seguro - Todas as configurações são protegidas', 'dashlistorcam'); ?>
            </div>
            
            <div class="dashlistorcam-container">
                <!-- PARTE 08.1 - Sistema de Abas -->
                <div class="dashlistorcam-tabs">
                    <button class="dashlistorcam-tab active" data-tab="geral">
                        <img src="<?php echo esc_url($icon_geral); ?>" class="dashlistorcam-tab-icon" alt="">
                        <?php _e('Configurações Gerais', 'dashlistorcam'); ?>
                    </button>
                    
                    <button class="dashlistorcam-tab" data-tab="dashboard">
                        <img src="<?php echo esc_url($icon_dashboard); ?>" class="dashlistorcam-tab-icon" alt="">
                        <?php _e('Configurações Dashboard', 'dashlistorcam'); ?>
                    </button>
                    
                    <button class="dashlistorcam-tab" data-tab="login">
                        <img src="<?php echo esc_url($icon_login); ?>" class="dashlistorcam-tab-icon" alt="">
                        <?php _e('Configurações Login', 'dashlistorcam'); ?>
                    </button>
                </div>
                
                <!-- PARTE 08.2 - Conteúdo da Aba 1 - Configurações Gerais -->
                <div class="dashlistorcam-tab-content active" id="tab-geral">
                    <?php $this->display_general_settings_tab(); ?>
                </div>
                
                <!-- PARTE 08.3 - Conteúdo da Aba 2 - Configurações Dashboard -->
                <div class="dashlistorcam-tab-content" id="tab-dashboard">
                    <?php $this->display_dashboard_settings_tab(); ?>
                </div>
                
                <!-- PARTE 08.4 - Conteúdo da Aba 3 - Configurações Login -->
                <div class="dashlistorcam-tab-content" id="tab-login">
                    <?php $this->display_login_settings_tab(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    // PARTE 09 - Conteúdo da Aba Configurações Gerais
    private function display_general_settings_tab() {
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        $products_url = $settings['products_page_url'];
        ?>
        <form id="dashlistorcam-settings-form">
            <input type="hidden" name="action" value="dashlistorcam_save_settings">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('dashlistorcam_save_settings'); ?>">
            
            <!-- Informações do Plugin -->
            <div class="dashlistorcam-card">
                <h3>
                    <img src="<?php echo DASHLISTORCAM_PLUGIN_URL . 'assets/images/configdashgeral.svg'; ?>" width="20" alt="">
                    <?php _e('Informações do Plugin', 'dashlistorcam'); ?>
                </h3>
                
                <div class="dashlistorcam-form-group">
                    <p><strong><?php _e('Descrição:', 'dashlistorcam'); ?></strong></p>
                    <p><?php _e('Este plugin foi desenvolvido especificamente para gestão de orçamentos de produtos que não contenham valores monetários, focando na organização e controle de itens e especificações técnicas.', 'dashlistorcam'); ?></p>
                </div>
                
                <div class="dashlistorcam-form-group">
                    <label class="dashlistorcam-label"><?php _e('Nome do Plugin:', 'dashlistorcam'); ?></label>
                    <input type="text" class="dashlistorcam-input" value="Dashlistorc" disabled>
                </div>
                
                <div class="dashlistorcam-form-group">
                    <label class="dashlistorcam-label"><?php _e('Autor:', 'dashlistorcam'); ?></label>
                    <input type="text" class="dashlistorcam-input" value="Ricardo Silvand" disabled>
                </div>
                
                <div class="dashlistorcam-form-group">
                    <label class="dashlistorcam-label"><?php _e('Versão:', 'dashlistorcam'); ?></label>
                    <input type="text" class="dashlistorcam-input" value="1.0.1" disabled>
                </div>
            </div>

            <!-- PARTE 09.1 - Grid de Configurações Rápidas -->
            <div class="dashlistorcam-settings-grid">
                
                <!-- Shortcode do Dashboard -->
                <div class="dashlistorcam-card">
                    <h3><?php _e('Shortcode do Dashboard', 'dashlistorcam'); ?></h3>
                    
                    <div class="dashlistorcam-form-group">
                        <label class="dashlistorcam-label"><?php _e('Use este shortcode para exibir o dashboard:', 'dashlistorcam'); ?></label>
                        
                        <div class="dashlistorcam-shortcode-box">
                            [dashlistorc_dashboard]
                            <button type="button" class="dashlistorcam-copy-btn">
                                <?php _e('Copiar', 'dashlistorcam'); ?>
                            </button>
                        </div>
                        
                        <p><small><?php _e('Clique no botão "Copiar" para copiar para a área de transferência.', 'dashlistorcam'); ?></small></p>
                    </div>
                </div>

                <!-- Shortcode do Login -->
                <div class="dashlistorcam-card">
                    <h3><?php _e('Shortcode do Login', 'dashlistorcam'); ?></h3>
                    
                    <div class="dashlistorcam-form-group">
                        <label class="dashlistorcam-label"><?php _e('Use este shortcode para exibir a tela de login:', 'dashlistorcam'); ?></label>
                        
                        <div class="dashlistorcam-shortcode-box">
                            [dashlistorc_login]
                            <button type="button" class="dashlistorcam-copy-btn">
                                <?php _e('Copiar', 'dashlistorcam'); ?>
                            </button>
                        </div>
                        
                        <p><small><?php _e('Clique no botão "Copiar" para copiar para a área de transferência.', 'dashlistorcam'); ?></small></p>
                    </div>
                </div>

                <!-- Link da Página de Produtos -->
                <div class="dashlistorcam-card">
                    <h3><?php _e('Configurações de Links', 'dashlistorcam'); ?></h3>
                    
                    <div class="dashlistorcam-form-group">
                        <label class="dashlistorcam-label" for="products-page-url">
                            <?php _e('Link da Página de Produtos:', 'dashlistorcam'); ?>
                        </label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="url" 
                                   id="products-page-url" 
                                   name="products_page_url"
                                   class="dashlistorcam-input" 
                                   placeholder="<?php _e('https://exemplo.com/produtos', 'dashlistorcam'); ?>"
                                   value="<?php echo esc_url($products_url); ?>"
                                   style="flex: 1;">
                            <button type="button" id="clear-products-url" class="button button-secondary" style="white-space: nowrap;">
                                <?php _e('Limpar', 'dashlistorcam'); ?>
                            </button>
                        </div>
                        <p><small><?php _e('Este link será usado nos botões internos do dashboard para redirecionar para a página de produtos.', 'dashlistorcam'); ?></small></p>
                    </div>
                </div>
            </div>

            <!-- Acessos Usuários -->
            <div class="dashlistorcam-card">
                <h3><?php _e('Acessos Usuários', 'dashlistorcam'); ?></h3>
                
                <div class="dashlistorcam-form-group">
                    <label class="dashlistorcam-label">
                        <?php _e('Selecione os usuários que terão acesso ao dashboard:', 'dashlistorcam'); ?>
                    </label>
                    
                    <!-- PARTE 09.2 - Container dos usuários -->
                    <div class="dashlistorcam-users-container">
                        
                        <!-- Cabeçalho com ações -->
                        <div class="dashlistorcam-users-header">
                            <h4 class="dashlistorcam-users-title"><?php _e('Usuários com Acesso ao Dashboard', 'dashlistorcam'); ?></h4>
                            <div class="dashlistorcam-users-actions">
                                <button type="button" id="select-all-users" class="dashlistorcam-users-btn">
                                    <?php _e('Selecionar Todos', 'dashlistorcam'); ?>
                                </button>
                                <button type="button" id="deselect-all-users" class="dashlistorcam-users-btn">
                                    <?php _e('Limpar Seleção', 'dashlistorcam'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <?php
                        // Buscar todos os usuários do WordPress
                        $all_users = get_users(array(
                            'fields' => array('ID', 'user_login', 'display_name', 'user_email'),
                            'number' => 1000,
                            'orderby' => 'display_name'
                        ));
                        
                        // Recuperar usuários selecionados
                        $selected_users = isset($settings['allowed_users']) ? $settings['allowed_users'] : array();
                        
                        // Se nenhum usuário selecionado, selecionar admin automaticamente
                        if (empty($selected_users)) {
                            $admin_user = get_user_by('login', 'admin');
                            if ($admin_user) {
                                $selected_users = array($admin_user->ID);
                            }
                        }
                        ?>
                        
                        <!-- Select de usuários -->
                        <div class="dashlistorcam-users-select-container">
                            <select 
                                id="dashlistorcam-allowed-users" 
                                name="allowed_users[]" 
                                multiple 
                                size="6"
                                class="dashlistorcam-users-select"
                            >
                                <?php foreach ($all_users as $user) : 
                                    $user_display = sprintf(
                                        '%s (%s) - %s',
                                        esc_html($user->display_name),
                                        esc_html($user->user_login),
                                        esc_html($user->user_email)
                                    );
                                ?>
                                    <option value="<?php echo esc_attr($user->ID); ?>" 
                                        <?php echo in_array($user->ID, $selected_users) ? 'selected' : ''; ?>>
                                        <?php echo $user_display; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Rodapé com informações -->
                        <div class="dashlistorcam-users-footer">
                            <div class="dashlistorcam-users-counter">
                                <span id="selected-users-count"><?php echo count($selected_users); ?></span>
                                <?php _e('usuário(s) selecionado(s)', 'dashlistorcam'); ?>
                            </div>
                            <div class="dashlistorcam-users-info">
                                <?php if (empty($selected_users)) : ?>
                                    <span class="highlight"><?php _e('Administrador selecionado automaticamente', 'dashlistorcam'); ?></span>
                                <?php else : ?>
                                    <?php _e('Use Ctrl (Windows) ou Cmd (Mac) para selecionar múltiplos usuários', 'dashlistorcam'); ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <p><small><?php _e('Os usuários selecionados poderão acessar o painel do Dashlistorc usando suas credenciais do WordPress.', 'dashlistorcam'); ?></small></p>
                </div>
            </div>

            <!-- Botão Salvar -->
            <div class="dashlistorcam-card" style="text-align: center; background: transparent; border: none;">
                <button type="submit" id="save-settings" class="dashlistorcam-save-btn">
                    <span class="dashlistorcam-loading" style="display: none;"></span>
                    <?php _e('Salvar Configurações', 'dashlistorcam'); ?>
                </button>
            </div>
        </form>
        <?php
    }
    
    // PARTE 10 - Conteúdo da Aba Configurações Dashboard
    private function display_dashboard_settings_tab() {
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        $dashboard_logo = $settings['dashboard_logo'];
        ?>
        <form id="dashlistorcam-dashboard-form">
            <input type="hidden" name="action" value="dashlistorcam_save_settings">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('dashlistorcam_save_settings'); ?>">
            <input type="hidden" id="dashboard_logo" name="dashboard_logo" value="<?php echo esc_url($dashboard_logo); ?>">

            <!-- PARTE 10.1 - Container para Logo e Cores -->
            <div class="dashlistorcam-settings-grid">
                
                <!-- Upload de Logo -->
                <div class="dashlistorcam-card">
                    <h3>
                        <img src="<?php echo DASHLISTORCAM_PLUGIN_URL . 'assets/images/dashconfig.svg'; ?>" width="20" alt="">
                        <?php _e('Logo do Dashboard', 'dashlistorcam'); ?>
                    </h3>
                    
                    <div class="dashlistorcam-logo-section">
                        <!-- Área de Seleção da Biblioteca -->
                        <div class="dashlistorcam-library-upload" id="dashboard-logo-upload-area">
                            <div class="dashlistorcam-upload-content-compact">
                                <div class="dashlistorcam-upload-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <div class="dashlistorcam-upload-text-compact">
                                    <p><strong><?php _e('Selecionar da Biblioteca', 'dashlistorcam'); ?></strong></p>
                                    <button type="button" class="dashlistorcam-library-btn" id="select-dashboard-logo">
                                        <?php _e('Escolher da Biblioteca', 'dashlistorcam'); ?>
                                    </button>
                                </div>
                            </div>
                            <p><small><?php _e('Formatos: JPG, PNG, SVG. Use a biblioteca do WordPress.', 'dashlistorcam'); ?></small></p>
                        </div>

                        <!-- Preview da Logo -->
                        <?php if ($dashboard_logo) : ?>
                        <div class="dashlistorcam-logo-preview-compact" id="dashboard-logo-preview">
                            <div class="dashlistorcam-preview-header">
                                <span><?php _e('Preview:', 'dashlistorcam'); ?></span>
                                <button type="button" class="dashlistorcam-remove-btn-compact" id="remove-dashboard-logo">
                                    <?php _e('Remover', 'dashlistorcam'); ?>
                                </button>
                            </div>
                            <img src="<?php echo esc_url($dashboard_logo); ?>" class="dashlistorcam-logo-image-compact" alt="Logo do Dashboard">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Configurações de Cores do Menu Lateral -->
                <div class="dashlistorcam-card">
                    <h3><?php _e('Cores do Menu Lateral', 'dashlistorcam'); ?></h3>
                    <p class="dashlistorcam-description"><?php _e('Personalize as cores do menu lateral do dashboard.', 'dashlistorcam'); ?></p>
                    
                    <div class="dashlistorcam-color-picker-group-compact">
                        <?php 
                        $color_settings = [
                            'menu_bg_color' => ['Fundo do Menu', '#2c5aa0'],
                            'menu_text_color' => ['Cor do Texto', '#ffffff'],
                            'menu_hover_bg_color' => ['Fundo Hover', '#1e4080'],
                            'menu_hover_text_color' => ['Texto Hover', '#ffffff'],
                            'icon_color' => ['Cor do Ícone', '#ffffff'],
                            'icon_bg_color' => ['Fundo do Ícone', 'transparent']
                        ];
                        
                        foreach ($color_settings as $key => $data) {
                            $this->display_color_picker($key, $data[0], $settings[$key], $data[1]);
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Preview do Dashboard -->
            <div class="dashlistorcam-card">
                <h3><?php _e('Preview do Dashboard', 'dashlistorcam'); ?></h3>
                <p class="dashlistorcam-description"><?php _e('Visualização aproximada das cores aplicadas:', 'dashlistorcam'); ?></p>
                
                <div class="dashlistorcam-dashboard-preview-compact">
                    <div class="dashlistorcam-preview-title"><?php _e('Pré-visualização do Dashboard', 'dashlistorcam'); ?></div>
                    <div class="dashlistorcam-preview-container-compact">
                        <div class="dashlistorcam-preview-sidebar-compact" style="background: <?php echo esc_attr($settings['menu_bg_color']); ?>; color: <?php echo esc_attr($settings['menu_text_color']); ?>;">
                            <div class="dashlistorcam-preview-logo-compact">
                                <?php echo $dashboard_logo ? '<img src="' . esc_url($dashboard_logo) . '" style="max-width: 100%; max-height: 30px;">' : 'LOGO'; ?>
                            </div>
                            <div class="dashlistorcam-preview-menu-item-compact" style="background: <?php echo esc_attr($settings['menu_hover_bg_color']); ?>; color: <?php echo esc_attr($settings['menu_hover_text_color']); ?>;">
                                <?php _e('Dashboard', 'dashlistorcam'); ?>
                            </div>
                            <div class="dashlistorcam-preview-menu-item-compact">
                                <?php _e('Orçamentos', 'dashlistorcam'); ?>
                            </div>
                            <div class="dashlistorcam-preview-menu-item-compact">
                                <?php _e('Empresa', 'dashlistorcam'); ?>
                            </div>
                        </div>
                        <div class="dashlistorcam-preview-content-compact">
                            <h4><?php _e('Área de Conteúdo', 'dashlistorcam'); ?></h4>
                            <p><?php _e('Esta é uma prévia do dashboard com as cores selecionadas.', 'dashlistorcam'); ?></p>
                            <div class="dashlistorcam-preview-widgets-compact">
                                <div class="dashlistorcam-preview-widget-compact">Widget 1</div>
                                <div class="dashlistorcam-preview-widget-compact">Widget 2</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botão Salvar -->
            <div class="dashlistorcam-card" style="text-align: center; background: transparent; border: none;">
                <button type="submit" id="save-dashboard-settings" class="dashlistorcam-save-btn">
                    <span class="dashlistorcam-loading" style="display: none;"></span>
                    <?php _e('Salvar Configurações do Dashboard', 'dashlistorcam'); ?>
                </button>
            </div>
        </form>
        <?php
    }
    
    // PARTE 11 - Conteúdo da Aba Configurações Login
    private function display_login_settings_tab() {
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        $login_logo = $settings['login_logo'];
        ?>
        <form id="dashlistorcam-login-form">
            <input type="hidden" name="action" value="dashlistorcam_save_settings">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('dashlistorcam_save_settings'); ?>">
            <input type="hidden" id="login_logo" name="login_logo" value="<?php echo esc_url($login_logo); ?>">

            <!-- PARTE 11.1 - Container para Logo e Cores do Login -->
            <div class="dashlistorcam-settings-grid">
                
                <!-- Upload de Logo Login -->
                <div class="dashlistorcam-card">
                    <h3>
                        <img src="<?php echo DASHLISTORCAM_PLUGIN_URL . 'assets/images/configlogin.svg'; ?>" width="20" alt="">
                        <?php _e('Logo da Tela de Login', 'dashlistorcam'); ?>
                    </h3>
                    
                    <div class="dashlistorcam-logo-section">
                        <!-- Área de Seleção da Biblioteca -->
                        <div class="dashlistorcam-library-upload" id="login-logo-upload-area">
                            <div class="dashlistorcam-upload-content-compact">
                                <div class="dashlistorcam-upload-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <div class="dashlistorcam-upload-text-compact">
                                    <p><strong><?php _e('Selecionar da Biblioteca', 'dashlistorcam'); ?></strong></p>
                                    <button type="button" class="dashlistorcam-library-btn" id="select-login-logo">
                                        <?php _e('Escolher da Biblioteca', 'dashlistorcam'); ?>
                                    </button>
                                </div>
                            </div>
                            <p><small><?php _e('Formatos: JPG, PNG, SVG. Use a biblioteca do WordPress.', 'dashlistorcam'); ?></small></p>
                        </div>

                        <!-- Preview da Logo Login -->
                        <?php if ($login_logo) : ?>
                        <div class="dashlistorcam-logo-preview-compact" id="login-logo-preview">
                            <div class="dashlistorcam-preview-header">
                                <span><?php _e('Preview:', 'dashlistorcam'); ?></span>
                                <button type="button" class="dashlistorcam-remove-btn-compact" id="remove-login-logo">
                                    <?php _e('Remover', 'dashlistorcam'); ?>
                                </button>
                            </div>
                            <img src="<?php echo esc_url($login_logo); ?>" class="dashlistorcam-logo-image-compact" alt="Logo da Tela de Login">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- PARTE 11.2 - Cores da Tela de Login -->
                <div class="dashlistorcam-card">
                    <h3><?php _e('Cores da Tela de Login', 'dashlistorcam'); ?></h3>
                    <p class="dashlistorcam-description"><?php _e('Personalize as cores da tela de acesso ao dashboard.', 'dashlistorcam'); ?></p>
                    
                    <div class="dashlistorcam-color-picker-group-compact">
                        <?php 
                        $login_color_settings = [
                            'login_bg_color' => ['Fundo da Tela', '#f8f9fa'],
                            'login_box_bg_color' => ['Fundo do Box', '#ffffff'],
                            'login_box_text_color' => ['Texto do Box', '#2c5aa0'],
                            'login_field_text_color' => ['Texto dos Campos', '#495057'],
                            'login_btn_bg_color' => ['Fundo do Botão', '#2c5aa0'],
                            'login_btn_text_color' => ['Texto do Botão', '#ffffff'],
                            'login_btn_hover_bg_color' => ['Fundo Hover', '#1e4080'],
                            'login_btn_hover_text_color' => ['Texto Hover', '#ffffff']
                        ];
                        
                        foreach ($login_color_settings as $key => $data) {
                            $this->display_color_picker($key, $data[0], $settings[$key], $data[1]);
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- PARTE 11.3 - Preview da Tela de Login -->
            <div class="dashlistorcam-card">
                <h3><?php _e('Preview da Tela de Login', 'dashlistorcam'); ?></h3>
                <p class="dashlistorcam-description"><?php _e('Visualização aproximada da tela de login:', 'dashlistorcam'); ?></p>
                
                <div class="dashlistorcam-login-preview-compact">
                    <div class="dashlistorcam-preview-title"><?php _e('Pré-visualização do Login', 'dashlistorcam'); ?></div>
                    <div class="dashlistorcam-login-preview-container-compact" style="background: <?php echo esc_attr($settings['login_bg_color']); ?>;">
                        <div class="dashlistorcam-login-box-compact" style="background: <?php echo esc_attr($settings['login_box_bg_color']); ?>; color: <?php echo esc_attr($settings['login_box_text_color']); ?>;">
                            <div class="dashlistorcam-login-logo-compact">
                                <?php echo $login_logo ? '<img src="' . esc_url($login_logo) . '" style="max-width: 100%; max-height: 40px;">' : 'LOGO'; ?>
                            </div>
                            <div class="dashlistorcam-login-form-compact">
                                <div class="dashlistorcam-login-field-compact">
                                    <input type="text" placeholder="Usuário" style="color: <?php echo esc_attr($settings['login_field_text_color']); ?>; border: 1px solid #e9ecef; padding: 8px; width: 100%; border-radius: 4px; margin-bottom: 8px;">
                                </div>
                                <div class="dashlistorcam-login-field-compact">
                                    <input type="password" placeholder="Senha" style="color: <?php echo esc_attr($settings['login_field_text_color']); ?>; border: 1px solid #e9ecef; padding: 8px; width: 100%; border-radius: 4px; margin-bottom: 12px;">
                                </div>
                                <button type="button" class="dashlistorcam-login-btn-compact" style="background: <?php echo esc_attr($settings['login_btn_bg_color']); ?>; color: <?php echo esc_attr($settings['login_btn_text_color']); ?>; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                    <?php _e('Entrar', 'dashlistorcam'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PARTE 11.4 - Botão Salvar -->
            <div class="dashlistorcam-card" style="text-align: center; background: transparent; border: none;">
                <button type="submit" id="save-login-settings" class="dashlistorcam-save-btn">
                    <span class="dashlistorcam-loading" style="display: none;"></span>
                    <?php _e('Salvar Configurações de Login', 'dashlistorcam'); ?>
                </button>
            </div>
        </form>
        <?php
    }
    
    // PARTE 12 - Helper para exibir color picker
    private function display_color_picker($name, $title, $value, $default) {
        $is_transparent = ($default === 'transparent');
        $button_class = $is_transparent ? 'dashlistorcam-clear-color-compact transparent' : 'dashlistorcam-clear-color-compact';
        $button_text = $is_transparent ? __('Transparente', 'dashlistorcam') : __('Padrão', 'dashlistorcam');
        ?>
        <div class="dashlistorcam-color-item-compact">
            <div class="dashlistorcam-color-header-compact">
                <div class="dashlistorcam-color-icon-compact" style="background: <?php echo esc_attr($value); ?>; <?php echo $is_transparent ? 'border: 1px solid #e9ecef;' : ''; ?>"></div>
                <span class="dashlistorcam-color-title-compact"><?php echo esc_html($title); ?></span>
            </div>
            <div class="dashlistorcam-color-preview-compact" style="background: <?php echo esc_attr($value); ?>" data-target="<?php echo esc_attr($name); ?>"></div>
            <input type="text" class="dashlistorcam-color-input-compact" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" readonly>
            <button type="button" class="<?php echo $button_class; ?>" data-target="<?php echo esc_attr($name); ?>" data-default="<?php echo esc_attr($default); ?>">
                <?php echo $button_text; ?>
            </button>
        </div>
        <?php
    }
    
    // PARTE 13 - Adicionar link de configurações na lista de plugins
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=dashlistorcam-settings') . '">' . __('Configurações', 'dashlistorcam') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
?>
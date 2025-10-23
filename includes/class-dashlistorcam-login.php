<?php
// PARTE 00 - Prevenção de acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// PARTE 01 - Classe da Tela de Login
class Dashlistorcam_Login {
    
    // PARTE 02 - Construtor
    public function __construct() {
        // Inicialização básica
    }
    
    // PARTE 03 - Inicializar hooks do login
    public function init() {
        add_action('init', array($this, 'register_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_login_scripts'));
    }
    
    // PARTE 04 - Registrar shortcodes do login
    public function register_shortcodes() {
        add_shortcode('dashlistorc_login', array($this, 'display_frontend_login'));
    }
    
    // PARTE 05 - Carregar scripts para a tela de login
    public function enqueue_login_scripts() {
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'dashlistorc_login')) {
            wp_enqueue_style(
                'dashlistorcam-login-css',
                DASHLISTORCAM_PLUGIN_URL . 'assets/css/login.css',
                array(),
                DASHLISTORCAM_VERSION
            );
            
            wp_enqueue_script(
                'dashlistorcam-login-js',
                DASHLISTORCAM_PLUGIN_URL . 'assets/js/login.js',
                array('jquery'),
                DASHLISTORCAM_VERSION,
                true
            );
            
            // Passar variáveis para o JavaScript do login
            wp_localize_script('dashlistorcam-login-js', 'dashlistorcLoginVars', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('dashlistorcam_login_nonce'),
                'redirect_url' => $this->get_redirect_url()
            ));
        }
    }
    
    // PARTE 06 - Obter URL de redirecionamento após login
    private function get_redirect_url() {
        // Redirecionar para a página do dashboard
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        if (!empty($settings['products_page_url'])) {
            return $settings['products_page_url'];
        }
        
        // Fallback para a página atual
        return home_url($_SERVER['REQUEST_URI']);
    }
    
    // PARTE 07 - Exibir tela de login no frontend
    public function display_frontend_login($atts = array()) {
        // Se usuário já está logado, redirecionar para o dashboard
        if (is_user_logged_in()) {
            return $this->get_already_logged_in_message();
        }
        
        // Iniciar buffer de output
        ob_start();
        
        $settings = Dashlistorcam_Plugin::get_plugin_settings();
        ?>
        
        <!-- PARTE 07.1 - Container principal do login -->
        <div class="dashlistorc-login-container" style="background: <?php echo esc_attr($settings['login_bg_color']); ?>;">
            <div class="dashlistorc-login-box" style="background: <?php echo esc_attr($settings['login_box_bg_color']); ?>; color: <?php echo esc_attr($settings['login_box_text_color']); ?>;">
                
                <!-- PARTE 07.2 - Logo do Login -->
                <div class="dashlistorc-login-logo">
                    <?php if ($settings['login_logo']) : ?>
                        <img src="<?php echo esc_url($settings['login_logo']); ?>" alt="Dashlistorc Logo" class="dashlistorc-login-logo-image">
                    <?php else : ?>
                        <div class="dashlistorc-login-logo-text">Dashlistorc</div>
                    <?php endif; ?>
                </div>
                
                <!-- PARTE 07.3 - Título do Login -->
                <div class="dashlistorc-login-header">
                    <h2><?php _e('Acessar Dashboard', 'dashlistorcam'); ?></h2>
                    <p><?php _e('Use suas credenciais do WordPress', 'dashlistorcam'); ?></p>
                </div>
                
                <!-- PARTE 07.4 - Formulário de Login -->
                <form class="dashlistorc-login-form" id="dashlistorc-login-form">
                    <input type="hidden" name="action" value="dashlistorcam_process_login">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('dashlistorcam_login_nonce'); ?>">
                    <input type="hidden" name="redirect_to" value="<?php echo esc_url($this->get_redirect_url()); ?>">
                    
                    <!-- PARTE 07.5 - Campo Usuário -->
                    <div class="dashlistorc-login-field">
                        <label for="dashlistorc-username" class="dashlistorc-login-label">
                            <?php _e('Usuário', 'dashlistorcam'); ?>
                        </label>
                        <input type="text" 
                               id="dashlistorc-username" 
                               name="username" 
                               class="dashlistorc-login-input" 
                               placeholder="<?php _e('Digite seu usuário', 'dashlistorcam'); ?>"
                               required
                               style="color: <?php echo esc_attr($settings['login_field_text_color']); ?>;">
                    </div>
                    
                    <!-- PARTE 07.6 - Campo Senha -->
                    <div class="dashlistorc-login-field">
                        <label for="dashlistorc-password" class="dashlistorc-login-label">
                            <?php _e('Senha', 'dashlistorcam'); ?>
                        </label>
                        <input type="password" 
                               id="dashlistorc-password" 
                               name="password" 
                               class="dashlistorc-login-input" 
                               placeholder="<?php _e('Digite sua senha', 'dashlistorcam'); ?>"
                               required
                               style="color: <?php echo esc_attr($settings['login_field_text_color']); ?>;">
                    </div>
                    
                    <!-- PARTE 07.7 - Lembrar de mim -->
                    <div class="dashlistorc-login-remember">
                        <label class="dashlistorc-remember-label">
                            <input type="checkbox" name="remember" class="dashlistorc-remember-input">
                            <span class="dashlistorc-remember-text"><?php _e('Lembrar de mim', 'dashlistorcam'); ?></span>
                        </label>
                    </div>
                    
                    <!-- PARTE 07.8 - Botão de Login -->
                    <button type="submit" 
                            class="dashlistorc-login-btn" 
                            style="background: <?php echo esc_attr($settings['login_btn_bg_color']); ?>; color: <?php echo esc_attr($settings['login_btn_text_color']); ?>;">
                        <span class="dashlistorc-login-btn-text"><?php _e('Entrar', 'dashlistorcam'); ?></span>
                        <span class="dashlistorc-login-loading" style="display: none;"></span>
                    </button>
                </form>
                
                <!-- PARTE 07.9 - Mensagens de erro/sucesso -->
                <div class="dashlistorc-login-messages" id="dashlistorc-login-messages"></div>
                
                <!-- PARTE 07.10 - Link de ajuda -->
                <div class="dashlistorc-login-footer">
                    <p class="dashlistorc-login-help">
                        <?php _e('Problemas para acessar? Entre em contato com o administrador.', 'dashlistorcam'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <?php
        // Retornar o conteúdo
        return ob_get_clean();
    }
    
    // PARTE 08 - Mensagem para usuário já logado
    private function get_already_logged_in_message() {
        $current_user = wp_get_current_user();
        $username = $current_user->display_name ?: $current_user->user_login;
        
        ob_start();
        ?>
        <div class="dashlistorc-already-logged-in">
            <div class="dashlistorc-logged-in-message">
                <h3><?php _e('Você já está logado!', 'dashlistorcam'); ?></h3>
                <p><?php printf(__('Olá, %s! Você já está autenticado no sistema.', 'dashlistorcam'), esc_html($username)); ?></p>
                <div class="dashlistorc-logged-in-actions">
                    <a href="<?php echo home_url(); ?>" class="dashlistorc-btn dashlistorc-btn-secondary">
                        <?php _e('Voltar para o site', 'dashlistorcam'); ?>
                    </a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>" class="dashlistorc-btn dashlistorc-btn-primary">
                        <?php _e('Sair', 'dashlistorcam'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // PARTE 09 - Processar login via AJAX (será movido para class-ajax)
    public function process_login() {
        // Esta função será movida para o arquivo AJAX
        // Mantida aqui por compatibilidade temporária
    }
}
?>
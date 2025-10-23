<?php
// PARTE 00 - Prevenção de acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// PARTE 01 - Classe de Handlers AJAX
class Dashlistorcam_Ajax {
    
    // PARTE 02 - Construtor
    public function __construct() {
        // Inicialização básica
    }
    
    // PARTE 03 - Inicializar hooks AJAX
    public function init() {
        // Handlers de configurações
        add_action('wp_ajax_dashlistorcam_save_settings', array($this, 'save_plugin_settings'));
        
        // Handlers de upload
        add_action('wp_ajax_dashlistorcam_upload_logo', array($this, 'handle_logo_upload'));
        add_action('wp_ajax_dashlistorcam_upload_login_logo', array($this, 'handle_login_logo_upload'));
        
        // Handlers de login
        add_action('wp_ajax_nopriv_dashlistorcam_process_login', array($this, 'process_login'));
        add_action('wp_ajax_dashlistorcam_process_login', array($this, 'process_login'));
        
        // Handler de produtos WooCommerce - CORRIGIDO
        add_action('wp_ajax_dashlistorc_get_products', array($this, 'get_woocommerce_products'));
        add_action('wp_ajax_nopriv_dashlistorc_get_products', array($this, 'get_woocommerce_products'));
    }
    
    // PARTE 04 - Salvar configurações via AJAX
    public function save_plugin_settings() {
        // Verificar nonce para segurança
        if (!wp_verify_nonce($_POST['nonce'], 'dashlistorcam_save_settings')) {
            wp_send_json_error(__('Erro de segurança. Atualize a página e tente novamente.', 'dashlistorcam'));
        }
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Você não tem permissão para realizar esta ação.', 'dashlistorcam'));
        }
        
        // Recuperar configurações atuais
        $current_settings = Dashlistorcam_Plugin::get_plugin_settings();
        
        // Preparar novas configurações com validação
        $new_settings = array(
            'plugin_active' => true,
            'products_page_url' => isset($_POST['products_page_url']) ? esc_url_raw($_POST['products_page_url']) : '',
            'allowed_users' => isset($_POST['allowed_users']) ? array_map('intval', $_POST['allowed_users']) : array(),
            'dashboard_logo' => isset($_POST['dashboard_logo']) ? esc_url_raw($_POST['dashboard_logo']) : '',
            'menu_bg_color' => isset($_POST['menu_bg_color']) ? sanitize_hex_color($_POST['menu_bg_color']) : '#2c5aa0',
            'menu_text_color' => isset($_POST['menu_text_color']) ? sanitize_hex_color($_POST['menu_text_color']) : '#ffffff',
            'menu_hover_bg_color' => isset($_POST['menu_hover_bg_color']) ? sanitize_hex_color($_POST['menu_hover_bg_color']) : '#1e4080',
            'menu_hover_text_color' => isset($_POST['menu_hover_text_color']) ? sanitize_hex_color($_POST['menu_hover_text_color']) : '#ffffff',
            'icon_color' => isset($_POST['icon_color']) ? sanitize_hex_color($_POST['icon_color']) : '#ffffff',
            'icon_bg_color' => isset($_POST['icon_bg_color']) ? sanitize_hex_color($_POST['icon_bg_color']) : 'transparent',
            'login_logo' => isset($_POST['login_logo']) ? esc_url_raw($_POST['login_logo']) : '',
            'login_bg_color' => isset($_POST['login_bg_color']) ? sanitize_hex_color($_POST['login_bg_color']) : '#f8f9fa',
            'login_box_bg_color' => isset($_POST['login_box_bg_color']) ? sanitize_hex_color($_POST['login_box_bg_color']) : '#ffffff',
            'login_box_text_color' => isset($_POST['login_box_text_color']) ? sanitize_hex_color($_POST['login_box_text_color']) : '#2c5aa0',
            'login_field_text_color' => isset($_POST['login_field_text_color']) ? sanitize_hex_color($_POST['login_field_text_color']) : '#495057',
            'login_btn_bg_color' => isset($_POST['login_btn_bg_color']) ? sanitize_hex_color($_POST['login_btn_bg_color']) : '#2c5aa0',
            'login_btn_text_color' => isset($_POST['login_btn_text_color']) ? sanitize_hex_color($_POST['login_btn_text_color']) : '#ffffff',
            'login_btn_hover_bg_color' => isset($_POST['login_btn_hover_bg_color']) ? sanitize_hex_color($_POST['login_btn_hover_bg_color']) : '#1e4080',
            'login_btn_hover_text_color' => isset($_POST['login_btn_hover_text_color']) ? sanitize_hex_color($_POST['login_btn_hover_text_color']) : '#ffffff',
        );
        
        // Combinar com configurações existentes
        $updated_settings = array_merge($current_settings, $new_settings);
        
        // Salvar no banco de dados
        if (update_option('dashlistorcam_settings', $updated_settings)) {
            wp_send_json_success(__('Configurações salvas com sucesso!', 'dashlistorcam'));
        } else {
            wp_send_json_error(__('Nenhuma alteração foi detectada ou ocorreu um erro ao salvar.', 'dashlistorcam'));
        }
    }

    // PARTE 05 - Função para upload de logo
    public function handle_logo_upload() {
        // Verificar nonce para segurança
        if (!wp_verify_nonce($_POST['nonce'], 'dashlistorcam_upload_nonce')) {
            wp_send_json_error(__('Erro de segurança. Atualize a página e tente novamente.', 'dashlistorcam'));
        }
        
        // Verificar permissões
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Você não tem permissão para realizar esta ação.', 'dashlistorcam'));
        }
        
        // Verificar se arquivo foi enviado
        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Erro no upload do arquivo.', 'dashlistorcam'));
        }
        
        $file = $_FILES['logo_file'];
        
        // Validar tipo de arquivo
        $file_type = wp_check_filetype($file['name']);
        $allowed_types = array(
            'jpg' => 'image/jpeg', 
            'jpeg' => 'image/jpeg', 
            'png' => 'image/png', 
            'svg' => 'image/svg+xml',
            'gif' => 'image/gif'
        );
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(__('Tipo de arquivo não permitido. Use JPG, PNG, GIF ou SVG.', 'dashlistorcam'));
        }
        
        // Fazer upload do arquivo
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload = wp_handle_upload($file, array(
            'test_form' => false,
            'mimes' => $allowed_types,
        ));
        
        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        }
        
        // Retornar URL do arquivo
        wp_send_json_success(array(
            'url' => $upload['url'],
            'file' => $upload['file']
        ));
    }

    // PARTE 06 - Função para upload de logo do login
    public function handle_login_logo_upload() {
        // Reutilizar a mesma lógica da função handle_logo_upload
        return $this->handle_logo_upload();
    }

    // PARTE 07 - Processar login
    public function process_login() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dashlistorcam_login_nonce')) {
            wp_send_json_error(__('Erro de segurança. Tente novamente.', 'dashlistorcam'));
        }
        
        // Validar campos obrigatórios
        if (empty($_POST['username']) || empty($_POST['password'])) {
            wp_send_json_error(__('Por favor, preencha todos os campos.', 'dashlistorcam'));
        }
        
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) ? true : false;
        
        // Tentar fazer login
        $user = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        ), is_ssl());
        
        if (is_wp_error($user)) {
            wp_send_json_error($user->get_error_message());
        }
        
        // Login bem-sucedido
        wp_send_json_success(array(
            'message' => __('Login realizado com sucesso!', 'dashlistorcam'),
            'redirect_url' => esc_url($_POST['redirect_to'])
        ));
    }

    // PARTE 08 - Buscar produtos do WooCommerce (CORRIGIDA E FUNCIONAL)
    public function get_woocommerce_products() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'dashlistorcam_frontend_nonce')) {
            wp_send_json_error('Erro de segurança. Atualize a página.');
        }

        // PARTE 08.1 - Parâmetros da consulta
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

        // PARTE 08.2 - Tentar buscar produtos reais do WooCommerce
        try {
            // Verificar se WooCommerce está ativo
            if (!class_exists('WooCommerce')) {
                throw new Exception('WooCommerce não está ativo');
            }

            $args = array(
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'paged' => $page,
                'orderby' => 'date',
                'order' => 'DESC',
            );

            // Adicionar busca se houver termo
            if (!empty($search_term)) {
                if (is_numeric($search_term)) {
                    $args['p'] = intval($search_term);
                } else {
                    $args['s'] = $search_term;
                }
            }

            $products_query = new WP_Query($args);
            $products = array();

            if ($products_query->have_posts()) {
                while ($products_query->have_posts()) {
                    $products_query->the_post();
                    $product_id = get_the_ID();
                    $product = wc_get_product($product_id);

                    if (!$product) {
                        continue;
                    }

                    // Imagem do produto
                    $image_id = $product->get_image_id();
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : '';
                    
                    if (!$image_url && class_exists('WooCommerce')) {
                        $image_url = WC()->plugin_url() . '/assets/images/placeholder.png';
                    }

                    // Laboratório
                    $laboratorio = $this->get_product_laboratory($product_id, $product);

                    $products[] = array(
                        'id' => $product_id,
                        'name' => get_the_title(),
                        'image' => $image_url,
                        'laboratorio' => $laboratorio ?: 'Não especificado',
                        'date' => get_the_date('d/m/Y', $product_id),
                        'edit_link' => admin_url('post.php?post=' . $product_id . '&action=edit')
                    );
                }
                wp_reset_postdata();
            }

            // PARTE 08.3 - Retornar produtos encontrados
            wp_send_json_success(array(
                'products' => $products,
                'pagination' => array(
                    'current_page' => $page,
                    'total_pages' => $products_query->max_num_pages,
                    'total_products' => $products_query->found_posts,
                    'per_page' => 10
                ),
                'debug' => array(
                    'query_found_posts' => $products_query->found_posts,
                    'search_term' => $search_term
                )
            ));

        } catch (Exception $e) {
            // PARTE 08.4 - Em caso de erro, retornar dados de exemplo
            $this->return_sample_products();
        }
    }

    // PARTE 09 - Função auxiliar para obter laboratório do produto
    private function get_product_laboratory($product_id, $product) {
        $laboratorio = '';
        
        // Tentar meta field direto
        $laboratorio = get_post_meta($product_id, 'laboratorio', true);
        if (!empty($laboratorio)) return $laboratorio;
        
        // Tentar meta field com underscore
        $laboratorio = get_post_meta($product_id, '_laboratorio', true);
        if (!empty($laboratorio)) return $laboratorio;
        
        // Tentar atributos do produto
        if ($product) {
            $attributes = $product->get_attributes();
            foreach ($attributes as $attribute) {
                if (stripos($attribute->get_name(), 'laboratorio') !== false) {
                    $terms = $attribute->get_options();
                    if (!empty($terms)) {
                        return is_array($terms) ? implode(', ', $terms) : $terms;
                    }
                }
            }
        }
        
        // Tentar taxonomia
        $laboratorio_terms = get_the_terms($product_id, 'pa_laboratorio');
        if ($laboratorio_terms && !is_wp_error($laboratorio_terms)) {
            $names = array();
            foreach ($laboratorio_terms as $term) {
                $names[] = $term->name;
            }
            return implode(', ', $names);
        }
        
        return '';
    }

    // PARTE 10 - Retornar produtos de exemplo
    private function return_sample_products() {
        $placeholder_url = class_exists('WooCommerce') ? 
            WC()->plugin_url() . '/assets/images/placeholder.png' : 
            '/wp-content/plugins/woocommerce/assets/images/placeholder.png';

        $sample_products = array(
            array(
                'id' => 1,
                'name' => 'Produto Exemplo 1',
                'image' => $placeholder_url,
                'laboratorio' => 'Laboratório A',
                'date' => date('d/m/Y'),
                'edit_link' => '#'
            ),
            array(
                'id' => 2,
                'name' => 'Produto Exemplo 2',
                'image' => $placeholder_url,
                'laboratorio' => 'Laboratório B',
                'date' => date('d/m/Y', strtotime('-1 day')),
                'edit_link' => '#'
            ),
            array(
                'id' => 3,
                'name' => 'Produto Exemplo 3',
                'image' => $placeholder_url,
                'laboratorio' => 'Laboratório C',
                'date' => date('d/m/Y', strtotime('-2 days')),
                'edit_link' => '#'
            )
        );

        wp_send_json_success(array(
            'products' => $sample_products,
            'pagination' => array(
                'current_page' => 1,
                'total_pages' => 1,
                'total_products' => 3,
                'per_page' => 10
            ),
            'debug' => array(
                'using_sample_data' => true
            )
        ));
    }
}
?>
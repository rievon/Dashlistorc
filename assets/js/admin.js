// PARTE 01 - JavaScript administrativo do Dashlistorc
jQuery(document).ready(function($) {
    
    // PARTE 02 - Sistema de abas das configurações
    $('.dashlistorcam-tab').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Remove classe active de todas as abas e conteúdos
        $('.dashlistorcam-tab').removeClass('active');
        $('.dashlistorcam-tab-content').removeClass('active');
        
        // Adiciona classe active na aba clicada e seu conteúdo
        $(this).addClass('active');
        $('#tab-' + tabId).addClass('active');
    });
    
    // PARTE 03 - Efeitos visuais de interação
    $('.dashlistorcam-card').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );

    // PARTE 04 - Copiar shortcode
    $('.dashlistorcam-copy-btn').on('click', function(e) {
        e.stopPropagation();
        var $shortcodeBox = $(this).closest('.dashlistorcam-shortcode-box');
        var shortcode = $shortcodeBox.text().trim().replace('Copiar', '');
        
        // Copiar para área de transferência
        var tempInput = $('<textarea>');
        $('body').append(tempInput);
        tempInput.val(shortcode).select();
        
        try {
            var successful = document.execCommand('copy');
            if (successful) {
                // Feedback visual
                var $btn = $(this);
                $btn.addClass('copied').text('Copiado!');
                $shortcodeBox.addClass('copied');
                
                setTimeout(function() {
                    $btn.removeClass('copied').text('Copiar');
                    $shortcodeBox.removeClass('copied');
                }, 2000);
                
                showModal('success', 'Shortcode copiado para a área de transferência!');
            } else {
                throw new Error('Falha ao copiar');
            }
        } catch (err) {
            showModal('error', 'Erro ao copiar shortcode. Selecione e copie manualmente.');
        } finally {
            tempInput.remove();
        }
    });

    // PARTE 05 - Limpar URL de produtos
    $('#clear-products-url').on('click', function() {
        $('#products-page-url').val('').focus();
        showModal('success', 'URL limpa com sucesso!');
    });

    // PARTE 06 - Seleção de usuários
    $('#select-all-users').on('click', function() {
        $('#dashlistorcam-allowed-users option').prop('selected', true);
        updateSelectedUsersCount();
        showModal('success', 'Todos os usuários selecionados!');
    });

    $('#deselect-all-users').on('click', function() {
        $('#dashlistorcam-allowed-users option').prop('selected', false);
        updateSelectedUsersCount();
        showModal('success', 'Seleção de usuários limpa!');
    });

    $('#dashlistorcam-allowed-users').on('change', function() {
        updateSelectedUsersCount();
    });

    function updateSelectedUsersCount() {
        var selectedCount = $('#dashlistorcam-allowed-users option:selected').length;
        $('#selected-users-count').text(selectedCount);
    }

    // PARTE 07 - Biblioteca de Mídia para Logo do Dashboard
    var dashboardMediaFrame;
    
    $('#select-dashboard-logo').on('click', function(e) {
        e.preventDefault();
        
        // Se o frame já existe, reabre
        if (dashboardMediaFrame) {
            dashboardMediaFrame.open();
            return;
        }
        
        // Cria novo frame
        dashboardMediaFrame = wp.media({
            title: 'Selecionar Logo do Dashboard',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // Quando uma imagem é selecionada
        dashboardMediaFrame.on('select', function() {
            var attachment = dashboardMediaFrame.state().get('selection').first().toJSON();
            
            // Atualiza o campo hidden
            $('#dashboard_logo').val(attachment.url);
            
            // Atualiza ou cria o preview
            updateLogoPreview('dashboard', attachment.url);
            
            // Atualiza o preview do dashboard
            updatePreview();
            
            showModal('success', 'Logo do dashboard selecionada com sucesso!');
        });
        
        // Abre o frame
        dashboardMediaFrame.open();
    });
    
    // Remover logo do dashboard
    $(document).on('click', '#remove-dashboard-logo', function() {
        $('#dashboard_logo').val('');
        $('#dashboard-logo-preview').remove();
        showModal('success', 'Logo do dashboard removida com sucesso!');
        updatePreview();
    });

    // PARTE 08 - Biblioteca de Mídia para Logo do Login
    var loginMediaFrame;
    
    $('#select-login-logo').on('click', function(e) {
        e.preventDefault();
        
        // Se o frame já existe, reabre
        if (loginMediaFrame) {
            loginMediaFrame.open();
            return;
        }
        
        // Cria novo frame
        loginMediaFrame = wp.media({
            title: 'Selecionar Logo do Login',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // Quando uma imagem é selecionada
        loginMediaFrame.on('select', function() {
            var attachment = loginMediaFrame.state().get('selection').first().toJSON();
            
            // Atualiza o campo hidden
            $('#login_logo').val(attachment.url);
            
            // Atualiza ou cria o preview
            updateLogoPreview('login', attachment.url);
            
            // Atualiza o preview do login
            updatePreview();
            
            showModal('success', 'Logo do login selecionada com sucesso!');
        });
        
        // Abre o frame
        loginMediaFrame.open();
    });
    
    // Remover logo do login
    $(document).on('click', '#remove-login-logo', function() {
        $('#login_logo').val('');
        $('#login-logo-preview').remove();
        showModal('success', 'Logo do login removida com sucesso!');
        updatePreview();
    });

    // PARTE 09 - Função para atualizar preview das logos
    function updateLogoPreview(type, logoUrl) {
        var previewId = type + '-logo-preview';
        var $previewContainer = $('#' + type + '-logo-upload-area').parent();
        
        // Remove preview existente
        $('#' + previewId).remove();
        
        // Cria novo preview
        var previewHtml = `
            <div class="dashlistorcam-logo-preview-compact" id="${previewId}">
                <div class="dashlistorcam-preview-header">
                    <span>Preview:</span>
                    <button type="button" class="dashlistorcam-remove-btn-compact" id="remove-${type}-logo">
                        Remover
                    </button>
                </div>
                <img src="${logoUrl}" class="dashlistorcam-logo-image-compact" alt="Logo ${type}">
            </div>
        `;
        
        $previewContainer.append(previewHtml);
    }

    // PARTE 10 - Sistema de cores
    $('.dashlistorcam-color-preview-compact').on('click', function() {
        var target = $(this).data('target');
        var currentColor = $('input[name="' + target + '"]').val();
        
        openColorPicker(target, currentColor);
    });

    $('.dashlistorcam-clear-color-compact').on('click', function() {
        var target = $(this).data('target');
        var defaultValue = $(this).data('default');
        
        $('input[name="' + target + '"]').val(defaultValue);
        $('[data-target="' + target + '"]').css('background', defaultValue);
        updatePreview();
        showModal('success', 'Cor redefinida para o valor padrão!');
    });

    // PARTE 11 - Color Picker Modal
    function openColorPicker(target, currentColor) {
        // Verificar se já existe um modal aberto
        if ($('#color-picker-modal').length) {
            $('#color-picker-modal').remove();
        }
        
        var modalHtml = `
            <div class="dashlistorcam-color-picker-modal active" id="color-picker-modal">
                <div class="dashlistorcam-color-picker-overlay"></div>
                <div class="dashlistorcam-color-picker-content">
                    <h3 class="dashlistorcam-color-picker-title">Selecionar Cor</h3>
                    <input type="color" class="dashlistorcam-color-picker-input" value="${currentColor}">
                    <div class="dashlistorcam-color-picker-actions">
                        <button type="button" class="dashlistorcam-color-picker-cancel">Cancelar</button>
                        <button type="button" class="dashlistorcam-color-picker-confirm">Aplicar</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Event handlers do modal
        $('.dashlistorcam-color-picker-overlay, .dashlistorcam-color-picker-cancel').on('click', function() {
            $('#color-picker-modal').remove();
            $(document).off('keyup.colorPicker');
        });
        
        $('.dashlistorcam-color-picker-confirm').on('click', function() {
            var newColor = $('.dashlistorcam-color-picker-input').val();
            $('input[name="' + target + '"]').val(newColor);
            $('[data-target="' + target + '"]').css('background', newColor);
            updatePreview();
            $('#color-picker-modal').remove();
            $(document).off('keyup.colorPicker');
            showModal('success', 'Cor aplicada com sucesso!');
        });
        
        // Focar no input de cor
        $('.dashlistorcam-color-picker-input').focus();
        
        // Fechar com ESC
        $(document).on('keyup.colorPicker', function(e) {
            if (e.keyCode === 27) {
                $('#color-picker-modal').remove();
                $(document).off('keyup.colorPicker');
            }
        });
    }

    // PARTE 12 - Atualizar preview em tempo real
    function updatePreview() {
        // Dashboard Preview
        var menuBgColor = $('input[name="menu_bg_color"]').val();
        var menuTextColor = $('input[name="menu_text_color"]').val();
        var menuHoverBgColor = $('input[name="menu_hover_bg_color"]').val();
        var menuHoverTextColor = $('input[name="menu_hover_text_color"]').val();
        var dashboardLogo = $('#dashboard_logo').val();
        
        $('.dashlistorcam-preview-sidebar-compact').css({
            'background': menuBgColor,
            'color': menuTextColor
        });
        
        $('.dashlistorcam-preview-menu-item-compact').first().css({
            'background': menuHoverBgColor,
            'color': menuHoverTextColor
        });
        
        // Atualizar logo no preview
        var $previewLogo = $('.dashlistorcam-preview-logo-compact');
        if (dashboardLogo) {
            $previewLogo.html('<img src="' + dashboardLogo + '" style="max-width: 100%; max-height: 30px;">');
        } else {
            $previewLogo.text('LOGO');
        }
        
        // Login Preview
        var loginBgColor = $('input[name="login_bg_color"]').val();
        var loginBoxBgColor = $('input[name="login_box_bg_color"]').val();
        var loginBoxTextColor = $('input[name="login_box_text_color"]').val();
        var loginFieldTextColor = $('input[name="login_field_text_color"]').val();
        var loginBtnBgColor = $('input[name="login_btn_bg_color"]').val();
        var loginBtnTextColor = $('input[name="login_btn_text_color"]').val();
        var loginLogo = $('#login_logo').val();
        
        $('.dashlistorcam-login-preview-container-compact').css('background', loginBgColor);
        $('.dashlistorcam-login-box-compact').css({
            'background': loginBoxBgColor,
            'color': loginBoxTextColor
        });
        
        $('.dashlistorcam-login-field-compact input').css('color', loginFieldTextColor);
        $('.dashlistorcam-login-btn-compact').css({
            'background': loginBtnBgColor,
            'color': loginBtnTextColor
        });
        
        // Atualizar logo no preview do login
        var $loginPreviewLogo = $('.dashlistorcam-login-logo-compact');
        if (loginLogo) {
            $loginPreviewLogo.html('<img src="' + loginLogo + '" style="max-width: 100%; max-height: 40px;">');
        } else {
            $loginPreviewLogo.text('LOGO');
        }
    }

    // Inicializar preview
    updatePreview();

    // PARTE 13 - Salvar configurações
    $('#save-settings').on('click', function(e) {
        e.preventDefault();
        saveSettings('dashlistorcam_save_settings', $('#dashlistorcam-settings-form'));
    });

    $('#save-dashboard-settings').on('click', function(e) {
        e.preventDefault();
        saveSettings('dashlistorcam_save_settings', $('#dashlistorcam-dashboard-form'));
    });

    $('#save-login-settings').on('click', function(e) {
        e.preventDefault();
        saveSettings('dashlistorcam_save_settings', $('#dashlistorcam-login-form'));
    });

    function saveSettings(action, $form) {
        var $btn = $form.find('.dashlistorcam-save-btn');
        var $loading = $btn.find('.dashlistorcam-loading');
        
        // Validar formulário antes de enviar
        if (!validateForm($form)) {
            return;
        }
        
        $btn.prop('disabled', true);
        $loading.show();
        
        var formData = $form.serialize();
        formData += '&action=' + action;
        
        // Adicionar nonce específico do formulário
        var nonceField = $form.find('input[name="nonce"]').val();
        if (nonceField) {
            formData += '&nonce=' + nonceField;
        }
        
        $.ajax({
            url: dashlistorcamAdminVars.ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showModal('success', response.data || dashlistorcamAdminVars.i18n.saveSuccess);
                } else {
                    showModal('error', response.data || dashlistorcamAdminVars.i18n.saveError);
                }
            },
            error: function(xhr, status, error) {
                console.error('Save error:', error);
                showModal('error', 'Erro de comunicação: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $loading.hide();
            }
        });
    }

    // PARTE 14 - Validação de formulário
    function validateForm($form) {
        var isValid = true;
        var errors = [];
        
        // Validar URLs
        $form.find('input[type="url"]').each(function() {
            var $input = $(this);
            var value = $input.val().trim();
            
            if (value && !isValidUrl(value)) {
                errors.push('URL inválida em: ' + $input.attr('name'));
                $input.addClass('error');
                isValid = false;
            } else {
                $input.removeClass('error');
            }
        });
        
        // Validar cores hex
        $form.find('input.dashlistorcam-color-input-compact').each(function() {
            var $input = $(this);
            var value = $input.val().trim();
            
            if (value && !isValidHexColor(value)) {
                errors.push('Cor hexadecimal inválida em: ' + $input.attr('name'));
                $input.addClass('error');
                isValid = false;
            } else {
                $input.removeClass('error');
            }
        });
        
        if (!isValid) {
            showModal('error', 'Erros de validação:<br>' + errors.join('<br>'));
        }
        
        return isValid;
    }
    
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    function isValidHexColor(color) {
        return /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(color) || color === 'transparent';
    }

    // PARTE 15 - Modal de feedback
    function showModal(type, message) {
        // Remover modais existentes
        $('.dashlistorcam-modal-overlay').remove();
        
        var icon = type === 'success' ? '✓' : type === 'warning' ? '⚠' : '✕';
        var title = type === 'success' ? 'Sucesso!' : type === 'warning' ? 'Atenção!' : 'Erro!';
        
        var modalHtml = `
            <div class="dashlistorcam-modal-overlay active">
                <div class="dashlistorcam-modal ${type} active">
                    <button class="dashlistorcam-modal-close">&times;</button>
                    <div class="dashlistorcam-modal-icon">${icon}</div>
                    <h3>${title}</h3>
                    <p>${message}</p>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Auto-remove após 3 segundos para success
        if (type === 'success') {
            setTimeout(function() {
                $('.dashlistorcam-modal-overlay').remove();
            }, 3000);
        }
        
        // Event handlers
        $('.dashlistorcam-modal-close, .dashlistorcam-modal-overlay').on('click', function(e) {
            if (e.target === this || $(e.target).hasClass('dashlistorcam-modal-close')) {
                $('.dashlistorcam-modal-overlay').remove();
                $(document).off('keyup.modal');
            }
        });
        
        // Fechar com ESC
        $(document).on('keyup.modal', function(e) {
            if (e.keyCode === 27) {
                $('.dashlistorcam-modal-overlay').remove();
                $(document).off('keyup.modal');
            }
        });
    }

    // PARTE 16 - Inicialização
    updateSelectedUsersCount();
    
    // Atualizar preview quando cores mudam via input
    $('.dashlistorcam-color-input-compact').on('change', function() {
        var target = $(this).attr('name');
        var color = $(this).val();
        $('[data-target="' + target + '"]').css('background', color);
        updatePreview();
    });

    // PARTE 17 - Prevenir envio duplo de formulários
    $('form').on('submit', function(e) {
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        
        if ($submitBtn.prop('disabled')) {
            e.preventDefault();
            return false;
        }
        
        $submitBtn.prop('disabled', true);
        setTimeout(function() {
            $submitBtn.prop('disabled', false);
        }, 3000);
    });

});
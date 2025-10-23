// PARTE 01 - JavaScript da Tela de Login
document.addEventListener('DOMContentLoaded', function() {
    
    // PARTE 02 - Inicializar formulário de login
    function initLoginForm() {
        const loginForm = document.getElementById('dashlistorc-login-form');
        const loginBtn = loginForm?.querySelector('.dashlistorc-login-btn');
        const messagesContainer = document.getElementById('dashlistorc-login-messages');

        if (loginForm && loginBtn) {
            loginForm.addEventListener('submit', handleLoginSubmit);
            
            // Adicionar validação em tempo real
            initRealTimeValidation(loginForm);
        }
    }

    // PARTE 03 - Manipular envio do formulário de login
    function handleLoginSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const loginBtn = form.querySelector('.dashlistorc-login-btn');
        const messagesContainer = document.getElementById('dashlistorc-login-messages');
        
        // Validar formulário
        if (!validateLoginForm(form)) {
            return;
        }
        
        // Mostrar loading
        setLoadingState(loginBtn, true);
        clearMessages(messagesContainer);
        
        // Preparar dados do formulário
        const formData = new FormData(form);
        
        // Enviar via AJAX
        sendLoginRequest(formData)
            .then(handleLoginSuccess)
            .catch(handleLoginError)
            .finally(() => {
                setLoadingState(loginBtn, false);
            });
    }

    // PARTE 04 - Validação do formulário de login
    function validateLoginForm(form) {
        const username = form.querySelector('#dashlistorc-username').value.trim();
        const password = form.querySelector('#dashlistorc-password').value.trim();
        const messagesContainer = document.getElementById('dashlistorc-login-messages');
        
        clearMessages(messagesContainer);
        
        let isValid = true;
        let errors = [];
        
        // Validar usuário
        if (!username) {
            errors.push('Por favor, informe seu usuário.');
            isValid = false;
        }
        
        // Validar senha
        if (!password) {
            errors.push('Por favor, informe sua senha.');
            isValid = false;
        }
        
        // Mostrar erros
        if (errors.length > 0) {
            showMessage(messagesContainer, errors.join('<br>'), 'error');
            // Adicionar efeito de shake
            form.classList.add('shake');
            setTimeout(() => form.classList.remove('shake'), 500);
        }
        
        return isValid;
    }

    // PARTE 05 - Validação em tempo real
    function initRealTimeValidation(form) {
        const inputs = form.querySelectorAll('.dashlistorc-login-input');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                clearFieldError(this);
            });
        });
    }

    // PARTE 06 - Validar campo individual
    function validateField(field) {
        const value = field.value.trim();
        
        if (!value) {
            showFieldError(field, 'Este campo é obrigatório.');
            return false;
        }
        
        clearFieldError(field);
        return true;
    }

    // PARTE 07 - Mostrar erro no campo
    function showFieldError(field, message) {
        clearFieldError(field);
        
        field.classList.add('error');
        
        const errorElement = document.createElement('div');
        errorElement.className = 'dashlistorc-field-error';
        errorElement.style.cssText = 'color: #dc3545; font-size: 12px; margin-top: 5px;';
        errorElement.textContent = message;
        
        field.parentNode.appendChild(errorElement);
    }

    // PARTE 08 - Limpar erro do campo
    function clearFieldError(field) {
        field.classList.remove('error');
        
        const existingError = field.parentNode.querySelector('.dashlistorc-field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    // PARTE 09 - Enviar requisição de login
    function sendLoginRequest(formData) {
        return new Promise((resolve, reject) => {
            // Usar fetch API
            fetch(window.dashlistorcLoginVars?.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta do servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    resolve(data.data);
                } else {
                    reject(new Error(data.data));
                }
            })
            .catch(error => {
                reject(new Error('Erro de rede: ' + error.message));
            });
        });
    }

    // PARTE 10 - Manipular sucesso no login
    function handleLoginSuccess(data) {
        const messagesContainer = document.getElementById('dashlistorc-login-messages');
        
        showMessage(messagesContainer, data.message || 'Login realizado com sucesso!', 'success');
        
        // Redirecionar após breve delay
        setTimeout(() => {
            window.location.href = data.redirect_url || window.dashlistorcLoginVars?.redirect_url || '/';
        }, 1500);
    }

    // PARTE 11 - Manipular erro no login
    function handleLoginError(error) {
        const messagesContainer = document.getElementById('dashlistorc-login-messages');
        
        showMessage(messagesContainer, error.message, 'error');
        
        // Adicionar efeito visual de erro
        const form = document.getElementById('dashlistorc-login-form');
        form.classList.add('shake');
        setTimeout(() => form.classList.remove('shake'), 500);
    }

    // PARTE 12 - Mostrar mensagens
    function showMessage(container, message, type) {
        if (!container) return;
        
        clearMessages(container);
        
        container.className = `dashlistorc-login-messages ${type}`;
        container.innerHTML = message;
        container.style.display = 'block';
        
        // Auto-remover mensagens de sucesso após 5 segundos
        if (type === 'success') {
            setTimeout(() => {
                clearMessages(container);
            }, 5000);
        }
    }

    // PARTE 13 - Limpar mensagens
    function clearMessages(container) {
        if (!container) return;
        
        container.className = 'dashlistorc-login-messages';
        container.innerHTML = '';
        container.style.display = 'none';
    }

    // PARTE 14 - Estado de loading
    function setLoadingState(button, isLoading) {
        if (!button) return;
        
        const btnText = button.querySelector('.dashlistorc-login-btn-text');
        const loadingSpinner = button.querySelector('.dashlistorc-login-loading');
        
        if (isLoading) {
            button.classList.add('loading');
            button.disabled = true;
            if (btnText) btnText.style.opacity = '0';
            if (loadingSpinner) loadingSpinner.style.display = 'inline-block';
        } else {
            button.classList.remove('loading');
            button.disabled = false;
            if (btnText) btnText.style.opacity = '1';
            if (loadingSpinner) loadingSpinner.style.display = 'none';
        }
    }

    // PARTE 15 - Melhorar UX do formulário
    function enhanceFormUX() {
        const form = document.getElementById('dashlistorc-login-form');
        
        if (!form) return;
        
        // Focar no primeiro campo
        const firstInput = form.querySelector('input[type="text"], input[type="email"]');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
        
        // Permitir submit com Enter
        form.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.click();
                }
            }
        });
    }

    // PARTE 16 - Verificar se estamos na página de login
    function isLoginPage() {
        return document.querySelector('.dashlistorc-login-container') !== null;
    }

    // PARTE 17 - Inicialização completa do login
    function initLogin() {
        if (!isLoginPage()) return;
        
        initLoginForm();
        enhanceFormUX();
        
        console.log('Dashlistorc Login inicializado com sucesso!');
    }

    // PARTE 18 - Inicializar quando a página estiver pronta
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLogin);
    } else {
        initLogin();
    }

});
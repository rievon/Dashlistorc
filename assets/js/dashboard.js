// PARTE 01 - JavaScript do Dashboard Frontend
document.addEventListener('DOMContentLoaded', function() {
    
    // PARTE 02 - Função para trocar abas
    function switchTab(tabName) {
        // Remove classe active de todas as abas e itens do menu
        document.querySelectorAll('.dashlistorc-tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.dashlistorc-nav-item').forEach(item => {
            item.classList.remove('dashlistorc-nav-active');
        });
        
        // Adiciona classe active na aba e item do menu selecionados
        const targetTab = document.getElementById('tab-content-' + tabName);
        const targetNavItem = document.querySelector('[data-tab="' + tabName + '"]')?.closest('.dashlistorc-nav-item');
        
        if (targetTab) targetTab.classList.add('active');
        if (targetNavItem) targetNavItem.classList.add('dashlistorc-nav-active');
        
        // Atualiza o título da página
        updatePageTitle(tabName);
        
        // Salvar aba ativa no sessionStorage
        sessionStorage.setItem('dashlistorc-active-tab', tabName);
    }
    
    // PARTE 03 - Atualizar título da página
    function updatePageTitle(tabName) {
        const titles = {
            'dashboard': 'Dashboard',
            'produtos': 'Lista de Produtos', 
            'adicionar': 'Adicionar Produto',
            'relatorio': 'Relatórios'
        };
        
        const pageTitle = document.querySelector('.dashlistorc-page-title');
        if (pageTitle && titles[tabName]) {
            pageTitle.textContent = titles[tabName];
        }
    }
    
    // PARTE 04 - Inicializar eventos de navegação
    function initNavigation() {
        document.querySelectorAll('.dashlistorc-nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const tabName = this.getAttribute('data-tab');
                if (tabName) {
                    switchTab(tabName);
                    
                    // Carregar produtos se for a aba de produtos
                    if (tabName === 'produtos') {
                        setTimeout(() => {
                            loadProducts();
                        }, 100);
                    }
                }
            });
        });
    }
    
    // PARTE 05 - Funcionalidade do Menu Lateral
    function initSidebar() {
        const sidebar = document.querySelector('.dashlistorc-sidebar');
        const floatingToggle = document.getElementById('floating-toggle');
        const toggleIcon = document.getElementById('toggle-icon');
        const mainContent = document.querySelector('.dashlistorc-main-content');

        if (floatingToggle && toggleIcon && sidebar && mainContent) {
            floatingToggle.addEventListener('click', function() {
                const isRecolhido = sidebar.classList.toggle('recolhido');
                
                // Troca o SVG conforme o estado
                if (isRecolhido) {
                    toggleIcon.style.transform = 'rotate(180deg)';
                    toggleIcon.alt = 'Expandir Menu';
                    mainContent.style.marginLeft = '70px';
                } else {
                    toggleIcon.style.transform = 'rotate(0deg)';
                    toggleIcon.alt = 'Recolher Menu';
                    mainContent.style.marginLeft = '280px';
                }
                
                // Salvar estado no localStorage
                localStorage.setItem('dashlistorc-sidebar-state', isRecolhido ? 'recolhido' : 'expandido');
            });
            
            // Restaurar estado do menu
            const savedState = localStorage.getItem('dashlistorc-sidebar-state');
            if (savedState === 'recolhido') {
                sidebar.classList.add('recolhido');
                toggleIcon.style.transform = 'rotate(180deg)';
                toggleIcon.alt = 'Expandir Menu';
                mainContent.style.marginLeft = '70px';
            }
        }
    }

    // PARTE 06 - Botão Sair
    function initLogoutButtons() {
        const sidebarLogoutBtn = document.getElementById('sidebar-logout-btn');
        const topLogoutBtn = document.getElementById('top-logout-btn');

        function handleLogout() {
            if (confirm('Tem certeza que deseja sair do sistema?')) {
                window.location.href = wp_logout_url ? wp_logout_url(window.location.href) : '/wp-login.php?action=logout';
            }
        }

        if (sidebarLogoutBtn) {
            sidebarLogoutBtn.addEventListener('click', handleLogout);
        }

        if (topLogoutBtn) {
            topLogoutBtn.addEventListener('click', handleLogout);
        }
    }
    
    // PARTE 07 - Restaurar aba ativa
    function restoreActiveTab() {
        const savedTab = sessionStorage.getItem('dashlistorc-active-tab');
        if (savedTab && savedTab !== 'dashboard') {
            switchTab(savedTab);
            
            // Carregar produtos se for a aba de produtos
            if (savedTab === 'produtos') {
                setTimeout(() => {
                    loadProducts();
                }, 100);
            }
        } else {
            // Inicializar com a aba dashboard ativa
            switchTab('dashboard');
        }
    }
    
    // PARTE 08 - Modal para frontend
    function showFrontendModal(type, message) {
        // Criar modal simples para frontend
        const modalHtml = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;">
                <div style="background: white; padding: 20px; border-radius: 8px; max-width: 400px; text-align: center;">
                    <h3 style="color: #2c5aa0; margin-bottom: 10px;">${type === 'info' ? 'Informação' : 'Aviso'}</h3>
                    <p style="margin-bottom: 20px;">${message}</p>
                    <button onclick="this.closest('div[style]').remove()" style="background: #2c5aa0; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">OK</button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }
    
    // PARTE 09 - Responsividade
    function handleResize() {
        const sidebar = document.querySelector('.dashlistorc-sidebar');
        const mainContent = document.querySelector('.dashlistorc-main-content');
        const floatingToggle = document.getElementById('floating-toggle');

        if (window.innerWidth <= 768) {
            // Comportamento mobile
            if (sidebar && !sidebar.classList.contains('recolhido')) {
                sidebar.classList.add('recolhido');
                if (mainContent) mainContent.style.marginLeft = '70px';
                if (floatingToggle) {
                    floatingToggle.querySelector('img').style.transform = 'rotate(180deg)';
                }
            }
        }
    }
    
    // PARTE 10 - Sistema de Lista de Produtos WooCommerce
    function initProductsList() {
        const productsTab = document.getElementById('tab-content-produtos');
        if (!productsTab) return;

        // Configurar busca
        setupProductsSearch();
        
        // Carregar produtos inicialmente
        loadProducts();
    }

    // PARTE 11 - Carregar produtos do WooCommerce
    function loadProducts(searchTerm = '', page = 1) {
        const contentElement = document.getElementById('products-content');
        if (!contentElement) {
            console.error('Elemento products-content não encontrado');
            return;
        }
        
        console.log('Carregando produtos...', { searchTerm, page });

        // Mostrar loading
        contentElement.innerHTML = `
            <div class="dashlistorc-loading-state">
                <div class="dashlistorc-loading-spinner"></div>
                Carregando produtos...
            </div>
        `;

        // Fazer requisição AJAX
        const formData = new FormData();
        formData.append('action', 'dashlistorc_get_products');
        formData.append('nonce', window.dashlistorcVars?.nonce || '');
        formData.append('page', page);
        formData.append('per_page', 10);
        
        if (searchTerm) {
            formData.append('search', searchTerm);
        }

        fetch(window.dashlistorcVars?.ajaxurl || '/wp-admin/admin-ajax.php', {
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
            console.log('Resposta produtos:', data);
            
            if (data.success) {
                displayProducts(data.data.products, data.data.pagination);
            } else {
                showError(data.data || 'Erro ao carregar produtos');
            }
        })
        .catch(error => {
            console.error('Erro na requisição:', error);
            showError('Erro de conexão. Carregando dados de exemplo...');
            
            // Dados de exemplo em caso de erro
            const exampleProducts = [
                {
                    id: 1,
                    name: 'Produto Exemplo 1',
                    image: window.dashlistorcVars?.placeholderImage || '/wp-content/plugins/woocommerce/assets/images/placeholder.png',
                    laboratorio: 'Laboratório A',
                    date: '01/01/2024',
                    edit_link: '#'
                },
                {
                    id: 2,
                    name: 'Produto Exemplo 2',
                    image: window.dashlistorcVars?.placeholderImage || '/wp-content/plugins/woocommerce/assets/images/placeholder.png',
                    laboratorio: 'Laboratório B',
                    date: '02/01/2024',
                    edit_link: '#'
                },
                {
                    id: 3,
                    name: 'Produto Exemplo 3',
                    image: window.dashlistorcVars?.placeholderImage || '/wp-content/plugins/woocommerce/assets/images/placeholder.png',
                    laboratorio: 'Laboratório C',
                    date: '03/01/2024',
                    edit_link: '#'
                }
            ];
            
            displayProducts(exampleProducts, {
                current_page: 1,
                total_pages: 1,
                total_products: 3,
                per_page: 10
            });
        });
    }

    // PARTE 12 - Exibir produtos na tabela
    function displayProducts(products, pagination) {
        const contentElement = document.getElementById('products-content');
        if (!contentElement) return;
        
        console.log('Exibindo produtos:', products);
        
        if (!products || products.length === 0) {
            contentElement.innerHTML = `
                <div class="dashlistorc-empty-state">
                    <div class="dashlistorc-empty-icon">📦</div>
                    <h3>Nenhum produto encontrado</h3>
                    <p>Tente ajustar os termos da busca ou verificar se existem produtos cadastrados.</p>
                </div>
            `;
            return;
        }

        let tableHTML = `
            <table class="dashlistorc-products-table">
                <thead class="dashlistorc-table-header">
                    <tr>
                        <th class="dashlistorc-product-id">ID</th>
                        <th class="dashlistorc-product-image">Imagem</th>
                        <th class="dashlistorc-product-name">Nome do Produto</th>
                        <th class="dashlistorc-product-lab">Laboratório</th>
                        <th class="dashlistorc-product-date">Data</th>
                        <th class="dashlistorc-product-actions">Ações</th>
                    </tr>
                </thead>
                <tbody>
        `;

        products.forEach(product => {
            tableHTML += `
                <tr class="dashlistorc-table-row" data-product-id="${product.id}">
                    <td class="dashlistorc-product-id">#${product.id}</td>
                    <td class="dashlistorc-product-image">
                        <img src="${product.image}" 
                             alt="${product.name}" 
                             class="dashlistorc-product-thumb"
                             onerror="this.src='${window.dashlistorcVars?.placeholderImage || '/wp-content/plugins/woocommerce/assets/images/placeholder.png'}'">
                    </td>
                    <td class="dashlistorc-product-name">
                        <strong>${product.name}</strong>
                    </td>
                    <td class="dashlistorc-product-lab">${product.laboratorio || 'Não especificado'}</td>
                    <td class="dashlistorc-product-date">${product.date || '—'}</td>
                    <td class="dashlistorc-product-actions">
                        <button class="dashlistorc-action-btn dashlistorc-edit-btn" 
                                onclick="window.open('${product.edit_link}', '_blank')"
                                title="Editar Produto">
                            <svg class="dashlistorc-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </button>
                        <button class="dashlistorc-action-btn dashlistorc-delete-btn" 
                                onclick="deleteProduct(${product.id})"
                                title="Excluir Produto">
                            <svg class="dashlistorc-action-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 6h18"></path>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                            </svg>
                        </button>
                    </td>
                </tr>
            `;
        });

        tableHTML += `
            </tbody>
        </table>
        `;

        // Adicionar paginação se houver
        if (pagination && pagination.total_pages > 1) {
            tableHTML += generatePagination(pagination);
        }

        contentElement.innerHTML = tableHTML;
    }

    // PARTE 13 - Gerar paginação
    function generatePagination(pagination) {
        const { current_page, total_pages, total_products } = pagination;
        
        let paginationHTML = `
            <div class="dashlistorc-pagination">
                <div class="dashlistorc-pagination-info">
                    Mostrando ${((current_page - 1) * 10) + 1}-${Math.min(current_page * 10, total_products)} de ${total_products} produtos
                </div>
                <div class="dashlistorc-pagination-controls">
        `;

        // Botão anterior
        paginationHTML += `
            <button class="dashlistorc-pagination-btn" 
                    ${current_page === 1 ? 'disabled' : ''}
                    onclick="loadProductsPage(${current_page - 1})">
                Anterior
            </button>
        `;

        // Números das páginas
        paginationHTML += `<div class="dashlistorc-pagination-numbers">`;
        
        const startPage = Math.max(1, current_page - 2);
        const endPage = Math.min(total_pages, current_page + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            paginationHTML += `
                <button class="dashlistorc-pagination-btn ${i === current_page ? 'active' : ''}"
                        onclick="loadProductsPage(${i})">
                    ${i}
                </button>
            `;
        }
        
        paginationHTML += `</div>`;

        // Botão próximo
        paginationHTML += `
            <button class="dashlistorc-pagination-btn" 
                    ${current_page === total_pages ? 'disabled' : ''}
                    onclick="loadProductsPage(${current_page + 1})">
                Próximo
            </button>
        `;

        paginationHTML += `
                </div>
            </div>
        `;
        
        return paginationHTML;
    }

    // PARTE 14 - Configurar sistema de busca
    function setupProductsSearch() {
        const searchInput = document.getElementById('products-search');
        if (!searchInput) return;
        
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            
            // Buscar imediatamente se campo estiver vazio ou termo for numérico (ID)
            if (searchTerm.length === 0 || (searchTerm.length >= 1 && /^\d+$/.test(searchTerm))) {
                searchTimeout = setTimeout(() => {
                    loadProducts(searchTerm);
                }, 300);
            } else if (searchTerm.length >= 3) {
                // Para texto, esperar 3 caracteres
                searchTimeout = setTimeout(() => {
                    loadProducts(searchTerm);
                }, 500);
            }
        });
    }

    // PARTE 15 - Funções auxiliares globais
    window.loadProductsPage = function(page) {
        const searchInput = document.getElementById('products-search');
        const searchTerm = searchInput ? searchInput.value.trim() : '';
        loadProducts(searchTerm, page);
    };

    window.deleteProduct = function(productId) {
        if (confirm('Tem certeza que deseja excluir este produto?')) {
            showFrontendModal('info', 'Função de exclusão será implementada em breve!');
        }
    };

    // PARTE 16 - Mostrar erro
    function showError(message) {
        const contentElement = document.getElementById('products-content');
        if (!contentElement) return;
        
        contentElement.innerHTML = `
            <div class="dashlistorc-error-state">
                <div class="dashlistorc-error-icon">⚠️</div>
                <h3>Erro ao carregar</h3>
                <p>${message}</p>
                <button onclick="loadProducts()" class="dashlistorc-retry-btn">
                    Tentar Novamente
                </button>
            </div>
        `;
    }

    // PARTE 17 - Inicialização completa do dashboard
    function initDashboard() {
        initNavigation();
        initSidebar();
        initLogoutButtons();
        initProductsList();
        
        restoreActiveTab();
        handleResize();

        // Adicionar listener de resize
        window.addEventListener('resize', handleResize);
        
        console.log('Dashlistorc Dashboard inicializado com sucesso!');
    }

    // PARTE 18 - Verificar se estamos no dashboard
    function isDashboardPage() {
        return document.querySelector('.dashlistorc-frontend-dashboard') !== null;
    }

    // PARTE 19 - Inicializar apenas se estivermos no dashboard
    if (isDashboardPage()) {
        initDashboard();
    }

});
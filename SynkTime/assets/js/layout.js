class Layout {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.mainWrapper = document.querySelector('.main-wrapper');
        this.toggleBtn = document.getElementById('toggleSidebar');
        this.initializeLayout();
    }

    initializeLayout() {
        // Toggle sidebar
        this.toggleBtn.addEventListener('click', () => {
            if (window.innerWidth <= 1024) {
                this.sidebar.classList.toggle('mobile-active');
            } else {
                this.sidebar.classList.toggle('collapsed');
                this.mainWrapper.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarState', 
                    this.sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
            }
        });

        // Click fuera del sidebar en móvil lo cierra
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024) {
                if (!this.sidebar.contains(e.target) && 
                    !this.toggleBtn.contains(e.target) && 
                    this.sidebar.classList.contains('mobile-active')) {
                    this.sidebar.classList.remove('mobile-active');
                }
            }
        });

        // Restaurar estado del sidebar
        if (window.innerWidth > 1024) {
            const sidebarState = localStorage.getItem('sidebarState');
            if (sidebarState === 'collapsed') {
                this.sidebar.classList.add('collapsed');
                this.mainWrapper.classList.add('sidebar-collapsed');
            }
        }

        // Actualizar datetime
        this.updateDateTime();
        setInterval(() => this.updateDateTime(), 1000);

        // Manejar resize de ventana
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                this.sidebar.classList.remove('mobile-active');
                const sidebarState = localStorage.getItem('sidebarState');
                if (sidebarState === 'collapsed') {
                    this.sidebar.classList.add('collapsed');
                    this.mainWrapper.classList.add('sidebar-collapsed');
                }
            } else {
                this.sidebar.classList.remove('collapsed');
                this.mainWrapper.classList.remove('sidebar-collapsed');
            }
        });
    }

    updateDateTime() {
        const now = new Date();
        const dateTimeStr = this.formatUTCDateTime(now);
        document.getElementById('currentDateTime').textContent = dateTimeStr;
    }

    formatUTCDateTime(date) {
        const year = date.getUTCFullYear();
        const month = String(date.getUTCMonth() + 1).padStart(2, '0');
        const day = String(date.getUTCDate()).padStart(2, '0');
        const hours = String(date.getUTCHours()).padStart(2, '0');
        const minutes = String(date.getUTCMinutes()).padStart(2, '0');
        const seconds = String(date.getUTCSeconds()).padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    new Layout();
    // Dropdown user menu
    const userDropdown = document.querySelector('.user-dropdown');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');

    if (userDropdown && userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
        });

        // Cerrar el menú al hacer click fuera
        document.addEventListener('click', (e) => {
            if (userDropdown.classList.contains('open')) {
                userDropdown.classList.remove('open');
            }
        });

        // Opcional: No cerrar al hacer click dentro del menú
        userMenu.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
});
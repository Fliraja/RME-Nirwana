document.addEventListener('DOMContentLoaded', function() {
    console.log('Sidebar script loaded');
    
    const collapseTriggers = document.querySelectorAll('[data-toggle="collapse"]');
    
    collapseTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href').substring(1);
            const target = document.getElementById(targetId);
            const parentItem = this.closest('.nav-item');
            
            console.log('Toggling submenu:', targetId);
            
            this.classList.toggle('collapsed');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            parentItem.classList.toggle('has-submenu-open');
            
            if (target.classList.contains('show')) {
                slideUp(target, 300);
                target.classList.remove('show');
            } else {
                slideDown(target, 300);
                target.classList.add('show');
            }
        });
    });
    
    function slideUp(element, duration) {
        element.style.height = element.offsetHeight + 'px';
        element.style.transitionProperty = 'height';
        element.style.transitionDuration = duration + 'ms';
        element.offsetHeight;
        element.style.height = '0';
        
        setTimeout(() => {
            element.style.display = 'none';
            element.style.removeProperty('height');
            element.style.removeProperty('transition-duration');
            element.style.removeProperty('transition-property');
        }, duration);
    }
    
    function slideDown(element, duration) {
        element.style.removeProperty('display');
        let display = window.getComputedStyle(element).display;
        if (display === 'none') display = 'block';
        element.style.display = display;
        
        let height = element.offsetHeight;
        element.style.height = '0';
        element.style.transitionProperty = 'height';
        element.style.transitionDuration = duration + 'ms';
        element.offsetHeight;
        element.style.height = height + 'px';
        
        setTimeout(() => {
            element.style.removeProperty('height');
            element.style.removeProperty('transition-duration');
            element.style.removeProperty('transition-property');
        }, duration);
    }
    
    const activeLinks = document.querySelectorAll('.collapse .nav-link.active');
    activeLinks.forEach(link => {
        const submenu = link.closest('.collapse');
        if (submenu) {
            const parentLink = document.querySelector('[href="#' + submenu.id + '"]');
            const parentItem = parentLink ? parentLink.closest('.nav-item') : null;
            
            submenu.classList.add('show');
            submenu.style.display = 'block';
            
            if (parentLink) {
                parentLink.classList.remove('collapsed');
                parentLink.setAttribute('aria-expanded', 'true');
            }
            
            if (parentItem) {
                parentItem.classList.add('has-submenu-open');
            }
            
            console.log('Auto-expanded submenu:', submenu.id);
        }
    });
    
    function setActiveMenuRalan() {
        const currentPath = window.location.pathname;
        
        if (currentPath.includes('/ralan')) {
            const ralanMenus = document.querySelectorAll('a.nav-link[href*="/ralan"]');
            
            ralanMenus.forEach(menu => {
                const href = menu.getAttribute('href');
                
                if (href && (href.endsWith('/ralan') || href.includes('ralan.index'))) {
                    menu.classList.add('active');
                    console.log('Rawat Jalan menu set to active');
                }
            });
        }
    }
    
    setActiveMenuRalan();

    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(response => {
            if (args[0] && args[0].includes('/ralan/')) {
                console.log('Fetch completed for ralan, maintaining active menu');
                setTimeout(setActiveMenuRalan, 100);
            }
            return response;
        });
    };
    
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url) {
        this.addEventListener('load', function() {
            if (url && url.includes('/ralan/')) {
                console.log('XHR completed for ralan, maintaining active menu');
                setTimeout(setActiveMenuRalan, 100);
            }
        });
        return originalOpen.apply(this, arguments);
    };
    
    const sidebarToggle = document.querySelector('.sidebar-toggle, .toggle-btn');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            
            setTimeout(function() {
                if (sidebar && sidebar.classList.contains('collapsed')) {
                    const openSubmenus = document.querySelectorAll('.collapse.show');
                    openSubmenus.forEach(submenu => {
                        submenu.classList.remove('show');
                        submenu.style.display = 'none';
                    });
                    
                    const collapseTriggers = document.querySelectorAll('[data-toggle="collapse"]');
                    collapseTriggers.forEach(trigger => {
                        trigger.classList.add('collapsed');
                        trigger.setAttribute('aria-expanded', 'false');
                    });
                    
                    const openParents = document.querySelectorAll('.nav-item.has-submenu-open');
                    openParents.forEach(parent => {
                        parent.classList.remove('has-submenu-open');
                    });
                    
                    console.log('Sidebar collapsed - closing all submenus');
                }
            }, 100);
        });
    }
    
    const activeLink = document.querySelector('.nav-link.active');
    if (activeLink) {
        const sidebarNav = document.querySelector('.sidebar-nav');
        if (sidebarNav) {
            const activeLinkTop = activeLink.getBoundingClientRect().top;
            const sidebarTop = sidebarNav.getBoundingClientRect().top;
            const scrollPosition = activeLinkTop - sidebarTop - 100;
            
            sidebarNav.scrollTo({
                top: sidebarNav.scrollTop + scrollPosition,
                behavior: 'smooth'
            });
        }
    }
    
    console.log('Sidebar script initialized successfully');
});
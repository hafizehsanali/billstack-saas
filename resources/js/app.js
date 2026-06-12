import './bootstrap';
import '@tabler/core/dist/js/tabler.min.js';

import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;

import { createIcons, icons } from 'lucide';

const renderIcons = () => createIcons({
    icons,
    attrs: {
        'aria-hidden': 'true',
        'stroke-width': 1.8,
    },
});

const initializeSidebarGroups = () => {
    document.querySelectorAll('[data-sidebar-group-toggle]').forEach((toggle) => {
        if (toggle.dataset.sidebarInitialized === 'true') {
            return;
        }

        toggle.dataset.sidebarInitialized = 'true';
        toggle.addEventListener('click', () => {
            const links = document.getElementById(toggle.dataset.sidebarGroupToggle);

            if (! links) {
                return;
            }

            const shouldOpen = links.hidden;
            links.hidden = ! shouldOpen;
            toggle.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        });
    });
};

const initializeSidebarToggle = () => {
    document.querySelectorAll('[data-sidebar-toggle]').forEach((toggle) => {
        if (toggle.dataset.sidebarToggleInitialized === 'true') {
            return;
        }

        toggle.dataset.sidebarToggleInitialized = 'true';
        toggle.addEventListener('click', () => {
            const isCollapsed = document.documentElement.classList.toggle('sidebar-collapsed');
            toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');

            try {
                localStorage.setItem('billstack.sidebar.collapsed', String(isCollapsed));
            } catch (error) {
                // Keep the control functional when browser storage is unavailable.
            }
        });
    });

    const isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
    document.querySelectorAll('[data-sidebar-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
    });
};

document.addEventListener('DOMContentLoaded', renderIcons);
document.addEventListener('DOMContentLoaded', initializeSidebarGroups);
document.addEventListener('DOMContentLoaded', initializeSidebarToggle);
document.addEventListener('livewire:navigated', () => {
    renderIcons();
    initializeSidebarGroups();
    initializeSidebarToggle();
});

<script setup>
import { usePage } from '@inertiajs/vue3';

defineProps({
    modulo: { type: String, required: true },
    titulo: { type: String, required: true },
});

const page = usePage();
</script>

<template>
    <div class="erp-topbar">
        <div class="erp-topbar__left">
            <img src="/images/logo-cedac.jpg" alt="CEDAC" class="erp-logo" />
            <div class="erp-topbar__breadcrumb">
                <span class="erp-bc-item">{{ modulo }}</span>
                <svg class="erp-bc-sep" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m9 18 6-6-6-6"/></svg>
                <span class="erp-bc-item erp-bc-item--active">{{ titulo }}</span>
            </div>
        </div>
        <div class="erp-topbar__right">
            <div class="erp-topbar__pill">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="erp-topbar__icon"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                <span>{{ page.props.auth.user.name }}</span>
            </div>
            <div class="erp-topbar__pill">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="erp-topbar__icon"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span>{{ new Date().toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' }) }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.erp-topbar {
    background: #1a3a5c;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 28px;
    gap: 16px;
    flex-wrap: wrap;
}
.erp-topbar__left {
    display: flex;
    align-items: center;
    gap: 14px;
}
.erp-logo {
    height: 32px;
    width: 32px;
    border-radius: 6px;
    object-fit: cover;
    flex-shrink: 0;
}
.erp-topbar__breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
}
.erp-bc-item {
    font-size: 0.78rem;
    color: rgba(255, 255, 255, 0.60);
    font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
}
.erp-bc-item--active {
    color: #fff;
    font-weight: 600;
}
.erp-bc-sep {
    color: rgba(255, 255, 255, 0.35);
    flex-shrink: 0;
}
.erp-topbar__right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.erp-topbar__pill {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.10);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 0.76rem;
    color: rgba(255, 255, 255, 0.85);
    white-space: nowrap;
    font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
}
.erp-topbar__icon {
    color: rgba(255, 255, 255, 0.55);
    flex-shrink: 0;
}
</style>

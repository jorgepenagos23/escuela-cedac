<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ErpTopbar from "@/Components/ErpTopbar.vue";
import { Head, usePage }   from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import "../../css/app.css";

const page     = usePage();
const authUser = computed(() => page.props.auth.user);

// ── Reloj en vivo ─────────────────────────────────────────────────────────────
const ahora   = ref(new Date());
let timerTick = null;

onMounted(()  => { timerTick = setInterval(() => ahora.value = new Date(), 1000); });
onUnmounted(() => clearInterval(timerTick));

const diaNombre = computed(() =>
    ahora.value.toLocaleDateString('es-MX', { weekday: 'long' })
);
const fechaCompleta = computed(() =>
    ahora.value.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' })
);
const horaStr = computed(() =>
    ahora.value.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true })
);
const horaHH = computed(() => ahora.value.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit', hour12: false }));

// Saludo dinámico
const saludo = computed(() => {
    const h = ahora.value.getHours();
    if (h <  6) return 'Buenas noches';
    if (h < 12) return 'Buenos días';
    if (h < 19) return 'Buenas tardes';
    return 'Buenas noches';
});

// Segundero visual (ángulo)
const segGrados  = computed(() => ahora.value.getSeconds() * 6);
const minGrados  = computed(() => ahora.value.getMinutes() * 6 + ahora.value.getSeconds() * 0.1);
const horGrados  = computed(() => (ahora.value.getHours() % 12) * 30 + ahora.value.getMinutes() * 0.5);

// Barra de progreso del día (0-100%)
const progDia = computed(() => {
    const total = 24 * 3600;
    const segs  = ahora.value.getHours() * 3600 + ahora.value.getMinutes() * 60 + ahora.value.getSeconds();
    return Math.round((segs / total) * 100);
});

// Nombre del primer rol visible
const rolLabel = computed(() => {
    const r = authUser.value?.roles?.[0];
    const map = { TI: 'TI / Soporte', Administrador: 'Administrador', Tutoria: 'Tutoría', Admisiones: 'Admisiones', Tutor: 'Tutor', Alumno: 'Alumno' };
    return map[r] ?? r ?? 'Usuario';
});
</script>

<template>
    <Head title="Panel de Control" />
    <AuthenticatedLayout>
        <ErpTopbar modulo="Inicio" titulo="Panel de Control" />
        <!-- Sin header slot → nav queda limpia -->

        <!-- ══ Pantalla principal del dashboard ══ -->
        <div class="dashboard-root">

            <!-- ── Hero: imagen de fondo ── -->
            <div class="hero-wrap">
                <img src="/images/dashboard_hero.png" alt="Sistema CEDAC" class="hero-img" />
                <!-- Overlay degradado -->
                <div class="hero-overlay" />

                <!-- Contenido sobre la imagen -->
                <div class="hero-content">

                    <!-- Bloque superior: saludo + nombre de usuario -->
                    <div class="hero-top">
                        <div class="hero-saludo-badge">
                            <v-icon size="14" color="white" class="mr-1">mdi-weather-sunny</v-icon>
                            {{ saludo }}, <strong>{{ authUser.name?.split(' ')[0] }}</strong>
                        </div>
                        <div class="hero-rol-badge">
                            <v-icon size="12" class="mr-1">mdi-shield-check</v-icon>
                            {{ rolLabel }}
                        </div>
                    </div>

                    <!-- Bloque central: reloj digital grande -->
                    <div class="clock-center">

                        <!-- Hora digital -->
                        <div class="clock-digital">
                            <span class="clock-hhmm">{{ horaHH }}</span>
                            <div class="clock-seconds-col">
                                <span class="clock-ss">
                                    {{ String(ahora.getSeconds()).padStart(2, '0') }}
                                </span>
                                <span class="clock-period">
                                    {{ ahora.getHours() >= 12 ? 'PM' : 'AM' }}
                                </span>
                            </div>
                        </div>

                        <!-- Separador decorativo -->
                        <div class="clock-divider" />

                        <!-- Fecha y día -->
                        <div class="clock-date-col">
                            <span class="clock-dia">{{ diaNombre }}</span>
                            <span class="clock-fecha">{{ fechaCompleta }}</span>

                            <!-- Barra de progreso del día -->
                            <div class="prog-dia-wrap mt-3">
                                <div class="prog-dia-bar">
                                    <div class="prog-dia-fill" :style="{ width: progDia + '%' }" />
                                    <div class="prog-dia-dot" :style="{ left: progDia + '%' }" />
                                </div>
                                <div class="prog-dia-labels">
                                    <span>00:00</span>
                                    <span class="text-white/60 text-xs">{{ progDia }}% del día</span>
                                    <span>23:59</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bloque inferior: hint de la paleta de comandos -->
                    <div class="hero-bottom">
                        <div class="search-hint">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="mr-2 opacity-70">
                                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                            </svg>
                            <span>Presiona</span>
                            <kbd class="hint-kbd">Ctrl</kbd>
                            <span>+</span>
                            <kbd class="hint-kbd">K</kbd>
                            <span>para buscar un módulo</span>
                        </div>

                        <!-- Info del sistema -->
                        <div class="sys-info">
                            <v-icon size="12" class="mr-1" color="white">mdi-circle</v-icon>
                            Sistema CEDAC · Plataforma Web Activa
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* ── Root ── */
.dashboard-root {
    min-height: calc(100vh - 56px);
    background: #0f172a;
    display: flex;
    flex-direction: column;
}

/* ── Hero ── */
.hero-wrap {
    position: relative;
    flex: 1;
    min-height: calc(100vh - 56px);
    overflow: hidden;
}

.hero-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    opacity: 0.55;
}

.hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        rgba(15, 23, 42, 0.30) 0%,
        rgba(15, 23, 42, 0.55) 40%,
        rgba(15, 23, 42, 0.80) 100%
    );
}

/* ── Contenido sobre la imagen ── */
.hero-content {
    position: relative;
    z-index: 10;
    height: 100%;
    min-height: calc(100vh - 56px);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 40px 48px;
    color: white;
}

/* ── Tope: saludo ── */
.hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 12px;
}

.hero-saludo-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 100px;
    padding: 6px 16px;
    font-size: 0.9rem;
    color: white;
    letter-spacing: 0.02em;
}

.hero-rol-badge {
    display: inline-flex;
    align-items: center;
    background: rgba(99,102,241,0.25);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(165,180,252,0.35);
    border-radius: 100px;
    padding: 6px 14px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #c7d2fe;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}

/* ── Reloj central ── */
.clock-center {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
    flex: 1;
}

/* Hora digital */
.clock-digital {
    display: flex;
    align-items: flex-start;
    gap: 8px;
}

.clock-hhmm {
    font-size: clamp(4rem, 12vw, 9rem);
    font-weight: 800;
    letter-spacing: -0.04em;
    color: white;
    line-height: 1;
    font-variant-numeric: tabular-nums;
    text-shadow: 0 4px 30px rgba(99,102,241,0.6), 0 0 60px rgba(99,102,241,0.3);
    font-family: 'ui-monospace', 'Cascadia Code', monospace;
}

.clock-seconds-col {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding-top: 12px;
    gap: 4px;
}

.clock-ss {
    font-size: clamp(1.4rem, 3.5vw, 2.5rem);
    font-weight: 700;
    color: #a5b4fc;
    font-family: monospace;
    font-variant-numeric: tabular-nums;
    line-height: 1;
}

.clock-period {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.15em;
    color: rgba(255,255,255,0.5);
    text-transform: uppercase;
}

/* Divisor */
.clock-divider {
    width: 1px;
    height: 100px;
    background: linear-gradient(to bottom, transparent, rgba(165,180,252,0.5), transparent);
    flex-shrink: 0;
    display: none;
}
@media (min-width: 640px) { .clock-divider { display: block; } }

/* Fecha */
.clock-date-col {
    display: flex;
    flex-direction: column;
    gap: 6px;
    min-width: 220px;
}

.clock-dia {
    font-size: clamp(1.3rem, 3vw, 2rem);
    font-weight: 700;
    color: white;
    text-transform: capitalize;
    letter-spacing: 0.02em;
    line-height: 1;
}

.clock-fecha {
    font-size: clamp(0.85rem, 2vw, 1.1rem);
    color: rgba(255,255,255,0.65);
    font-weight: 400;
    letter-spacing: 0.02em;
}

/* ── Barra de progreso del día ── */
.prog-dia-wrap { width: 100%; max-width: 260px; }

.prog-dia-bar {
    position: relative;
    height: 4px;
    background: rgba(255,255,255,0.15);
    border-radius: 100px;
    overflow: visible;
}

.prog-dia-fill {
    height: 100%;
    border-radius: 100px;
    background: linear-gradient(to right, #6366f1, #a78bfa);
    transition: width 1s linear;
}

.prog-dia-dot {
    position: absolute;
    top: 50%;
    width: 10px; height: 10px;
    background: white;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 0 8px rgba(165,180,252,0.8);
    transition: left 1s linear;
}

.prog-dia-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 6px;
    font-size: 0.68rem;
    color: rgba(255,255,255,0.4);
    font-family: monospace;
}

/* ── Bottom: hint de búsqueda ── */
.hero-bottom {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    flex-wrap: wrap;
    gap: 12px;
}

.search-hint {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.18);
    border-radius: 100px;
    padding: 10px 20px;
    font-size: 0.85rem;
    color: rgba(255,255,255,0.85);
    cursor: default;
    transition: background 0.2s;
}
.search-hint:hover { background: rgba(255,255,255,0.16); }

.hint-kbd {
    display: inline-flex;
    align-items: center;
    font-size: 0.7rem;
    font-weight: 800;
    font-family: monospace;
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
    border-bottom-width: 2px;
    border-radius: 5px;
    padding: 1px 7px;
}

.sys-info {
    display: inline-flex;
    align-items: center;
    font-size: 0.72rem;
    color: rgba(255,255,255,0.35);
    letter-spacing: 0.05em;
}
</style>

<script setup>
import { ref, computed, onMounted } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import ErpTopbar from "@/Components/ErpTopbar.vue";
import { Head } from "@inertiajs/vue3";
import axios from 'axios';

// ── Período por defecto: mes actual ────────────────────────────────────────────
const hoy = new Date().toISOString().split('T')[0];
const primerDiaMes = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];

const filtroDesde = ref(primerDiaMes);
const filtroHasta = ref(hoy);
const cargando    = ref(false);
const datos       = ref(null);
const error       = ref('');

// ── Formato moneda ─────────────────────────────────────────────────────────────
const mxn = (n) => Number(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const pct = (parte, total) => total > 0 ? ((parte / total) * 100).toFixed(1) + '%' : '0.0%';

// ── Carga ──────────────────────────────────────────────────────────────────────
const cargar = async () => {
    cargando.value = true;
    error.value    = '';
    try {
        const res = await axios.get('/api/v1/dashboard/financiero', {
            params: { desde: filtroDesde.value, hasta: filtroHasta.value }
        });
        datos.value = res.data;
    } catch (e) {
        error.value = 'No se pudo cargar el dashboard financiero.';
        console.error(e);
    } finally {
        cargando.value = false;
    }
};

onMounted(cargar);

// ── Helpers de presentación ────────────────────────────────────────────────────
const labelPeriodo = computed(() => {
    if (!datos.value) return '';
    const d = new Date(datos.value.periodo.desde + 'T12:00:00');
    const h = new Date(datos.value.periodo.hasta + 'T12:00:00');
    const fmt = (f) => f.toLocaleDateString('es-MX', { day: '2-digit', month: 'long', year: 'numeric' });
    return `${fmt(d)} — ${fmt(h)}`;
});

const porcentajeVencida = computed(() => {
    if (!datos.value) return 0;
    const t = datos.value.cartera.total;
    return t > 0 ? ((datos.value.cartera.vencida.monto / t) * 100).toFixed(1) : 0;
});
</script>

<template>
  <AuthenticatedLayout>
    <ErpTopbar modulo="Finanzas" titulo="Dashboard Financiero y Operativo" />
    <Head title="Dashboard Financiero" />

    <div class="df-root">

      <!-- ══ CABECERA DEL SISTEMA (estilo CompaqCont) ══════════════════════════ -->
      <div class="df-system-bar">
        <div class="df-system-bar__left">
          <div class="df-system-bar__logo">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4M6 8h4M6 12h8"/></svg>
          </div>
          <span class="df-system-bar__name">CEDAC · SISTEMA DE GESTIÓN ESCOLAR</span>
          <span class="df-system-bar__sep">|</span>
          <span class="df-system-bar__module">ESTADO DE RESULTADOS FINANCIERO</span>
        </div>
        <div class="df-system-bar__right">
          <span class="df-system-bar__date">
            Generado: {{ new Date().toLocaleDateString('es-MX', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }) }}
          </span>
        </div>
      </div>

      <!-- ══ BARRA DE CORTE DE FECHAS ══════════════════════════════════════════ -->
      <div class="df-corte-bar">
        <div class="df-corte-bar__label">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1a3a5c" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          CORTE DE FECHAS
        </div>
        <div class="df-corte-bar__inputs">
          <div class="df-date-group">
            <label class="df-date-label">Período Desde</label>
            <input v-model="filtroDesde" type="date" class="df-date-input" />
          </div>
          <div class="df-corte-bar__arrow">→</div>
          <div class="df-date-group">
            <label class="df-date-label">Período Hasta</label>
            <input v-model="filtroHasta" type="date" class="df-date-input" />
          </div>
          <button class="df-consultar-btn" @click="cargar" :disabled="cargando">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" :class="cargando ? 'df-spin' : ''"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
            {{ cargando ? 'Procesando...' : 'Consultar' }}
          </button>
        </div>
        <div v-if="datos" class="df-corte-bar__periodo">
          {{ labelPeriodo }}
        </div>
      </div>

      <!-- Loading / Error -->
      <div v-if="cargando" class="df-loading">
        <v-progress-circular indeterminate color="#1a3a5c" size="40" />
        <p>Calculando estado financiero...</p>
      </div>
      <div v-else-if="error" class="df-error">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>
        {{ error }}
      </div>

      <template v-else-if="datos">
        <div class="df-body">

          <!-- ══ ESTADO DE RESULTADOS (tabla contable) ══════════════════════════ -->
          <div class="er-container">

            <!-- Título del estado -->
            <div class="er-title-block">
              <div class="er-title-block__main">ESTADO DE RESULTADOS</div>
              <div class="er-title-block__sub">{{ labelPeriodo }}</div>
            </div>

            <!-- Tabla contable -->
            <table class="er-table">
              <thead>
                <tr>
                  <th class="er-col-concepto">CONCEPTO</th>
                  <th class="er-col-num">REF.</th>
                  <th class="er-col-monto er-th--cargo">CARGOS (-)</th>
                  <th class="er-col-monto er-th--abono">ABONOS (+)</th>
                  <th class="er-col-saldo">SALDO</th>
                </tr>
              </thead>
              <tbody>

                <!-- ── SECCIÓN: INGRESOS OPERATIVOS ── -->
                <tr class="er-section-row">
                  <td colspan="5">
                    <span class="er-section-label">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                      100 — INGRESOS OPERATIVOS
                    </span>
                  </td>
                </tr>

                <tr class="er-row">
                  <td class="er-cell-concepto">
                    <span class="er-cuenta-num">101</span>
                    Colegiaturas / Abonos de Mensualidades
                  </td>
                  <td class="er-cell-ref">{{ datos.ingresos.count_movimientos }} mov.</td>
                  <td class="er-cell-cargo">—</td>
                  <td class="er-cell-abono">${{ mxn(datos.ingresos.colegiaturas) }}</td>
                  <td class="er-cell-saldo er-saldo--pos">${{ mxn(datos.ingresos.colegiaturas) }}</td>
                </tr>

                <tr class="er-row">
                  <td class="er-cell-concepto">
                    <span class="er-cuenta-num">102</span>
                    Ingresos por Inscripciones (Nuevos Ingresos)
                  </td>
                  <td class="er-cell-ref">{{ datos.ingresos.inscripciones_count }} alumnos</td>
                  <td class="er-cell-cargo">—</td>
                  <td class="er-cell-abono">${{ mxn(datos.ingresos.inscripciones_monto) }}</td>
                  <td class="er-cell-saldo er-saldo--pos">${{ mxn(datos.ingresos.inscripciones_monto) }}</td>
                </tr>

                <tr class="er-subtotal-row er-subtotal--green">
                  <td class="er-cell-concepto font-bold">SUBTOTAL INGRESOS OPERATIVOS</td>
                  <td class="er-cell-ref"></td>
                  <td class="er-cell-cargo">—</td>
                  <td class="er-cell-abono font-bold">${{ mxn(datos.ingresos.total) }}</td>
                  <td class="er-cell-saldo font-bold er-saldo--pos">${{ mxn(datos.ingresos.total) }}</td>
                </tr>

                <!-- ── SECCIÓN: AJUSTES Y NOTAS DE CRÉDITO ── -->
                <tr class="er-section-row">
                  <td colspan="5">
                    <span class="er-section-label er-section-label--red">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m15 9-6 6M9 9l6 6"/><circle cx="12" cy="12" r="10"/></svg>
                      200 — AJUSTES / NOTAS DE CRÉDITO
                    </span>
                  </td>
                </tr>

                <tr class="er-row">
                  <td class="er-cell-concepto">
                    <span class="er-cuenta-num">201</span>
                    Cancelación de Abonos / Reversiones
                  </td>
                  <td class="er-cell-ref">{{ datos.cancelaciones.count }} mov.</td>
                  <td class="er-cell-cargo er-cargo--red">${{ mxn(datos.cancelaciones.total) }}</td>
                  <td class="er-cell-abono">—</td>
                  <td class="er-cell-saldo er-saldo--neg">(${{ mxn(datos.cancelaciones.total) }})</td>
                </tr>

                <tr class="er-subtotal-row er-subtotal--red">
                  <td class="er-cell-concepto font-bold">SUBTOTAL AJUSTES</td>
                  <td class="er-cell-ref"></td>
                  <td class="er-cell-cargo er-cargo--red font-bold">${{ mxn(datos.cancelaciones.total) }}</td>
                  <td class="er-cell-abono">—</td>
                  <td class="er-cell-saldo font-bold er-saldo--neg">(${{ mxn(datos.cancelaciones.total) }})</td>
                </tr>

                <!-- ── INGRESO NETO ── -->
                <tr class="er-neto-row">
                  <td class="er-cell-concepto" colspan="2">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" class="inline mr-2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    INGRESO NETO DEL PERÍODO
                  </td>
                  <td class="er-cell-cargo text-white font-bold">${{ mxn(datos.cancelaciones.total) }}</td>
                  <td class="er-cell-abono text-white font-bold">${{ mxn(datos.ingresos.total) }}</td>
                  <td class="er-neto-val">
                    <span :class="datos.ingreso_neto >= 0 ? 'er-neto-pos' : 'er-neto-neg'">
                      ${{ mxn(datos.ingreso_neto) }}
                    </span>
                  </td>
                </tr>

                <!-- ── SECCIÓN: CARTERA POR COBRAR ── -->
                <tr class="er-section-row">
                  <td colspan="5">
                    <span class="er-section-label er-section-label--blue">
                      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                      300 — CARTERA POR COBRAR
                    </span>
                  </td>
                </tr>

                <tr class="er-row er-row--vencida">
                  <td class="er-cell-concepto">
                    <span class="er-cuenta-num er-cuenta-num--red">301</span>
                    Cartera Vencida — Alumnos en Mora
                    <span class="er-pct-badge er-pct-badge--red">{{ porcentajeVencida }}%</span>
                  </td>
                  <td class="er-cell-ref">{{ datos.cartera.vencida.count }} alumnos</td>
                  <td class="er-cell-cargo er-cargo--red">${{ mxn(datos.cartera.vencida.monto) }}</td>
                  <td class="er-cell-abono">—</td>
                  <td class="er-cell-saldo er-saldo--neg">${{ mxn(datos.cartera.vencida.monto) }}</td>
                </tr>

                <tr class="er-row">
                  <td class="er-cell-concepto">
                    <span class="er-cuenta-num er-cuenta-num--orange">302</span>
                    Vencimientos Esta Semana — Por Gestionar
                  </td>
                  <td class="er-cell-ref">{{ datos.cartera.esta_semana.count }} alumnos</td>
                  <td class="er-cell-cargo er-cargo--orange">${{ mxn(datos.cartera.esta_semana.monto) }}</td>
                  <td class="er-cell-abono">—</td>
                  <td class="er-cell-saldo er-saldo--orange">${{ mxn(datos.cartera.esta_semana.monto) }}</td>
                </tr>

                <tr class="er-row">
                  <td class="er-cell-concepto">
                    <span class="er-cuenta-num er-cuenta-num--green">303</span>
                    Cartera Vigente — Al Corriente
                  </td>
                  <td class="er-cell-ref">{{ datos.cartera.proxima.count }} alumnos</td>
                  <td class="er-cell-cargo">—</td>
                  <td class="er-cell-abono er-abono--green">${{ mxn(datos.cartera.proxima.monto) }}</td>
                  <td class="er-cell-saldo er-saldo--pos">${{ mxn(datos.cartera.proxima.monto) }}</td>
                </tr>

                <tr class="er-subtotal-row">
                  <td class="er-cell-concepto font-bold">TOTAL CARTERA POR COBRAR (Saldo Pendiente Global)</td>
                  <td class="er-cell-ref">{{ datos.cartera.alumnos_deudores }} alumnos</td>
                  <td class="er-cell-cargo">—</td>
                  <td class="er-cell-abono font-bold">${{ mxn(datos.cartera.total) }}</td>
                  <td class="er-cell-saldo font-bold">${{ mxn(datos.cartera.total) }}</td>
                </tr>

              </tbody>
            </table>

            <!-- Firma / timestamp -->
            <div class="er-footer">
              <div class="er-footer__firma">
                <div class="er-footer__line"></div>
                <div class="er-footer__label">Responsable Financiero</div>
              </div>
              <div class="er-footer__info">
                <div>Corte generado por sistema automático</div>
                <div class="font-mono text-xs mt-0.5">{{ new Date().toLocaleString('es-MX') }}</div>
              </div>
              <div class="er-footer__firma">
                <div class="er-footer__line"></div>
                <div class="er-footer__label">Vo.Bo. Dirección</div>
              </div>
            </div>
          </div>

          <!-- ══ DESGLOSES (3 columnas) ══════════════════════════════════════════ -->
          <div class="desglose-grid">

            <!-- Por Banco -->
            <div class="desglose-card">
              <div class="desglose-card__header desglose-card__header--blue">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2"><path d="M3 21h18M3 10h18M5 6l7-3 7 3M4 10v11M20 10v11M8 14v3M12 14v3M16 14v3"/></svg>
                Desglose por Banco / Cuenta
              </div>
              <table class="desglose-table">
                <thead><tr><th>Banco</th><th>Mov.</th><th class="text-right">Monto</th><th class="text-right">%</th></tr></thead>
                <tbody>
                  <tr v-if="!datos.desglose.por_banco.length">
                    <td colspan="4" class="text-center text-gray-400 py-4 text-xs">Sin movimientos</td>
                  </tr>
                  <tr v-for="(b, i) in datos.desglose.por_banco" :key="i">
                    <td class="font-semibold text-xs">{{ b.banco }}</td>
                    <td class="text-center text-xs text-gray-400">{{ b.count }}</td>
                    <td class="text-right font-bold text-xs text-green-700">${{ mxn(b.monto) }}</td>
                    <td class="text-right text-xs text-gray-400">{{ pct(b.monto, datos.ingresos.colegiaturas) }}</td>
                  </tr>
                </tbody>
                <tfoot v-if="datos.desglose.por_banco.length">
                  <tr>
                    <td colspan="2" class="font-bold text-xs">Total</td>
                    <td class="text-right font-bold text-xs text-green-700">${{ mxn(datos.ingresos.colegiaturas) }}</td>
                    <td class="text-right text-xs">100%</td>
                  </tr>
                </tfoot>
              </table>
            </div>

            <!-- Por Diplomado -->
            <div class="desglose-card">
              <div class="desglose-card__header desglose-card__header--navy">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg>
                Desglose por Diplomado / Programa
              </div>
              <table class="desglose-table">
                <thead><tr><th>Programa</th><th>Mov.</th><th class="text-right">Monto</th><th class="text-right">%</th></tr></thead>
                <tbody>
                  <tr v-if="!datos.desglose.por_diplomado.length">
                    <td colspan="4" class="text-center text-gray-400 py-4 text-xs">Sin movimientos</td>
                  </tr>
                  <tr v-for="(d, i) in datos.desglose.por_diplomado" :key="i">
                    <td class="font-semibold text-xs leading-tight">{{ d.diplomado }}</td>
                    <td class="text-center text-xs text-gray-400">{{ d.count }}</td>
                    <td class="text-right font-bold text-xs text-blue-700">${{ mxn(d.monto) }}</td>
                    <td class="text-right text-xs text-gray-400">{{ pct(d.monto, datos.ingresos.colegiaturas) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Por Cajero -->
            <div class="desglose-card">
              <div class="desglose-card__header desglose-card__header--teal">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M19 8v6M22 11h-6"/></svg>
                Desempeño por Cajero / Gestor
              </div>
              <table class="desglose-table">
                <thead><tr><th>Cajero</th><th>Mov.</th><th class="text-right">Monto</th><th class="text-right">%</th></tr></thead>
                <tbody>
                  <tr v-if="!datos.desglose.por_cajero.length">
                    <td colspan="4" class="text-center text-gray-400 py-4 text-xs">Sin movimientos</td>
                  </tr>
                  <tr v-for="(c, i) in datos.desglose.por_cajero" :key="i">
                    <td>
                      <div class="flex items-center gap-2">
                        <div class="cajero-avatar">{{ (c.cajero ?? '?').charAt(0).toUpperCase() }}</div>
                        <span class="text-xs font-semibold">{{ c.cajero }}</span>
                      </div>
                    </td>
                    <td class="text-center text-xs text-gray-400">{{ c.count }}</td>
                    <td class="text-right font-bold text-xs text-teal-700">${{ mxn(c.monto) }}</td>
                    <td class="text-right text-xs text-gray-400">{{ pct(c.monto, datos.ingresos.colegiaturas) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div><!-- /desglose-grid -->

          <!-- ══ INDICADORES SEMÁFORO ══════════════════════════════════════════ -->
          <div class="semaforo-strip">
            <div class="semaforo-card semaforo-card--blue">
              <div class="semaforo-val">${{ mxn(datos.ingreso_neto) }}</div>
              <div class="semaforo-label">Ingreso Neto del Período</div>
              <div class="semaforo-sub">Colegiaturas + Inscripciones − Cancelaciones</div>
            </div>
            <div class="semaforo-card semaforo-card--red">
              <div class="semaforo-val">{{ porcentajeVencida }}%</div>
              <div class="semaforo-label">Índice de Morosidad</div>
              <div class="semaforo-sub">Cartera vencida / cartera total</div>
            </div>
            <div class="semaforo-card semaforo-card--orange">
              <div class="semaforo-val">${{ mxn(datos.cartera.total) }}</div>
              <div class="semaforo-label">Cartera Total por Cobrar</div>
              <div class="semaforo-sub">{{ datos.cartera.alumnos_deudores }} alumnos con saldo pendiente</div>
            </div>
            <div class="semaforo-card" :class="datos.cartera.vencida.monto / (datos.cartera.total || 1) < 0.2 ? 'semaforo-card--green' : 'semaforo-card--red'">
              <div class="semaforo-val">${{ mxn(datos.cartera.vencida.monto) }}</div>
              <div class="semaforo-label">Cartera Vencida</div>
              <div class="semaforo-sub">{{ datos.cartera.vencida.count }} alumnos en mora</div>
            </div>
          </div>

        </div><!-- /df-body -->
      </template>

    </div><!-- /df-root -->
  </AuthenticatedLayout>
</template>

<style scoped>
/* ── Layout base ────────────────────────────────────────────────────────────── */
.df-root {
    background: #eef2f7;
    min-height: 100vh;
    font-family: -apple-system, 'Segoe UI', Roboto, sans-serif;
}

/* ── Barra del sistema (estilo CompaqCont) ──────────────────────────────────── */
.df-system-bar {
    background: #0d2137;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 28px;
    border-bottom: 2px solid #0070f3;
    flex-wrap: wrap;
    gap: 8px;
}
.df-system-bar__left {
    display: flex;
    align-items: center;
    gap: 10px;
}
.df-system-bar__logo {
    width: 28px; height: 28px;
    background: #0070f3;
    border-radius: 5px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.df-system-bar__name   { font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,.9); letter-spacing: 0.04em; }
.df-system-bar__sep    { color: rgba(255,255,255,.3); font-size: 0.8rem; }
.df-system-bar__module { font-size: 0.75rem; color: #60a5fa; font-weight: 600; }
.df-system-bar__date   { font-size: 0.72rem; color: rgba(255,255,255,.5); }

/* ── Barra de corte ─────────────────────────────────────────────────────────── */
.df-corte-bar {
    background: #fff;
    border-bottom: 1px solid #dde3ea;
    padding: 12px 28px;
    display: flex;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}
.df-corte-bar__label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #1a3a5c;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    white-space: nowrap;
}
.df-corte-bar__inputs {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.df-corte-bar__arrow { color: #94a3b8; font-weight: bold; }
.df-corte-bar__periodo {
    margin-left: auto;
    font-size: 0.78rem;
    color: #1a3a5c;
    font-weight: 600;
    background: #e0e7ff;
    padding: 4px 14px;
    border-radius: 999px;
}
.df-date-group { display: flex; flex-direction: column; gap: 2px; }
.df-date-label { font-size: 0.65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.df-date-input {
    padding: 6px 10px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.8rem;
    color: #1e293b;
    background: #f8fafc;
    outline: none;
}
.df-date-input:focus { border-color: #1a3a5c; background: #fff; }

.df-consultar-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 18px;
    background: #1a3a5c;
    color: #fff;
    border: none;
    border-radius: 7px;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.15s;
}
.df-consultar-btn:hover:not(:disabled) { background: #142d47; }
.df-consultar-btn:disabled { opacity: 0.5; cursor: not-allowed; }

@keyframes spin { to { transform: rotate(360deg); } }
.df-spin { animation: spin 0.8s linear infinite; }

/* Loading / Error */
.df-loading { text-align: center; padding: 60px; color: #64748b; }
.df-loading p { margin-top: 14px; font-size: 0.85rem; }
.df-error { display: flex; align-items: center; gap: 10px; padding: 20px 28px; background: #fef2f2; color: #dc2626; font-size: 0.85rem; border-bottom: 1px solid #fecaca; }

/* ── Body ───────────────────────────────────────────────────────────────────── */
.df-body { padding: 20px 28px 50px; display: flex; flex-direction: column; gap: 20px; }

/* ══ ESTADO DE RESULTADOS ════════════════════════════════════════════════════ */
.er-container {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 12px;
    overflow: hidden;
}

.er-title-block {
    background: #0d2137;
    padding: 16px 24px;
    border-bottom: 3px solid #0070f3;
    text-align: center;
}
.er-title-block__main { font-size: 1.1rem; font-weight: 900; color: #fff; letter-spacing: 0.15em; text-transform: uppercase; }
.er-title-block__sub  { font-size: 0.78rem; color: #60a5fa; margin-top: 3px; font-weight: 600; }

.er-table { width: 100%; border-collapse: collapse; font-size: 0.8rem; }
.er-col-concepto { width: auto; }
.er-col-num      { width: 90px; }
.er-col-monto    { width: 160px; }
.er-col-saldo    { width: 160px; }

.er-table thead tr { background: #1a3a5c; }
.er-table thead th {
    padding: 10px 14px;
    text-align: left;
    font-size: 0.68rem;
    font-weight: 700;
    color: rgba(255,255,255,.85);
    text-transform: uppercase;
    letter-spacing: 0.06em;
    border-right: 1px solid rgba(255,255,255,.08);
}
.er-th--cargo { color: #fca5a5; }
.er-th--abono { color: #86efac; }

/* Sección */
.er-section-row td { background: #f1f5f9; padding: 8px 14px 6px; border-top: 1px solid #dde3ea; border-bottom: 1px solid #e2e8f0; }
.er-section-label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 800;
    color: #1a3a5c;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.er-section-label--red  { color: #dc2626; }
.er-section-label--blue { color: #1a3a5c; }

/* Filas normales */
.er-row td { padding: 8px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.er-row:nth-child(even) td { background: #f9fafb; }
.er-row:hover td { background: #eff6ff !important; }
.er-row--vencida td { background: #fff5f5; }
.er-row--vencida:hover td { background: #fee2e2 !important; }

.er-cell-concepto { color: #1e293b; padding-left: 28px !important; }
.er-cell-ref      { text-align: center; color: #94a3b8; font-size: 0.72rem; white-space: nowrap; }
.er-cell-cargo    { text-align: right; color: #dc2626; font-family: 'Courier New', monospace; white-space: nowrap; }
.er-cell-abono    { text-align: right; color: #16a34a; font-family: 'Courier New', monospace; white-space: nowrap; }
.er-cell-saldo    { text-align: right; font-family: 'Courier New', monospace; font-weight: 600; border-left: 2px solid #e2e8f0; white-space: nowrap; }

.er-cargo--red    { color: #dc2626; font-weight: 700; }
.er-cargo--orange { color: #f97316; font-weight: 700; }
.er-abono--green  { color: #16a34a; font-weight: 700; }
.er-saldo--pos    { color: #16a34a; }
.er-saldo--neg    { color: #dc2626; }
.er-saldo--orange { color: #f97316; }

.er-cuenta-num {
    display: inline-block;
    margin-right: 8px;
    font-size: 0.65rem;
    font-weight: 700;
    color: #94a3b8;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 3px;
    padding: 1px 5px;
    font-family: monospace;
}
.er-cuenta-num--red    { color: #dc2626; background: #fef2f2; border-color: #fecaca; }
.er-cuenta-num--orange { color: #f97316; background: #fff7ed; border-color: #fed7aa; }
.er-cuenta-num--green  { color: #16a34a; background: #f0fdf4; border-color: #bbf7d0; }

.er-pct-badge {
    display: inline-block;
    margin-left: 8px;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 999px;
}
.er-pct-badge--red { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

/* Subtotales */
.er-subtotal-row td { padding: 9px 14px; border-top: 2px solid #e2e8f0; border-bottom: 2px solid #e2e8f0; background: #f8fafc; }
.er-subtotal--green td { background: #f0fdf4; border-top-color: #bbf7d0; border-bottom-color: #bbf7d0; }
.er-subtotal--red   td { background: #fef2f2; border-top-color: #fecaca; border-bottom-color: #fecaca; }

/* Fila neta */
.er-neto-row td {
    padding: 13px 14px;
    background: #0d2137 !important;
    color: white;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    border-top: 3px solid #0070f3;
}
.er-neto-val { text-align: right; font-family: 'Courier New', monospace; border-left: 2px solid rgba(255,255,255,.2) !important; }
.er-neto-pos { color: #86efac; font-size: 1.05rem; font-weight: 900; }
.er-neto-neg { color: #fca5a5; font-size: 1.05rem; font-weight: 900; }

/* Firma */
.er-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 16px 28px 20px;
    border-top: 1px solid #e2e8f0;
    background: #f8fafc;
}
.er-footer__firma { text-align: center; }
.er-footer__line  { width: 180px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px; }
.er-footer__label { font-size: 0.68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.er-footer__info  { text-align: center; font-size: 0.72rem; color: #94a3b8; }

/* ══ DESGLOSES ════════════════════════════════════════════════════════════════ */
.desglose-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
@media (max-width: 1000px) { .desglose-grid { grid-template-columns: 1fr; } }

.desglose-card {
    background: #fff;
    border: 1px solid #dde3ea;
    border-radius: 10px;
    overflow: hidden;
}
.desglose-card__header {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.desglose-card__header--blue  { background: linear-gradient(135deg, #1a3a5c, #0070f3); }
.desglose-card__header--navy  { background: linear-gradient(135deg, #312e81, #6366f1); }
.desglose-card__header--teal  { background: linear-gradient(135deg, #0f4c3a, #059669); }

.desglose-table { width: 100%; border-collapse: collapse; }
.desglose-table thead tr { background: #f8fafc; }
.desglose-table thead th { padding: 7px 12px; text-align: left; font-size: 0.67rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; border-bottom: 1px solid #e2e8f0; }
.desglose-table td { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.desglose-table tfoot td { padding: 8px 12px; background: #f1f5f9; border-top: 2px solid #e2e8f0; }
.desglose-table tbody tr:hover td { background: #eff6ff; }

.cajero-avatar {
    width: 24px; height: 24px;
    border-radius: 50%;
    background: #1a3a5c;
    color: #fff;
    font-size: 0.68rem;
    font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* ══ SEMÁFORO ════════════════════════════════════════════════════════════════ */
.semaforo-strip {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
}
@media (max-width: 900px) { .semaforo-strip { grid-template-columns: repeat(2, 1fr); } }

.semaforo-card {
    border-radius: 10px;
    padding: 18px 20px;
    color: white;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}
.semaforo-card--blue   { background: linear-gradient(135deg, #1a3a5c 0%, #0070f3 100%); }
.semaforo-card--red    { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%); }
.semaforo-card--orange { background: linear-gradient(135deg, #92400e 0%, #f97316 100%); }
.semaforo-card--green  { background: linear-gradient(135deg, #14532d 0%, #16a34a 100%); }

.semaforo-val   { font-size: 1.35rem; font-weight: 900; color: white; line-height: 1.2; }
.semaforo-label { font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,.8); text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px; }
.semaforo-sub   { font-size: 0.68rem; color: rgba(255,255,255,.55); margin-top: 2px; }
</style>

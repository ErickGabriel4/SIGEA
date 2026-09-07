/*
 * JavaScript do SIGEA.
 * jQuery/Bootstrap vêm dos assets do AdminLTE (public/vendor).
 */

/*
 * Wizard de notificação (T04): navegação entre passos com validação HTML5.
 */
window.SigeaWizard = {
    passoAtual: 1,
    total: 4,

    irPara(passo) {
        document.querySelectorAll('.wizard-passo').forEach((p) => p.classList.add('d-none'));
        const alvo = document.getElementById('wizard-passo-' + passo);
        if (alvo) alvo.classList.remove('d-none');

        document.querySelectorAll('.stepper .passo').forEach((s, i) => {
            s.classList.toggle('ativo', i + 1 === passo);
            s.classList.toggle('concluido', i + 1 < passo);
        });

        this.passoAtual = passo;
        window.scrollTo({ top: 0, behavior: 'smooth' });

        if (passo === this.total && typeof window.montarRevisao === 'function') {
            window.montarRevisao();
        }
    },

    avancar() {
        const passoEl = document.getElementById('wizard-passo-' + this.passoAtual);
        const campos = passoEl.querySelectorAll('input, select, textarea');
        for (const campo of campos) {
            if (!campo.checkValidity()) {
                campo.reportValidity();
                return;
            }
        }
        if (this.passoAtual < this.total) this.irPara(this.passoAtual + 1);
    },

    voltar() {
        if (this.passoAtual > 1) this.irPara(this.passoAtual - 1);
    },
};

/*
 * Cálculo da matriz de risco em tempo real (tela de triagem — T10).
 * Elementos esperados: #probabilidade, #severidade, #score-risco,
 * #nivel-risco, #prioridade (select), #prazo_conclusao (input date).
 */
window.calcularRisco = function () {
    const probEl = document.getElementById('probabilidade');
    const sevEl = document.getElementById('severidade');
    if (!probEl || !sevEl) return;

    const prob = parseInt(probEl.value || '0', 10);
    const sev = parseInt(sevEl.value || '0', 10);

    const probOut = document.getElementById('probabilidade-valor');
    const sevOut = document.getElementById('severidade-valor');
    if (probOut) probOut.textContent = prob || '—';
    if (sevOut) sevOut.textContent = sev || '—';

    const scoreEl = document.getElementById('score-risco');
    const nivelEl = document.getElementById('nivel-risco');
    if (!prob || !sev) {
        if (scoreEl) scoreEl.textContent = '—';
        if (nivelEl) nivelEl.textContent = '—';
        return;
    }

    const score = prob * sev;
    let nivel, classe, sla;
    if (score <= 4) { nivel = 'Baixo'; classe = 'success'; sla = 60; }
    else if (score <= 9) { nivel = 'Moderado'; classe = 'warning'; sla = 45; }
    else if (score <= 15) { nivel = 'Alto'; classe = 'orange'; sla = 30; }
    else { nivel = 'Extremo'; classe = 'danger'; sla = 15; }

    if (scoreEl) scoreEl.textContent = score;
    if (nivelEl) {
        nivelEl.textContent = nivel;
        nivelEl.className = 'badge badge-' + classe;
    }

    // Prioridade sugerida (sem sobrescrever escolha manual do gestor)
    const prioridadeEl = document.getElementById('prioridade');
    if (prioridadeEl && !prioridadeEl.dataset.manual) {
        const mapa = { Baixo: 'baixa', Moderado: 'media', Alto: 'alta', Extremo: 'critica' };
        prioridadeEl.value = mapa[nivel];
    }

    // Prazo auto-preenchido pelo SLA (editável)
    const prazoEl = document.getElementById('prazo_conclusao');
    if (prazoEl && !prazoEl.dataset.manual) {
        const prazo = new Date();
        prazo.setDate(prazo.getDate() + sla);
        prazoEl.value = prazo.toISOString().slice(0, 10);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    ['probabilidade', 'severidade'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', window.calcularRisco);
    });

    ['prioridade', 'prazo_conclusao'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', () => { el.dataset.manual = '1'; });
    });

    if (document.getElementById('probabilidade')) {
        window.calcularRisco();
    }
});

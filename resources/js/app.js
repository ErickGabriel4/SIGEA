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

/*
 * Diagrama de Ishikawa em SVG (T12), desenhado a partir das causas cadastradas.
 * dados: { pessoa: [{descricao, raiz}], processo: [...], ... }
 */
window.desenharIshikawa = function (svgId, dados, efeito) {
    const svg = document.getElementById(svgId);
    if (!svg) return;

    const eixosSuperiores = ['pessoa', 'processo', 'equipamento'];
    const eixosInferiores = ['ambiente', 'material', 'gestao'];
    const rotulos = {
        pessoa: 'Pessoa', processo: 'Processo', equipamento: 'Equipamento',
        ambiente: 'Ambiente', material: 'Material', gestao: 'Gestão',
    };

    const larg = 1000;
    const alt = 460;
    svg.setAttribute('viewBox', `0 0 ${larg} ${alt}`);
    svg.innerHTML = '';

    const ns = 'http://www.w3.org/2000/svg';
    const linha = (x1, y1, x2, y2, cor = '#495057', esp = 2) => {
        const l = document.createElementNS(ns, 'line');
        l.setAttribute('x1', x1); l.setAttribute('y1', y1);
        l.setAttribute('x2', x2); l.setAttribute('y2', y2);
        l.setAttribute('stroke', cor); l.setAttribute('stroke-width', esp);
        svg.appendChild(l);
    };
    const texto = (x, y, conteudo, opts = {}) => {
        const t = document.createElementNS(ns, 'text');
        t.setAttribute('x', x); t.setAttribute('y', y);
        t.setAttribute('font-size', opts.tam || 12);
        t.setAttribute('fill', opts.cor || '#212529');
        if (opts.peso) t.setAttribute('font-weight', opts.peso);
        if (opts.ancora) t.setAttribute('text-anchor', opts.ancora);
        t.textContent = conteudo;
        svg.appendChild(t);
    };

    const meioY = alt / 2;
    const cabecaX = larg - 150;

    // Espinha central + cabeça (efeito)
    linha(40, meioY, cabecaX, meioY, '#212529', 3);
    const cabeca = document.createElementNS(ns, 'rect');
    cabeca.setAttribute('x', cabecaX); cabeca.setAttribute('y', meioY - 42);
    cabeca.setAttribute('width', 140); cabeca.setAttribute('height', 84);
    cabeca.setAttribute('rx', 8);
    cabeca.setAttribute('fill', '#dc3545');
    svg.appendChild(cabeca);

    const quebrado = (efeito || 'Evento adverso').match(/.{1,18}(\s|$)/g) || [];
    quebrado.slice(0, 4).forEach((parte, i) => {
        texto(cabecaX + 70, meioY - 20 + i * 16, parte.trim(), { cor: '#fff', ancora: 'middle', tam: 12, peso: 700 });
    });

    const desenharEixo = (nome, indice, superior) => {
        const baseX = 150 + indice * 260;
        const topoY = superior ? 46 : alt - 46;
        const juntaX = baseX + 130;

        linha(baseX, topoY, juntaX, meioY, '#6c757d', 2);
        texto(baseX, superior ? topoY - 10 : topoY + 20, rotulos[nome], { peso: 700, tam: 14 });

        const causas = (dados[nome] || []).slice(0, 4);
        causas.forEach((causa, i) => {
            const frac = (i + 1) / (causas.length + 1);
            const cx = baseX + (juntaX - baseX) * frac;
            const cy = topoY + (meioY - topoY) * frac;
            linha(cx, cy, cx - 85, cy, causa.raiz ? '#dc3545' : '#adb5bd', causa.raiz ? 2.5 : 1.5);
            const rotulo = causa.descricao.length > 30 ? causa.descricao.slice(0, 28) + '…' : causa.descricao;
            texto(cx - 88, cy - 4, (causa.raiz ? '★ ' : '') + rotulo, {
                ancora: 'end', tam: 10, cor: causa.raiz ? '#dc3545' : '#495057', peso: causa.raiz ? 700 : 400,
            });
        });
    };

    eixosSuperiores.forEach((nome, i) => desenharEixo(nome, i, true));
    eixosInferiores.forEach((nome, i) => desenharEixo(nome, i, false));
};

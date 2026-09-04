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

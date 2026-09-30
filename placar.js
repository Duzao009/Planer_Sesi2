// ============================================
// ATUALIZAÇÃO DE PLACAR EM TEMPO REAL
// ============================================

let intervaloAtualizacao;
let ultimaAtualizacao = {};

function atualizarPlacar() {
    fetch((window.PLANER_BASE_URL || '') + '/api/get_placar.php')
        .then(res => res.json())
        .then(data => {
            if (data.sucesso && data.dados) {
                data.dados.forEach(placar => {
                    const id = placar.jogo_id;
                    
                    // Verificar se mudou
                    const chave = `${placar.placar_a}-${placar.placar_b}`;
                    const mudou = ultimaAtualizacao[id] !== chave;
                    
                    if (mudou) {
                        ultimaAtualizacao[id] = chave;
                        
                        // Atualizar placar A
                        const elA = document.getElementById(`placar-a-${id}`);
                        if (elA && elA.textContent !== placar.placar_a.toString()) {
                            elA.textContent = placar.placar_a;
                            piscarElemento(elA);
                        }
                        
                        // Atualizar placar B
                        const elB = document.getElementById(`placar-b-${id}`);
                        if (elB && elB.textContent !== placar.placar_b.toString()) {
                            elB.textContent = placar.placar_b;
                            piscarElemento(elB);
                        }
                        
                        // Atualizar nomes dos times
                        const timeA = document.getElementById(`time-a-nome-${id}`);
                        if (timeA) timeA.textContent = placar.time_a;
                        
                        const timeB = document.getElementById(`time-b-nome-${id}`);
                        if (timeB) timeB.textContent = placar.time_b;
                        
                        // Atualizar status badge
                        const badge = document.getElementById(`status-badge-${id}`);
                        if (badge) {
                            badge.textContent = traduzirStatus(placar.status);
                        }
                        
                        // Atualizar horário
                        const horario = document.getElementById(`atualizado-${id}`);
                        if (horario) {
                            horario.textContent = `Última atualização: ${data.hora}`;
                        }
                    }
                });
            }
        })
        .catch(err => console.log('Erro ao atualizar placar:', err));
}

function piscarElemento(el) {
    el.style.transform = 'scale(1.3)';
    el.style.color = '#FF0000';
    el.style.transition = 'all 0.3s';
    
    setTimeout(() => {
        el.style.transform = 'scale(1)';
        el.style.color = '#CC0000';
    }, 500);
}

function traduzirStatus(status) {
    const map = {
        'aguardando': 'Aguardando',
        'em_andamento': 'Em Andamento',
        'finalizado': 'Finalizado'
    };
    return map[status] || status;
}

// Iniciar atualização automática a cada 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('placarContainer')) {
        atualizarPlacar();
        intervaloAtualizacao = setInterval(atualizarPlacar, 5000);
    }
});

// Parar quando sair da aba
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(intervaloAtualizacao);
    } else {
        atualizarPlacar();
        intervaloAtualizacao = setInterval(atualizarPlacar, 5000);
    }
});
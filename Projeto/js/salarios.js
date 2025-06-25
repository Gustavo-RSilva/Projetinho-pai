let cargos = [];

async function carregarSalarios() {
  const res = await fetch('../salario.txt');
  const texto = await res.text();
  const linhas = texto.split('\n');
  cargos = linhas.map(linha => {
    const [nome, salario] = linha.trim().split(';');
    return { nome, salario };
  });
  exibirSugestoes();
}

function exibirSugestoes() {
  const container = document.getElementById('cardsContainer');
  container.innerHTML = '';

  cargos.slice(0, 4).forEach(cargo => {
    const col = document.createElement('div');
    col.className = 'col-md-3';
    col.innerHTML = `
      <div class="card-cargo h-100">
        <h5>${cargo.nome}</h5>
        <p>R$ ${cargo.salario}/mês</p>
      </div>`;
    container.appendChild(col);
  });
}

document.getElementById('formPesquisa').addEventListener('submit', function (e) {
  e.preventDefault();
  const termo = document.getElementById('inputCargo').value.toLowerCase();
  const resultado = cargos.find(c => c.nome.toLowerCase().includes(termo));
  const saida = document.getElementById('resultadoBusca');

  // Limpa resultados anteriores
  saida.innerHTML = '';

  if (resultado) {
    saida.innerHTML = `
      <div class="alert alert-success d-flex align-items-center" role="alert">
        <i class="material-icons me-2">check_circle</i>
        <div>
          O salário de <strong>"${resultado.nome}"</strong> é <strong>R$ ${resultado.salario}/mês</strong>.
        </div>
      </div>`;
  } else {
    saida.innerHTML = `
      <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="material-icons me-2">error</i>
        <div>
          Cargo não encontrado.
        </div>
      </div>`;
  }
});

carregarSalarios();
const inputCargo = document.getElementById('inputCargo');
const sugestoesLista = document.getElementById('sugestoesLista');

inputCargo.addEventListener('input', () => {
  const termo = inputCargo.value.toLowerCase();
  sugestoesLista.innerHTML = '';

  if (termo.length < 2) return;

  const sugestoes = cargos
    .filter(c => c.nome.toLowerCase().includes(termo))
    .slice(0, 5); // máximo de 5 sugestões

  sugestoes.forEach(cargo => {
    const li = document.createElement('li');
    li.textContent = cargo.nome;
    li.classList.add('list-group-item');
    li.addEventListener('click', () => {
      inputCargo.value = cargo.nome;
      sugestoesLista.innerHTML = '';
    });
    sugestoesLista.appendChild(li);
  });
});

// Oculta sugestões se clicar fora
document.addEventListener('click', e => {
  if (!inputCargo.contains(e.target)) {
    sugestoesLista.innerHTML = '';
  }
});

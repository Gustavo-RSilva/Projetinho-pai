function filtrarVagas() {
    const termo = document.getElementById("vagaSearchInput").value.toLowerCase();
    const vagas = document.querySelectorAll("#jobList .list-group-item");

    vagas.forEach(vaga => {
        const conteudo = vaga.textContent.toLowerCase();
        vaga.style.display = conteudo.includes(termo) ? "flex" : "none";
    });
}

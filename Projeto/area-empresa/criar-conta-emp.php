<?php
session_start();
include("../db/conexao.php");

// Inicializar variáveis de erro/sucesso
$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cnpj = trim($_POST['cnpj']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $ramo_atuacao = trim($_POST['ramo_atuacao']);
    $descricao = trim($_POST['descricao'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $endereco = trim($_POST['endereco'] ?? '');
    $logo = './img/default-company.png'; // Valor padrão

    // Validações
    if (empty($nome) || empty($cnpj) || empty($email) || empty($senha) || empty($ramo_atuacao)) {
        $erro = "Todos os campos obrigatórios devem ser preenchidos!";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres!";
    } else {
        // Limpar CNPJ para validação (remover pontos, traços e barras)
        $cnpj_limpo = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj_limpo) !== 14) {
            $erro = "CNPJ deve ter 14 dígitos!";
        } else {
            // Verificar se email já existe
            $sql = "SELECT id_empresa FROM empresas WHERE email = ? OR cnpj = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $email, $cnpj); // Agora usando o CNPJ com máscara
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $erro = "Email ou CNPJ já cadastrado!";
            }
            $stmt->close();
        }
    }

    // Se não há erros, processar o cadastro
    if (empty($erro)) {
        // Processar upload do logo (se existir)
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            // Definir diretório para upload
            // Diretório real no servidor
            $uploadDir = __DIR__ . '/../img/logo-empresa/';

            // Criar pasta se não existir
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Informações do arquivo
            $fileName = $_FILES['logo']['name'];
            $fileTmp = $_FILES['logo']['tmp_name'];
            $fileSize = $_FILES['logo']['size'];
            $fileError = $_FILES['logo']['error'];

            // Extensão do arquivo
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($fileExt, $allowedExt)) {
                if ($fileError === 0) {
                    if ($fileSize <= 2 * 1024 * 1024) {
                        $newFileName = uniqid('empresa_', true) . '.' . $fileExt;
                        $fileDestination = $uploadDir . $newFileName;

                        if (move_uploaded_file($fileTmp, $fileDestination)) {
                            // Caminho salvo no banco (relativo, igual já está sendo usado)
                            $logo = '../img/logo-empresa/' . $newFileName;
                        } else {
                            $erro = "Erro ao mover o arquivo para a pasta destino.";
                        }
                    } else {
                        $erro = "O arquivo é muito grande. Máximo permitido: 2MB.";
                    }
                } else {
                    $erro = "Erro no upload do arquivo. Código: " . $fileError;
                }
            } else {
                $erro = "Formato de arquivo não permitido. Use JPG ou PNG.";
            }
        } elseif (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            // Se há um arquivo mas ocorreu erro no upload
            switch ($_FILES['logo']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $erro = "O arquivo é muito grande. Tamanho máximo permitido: 2MB.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $erro = "O upload do arquivo foi interrompido.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    // Nenhum arquivo enviado, não é um erro
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $erro = "Pasta temporária não configurada.";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $erro = "Falha ao gravar o arquivo no disco.";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $erro = "Uma extensão do PHP interrompeu o upload.";
                    break;
                default:
                    $erro = "Erro desconhecido no upload.";
                    break;
            }
        }

        // Se ainda não há erros, inserir no banco
        if (empty($erro)) {
            // Hash da senha
            $senha_hash = md5($senha);

            // Inserir no banco de dados com CNPJ formatado
            $sql = "INSERT INTO empresas (nome, cnpj, email, senha, ramo_atuacao, descricao, website, telefone, endereco, url_logo) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssss", $nome, $cnpj, $email, $senha_hash, $ramo_atuacao, $descricao, $website, $telefone, $endereco, $logo);

            if ($stmt->execute()) {
                // Configurar sessão para empresa
                $_SESSION['id_empresa'] = $stmt->insert_id;
                $_SESSION['email_empresa'] = $email;
                $_SESSION['nome_empresa'] = $nome;
                $_SESSION['tipo'] = 'empresa';

                // Redirecionar para index.php
                header("Location: index.php?sucesso=" . urlencode("Empresa cadastrada com sucesso!"));
                exit();
            } else {
                $erro = "Erro ao cadastrar empresa. Tente novamente.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Cadastro de Empresa</title>
    <link rel="icon" href="../img/icon/icone-pag.png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        /* icon olhos */
        .eye-icon {
            right: 12px;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            width: 24px !important;
            height: 24px !important;
            z-index: 2;
            font-size: 24px;
            /* Para ícones de fonte */
            width: 25px !important;
            height: 25px !important
        }

        .input-with-icon {
            position: relative;
        }

        .form-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .logo-preview {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border: 2px dashed #007bff;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 5px;
            background-color: #f8f9fa;
        }

        .upload-label {
            cursor: pointer;
            color: #007bff;
        }

        .form-title {
            color: #007bff;
            margin-bottom: 30px;
            text-align: center;
        }

        .required-field::after {
            content: " *";
            color: red;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="form-container">
            <h2 class="form-title">Cadastro de Empresa</h2>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="text-center mb-4">
                    <img src="./img/default-company.png" class="logo-preview" id="preview">
                    <label for="logo" class="upload-label d-block">
                        <i class="bi bi-camera-fill"></i> Escolher Logo (opcional)
                    </label>
                    <input type="file" name="logo" id="logo" accept="image/*" class="d-none">
                    <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB</div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome" class="form-label required-field">Nome da Empresa</label>
                        <input type="text" class="form-control" id="nome" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="cnpj" class="form-label required-field">CNPJ</label>
                        <input type="text" class="form-control" id="cnpj" name="cnpj" placeholder="00.000.000/0000-00" value="<?php echo isset($_POST['cnpj']) ? htmlspecialchars($_POST['cnpj']) : ''; ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label required-field">Email Corporativo</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="senha" class="form-label required-field">Senha</label>
                        <div class="input-with-icon">
                            <input type="password" class="form-control password-input" id="senha" name="senha" required minlength="6">
                            <img src="../img/view.png" class="toggle-password eye-icon" alt="Mostrar senha" title="Mostrar senha">
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="confirmar_senha" class="form-label required-field">Confirmar Senha</label>
                        <div class="input-with-icon">
                            <input type="password" class="form-control password-input" id="confirmar_senha" name="confirmar_senha" required>
                            <img src="../img/view.png" class="toggle-password eye-icon" alt="Mostrar senha" title="Mostrar senha">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="ramo_atuacao" class="form-label required-field">Ramo de Atuação</label>
                        <select class="form-select" id="ramo_atuacao" name="ramo_atuacao" required>
                            <option value="">Selecione um ramo</option>
                            <option value="Tecnologia" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Tecnologia') ? 'selected' : ''; ?>>Tecnologia</option>
                            <option value="Saúde" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Saúde') ? 'selected' : ''; ?>>Saúde</option>
                            <option value="Educação" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Educação') ? 'selected' : ''; ?>>Educação</option>
                            <option value="Finanças" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Finanças') ? 'selected' : ''; ?>>Finanças</option>
                            <option value="Varejo" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Varejo') ? 'selected' : ''; ?>>Varejo</option>
                            <option value="Indústria" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Indústria') ? 'selected' : ''; ?>>Indústria</option>
                            <option value="Serviços" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Serviços') ? 'selected' : ''; ?>>Serviços</option>
                            <option value="Construção Civil" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Construção Civil') ? 'selected' : ''; ?>>Construção Civil</option>
                            <option value="Alimentação" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Alimentação') ? 'selected' : ''; ?>>Alimentação</option>
                            <option value="Transporte" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Transporte') ? 'selected' : ''; ?>>Transporte</option>
                            <option value="Outros" <?php echo (isset($_POST['ramo_atuacao']) && $_POST['ramo_atuacao'] == 'Outros') ? 'selected' : ''; ?>>Outros</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição da Empresa</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3" placeholder="Breve descrição sobre a empresa e suas atividades"><?php echo isset($_POST['descricao']) ? htmlspecialchars($_POST['descricao']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="website" class="form-label">Site</label>
                        <input type="url" class="form-control" id="website" name="website" placeholder="https://" value="<?php echo isset($_POST['website']) ? htmlspecialchars($_POST['website']) : ''; ?>">
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Telefone</label>
                        <input type="tel" class="form-control" id="telefone" name="telefone" placeholder="(00) 00000-0000" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
                    </div>

                    <div class="mb-4">
                        <label for="endereco" class="form-label">Endereço</label>
                        <input type="text" class="form-control" id="endereco" name="endereco" placeholder="Rua, número, complemento" value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">Cadastrar Empresa</button>

                    <div class="mt-3 text-center">
                        Já tem uma conta? <a href="login_empresa.php">Faça login</a>
                    </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.toggle-password');

            toggles.forEach(function(toggle) {
                toggle.addEventListener('click', function() {
                    const input = this.previousElementSibling; // pega o input antes do ícone
                    if (input && input.classList.contains('password-input')) {
                        const type = input.type === 'password' ? 'text' : 'password';
                        input.type = type;

                        // Alterna ícone
                        if (type === 'password') {
                            this.src = '../img/view.png';
                            this.alt = 'Mostrar senha';
                            this.title = 'Mostrar senha';
                        } else {
                            this.src = '../img/hidden.png';
                            this.alt = 'Ocultar senha';
                            this.title = 'Ocultar senha';
                        }
                    }
                });
            });
        });
    </script>

    <script>
        // Preview do logo selecionado
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Verificar tamanho do arquivo (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    alert('O arquivo deve ter no máximo 2MB');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('preview').src = event.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Máscara para CNPJ
        document.getElementById('cnpj').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 14) value = value.slice(0, 14);

            if (value.length > 12) {
                value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, "$1.$2.$3/$4-$5");
            } else if (value.length > 8) {
                value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})/, "$1.$2.$3/$4");
            } else if (value.length > 5) {
                value = value.replace(/(\d{2})(\d{3})(\d{3})/, "$1.$2.$3");
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{3})/, "$1.$2");
            }
            e.target.value = value;
        });

        // Máscara para telefone
        document.getElementById('telefone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);

            if (value.length > 6) {
                value = value.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
            } else if (value.length > 2) {
                value = value.replace(/(\d{2})(\d{4})/, "($1) $2");
            } else if (value.length > 0) {
                value = value.replace(/(\d{2})/, "($1)");
            }
            e.target.value = value;
        });

        // Validação de senha
        document.querySelector('form').addEventListener('submit', function(e) {
            const senha = document.getElementById('senha').value;
            const confirmarSenha = document.getElementById('confirmar_senha').value;

            if (senha !== confirmarSenha) {
                e.preventDefault();
                alert('As senhas não coincidem!');
                return false;
            }

            if (senha.length < 6) {
                e.preventDefault();
                alert('A senha deve ter pelo menos 6 caracteres!');
                return false;
            }

            // Validação básica de CNPJ (apenas formato)
            const cnpj = document.getElementById('cnpj').value.replace(/\D/g, '');
            if (cnpj.length !== 14) {
                e.preventDefault();
                alert('CNPJ deve ter 14 dígitos!');
                return false;
            }
        });
    </script>
</body>

</html>
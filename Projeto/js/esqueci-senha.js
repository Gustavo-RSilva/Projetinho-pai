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
                  this.src = './img/view.png';
                  this.alt = 'Mostrar senha';
                  this.title = 'Mostrar senha';
              } else {
                  this.src = './img/hidden.png';
                  this.alt = 'Ocultar senha';
                  this.title = 'Ocultar senha';
              }
          }
      });
  });
});


let currentEmail = '';

function goToStep(step) {
  document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
  document.getElementById(`step${step}`).classList.add('active');
}
function clearErrors() {
  document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
  document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
}

document.getElementById('btnSendCode').addEventListener('click', async () => {
  clearErrors();
  const email = document.getElementById('email').value.trim();
  const emailField = document.getElementById('email');
  const emailError = document.getElementById('emailError');

  if (!email) {
    emailField.classList.add('is-invalid');
    emailError.textContent = 'Digite um e-mail';
    return;
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    emailField.classList.add('is-invalid');
    emailError.textContent = 'Digite um e-mail válido';
    return;
  }

  try {
    const res = await fetch('config-esqueci.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'enviar_codigo', email })
    });
    const data = await res.json();

    if (data.status === 'ok') {
      currentEmail = email;
      document.getElementById('emailDisplay').textContent = email;
      goToStep(2);
    } else {
      emailField.classList.add('is-invalid');
      emailError.textContent = data.msg || 'Erro ao enviar código';
    }
  } catch (e) {
    emailField.classList.add('is-invalid');
    emailError.textContent = 'Falha de conexão';
  }
});

document.getElementById('btnResendCode').addEventListener('click', () => {
  document.getElementById('btnSendCode').click();
});

document.getElementById('btnVerifyCode').addEventListener('click', async () => {
  clearErrors();
  const codeField = document.getElementById('code');
  const code = codeField.value.trim();

  if (!/^\d{6}$/.test(code)) {
    codeField.classList.add('is-invalid');
    document.getElementById('codeError').textContent = 'Digite um código de 6 dígitos';
    return;
  }

  const res = await fetch('config-esqueci.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ action: 'verificar_codigo', email: currentEmail, code })
  });
  const data = await res.json();

  if (data.status === 'ok') {
    goToStep(3);
  } else {
    codeField.classList.add('is-invalid');
    document.getElementById('codeError').textContent = data.msg || 'Código inválido';
  }
});

document.getElementById('btnChangePassword').addEventListener('click', async () => {
  clearErrors();
  const pass = document.getElementById('newPassword').value;
  const conf = document.getElementById('confirmPassword').value;
  const passErr = document.getElementById('passwordError');

  if (pass.length < 8) {
    document.getElementById('newPassword').classList.add('is-invalid');
    passErr.textContent = 'Mínimo 8 caracteres';
    return;
  }
  if (pass !== conf) {
    document.getElementById('confirmPassword').classList.add('is-invalid');
    passErr.textContent = 'Senhas não coincidem';
    return;
  }

  const res = await fetch('config-esqueci.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ action: 'alterar_senha', email: currentEmail, senha: pass })
  });
  const data = await res.json();

  if (data.status === 'ok') {
    document.getElementById('successMessage').classList.remove('d-none');
    setTimeout(() => window.location.href = '/../area-exclusiva/pag-minha-conta.php', 3000);
  } else {
    alert(data.msg || 'Erro ao atualizar senha');
  }
});

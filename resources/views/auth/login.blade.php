<x-guest-layout>
 @section('robots', 'noindex, nofollow')
 @section('title', 'Iniciar sesión')
    <section class="login">
        <div class="contenedor">
            @if (session('status'))
                <div class="alert alert-success mb-3 rounded-0" role="alert">
                    {{ session('status') }}
                </div>
            @endif
            <div class="login-form">
                <h2>Bienvenido al</h2>
                <p>Administrador del Portal de Información Municipal y Regional del Estado de Puebla</p>
                <form method="POST" action="{{ route('login') }}" class="contenedor-formulario" novalidate>
                    @csrf
                    <div class="mb-3 w-100">
                        <input class="{{ $errors->has('email') ? 'is-invalid' : '' }} w-100" type="email"
                            name="email" :value="old('email')" placeholder="Correo" required autocomplete="on">
                        @error('email')
                            <small class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </small>
                        @enderror
                    </div>
                    <div class="mb-3 w-100">
                        <input class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }} " type="password"
                            name="password" required autocomplete="current-password" placeholder="Contraseña" autocomplete="on">
                        @error('password')
                            <small class="invalid-feedback">
                                <strong>{{ $message }}</strong>
                            </small>
                        @enderror
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-end align-items-center">
                            <button>
                                {{ __('Entrar') }}
                            </button>
                        </div>
                    </div>
                </form>
                <div class="footer row">
                    <div class="col-md-12 d-flex justify-content-center align-items-center">
                        <img class="w-100" src="{{ asset('img/LOGOS_negativo.png') }}" alt="Gobierno de Puebla">
                    </div>
                </div>
            </div>
            <div class="image-container">
                <img src="{{ asset('img/fondo.jpg') }}"
                    alt="Imagen de una artesanía en color azul con detalles más coloridos."
                    tabindex="Imagen de fondo de la pantalla de Login">
            </div>
        </div>
    </section>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    <script>
        const password = document.querySelector('input[name="password"]');

        if (password) {
            const field = password.closest('.mb-3');
            const toggle = document.createElement('button');
            const eyeIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" focusable="false"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>`;
            const eyeOffIcon = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" focusable="false"><path d="m3 3 18 18"/><path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/><path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 8 10 8a18.5 18.5 0 0 1-3 4.2M6.6 6.6C3.7 8.5 2 12 2 12s3.5 7 10 7c1.2 0 2.3-.2 3.3-.6"/></svg>`;

            field.classList.add('password-field');
            password.id = 'password';
            toggle.type = 'button';
            toggle.className = 'password-toggle';
            toggle.setAttribute('aria-controls', 'password');

            const setPasswordVisibility = (isVisible) => {
                password.type = isVisible ? 'text' : 'password';
                toggle.innerHTML = isVisible ? eyeOffIcon : eyeIcon;
                toggle.setAttribute('aria-label', isVisible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggle.setAttribute('title', isVisible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggle.setAttribute('aria-pressed', String(isVisible));
            };

            setPasswordVisibility(false);

            toggle.addEventListener('click', function() {
                setPasswordVisibility(password.type === 'password');
            });

            password.insertAdjacentElement('afterend', toggle);
        }
    </script>
</x-guest-layout>

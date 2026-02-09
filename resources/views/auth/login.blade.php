<x-guest-layout>
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
</x-guest-layout>

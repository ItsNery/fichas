<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Verificación de seguridad</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        .caja {
            background: #fff;
            border-top: 5px solid #861e34;
            border-radius: 8px;
            padding: 2.5rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .08);
        }

        .caja h1 {
            font-size: 1.3rem;
            color: #246257;
            margin-top: 0;
        }

        .caja p {
            color: #555;
            font-size: .95rem;
        }

        .error {
            color: #861e34;
            font-size: .9rem;
            margin-top: .75rem;
        }

        button {
            margin-top: 1rem;
            background: #861e34;
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: .7rem 1.6rem;
            font-size: 1rem;
            cursor: pointer;
        }

        button:disabled {
            opacity: .5;
            cursor: wait;
        }
    </style>
</head>

<body>
    <div class="caja">
        <h1>Verificación de seguridad</h1>
        <p>Completa esta verificación para continuar. Nos ayuda a proteger el portal contra
            consultas automatizadas.</p>

        <form method="POST" action="{{ route('turnstile.verify') }}" id="form-challenge">
            @csrf
            <input type="hidden" name="redirect" value="{{ $redirect }}">
            <div class="cf-turnstile" data-sitekey="{{ $siteKey }}" data-callback="liberarBoton"
                data-error-callback="falloCaptcha" data-theme="light"></div>
            @error('cf-turnstile-response')
                <p class="error" id="error-token">{{ $message }}</p>
            @enderror
            <p class="error" id="error-captcha" hidden></p>
            <button type="submit" id="btn-enviar" disabled>Continuar</button>
        </form>
    </div>

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    <script>
        function liberarBoton() {
            document.getElementById('btn-enviar').disabled = false;
            document.getElementById('error-captcha').hidden = true;
        }

        function falloCaptcha() {
            var e = document.getElementById('error-captcha');
            e.textContent = 'El captcha falló, recarga la página e intenta de nuevo.';
            e.hidden = false;
        }
    </script>
</body>

</html>

{{-- @extends('publico.index') --}}
<footer class="mt-3">
    <div class="container-fluid bg-dark text-white-50 py-5 px-sm-3 px-lg-5 footer-1">
        <div class="row pt-5">
            <div class="col-lg-3 col-md-6 mb-5">
                <a href="{{ url('/') }}" class="navbar-brand" rel="noopener">
                    <h2>
                        PIMREP
                    </h2>
                </a>
                <p>
                    Portal de Información Municipal y Regional del Estado de Puebla
                </p>
                <h6 class="text-white text-uppercase mt-4 mb-3 h6_planeader">
                    Síguenos
                </h6>
                <div class="d-flex justify-content-start">
                    <a title="X/Twitter de la Secretaría de Planeación y Finanzas"
                        class="btn btn-outline-primary btn-square mr-2" href="https://x.com/SPFAGobPue"
                        rel="noopener" target="_blank">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <a title="Facebook de la Secretaría de Planeación y Finanzas"
                        class="btn btn-outline-primary btn-square mr-2" href="https://www.facebook.com/finanzasgobpue"
                        rel="noopener" target="_blank">
                        <i class="fa-brands fa-facebook"></i>
                    </a>
                </div>
                <div id="evaluationDiv" class="evaluation-container">
                    <h5>¡Ayúdanos a mejorar!</h5>
                    <p>¿Te ha sido útil la información de esta página?</p>
                    <div class="emoji-container">
                        <div class="emoji-wrapper" data-score="3" title="¡Muy útil!">
                            <span class="emoji">😊</span>
                            <span class="emoji-label">Útil</span>
                        </div>
                        <div class="emoji-wrapper" data-score="2" title="Más o menos">
                            <span class="emoji">😐</span>
                            <span class="emoji-label">Regular</span>
                        </div>
                        <div class="emoji-wrapper" data-score="1" title="No me sirvió">
                            <span class="emoji">😞</span>
                            <span class="emoji-label">Poco útil</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-5">
                <h5 class="text-white text-uppercase mb-4">
                    Mapa de sitio
                </h5>
                <div class="d-flex flex-column justify-content-start">
                    <a class="text-white-50 mb-2" href="{{ url('/') }}">
                        <i class="fa fa-home mr-2"></i>
                        Inicio
                    </a>
                    <a class="text-white-50 mb-2" href="{{ url('/banco-indicadores') }}">
                        <i class="fa-solid fa-chart-bar mr-2"></i>
                        Banco de Indicadores
                    </a>
                    <a class="text-white-50 mb-2" href="{{ url('/datos-abiertos') }}">
                        <i class="fa-solid fa-chart-pie mr-2"></i>
                        Datos abiertos
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-5">
                <h5 class="text-white text-uppercase mb-4">
                    Sitios de interés
                </h5>
                <div class="d-flex flex-column justify-content-start">
                    <a target="_blank" class="text-white-50 mb-2" href="https://www.gob.mx/" rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        Gobierno
                        de los Estados Unidos Mexicanos
                    </a>
                    <a target="_blank" class="text-white-50 mb-2" href="https://www.puebla.gob.mx/" rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        Gobierno
                        del Estado de
                        Puebla
                    </a>
                    <a target="_blank" class="text-white-50 mb-2" href="https://spf.puebla.gob.mx/" rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        Secretaría
                        de Planeación, Finanzas y Administración
                    </a>
                    <a target="_blank" class="text-white-50 mb-2" href="https://agenda2030.puebla.gob.mx/"
                        rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        Agenda
                        2030
                    </a>
                    <a target="_blank" class="text-white-50 mb-2" href="https://sei.puebla.gob.mx/" rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        Sistema Estatal de Información
                    </a>
                    <a target="_blank" class="text-white-50 mb-2" href="https://ceigep.puebla.gob.mx/" rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        CEIGEP
                    </a>
                    <a target="_blank" class="text-white-50 mb-2" href="https://evaluacion.puebla.gob.mx/"
                        rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        Evaluación
                    </a>
                    <a target="_blank" class="text-white-50 mb-2" href="https://sped.puebla.gob.mx/" rel="noopener">
                        <i class="fa fa-angle-right mr-2"></i>
                        SPED
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-5">
                <h5 class="text-white text-uppercase mb-4">
                    Contáctanos
                </h5>
                <p>
                    <i class="fa fa-map-marker-alt mr-2"></i>
                    Avenida 11 Oriente 2224 Tercer Piso,
                    72501 Puebla, Puebla
                </p>
                <p>
                    <i class="fa fa-phone-alt mr-2"></i>
                    <a class="no_s text-inherit" href="tel:2222297000">
                        222 229
                        7000 Ext. 5012
                    </a>
                </p>
                <p>
                    <i class="fa fa-envelope mr-2"></i>
                    <a class="no_s text-inherit" href="mailto:planeacion@puebla.gob.mx">
                        planeacion@puebla.gob.mx
                    </a>
                </p>
                <h6 class="text-white text-uppercase mt-4 mb-3 h6_planeader sm-d-none">
                    Ubicación
                </h6>
                {{-- <div class="w-100 sm-d-none">
                    <iframe id="mapIframe" title="Iframe de Mapas" class="iframe-footer"
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3771.6779345685!2d-98.18924812493688!3d19.033908382162036!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85cfc7113bf027f9%3A0x5019db208e053e0!2sSecretaria%20de%20Finanzas%20de%20Gobierno%20del%20Puebla!5e0!3m2!1sen!2smx!4v1700847803603!5m2!1sen!2smx"
                        allowfullscreen="">
                    </iframe>
                </div> --}}
            </div>
        </div>
    </div>
    <div class="container-fluid bg-dark text-white border-top py-4 px-sm-3 px-md-5 footer-2">
        <div class="row">
            <div class="col-lg-6 text-center text-md-left mb-3 mb-md-0">
                <p class="m-0 text-white-50">
                    <a href="https://spf.puebla.gob.mx" target="_blank" rel="noopener">Secretaría de Planeación,
                        Finanzas y Administración</a>
                    <a href="https://creativecommons.org/licenses/by/4.0/deed.es" rel="noopener" target="_blank">
                        <img src="{{ asset('img/cc.xlarge.png') }}" class="w-20px" alt="Logo de Creative Commons"
                            title="Logo de Creative Commons">
                        <img src="{{ asset('img/by.xlarge.png') }}" class="w-20px" alt="Logo de Creative Commons"
                            title="Logo de Creative Commons"></a>
                    Se autoriza la reproducción parcial o total del contenido, siempre que se cite y referencie la
                    fuente.
                </p>
            </div>
            <div class="col-lg-6 text-center text-md-right">
                <p class="m-0 text-white-50">
                    Diseñado por la
                    <a target="_blank" href="https://planeacion.puebla.gob.mx" rel="noopener">
                        Subsecretaría de
                        Planeación
                    </a>
                    .
                </p>
                <p class="m-0 text-white-50">
                    <a target="_blank" href="{{ url('/login') }}" rel="noopener">Iniciar sesión</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<script>
    // Esperamos a que todo el contenido de la página se haya cargado.
    document.addEventListener('DOMContentLoaded', function() {



        // 1. Obtenemos las referencias a los elementos del DOM.
        const evaluationDiv = document.getElementById('evaluationDiv');
        const emojiWrappers = document.querySelectorAll('.emoji-container .emoji-wrapper');



        // Si el cuadro de evaluación o los emojis no existen, no hacemos nada más.
        if (!evaluationDiv || emojiWrappers.length === 0) {
            console.error(
                "No se encontraron los elementos necesarios para la evaluación. El script se detendrá.");
            return;
        }

        // --- LA REGLA DE "VOTAR UNA SOLA VEZ" ---
        // 2. Revisamos en localStorage si el usuario ya ha votado antes.
        if (localStorage.getItem('siteEvaluationVoted') === 'true') {
            // Si ya votó, simplemente ocultamos el cuadro y detenemos el script.
            evaluationDiv.style.display = 'none';
            return;
        }

        // --- LA FUNCIÓN QUE ENVÍA EL VOTO AL SERVIDOR ---
        function submitEvaluation(score) {
            // Obtenemos el token CSRF para la seguridad de Laravel.
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Mostramos un mensaje de "cargando" para el usuario.
            evaluationDiv.innerHTML = '<p class="text-black">Procesando tu voto...</p>';

            // Usamos la API fetch para enviar los datos a tu backend.
            fetch(`${window.APP_URL}/api/site-evaluation`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        score: score
                    }) // Enviamos la puntuación como JSON.
                })
                .then(response => {
                    if (!response.ok) { // Si hay un error en el servidor.
                        throw new Error('La respuesta del servidor no fue exitosa.');
                    }
                    return response.json();
                })
                .then(data => {
                    // ¡ÉXITO! El servidor guardó el voto.
                    // 1. Guardamos la bandera en localStorage para que no pueda volver a votar.
                    localStorage.setItem('siteEvaluationVoted', 'true');

                    // 2. Mostramos el mensaje de agradecimiento y añadimos la clase CSS.
                    evaluationDiv.innerHTML = '<p>¡Gracias por tu evaluación!</p>';
                    evaluationDiv.classList.add('thank-you');

                    // 3. Hacemos que el cuadro desaparezca después de 1.5 segundos.
                    setTimeout(() => {
                        evaluationDiv.style.transition =
                            'opacity 0.5s ease-out, transform 0.5s ease-out';
                        evaluationDiv.style.opacity = '0';
                        evaluationDiv.style.transform = 'translateY(-20px)';
                        setTimeout(() => evaluationDiv.style.display = 'none', 500);
                    }, 1500);
                })
                .catch(error => {
                    // Si hubo un error, lo mostramos en la consola y al usuario.
                    console.error('Error al enviar la evaluación:', error);
                    evaluationDiv.innerHTML =
                        '<p class="text-danger">Hubo un error. Por favor, intenta más tarde.</p>';
                });
        }

        // --- CONECTAMOS LOS CLICS A LA FUNCIÓN ---
        // 3. Recorremos cada emoji-wrapper y le añadimos el evento de clic.
        emojiWrappers.forEach(wrapper => {
            wrapper.addEventListener('click', function() {
                // Obtenemos la puntuación del atributo 'data-score'.
                const score = this.dataset.score;
                // Llamamos a la función para enviar el voto.
                submitEvaluation(score);
            });
        });

    });
</script>

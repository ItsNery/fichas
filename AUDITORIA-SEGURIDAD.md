# Auditoría de seguridad — Fichas Municipales

**Fecha:** 23 de octubre de 2026  
**Revisión:** `0ea4b51` (`main...origin/main`, árbol limpio al inicio)  
**Alcance:** código, configuración y manifiestos presentes en el repositorio local. No se hicieron peticiones al dominio público, a producción ni a servidores auditados. Sólo se consultó documentación oficial de soporte y se intentó el registro de paquetes requerido por `npm audit`.

## 1. Resumen ejecutivo

El proyecto tiene una base sólida: Laravel 12 vigente para parches de seguridad, autorización granular con Spatie, CSRF, flujos de sesión correctos y límites para varias rutas costosas. Encontré tres prioridades reales: XSS almacenado en narrativas que se muestran al público, PDF regionales costosos sin límite de tasa y Chrome ejecutado sin sandbox. También hay una contraseña predecible en un seeder no invocado por el despliegue y faltan límites de tamaño en importaciones. No se detectaron vulnerabilidades de dependencias porque los dos audit automáticos no pudieron completarse en este equipo; no debe interpretarse como que no existan.

## 2. Inventario y metodología

| Elemento | Evidencia y resultado |
| --- | --- |
| PHP requerido | `^8.2` en `composer.json:11`; el CLI local reportó PHP 8.2.30. La rama 8.2 aún recibía correcciones de seguridad hasta el 31-dic-2026 al momento de la revisión, por lo que conviene planear actualización antes de esa fecha. La versión de PHP de producción no fue verificable. |
| Laravel | `laravel/framework` `v12.69.3` en `composer.lock:1897-1898`; `php artisan about` confirmó 12.69.3 localmente. Laravel 12 mantiene correcciones de seguridad hasta el 24-feb-2027 según el [calendario oficial de Laravel](https://laravel.com/docs/12.x/releases#support-policy). |
| Paquetes de aplicación | Sanctum 4.3.1 (`composer.lock:2252-2253`), Dompdf 3.1.1 (`:10-11`), Laravel Excel 3.1.67 (`:3001-3002`), Browsershot 5.4.0 (`:4910-4911`), Activitylog 4.12.3 (`:4978-4979`), Spatie Permission 6.25.0 (`:5130-5131`). Breeze 2.3.8 y Boost 2.10.1 están en `require-dev` (`composer.json:26-34`). No hay Livewire ni Volt en el manifiesto ni en el código. |
| Frontend | Dependencias de ejecución: `proj4` y `shapefile`; las demás están en `devDependencies` (`package.json:9-23`). |
| Despliegue | Único flujo incluido: `.github/workflows/deploy.yml`. Se ejecuta en runner propio, usa `npm ci`, genera assets, instala Composer con `--no-dev`, ejecuta `migrate --force` y recompila cachés (`:141-164`). No hay Dockerfile, Compose ni configuración Nginx/Apache versionada, salvo `public/.htaccess`. |
| Auditorías de dependencias | `composer audit` no pudo ejecutarse: Composer y Docker no están disponibles en este equipo. `npm audit --omit=dev` se intentó y falló contra `registry.npmjs.org` con `ECONNRESET`; no devolvió advisories. Por tanto, **no se afirma que las dependencias estén libres de CVE**. |
| Secretos históricos | El comando requerido de Gitleaks no pudo ejecutarse porque Docker no está instalado. La revisión del árbol actual no sustituye ese análisis de todo el historial. |
| Pruebas | Se ejecutó `php artisan test --do-not-cache-result`; PHPUnit terminó antes de cargar pruebas con `Cannot open bootstrap script ...vendor/autoload.php`, aunque el archivo existe en el sistema de archivos. No hay resultado de suite ni cobertura ejecutable. |

## 3. Tabla resumen de hallazgos

| # | Hallazgo | Gravedad | Evidencia | Esfuerzo estimado |
| --- | --- | --- | --- | --- |
| H-01 | XSS almacenado en narrativas configurables renderizadas sin escape | Alto | `app/Http/Controllers/Admin/ConfiguracionFichaController.php:86,146`; `resources/views/municipios/perfil.blade.php:438` | 0.5–1 día |
| H-02 | PDF regional público, costoso y sin rate limit | Alto | `routes/web.php:87,97-98`; `app/Services/ExportV3Service.php:77-96` | 1–2 horas |
| H-03 | Chromium de exportación se ejecuta con `--no-sandbox` | Medio | `app/Services/ExportV3Service.php:38-43,89-96,113-119` | 0.5–2 días |
| H-04 | Contraseña predecible comprometida en un seeder de superadministrador | Medio | `database/seeders/SuperAdminSeeder.php:17-26` | 1–2 horas |
| H-05 | Importaciones Excel/CSV sin límite de tamaño ni contención de recursos | Medio | `app/Http/Controllers/Admin/ImportController.php:60,292-295,318-320,383-385` | 0.5 día |
| H-06 | No hay cabeceras de seguridad definidas por la aplicación ni por la configuración web incluida | Informativo | búsqueda sin coincidencias; `public/.htaccess:1-20` | 0.5–1 día |
| H-07 | `.gitignore` no cubre genéricamente respaldos ni volcados comunes | Bajo | `.gitignore:1-30`; `git check-ignore` no ignoró muestras `.sql`, `.dump`, `.zip` | 15 min |
| H-08 | No se invalida automáticamente una sesión existente al cambiar la contraseña | Bajo | `app/Http/Kernel.php:37` | 1–2 horas |

## 4. Detalle de hallazgos

### H-01 — XSS almacenado en narrativas configurables renderizadas sin escape

**Descripción.** Las personas con `configuracion-fichas.crear` o `configuracion-fichas.editar` pueden guardar `plantilla_narrativa` como cualquier cadena. Esa cadena se procesa sin saneamiento y se entrega con `{!! !!}` en páginas públicas. También hay serialización JavaScript sin un encoder seguro para el contexto `<script>`.

**Evidencia.**

```php
// app/Http/Controllers/Admin/ConfiguracionFichaController.php:80-94
'plantilla_narrativa' => 'nullable|string',

// app/Services/FichaNarratorService.php:45-67
return str_replace(array_keys($reemplazos), array_values($reemplazos), $plantilla);

// resources/views/municipios/perfil.blade.php:436-438
<p class="perfil-tarjeta__narrativa-texto">{!! $item['narrativa'] !!}</p>
```

Los mismos patrones aparecen en `resources/views/regiones/perfil.blade.php:362` y `resources/views/municipios/resumen_v3.blade.php:214`; `resumen_v3.blade.php:318` usa `{!! json_encode(...) !!}` dentro de JavaScript. No se encontró un sanitizador HTML entre la validación y el renderizado.

**Impacto realista.** Una cuenta interna con permiso de editar configuraciones —o una importación/proceso administrativo que inyecte contenido no confiable— puede almacenar una etiqueta `<script>` o un atributo de evento. Al visitar el perfil, cualquier persona ejecutaría el script bajo `sei.puebla.gob.mx`: alteración del contenido, redirecciones, acciones con sesión de personal autenticado y robo de datos accesibles desde esa sesión. Es un XSS almacenado, no depende de que la víctima abra una URL especialmente manipulada.

**Corrección recomendada.** Si no se necesita formato HTML, escapar en Blade y conservar la narrativa como texto:

```blade
<p class="perfil-tarjeta__narrativa-texto">{{ $item['narrativa'] }}</p>
```

Si se necesita un subconjunto de HTML, convertir primero la plantilla en texto escapado y permitir únicamente etiquetas seguras mediante un sanitizador HTML mantenido y configurado (por ejemplo, sólo `strong`, `em` y `br`, sin atributos). No basar la solución en `strip_tags` con etiquetas permitidas. Para JavaScript, usar el encoder de Laravel:

```blade
const kpi = {{ Illuminate\Support\Js::from($kpis[0] ?? null) }};
```

Añadir pruebas que intenten guardar texto con `<script>` y confirmen que se muestra literal, no ejecutable.

### H-02 — PDF regional público, costoso y sin rate limit

**Descripción.** Los PDF municipales tienen `throttle:ficha-pdf`, pero los PDF estatal, macrorregional y microrregional no. Todos son públicos y el motor regional compone datos y abre Chromium, con un tiempo de espera de hasta 600 segundos.

**Evidencia.**

```php
// routes/web.php:65-71 — municipal: sí está limitado
Route::get('/{municipio:slug}/perfil.pdf', ...)->middleware('throttle:ficha-pdf');

// routes/web.php:87,97-98 — regionales: no hay middleware throttle
Route::get('ficha/estatal/pdf', [RegionController::class, 'exportarEstatalPDF']);
Route::get('ficha/macrorregion/{macrorregion:slug}/pdf', ...);
Route::get('ficha/microrregion/{microrregion:slug}/pdf', ...);

// app/Services/ExportV3Service.php:77-96
->timeout(600)
->protocolTimeout(600)
->waitForFunction('window.__pdfReady', null, 110000)
->pdf();
```

**Impacto realista.** Cualquier cliente puede solicitar esas rutas repetidamente y forzar procesos de navegador, consultas y generación de PDF simultáneas. Un número pequeño de solicitudes en paralelo puede agotar workers de PHP, CPU o memoria y degradar el sitio para todas las personas. La limitación global existente de 30 PDF/hora no aplica a estas tres rutas.

**Corrección recomendada.** Aplicar el limitador de PDF existente a todas las exportaciones regionales y revisar capacidad/concurrencia del worker:

```php
Route::get('ficha/estatal/pdf', [RegionController::class, 'exportarEstatalPDF'])
    ->middleware('throttle:ficha-pdf');

Route::get('ficha/macrorregion/{macrorregion:slug}/pdf', [RegionController::class, 'exportarMacrorregionPDF'])
    ->middleware('throttle:ficha-pdf');
```

Como mejora posterior, poner la generación en una cola con una concurrencia máxima y entregar un resultado cacheado por región/versión de datos. Medir primero los tiempos reales para decidir límites que no penalicen uso legítimo.

### H-03 — Chromium de exportación se ejecuta sin sandbox

**Descripción.** Browsershot pasa a Chrome `--no-sandbox` y `--disable-setuid-sandbox` en los tres flujos de PDF. Es común como solución temporal en hosts incompatibles, pero elimina una capa relevante frente a un fallo del navegador o HTML/JavaScript inesperado.

**Evidencia.**

```php
// app/Services/ExportV3Service.php:38-43 (mismo patrón en :89-96 y :113-119)
->setOption('args', [
    '--no-sandbox',
    '--disable-setuid-sandbox',
    '--disable-dev-shm-usage',
])
```

**Impacto realista.** El PDF procesa HTML generado desde datos y configuraciones administrativas. Si se combinara un HTML malicioso (véase H-01) con una vulnerabilidad del navegador, la ausencia de sandbox incrementa el impacto potencial sobre el proceso/host de exportación. No se afirmó una explotación de Chrome ni acceso al servidor; el riesgo deriva de haber eliminado una barrera de aislamiento.

**Corrección recomendada.** Ejecutar Chrome con sandbox habilitado bajo un usuario no privilegiado y con el soporte del kernel/contenedor necesario. Si la plataforma obliga a desactivarlo, aislar el exportador en un worker o contenedor efímero sin secretos, con usuario no root, red mínima, límites de CPU/memoria/PIDs y sistema de archivos de sólo lectura. Mantener Browsershot/Chrome actualizados.

### H-04 — Contraseña predecible comprometida en un seeder de superadministrador

**Descripción.** Hay una contraseña fija para crear una cuenta con rol `super_admin`. `DatabaseSeeder` no invoca este seeder (`database/seeders/DatabaseSeeder.php:14-18`) y el flujo de despliegue no ejecuta `db:seed`, por lo que no se pudo confirmar que la cuenta exista en producción. Aun así, la credencial está en el historial de trabajo actual y cualquier ejecución manual de esa clase crea o reutiliza una cuenta privilegiada conocida.

**Evidencia.**

```php
// database/seeders/SuperAdminSeeder.php:17-26
User::firstOrCreate([...], [
    'password' => Hash::make('de******25'),
]);
$user->assignRole('super_admin');
```

La contraseña se muestra enmascarada deliberadamente; no se reproduce en este informe.

**Impacto realista.** Si alguien ejecuta `php artisan db:seed --class=SuperAdminSeeder` contra una base con la cuenta ausente, se habilita una cuenta superadministradora con credenciales conocidas por quien tenga acceso al repositorio. Si la cuenta ya existía desde un despliegue/operación previa, el riesgo debe verificarse y la contraseña debe rotarse. No hay evidencia de una ruta pública que ejecute ese seeder.

**Corrección recomendada.** Eliminar el valor fijo, retirar el seeder de producción o exigir una contraseña de aprovisionamiento entregada exclusivamente por el gestor de secretos. Por ejemplo:

```php
$password = config('bootstrap.initial_superadmin_password');
throw_if(blank($password), LogicException::class, 'Falta contraseña inicial segura.');

$user = User::firstOrCreate($identity, [
    'name' => 'Super Administrador',
    'password' => Hash::make($password),
]);
```

Configurar ese valor sólo en el entorno de provisión, rotar la contraseña de cualquier cuenta creada antes y borrar el secreto de las revisiones históricas mediante un procedimiento controlado si el repositorio estuvo expuesto.

### H-05 — Importaciones Excel/CSV sin límite de tamaño ni contención de recursos

**Descripción.** Las importaciones requieren tipo MIME, y están correctamente protegidas por permisos, pero no establecen `max`. Laravel Excel carga el contenido para validarlo/procesarlo.

**Evidencia.**

```php
// app/Http/Controllers/Admin/ImportController.php:292-295
$request->validate([
    'indicador_id' => 'required|integer|exists:indicadors,id',
    'archivo' => 'required|file|mimes:xlsx,xls,csv',
]);

// app/Http/Controllers/Admin/ImportController.php:28-37
$this->middleware('permission:datos.importar|catalogos.importar|instrumentos.importar')
```

El mismo patrón sin `max` aparece en `:60`, `:78`, `:96`, `:114`, `:318`, `:383`, `:465` y `:482`. La conservación sí es privada: `Storage::disk('local')->putFileAs(...)` en `app/Services/LoteDatosService.php:34-39`, fuera del disco `public` (`config/filesystems.php:33-42`).

**Impacto realista.** Un usuario autorizado —o una cuenta administrativa comprometida— puede enviar un XLSX/CSV muy grande o diseñado para consumo elevado de memoria/CPU. Esto puede bloquear workers y degradar la administración; con suficiente carga concurrente puede afectar al sitio. No se afirma ejecución de archivos: el hallazgo es agotamiento de recursos.

**Corrección recomendada.** Establecer un límite coherente en cada acción, limitar también `upload_max_filesize`, `post_max_size` y el proxy web, y procesar por chunks/colas cuando el volumen legítimo lo requiera:

```php
'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // KiB; ejemplo: 10 MiB
```

Definir el límite por tipo de archivo, registrar rechazos sin incluir contenido del archivo y añadir una prueba de validación para un archivo sobre el máximo.

### H-06 — Cabeceras de seguridad no definidas en el material versionado

**Descripción.** No se encontró middleware ni configuración incluida que emita CSP, `X-Frame-Options`/`frame-ancestors`, `X-Content-Type-Options`, HSTS, `Referrer-Policy` o `Permissions-Policy`. El único archivo Apache incluido sólo reescribe solicitudes.

**Evidencia.**

```apache
# public/.htaccess:1-20
Options -MultiViews -Indexes
RewriteEngine On
... # no Header always set ni cabeceras equivalentes
```

La búsqueda de los nombres de cabecera en `app/`, `config/`, `routes/`, `public/` y `.github/` no devolvió coincidencias. No hay configuración de Nginx/Apache/Caddy versionada, por lo que no se puede afirmar que el servidor de producción no las añada.

**Impacto realista.** Sin una CSP, un XSS como H-01 tiene menos barreras de contención. Sin protección de framing puede haber clickjacking si el servidor no la establece. HSTS y `nosniff` protegen contra degradación de transporte y tipos MIME ambiguos. El impacto concreto depende de la configuración externa no disponible.

**Corrección recomendada.** Preferir configurarlas en Nginx/Apache/reverse proxy central; documentarlas y probarlas desde un entorno controlado. Como segunda línea, añadir middleware de respuesta. Ejemplo orientativo (ajustar los orígenes de Vite, fuentes, mapas y analítica antes de activar CSP):

```php
$response->headers->set('X-Content-Type-Options', 'nosniff');
$response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
$response->headers->set('X-Frame-Options', 'SAMEORIGIN');
$response->headers->set('Content-Security-Policy', "default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
```

Aplicar HSTS sólo cuando HTTPS esté garantizado para todos los subdominios que se incluyan.

### H-07 — `.gitignore` no cubre genéricamente respaldos ni volcados comunes

**Descripción.** Se ignora `.env`, dependencias y partes de `storage`, pero sólo se excluye un nombre concreto de SQL y RAR. Muestras de nombres comunes de respaldo no resultan ignoradas.

**Evidencia.**

```gitignore
# .gitignore:1-8,23-24
/node_modules
/storage/*.key
/vendor
.env
.env.backup
fichas_municipales.sql
fichas_municipales.rar
```

`git check-ignore -v audit-sample.sql audit-sample.dump audit-sample.zip` no produjo regla coincidente. `git ls-files` sólo devolvió `.env.example` entre `.env`, `.sql`, `.dump`, `.zip`, `.pem` y `.key` en la rama actual; eso es positivo pero no evita una inclusión futura.

**Impacto realista.** Un respaldo creado por una persona desarrolladora puede contener datos personales, hashes de contraseña o configuración y ser añadido accidentalmente al repositorio.

**Corrección recomendada.** Añadir patrones genéricos y excepciones explícitas sólo si fueran imprescindibles:

```gitignore
*.sql
*.dump
*.sql.gz
*.zip
*.rar
*.bak
*.pem
*.key
```

Esto debe acompañarse con Gitleaks/secret scanning obligatorio en CI, no sólo con `.gitignore`.

### H-08 — No se invalida automáticamente una sesión existente al cambiar la contraseña

**Descripción.** El grupo `web` deja comentado `AuthenticateSession`. Inicio y cierre de sesión sí regeneran/invalida correctamente la sesión actual, pero no hay el control adicional que compara el hash de contraseña de sesiones ya existentes.

**Evidencia.**

```php
// app/Http/Kernel.php:33-40
\Illuminate\Session\Middleware\StartSession::class,
// \Illuminate\Session\Middleware\AuthenticateSession::class,
\App\Http\Middleware\VerifyCsrfToken::class,

// app/Http/Controllers/Auth/AuthenticatedSessionController.php:46-50
Auth::guard('web')->logout();
$request->session()->invalidate();
$request->session()->regenerateToken();
```

**Impacto realista.** Si una sesión es robada y el propietario cambia su contraseña desde otra sesión, la sesión robada puede continuar hasta expirar. El periodo efectivo depende de `SESSION_LIFETIME` y del driver de producción, que no se verificaron.

**Corrección recomendada.** Valorar activar el middleware y comprobarlo con pruebas de cambio/restablecimiento de contraseña:

```php
\Illuminate\Session\Middleware\AuthenticateSession::class,
```

Documentar el comportamiento esperado para administradores y confirmar que no rompe el flujo de sesiones del despliegue actual.

### Revisión adicional de salida, Blade y JavaScript

| Patrón revisado | Origen del contenido | Resultado |
| --- | --- | --- |
| `resources/views/components/input.blade.php:3` | Bolsa de atributos de componente Blade | Uso esperado de `AttributeBag`; no es cuerpo de contenido ingresado por visitantes. |
| `resources/views/vendor/pagination/*.blade.php` | Cadenas de traducción de Laravel | No provienen de visitantes. |
| `municipios/perfil.blade.php:438`, `regiones/perfil.blade.php:362`, `municipios/resumen_v3.blade.php:214` | `plantilla_narrativa` editable por personal con permiso | Afectado por H-01. |
| `municipios/resumen_test.blade.php:295`, `municipios/resumen_v3.blade.php:318` | Datos de KPI/configuración administrativa | `json_encode` sin encoder seguro para `<script>`; incluido en H-01. |
| `municipios/resumen_v3_pdf.blade.php:244` | Historial preparado en servidor | No se pudo verificar si siempre es JSON seguro; sustituir por `Js::from` o serializar/validar antes de renderizar. |
| `innerHTML` | Hay muchas asignaciones estáticas de interfaz. Las que interpolan datos del servidor incluyen notas de gráficas (`public/js/graficas-con-regionalizacion.js:318`) y respuestas administrativas. | No se encontraron `v-html` ni `x-html`. El flujo de narrativas es el caso público comprobado; adoptar `textContent`/creación de nodos para cualquier texto variable y dejar `innerHTML` sólo para HTML saneado. |

## 5. Rutas, autenticación y autorización

Se ejecutó `php artisan route:list --json`: **180 rutas en el entorno local**, incluidas rutas de desarrollo de Boost. La tabla agrupa rutas con el mismo control para mantenerla legible; los nombres/patrones provienen de `routes/web.php`, `routes/api.php`, `routes/auth.php` y del listado de Artisan.

| Ruta o grupo | Método | Middleware efectivo | ¿Auth? | ¿Autorización / observación? |
| --- | --- | --- | --- | --- |
| `/`, municipios, datos abiertos, perfiles y exportaciones públicas | GET | `web`; secciones municipales y PDF municipales añaden sus `throttle:*` | No | Datos destinados al público. PDF municipales: `ficha-pdf`; PDF regionales: **sin throttle** (H-02). |
| `banco-indicadores/api/data`, `banco-indicadores/exportar`, `api/datos-historicos` | POST | `web` (incluye CSRF) | No | Consultas/exportación; no se encontró escritura persistente en estas acciones. Sin permiso porque el recurso es público. |
| `/api/v1/*`, `/api/site-evaluation` | GET/POST | `api`, `throttle:api` (60/min por usuario o IP) | No, salvo `/api/user` | API v1 pública de consulta. `site-evaluation` escribe retroalimentación pública, valida `score`, comentario y metadatos en `SiteEvaluationController.php:21-58`. |
| `/api/user` | GET | `api`, `auth:sanctum` | Sí | Única ruta Sanctum encontrada. No se encontraron llamadas a `createToken()` en el código. |
| `login`, `forgot-password`, `reset-password` | GET/POST | `web`, `guest` | No | Login usa `LoginRequest`: 5 intentos por clave correo+IP (`LoginRequest.php:66-92`). Restablecimiento tiene expiración y throttle de 60 min (`config/auth.php:89-95`). |
| `verify-email/{id}/{hash}` y notificación | GET/POST | `web`, `auth`, `signed`/`throttle:6,1` | Sí | Firma para verificación y límite de reenvío presentes (`routes/auth.php:37-47`). |
| `logout`, `confirm-password`, `/dashboard` | POST/GET | `web`, `auth` | Sí | El logout invalida sesión y regenera CSRF; dashboard elige un destino conforme a permisos (`routes/web.php:129-137`). |
| `admin/catalogos/*` | GET/POST/PUT/PATCH/DELETE | `web`, `auth`, `permission:catalogos.*` | Sí | Spatie separa ver, crear, editar y eliminar; confirmado en `route:list --json`. |
| `admin/datos/*`, territorial y lotes | GET/POST/PUT/PATCH/DELETE | `web`, `auth`, permisos `datos.*`/`datos.importar` | Sí | Operaciones administrativas con permisos por controlador/ruta. |
| `admin/configuracion-fichas/*` | GET/POST/PUT/PATCH/DELETE | `web`, `auth`, `permission:configuracion-fichas.*` | Sí | Bien protegido, pero el contenido permitido desencadena H-01. |
| `admin/formulas/*` | GET/POST/PUT/DELETE | `web`, `auth`; `Gate::authorize` dentro del controlador | Sí | El controlador autoriza cada acción (`FormulaController.php:23,32,45,71,84,98,141,185,212`). |
| `admin/importar/*` | GET/POST | `web`, `auth`, `catalogos.importar` / `datos.importar` / `instrumentos.importar` | Sí | Permisos específicos en `ImportController.php:26-37`; pendiente corregir H-05. |
| `admin/users/*`, roles y permisos | GET/POST/PUT/PATCH/DELETE | `web`, `auth`, `permission:usuarios.*`/roles/permisos | Sí | `UserController` protege asignación de roles y superadministrador (`UserController.php:153-177`). |
| `admin/auditoria`, `admin/trafico`, dashboard y salud de datos | GET/POST | `web`, `auth`, permisos específicos | Sí | `auditoria.ver`, `trafico.ver`, `dashboard.ejecutivo` y permisos asociados, según `route:list --json`. |
| `_boost/browser-logs` | POST | sin middleware en local | No en local | Es de Boost, paquete en `require-dev`; el despliegue instala con `composer install --no-dev` (`deploy.yml:144`). No forma parte del artefacto de producción si el flujo se respeta. |

**Conclusión de rutas mutables.** El listado local contiene 74 rutas con POST/PUT/PATCH/DELETE. Las mutaciones administrativas revisadas exigen `auth` y permiso/Gate; las sin auth son los flujos deliberadamente públicos de autenticación, consulta/exportación, feedback y Boost local. No se encontró una ruta administrativa de mutación sin autenticación/autorización. Esta conclusión no sustituye pruebas de integración, que no pudieron ejecutarse.

**IDOR.** Los recursos administrativos usan bindings y middleware de permisos; para usuarios, `UserController` limita explícitamente la gestión de superadministradores. Las fichas por municipio/región son públicas, por lo que su identificador no protege un recurso privado. No se verificó dinámicamente cada política/model binding por la imposibilidad de ejecutar la suite; no se afirma cobertura exhaustiva de IDOR.

**Sanctum.** `config/sanctum.php:47` deja la expiración de tokens personales en `null`, pero no se encontró emisión de tokens en rutas, controladores ni servicios. Es informativo: si se empiezan a emitir tokens, definir expiración y abilities por token antes de exponer nuevos endpoints.

## 6. Lo que está bien verificado

- `config/app.php:29,42,122` tiene valores por defecto adecuados para producción: `APP_ENV=production`, `APP_DEBUG=false` y `APP_KEY` requerida. El `.env.example` es de desarrollo (`APP_ENV=local`, `APP_DEBUG=true`) y deja las claves vacías; no es evidencia de la configuración real de producción.
- CORS permite `*`, pero `supports_credentials` es `false` (`config/cors.php:18-32`); no se da la combinación insegura de origen comodín con cookies/credenciales.
- La configuración de proxies **sí** se aplica: `config/trustedproxy.php:14` declara los proxies concretos y el middleware base de Laravel consume `config('trustedproxy.proxies')` cuando la propiedad no está fijada (`vendor/laravel/framework/.../TrustProxies.php:67-92`). No se reporta como fallo.
- La sesión usa un driver configurable (por defecto `file`) y 120 minutos (`config/session.php:21,34`); `http_only` es `true` y `same_site` es `lax` (`:184,199`). La marca `secure` se toma de `SESSION_SECURE_COOKIE` (`:171`), por lo que su valor efectivo en producción sigue pendiente de verificación de infraestructura.
- El CSRF está activo para `web` (`app/Http/Kernel.php:33-40`) y `app/Http/Middleware/VerifyCsrfToken.php:14-16` no tiene excepciones.
- Inicio de sesión valida correo/contraseña, limita intentos, regenera sesión tras autenticarse y el logout invalida sesión/regenera token (`LoginRequest.php:31-92`, `AuthenticatedSessionController.php:29-52`).
- Verificación de correo usa URLs firmadas y throttling; recuperación de contraseña expira en 60 minutos (`routes/auth.php:41-46`, `config/auth.php:89-95`).
- Contraseñas de usuarios se guardan con `Hash::make`; no se encontraron usos de `md5`/`sha1` para contraseñas. El único `md5` hallado es una clave de caché de región (`RegionController.php:146`).
- Los modelos de usuario sólo exponen `name`, `email` y `password` como fillable (`app/Models/User.php:21-25`); no se encontró `guarded = []`. Modelos de lotes incluyen IDs/estado, pero el servicio asigna el usuario de carga explícitamente (`LoteDatosService.php:164-202`) y las rutas requieren permisos.
- Los usos relevantes de SQL crudo revisados usan valores normalizados o bindings: `RankingService` reduce la operación a `sum`/`avg` (`RankingService.php:509-512`) y los `havingRaw`/`orderByRaw` encontrados emplean parámetros ligados. No se encontró interpolación directa de `$request` en SQL crudo durante la revisión dirigida.
- Los archivos de lote van al disco `local` y se renombran con UUID, no se guardan con una URL pública directa (`LoteDatosService.php:34-39`; `config/filesystems.php:33-42`). La descarga de plantillas usa tipos controlados por servidor.
- El middleware de telemetría registra huellas HMAC para cookie/sesión/user-agent/referer, no esos valores en claro (`RecordCostlyTraffic.php:39-64`), y la retención predeterminada es de siete días (`config/traffic.php:4`; `PruneTrafficRequestEvents.php:13-19`). Aun así, conserva IP, por lo que la retención y aviso de privacidad deben ser decisión institucional.
- No se encontraron Livewire, Volt, `WithFileUploads`, exclusiones de CSRF, webhooks entrantes ni ejecución directa de `exec`, `shell_exec`, `system`, `passthru`, `proc_open` o `Process` con datos externos en `app/`, `routes/`, `config/` y `database/`.
- El flujo de despliegue excluye `.env`, `storage`, `vendor`, `node_modules` y `public/storage` de rsync (`.github/workflows/deploy.yml:121-127`), instala PHP sin dependencias de desarrollo (`:144`) y construye assets desde lockfile con `npm ci` (`:141-142`).
- El repositorio no versiona un `.env` real en la rama actual: `git ls-files` sólo listó `.env.example`. `storage/app/.gitignore:1-3` evita versionar el contenido de storage salvo la estructura de control.
- Hay 40 clases de prueba identificadas, incluidas `AdminAuthorizationTest`, `AdminFormulaControllerTest`, `DataHealthActionsTest`, `FichaRateLimitingTest` y `TrustProxiesTest`. La cobertura de autorización **no es calculable** sin que PHPUnit corra; estática y parcialmente hay pruebas de permitido/403 en módulos administrativos, pero no se puede afirmar un porcentaje ni cobertura de las 74 rutas mutables.

## 7. Aspectos no verificados y límites

| Tema | Por qué no se verificó | Acción siguiente segura |
| --- | --- | --- |
| Vulnerabilidades Composer | No hay Composer ni Docker local; no se instaló nada por ser auditoría de solo lectura. | Ejecutar `composer audit --locked` en el runner de CI o en contenedor desechable. |
| Vulnerabilidades npm | `npm audit --omit=dev` falló con `ECONNRESET` contra el registro; no devolvió advisories. | Reintentar en CI/red permitida y guardar el JSON como artefacto. |
| Secretos en todo el historial | Docker/Gitleaks no están disponibles. | Ejecutar exactamente `docker run --rm -v "$PWD":/repo zricethezav/gitleaks:latest detect --source=/repo -v --redact` en un entorno autorizado. |
| Variables reales de producción | No se leyó `.env` de producción ni se contactó el servidor, por instrucción. | Comprobar allí, fuera de este ejercicio, `APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY` presente, `SESSION_SECURE_COOKIE=true`, driver de sesión y `TRUSTED_PROXIES`. |
| Cabeceras reales, TLS, WAF, permisos y backups | Nginx/Apache y el host no están versionados; no se hicieron peticiones al dominio. | Revisar configuración del proxy y ejecutar una prueba controlada de cabeceras y permisos de un backup en beta. |
| Pruebas | PHPUnit no abrió el bootstrap y abortó antes de ejecutar casos. | Reparar el entorno de pruebas sin cambiar aplicación y ejecutar `php artisan test --do-not-cache-result`; luego publicar cobertura. |
| IDOR exhaustivo y autorización dinámica | La suite no corrió; sólo hubo análisis de rutas y código. | Añadir matrices de roles/recursos para cada operación mutable y ejecutar pruebas en base efímera. |
| Estado de la cuenta creada por el seeder | No se consultó la base de datos de producción. | Verificar con el equipo autorizado y rotar inmediatamente si existió. |

**Nota sobre TrustHosts.** `app/Http/Kernel.php:17` deja `TrustHosts` deshabilitado. No se calificó como hallazgo porque no hay evidencia de que el servidor web acepte hosts arbitrarios ni de que la aplicación construya URLs desde el Host sin validar. Debe confirmarse que el virtual host/reverse proxy restringe los nombres esperados; si no, habilitar el middleware o la restricción en el proxy.

## 8. Plan de corrección priorizado

### Hoy

1. Corregir H-01: escapar/sanitizar las narrativas y migrar el JSON embebido a `Js::from`; probar XSS almacenado.
2. Aplicar `throttle:ficha-pdf` a los tres PDF regionales (H-02) y observar el comportamiento antes de subir límites.
3. Retirar la contraseña fija del seeder (H-04), revisar si esa cuenta se usó alguna vez y rotarla si corresponde.
4. Reducir la exposición del exportador: quitar `--no-sandbox` cuando el host lo soporte o aislarlo (H-03).

### Esta semana

1. Establecer límites de tamaño, carga en chunks y límites del proxy/PHP para importaciones (H-05).
2. Acordar e implantar cabeceras de seguridad en el reverse proxy; desplegarlas primero en beta con CSP en modo de reporte si hay scripts externos (H-06).
3. Activar secret scanning y `composer audit`/`npm audit` en CI; arreglar el entorno de PHPUnit y ejecutar toda la suite.
4. Añadir patrones de respaldo a `.gitignore` y una revisión de archivos grandes/secretos pre-commit (H-07).

### Después

1. Activar y probar `AuthenticateSession` (H-08); evaluar reducir `SESSION_LIFETIME` para cuentas administrativas.
2. Convertir PDFs costosos a trabajos en cola con caché, límite de concurrencia y métricas.
3. Verificar configuración de producción y de infraestructura: HTTPS/HSTS, `SESSION_SECURE_COOKIE`, VirtualHost/TrustHosts, permisos reales de respaldos y rotación/retención de datos de telemetría.
4. Definir expiración y abilities de Sanctum antes de emitir tokens de acceso personal.


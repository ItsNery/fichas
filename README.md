# Sistema de Fichas Municipales y Banco de Indicadores

Sistema web para la gestión, consulta y difusión de información estadística y geográfica municipal, desarrollado con Laravel 8.

## 📋 Descripción

Esta plataforma permite la administración y visualización de indicadores estratégicos, ofreciendo herramientas para el análisis de datos históricos, generación de fichas municipales y descarga de información en formatos abiertos. El sistema facilita la toma de decisiones mediante el acceso centralizado a censos, conteos y registros administrativos.

## ✨ Características Principales

### Área Pública

- **Banco de Indicadores**: Buscador y consulta de indicadores por municipio y temática.
- **Fichas Municipales**: Generación de resúmenes estadísticos por municipio (`FichaController`).
- **Mapas y Visualización**: Servicio de mapas temáticos e interactivos.
- **Datos Abiertos**: Descarga masiva de catálogos y series históricas en formatos estándar (Excel/CSV).
- **Exportación de Reportes**: Generación dinámica de reportes en PDF y archivos Excel.

### Área Administrativa

Panel de administración protegido para la gestión de contenidos:

- **Dashboard**: Monitoreo de "Salud de Datos" y métricas del sistema.
- **Gestión de Catálogos**: ABM (Altas, Bajas, Modificaciones) completo para:
    - Dimensiones y Temáticas
    - Indicadores y Variables
- **Importación Masiva**: Módulo avanzado (`ImportController`) para cargar:
    - Dimensiones, Temáticas, Indicadores.
    - Datos Estadísticos y Datos Complejos desde plantillas Excel.
    - Instrumentos de planeación.
- **Gestión de Usuarios**: Administración de cuentas de acceso.
- **Instrumentos**: Control de instrumentos y su asignación municipal.

## 🛠️ Stack Tecnológico

- **Framework**: Laravel 8.x
- **PHP**: 7.4 / 8.0
- **Base de Datos**: MySQL
- **Frontend**: Tailwind CSS (v3.x), Alpine.js, Blade
- **Autenticación**: Laravel Breeze / Sanctum (Infraestructura base)
- **Librerías Clave**:
    - `maatwebsite/excel`: Importación y exportación de datos.
    - `barryvdh/laravel-dompdf`: Generación de documentos PDF.
    - `fruitcake/laravel-cors`: Manejo de CORS para API.
    - `guzzlehttp/guzzle`: Cliente HTTP.

## 📦 Instalación

### Requisitos Previos

- PHP >= 7.4
- Composer
- MySQL
- Node.js y NPM

### Pasos de Instalación

1.  **Clonar el repositorio**

    ```bash
    git clone <repository-url>
    cd fichas_municipales
    ```

2.  **Instalar dependencias de PHP**

    ```bash
    composer install
    ```

3.  **Instalar dependencias de Node.js**

    ```bash
    npm install
    ```

4.  **Configurar el archivo de entorno**

    ```bash
    cp .env.example .env
    ```

    Editar `.env` con tus credenciales de base de datos:

    ```env
    DB_DATABASE=fichas_municipales
    DB_USERNAME=root
    DB_PASSWORD=
    ```

5.  **Generar la clave de aplicación**

    ```bash
    php artisan key:generate
    ```

6.  **Ejecutar las migraciones**

    ```bash
    php artisan migrate
    ```

7.  **Compilar assets (Tailwind CSS / JS)**

    ```bash
    npm run dev
    # o para producción
    npm run production
    ```

8.  **Crear el enlace simbólico de storage**

    ```bash
    php artisan storage:link
    ```

9.  **Iniciar el servidor**

    ```bash
    php artisan serve
    ```

    Accede a `http://localhost:8000`.

## 📁 Estructura del Proyecto

```
fichas_municipales/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       ├── Admin/
│   │       │   ├── ImportController.php        # Lógica de importación masiva
│   │       │   ├── DatoHistoricoController.php # Gestión de datos
│   │       │   ├── CatalogoController.php      # Catálogos del sistema
│   │       │   └── ...
│   │       ├── FichaController.php             # Controlador principal público
│   │       └── ...
│   └── Models/
│       ├── DatoHistorico.php
│       ├── Indicador.php
│       ├── Municipio.php
│       ├── Variable.php
│       └── ...
├── database/
│   └── migrations/
├── resources/
│   └── views/
│       ├── admin/          # Vistas del panel de control
│       ├── fichas/         # Vistas públicas de fichas
│       └── layouts/        # Plantillas base
└── routes/
    └── web.php             # Definición de rutas
```

## 🌐 Rutas Principales

### Rutas Públicas

- `/` - Inicio
- `/banco-indicadores` - Buscador principal de indicadores
- `/banco-indicadores/resumen/{municipio}` - Resumen estadístico municipal
- `/datos-abiertos` - Sección de descarga de datos abiertos
- `/api/municipios/search` - API de búsqueda de municipios

### Rutas Administrativas (requieren login)

- `/admin/dashboard` - Tablero principal
- `/admin/importar` - Centro de importación de datos
- `/admin/datos` - Gestión manual de datos históricos
- `/admin/catalogos` - Administración de catálogos (Dimensiones, Temáticas, etc.)
- `/admin/users` - Gestión de usuarios

## 📝 Notas Técnicas

- **Validación de Importaciones**: El sistema cuenta con validaciones estrictas en `ImportController` para asegurar la integridad de los datos cargados desde Excel.
- **APIs Internas**: Se utilizan rutas API (`/api/data`, `/api/mapa-datos`) para alimentar los gráficos y mapas interactivos del frontend.

## 🤝 Contribución

Para contribuir al proyecto, por favor sigue el flujo de trabajo estándar de Pull Requests y asegúrate de mantener los estándares de código existentes.

## 📄 Licencia

Propiedad exclusiva. Todos los derechos reservados.

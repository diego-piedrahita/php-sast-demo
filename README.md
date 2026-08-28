# PHP SAST Demo

Proyecto de prueba (login + registro de productos) para evaluar como ejecutar **SAST para PHP en GitHub sin usar CodeQL ni SonarQube**.

> ⚠️ Este codigo contiene vulnerabilidades y malas practicas **intencionales** con fines de prueba. No usar en produccion.

## Stack

- Backend: PHP 7.4+ / 8.x con `mysqli`
- Frontend: HTML, CSS, JavaScript (vanilla)
- Base de datos: MySQL

## Vulnerabilidades intencionales incluidas

| Archivo | Vulnerabilidad |
|---|---|
| [src/login.php](src/login.php) | SQL Injection (concatenacion en consulta), hash MD5 de contrasenas, divulgacion de error SQL |
| [src/register.php](src/register.php) | SQL Injection, XSS reflejado, hash MD5, sin validacion de entrada |
| [src/products/add.php](src/products/add.php) | SQL Injection, sin token CSRF |
| [src/products/list.php](src/products/list.php) | XSS almacenado (salida sin `htmlspecialchars`) |
| [src/products/view.php](src/products/view.php) | SQL Injection vía parametro GET, IDOR, divulgacion de error SQL |
| [src/config/config.php](src/config/config.php) | Credenciales y secretos hardcodeados |
| [src/logout.php](src/logout.php) | Sesion no invalidada/regenerada correctamente (session fixation) |

## Analisis de codigo fuente (SAST) sin CodeQL / SonarQube

Se usa una combinacion de dos herramientas open-source para PHP, ambas publicando resultados en formato **SARIF** hacia la pestaña **Security > Code scanning alerts** de GitHub, igual que lo haria CodeQL:

1. **[Psalm](https://psalm.dev/)** con **taint analysis** (`--taint-analysis`): rastrea el flujo de datos desde fuentes no confiables (`$_GET`, `$_POST`) hasta sinks peligrosos (queries SQL, `echo`, etc.), detectando SQL Injection, XSS, etc.
2. **[phpcs-security-audit](https://github.com/pheromone/phpcs-security-audit)** sobre PHP_CodeSniffer: detecta patrones inseguros (uso de `md5`/`sha1` para passwords, `eval`, `extract`, configuracion insegura de sesiones, etc.). Su reporte JSON se convierte a SARIF con [scripts/phpcs-to-sarif.php](scripts/phpcs-to-sarif.php).

El workflow [.github/workflows/sast.yml](.github/workflows/sast.yml) ejecuta ambas herramientas en cada push/PR a `dev`, `qas` y `main`, y sube los dos SARIF con categorias distintas (`psalm-taint-analysis` y `phpcs-security-audit`) para que los hallazgos aparezcan en la pestaña **Security** del repositorio.

## Uso local

```bash
composer install

# Analisis con Psalm (taint analysis)
composer run sast:psalm

# Analisis con phpcs-security-audit
composer run sast:phpcs
php scripts/phpcs-to-sarif.php phpcs-results.json phpcs-results.sarif
```

## Ejecutar la aplicacion localmente

1. Crear la base de datos con [database/schema.sql](database/schema.sql).
2. Ajustar credenciales en [src/config/config.php](src/config/config.php) si es necesario.
3. Levantar el servidor embebido de PHP:

```bash
php -S localhost:8000 -t src
```

4. Abrir `http://localhost:8000` (usuario de prueba: `admin` / `admin123`).

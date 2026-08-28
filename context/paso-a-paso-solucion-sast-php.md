# Paso a paso: SAST para PHP en GitHub sin CodeQL ni SonarQube

Documento de seguimiento del ejercicio descrito en [context.md](context.md). Resume, en orden cronológico, lo realizado durante la sesión de trabajo.

## 1. Análisis del contexto y decisión de herramientas

Se partió del requerimiento: analizar código PHP en GitHub sin CodeQL (no soporta PHP nativamente) ni SonarQube, mostrando resultados dentro del propio GitHub.

Se evaluaron alternativas y se eligió una combinación de dos herramientas open-source, por ser la opción más robusta para un escenario productivo:

| Herramienta | Rol | Por qué |
|---|---|---|
| **Psalm** (`--taint-analysis`) | Motor principal de seguridad | Único analizador PHP open-source con *taint analysis* real: rastrea el flujo de datos desde fuentes no confiables (`$_GET`, `$_POST`) hasta *sinks* peligrosos (queries SQL, `echo`, etc.). Detecta SQL Injection, XSS, etc. |
| **phpcs-security-audit** (sobre PHP_CodeSniffer) | Complemento por reglas | Detecta patrones inseguros puntuales (funciones débiles, malas prácticas) que el taint analysis no siempre cubre. |

Ambas herramientas exportan resultados a **SARIF 2.1.0**, el formato estándar que GitHub usa para poblar la pestaña **Security → Code scanning alerts**, replicando la experiencia de CodeQL sin usarlo.

Se descartó usar una sola herramienta: PHPCS/phpcs-security-audit solo no cubre flujo de datos real, y PHPStan no tiene un motor de seguridad maduro comparable a Psalm.

## 2. Creación del proyecto demo (login + registro de productos)

Se construyó una aplicación PHP mínima funcional, con frontend en HTML/CSS/JS y backend en PHP (`mysqli`), con la siguiente estructura:

```
test-php-sast/
├── composer.json                    # Dependencias: psalm, phpcs, phpcs-security-audit
├── psalm.xml / phpcs.xml            # Configuración de los analizadores
├── database/schema.sql              # Tablas users, products
├── scripts/phpcs-to-sarif.php       # Convierte el reporte JSON de phpcs a SARIF
├── .github/workflows/sast.yml       # Pipeline de análisis (CI)
├── src/
│   ├── config/config.php            # Conexión a BD
│   ├── includes/{auth,header,footer}.php
│   ├── index.php / login.php / register.php / logout.php / dashboard.php
│   ├── products/{list,add,view}.php
│   └── assets/{css,js}
└── README.md
```

## 3. Vulnerabilidades y malas prácticas sembradas intencionalmente

Para que el análisis tuviera hallazgos reales que validar:

| Archivo | Vulnerabilidad |
|---|---|
| `src/login.php` | SQL Injection (concatenación en query), hash MD5 de contraseñas, divulgación de error SQL |
| `src/register.php` | SQL Injection, XSS reflejado, hash MD5, sin validación de entrada |
| `src/products/add.php` | SQL Injection, sin token CSRF |
| `src/products/list.php` | XSS almacenado (salida sin `htmlspecialchars`) |
| `src/products/view.php` | SQL Injection vía parámetro `GET`, IDOR, divulgación de error SQL |
| `src/config/config.php` | Credenciales y secretos hardcodeados |
| `src/logout.php` | Sesión no invalidada/regenerada correctamente (session fixation) |

## 4. Construcción del pipeline de análisis (`.github/workflows/sast.yml`)

Workflow que se dispara en `push`/`pull_request` a `dev`, `qas` y `main`, con los siguientes pasos:

1. `actions/checkout` + `shivammathur/setup-php` (PHP 8.2).
2. `composer install` de las dependencias de análisis.
3. `vendor/bin/psalm --taint-analysis --report=psalm-results.sarif` → sube el SARIF con `github/codeql-action/upload-sarif` bajo la categoría `psalm-taint-analysis`.
4. `vendor/bin/phpcs --standard=phpcs.xml --report=json` → genera `phpcs-results.json`.
5. `scripts/phpcs-to-sarif.php` convierte ese JSON a SARIF 2.1.0 (no existe conversor oficial), generando reglas y resultados con ubicación de archivo/línea.
6. Se sube el segundo SARIF bajo la categoría `phpcs-security-audit`.

Ambos SARIF conviven en la misma pestaña Security, diferenciados por categoría y por el nombre de la herramienta (`tool.driver.name`).

## 5. Publicación del repositorio en GitHub

- Se decidió publicar en la cuenta personal `https://github.com/diego-piedrahita` (no en la organización `bupalatamea`, mencionada inicialmente en las notas de gobierno del piloto).
- Se ajustó `composer.json` (`name`) de `bupalatamea/php-sast-demo` a `diego-piedrahita/php-sast-demo`.
- Se inicializó el repositorio local (`git init`), commit inicial, y se creó/publicó el repositorio remoto con `gh repo create ... --push`, directo a `main` (por tratarse de un repositorio de pruebas, sin necesidad del flujo `feature → dev → qas → main`).

Resultado: [https://github.com/diego-piedrahita/php-sast-demo](https://github.com/diego-piedrahita/php-sast-demo)

## 6. Incidencia detectada y corregida

El primer run del workflow falló en `composer install`:

```
Root composer.json requires pheromone/phpcs-security-audit ^3.0,
found ... but it does not match the constraint.
```

Causa: la versión `^3.0` no existe publicada en Packagist (la última disponible es `2.0.1`). Se corrigió el constraint a `^2.0` en `composer.json`, se commiteó y se volvió a subir. El segundo run finalizó en estado `success`.

## 7. Validación de resultados en GitHub Security

Se consultó la API `repos/{owner}/{repo}/code-scanning/alerts` y se confirmaron **14 alertas publicadas**, repartidas así:

**Psalm (taint analysis) — 7 hallazgos, severidad `error`:**
- `TaintedSql` (SQL Injection) en `login.php:12`, `register.php:13`, `register.php:22`, `register.php:45`, `products/add.php:20`, `products/view.php:10`.
- `TaintedTextWithQuotes` (XSS) en `register.php:45`.

**phpcs-security-audit — 7 hallazgos:**
- 6× `WarnMysqlimysqli_query` (uso de `mysqli_query` con parámetro dinámico) en `login.php`, `register.php` (x2), `products/add.php`, `products/list.php`, `products/view.php`.
- 1× `Internal.NoCodeFound` en `footer.php` (falso positivo interno de phpcs-security-audit, sin impacto real; candidato a descartarse como *won't fix*).

## 8. Conclusión

Queda demostrado que es posible ejecutar SAST para proyectos PHP directamente en GitHub, sin CodeQL ni SonarQube, integrando **Psalm** (taint analysis) y **phpcs-security-audit** (reglas de patrones inseguros) en un mismo workflow de GitHub Actions, publicando ambos resultados como SARIF en la pestaña nativa **Security → Code scanning alerts** del repositorio.

## 9. Evaluación de brechas frente a un programa DevSecOps/AppSec completo

La solución construida cubre **SAST**, pero un programa de AppSec en GitHub normalmente exige más capacidades. Estado verificado (vía `gh api repos/diego-piedrahita/php-sast-demo`) al momento de esta evaluación:

| Capacidad | ¿Cubierta hoy? | Cómo lograrla en GitHub |
|---|---|---|
| SAST | ✅ Sí | Psalm (taint analysis) + phpcs-security-audit, SARIF en Code Scanning |
| SCA (CVE + licencias en dependencias) | ❌ No — `vulnerability-alerts` estaba deshabilitado (`404 Vulnerability alerts are disabled`) | Habilitar **Dependabot alerts** + `security_and_analysis.dependabot_security_updates`, y `.github/dependabot.yml` para actualizaciones automáticas |
| SBOM | ❌ No | Exportar SBOM SPDX vía endpoint nativo `GET /repos/{owner}/{repo}/dependency-graph/sbom`, automatizado en el workflow como artifact |
| Secret scanning | ⚠️ Parcial | `secret_scanning.status = enabled` y `secret_scanning_push_protection.status = enabled` (por defecto en repos públicos), pero **0 alertas** detectadas: los patrones genéricos hardcodeados en `src/config/config.php` (`root`/`admin123`, `APP_SECRET`) no calzan con los patrones de partners que detecta el escaneo estándar, solo credenciales de proveedores conocidos (tokens de servicios). Para secretos genéricos se requeriría un motor adicional (p. ej. Gitleaks) o patrones custom (solo disponible con GHAS Enterprise) |
| Calidad de código / deuda técnica | ❌ No | Añadir PHPMD (mantenibilidad/complejidad) o phpcpd (duplicación) como jobs adicionales del workflow |
| Escaneo de IaC | ❌ No aplica aún | No hay archivos IaC en el repo; si se agregan (Bicep/Terraform/Dockerfile), usar Trivy o Checkov |
| Integración CI/CD | ✅ Sí | GitHub Actions dispara en push/PR a `dev`, `qas`, `main` y `workflow_dispatch` |
| Flujo de excepciones/aceptación de riesgo | ⚠️ Parcial | Code Scanning permite `dismiss` con razón (`false positive`, `won't fix`, `used in tests`), trazable vía API (`dismissed_by/at/reason`), pero sin aprobación dual ni gobierno formal |
| Métricas ejecutivas / dashboards de portafolio | ❌ No | Requiere GHAS Enterprise (Security Overview) o un pipeline propio que consuma la API de Code Scanning/Dependabot/Secret Scanning |

**Conclusión de la brecha:** el POC resuelve el problema puntual solicitado (SAST para PHP sin CodeQL/SonarQube). Para un programa AppSec completo restan, en orden de esfuerzo: (1) habilitar Dependabot alerts + `dependabot.yml` (bajo esfuerzo, nativo), (2) automatizar export de SBOM (bajo esfuerzo, nativo), (3) formalizar el flujo de excepciones y (4) evaluar GHAS Enterprise para métricas ejecutivas y detección de secretos genéricos.

## 10. Implementación de extensiones (Dependabot, Secret Scanning, SBOM)

A partir de la evaluación anterior, se implementaron las extensiones de menor esfuerzo directamente sobre el repositorio ya publicado:

- **Dependabot alerts (SCA):** se habilitaron las alertas de vulnerabilidades (`vulnerability-alerts`) y las actualizaciones de seguridad automáticas (`dependabot_security_updates`) vía API, y se añadió `.github/dependabot.yml` con dos ecosistemas: `composer` (dependencias PHP) y `github-actions` (versiones de acciones usadas en los workflows), ambos con revisión semanal.
- **Secret scanning:** se confirmó que ya estaba habilitado por defecto (repo público) junto con *push protection*; se documenta como limitación conocida que no cubre los secretos genéricos sembrados en `src/config/config.php` (solo detecta patrones de proveedores conocidos).
- **SBOM:** se agregó un job al workflow (`sast.yml`) que consulta el endpoint nativo `GET /repos/{owner}/{repo}/dependency-graph/sbom` (formato SPDX) usando el token de GitHub Actions y publica el resultado como artifact descargable en cada ejecución.

# Project Overview

Este proyecto funciona como un middleware entre las plataformas de comercio electrónico Shopify (shopify.com) y Shiphero (shiphero.com), permitiendo la integración de ambas plataformas
al nivel de: 

- Catálogo de productos
- Imágenes de productos
- Órdenes de Compra (Purchase Orders, POs)
- Items de Órdenes de Compra (Purchase Order Items) y cantidades asociadas de los productos en cada orden.
- Proveedores (Vendors)

# Project Runtime Enviroment

EL proyecto se ejecuta bajo un stack tipo LAMP con Linux Ubuntu 22.04.2 LTS, PHP 8.1.21 y MariaDB 10.11.2, todo esto corriendo en contenedores docker, por separado, un contenedor para cada servicio (app y DB) para mayor información ver el archivo docker-compose.yml

Adicionalmente del stack, el proyecto utiliza el framework php Laravel en su versión 10.17

# Condiciones Varias del Proyecto.

- El proyecto está desarrollado en PHP 8.1
- El proyecto está desarrollado con Laravel 10.14
- El proyecto está desarrollado con MariaDB 10.11.2
- El proyecto está desarrollado con Linux Ubuntu 22.04.2 LTS
- El proyecto está desarrollado con Docker
- El proyecto está desarrollado con Docker Compose
- El sistema de autenticación y autorización está implementado 

# Running Tests, Debugging o Troubleshooting

Para ejecutar los tests del proyecto se debe tener en cuenta las siguientes consideraciones:

- Los tests se deben ejecutar siempre dentro del contenedor denominado "sleefs-app" (contenedor que corre la aplicación) o en el entorno de staging (ver en el archivo .ENV la constante APP_ENV) el contenedor se denomina stg-sleefs-app.
- La ruta del proyecto dentro del contenedor de la aplicación (sleefs-app o stg-sleefs-app) es: /var/www/app
- La base de datos corre en el contenedor denominado "sleefs-db" (igual que con el contenedor de aplicación, el contenedor de la base de datos en staging puede ser stg-sleefs-db)
- Culquier ejecución de comandos, ya sea para testing, debugging o troubleshooting dentro del contenedor de aplicación (sleefs-app o stg-sleefs-app) debe ser realizada desde la ruta /var/www/app.

Un comando base para testing, debugging o troubleshooting es:

```bash
docker exec sleefs-app bash -c "cd /var/www/app && php artisan {comando}"
```

o bien de manera general

```bash
docker exec sleefs-app bash -c "cd /var/www/app && {comando}"
```
- Para ejecutar comandos que requieran interacción con el usuario, se debe usar el flag -it

# CONTRIBUTING

- Definición de variables y métodos en Camel Case
- Definición de clases e interfaces en Pascal Case
- Definición de constantes y variables de entorno en Upper Case 
- Los nombres de los métodos y clases siempre en una sola línea.

# Patrones de Codificación, Paradigmas de Programación y Buenas Prácticas

- El proyecto está desarrollado con POO (teniendo en cuenta que trabaja con el framework Laravel), siguiendo las filosofías SOLID y DRY

- Como existen muchas formas de implementar los principios SOLID, a continuación se definen reglas de codificación para herencia, inyección de dependencias e inversión de control que respetan los principios SOLID y DRY:

    - Herencia: 
        
        - Se prioriza la composición sobre la herencia.
        - Se evita el uso de herencia múltiple.
        - Se prioriza la definición de interfaces para la herencia, en lugar de clases abstractas.
        - Se evita el uso de herencia profunda (más de 3 niveles).
        - Se evita la definición de clases abstractas (lo ideal es solo usar interfaces).
        - Se evita la herencia desde clases que no son abstractas.
        - Las interfaces deben definir conceptos, no implementaciones, por ejemplo: Interfaz MétodoDeTransporteInterfaz, clases que heredan: MétodoDeTransporteCoche, MétodoDeTransporteAvion, MétodoDeTransporteBarco, como se puede observar la interfaz define un concepto, no una implementación específica.
        - Las clases que implementan una interfaz deben implementar todos los métodos definidos en la interfaz.
        
    - Inyección de dependencias:
        - Se prioriza la inyección de dependencias por constructor sobre la inyección de dependencias por método.
        - Como segunda alternativa, se puede gestionar la inyección de dependencias por métodos get y set, siempre respetando la interfaz ya definida de la clase dependiente (la que gestiona las dependencias por los métodos get/set).
        - En caso de que la clase dependiente no tenga una interfaz, se debe crear una interfaz para ella.
        - Para métodos que tengan una dependencia específica y que esa dependencia no genere un cambio de estado en la clase dependiente, se puede inyectar la dependencia como un parámetro del método.
        - En la definición de parámetros, el orden debe ser: Primero los parámetros obligatorios, luego los parámetros opcionales. También, primero los objetos que representan o contienen datos, junto con los datos primitivos, posteriormente (por último) los objetos que representan o contienen lógica de negocio.

    - Instanciación de objetos:
        - Se prioriza la inyección de dependencias por constructor sobre la instanciación de objetos dentro del método.
        - Se evita la instanciación de objetos dentro del método, a menos que sea necesario para la lógica del método.
        - Se evita la instanciación de objetos dentro de los constructores de las clases, a menos que sea necesario para la lógica del constructor.
        - El orden esperado de las estrategias para instanciar objetos es:

            1. Utilizar el Service Container de Laravel a través de la inyección de dependencias en métodos (ya sean métodos constructures, setters o cualquier otro método que reciba la dependencia como parámetro).
            2. Instanciar el objeto dentro del método utilizando el Service Container de Laravel, es decir, que las clases deben ser registradas en el service container de Laravel a través del ServiceProvider del package correspondiente.
            3. Utilizar el patrón Factory para instanciar objetos.

        En este apartado, lo ideal es evitar la instaciación de objetos directamente a través de la palabra reservada new.
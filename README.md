# PHP Image Manager API

This project is a POC for a PHP image upload manager. The project is implemented in PHP using the Slim framework to build a backend API. The user register and login routes can be used for other backend APIs if desired (just remove all the unnecessary image related files, routes, and SQL statements fom the sql script).

The API uses [JSON Web Tokens](https://jwt.io/) to authenticate a user. Routes `/api/register` and `/api/login` can be found in the `/www/api/index.php` file. Sample curl and JS commands for testing can be found in the `text-commands.sample` file.

## Docker

The dev environment is managed via docker with the following containers:

- mariadb:10.9
- nginx:latest
- php:8.1-fpm
- phpmyadmin/phpmyadmin:latest

The web server can be reached at `localhost:80`, phpmyadmin can be accessed at `localhost:8080`, and the database at `localhost:3306`.

See the [docker-compose.yml](./docker-compose.yml) file for detais and config options.

## Composer

PHP is managed using composer. To manage packages, use the following command:

```sh
docker run --rm --interactive --tty --volume `./www/DESIRED_LOCATION`:/app composer require PACKAGE
```

Modify the command above to install, update, remove, etc. as necessary.

## Layout

The project is laid as follows:

``` sh
/
|- nginx-conf/
|  |- nginx.conf
|
|- www/
|  |- api/
|  |  |- config/
|  |  |  |- db-access.php
|  |  |  
|  |  |- controllers/
|  |  |  |- LoginController.php
|  |  |  |- UserController.php
|  |  |  
|  |  |- models/
|  |  |  |- User.php
|  |  |
|  |  |- utils/
|  |  |  |- utils.php
|  |  |
|  |  |- vendor/
|  |  |- index.php
|  |
|  |- images/
|  |- js/
|  |- index.php
|
|- docker-compose.yml
|- img_db.sql
|- php-dockerfile
|- test-commands.sample
```

### 


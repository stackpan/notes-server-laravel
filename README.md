# Notes API Laravel

Dicoding notes app the backend project, but it's powered by laravel.

## Overview

This project was made using Laravel 10.
Like general backend applications, it can process a request and send a
response, store into a database and cache it (we use [Redis](https://redis.io) for caching),
authenticate request & authorize resource
(we use [JWT](https://jwt.io/)), process queue job, send emails, store images, etc.

For the application feature, we build it according to
the [original project](https://github.com/dicodingacademy/a271-backend-menengah-labs).

## Usage

We have documented the [OpenAPI spec](https://swagger.io/specification/) of the app.
You can
check [here](https://github.com/stackpan/notes-server-laravel/tree/main/docs).

## How to Run

### Docker (recommended)

This method is preferred. Because it can make your life easier.

You just need Docker and its stuff like Docker Compose is installed to your machine.

After that, clone this repo and `cd` to it:

```
git clone https://github.com/stackpan/notes-server-laravel
```

We already made a preconfigured `docker-compose.yaml` file in the root project.
In this file, we have prepared some service configuration that will be ready to run.
Like `mariadb` for the database, `redis` for server side caching service, `mailpit` for SMTP testing server.
And, there is already have a frontend service in it, this is cool.
Isn't it?

This is the advantage of using this method. You don't need to bother thinking about the support services.

You can change the docker compose configuration if you want.

Next, what you need to do is just set some `.env` values.
You can get the template from the `.env.example` file by copying it.

```shell
cp .env.example .env
```

We recommend filling these keys:
```dotenv
# Your database name
DB_DATABASE=

# Your database credentials
DB_USERNAME=
DB_PASSWORD=

# Your root user database password
DB_ROOT_PASSWORD=

# You can change it as you wish, if you want to
MAIL_FROM_ADDRESS=
```

For other environment keys, you can check it on the docker compose file

If you're already done, let's start the containers:
```shell
docker compose up -d
```

### Manual Installation

Just like the Laravel app, you need these things installed to your machine:

- [PHP 8.1](https://www.php.net/releases/8.1/en.php),
  with [required extensions for Laravel](https://laravel.com/docs/10.x/deployment#server-requirements)
- [Composer](https://getcomposer.org/)
- [RDBMS](https://en.wikipedia.org/wiki/Relational_database) Server like one of
  these: [MySQL](https://www.mysql.com/), [MariaDB](https://mariadb.com/),
  or [PostgreSQL](https://www.postgresql.org/) (select one)
- [Redis](https://redis.io/) Server
- [SMTP](https://en.wikipedia.org/wiki/Simple_Mail_Transfer_Protocol) Server (real or testing).
  For testing, you can use
  one of these: [mailpit](https://github.com/axllent/mailpit), [mailtrap](https://mailtrap.io/inboxes), etc.


After you ready for all the required things above, follow these steps:

1. Clone this repo & `cd` to it

    ```shell
    git clone https://github.com/stackpan/notes-server-laravel
    ```

2. Fill out the `.env` file. You can copy the template from `.env.example` file

    ```shell
    cp .env.example .env
    ```

   You just have to fill some of them:
    ```dotenv
    # It should be 'true' or 'false'
    # The default is 'false' for production mode
    APP_DEBUG=

    # This server address
    # Example: 'http://example.com', 'http://localhost', 'http://localhost:8000', 'http://172.16.0.10'
    # It must be declared
    # If the port is other than 80, you MUST declared it explicitly
    # If you are using via our preconfigured docker compose, you can skip it
    APP_URL=
    ```

    ```dotenv
    # Set to 'mysql' if you are gonna use MySQL or MariaDB
    # Set to 'pgsql' if you are gonna use PostgreSQL
    DB_CONNECTION=

    # Your database server address
    # Example: 'http://mydbaddr', 'http://localhost', 'http://172.16.0.11'
    # If you are using via our preconfigured docker compose, you can skip it
    DB_HOST=

    # The default is 3306. For PostgreSQL, the default is 5432
    DB_PORT=

    # Your database name
    DB_DATABASE=

    # Your database credentials
    DB_USERNAME=
    DB_PASSWORD=
    ```

    ```dotenv
    # Your Redis server address
    # Example: 'http://myredisaddr', 'http://localhost', 'http://172.16.0.13'
    # If you are using via our preconfigured docker compose, you can skip it
    REDIS_HOST=

    # Your Redis password
    # You can leave it blank if you don't set the password
    REDIS_PASSWORD=

    # The default Redis port is 6379
    REDIS_PORT=
    ```

    ```dotenv
    # Your SMTP server address
    # Example: 'http://mysmtpaddr', 'http://localhost', 'http://172.16.0.14'
    # If you are using via our preconfigured docker compose, you can skip it
    MAIL_HOST=

    # Your SMTP server port
    MAIL_PORT=

    # Your SMTP server credentials
    # You can blank it if you dont set the credentials
    MAIL_USERNAME=
    MAIL_PASSWORD=
    MAIL_ENCRYPTION=

    # You can change it as you wish, if you want to
    MAIL_FROM_ADDRESS=
    ```

3. Install the dependencies using Composer

    ```shell
    composer install --optimize-autoloader --no-dev
    ```

4. Generate app keys and JWT secrets

    ```shell
    php artisan key:generate
 
    echo yes | php artisan jwt:secret
    ```

5. Generate app caches

    ```shell
    php artisan route:cache

    php artisan view:cache
   
    php artisan config:cache
    ```
   
6. Run database migration

    ```shell
    php artisan migrate:fresh
    ```

7. And finally, run the app
    
    ```shell
    php artisan serve --host=0.0.0.0 --port=<THIS_APP_PORT> & php artisan queue:work -v
    ```

#### Frontend Service

To install the frontend service,
clone [this repository](https://github.com/stackpan/notes-client) and follow the instructions inside it.

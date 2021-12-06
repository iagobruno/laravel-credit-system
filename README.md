# laravel-coin-system

## Getting started

Clone this repo and run commands in the order below:

```bash
composer install
yarn install
cp .env.example .env # And edit the values
php artisan key:generate
```

Then start Docker containers:

```bash
sail up -d
```

And run the migrations:

```bash
sail artisan migrate
sail artisan db:seed # Optional
```

## Running tests

To run tests, first create a database named "testing-laravel"

```sql
CREATE DATABASE "testing-laravel";
```

And run the following command:

```bash
sail artisan test
# sail artisan test --filter GetUserTest
# sail artisan test --filter "Deve retornar um erro ..."
# sail artisan test --stop-on-failure
```

> NOTE: Make sure you started the docker containers first.

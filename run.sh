#!/bin/bash

php artisan config:cache

php artisan migrate:fresh

php artisan serve --host=0.0.0.0 --port=9000 & php artisan queue:work -v

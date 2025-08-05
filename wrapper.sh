#!/bin/bash

# Start the first process
php artisan queue:work --queue=default --timeout=0 &
php artisan queue:work --queue=external --timeout=0 &
php artisan queue:work --queue=calculation --timeout=0 &
php artisan schedule:work &
  
# Start the second process
php-fpm &
  
# Wait for any process to exit
wait -n
  
# Exit with status of process that exited first
exit $?
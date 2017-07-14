#!/bin/sh

scp -r app/Http/* root@115.29.163.138:/var/www/web/app/Http/

scp -r app/Console/* root@115.29.163.138:/var/www/web/app/Console/

scp -r app/Http/Controllers/Api/V1/GoalController.php root@115.29.163.138:/var/www/web/app/Http/Controllers/Api/V1

scp -r app/Http/Controllers/Api/V1/* root@115.29.163.138:/var/www/web/app/Http/Controllers/Api/V1


scp -r app/* root@115.29.163.138:/var/www/web/app/

scp -r app/Libs/* root@115.29.163.138:/var/www/web/app/Libs/

scp -r app/Models/* root@115.29.163.138:/var/www/web/app/Models/

scp -r resources/views/* root@115.29.163.138:/var/www/web/resources/views/

scp -r app/Http/routes.php root@115.29.163.138:/var/www/web/app/Http/

scp -r app/User.php root@115.29.163.138:/var/www/web/app/


scp -r vendor/laravel/* root@115.29.163.138:/var/www/web/vendor/laravel/




GRANT ALL PRIVILEGES ON *.* TO 'root'@'47.89.48.138' IDENTIFIED BY 'tuomi!@#$%^' WITH GRANT OPTION; 
nano templates/nginx.conf.jinja

    replace ngrok domain as server_name

fab stop; fab start

or

docker exec -it easydev-nginx nginx -s reload

docker exec -it hla-catalystlms-web /bin/bash

    php /var/www/html/admin/cli/purge_caches.php
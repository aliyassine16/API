Nginx Conf
================================
server {
        listen 80;
        server_name api.local;
        root /var/www/api.local/;
        index index.html index.htm index.php;


    location ^~ /api/ {
	rewrite "^/api/"	/api.php?$args last;
    }

    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}


api Call
================================
http://api.local/api/group/test/11/12



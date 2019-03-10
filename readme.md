# µIMG
µIMG (micro image) is a tiny self hosted image dump service, with no UI for uploading — only CLI. Inspired by [PictShare](https://pictshare.net/).

> This is still very much a work in progress, use at own risk.

Example of upload CLI function:
```
uimg () {
    curl -s -F "file=@${1:--}" https://your.uimg.instance/upload | jq -r
}
```

All images, uploaded and resized, are stored on a S3 backend, I use [Minio](https://github.com/minio/minio). There is currently no support for local file system storage, although implementing it would only require a simple configuration change of the [File Storage](https://laravel.com/docs/master/filesystem).

µIMG is meant to be placed behind a caching service, like a CDN, Cloudflare or a caching nginx web server.

## Features
* CLI upload
* On-the-fly image resizing
* Auto-rotate and strip exif data on upload
* S3 storage back-end
* Purge uploaded but never accessed images

## Requirements
* PHP 7.x
* MySQL
* Composer
* Imagick
* S3 ([Minio](https://github.com/minio/minio))

## Nginx config
```
server {
    listen 80;
    server_name something.something.something.uimg;

    root /var/www/uimg/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        try_files $fastcgi_script_name =404;
        set $path_info $fastcgi_path_info;
        fastcgi_param PATH_INFO $path_info;

        fastcgi_index index.php;
        include fastcgi.conf;
        fastcgi_pass unix:/run/php/php7.1-fpm.sock;

        if ($request_uri ~ \.(?:ico|gif|jpe?g|png|webp|bmp)$) {
            add_header Cache-Control "public";
            expires max;
        }
    }
}
```

## License

µIMG is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

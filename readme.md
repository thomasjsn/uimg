# µIMG
µIMG (micro image) is a tiny self hosted image dump service, with no UI for uploading — only CLI. Inspired by [PictShare](https://pictshare.net/).

> This is still very much a work in progress, use at own risk.

## Feature
* CLI upload
* On-the-fly image resizing
* Auto-rotate and strip exif data on upload
* Return URL for already existing images, instead of uploading
* S3 compatible storage back-end
* Purge uploaded but never accessed images
* No personal information is stored

All images, uploaded and resized, are stored on a S3 backend, I use [Minio](https://github.com/minio/minio). There is currently no support for local file system storage, although implementing it would only require a simple configuration change of the [File Storage](https://laravel.com/docs/master/filesystem).

µIMG is meant to be placed behind a caching service, like a CDN, Cloudflare or a caching nginx web server.

Each image has an `accessed` value that increases by one each time the image is viewed. This counter is mostly useless, since this service is meant be be behind a caching layer. But it can be used to determined if the image has been accessed at all. Images that have never been viewed, and are a week old will be purged.

Images can be resized by adding dimensions to the URL, e.g.: `/300x300/1u5c7w.jpg`, image ratio will not change. The resized image is stored on the S3 back-end and will be used for future requests.

## Requirements
* nginx (untested on Apache)
* PHP 7.x
* MySQL
* Composer
* Imagick
* S3 compatible storage (like [Minio](https://github.com/minio/minio))

## CLI upload
Example of upload CLI function:
```
uimg () {
    curl -s -F "file=@${1:--}" https://your.uimg.instance/upload | jq -r
}
```
> Package `jq` required for json decoding.

Usage:
```
cat my-image.jpg | uimg
```

Response:
```
{
  "status": "ok",
  "message": "Image successfully uploaded",
  "url": "https://your.uimg.instance/1u5c7w.jpg"
}
```

If you try to upload an image already uploaded, the URL of that image will be returned instead;
```
{
  "status": "ok",
  "message": "Image already uploaded",
  "url": "https://your.uimg.instance/1u5c7w.jpg"
}
```

## Scheduler
To run the scheduler a cron job must be added;
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Read more about scheduling [here](https://laravel.com/docs/master/scheduling).

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

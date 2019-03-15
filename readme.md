# µIMG
µIMG (micro image) is a tiny self hosted image dump service, with no UI for uploading — only CLI. Inspired by [PictShare](https://pictshare.net/) and built with [Lumen](https://lumen.laravel.com/).

> This is still very much a work in progress, use at own risk.

## Features
* CLI upload
* On-the-fly image resizing by changing the URL
* Auto-rotate and strip EXIF data on upload
* Return URL for already existing images, instead of uploading
* S3 compatible back-end storage
* Automatic image clean-up
* Issue and manage API keys for upload and delete

All images, uploaded and resized, are stored on a S3 backend. There is currently no support for local file system storage, although implementing it would only require a simple configuration change of the [File Storage](https://laravel.com/docs/master/filesystem).

µIMG is meant to be placed behind a caching service, like a CDN, Cloudflare or a caching nginx web server.

## Requirements
* nginx (not tested with Apache)
* PHP >= 7.1.3 (with OpenSSL, PDO, and Mbstring)
    * `php7.3-phm`
    * `php7.3-xml`
    * `php7.3-mbstring`
    * `php7.3-mysql`
* MySQL, Postgres, SQLite, or SQL Server
* [Composer](https://getcomposer.org/)
* ImageMagick (php-imagick)
* S3 compatible storage (like [Minio](https://github.com/minio/minio), or [Wasabi](https://wasabi.com/))

µIMG is build with [Lumen](https://lumen.laravel.com/), so its requirements and documentation applies.

## Install
Clone the repository:
```
$ git clone https://github.com/thomasjsn/uimg.git
```

Install packages:
```
$ cd /your/uimg/path
$ composer install
```

Set up database:
```
$ sudo mysql

MariaDB [(none)]> CREATE DATABASE uimg;
MariaDB [(none)]> GRANT ALL PRIVILEGES ON uimg.* To 'uimg'@'localhost' IDENTIFIED BY 't!3w5eYwns9X&sYI';
```

Set configuration options; make sure to set the key to a random string. Typically, this string should be 32 characters long.
```
$ cp .env.example .env
$ vim .env
```

Migrate the database:
```
$ php artisan migrate
```

* Make sure `storage/` is writable by the webserver.
* Edit your `php.ini` and change `upload_max_filesize` and `post_max_size` to a larger value.
* Add and enable nginx site, see [nginx config](#nginx-config), then reload nginx.

## Upgrade
Simply pull the repository and install any updated packages:
```
$ cd /your/uimg/path
$ git pull
$ composer install
```

## API keys
Issue new key:
```
$ ./artisan apikey:add (--comment="")
```

List all keys:
```
$ ./artisan apikey:list
```

Remove key:
```
$ ./artisan apikey:remove api-key
```

## Upload
The `key` value must match a valid API key the upload to be accepted.

### Alias
Put this in your `.bashrc` or `.zshrc`:
```
uimg () {
    curl -s -F "file=@${1:--}" -F "key=api-key" https://your.uimg.instance/upload | jq -r
}
```
Package `jq` required for json decoding.

Usage:
```
cat my-image.jpg | uimg
```

### Script
Since aliases isn't available in e.g. [Ranger](https://github.com/ranger/ranger), using a script is also an option. Make sure to make it available in PATH, like `/usr/local/bin/`:
```
#!/bin/bash

KEY=api-key
UIMG=`curl -s -F "file=@${1:--}" -F "key=$KEY" https://your.uimg.instance/upload`

echo $UIMG | jq -r '.url' | xclip -i -sel clipboard
echo $UIMG | jq -r
```

This will upload the image, and put the returned URL in the clipboard. This is useful because running the command from inside Ranger doesn't give the user time to view or copy the output. The script requires `jq` and `xclip`.

### Response
```
{
  "status": "ok",
  "operation": "create",
  "message": "Image successfully uploaded",
  "image_id": "1u5c7w",
  "size_mib": 2.724,
  "url": "https://your.uimg.instance/1u5c7w.jpg"
}
```

If you try to upload an image already uploaded, the URL of that image will be returned instead;
```
{
  "status": "ok",
  "operation": "retrieve",
  "message": "Image already uploaded",
  "image_id": "1u5c7w",
  "url": "https://your.uimg.instance/1u5c7w.jpg"
}
```

## Delete
The `key` value must match the API key that uploaded the image for the delete request to be accepted.
```
curl -X "DELETE" "https://your.uimg.instance/ei10v8.jpg?key=api-key"
```

### Response
```
{
  "status": "ok",
  "operation": "destroy",
  "message": "Image was deleted",
  "image_id": "ei10v8"
}
```

Please note that if µIMG is behind a caching service, the image might still be cached on that service and thus still available even after deletion until the cache expires.

## Resize
Images can be resized by adding dimensions to the URL, e.g.:
```
/300x300/1u5c7w.jpg
```
Image ratio will not change. The resized image is stored on the S3 back-end and will be used for future requests. A `X-Image-Derivative` header is added which will show if the image was found on the back-end storage, or created.

## Commands
### Cleanup
Running the cleanup command `artisan images:cleanup` will:

* Delete images older than 90 days that have not been viewed
* Delete images that have not been viewed in 1 year
* Delete database entries referencing missing image files
* Delete image derivatives older than 90 days

Note that if a caching service is placed in front of µIMG, most requests will not pass through. So the `last_viewed` field in the database does not correctly reflect when the image was last viewed. It's important that any caching headers have a shorter `maxage` than 1 year, e.g. 3 months; in which case the `last_viewed` field might only get updated when the cache is revalidated every 3 months. But that still gives a correct indication of which images have been stale for a whole year.

### Scheduler
To run the scheduler a cron job must be added;
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Read more about scheduling [here](https://laravel.com/docs/master/scheduling).

## Filesystem driver
Set the `FILESYSTEM_CLOUD` variable in your `.env` file to the filesystem driver of your choice. You'll find available values in the `config/filesystems.php` file. Also make sure to add all environment variables needed for that driver to your `.env` file. The `.env.sample` is set up with the `minio` driver.

## nginx config
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
        fastcgi_pass unix:/run/php/php7.3-fpm.sock;
    }

    location ~ \.(?:ico|txt)$ {
        add_header Cache-Control "public";
        expires 3M;
    }

}
```

## License

µIMG is open-sourced software licensed under the [MIT license](LICENSE).

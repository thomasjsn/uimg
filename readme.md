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

All images, uploaded and resized, are stored on a S3 backend. There is currently no support for local file system storage, although implementing it would only require a simple configuration change of the [File Storage](https://laravel.com/docs/master/filesystem).

µIMG is meant to be placed behind a caching service, like a CDN, Cloudflare or a caching nginx web server.

## Requirements
* nginx (not tested with Apache)
* PHP >= 7.1.3 (with OpenSSL, PDO, and Mbstring)
    * `php7.3-phm`
    * `php7.3-xml`
    * `php7.3-mbstring`
    * `php7.3-mysql`
* Redis server
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

Set configuration options; make sure to set the key to a random string. Typically, this string should be 32 characters long.
```
$ cp .env.example .env
$ vim .env
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
$ ./artisan apikey:add comment (--expire=nn)

comment  :  Key description or owner
expire   :  Days until key expires
```

List all keys:
```
$ ./artisan apikey:list
```

Remove key:
```
$ ./artisan apikey:remove api-key
```

On each post request the remaining time-to-live (TTL), in days, is returned for the key used. `-1` meaning the key will never expire.

## Upload
The `key` value must match a valid API key for the upload to be accepted.

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
  "message": "Image successfully uploaded",
  "size_mib": 2.724,
  "key_ttl_d": 29,
  "url": "https://your.uimg.instance/1u5c7w.jpg"
}
```

If you try to upload an image already uploaded, the URL of that image will be returned instead;
```
{
  "status": "ok",
  "message": "Image already uploaded",
  "key_ttl_d": 29,
  "url": "https://your.uimg.instance/1u5c7w.jpg"
}
```

## Resize
Images can be resized by adding dimensions to the URL, e.g.:
```
/300x300/1u5c7w.jpg
```
Image ratio will not change. The resized image is stored on the S3 back-end and will be used for future requests. A `X-Image-Derivative` header is added which will show if the image was found on the back-end storage, or created.

## Expiration
Images are set to expire 7 days after initial upload, this is kicked back to 1 year each time the image is viewed.

## Commands
### Cleanup
Running the cleanup command `artisan images:cleanup` will:

* Delete database entries referencing missing image files
* Delete images with no database entries
* Delete image derivatives older than 90 days

### Scheduler
To run the scheduler a cron job must be added;
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Read more about scheduling [here](https://laravel.com/docs/master/scheduling).

## Filesystem driver
Set the `FILESYSTEM_CLOUD` variable in your `.env` file to the filesystem driver of your choice. You'll find available values in the `config/filesystems.php` file. Also make sure to add all environment variables needed for that driver to your `.env` file. The `.env.sample` is set up with the `minio` driver.

## nginx config
With rate limiting;
```
limit_req_zone $binary_remote_addr zone=uimg:10m rate=2r/s;

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

        limit_req zone=uimg burst=3;
        limit_req_status 429;
    }

    location ~ \.(?:ico|txt)$ {
        add_header Cache-Control "public";
        expires 3M;
    }

}
```

## License

µIMG is open-sourced software licensed under the [MIT license](LICENSE).

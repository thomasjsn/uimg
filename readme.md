# µIMG
µIMG (micro image) is a tiny self hosted image dump service, with no UI for uploading — only CLI. Inspired by [PictShare](https://pictshare.net/) and built with [Lumen](https://lumen.laravel.com/).

> This is still very much a work in progress, use at own risk.

## Features
* CLI upload
* On-the-fly image resizing
* Auto-rotate and strip exif data on upload
* Return URL for already existing images, instead of uploading
* S3 compatible storage back-end
* Purge uploaded but never accessed images
* No personal information is stored

All images, uploaded and resized, are stored on a S3 backend, I use [Minio](https://github.com/minio/minio). There is currently no support for local file system storage, although implementing it would only require a simple configuration change of the [File Storage](https://laravel.com/docs/master/filesystem).

µIMG is meant to be placed behind a caching service, like a CDN, Cloudflare or a caching nginx web server.

## Requirements
* nginx (untested on Apache)
* PHP 7.x
* MySQL
* Composer
* Imagick
* S3 compatible storage (like [Minio](https://github.com/minio/minio))

## Install
TBD

## Upload
### Alias
Put this in your `.bashrc` or `.zshrc`:
```
uimg () {
    curl -s -F "file=@${1:--}" https://your.uimg.instance/upload | jq -r
}
```
Package `jq` required for json decoding.

Usage:
```
cat my-image.jpg | uimg
```

Response:
```
{
  "status": "ok",
  "message": "Image successfully uploaded",
  "image_id": "1u5c7w",
  "token": "7c6a636f2bb0694a33bca7c79c715e63075d66d76acfea5eccb35febb6355ad6",
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

### Script
Since aliases isn't available in e.g. [Ranger](https://github.com/ranger/ranger), using a script is also an option. Make sure to make it available in PATH, like `/usr/local/bin/`:
```
#!/bin/bash

UIMG=`curl -s -F "file=@${1:--}" https://your.uimg.instance/upload`

echo $UIMG | jq -r '.url' | xclip -i -sel clipboard
echo $UIMG | jq -r
```

This will upload the image, and put the returned URL in the clipboard. This is useful because running the command from inside Ranger doesn't give the user time to view or copy the output. The script requires `jq` and `xclip`.

## Delete
When uploading an image a token is returned with the response, this token can be used to delete the image. Curl example:
```
curl -X "DELETE" "https://your.uimg.instance/ei10v8/7c6a636f2bb0694a33bca7c79c715e63075d66d76acfea5eccb35febb6355ad6"
```

A 204 (empty response) code will be returned if delete was successful.

## Resize
Images can be resized by adding dimensions to the URL, e.g.:
```
/300x300/1u5c7w.jpg
```
Image ratio will not change. The resized image is stored on the S3 back-end and will be used for future requests.

## Cleanup
Running the cleanup command `artisan images:cleanup` will:

* Delete images older than 1 week that have not been viewed
* Delete images that have not been viewed in 1 year

Note that if a caching service is placed in front of µIMG, most requests will not pass through. So the `accessed` field in the database does not correctly reflect when the image was last viewed. It's important that any caching headers have a shorter `maxage` than 1 year, e.g. 3 months; in which case the `accessed` field might only get updated when the cache is revalidated every 3 months. But that still gives a correct indication of which images have been stale for a whole year.

### Scheduler
To run the scheduler a cron job must be added;
```
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Read more about scheduling [here](https://laravel.com/docs/master/scheduling).


## Database
Each image upload is stored in the database;

| Field | Usage |
| ----- | ----- |
| `id` | The unique ID generated for each image upload. |
| `filename` | ID + file extension. |
| `mime_type` | Used for returning correct `Content-Type` header. |
| `checksum` | SHA1 checksum of image file, after exif removal and auto rotation, used for finding duplicates. |
| `size` | Size of uploaded image, allows for different expire rules for large files. |
| `token` | Secure token generated for each image upload, needed for image deletion. |
| `accessed` | Timestamp of when image was last viewed, used for finding images never accessed and stale images. |
| `timestamp` | Data and time of image upload. |

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
            expires 3M;
        }
    }
}
```

## License

µIMG is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

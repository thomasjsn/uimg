# µIMGs
µIMGs (micro image service) is a tiny self hosted image dump service, with no UI for uploading — only CLI.

> This is still very much a work in progress, use at own risk.

Example of upload CLI function:
```
uimgs () {
    curl -s -F "file=@${1:--}" https://your.uimgs.instance/upload | jq -r
}
```

All images, uploaded and resized, are stored on a S3 backend, I use [Minio](https://github.com/minio/minio). There is currently no support for local file system storage, although implementing it would only require a simple configuration change of the [File Storage](https://laravel.com/docs/master/filesystem).

µIMGs is meant to be placed behind a caching service, like a CDN, Cloudflare or a caching nginx web server.

## Features
* CLI upload
* On-the-fly image resizing
* S3 storage back-end
* Purge uploaded but never accessed images

## Requirements
* PHP 7.x
* MySQL
* Composer
* Imagick
* S3 ([Minio](https://github.com/minio/minio))

## License

µIMGs is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

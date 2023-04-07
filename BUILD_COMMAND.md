# Build Command Documentation

This document describes the build command of the maid-cli.

## Arguments

### `--asset-url`

The asset URL to use for the build.

### `--build-arg`

Set build-time variables.

### `--revision`

The revision to use for the build. It's usually a unique identifier, or current timestamp `date('YmdHis')`.
It will be included as suffix in the image tag.

### `--working-dir`

The working directory is the directory where the maid-cli is executed. This is the directory 
where the `maid.yml` file is located. Per default the current working directory is used.

```shell
php maid build --working-dir=../laravel
```

### `--no-push`

This will prevent the image from being pushed to the registry.

## Direct Usage of the Build Command

For development purposes it's possible to directly use the build command. This is useful if you want to
build a Docker image without pushing it to a registry or deploying it to any platform.

The following command will build an image as `pkg.maid.sh/example-app/app:develop-test`:

```shell
php maid build develop --revision=build --working-dir=../laravel --no-push
```

Example using build arguments:

```shell
php maid build develop --revision=build --working-dir=../laravel --no-push
```

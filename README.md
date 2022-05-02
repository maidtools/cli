![](https://cdn.maid.sh/ghostzero/maid-banner-v2.png)

# Make Development Faster

Maid is a **containerized deployment platform** for Laravel, powered by Kubernetes. From simple to complex apps, launch your Laravel application on [maid.sh](https://maid.sh), enjoy the simplicity and focus on innovating and shipping value.

> Created by GhostZero, Certified Laravel Developer

# Getting Started

## Installation

Most likely you want to install maid as a global command, this can be done with Composer using the following command:

```shell
composer global require ghostzero/maid
```

To upgrade maid, simply use the following command:

```shell
composer global update ghostzero/maid
```

## Logging In

After installing the maid-cli, you need to authorize using your user account credentials.

```shell
maid login
```

> Note: To prevent the command from launching a web browser, use **maid login --console-only**. To authorize without a web browser and non-interactively, create a `credentials.json` file within the maid-cli config directory.

# Quickstart

## Create a Manifest

First we need to create a new `maid.yml`, the maid-cli tries to recognize components from the Laravel ecosystem and define them in your manifest file:

```shell
maid init
```

## Deploy your Application

```shell
maid deploy
```

# Commands

## Database

### Creating a Database

```shell
maid database maid-db
```

### Create a Database User

If you need another user for your database, create one by executing the following command:

```shell
maid database:user maid-db 
```

### Delete a Database User

If you don't need the database user anymore, you can delete it with the following command:

```shell
maid database:user:delete maid-db user-2
```

### Delete a Database

To delete a database instance

```shell
maid database:delete maid-db
```

## Cache

### Creating a Cache

```shell
maid cache maid-cache
```

### Delete a Cache

```shell
maid cache:delete maid-cache
```

## DNS Management

### Creating DNS Zones

The following commands are used to create DNS zones:

```shell
maid zone example.com
```

### Manage DNS Records

> The record command functions as an "UPSERT" operation. If an existing record exists with the given type and name, its value will be updated to the given value. If no record exists with the given type or name, the record will be created.

```shell
maid record example.com CNAME foo another-example.com
```

### Delete DNS Records

```shell
maid record:delete example.com A www
```

### Delete DNS Zones

```shell
maid zone:delete example.com
```

![](https://cdn.maid.sh/ghostzero/maid-banner-v3.png)

<p align="center">
  <a href="https://packagist.org/packages/ghostzero/maid"><img src="https://img.shields.io/packagist/dt/ghostzero/maid" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/ghostzero/maid"><img src="https://img.shields.io/packagist/v/ghostzero/maid" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/ghostzero/maid"><img src="https://img.shields.io/packagist/l/ghostzero/maid" alt="License"></a>
  <a href="https://ghostzero.dev/discord"><img src="https://discordapp.com/api/guilds/590942233126240261/embed.png?style=shield" alt="Discord"></a>
</p>

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

First we need to create a `maid.yml` within the root directory of your project, this is done interactively by the following command:

```shell
maid init
```

> During the initialization process it tries to recognize frequently used Laravel ecosystem components from your project and also define them in your manifest file.

## Deploy your Application

After initializing your project you can start to deploy your first version:

```shell
maid deploy
```

![](https://cdn.maid.sh/ghostzero/maid-cli-usage-v1.gif)

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
maid domain example.com
```

> After creating the DNS zone, the domain must be changed to the nameservers `ns1.bitinflow.com` and `ns2.bitinflow.com`. If you want to continue to use your own DNS server, then you must add all DNS records from `record:list` (which could change over time).

### List DNS Zones

The following commands are used to list DNS zones:

```shell
maid domain:list
```

The following is an example of a project where the `positive-mite-zem35d.maid.build` domain has been assigned:

```plain
+---------------------------------+-------------------+-------------------+-------------+
| name                            | primary_ns        | secondary_ns      | verified_at |
+---------------------------------+-------------------+-------------------+-------------+
| positive-mite-zem35d.maid.build | ns1.bitinflow.com | ns2.bitinflow.com | 1651950973  |
+---------------------------------+-------------------+-------------------+-------------+
```

### Create DNS Records

> The record command functions as an "UPSERT" operation. If an existing record exists with the given type and name, its value will be updated to the given value. If no record exists with the given type or name, the record will be created.

```shell
maid record example.com CNAME foo another-example.com
```

> Subdomains starting with `*.ingress` are currently reserved for the Ingress Controller and cannot be created, edited or deleted.

### List DNS Records

```shell
maid record:list example.com
```

The following example shows a listing of domain records of `positive-mite-zem35d.maid.build`:

```plain
+-------------------------------------------+-------+-------------------------------------------------------------------------------+-------+
| name                                      | type  | content                                                                       | ttl   |
+-------------------------------------------+-------+-------------------------------------------------------------------------------+-------+
| positive-mite-zem35d.maid.build           | SOA   | ns1.bitinflow.com hostmaster.bitinflow.com 2022050726 28800 7200 604800 86400 | 86400 |
| positive-mite-zem35d.maid.build           | NS    | ns1.bitinflow.com                                                             | 300   |
| positive-mite-zem35d.maid.build           | NS    | ns2.bitinflow.com                                                             | 300   |
| ingress.positive-mite-zem35d.maid.build   | A     | 192.248.183.183                                                               | 60    |
| *.ingress.positive-mite-zem35d.maid.build | CNAME | ingress.positive-mite-zem35d.maid.build                                       | 60    |
| foo.positive-mite-zem35d.maid.build       | TXT   | "another-example.com"                                                         | 300   |
+-------------------------------------------+-------+-------------------------------------------------------------------------------+-------+
```

### Delete DNS Records

```shell
maid record:delete positive-mite-zem35d.maid.build A www
```

### Delete DNS Zones

```shell
maid domain:delete example.com
```

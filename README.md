![](https://cdn.maid.sh/ghostzero/maid-banner-v3.png)

<p align="center">
  <a href="https://packagist.org/packages/maidtools/maid"><img src="https://img.shields.io/packagist/dt/maidtools/maid" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/maidtools/maid"><img src="https://img.shields.io/packagist/v/maidtools/maid" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/maidtools/maid"><img src="https://img.shields.io/packagist/l/maidtools/maid" alt="License"></a>
  <a href="https://ghostzero.dev/discord"><img src="https://discordapp.com/api/guilds/590942233126240261/embed.png?style=shield" alt="Discord"></a>
</p>

# Make Development Faster

Maid is a **containerized deployment platform** for Laravel, powered by Kubernetes. From simple to complex apps, launch your Laravel application on [maid.sh](https://maid.sh), enjoy the simplicity and focus on innovating and shipping value.

> Created by GhostZero, Certified Laravel Developer

# Quick Start

## Installation

Most likely you want to install maid as a global command, this can be done with Composer using the following command:

```shell
composer global require maid/maid
```

To upgrade maid, simply use the following command:

```shell
composer global update maid/maid
```

## Logging In

After installing the maid-cli, you need to authorize using your user account credentials.

```shell
maid login
```

> Note: To prevent the command from launching a web browser, use **maid login --console-only**. To authorize without a web browser and non-interactively, create a `credentials.json` file within the maid-cli config directory.

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

# Official Documentation

You can view our official documentation [here](https://docs.maid.build/).

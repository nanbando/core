# Nanbando

[![Documentation Status](https://readthedocs.org/projects/nanbando/badge/?version=latest)](http://nanbando.readthedocs.io/en/latest/?badge=latest)

<img src="https://raw.githubusercontent.com/nanbando/core/master/docs/img/logo.png" alt="Nanbando" style="max-width:100%;" height="300px">

Nanbando is a simple application to automate website backups. It provides an elegant way to extend and configure the
backup parts. Nanbando has built-in support for various storage's and provides easy to use sync and fetch operations. It
was built with modularity, extensibility and simplicity in mind.

## Status

This repository **will become** version 1.0 of Nanbando. It is **under heavy development** and currently it's APIs and
code are not yet stable (pre 1.0).

## Installation

To install the application simply download the executable and move it to the global bin folder.

```bash
wget http://nanbando.github.io/core/nanbando.phar
wget http://nanbando.github.io/core/nanbando.phar.pubkey
chmod +x nanbando.phar
mv nanbando.phar /usr/local/bin/nanbando
mv nanbando.phar.pubkey /usr/local/bin/nanbando.pubkey
nanbando check
```

After first installation you can update the application with a built-in command.

```bash
nanbando self-update
```

The executable is signed with a OpenSSL private key.

## Requirements

* php ^5.6 || ^7.0
* ext-xml
* ext-curl
* ext-mbstring
* ext-zip

## Documentation

See the official documentation on [nanbando.readthedocs.io](http://nanbando.readthedocs.io/en/latest/).

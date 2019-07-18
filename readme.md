# Application Passwords for WordPress

[![Build Status](https://travis-ci.org/georgestephanis/application-passwords.svg?branch=master)](https://travis-ci.org/georgestephanis/application-passwords)

Creates unique passwords for applications to authenticate users without revealing their main passwords.


## Install

Install from the official [WordPress.org plugin repository](https://wordpress.org/plugins/application-passwords/) by searching for "Application Passwords" under "Plugins" → "Add New" in your WordPress dashboard.

### Install Using Composer

	composer require georgestephanis/application-passwords


## Documentation

See the [plugin description on WordPress.org](https://wordpress.org/plugins/application-passwords/).

### Development Environment

Included is a local devolopment environment using [Docker](https://www.docker.com) with an optional [Vagrant](https://www.vagrantup.com) wrapper for network isolation and ZeroConf for automatic [application-passwords.local](http://application-passwords.local) discovery. Run `docker-compose up -d` to start the Docker containers on your host machine or `vagrant up` to start it in a [VirtualBox](https://www.virtualbox.org) environment.


## Contribute

All contributions are welcome!


## Credits

Created by [George Stephanis](https://github.com/georgestephanis). View [all contributors](https://github.com/georgestephanis/application-passwords/graphs/contributors).

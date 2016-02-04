=== Application Passwords ===
Contributors: georgestephanis, valendesigns, kraftbj
Tags: application-passwords
Requires at least: 4.4
Tested up to: 4.5
Stable tag: trunk

A feature plugin for core to provide Application Passwords

== Description ==

Application Passwords allows you to use the WordPress REST API and legacy XML-RPC API with generated per-application passwords, rather than building either an oAuth flow or passing your normal account password along with the request.

Generated application passwords only work for API requests, such as the REST API or XML-RPC API, and can not be used to log into the traditional wp-admin interface.

This is a spinoff of the main Two-Factor Authentication plugin, found at https://github.com/georgestephanis/two-factor/, and active development happens on GitHub: https://github.com/georgestephanis/application-passwords/

== Screenshots ==

1. In your user profile screen, by default it will just be a field to create a new Application Password.
2. After at least one Application Password for you account exists, you'll see a table displaying them, allowing you to view usage and revoke them as desired.

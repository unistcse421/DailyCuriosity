# DailyCuriosity

DailyCuriosity is a forum-like web service with features explicitly designed to help people debate online, such as revision management and paragraphwise hyperlinks.

This is a UNIST CSE421 term project worked by 20141396 Huisu Yun (also known as Miyu Tokoromi) during the spring semester of 2016.

## Writing a post

Brackets, brackets everywhere!

* Hyperlink syntax: use \[x:y:z\] to point to the paragraph #y of post #x (revision #z).
* To express your support of a specific position, use \[@position\].
* Hashtag syntax: \[#keyword\]. Brackets provide better interoperability with fusional languages.

Two or more newlines introduce a paragraph break. A single newline will be ignored.

## Known limitations

Since the project is not yet finished, there are a number of limitations.

* All posts are stored with a user ID of 0; i.e. no login. We decided to work on the user access control feature later with explicit considerations regarding social login platforms.
* Vulnerable to SQL injection attacks. Protecting against most of them is a trivial task, but we are intentionally leaving the vulnerabilities to debug the service on our smartphones. Perhaps we should have made a separate web console interface, but who would ever bother to perform an attack on a service which does not even have a login feature?

## Install

Should work on any modern (or nearly-modern) configuration of PHP and MySQL with a decent web server. More specifically, the code requires PHP 5.2 or later.

At the root directory (which is where ```index.html``` and ```dc.php```, among other things, reside), create a file named ```db.php``` with the following contents:

```php
<?php

$db_host = "host";
$db_username = "username";
$db_password = "password";
$db_database = "database";

?>
```

Of course, you should make necessary modifications to properly reflect the database credentials.

## Dependencies

Based on jQuery and Bootstrap.

We also made use of Bower, but you don't need to since we do not (and are not going to) have ```bower_components``` in our ```.gitignore``` file.

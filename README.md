synode
======

Want to be Realtime in Existing Web Frameworks ? No Problem, Synode is here !

Don't you ever dream of being able to use realtime data update with Symfony, Zend or Rails ? Synode is an answer.


Get Started
===========

Once you've installed nodejs, and a Web Server with PHP, put this projet in your web root.

In a first terminal, go to the synode-bridge directory then ignite the realtime bridge :
$ node synode-bridge.js

In your Web Browser, point to http://localhost/[YOUR_PATH_TO]/synode-server/
Then you sould see a connection in your first terminal, open the console (F12)

Start a second terminal, and go to the synode-server directory
$ php push_sample.php

The message trigged by php sould be viewed in the first terminal AND you web browser's console !

Now you can build realtime webapps with ease !

;)
You're welcome.


Bridge : Written in Javascript, with NodeJS and Socket.IO
ServerSample : Written in PHP using Elephant.IO (for the moment !), but should (and will !) be adapted in Ruby (with dkastner or markjeee).

nRelay
======

Want to be Realtime in Existing Web Frameworks ? No Problem, nRelay is here !

Don't you ever dream of being able to use realtime data update with PHP Framworks ? nRelay is a start of solution.


Notes :
=======

After some researches, it seems that this project look like a free and open source Heroku Pusher (https://devcenter.heroku.com/articles/pusher).

New :
=====

- Support SSL Encryption and I use it in production environnement with a fallback to traditionnal mode.
- Socket.IO 1.0.x (Optimized Elephant.IO - Pulling Request in progress)

Get Started
===========

Once you've installed nodejs, and a Web Server with PHP, put this projet in your web root.

In a first terminal, go to the nRelay-bridge directory then ignite the realtime bridge :
$ node nRelay-bridge.js

In your Web Browser, point to http://localhost/[YOUR_PATH_TO]/nRelay-server/
Then you sould see a connection in your first terminal, open the console (F12)

Start a second terminal, and go to the nRelay-server directory
$ php push_sample.php

The message trigged by php sould be viewed in the first terminal AND you web browser's console !

Now you can build realtime webapps with ease !

;)
You're welcome.

In this app
===========

Bridge : Written in Javascript, with NodeJS and Socket.IO
ServerSample : Written in PHP using Elephant.IO, 

I'm looking for help to adapt this in Ruby (with dkastner or markjeee).
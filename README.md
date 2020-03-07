<img align="right" width="60" src="http://www.pearscom.com/images/newfav.png">
<h1>Pearscom</h1>

An independent and ad-free social media website that reduces the amount of data recorded and tracked per user in order to minimize data feuds and abuses. It may result in a different user experience since Pearscom might be unprecise or unable to give highly-customized suggestions and AI-powered feeds but this tradeoff is going to pay off when users can trust the service and their data is kept in safe.<br><br>
<b>Note:</b> this is a legacy project that is almost 4 years old and is not under development. Since then a lot of things has changed and might be deprecated but Pearscom is still working and functioning correctly.

## Features
Pearscom mostly offers the same features and services as other social media sites and some unique ones as well:
  - News feed to check the happenings around friends and nearby users
  - Own user profile that is highly customizable
  - Friend and user blocking system & friend suggestions
  - Upload videos & photos
  - Advanced article writing system with custom formatting
  - Group system (private, public)
  - Invite friends to the site who are not users yet
  - Notification and messaging system (not real-time yet, would be a nice todo)
  - Use geolocation extensively in suggestions and recommendations (not explicitly seen in other social platforms)
  - many more...

Unlike Facebook and other social media sites Pearscom does not require users to give valid and real-life information about themselves. The emphasis is on communication and connecting people and not on the validity of users.<br><br>
Also, when deleting a user profile, photo, article etc. it actually gets deleted from the database and server and Pearscom will not keep any redundant information about its users.<br><br>
One type of information that a potential user has to give is their geolocation. It is allowed in most browsers and is used by Pearscom to serve suggestions that are somehow connected to nearby users.<br><br>
Due to the lack of configurability I have on the server that I hire users also need to give their timezone at sign up that otherwise would be obvious.
Pearscom does not have an application in any stores but it can be downloaded as a PWA (Progessive Web App) for both mobile and desktop.

## Nice TODOs
The site is written is pure PHP & MySQL and JavaScript. Back then, it started out as a small project and only evolved to a somwhat larger one later so I didn't use any framework or library, that now I regret.
  - Integrate the service with a framework/library like React or Angular (may also consider using Django, Laravel or Node.js on server-side)
  - Implement messaging, notifications etc. in real-time (perhaps with Node.js and Socket.io)
  - Better integration with MySQL, more efficient queries etc.
  - More clever UI
  - More server side checks
  - Do the tasks marked with `TODO...` in the source code
  - Anything else you think would be great
 
## Play with the code
`git clone https://github.com/squancy/pearscom`<br>
In order to test Pearscom on your machine you will need a local server like XAMPP or LAMPP. The database scheme is located in `make_sql` as `schema.sql` which is a description of the database structure. It will create the necessary database and tables for Pearscom.<br>
In `php_includes` edit `conn.php` to be appropriate for your own local server. This file is used for connecting to the database.<br>
Use PHP version 7.1 for the best experience.<br>
You may also need to edit `.htaccess` and `php.ini` to configure some settings to your own local server. SEO friendly URLs, https, custom caching etc. are used on the live product so make sure that these are set on your machine as well.<br>
See the live product at <a href="https://www.pearscom.com/">pearscom.com</a>.<br>

## About the live product
You can check <a href="https://www.pearscom.com/">Pearscom</a>, play with its features and try out the site.<br>
  - It is on a shared-host web server (I will consider moving it to a VPS because that would be fun)
  - The server is running Linux x84_64
  - Uses Let's Encrypt for https
  - The server is located in Hungary

## Contribute
If you have any questions or suggestions drop me with an email at <a href="mailto:mark@pearscom.com">mark@pearscom.com</a>.

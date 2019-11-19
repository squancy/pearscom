# Pearscom
An independent and free social media website without ads that sacrifices the "better user experience" for recording the lowest possible amount of information about the users. Therefore users can focus on what truly matters for them instead of system-given suggestions and being able to make their own decisions.<br>
<b>Note:</b> this is a legacy project that is over 3 years old and is not under development. Since then a lot of things has changed and might be deprecated but Pearscom is still working and functioning currectly.

# Features
Pearscom mostly offers the same features and services as other social media sites and some unique ones as well:
  - News feed to check the happenings around friends and nearby users
  - Own user profile that is highly customizable
  - Friend and user blocking system & friend suggestions
  - Upload videos & photos
  - Advanced article writing system with custom formatting
  - Group system (closed, public)
  - Invite friends to the site who are not users yet
  - Notification and messaging system (not real-time yet, would be a nice todo)
  - many more ...

Pearscom does not require the user to give a valid real name so the user can be completely anonymous under a fantasy username.<br>
When deleting a user profile, photo, article etc. it actually gets deleted from the database and Pearscom will not keep any redundant information about its users.<br>
Also note that by not having lots of information about users does not necessarily mean that it will result in a bad user experience, only in a different one.<br>
The only additional information that is used in suggestions and queries (apart from that the user gave at sign up) is geolocation.<br>
Pearscom can also be downloaded as a PWA (Progessive Web App) for both mobile and computer.

# Nice TODOs:
The site is written is pure PHP & MySQL, HTML5, CSS3 and JS with some jQuery.
  - Integrate the service with a framework like React or Angular (may also consider using Django or Node.js on server-side)
  - Restructure code for better readibility and reusability
  - Implement messaging, notifications etc. in real-time (perhaps with Node.js)
  - Better integration with MySQL, more efficient queries etc.
  - Anything else you think would be great
  - More clever UI
 
# Play with the code
`git clone https://github.com/squancy/pearscom`
In order to test Pearscom on your machine you will need a local server like XAMPP or LAMPP. To access MySQL you can run the `.php` files inside `make_sql` that will automatically create the desired tables and databases. In `php_includes` edit `conn.php` to be appropriate for your own local server. Use PHP version 7.1 for the best experience.<br>
See the live product at <a href="https://www.pearscom.com/">pearscom.com</a>.<br>
<b>Note:</b> due to the lots of images Pearscom costs about 1.3GB.

# About the live product
You can check <a href="https://www.pearscom.com/">Pearscom</a>, play with its features and try out the site.<br>
  - It is on a shared-host web server (I will consider moving it to a VPS because that would be fun)
  - The server is running Linux x84_64
  - Uses Let's Encrypt for https
  - The server is located in Hungary

# Contribute
If you have any questions or suggestions drop me with an email at <a href="mailto:mark@pearscom.com">mark@pearscom.com</a>.


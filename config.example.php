<?php
// Copy to config.php and enter your hosting.com MySQL credentials.
// The existing book-site database may be used: MyNotes tables are prefixed mn_.
define('MN_DB_HOST','localhost');
define('MN_DB_NAME','');
define('MN_DB_USER','');
define('MN_DB_PASS','');
define('MN_MAIL_FROM',''); // verified sender address
// Set to a long, random string unique to MyNotes. Do not reuse database passwords.
define('MN_SECRET','REPLACE_WITH_RANDOM_64_CHARACTER_STRING');

# MEXP-Resource-Space
WordPress Media Explorer ResourceSpace extension

## Setup

You must define the following settings. It is reccommended that this is added to your `wp-config.php` file.

````php
define( 'PJ_RESOURCE_SPACE_DOMAIN', 'http://HumanMade:errAnOadDeshCeyd@pix.yelsterdigital.com' );
define( 'PJ_RESOURCE_SPACE_KEY',    'T2B7dGlIdWFiaTY3NXcmImRgcTAzIWJhP3MicTRnLWA3IDIxZiBzJGM1' );
````

Additionally, if your resourcespace install is behind basic auth, add the following.

````php
define( 'PJ_RESOURCE_SPACE_AUTHL',  'HumanMade' );
define( 'PJ_RESOURCE_SPACE_AUTHP',  'errAnOadDeshCeyd' );
````

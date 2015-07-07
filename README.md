# MEXP-Resource-Space
WordPress Media Explorer ResourceSpace extension

## Setup

You must define the following settings. It is reccommended that this is added to your `wp-config.php` file.

````php
define( 'PJ_RESOURCE_SPACE_DOMAIN', '' );
define( 'PJ_RESOURCE_SPACE_KEY',    '' );
````

Additionally, if your resourcespace install is behind basic auth, add the following.

````php
define( 'PJ_RESOURCE_SPACE_AUTHL',  '' );
define( 'PJ_RESOURCE_SPACE_AUTHP',  '' );
````

Further (optional) settings

````php
// Number of results to fetch for each page. Default is 20.
define( 'PJ_RESOURCE_SPACE_RESULTS_PER_PAGE', 20 );
````

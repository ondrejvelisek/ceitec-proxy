# simplesamlphp-authgoogleoauth2
An authentication module for simplesamlphp which authenticates against Google OAuth2

I created this largely because of Google's OpenID 2.0 deprecation. I used OpenID for authentication to RightScale,
among other things.

# Installation

Git clone this repo into your modules/ directory, then be sure to install the dependencies with composer

```
cd /path/to/simplesaml/modules/
git clone https://github.com/rgeyer/simplesamlphp-authgoogleoauth2.git authgoogleoauth2 
cd authgoogleoauth2
# Install composer, skip this step if you've already got it
curl -s https://getcomposer.org/installer | php
# Install the authgoogleoauth2 module dependencies, you can just do `composer install` if you've already
# installed composer globally
php composer.phar install
```

# Configuration

You'll first need to go to the Google developer console and create an application which your users will be asked to
"trust" when they use this authentication mechanism. This is the same as the typical OAuth process when you enable
an application to access your google account information. In this case, the only thing this application accesses about
your account are your user details.

https://console.developers.google.com/project

Then, a simple entry in your simplesamlphp config is all you need.

## config/authsources.php
```
  'authgoogleoauth2' => array(
      'authgoogleoauth2:authgoogleoauth2',
      'client_id' => 'yourclientid.apps.googleusercontent.com',
      'client_secret' => 'Your Secret',
      'developer_key' => 'Your API Key',
    ),
```
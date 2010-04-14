=== Plugin Name ===
Contributors: borkweb, misterbisson
Tags: Auth, authentication, central authentication service, wpcas, wpcas-server, integration, phpCAS, CAS
Requires at least: 2.8
Tested up to: 2.9.2
Stable tag: trunk

Turns WordPress or WordPress MU into a CAS single sign-on authenticator.

== Description ==

This plugin reserves a collection of URIs that create, validate, and destroy CAS tickets.

* /cas/login :: If user is not authenticated he/she is redirected to the login page.  Otherwise the user is redirected to the service specified as a GET variable in the URL - or if service is not provided, the user is redirected to the WordPress instance's home.

* /cas/logout :: The user's session is destroyed, user is logged out of the WordPress instance, and redirected to $_GET['service'] (or the blog home if service isn't provided) 

* /cas/proxyValidate and /cas/validate :: The CAS ticket must be passed as a GET parameter in the URL when calling /cas/validate.  The ticket is validated and XML is output with either cas:authenticationSuccess or cas:authenticationFailure

== Installation ==

1. Upload `wpcas-server` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= What version of CAS does this plugin replicate? =

wpCAS Server currently replicates partial functionality of a CAS 2.0 server.

= You just said "partial"...what doesn't this support? =

Currently wpCAS Server has not implemented the Proxy ticketing found in the <a href="http://www.jasig.org/cas/cas2-architecture">CAS 2.0 architecture</a>.

== Hooks & Filters ==

= wpcas_server_login Hook =

This hook allows for the insertion of code after login has successfully completed and just before the ticket creation.  One common use of this hook is to fill out the $_SESSION variable with site/user specific information.

= wpcas_server_auth_value Filter =

This filter (executed in a successful ticket validation in /cas/validate) is used to override the user identifier returned in the cas:authenticationSuccess XML response.  By default, the value returned is the $user_ID of the authenticated user.  Using this filter, that value can be altered to whatever suits your implementation.

== Changelog ==

= 1.0 =
* Initial release

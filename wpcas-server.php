<?php
/*
Plugin Name: wpCAS Server
Version: 1.0
Plugin URI: http://borkweb.com/projects/wpcas-server
Description: Turns WordPress into a <a href="http://en.wikipedia.org/wiki/Central_Authentication_Service">CAS</a> single sign-on authenticator.  Based on the original, partially completed code by Casey Bisson (http://maisonbisson.com).
Author: Matthew Batchelder
Author URI: http://borkweb.com/
*/

/* 
 Copyright (C) 2010 Matthew Batchelder

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	 02111-1307	 USA 
*/
class wpCAS_server {
	/**
	 * creates an auth ticket number
	 *
	 * @param $type \b type of ticket (ST, TGC, PGT, PGTIOU, PT)
	 */
	public function create_ticket($user_id, $type = 'ST') {
		return $type . '-'. urlencode( str_rot13( wp_generate_auth_cookie( $user_id, time() + 15, 'auth' )));
	}//end create_ticket

	/**
	 * fail the cas request
	 */
	public function fail() {
		die('fail');
	}//end fail

	/**
	 * return script path
	 */
	public function get_path() {
		return parse_url( 'https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'], PHP_URL_PATH );		
	}//end get_path

	/**
	 * initialize the cas request and redirect to the appropriate cas function
	 */
	public function init() {
		$path = self::get_path();
		switch( substr($path, strpos($path, '/cas/') ) ) {
			case '/cas/login/':
			case '/cas/login' : self::login(); break;
			case '/cas/logout/':
			case '/cas/logout' : self::logout(); break;
			case '/cas/validate/':
			case '/cas/validate' : self::validate(); break;
			case '/cas/proxyValidate/':
			case '/cas/proxyValidate' :
			case '/cas/serviceValidate/':
			case '/cas/serviceValidate' : self::serviceValidate(); break;
			default : self::fail(); break;
		}//end switch
	}//end init

	/**
	 * authenticate, create CAS ticket, and pass back to service
	 */
	public function login() {
		global $userdata, $user_ID;

		// renew requested; perform a logout, then send user back to this
		// same page (minus renew=true) and let normal processing continue. 
		// this prevents us from getting stuck in a "renew" loop.
		if( isset($_GET['renew']) && 'true' === $_GET['renew'] ) {
			wp_logout();

			$proto = is_ssl() ? 'https://' : 'http://';
			$url = $proto . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$url = remove_query_arg( 'renew', $url );

			die( wp_redirect( $url ) );
		}

		if( !is_user_logged_in() ) {
			die( auth_redirect() );
		}//end if

		get_currentuserinfo();

		do_action('wpcas_server_login');

		$ticket = 'ticket=' . self::create_ticket($user_ID);
		if( isset( $_GET['service'] ) && $service = sanitize_url( $_GET['service'] )) {
			die( wp_redirect( $service . (strpos( $service, '?' ) !== false ? '&' : '?'). $ticket ) );
		}//end if

		die( wp_redirect( get_option( 'home' )));
	}//end login

	/**
	 * destroy session and redirect
	 */
	public function logout() {
		self::session_start();

		session_unset();
		session_destroy();

		wp_logout();

		die( wp_redirect( $_GET['service'] ? $_GET['service'] : get_option('home') ) );
	}//end logout

	/**
	 * initialize the session
	 */
	public function session_start() {
		session_start();
	}//end session_start

	/**
	 * validate a given ticket
	 * @return string user id
	 */
	public function _validate() {
		self::session_start();

		$path = self::get_path();

		$ticket = substr( $_GET['ticket'], 3 );
		$decrypted_ticket = str_rot13($ticket);

		if( isset( $_GET['ticket'] ) && $user_id = wp_validate_auth_cookie( $decrypted_ticket, 'auth' ) ) {
			return $user_id;
		}
	}//end _validate

	/**
	 * CAS 2.0 serviceValidate.
	 */
	public function serviceValidate() {
		$auth_value = false;

		header( 'Content-Type: text/xml' );

		$response = '<?xml version="1.0"?'.'>'."\n";
		$response .= '<cas:serviceResponse xmlns:cas="'.get_bloginfo('url').'/cas">'."\n";

		if( $user_id = self::_validate() ) {
			$auth_value = apply_filters('wpcas_server_auth_value', $user_id);

			$response .= '  <cas:authenticationSuccess>'."\n";
			$response .= '    <cas:user>'.$auth_value.'</cas:user>'."\n";
			$response .= '  </cas:authenticationSuccess>'."\n";
		} else {
			$response .= '  <cas:authenticationFailure code="">no</cas:authenticationFailure>'."\n";
		}//end else
		$response .= '</cas:serviceResponse>';

		$response = apply_filters( 'wpcas_server_auth_response', $response, $auth_value );
 
		die( $response );
	}//end serviceValidate

	/**
	 * CAS 1.0 validate.
	 */
	public function validate() {
		if( $user_id = self::_validate() ) {
			$auth_value = apply_filters('wpcas_server_auth_value', $user_id);
			$response = "yes\n{$auth_value}\n";
		} else {
			$response = "no\n\n";
		}

		die( $response );
	}//end validate
}//end class wpCAS_server

if ( strstr( $_SERVER['REQUEST_URI'], '/cas/' ) && !is_admin() ) {
	add_action( 'init', array( 'wpCAS_server', 'init' ));
}//end if

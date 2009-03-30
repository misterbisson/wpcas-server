<?php
/*
Plugin Name: wpCAS Server
Version: 0.01
Plugin URI: http://maisonbisson.com/projects/wordpress-cas-server
Description: Turns WordPress or WordPress MU into a <a href="http://en.wikipedia.org/wiki/Central_Authentication_Service">CAS</a> single sign-on authenticator.
Author: Casey Bisson
Author URI: http://maisonbisson.com/
*/

/* 
 Copyright (C) 2009 Casey Bisson

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

	function wpCAS_server(){
		if ( preg_match( '/^\/cas\//', $_SERVER['REQUEST_URI'] ) && !is_admin() )
			add_action( 'init', array( &$this, 'init' ));
	}

	function init(){
		$path = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );		
		switch( $path ){
			case '/cas/login' :
				$this->login();
			case '/cas/logout' :
				$this->logout();
			case '/cas/validate' :
				$this->validate();
			default :
				$this->fail();
		}
	}

	function login(){
		if( !is_user_logged_in() )
			die( auth_redirect() );

		$ticket = 'ticket=ST-'. urlencode( str_rot13( wp_generate_auth_cookie( 1, time() + 15, 'auth' )));
		if( isset( $_GET['service'] ) && $service = sanitize_url( $_GET['service'] ))
			die( wp_redirect( strpos( $service . '?' ) ? $service .'&'. $ticket : $service .'?'. $ticket ));

		die( wp_redirect( get_option( 'home' )));
	}

	function logout(){
		wp_logout();
		die( wp_redirect( get_option( 'home' )));
	}

	function validate(){
/*
/validate
	?service
	&ticket
*/

		if( isset( $_GET['ticket'] ) && wp_validate_auth_cookie( str_rot13( substr( $_GET['ticket'], 3 )), 'auth' )){
			echo "yes\rusername\r";
		}else{
			echo "no\r\r";
		}

		die();
	}

	function fail(){
		echo 'fail';
		die();
	}


}
$wpcas_server = & new wpCAS_server;
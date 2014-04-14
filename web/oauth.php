<?php
/*
	Copyright 2014 Alex Zaddach. (mrzmanwiki@gmail.com)

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

*/

// OAuth stuff adapted from Brad Jorsch's PD Hello World app

class OAuth {
	
	private $gTokenKey, $gTokenSecret, $gConsumerKey, $gConsumerSecret;
	private $gUserAgent, $mwOAuthUrl;

	private function __construct(&$error, $inifile) {
		// Setup the session cookie
		session_name( 'WPPopularPages' );
		$params = session_get_cookie_params();
		session_set_cookie_params(
			$params['lifetime'],
			dirname( $_SERVER['SCRIPT_NAME'] )
		);

		// Read the ini file
		$ini = parse_ini_file( $inifile );
		if ( $ini === false ) {
			$error = 'The ini file could not be read';
			return;
		}
		if ( !isset( $ini['agent'] ) ||
			!isset( $ini['consumerKey'] ) ||
			!isset( $ini['consumerSecret'] ) ||
			!isset( $ini['mwOAuthUrl'] )
		) {
			$error = 'Required configuration directives not found in ini file';
			return;
		}
		$this->gUserAgent = $ini['agent'];
		$this->gConsumerKey = $ini['consumerKey'];
		$this->gConsumerSecret = $ini['consumerSecret'];
		$this->mwOAuthUrl = $ini['mwOAuthUrl'];
		
		$this->gTokenKey = $this->gTokenSecret = '';
		
		// Load the user token (request or access) from the session
		session_start();
		if ( isset( $_SESSION['tokenKey'] ) ) {
			$this->gTokenKey = $_SESSION['tokenKey'];
			$this->gTokenSecret = $_SESSION['tokenSecret'];
		}
		session_write_close();
	}
	
	public static function startOAuth($inifile) {
		$error = '';
		$o = new OAuth($error, $inifile);
		return array($o, $error);
	}

	public function checkAccessToken() {
		// Fetch the access token if this is the callback from requesting authorization
		if ( isset( $_GET['oauth_verifier'] ) && $_GET['oauth_verifier'] ) {
			$error = $this->fetchAccessToken();
			return $error;
		} else {
			return '';
		}	
	}
	
	private function fetchAccessToken() {
		$url = $this->mwOAuthUrl . '/token';
		$url .= strpos( $url, '?' ) ? '&' : '?';
		$url .= http_build_query( array(
			'format' => 'json',
			'oauth_verifier' => $_GET['oauth_verifier'],
			// OAuth information
			'oauth_consumer_key' => $this->gConsumerKey,
			'oauth_token' => $this->gTokenKey,
			'oauth_version' => '1.0',
			'oauth_nonce' => md5( microtime() . mt_rand() ),
			'oauth_timestamp' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
		) );
		$signature = $this->sign_request( 'GET', $url );
		$url .= "&oauth_signature=" . urlencode( $signature );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_USERAGENT, $this->gUserAgent );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$data = curl_exec( $ch );
		if ( !$data ) { 
			return 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
		}
		curl_close( $ch );
		$token = json_decode( $data );
		if ( is_object( $token ) && isset( $token->error ) ) {
			return 'Error retrieving token: ' . htmlspecialchars( $token->error );
		}
		if ( !is_object( $token ) || !isset( $token->key ) || !isset( $token->secret ) ) {
			return 'Invalid response from token request';
		}

		// Save the access token
		session_start();
		$_SESSION['tokenKey'] = $this->gTokenKey = $token->key;
		$_SESSION['tokenSecret'] = $this->gTokenSecret = $token->secret;
		$_SESSION['authorized'] = 'true';
		session_write_close();
		return '';
	}
	
	public function isAuthorized() {
		return isset($_SESSION['authorized']) && $_SESSION['authorized'] == 'true';
	}
	
	public function getURL() {
		$this->gTokenSecret = '';
		$url = $this->mwOAuthUrl . '/initiate';
		$url .= strpos( $url, '?' ) ? '&' : '?';
		$url .= http_build_query( array(
			'format' => 'json',
			// OAuth information
			'oauth_callback' => 'oob', // Must be "oob" for MWOAuth
			'oauth_consumer_key' => $this->gConsumerKey,
			'oauth_version' => '1.0',
			'oauth_nonce' => md5( microtime() . mt_rand() ),
			'oauth_timestamp' => time(),
			// We're using secret key signatures here.
			'oauth_signature_method' => 'HMAC-SHA1',
		) );
		$signature = $this->sign_request( 'GET', $url );
		$url .= "&oauth_signature=" . urlencode( $signature );
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_USERAGENT, $this->gUserAgent );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$data = curl_exec( $ch );
		if ( !$data ) {
			return array('error', 'Curl error: ' . htmlspecialchars( curl_error( $ch ) ));
		}
		curl_close( $ch );
		$token = json_decode( $data );
		if ( is_object( $token ) && isset( $token->error ) ) {
			return array('error', 'Error retrieving token: ' . htmlspecialchars( $token->error ));
		}
		if ( !is_object( $token ) || !isset( $token->key ) || !isset( $token->secret ) ) {
			return array('error', 'Invalid response from token request');
		}
		// Now we have the request token, we need to save it for later.
		session_start();
		$_SESSION['tokenKey'] = $token->key;
		$_SESSION['tokenSecret'] = $token->secret;
		$_SESSION['authorized'] = 'false';
		session_write_close();

		// Then we send the user off to authorize
		$url = $this->mwOAuthUrl . '/authorize';
		$url .= strpos( $url, '?' ) ? '&' : '?';
		$url .= http_build_query( array(
			'oauth_token' => $token->key,
			'oauth_consumer_key' => $this->gConsumerKey,
		) );
		return array('success', str_replace('&', '&amp;', $url));
	}
	
	private function sign_request( $method, $url, $params = array() ) {
		$parts = parse_url( $url );

		// We need to normalize the endpoint URL
		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'http';
		$host = isset( $parts['host'] ) ? $parts['host'] : '';
		$port = isset( $parts['port'] ) ? $parts['port'] : ( $scheme == 'https' ? '443' : '80' );
		$path = isset( $parts['path'] ) ? $parts['path'] : '';
		if ( ( $scheme == 'https' && $port != '443' ) ||
			( $scheme == 'http' && $port != '80' ) 
		) {
			// Only include the port if it's not the default
			$host = "$host:$port";
		}

		// Also the parameters
		$pairs = array();
		parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query );
		$query += $params;
		unset( $query['oauth_signature'] );
		if ( $query ) {
			$query = array_combine(
				// rawurlencode follows RFC 3986 since PHP 5.3
				array_map( 'rawurlencode', array_keys( $query ) ),
				array_map( 'rawurlencode', array_values( $query ) )
			);
			ksort( $query, SORT_STRING );
			foreach ( $query as $k => $v ) {
				$pairs[] = "$k=$v";
			}
		}

		$toSign = rawurlencode( strtoupper( $method ) ) . '&' .
			rawurlencode( "$scheme://$host$path" ) . '&' .
			rawurlencode( join( '&', $pairs ) );
		$key = rawurlencode( $this->gConsumerSecret ) . '&' . rawurlencode( $this->gTokenSecret );
		return base64_encode( hash_hmac( 'sha1', $toSign, $key, true ) );
	}

	public function getID() {
		$url = $this->mwOAuthUrl . '/identify';
		$headerArr = array(
			'oauth_consumer_key' => $this->gConsumerKey,
			'oauth_token' => $this->gTokenKey,
			'oauth_version' => '1.0',
			'oauth_nonce' => md5( microtime() . mt_rand() ),
			'oauth_timestamp' => time(),
			'oauth_signature_method' => 'HMAC-SHA1',
		);
		$signature = $this->sign_request( 'GET', $url, $headerArr );
		$headerArr['oauth_signature'] = $signature;

		$header = array();
		foreach ( $headerArr as $k => $v ) {
			$header[] = rawurlencode( $k ) . '="' . rawurlencode( $v ) . '"';
		}
		$header = 'Authorization: OAuth ' . join( ', ', $header );

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $header ) );
		//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_USERAGENT, $this->gUserAgent );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$data = curl_exec( $ch );
		if ( !$data ) {
			header( "HTTP/1.1 500 Internal Server Error" );
			echo 'Curl error: ' . htmlspecialchars( curl_error( $ch ) );
			templatebottom();
			exit(0);
		}
		$err = json_decode( $data );
		if ( is_object( $err ) && isset( $err->error ) && $err->error === 'mwoauthdatastore-access-token-not-found' ) {
			// We're not authorized!
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
			session_destroy();
			return array('error', 'You haven\'t authorized this application yet! Please reload the page to do that.');
		}

		// There are three fields in the response
		$fields = explode( '.', $data );
		if ( count( $fields ) !== 3 ) {
			return array('error', 'Invalid identify response: ' . htmlspecialchars( $data ));
		}

		// Validate the header. MWOAuth always returns alg "HS256".
		$header = base64_decode( strtr( $fields[0], '-_', '+/' ), true );
		if ( $header !== false ) {
			$header = json_decode( $header );
		}
		if ( !is_object( $header ) || $header->typ !== 'JWT' || $header->alg !== 'HS256' ) {
			return array('error', 'Invalid header in identify response: ' . htmlspecialchars( $data ));
		}

		// Verify the signature
		$sig = base64_decode( strtr( $fields[2], '-_', '+/' ), true );
		$check = hash_hmac( 'sha256', $fields[0] . '.' . $fields[1], $this->gConsumerSecret, true );
		if ( $sig !== $check ) {
			return array('error', 'JWT signature validation failed: ' . htmlspecialchars( $data ));
		}

		// Decode the payload
		$payload = base64_decode( strtr( $fields[1], '-_', '+/' ), true );
		if ( $payload !== false ) {
			$payload = json_decode( $payload );
		}
		if ( !is_object( $payload ) ) {
			return array('error', 'Invalid payload in identify response: ' . htmlspecialchars( $data ));
		}
		$data = array(
			'username' => $payload->username,
			'editcount' => $payload->editcount,
			'blocked' => $payload->blocked
		);
		return array('success', $data);
	}	
}

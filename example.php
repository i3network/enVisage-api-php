<?php
/*
	            _    ___
	  ___  ____| |  / (_)________ _____ ____
	 / _ \/ __ \ | / / / ___/ __ `/ __ `/ _ \
	/  __/ / / / |/ / (__  ) /_/ / /_/ /  __/
	\___/_/ /_/|___/_/____/\__,_/\__, /\___/
	                            /____/

	ENVISAGE - VERSION 3.0.0
	(c) 2008-2015 i3network computer technologies
	All Rights Reserved.

	http://i3network.net  -  http://envisageapp.com


	------

	This is an example file, to explain how to connect to the enVisage API.
	Any API on the server can be used by this API pipe - even those that
	are included in extensions.

*/
	//initiate a new API Application instance.
		$_api=new enVisage(
			'Example API App', //application Name
			'0.0.1', //application Version
			'com.envisageapp.api-example', //application Identifier
			'12iu3h12i1203131p31mk31', //application API key
			array(
				//options to pass to the API
				'debug'=>0 //show debug information to trace issues
				)
			);

	//connect to a server...
		$_api->server(
			'api.envisageapp.com', //server or IP address of server
			80, //port number of server, 80, 443 or 149 (or custom port if behind firewall)
			'http', //protocol - http or https, must relate to port number and have a valid certificate if https
			1 //is this an enVisage API server, 1= yes, 0=enVisage Application Engine
			/*
				api.envisageapp.com is an API server,
				yourbusinessname.envisageapp.com is the enVisage Application Engine
				*/
			);

	//log in to enVisage...
		$auth=$_api->api('user.authenticate',array(
			'username'=>$_REQUEST['username'],
			'password'=>$_REQUEST['password'],
			'company'=>17,
			));
		print_r($auth);

	//find info abotu this user...
		$whoami=$_api->api('users/whoami');
		print_r($whoami);

	//terminate the session...
		$auth=$_api->api('user.authenticate.logout');
		print_r($auth);


	//do some cleanups on this server
		//remove the cookie jar.
			$cookiejar='data/tmp/cookiejars/'.session_id().'.i3cookiejar';
			unlink($cookiejar);
		//start a new session...
			session_regenerate_id();
?>
<?php
class enVisage {
	/////////////////////////////////////////////////////////////////////
	//config options
		public $_method;
		private $_about = array(
			'type'=>'api-php',
			'version'=>'envisageapi-1-20150612-1750',
			);
		private $_connection; //the mySQL connection resource
		private $_options = array(
			'orderby'=>"ASC",
			'debug'=>0
			);
		private $_run = array(
			); //variable used to store information to build the query.
		private $_query;
	/////////////////////////////////////////////////////////////////////
	//PHP Construct and destruct
		function __construct($applicationName,$applicationVersion,$applicationIdentifier,$applicationAPIKey,$options=NULL){
			global $_mysql;

			//start sessions..
				session_start();
			//load options
				$this->_options=(is_array($options) ? $options : $this->_options);
			//connection signature:
				$connection_signature=md5("envisage://".$server.':'.$port);
				$this->debug("Construct","Converting connection details to connection signature... [".$connection_signature."]");

			$this->_run=array(
				'application'=>array(
					'name'=>$applicationName,
					'version'=>$applicationVersion,
					'identifier'=>$applicationIdentifier,
					'apikey'=>$applicationAPIKey,
					),
				);

			if($this->_connection->connect_errno > 0){
			    die('Unable to connect to enVisage server');
			}

			$this->_run['time']['start']=microtime();
			}
		function __destruct(){
			$this->debug("Destruct","Closing connecting to server.");
			$this->time_end();
			}
	//Le About
		public function about($method){
			echo "<pre>Version: ".$this->_about->version."</pre>";
			}

		public function server($serverHostName,$serverPort=80,$serverProtocol="http",$is_enVisageAPI_sever=1){
			global $_run;
			$_run['server']=array(
				'protocol'=>$serverProtocol,
				'hostname'=>$serverHostName,
				'port'=>$serverPort,
				'isAPIServer'=>$is_enVisageAPI_sever,
				);
			}

	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	//curl
	private function http($url,$data,$session=""){
		$ch = curl_init();

		//are we posting information?
			if(is_array($data)){
	/*
				foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
				rtrim($fields_string, '&');
	*/
				$fields=http_build_query($data);
				curl_setopt($ch,CURLOPT_POST, count($data));
				curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
			}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1); // return HTTP headers with response
		$headers=array(
		    'enVisageAPI: 1',
		    'enVisageAPI-type: '.$this->_about['type'],
		    'enVisageAPI-version: '.$this->_about['version'],
		    'enVisageAPI-application: '.json_encode($this->_run['application']),
		    );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

		//cookiejar.
			$cookiejar='data/tmp/cookiejars/'.session_id().'.i3cookiejar';
			curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookiejar);
			curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookiejar);

		$file_contents = curl_exec($ch);
		$error=curl_errno($ch);
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($error!=""){
			if($error==1){

			}
		$error_types=array(
			0=>'OK',
			1=>'Unsupported Protocol',
			2=>'Failed init',
			3=>'Malformed URL',
			4=>'Malformed User',
			5=>'Couldn\'t resolve proxy',
			6=>'Couldn\'t resolve host',
			7=>'Couldn\'t connect',
			8=>'Weird server reply (FTP)',
			);
			$this->debug("cURL","An error occurred. ".$error_types[$error]." (".$error.")");
		}else{
			$this->debug("cURL","Successfully loaded. ".$url);
		}

		/////////////////////////////////////////////////////////////////////
		//Headers
		list($headers, $response) = explode("\r\n\r\n", $file_contents, 2);
			// $headers now has a string of the HTTP headers
			// $response is the body of the HTTP response

			$headers = explode("\n", $headers);
			$headers_out=array();
			foreach($headers as $header) {
				$h=explode(": ",$header);
			    $headers_out[$h[0]]=$h[1];
			}

		$return = array('out'=>$response,'http-status'=>$httpcode,'headers'=>$headers_out,'result'=>$error);
		return $return;

	}


	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	//API
		public function api($api,$parameters=array()){
			global $_run;
			if($api!=""){
				$api_url=$_run['server']['protocol'].'://'.$_run['server']['hostname'].':'.$_run['server']['port'].'/'.(!$_run['server']['isAPIServer'] ? 'api/' : '').$api;
				$http=$this->http($api_url,$parameters);
				$_run['cookies']=$http['headers']['Set-Cookie'];
				$return=json_decode($http['out'],1);
				//check if is actually JSON: http://php.net/manual/en/function.json-decode.php#110820
				if (json_last_error() === JSON_ERROR_NONE) {
					//do something with $json. It's ready to use
					return $return;
				} else {
					//yep, it's not JSON. Log error or alert someone or do nothing
					return $http['out'];
				}
			}
		}


	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	//time
		public function time_checkpoint($name=""){
			$start=$this->_run['time']['start'];
			$now=microtime();
			//add now to checkpoint..
				if($name==""){
					$this->_run['time']['checkpoints'][]=$now;
				}else{
					$this->_run['time']['checkpoints'][$name]=$now;
				}
			}

		public function time_split($name,$split="start"){
			$now=microtime();
			$this->_run['time']['splits'][$name][$split]=$now;
			if($split=="end"){
				$duration=$now - $this->_run['time']['splits'][$name]['start'];
				$this->_run['time']['splits'][$name]['_duration']=$now;
				}
			}

		public function time_end(){
			$now=microtime();
			//add now to checkpoint..
				$this->_run['time']['end']=$now;
				}

		public function time(){
			//shows the current duration..
			$start=$this->_run['time']['start'];
			$now=microtime();
			//add now to checkpoint..
				return $now-$start;
			}

	/////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////
	//debug
		public function debug($title,$message,$die=0,$force=0){
			if($this->_options['debug'] || $force){
				echo ($force ? "Critical Error" : "Debug").": ".$title."> [svr: ".($this->_run['server']!="" ? $this->_run['server'] : '<i>servernotconnected</i>')."] ";
				if(is_array($message)){
					print_r($message);
				}else{
					echo $message;
				}
				echo "<br>\n";
				}else {
				}
			if($die){
				echo "<!--".$this->_query.(count($this->_options['debugnotes'])==0 ? "\nNo debug notes..." : "\nDebug Notes: ".implode("\n",$this->_options['debugnotes']))."-->";
				die("> Killed by debug.\n");
				}else{
					return $this;
				}
			}
		private function stop($title,$message){
			$this->debug($title,$message,1,1);
			}

		public function debug_privates(){
			echo "<br>\n<pre>DEBUG:\n=======\n";
			echo "_options: ";
			print_r($this->_options);
			echo "<br>\n_run: ";
			print_r($this->_run);
			echo "<br>\n</pre>";
			}

		public function options($option, $value){
			$this->debug("options(set)","Setting `".$option."` to '".$value."'");
			$this->_options[$option]=$value;
			}
		public function option($option, $value){ //alias
			$this->options($option, $value);
			}
		public function purpose($value){ //alias for option:purpose
			//allows for short hand calling for changing mode to nest.
			$this->options("purpose", $value);
			}
		public function print_options(){
			print_r($this->_options['debug']);
			}
		public function debug_note($debugnote){
			$this->debug("options(set)","Adding `".$debugnote."` to options:debugnotes");
			$this->_options['debugnotes'][]=$debugnote;
			}
}

	?>
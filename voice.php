<?php 

//$response = getRoomInvite('NA','1211212');
if ( !isset($_GET['server']) || !isset($_GET['hash']) ){
	echo 'Forbidden';
}else{
	$response = getRoomInvite($_GET['server'],$_GET['hash']);
	header('Location: '.$response['url']);
}

// PARA BORRAR EN UN FUTURO LOS CHANNELS
//$response = getGuildsChannels($guild_id,$token);
//var_dump($response);


function getToken(){
	$msg = array(
		'email'  => 'support@decayoflegends.com',
		'password' => $_SERVER['DISCORD_PASS']
	);
	$queryMSG = http_build_query($msg);

	$url = 'https://discordapp.com/api/auth/login';

	$options = array(
	    'http'    => array(
	      'method'  => 'POST',
	      'header'  =>  "Content-Type: application/x-www-form-urlencoded\r\n",
	      'content' => $queryMSG,
	      'ignore_errors' => true
	  )
	);

	$context  = stream_context_create( $options );
	$result = file_get_contents($url, FALSE, $context);

	return json_decode($result,TRUE)['token'];
}

function getGuildID($server){
	switch ($server) {
		case 'NA':
			$guild = '146036033123909633';
			break;

		case 'EU':
			$guild = '146041836660719616';
			break;

		case 'LA':
			$guild = '146188633450217472';
			break;

		case 'OCE':
			$guild = '146188850664833024';
			break;
		
		default:
			$guild = '146188633450217472';
			break;
	}
	return $guild;
}

function newRoom($guild,$num,$token){
	
	$msg = array(
		'name'  => time(),
		'type' => 'voice'
	);
	$queryMSG = http_build_query($msg);

	$url = 'https://discordapp.com/api/guilds/'.$guild.'/channels';

	$options = array(
	    'http'    => array(
	      'method'  => 'POST',
	      'header'  =>  "Content-Type: application/x-www-form-urlencoded\r\n".
	      				"Authorization: " . $token . "\r\n",
	      'content' => $queryMSG
	  )
	);

	$context  = stream_context_create( $options );
	$result = file_get_contents($url, FALSE, $context);
	return json_decode($result,TRUE)['id'];
}

function newInvite($id,$token){

	$url = 'https://discordapp.com/api/channels/'.$id.'/invites';

	$options = array(
	    'http'    => array(
	      'method'  => 'POST',
	      'header'  =>  "Content-Type: application/x-www-form-urlencoded\r\n".
	      				"Authorization: " . $token . "\r\n",
	      'ignore_errors' => true
	  )
	);

	$context  = stream_context_create( $options );
	$result = file_get_contents($url, FALSE, $context);

	return 'https://discordapp.com/invite/' . json_decode($result,TRUE)['code'];
}

function getGuildsChannels($id,$token){

	$url = 'https://discordapp.com/api/guilds/'.$id.'/channels';

	$options = array(
	    'http'    => array(
	      'method'  => 'GET',
	      'header'  =>  "Content-Type: application/x-www-form-urlencoded\r\n".
	      				"Authorization: " . $token . "\r\n",
	      'ignore_errors' => true
	  )
	);

	$context  = stream_context_create( $options );
	$result = file_get_contents($url, FALSE, $context);

	return $result;
}

function getInviteUses($id,$token){

	$url = 'https://discordapp.com/api/channels/'.$id.'/invites';

	$options = array(
	    'http'    => array(
	      'method'  => 'GET',
	      'header'  =>  "Content-Type: application/x-www-form-urlencoded\r\n".
	      				"Authorization: " . $token . "\r\n",
	      'ignore_errors' => true
	  )
	);

	$context  = stream_context_create( $options );
	$result = file_get_contents($url, FALSE, $context);
	//var_dump($result);
	return json_decode($result,TRUE)[0]['uses'];
}

function getRoomInvite($server,$hash){
// Returns channel invite url and how many times it was used

	$guild_id = getGuildID($server);
	$memcache = new Memcache;
	$token = $memcache->get('token');
	if ( $token === false ) {
	    $token = getToken();
	}

	$channel_id= $memcache->get($server.$hash);
	if ( $channel_id === false ) {
	    $channel_id =  newRoom($guild_id,$hash,$token);
	    $memcache->set($server.$hash, $channel_id);
	}
	/*
	$response = [
		'url' =>newInvite($channel_id,$token),
		'uses' => getInviteUses($channel_id,$token)
	];
	*/
	$response = [
		'url' =>newInvite($channel_id,$token)
	];
	return $response;
}


?>
<?php

/*
|--------------------------------------------------------------------------
|                  -- Slack Community Platform Creator --
|--------------------------------------------------------------------------
|
| Originally created by Pieter Levels
| Adapted by Struan McDonough for ease of use and installation
|
*/

date_default_timezone_set('Europe/London');
mb_internal_encoding("UTF-8");

/*
|--------------------------------------------------------------------------
| User Defined Variables
|--------------------------------------------------------------------------
*/
$typeformApiKey =				'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$typeformFormId =				'xxxxxx';
$typeformEmailField =			'email_xxxxxxxx';
$typeformNameField =			'textfield_xxxxxxxx';

$slackHostName =				'nameofyourcommunity';
$slackAutoJoinChannels =		'xxxxxxxxx,xxxxxxxxx,xxxxxxxxx,xxxxxxxxx,xxxxxxxxx,xxxxxxxxx';
$slackAuthToken =				'xoxp-xxxxxxxxxxx-xxxxxxxxxxx-xxxxxxxxxxx-xxxxxxxxxx';

$previouslyInvitedEmailsFile = __DIR__ . '/previouslyInvited.json';



// Create a log file
if (!file_get_contents($previouslyInvitedEmailsFile)) {
	$previouslyInvitedEmails = array();
}
else {
	$previouslyInvitedEmails = json_decode(file_get_contents($previouslyInvitedEmailsFile), true);
}
$offset = count($previouslyInvitedEmails);

/*
|--------------------------------------------------------------------------
| Contact the TypeForm API
|--------------------------------------------------------------------------
*/
$typeformApiUrl = 'https://api.typeform.com/v0/form/' . $typeformFormId . '?key=' . $typeformApiKey . '&completed=true&offset=' . $offset;

$usersToInvite = array();

foreach($typeformData['responses'] as $response) {
	$user['email'] = $response['answers'][$typeformEmailField];
if(!$typeformApiResponse = file_get_contents($typeformApiUrl)) {
	echo "Sorry, can't access API";
	exit;
}

$typeformData = json_decode($typeformApiResponse,true);

	$user['name'] = $response['answers'][$typeformNameField];
	if(!in_array($user['email'], $previouslyInvitedEmails)) {
			array_push($usersToInvite,$user);
	}
}

/*
|--------------------------------------------------------------------------
| Contact the Slack API
|--------------------------------------------------------------------------
*/
$slackInviteUrl = 'https://' . $slackHostName . '.slack.com/api/users.admin.invite?t=' . time();

$i = 1;
foreach($usersToInvite as $user) {
echo date('c') . ' - ' . $i . ' - ' . "\"" . $user['name'] . "\" <" . $user['email'] . "> - Inviting to " . $slackHostName . " Slack\n";

$fields = array(
	'email' 			=> urlencode($user['email']),
	'channels'			=> urlencode($slackAutoJoinChannels),
	'first_name' 		=> urlencode($user['name']),
	'token' 			=> $slackAuthToken,
	'set_active' 		=> urlencode('true'),
	'_attempts' 		=> '1'
);

$fields_string = '';
foreach($fields as $key => $value) {
	$fields_string .= $key . '=' . $value . '&';
}

rtrim($fields_string, '&');

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $slackInviteUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, count($fields));
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);

$replyRaw = curl_exec($ch);
$reply = json_decode($replyRaw, true);

if ($reply['ok'] == false) {
	echo date('c') . ' - ' . $i . ' - ' . "\"" . $user['name'] . "\" <" . $user['email'] . "> - " . 'Error: ' . $reply['error'] . "\n";
}

else {
	echo date('c') . ' - ' . $i . ' - ' . "\"" . $user['name'] . "\" <" . $user['email'] . "> - " . 'Invited successfully' . "\n";
}

curl_close($ch);

array_push($previouslyInvitedEmails, $user['email']);
	$i++;
}

file_put_contents($previouslyInvitedEmailsFile, json_encode($previouslyInvitedEmails));
?>

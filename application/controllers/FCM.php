<?php

class FCM {

	public static function send_message($title, $body, $token, $data) {
		$url = 'https://fcm.googleapis.com/fcm/send';
	    $fields = array(
            'registration_ids' => array($token)
    	);
    	if (sizeof($data) > 0) {
    		$fields['data'] = $data;
    	}
    	$fields = json_encode($fields);
    	$headers = array (
            'Authorization: key=' . "AAAAc-jbIic:APA91bG8Tp-gr8M5Ny-JBaI9FBRCWdVp_T0MkBHVvmXf4Zz0VjG8qe8m04md1soAvjJPNNZh0KdyjwPDsvIGLvjxAX1Zq8jHJGnQJOXn45N-GzGQfg1td7f8rpscIon0-vhonhi2qFIt",
            'Content-Type: application/json'
    	);
    	$ch = curl_init ();
    	curl_setopt ( $ch, CURLOPT_URL, $url );
    	curl_setopt ( $ch, CURLOPT_POST, true );
    	curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
    	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
	    $result = curl_exec ( $ch );
	    curl_close ( $ch );
	    //echo $result;
	}

	public static function send_message_to_topic($title, $body, $topic, $data) {
		$url = 'https://fcm.googleapis.com/fcm/send';
	    $fields = array(
            'to' => '/topics/' . $topic
    	);
    	if (sizeof($data) > 0) {
    		$fields['data'] = $data;
    	}
    	$fields = json_encode($fields);
    	$headers = array (
            'Authorization: key=' . "AAAAc-jbIic:APA91bG8Tp-gr8M5Ny-JBaI9FBRCWdVp_T0MkBHVvmXf4Zz0VjG8qe8m04md1soAvjJPNNZh0KdyjwPDsvIGLvjxAX1Zq8jHJGnQJOXn45N-GzGQfg1td7f8rpscIon0-vhonhi2qFIt",
            'Content-Type: application/json'
    	);
    	$ch = curl_init ();
    	curl_setopt ( $ch, CURLOPT_URL, $url );
    	curl_setopt ( $ch, CURLOPT_POST, true );
    	curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
    	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
	    $result = curl_exec ( $ch );
	    curl_close ( $ch );
	    //echo $result;
	}

	public static function send_notification($title, $body, $token, $data) {
		$url = 'https://fcm.googleapis.com/fcm/send';
	    $fields = array(
            'registration_ids' => array($token),
            'notification' => array(
            	'title' => $title,
            	'body' => $body
            )
    	);
    	if (sizeof($data) > 0) {
    		$fields['data'] = $data;
    	}
    	$fields = json_encode($fields);
    	$headers = array (
            'Authorization: key=' . "AAAAc-jbIic:APA91bG8Tp-gr8M5Ny-JBaI9FBRCWdVp_T0MkBHVvmXf4Zz0VjG8qe8m04md1soAvjJPNNZh0KdyjwPDsvIGLvjxAX1Zq8jHJGnQJOXn45N-GzGQfg1td7f8rpscIon0-vhonhi2qFIt",
            'Content-Type: application/json'
    	);
    	$ch = curl_init ();
    	curl_setopt ( $ch, CURLOPT_URL, $url );
    	curl_setopt ( $ch, CURLOPT_POST, true );
    	curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headers );
    	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    	curl_setopt ( $ch, CURLOPT_POSTFIELDS, $fields );
	    $result = curl_exec ( $ch );
	    curl_close ( $ch );
	    //echo $result;
	}
}

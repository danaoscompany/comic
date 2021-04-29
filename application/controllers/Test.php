<?php

include "FCM.php";
include "simple_html_dom.php";

class Test extends CI_Controller {
	
	public function update_comments_nickname() {
		$comments = $this->db->query("SELECT * FROM `comments`")->result_array();
		for ($i=0; $i<sizeof($comments); $i++) {
			$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $comments[$i]['user_id'])->row_array();
			$name = "";
			if ($user != NULL) {
				$name = $user['name'];
			}
			$this->db->query("UPDATE `comments` SET `nickname`='" . $name . "' WHERE `id`=" . $comments[$i]['id']);
		}
	}
	
	public function insert_emoji_to_db() {
		$this->db->insert("comments", array(
			'comment' => 'tes 😁'
		));
	}
	
	public function rearrange_user_id() {
		$users = $this->db->query("SELECT * FROM `users`")->result_array();
		for ($i=0; $i<sizeof($users); $i++) {
			$this->db->query("UPDATE `users` SET `id`=" . ($i+1) . " WHERE `uuid`='" . $users[$i]['uuid'] . "'");
		}
	}
	
	public function update_real_names() {
		$users = $this->db->query("SELECT * FROM `users`")->result_array();
		for ($i=0; $i<sizeof($users); $i++) {
			$this->db->query("UPDATE `users` SET `real_name`='" . str_replace("'", "\'", $users[$i]['name']) . "' WHERE `id`=" . $users[$i]['id']);
		}
	}
	
	public function fcm() {
		FCM::send_message_to_topic("Pesan baru", "", "groupchat", array(
			'type' => 'new_group_message',
			'user_id' => "1",
			'message' => "Tes",
			'user' => "[]"
		));
	}
	
	public function parse_html_mediafire() {
		$data = file_get_contents("http://localhost/comic/mediafire.html");
		$html = str_get_html($data);
		//print_r($html->find('.download_link')[0]->find("a")[1]->href);
		print_r($html->find('title', 0)->innertext);
	}
	
	public function parse_html_zippyshare() {
		$data = file_get_contents("http://localhost/comic/zippyshare.html");
		$html = str_get_html($data);
		print_r($html->find('.download_link')[0]->find("a")[1]->href);
	}
}

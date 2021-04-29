<?php

include "Util.php";
include "FCM.php";
include "simple_html_dom.php";

class User extends CI_Controller {
	
	public function json_response() {
		header('Content-Type: application/json');
	}
	
	public function authorize() {
		$headers = $this->input->request_headers();
		$auth = $headers['Authorization'];
		if ($auth != null) {
			$auth = trim($auth);
			if (strpos($auth, ' ') !== false) {
				if (substr($auth, 0, 6) == 'Basic ') {
					$apiKey = explode(' ', $auth)[1];
					$apiKey = substr($apiKey, 0, strlen($apiKey)-1);
					$apiKey = base64_decode($apiKey, false);
					$developers = $this->db->query("SELECT * FROM `developers` WHERE `api_secret`='" . $apiKey . "'")->num_rows();
					if ($developers <= 0) {
						echo json_encode(array(
							'error' => 'Invalid API key supplied to request.'
						));
						return false;
					} else {
						return true;
					}
				} else {
					echo json_encode(array(
						'error' => 'Invalid API key supplied to request.'
					));
					return false;
				}
			} else {
				echo json_encode(array(
					'error' => 'Invalid API key supplied to request.'
				));
				return false;
			}
		} else {
			echo json_encode(array(
				'error' => 'Invalid API key supplied to request.'
			));
			return false;
		}
	}
	
	public function get_video_by_id() {
		$this->json_response();
		if (!$this->authorize()) return;
		$videoUUID = $this->input->post('video_uuid');
		$userID = intval($this->input->post('user_id'));
		$video = $this->db->query("SELECT * FROM `videos` WHERE `id`=" . $videoUUID)->row_array();
		$video['comment_count'] = $this->db->query("SELECT * FROM `comments` WHERE `video_uuid`='" . $videoUUID . "'")->num_rows();
		echo json_encode($video);
	}
	
	public function get_comments() {
		$this->json_response();
		if (!$this->authorize()) return;
		$videoUUID = $this->input->post('video_uuid');
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$comments = $this->db->query("SELECT * FROM `comments` WHERE `video_uuid`='" . $videoUUID . "' AND `parent_comment_id`=0 ORDER BY `date`")->result_array();
		$allComments = [];
		$j = 0;
		for ($i=0; $i<sizeof($comments); $i++) {
			$comments[$i]['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $comments[$i]['user_id'])->row_array();
			$likeCount = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $comments[$i]['id'] . " AND `user_id`=" . $comments[$i]['user_id'])->num_rows();
			$comments[$i]['liked'] = $likeCount>0;
			$comments[$i]['like_count'] = $likeCount;
			$comments[$i]['comment_count'] = $this->db->query("SELECT * FROM `comments` WHERE `parent_comment_id`=" . $comments[$i]['id'])->num_rows();
			$stickerIDs = json_decode($comments[$i]['sticker_ids'], true);
			$stickers = [];
			for ($j=0; $j<sizeof($stickerIDs); $j++) {
				array_push($stickers, $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerIDs[$j])->row_array());
			}
			$comments[$i]['stickers'] = json_encode($stickers);
			$parentCommentID = intval($comments[$i]['parent_comment_id']);
			if ($parentCommentID != 0) {
				$parentComment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $parentCommentID)->row_array();
				if ($parentComment != null) {
					$parentCommentUserID = intval($parentComment['user_id']);
					$comments[$i]['parent_comment_name'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $parentCommentUserID)->row_array()['name'];
				}
			}
			array_push($allComments, $comments[$i]);
			$j++;
			// Subcomments
			$subcomments = $this->db->query("SELECT * FROM `comments` WHERE `video_uuid`='" . $videoUUID . "' AND `parent_comment_id`=" . $comments[$i]['id'] . " ORDER BY `date` ASC")->result_array();
			if (sizeof($subcomments) > 0) {
				for ($k=0; $k<sizeof($subcomments); $k++) {
					$subcomments[$k]['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $subcomments[$k]['user_id'])->row_array();
					$likeCount = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $subcomments[$k]['id'] . " AND `user_id`=" . $subcomments[$k]['user_id'])->num_rows();
					$subcomments[$k]['liked'] = $likeCount>0;
					$subcomments[$k]['like_count'] = $likeCount;
					$subcomments[$k]['comment_count'] = $this->db->query("SELECT * FROM `comments` WHERE `parent_comment_id`=" . $subcomments[$k]['id'])->num_rows();
					$stickerIDs = json_decode($subcomments[$k]['sticker_ids'], true);
					$stickers = [];
					for ($j=0; $j<sizeof($stickerIDs); $j++) {
						array_push($stickers, $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerIDs[$j])->row_array());
					}
					$subcomments[$k]['stickers'] = json_encode($stickers);
					$parentCommentID = intval($subcomments[$k]['parent_comment_id']);
					if ($parentCommentID != 0) {
						$parentComment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $parentCommentID)->row_array();
						if ($parentComment != null) {
							$parentCommentUserID = intval($parentComment['user_id']);
							$subcomments[$k]['parent_comment_name'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $parentCommentUserID)->row_array()['name'];
						}
					}
					array_push($allComments, $subcomments[$k]);
					$j++;
				}
			}
		}
		for ($i=0; $i<sizeof($allComments); $i++) {
			$allComments[$i]['likes'] = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $allComments[$i]['id'])->num_rows();
		}
		usort($allComments, function ($comment1, $comment2) {
    		return intval($comment2['likes']) <=> intval($comment1['likes']);
		});
		//$allComments = array_slice($allComments, $start, $length);
		echo json_encode($allComments);
	}
	
	public function like_video() {
		$this->json_response();
		if (!$this->authorize()) return;
		$videoUUID = $this->input->post('video_uuid');
		$userID = intval($this->input->post('user_id'));
		$this->db->query("DELETE FROM `video_likes` WHERE `video_uuid`='" . $videoUUID . "' AND `user_id`=" . $userID);
		$this->db->query("INSERT INTO `video_likes` (`video_uuid`, `user_id`) VALUES ('" . $videoUUID . "', " . $userID . ")");
	}
	
	public function dislike_video() {
		$this->json_response();
		if (!$this->authorize()) return;
		$videoUUID = $this->input->post('video_uuid');
		$userID = intval($this->input->post('user_id'));
		$this->db->query("DELETE FROM `video_likes` WHERE `video_uuid`='" . $videoUUID . "' AND `user_id`=" . $userID);
	}
	
	public function like_comment() {
		$this->json_response();
		if (!$this->authorize()) return;
		$commentID = intval($this->input->post('comment_id'));
		$userID = intval($this->input->post('user_id'));
		$this->db->query("DELETE FROM `comment_likes` WHERE `comment_id`=" . $commentID . " AND `user_id`=" . $userID);
		$this->db->query("INSERT INTO `comment_likes` (`comment_id`, `user_id`) VALUES (" . $commentID . ", " . $userID . ")");
	}
	
	public function dislike_comment() {
		$this->json_response();
		if (!$this->authorize()) return;
		$commentID = intval($this->input->post('comment_id'));
		$userID = intval($this->input->post('user_id'));
		$this->db->query("DELETE FROM `comment_likes` WHERE `comment_id`=" . $commentID . " AND `user_id`=" . $userID);
	}
	
	public function add_comment() {
		$this->json_response();
		if (!$this->authorize()) return;
		$this->db->query("ALTER TABLE comments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
		$parentCommentID = intval($this->input->post('parent_comment_id'));
		$videoUUID = $this->input->post('video_uuid');
		$userID = intval($this->input->post('user_id'));
		$commentText = $this->input->post('comment');
		$stickerIDs = json_decode($this->input->post('sticker_ids'));
		$date = $this->input->post('date');
		$this->db->insert('comments', array(
			'parent_comment_id' => $parentCommentID,
			'video_uuid' => $videoUUID,
			'user_id' => $userID,
			'sticker_ids' => json_encode($stickerIDs),
			'comment' => $commentText,
			'date' => $date
		));
		$commentID = intval($this->db->insert_id());
		$comment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $commentID)->row_array();
		$comment['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $comment['user_id'])->row_array();
		$likeCount = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $comment['id'] . " AND `user_id`=" . $comment['user_id'])->num_rows();
		$comment['liked'] = $likeCount>0;
		$comment['like_count'] = $likeCount;
		$comment['comment_count'] = $this->db->query("SELECT * FROM `comments` WHERE `parent_comment_id`=" . $comment['id'])->num_rows();
		$stickers = [];
		for ($i=0; $i<sizeof($stickerIDs); $i++) {
			$stickerID = $stickerIDs[$i];
			if ($stickerID != 0) {
				array_push($stickers, $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerID)->row_array());
			}
		}
		$comment['stickers'] = $stickers;
		$parentCommentID = intval($comment['parent_comment_id']);
		if ($parentCommentID != 0) {
			$parentComment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $parentCommentID)->row_array();
			if ($parentComment != null) {
				$parentCommentUserID = intval($parentComment['user_id']);
				$comment['parent_comment_name'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $parentCommentUserID)->row_array()['name'];
			}
		}
		echo json_encode($comment, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
	}
	
	public function add_comment_image() {
		$this->json_response();
		if (!$this->authorize()) return;
		$parentCommentID = intval($this->input->post('parent_comment_id'));
		$videoUUID = $this->input->post('video_uuid');
		$userID = intval($this->input->post('user_id'));
		$comment = $this->input->post('comment');
		$gDriveFileID = $this->input->post('gdrive_file_id');
		$gDriveFileLink = $this->input->post('gdrive_file_link');
		$stickerIDs = json_decode($this->input->post('sticker_ids'));
		$date = $this->input->post('date');
		$this->db->insert('comments', array(
			'parent_comment_id' => $parentCommentID,
			'video_uuid' => $videoUUID,
			'user_id' => $userID,
			'sticker_ids' => json_encode($stickerIDs),
			'comment' => $comment,
			'gdrive_file_id' => $gDriveFileID,
			'gdrive_file_link' => $gDriveFileLink,
			'date' => $date
		));
		$commentID = intval($this->db->insert_id());
		$comment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $commentID)->row_array();
		$comment['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $comment['user_id'])->row_array();
		$likeCount = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $comment['id'] . " AND `user_id`=" . $comment['user_id'])->num_rows();
		$comment['liked'] = $likeCount>0;
		$comment['like_count'] = $likeCount;
		$comment['comment_count'] = $this->db->query("SELECT * FROM `comments` WHERE `parent_comment_id`=" . $comment['id'])->num_rows();
		$stickers = [];
		for ($i=0; $i<sizeof($stickerIDs); $i++) {
			$stickerID = $stickerIDs[$i];
			if ($stickerID != 0) {
				array_push($stickers, $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerID)->row_array());
			}
		}
		$comment['stickers'] = $stickers;
		$parentCommentID = intval($comment['parent_comment_id']);
		if ($parentCommentID != 0) {
			$parentComment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $parentCommentID)->row_array();
			if ($parentComment != null) {
				$parentCommentUserID = intval($parentComment['user_id']);
				$comment['parent_comment_name'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $parentCommentUserID)->row_array()['name'];
			}
		}
		echo json_encode($comment);
	}
	
	public function add_sticker() {
		$this->json_response();
		if (!$this->authorize()) return;
		$parentCommentID = intval($this->input->post('parent_comment_id'));
		$videoUUID = $this->input->post('video_uuid');
		$userID = intval($this->input->post('user_id'));
		$comment = $this->input->post('comment');
		$stickerID = intval($this->input->post('sticker_id'));
		$date = $this->input->post('date');
		$this->db->insert('comments', array(
			'parent_comment_id' => $parentCommentID,
			'video_uuid' => $videoUUID,
			'user_id' => $userID,
			'sticker_id' => $stickerID,
			'comment' => $comment,
			'date' => $date
		));
		$commentID = intval($this->db->insert_id());
		$comment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $commentID)->row_array();
		$comment['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $comment['user_id'])->row_array();
		$likeCount = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $comment['id'] . " AND `user_id`=" . $comment['user_id'])->num_rows();
		$comment['liked'] = $likeCount>0;
		$comment['like_count'] = $likeCount;
		$comment['comment_count'] = $this->db->query("SELECT * FROM `comments` WHERE `parent_comment_id`=" . $comment['id'])->num_rows();
		$stickerID = intval($comment['sticker_id']);
		if ($stickerID != 0) {
			$comment['sticker'] = $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $comment['sticker_id'])->row_array();
		}
		$parentCommentID = intval($comment['parent_comment_id']);
		if ($parentCommentID != 0) {
			$parentComment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $parentCommentID)->row_array();
			if ($parentComment != null) {
				$parentCommentUserID = intval($parentComment['user_id']);
				$comment['parent_comment_name'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $parentCommentUserID)->row_array()['name'];
			}
		}
		echo json_encode($comment);
	}
	
	public function get_stickers_by_category() {
		$this->json_response();
		if (!$this->authorize()) return;
		$category = $this->input->post('category');
		$stickers = $this->db->query("SELECT * FROM `stickers` WHERE `category`='" . $category . "'")->result_array();
		echo json_encode($stickers);
	}
	
	public function get_sticker_categories() {
		$this->json_response();
		if (!$this->authorize()) return;
		$stickers = $this->db->query("SELECT * FROM `stickers` GROUP BY `category`")->result_array();
		echo json_encode($stickers);
	}
	
	public function update_comment() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = intval($this->input->post('id'));
		$comment = $this->input->post('comment');
		$this->db->query("UPDATE `comments` SET `comment`='" . $comment . "' WHERE `id`=" . $id);
	}
	
	public function delete_comment() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = intval($this->input->post('id'));
		$this->db->query("DELETE FROM `comments` WHERE `parent_comment_id`=" . $id);
		$this->db->query("DELETE FROM `comments` WHERE `id`=" . $id);
	}
	
	public function get_user_by_id() {
		$this->json_response();
		if (!$this->authorize()) return;
		$userID = intval($this->input->post('id'));
		echo json_encode($this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array());
	}
	
	public function set_user_uuid($uuid, $name) {
		$userCount = $this->db->query("SELECT * FROM `users` WHERE `uuid`='" . $uuid . "'")->num_rows();
		if (sizeof($userCount) <= 0) {
			$this->db->insert('users', array(
				'uuid' => $uuid,
				'name' => $name,
				'real_name' => $name
			));
		}
	}
	
	public function login_with_google() {
		$uuid = $this->input->post('uuid');
		$name = $this->input->post('name');
		$users = $this->db->query("SELECT * FROM `users` WHERE `google_uuid`='" . $uuid . "'")->result_array();
		if (sizeof($users) <= 0) {
			$this->db->insert('users', array(
				'uuid' => $uuid,
				'name' => $name,
				'real_name' => $name
			));
			$id = intval($this->db->insert_id());
			echo $id;
		} else {
			$user = $users[0];
			$currentName = $user['name'];
			if ($currentName == null || trim($currentName) == "") {
				$this->db->where('google_uuid', $uuid);
				$this->db->update('users', array(
					'name' => $name
				));
			}
			$id = intval($user['id']);
			echo $id;
		}
	}
	
	public function is_video_liked() {
		$userID = intval($this->input->post('user_id'));
		$videoUuid = $this->input->post('video_uuid');
		$videoLikeCount = $this->db->query("SELECT * FROM `video_likes` WHERE `user_id`=" . $userID . " AND `video_uuid`='" . $videoUuid . "'")->num_rows();
		if ($videoLikeCount > 0) {
			echo 1;
		} else {
			echo 0;
		}
	}
	
	public function get_comment_count() {
		$videoUUID = $this->input->post('video_uuid');
		echo $this->db->query("SELECT * FROM `comments` WHERE `video_uuid`='" . $videoUUID . "'")->num_rows();
	}
	
	public function update_premium_status() {
		$userID = $this->input->post('user_id');
		$premium = $this->input->post('premium');
		$this->db->query("UPDATE `users` SET `premium`=" . $premium . " WHERE `id`=" . $userID);
		echo "UPDATE `users` SET `premium`=" . $premium . " WHERE `id`=" . $userID;
	}
	
	public function get_user_by_google_uuid() {
		$uuid = $this->input->post('uuid');
		$email = $this->input->post('email');
		$name = $this->input->post('name');
		$users = $this->db->query("SELECT * FROM `users` WHERE `google_uuid`='" . $uuid . "'")->result_array();
		if (sizeof($users) > 0) {
			$user = $users[0];
			echo json_encode($user);
		} else {
			$this->db->insert('users', array(
				'uuid' => Util::generateUUIDv4(),
				'email' => $email,
				'name' => $name,
				'real_name' => $name,
				'google_uuid' => $uuid
			));
			$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $this->db->insert_id())->row_array();
			echo json_encode($user);
		}
	}
	
	public function update_profile() {
		$userID = intval($this->input->post('user_id'));
		$nickname = $this->input->post('nickname');
		$profilePictureChanged = intval($this->input->post('profile_picture_changed'));
		if ($profilePictureChanged == 1) {
			$config['upload_path']          = './userdata/';
	        $config['allowed_types']        = '*';
	        $config['max_size']             = 2147483647;
	        $config['file_name']            = Util::generateUUIDv4();
			$this->load->library('upload', $config);
	        if ($this->upload->do_upload('file')) {
	        	$this->db->where('id', $userID);
	        	$this->db->update('users', array(
	        		'profile_picture' => $this->upload->data()['file_name']
	        	));
	        } else {
	        	echo json_encode($this->upload->display_errors());
	        }
		}
		$this->db->where('id', $userID);
		$this->db->update('users', array(
			'name' => $nickname
		));
		echo json_encode($this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array());
	}
	
	public function send_group_chat_message() {
		$userID = intval($this->input->post('user_id'));
		$message = $this->input->post('message');
		$date = $this->input->post('date');
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array();
		$this->db->insert('group_messages', array(
			'user_id' => $userID,
			'type' => 'text',
			'message' => $message,
			'date' => $date
		));
		FCM::send_message_to_topic("Pesan baru", "", "groupchat", array(
			'type' => 'new_group_message',
			'user_id' => "" . $userID,
			'subtype' => 'text',
			'message' => $message,
			'user' => json_encode($user),
			'date' => $date
		));
	}
	
	public function send_group_chat_image() {
		$userID = intval($this->input->post('user_id'));
		$message = $this->input->post('message');
		$date = $this->input->post('date');
		$image = $this->input->post('image');
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array();
		$this->db->insert('group_messages', array(
			'user_id' => $userID,
			'type' => 'image',
			'message' => $message,
			'image' => $image,
			'date' => $date
		));
		FCM::send_message_to_topic("Pesan baru", "", "groupchat", array(
			'type' => 'new_group_message',
			'user_id' => "" . $userID,
			'subtype' => 'image',
			'message' => $message,
			'image' => $image,
			'user' => json_encode($user),
			'date' => $date
		));
	}
	
	public function send_group_chat_sticker() {
		$userID = intval($this->input->post('user_id'));
		$message = $this->input->post('message');
		$date = $this->input->post('date');
		$stickerIDs = json_decode($this->input->post('sticker_ids'));
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $userID)->row_array();
		$stickers = [];
		for ($i=0; $i<sizeof($stickerIDs); $i++) {
			array_push($stickers, $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerIDs[$i])->row_array());
		}
		$this->db->insert('group_messages', array(
			'user_id' => $userID,
			'type' => 'sticker',
			'message' => $message,
			'sticker_ids' => json_encode($stickerIDs),
			'date' => $date
		));
		FCM::send_message_to_topic("Pesan baru", "", "groupchat", array(
			'type' => 'new_group_message',
			'user_id' => "" . $userID,
			'subtype' => 'sticker',
			'message' => $message,
			'stickers' => json_encode($stickers),
			'user' => json_encode($user),
			'date' => $date
		));
	}
	
	public function update_fcm_id() {
		$userID = intval($this->input->post('user_id'));
		$fcmID = $this->input->post('fcm_id');
		$this->db->where('id', $userID);
		$this->db->update('users', array(
			'fcm_id' => $fcmID
		));
	}
	
	public function get_group_messages() {
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$messages = $this->db->query("SELECT * FROM `group_messages` ORDER BY `date` DESC LIMIT " . $start . "," . $length)->result_array();
		for ($i=0; $i<sizeof($messages); $i++) {
			$messages[$i]['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $messages[$i]['user_id'])->row_array();
			$stickers = [];
			if ($messages[$i]['type'] == 'sticker') {
				$stickerIDs = json_decode($messages[$i]['sticker_ids'], true);
				for ($j=0; $j<sizeof($stickerIDs); $j++) {
					$sticker = $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerIDs[$j])->row_array();
					if ($sticker != null) {
						array_push($stickers, $sticker);
					}
				}
			}
			$messages[$i]['stickers'] = $stickers;
		}
		echo json_encode($messages);
	}
	
	public function get_private_chat() {
		$senderUserID = intval($this->input->post('sender_user_id'));
		$receiverUserID = intval($this->input->post('receiver_user_id'));
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$chats = $this->db->query("SELECT * FROM `private_chats` WHERE (`sender_user_id`=" . $senderUserID . " AND `receiver_user_id`=" . $receiverUserID . ") OR (`sender_user_id`=" . $receiverUserID . " AND `receiver_user_id`=" . $senderUserID . ")")->result_array();
		$uuid = "";
		if (sizeof($chats) > 0) {
			$uuid = $chats[0]['uuid'];
		} else {
			$uuid = Util::generateUUIDv4();
			$this->db->insert('private_chats', array(
				'uuid' => $uuid,
				'sender_user_id' => $senderUserID,
				'receiver_user_id' => $receiverUserID
			));
		}
		$messages = $this->db->query("SELECT * FROM `private_chat_messages` WHERE `uuid`='" . $uuid . "' ORDER BY `date` DESC LIMIT " . $start . "," . $length)->result_array();
		for ($i=0; $i<sizeof($messages); $i++) {
			$messages[$i]['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $messages[$i]['user_id'])->row_array();
			$stickers = [];
			if ($messages[$i]['type'] == 'sticker') {
				$stickerIDs = json_decode($messages[$i]['sticker_ids'], true);
				for ($j=0; $j<sizeof($stickerIDs); $j++) {
					$sticker = $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerIDs[$j])->row_array();
					if ($sticker != null) {
						array_push($stickers, $sticker);
					}
				}
			}
			$messages[$i]['stickers'] = $stickers;
		}
		echo json_encode(array(
			'uuid' => $uuid,
			'messages' => $messages
		));
	}
		
	public function send_private_chat_message() {
		$uuid = $this->input->post('uuid');
		$senderUserID = intval($this->input->post('sender_user_id'));
		$receiverUserID = intval($this->input->post('receiver_user_id'));
		$message = $this->input->post('message');
		$date = $this->input->post('date');
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $senderUserID)->row_array();
		$this->db->insert('private_chat_messages', array(
			'uuid' => $uuid,
			'user_id' => $senderUserID,
			'type' => 'text',
			'message' => $message,
			'date' => $date
		));
		$this->db->where("uuid", $uuid);
		$this->db->update('private_chats', array(
			'date' => $date
		));
		$receiver = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $receiverUserID)->row_array();
		FCM::send_notification("Pesan baru", $message, $receiver['fcm_id'], array(
			'type' => 'new_private_message',
			'subtype' => 'text',
			'user_id' => "" . $senderUserID,
			'sender_user_id' => "" . $senderUserID,
			'receiver_user_id' => "" . $receiverUserID,
			'message' => $message,
			'user' => json_encode($user),
			'date' => $date,
			'hide_notification' => 'true'
		), 'private_chat');
	}
	
	public function send_private_chat_image() {
		$uuid = $this->input->post('uuid');
		$senderUserID = intval($this->input->post('sender_user_id'));
		$receiverUserID = intval($this->input->post('receiver_user_id'));
		$message = $this->input->post('message');
		$image = $this->input->post('image');
		$date = $this->input->post('date');
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $senderUserID)->row_array();
		$this->db->insert('private_chat_messages', array(
			'uuid' => $uuid,
			'user_id' => $senderUserID,
			'message' => $message,
			'type' => 'image',
			'image' => $image,
			'date' => $date
		));
		$this->db->where("uuid", $uuid);
		$this->db->update('private_chats', array(
			'date' => $date
		));
		$receiver = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $receiverUserID)->row_array();
		FCM::send_notification("Pesan baru", "[Gambar]", $receiver['fcm_id'], array(
			'type' => 'new_private_message',
			'subtype' => 'image',
			'user_id' => "" . $senderUserID,
			'sender_user_id' => "" . $senderUserID,
			'receiver_user_id' => "" . $receiverUserID,
			'message' => $message,
			'user' => json_encode($user),
			'date' => $date,
			'image' => $image,
			'hide_notification' => 'true'
		), 'private_chat');
		echo json_encode(array(
			'image' => $image,
			'user' => $user,
			'date' => $date
		));
	}
	
	public function send_private_chat_sticker() {
		$uuid = $this->input->post('uuid');
		$senderUserID = intval($this->input->post('sender_user_id'));
		$receiverUserID = intval($this->input->post('receiver_user_id'));
		$message = $this->input->post('message');
		$date = $this->input->post('date');
		$stickerIDs = json_decode($this->input->post('sticker_ids'));
		$user = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $senderUserID)->row_array();
		$stickers = [];
		for ($i=0; $i<sizeof($stickerIDs); $i++) {
			array_push($stickers, $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $stickerIDs[$i])->row_array());
		}
		$this->db->insert('private_chat_messages', array(
			'uuid' => $uuid,
			'user_id' => $senderUserID,
			'message' => $message,
			'type' => 'sticker',
			'sticker_ids' => json_encode($stickerIDs),
			'date' => $date
		));
		$this->db->where("uuid", $uuid);
		$this->db->update('private_chats', array(
			'date' => $date
		));
		$receiver = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $receiverUserID)->row_array();
		FCM::send_notification("Pesan baru", "[Stiker]", $receiver['fcm_id'], array(
			'type' => 'new_private_message',
			'subtype' => 'sticker',
			'user_id' => "" . $senderUserID,
			'sender_user_id' => "" . $senderUserID,
			'receiver_user_id' => "" . $receiverUserID,
			'message' => $message,
			'user' => json_encode($user),
			'date' => $date,
			'stickers' => json_encode($stickers),
			'hide_notification' => 'true'
		), 'private_chat');
		echo json_encode(array(
			'stickers' => $stickers,
			'user' => $user,
			'date' => $date
		));
	}
	
	public function generate_direct_download_link() {
		$link = $this->input->post('link');
		$server = strtolower($this->input->post('server'));
		$data = Util::downloadFile($link);
		$html = str_get_html($data);
		$title = $html->find('title', 0)->innertext;
		$directURL = "";
		if ($server == "mediafire") {
			$directURL = $html->find('.download_link')[0]->find("a")[1]->href;
		} else if ($server == "zippyshare") {
		}
		echo json_encode(array(
			'title' => $title,
			'direct_url' => $directURL
		));
	}
	
	public function get_all_chats() {
		$chats = [];
		$userID = intval($this->input->post('user_id'));
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$lastGroupMessageJSON = $this->db->query("SELECT * FROM `group_messages` ORDER BY `date` LIMIT 1")->row_array();
		$lastGroupMessage = "";
		if ($lastGroupMessageJSON['type'] == 'text') {
			$lastGroupMessage = $lastGroupMessageJSON['message'];
		} else if ($lastGroupMessageJSON['type'] == 'image') {
			$lastGroupMessage = '[Gambar]';
		} else if ($lastGroupMessageJSON['type'] == 'sticker') {
			$lastGroupMessage = '[Stiker]';
		}
		array_push($chats, array(
			'user_id' => 0,
			'name' => 'Chat Room',
			'last_message' => $lastGroupMessage,
			'profile_picture' => ''
		));
		$privateChats = $this->db->query("SELECT * FROM `private_chats` WHERE `sender_user_id`=" . $userID . " OR `receiver_user_id`=" . $userID . " ORDER BY `date` DESC LIMIT " . $start . "," . $length)->result_array();
		for ($i=0; $i<sizeof($privateChats); $i++) {
			$privateChat = $privateChats[$i];
			$opponentUserID = 0;
			if (intval($privateChat['sender_user_id']) == $userID) {
				$opponentUserID = intval($privateChat['receiver_user_id']);
			} else if (intval($privateChat['receiver_user_id']) == $userID) {
				$opponentUserID = intval($privateChat['sender_user_id']);
			}
			$opponentName = "";
			$lastMessage = "";
			$lastMessageType = "";
			$opponent = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $opponentUserID)->row_array();
			if ($opponent != null) {
				$opponentName = $opponent['name'];
			}
			$privateMessages = $this->db->query("SELECT * FROM `private_chat_messages` WHERE `uuid`='" . $privateChat['uuid'] . "' ORDER BY `date` DESC LIMIT 1")->result_array();
			if (sizeof($privateMessages) > 0) {
				$lastMessage = $privateMessages[0]['message'];
				$lastMessageType = $privateMessages[0]['type'];
				if ($privateMessages[0]['type'] == 'image') {
					$lastMessage = '[Gambar]';
				} else if ($privateMessages[0]['type'] == 'sticker') {
					$lastMessage = '[Stiker]';
				}
			}
			array_push($chats, array(
				'user_id' => $opponentUserID,
				'name' => $opponentName,
				'last_message' => $lastMessage,
				'last_message_type' => $lastMessageType,
				'profile_picture' => $opponent['profile_picture']
			));
		}
		echo json_encode($chats);
	}
}

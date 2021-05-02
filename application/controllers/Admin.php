<?php

class Admin extends CI_Controller {
	
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
	
	public function login() {
		$this->json_response();
		if (!$this->authorize()) return;
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$admins = $this->db->query("SELECT * FROM `admins` WHERE `email`='" . $email . "' AND `password`='" . $password . "'")->result_array();
		if (sizeof($admins) > 0) {
			$admin = $admins[0];
			$admin['response_code'] = 1;
			$admin['is_moderator'] = 0;
			echo json_encode($admin);
		} else {
			$moderators = $this->db->query("SELECT * FROM `moderators` WHERE `email`='" . $email . "' AND `password`='" . $password . "'")
				->result_array();
			if (sizeof($moderators) > 0) {
				$moderator = $moderators[0];
				$moderator['response_code'] = 1;
				$moderator['is_moderator'] = 1;
				echo json_encode($moderator);
			} else {
				echo json_encode(array(
					'response_code' => -1
				));
			}
		}
	}
	
	public function get_admins() {
		$this->json_response();
		if (!$this->authorize()) return;
		$admins = $this->db->query("SELECT * FROM `admins` ORDER BY `email`")->result_array();
		echo json_encode($admins);
	}
	
	public function get_moderators() {
		$this->json_response();
		if (!$this->authorize()) return;
		$moderators = $this->db->query("SELECT * FROM `moderators` ORDER BY `email`")->result_array();
		echo json_encode($moderators);
	}
	
	public function get_users() {
		$this->json_response();
		if (!$this->authorize()) return;
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$users = $this->db->query("SELECT * FROM `users` ORDER BY `name` DESC LIMIT " . $start . "," . $length)->result_array();
		echo json_encode($users);
	}
	
	public function search_users() {
		$this->json_response();
		if (!$this->authorize()) return;
		$query = $this->input->post('query');
		$users = $this->db->query("SELECT * FROM `users` WHERE `name` LIKE '%" . $query . "%' ORDER BY `name` DESC")->result_array();
		echo json_encode($users);
	}
	
	public function get_admin_by_id() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = $this->input->post('id');
		echo json_encode($this->db->query("SELECT * FROM `admins` WHERE `id`=" . $id)->row_array());
	}
	
	public function get_moderator_by_id() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = $this->input->post('id');
		echo json_encode($this->db->query("SELECT * FROM `moderators` WHERE `id`=" . $id)->row_array());
	}
	
	public function add_admin() {
		$this->json_response();
		if (!$this->authorize()) return;
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$admins = $this->db->query("SELECT * FROM `admins` WHERE `email`='" . $email . "'")->result_array();
		if (sizeof($admins) > 0) {
			echo json_encode(array(
				'response_code' => -1
			));
		} else {
			$this->db->insert('admins', array(
				'email' => $email,
				'password' => $password
			));
		}
	}
	
	public function add_moderator() {
		$this->json_response();
		if (!$this->authorize()) return;
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$moderators = $this->db->query("SELECT * FROM `moderators` WHERE `email`='" . $email . "'")->result_array();
		if (sizeof($moderators) > 0) {
			echo json_encode(array(
				'response_code' => -1
			));
		} else {
			$this->db->insert('moderators', array(
				'email' => $email,
				'password' => $password
			));
		}
	}
	
	public function update_admin() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = intval($this->input->post('id'));
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$emailChanged = intval($this->input->post('email_changed'));
		if ($emailChanged == 1) {
			$admins = $this->db->query("SELECT * FROM `admins` WHERE `email`='" . $email . "'")->result_array();
			if (sizeof($admins) > 0) {
				echo json_encode(array(
					'response_code' => -1
				));
				return;
			}
		}
		$this->db->where('id', $id);
		$this->db->update('admins', array(
			'email' => $email,
			'password' => $password
		));
		echo json_encode(array(
			'response_code' => 1
		));
	}
	
	public function update_moderator() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = intval($this->input->post('id'));
		$email = $this->input->post('email');
		$password = $this->input->post('password');
		$emailChanged = intval($this->input->post('email_changed'));
		if ($emailChanged == 1) {
			$moderators = $this->db->query("SELECT * FROM `moderators` WHERE `email`='" . $email . "'")->result_array();
			if (sizeof($moderators) > 0) {
				echo json_encode(array(
					'response_code' => -1
				));
				return;
			}
		}
		$this->db->where('id', $id);
		$this->db->update('moderators', array(
			'email' => $email,
			'password' => $password
		));
		echo json_encode(array(
			'response_code' => 1
		));
	}
	
	public function delete_admin() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = $this->input->post('id');
		$this->db->where('id', $id);
		$this->db->delete('admins');
	}
	
	public function delete_moderator() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = $this->input->post('id');
		$this->db->where('id', $id);
		$this->db->delete('moderators');
	}
	
	public function block_user() {
		$this->json_response();
		if (!$this->authorize()) return;
		$userID = $this->input->post('user_id');
		$this->db->where('id', $userID);
		$this->db->update('users', array(
			'blocked' => 1
		));
	}
	
	public function unblock_user() {
		$this->json_response();
		if (!$this->authorize()) return;
		$userID = $this->input->post('user_id');
		$this->db->where('id', $userID);
		$this->db->update('users', array(
			'blocked' => 0
		));
	}
	
	public function get_comments() {
		$this->json_response();
		if (!$this->authorize()) return;
		$videoID = intval($this->input->post('video_id'));
		$comments = $this->db->query("SELECT * FROM `comments` WHERE `video_id`=" . $videoID . " ORDER BY `date`")->result_array();
		for ($i=0; $i<sizeof($comments); $i++) {
			$comments[$i]['user'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $comments[$i]['user_id'])->row_array();
			$likeCount = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $comments[$i]['id'] . " AND `user_id`=" . $comments[$i]['user_id'])->num_rows();
			$comments[$i]['liked'] = $likeCount>0;
			$comments[$i]['like_count'] = $likeCount;
			$comments[$i]['comment_count'] = $this->db->query("SELECT * FROM `comments` WHERE `parent_comment_id`=" . $comments[$i]['id'])->num_rows();
			$stickerID = intval($comments[$i]['sticker_id']);
			if ($stickerID != 0) {
				$comments[$i]['sticker'] = $this->db->query("SELECT * FROM `stickers` WHERE `id`=" . $comments[$i]['sticker_id'])->row_array();
			}
			$parentCommentID = intval($comments[$i]['parent_comment_id']);
			if ($parentCommentID != 0) {
				$parentComment = $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $parentCommentID)->row_array();
				if ($parentComment != null) {
					$parentCommentUserID = intval($parentComment['user_id']);
					$comments[$i]['parent_comment_name'] = $this->db->query("SELECT * FROM `users` WHERE `id`=" . $parentCommentUserID)->row_array()['name'];
				}
			}
		}
		echo json_encode($comments);
	}
	
	public function get_all_comments() {
		$this->json_response();
		if (!$this->authorize()) return;
		$adminID = intval($this->input->post('admin_id'));
		$start = intval($this->input->post('start'));
		$length = intval($this->input->post('length'));
		$comments = $this->db->query("SELECT * FROM `comments` ORDER BY `date`")->result_array();
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
		}
		for ($i=0; $i<sizeof($allComments); $i++) {
			$allComments[$i]['likes'] = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $allComments[$i]['id'])->num_rows();
			$allComments[$i]['pinned'] = $this->db->query("SELECT * FROM `pinned_comments` WHERE `admin_id`=" . $adminID . " AND `comment_id`=" . $allComments[$i]['id'])->num_rows()>=1;
		}
		usort($allComments, function ($comment1, $comment2) {
    		return intval($comment2['likes']) <=> intval($comment1['likes']);
		});
		$allComments = array_slice($allComments, $start, $length);
		echo json_encode($allComments);
	}
	
	public function get_pinned_comments() {
		$this->json_response();
		if (!$this->authorize()) return;
		$adminID = intval($this->input->post('admin_id'));
		$pinnedComments = $this->db->query("SELECT * FROM `pinned_comments` WHERE `admin_id`=" . $adminID)->result_array();
		$comments = [];
		for ($i=0; $i<sizeof($pinnedComments); $i++) {
			array_push($comments, $this->db->query("SELECT * FROM `comments` WHERE `id`=" . $pinnedComments[$i]['comment_id'])->row_array());
		}
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
		}
		for ($i=0; $i<sizeof($allComments); $i++) {
			$allComments[$i]['likes'] = $this->db->query("SELECT * FROM `comment_likes` WHERE `comment_id`=" . $allComments[$i]['id'])->num_rows();
			$allComments[$i]['pinned'] = $this->db->query("SELECT * FROM `pinned_comments` WHERE `admin_id`=" . $adminID . " AND `comment_id`=" . $allComments[$i]['id'])->num_rows()>=1;
		}
		usort($allComments, function ($comment1, $comment2) {
    		return intval($comment2['likes']) <=> intval($comment1['likes']);
		});
		echo json_encode($allComments);
	}
	
	public function delete_comment() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = $this->input->post('id');
		$this->db->where('id', $id);
		$this->db->delete('comments');
	}
	
	public function get_videos() {
		$this->json_response();
		if (!$this->authorize()) return;
		$videos = $this->db->query("SELECT * FROM `videos` ORDER BY `title`")->result_array();
		echo json_encode($videos);
	}
	
	public function add_sticker_category() {
		$this->json_response();
		if (!$this->authorize()) return;
		$name = $this->input->post('name');
		$config = array(
			'upload_path' => './userdata/',
			'allowed_types' => "*",
			'overwrite' => TRUE
		);
		$this->load->library('upload', $config);
		if ($this->upload->do_upload('file')) {
			$this->db->insert('stickers', array(
				'category_icon' => $this->upload->data()['file_name'],
				'category' => $name,
				'img' => $this->upload->data()['file_name']
			));
		}
	}
	
	public function add_sticker() {
		$this->json_response();
		if (!$this->authorize()) return;
		$category = $this->input->post('category');
		$stickers = $this->db->query("SELECT * FROM `stickers` WHERE `category`='" . $category . "'")->result_array();
		$config = array(
			'upload_path' => './userdata/',
			'allowed_types' => "*",
			'overwrite' => TRUE
		);
		$this->load->library('upload', $config);
		if ($this->upload->do_upload('file')) {
			$this->db->insert('stickers', array(
				'category_icon' => $stickers[0]['category_icon'],
				'category' => $category,
				'img' => $this->upload->data()['file_name']
			));
		}
	}
	
	public function delete_category() {
		$this->json_response();
		if (!$this->authorize()) return;
		$category = $this->input->post('category');
		$this->db->query("DELETE FROM `stickers` WHERE `category`='" . $category . "'");
	}
	
	public function delete_sticker() {
		$this->json_response();
		if (!$this->authorize()) return;
		$id = $this->input->post('id');
		$this->db->query("DELETE FROM `stickers` WHERE `id`=" . $id);
	}
	
	public function pin_comment() {
		$adminID = intval($this->input->post('admin_id'));
		$commentID = intval($this->input->post('comment_id'));
		$this->db->insert('pinned_comments', array(
			'admin_id' => $adminID,
			'comment_id' => $commentID
		));
	}
	
	public function unpin_comment() {
		$adminID = intval($this->input->post('admin_id'));
		$commentID = intval($this->input->post('comment_id'));
		$this->db->query("DELETE FROM `pinned_comments` WHERE `admin_id`=" . $adminID . " AND `comment_id`=" . $commentID);
	}
}

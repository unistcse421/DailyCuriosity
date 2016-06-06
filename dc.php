<?php

ini_set("display_errors", 1);
error_reporting(~0);
require_once("db.php");

class dc_mysqli extends mysqli
{
	public function read_post($post_id)
	{
		$arr = array();
		$res = $this->query("SELECT paragraph_loc, MAX(paragraph_rev), MAX(paragraph_id) FROM paragraph_history WHERE post_id=" . $post_id . " GROUP BY paragraph_loc ORDER BY paragraph_loc ASC");
		
		while($row = $res->fetch_row())
		{
			$res2 = $this->query("SELECT paragraph_body, created FROM paragraph_archive WHERE paragraph_id=" . $row[2]);
			$row2 = $res2->fetch_row();
			$arr[] = array("id" => $row[0], "revision" => $row[1], "body" => $row2[0], "last_modified" => $row2[1]);
		}
		
		return $arr;
	}
	
	public function read_topic($topic_id)
	{
		$posts = array();
		$res = $this->query("SELECT post_id, position_id, post_name FROM post WHERE topic_id=" . $topic_id . " ORDER BY post_id DESC");
		
		while($row = $res->fetch_row())
		{
			$posts[] = array("id" => $row[0], "position" => $row[1], "title" => $row[2], "paragraphs" => $this->read_post($row[0]));
		}
		
		$positions = array();
		$res = $this->query("SELECT position_id, position_name, position_body FROM position WHERE topic_id=" . $topic_id);
		
		while($row = $res->fetch_row())
		{
			$res2 = $this->query("SELECT COUNT(*) FROM post WHERE position_id=" . $row[0]);
			$row2 = $res2->fetch_row();
			$positions[] = array("id" => $row[0], "title" => $row[1], "description" => $row[2], "count" => $row2[0]);
		}
		
		$res = $this->query("SELECT post_name FROM post WHERE post_id=" . $topic_id);
		$row = $res->fetch_row();
		return array("title" => $row[0], "paragraphs" => $this->read_post($topic_id), "posts" => $posts, "positions" => $positions);
	}
	
	public function get_history($post_id, $paragraph_id)
	{
		$arr = array();
		$res = $this->query("SELECT paragraph_rev, paragraph_id FROM paragraph_history WHERE post_id=" . $post_id . " AND paragraph_loc=" . $paragraph_id);
		
		while($row = $res->fetch_row())
		{
			$res2 = $this->query("SELECT paragraph_body, created FROM paragraph_archive WHERE paragraph_id=" . $row[1]);
			$row2 = $res2->fetch_row();
			$arr[] = array("revision" => $row[0], "body" => $row2[0], "last_modified" => $row2[1]);
		}
		
		return $arr;
	}
	
	public function get_paragraph($post_id, $paragraph_id, $paragraph_rev)
	{
		$res = $this->query($paragraph_rev ? "SELECT paragraph_id FROM paragraph_history WHERE post_id=" . $post_id . " AND paragraph_loc=" . $paragraph_id . " AND paragraph_rev=" . $paragraph_rev : "SELECT MAX(paragraph_id) FROM paragraph_history WHERE post_id=" . $post_id . " AND paragraph_loc=" . $paragraph_id);
		$row = $res->fetch_row();
		$res = $this->query("SELECT paragraph_body FROM paragraph_archive WHERE paragraph_id=" . $row[0]);
		$row = $res->fetch_row();
		return array("body" => $row[0]);
	}
	
	public function put_keywords($raw_id, $str)
	{
		preg_match_all("/\[#([^\]]+)\]/", $str, $matches, PREG_SET_ORDER);
		foreach($matches as $match)
		{
			$name = trim($match[1]);
			$res = $this->query("SELECT keyword_id FROM keyword WHERE keyword_name=\"" . $name . "\"");
			if($row = $res->fetch_row())
			{
				$this->query("INSERT INTO paragraph_has_keyword (paragraph_id, keyword_id) VALUES (" . $raw_id . ", " . $row[0] . ")");
			}
			else
			{
				$this->query("INSERT INTO keyword (keyword_name) VALUES (\"" . $name . "\")");
				$this->query("INSERT INTO paragraph_has_keyword (paragraph_id, keyword_id) VALUES (" . $raw_id . ", " . $this->insert_id . ")");
			}
		}
	}
	
	public function create_raw_post($topic_id, $position_id, $title, $text)
	{
		$this->query("INSERT INTO post (topic_id, user_id, position_id, post_name) VALUES (" . $topic_id . ", 0, " . $position_id . ", \"" . $title . "\")");
		$post_id = $this->insert_id;
		
		$paragraphs = explode("\n\n", $text);
		$i = 1;
		foreach($paragraphs as $str)
		{
			$str = trim($str);
			if(strlen($str))
			{
				$this->query("INSERT INTO paragraph_archive (paragraph_body) VALUES (\"" . $str . "\")");
				$raw_id = $this->insert_id;
				$this->put_keywords($raw_id, $str);
				$this->query("INSERT INTO paragraph_history (post_id, paragraph_loc, paragraph_rev, paragraph_id) VALUES (" . $post_id . ", " . $i++ . ", 1, " . $raw_id . ")");
			}
		}
		
		return $post_id;
	}
	
	public function get_position($topic_id, $post_id, $text)
	{
		$position_id = 0;
		
		if($post_id)
		{
			$res = $this->query("SELECT topic_id, position_id FROM post WHERE post_id=" . $post_id);
			$row = $res->fetch_row();
			if(intval($row[1]))
			{
				return 0;
			}
			else
			{
				$topic_id = $row[0];
			}
		}
		
		preg_match_all("/\[@([^\]]+)\]/", $text, $matches, PREG_SET_ORDER);
		foreach($matches as $match)
		{
			$name = trim($match[1]);
			$res = $this->query("SELECT position_id FROM position WHERE topic_id=" . $topic_id . " AND position_name=\"" . $name . "\"");
			if($row = $res->fetch_row())
			{
				$position_id = $row[0];
				break;
			}
		}
		
		return $position_id;
	}
	
	public function create_post($topic_id, $title, $text)
	{
		$position_id = $this->get_position($topic_id, 0, $text);		
		return array("id" => $this->create_raw_post($topic_id, $position_id, $title, $text));
	}
	
	public function update_positions($topic_id, $text)
	{
		preg_match_all("/\[@([^:\]]+):([^\]]+)\]/", $text, $matches, PREG_SET_ORDER);
		foreach($matches as $match)
		{
			$name = trim($match[1]);
			$body = trim($match[2]);
			$res = $this->query("SELECT position_id FROM position WHERE topic_id=" . $topic_id . " AND position_name=\"" . $name . "\"");
			if($row = $res->fetch_row())
			{
				$position_id = $row[0];
				$this->query("UPDATE position SET position_body=\"". $body . "\" WHERE position_id=" . $position_id);
			}
			else
			{
				$this->query("INSERT INTO position (position_name, position_body, topic_id) VALUES (\"" . $name . "\", \"" . $body . "\", " . $topic_id . ")");
			}
		}
	}
	
	public function create_topic($title, $text)
	{
		$topic_id = $this->create_raw_post(0, 0, $title, $text);
		$this->update_positions($topic_id, $text);
		return array("id" => $topic_id);
	}
	
	public function modify_post($post_id, $paragraph_id, $text)
	{
		$this->query("INSERT INTO paragraph_archive (paragraph_body) VALUES (\"" . $text . "\")");
		$raw_id = $this->insert_id;
		$this->put_keywords($raw_id, $text);
		$res = $this->query("SELECT MAX(paragraph_rev) FROM paragraph_history WHERE post_id=" . $post_id . " AND paragraph_loc=" . $paragraph_id);
		$row = $res->fetch_row();
		$revision = intval($row[0]) + 1;
		$this->query("INSERT INTO paragraph_history (post_id, paragraph_loc, paragraph_rev, paragraph_id) VALUES (" . $post_id . ", " . $paragraph_id . ", " . $revision . ", " . $raw_id . ")");
		
		if($position_id = $this->get_position(0, $post_id, $text))
		{
			$this->query("UPDATE post SET position_id=" . $position_id . " WHERE post_id=" . $post_id);
		}
	}
	
	public function modify_topic($topic_id, $paragraph_id, $text)
	{
		$this->modify_post($topic_id, $paragraph_id, $text);
		$this->update_positions($topic_id, $text);
	}
	
	public function search_post($keyword)
	{
		$arr = array();
		$res = $this->query("SELECT keyword_id FROM keyword WHERE keyword_name=\"" . $keyword . "\"");
		$row = $res->fetch_row();
		$res = $this->query("SELECT paragraph_id FROM paragraph_has_keyword WHERE keyword_id=" . $row[0] . " ORDER BY paragraph_id DESC");
		
		while($row = $res->fetch_row())
		{
			$res2 = $this->query("SELECT post_id, paragraph_loc, paragraph_rev FROM paragraph_history WHERE paragraph_id=" . $row[0]);
			$row2 = $res2->fetch_row();
			
			$res2 = $this->query("SELECT paragraph_id FROM paragraph_history WHERE post_id=" . $row2[0] . " AND paragraph_loc=" . $row2[1] . " AND paragraph_rev>" . $row2[2]);
			if($res2->fetch_row())
			{
				continue;
			}
			
			$post_id = $row2[0];
			$paragraph_id = $row2[1];
			$paragraph_rev = $row2[2];
			
			$res2 = $this->query("SELECT paragraph_body, created FROM paragraph_archive WHERE paragraph_id=" . $row[0]);
			$row2 = $res2->fetch_row();
			$arr[] = array("id" => $paragraph_id, "revision" => $paragraph_rev, "body" => $row2[0], "post_id" => $post_id, "last_modified" => $row2[1]);
		}
		
		return $arr;
	}
}

$data = json_decode(file_get_contents("php://input"), true);
$arr = array();
$req_type = $data["type"];
$db = new dc_mysqli("p:" . $db_host, $db_username, $db_password, $db_database);

if($req_type == "read_topic")
{
	$topic_id = intval($data["topic_id"]);
	$arr = $db->read_topic($topic_id);
}
else if($req_type == "history")
{
	$post_id = intval($data["post_id"]);
	$paragraph_id = intval($data["paragraph_id"]);
	$arr = $db->get_history($post_id, $paragraph_id);
}
else if($req_type == "get")
{
	$post_id = intval($data["post_id"]);
	$paragraph_id = intval($data["paragraph_id"]);
	$arr = $db->get_paragraph($post_id, $paragraph_id, 0);
}
else if($req_type == "get_revision")
{
	$post_id = intval($data["post_id"]);
	$paragraph_id = intval($data["paragraph_id"]);
	$paragraph_rev = intval($data["revision"]);
	$arr = $db->get_paragraph($post_id, $paragraph_id, $paragraph_rev);
}
else if($req_type == "create_post")
{
	$topic_id = intval($data["topic_id"]);
	$title = $data["title"];
	$text = $data["text"];
	$arr = $db->create_post($topic_id, $title, $text);
}
else if($req_type == "create_topic")
{
	$title = $data["title"];
	$text = $data["text"];
	$arr = $db->create_topic($title, $text);
}
else if($req_type == "modify_post")
{
	$post_id = intval($data["post_id"]);
	$paragraph_id = intval($data["paragraph_id"]);
	$text = $data["text"];
	$arr = $db->modify_post($post_id, $paragraph_id, $text);
}
else if($req_type == "modify_topic")
{
	$topic_id = intval($data["topic_id"]);
	$paragraph_id = intval($data["paragraph_id"]);
	$text = $data["text"];
	$arr = $db->modify_topic($topic_id, $paragraph_id, $text);
}
else if($req_type == "search")
{
	$keyword = $data["keyword"];
	$arr = $db->search_post($keyword);
}

$db->close();
echo json_encode($arr);

?>

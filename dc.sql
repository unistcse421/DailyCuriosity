DROP TABLE IF EXISTS post, paragraph_archive, paragraph_history, keyword, position, paragraph_has_keyword;

CREATE TABLE IF NOT EXISTS post (
	post_id INT NOT NULL AUTO_INCREMENT,
	topic_id INT NOT NULL,
	user_id INT NOT NULL,
	position_id INT NOT NULL,
	post_name VARCHAR(512) NOT NULL,
	PRIMARY KEY (post_id)
);

CREATE TABLE IF NOT EXISTS paragraph_archive (
	paragraph_id INT NOT NULL AUTO_INCREMENT,
	paragraph_body VARCHAR(16384) NOT NULL,
	created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (paragraph_id)
);

CREATE TABLE IF NOT EXISTS paragraph_history (
	post_id INT NOT NULL,
	paragraph_loc INT NOT NULL,
	paragraph_rev INT NOT NULL,
	paragraph_id INT NOT NULL,
	PRIMARY KEY (post_id, paragraph_loc, paragraph_rev)
);

CREATE TABLE IF NOT EXISTS keyword (
	keyword_id INT NOT NULL AUTO_INCREMENT,
	keyword_name VARCHAR(512) NOT NULL,
	PRIMARY KEY (keyword_id)
);

CREATE TABLE IF NOT EXISTS position (
	position_id INT NOT NULL AUTO_INCREMENT,
	position_name VARCHAR(512) NOT NULL,
	position_body VARCHAR(16384) NOT NULL,
	topic_id INT NOT NULL,
	PRIMARY KEY (position_id)
);

CREATE TABLE IF NOT EXISTS paragraph_has_keyword (
	paragraph_id INT NOT NULL,
	keyword_id INT NOT NULL,
	PRIMARY KEY (paragraph_id, keyword_id)
);

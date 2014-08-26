CREATE TABLE championship (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, url_logo VARCHAR(255) NOT NULL, league_id INT NOT NULL, rule_id INT NOT NULL, game_id INT NOT NULL, user_id INT NOT NULL, date_start DATE, date_end DATE, hotlap_ip VARCHAR(255), description TEXT, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, INDEX league_id_idx (league_id), INDEX user_id_idx (user_id), INDEX rule_id_idx (rule_id), INDEX game_id_idx (game_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE championship_race (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, race_number INT NOT NULL, race_date DATETIME NOT NULL, race_laps INT, track_id INT NOT NULL, championship_id INT NOT NULL, description TEXT, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, INDEX track_id_idx (track_id), INDEX championship_id_idx (championship_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE championship_team (id BIGINT AUTO_INCREMENT, championship_id INT NOT NULL, team_id INT NOT NULL, name VARCHAR(255) NOT NULL, url_logo VARCHAR(255) NOT NULL, url_logo_car VARCHAR(255) NOT NULL, team_number INT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, INDEX championship_id_idx (championship_id), INDEX team_id_idx (team_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE championship_team_driver (id BIGINT AUTO_INCREMENT, championship_id INT NOT NULL, team_id INT NOT NULL, user_id INT NOT NULL, team_role_id INT NOT NULL, driver_number INT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, INDEX championship_id_idx (championship_id), INDEX team_id_idx (team_id), INDEX user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE comment (id BIGINT AUTO_INCREMENT, user_id BIGINT NOT NULL, post_id BIGINT NOT NULL, title VARCHAR(255), content TEXT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, status INT NOT NULL, parent_comment_id BIGINT, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE content_type (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE country (id INT AUTO_INCREMENT, nativename VARCHAR(255) NOT NULL, englishname VARCHAR(255) NOT NULL, abbreviation VARCHAR(5) NOT NULL, urlimageround VARCHAR(255) NOT NULL, urlimageglossywave VARCHAR(255) NOT NULL, datecreate DATETIME NOT NULL, dateedit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE event (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, date_event DATETIME NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, url_event VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE league (id INT AUTO_INCREMENT, name VARCHAR(120) NOT NULL, url_logo VARCHAR(255) NOT NULL, description TEXT NOT NULL, user_id INT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, INDEX user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE migration_version (id BIGINT AUTO_INCREMENT, version INT, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE post (id INT AUTO_INCREMENT, user_id INT NOT NULL, post_type_id INT NOT NULL, content_type_id INT NOT NULL, name VARCHAR(255) NOT NULL, preview TEXT NOT NULL, text TEXT NOT NULL, image VARCHAR(255) NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, views INT NOT NULL, publish INT NOT NULL, publish_to_slider INT NOT NULL, last_ip VARCHAR(50) NOT NULL, INDEX user_id_idx (user_id), INDEX content_type_id_idx (content_type_id), INDEX post_type_id_idx (post_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE post_type (id INT AUTO_INCREMENT, name VARCHAR(100) NOT NULL, description TEXT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE privilege (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, resource_id INT NOT NULL, description TEXT, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE resource (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, module VARCHAR(255) NOT NULL, controller VARCHAR(255) NOT NULL, parent_resource_id INT, description TEXT, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE resources_access (id INT AUTO_INCREMENT, role_id INT, resource_id INT, privilege_id INT, allow TINYINT, date_create DATETIME, date_edit DATETIME, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE role (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, parent_role_id INT, description TEXT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE schema_version (version BIGINT, PRIMARY KEY(version)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE team (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE track (id INT AUTO_INCREMENT, name VARCHAR(255) NOT NULL, track_year BIGINT NOT NULL, track_length VARCHAR(125), url_track_logo VARCHAR(255), url_track_scheme VARCHAR(255), city_id INT, country_id INT, description TEXT, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE user (id INT AUTO_INCREMENT, login VARCHAR(30) NOT NULL, password VARCHAR(40) NOT NULL, date_last_activity DATETIME, last_login_ip VARCHAR(255), code_activate VARCHAR(15), code_restore_pass VARCHAR(15), enable INT NOT NULL, user_role_id INT NOT NULL, email VARCHAR(100) NOT NULL, name VARCHAR(250), surname VARCHAR(250), country_id INT, lang VARCHAR(3), city VARCHAR(100), birthday DATE, avatar_type INT, avatar_load VARCHAR(255), avatar_link VARCHAR(255), avatar_gravatar_email VARCHAR(255), skype VARCHAR(255), icq VARCHAR(20), gtalk VARCHAR(100), www VARCHAR(255), vk VARCHAR(255), fb VARCHAR(255), tw VARCHAR(255), gp VARCHAR(255), date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, about TEXT, INDEX country_id_idx (country_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE user_chat (id INT AUTO_INCREMENT, user_id INT NOT NULL, message TEXT NOT NULL, date_create DATETIME NOT NULL, date_edit DATETIME NOT NULL, INDEX user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
CREATE TABLE user_role (id BIGINT AUTO_INCREMENT, user_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 ENGINE = INNODB;
ALTER TABLE championship ADD CONSTRAINT championship_user_id_user_id FOREIGN KEY (user_id) REFERENCES user(id);
ALTER TABLE championship ADD CONSTRAINT championship_rule_id_post_id FOREIGN KEY (rule_id) REFERENCES post(id);
ALTER TABLE championship ADD CONSTRAINT championship_league_id_league_id FOREIGN KEY (league_id) REFERENCES league(id);
ALTER TABLE championship ADD CONSTRAINT championship_game_id_post_id FOREIGN KEY (game_id) REFERENCES post(id);
ALTER TABLE championship_race ADD CONSTRAINT championship_race_track_id_track_id FOREIGN KEY (track_id) REFERENCES track(id);
ALTER TABLE championship_race ADD CONSTRAINT championship_race_championship_id_championship_id FOREIGN KEY (championship_id) REFERENCES championship(id);
ALTER TABLE championship_team ADD CONSTRAINT championship_team_team_id_team_id FOREIGN KEY (team_id) REFERENCES team(id);
ALTER TABLE championship_team ADD CONSTRAINT championship_team_championship_id_championship_id FOREIGN KEY (championship_id) REFERENCES championship(id);
ALTER TABLE championship_team_driver ADD CONSTRAINT championship_team_driver_user_id_user_id FOREIGN KEY (user_id) REFERENCES user(id);
ALTER TABLE championship_team_driver ADD CONSTRAINT championship_team_driver_team_id_team_id FOREIGN KEY (team_id) REFERENCES team(id);
ALTER TABLE championship_team_driver ADD CONSTRAINT championship_team_driver_championship_id_championship_id FOREIGN KEY (championship_id) REFERENCES championship(id);
ALTER TABLE league ADD CONSTRAINT league_user_id_user_id FOREIGN KEY (user_id) REFERENCES user(id);
ALTER TABLE post ADD CONSTRAINT post_user_id_user_id FOREIGN KEY (user_id) REFERENCES user(id);
ALTER TABLE post ADD CONSTRAINT post_post_type_id_post_type_id FOREIGN KEY (post_type_id) REFERENCES post_type(id);
ALTER TABLE post ADD CONSTRAINT post_content_type_id_content_type_id FOREIGN KEY (content_type_id) REFERENCES content_type(id);
ALTER TABLE user ADD CONSTRAINT user_country_id_country_id FOREIGN KEY (country_id) REFERENCES country(id);
ALTER TABLE user_chat ADD CONSTRAINT user_chat_user_id_user_id FOREIGN KEY (user_id) REFERENCES user(id);
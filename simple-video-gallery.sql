DROP TABLE IF EXISTS [video];

CREATE TABLE `[video]` (
  `video_id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(100) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `duration` int(11) NOT NULL,
  `image` text NOT NULL,
  `link` text NOT NULL,
  `playlist_id` int(11) NOT NULL,
  `featured` int(11) NOT NULL,
  `date_added` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`video_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS [playlist];

CREATE TABLE `[playlist]` (
  `playlist_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  PRIMARY KEY (`playlist_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


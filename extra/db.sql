CREATE TABLE `discreddit` (
  `discord_id` bigint(32) NOT NULL,
  `reddit_username` varchar(64) NOT NULL,
  `created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `discreddit`
  ADD PRIMARY KEY (`discord_id`),
  ADD UNIQUE KEY `reddit_username` (`reddit_username`);
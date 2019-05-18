ALTER TABLE comments ADD COLUMN is_top tinyint(4) unsigned NOT NULL DEFAULT 0 COMMENT '评论置顶';
ALTER TABLE comments ADD INDEX idx_ttid(`torrent`,`is_top`,`id`);
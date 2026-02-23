START TRANSACTION;

SET @marker := '[[STATS_TEST]]';

DELETE FROM comment WHERE content LIKE CONCAT(@marker, '%');
DELETE FROM resource WHERE title LIKE CONCAT(@marker, '%');
DELETE FROM `user` WHERE email LIKE 'stats.test.%@example.com';
DELETE FROM share WHERE channel LIKE CONCAT(@marker, '%');
DELETE FROM user_resource_state WHERE started_at >= '2099-01-01 00:00:00';

COMMIT;

SELECT
    (SELECT COUNT(*) FROM resource WHERE title LIKE CONCAT(@marker, '%')) AS marker_resources,
    (SELECT COUNT(*) FROM comment WHERE content LIKE CONCAT(@marker, '%')) AS marker_comments,
    (SELECT COUNT(*) FROM share WHERE channel LIKE CONCAT(@marker, '%')) AS marker_shares,
    (SELECT COUNT(*) FROM `user` WHERE email LIKE 'stats.test.%@example.com') AS marker_users,
    (SELECT COUNT(*) FROM user_resource_state WHERE started_at >= '2099-01-01 00:00:00') AS marker_states;

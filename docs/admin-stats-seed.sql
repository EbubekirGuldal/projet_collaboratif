START TRANSACTION;

SET @marker := '[[STATS_TEST]]';

-- Clean previous test data (safe to re-run)
DELETE FROM comment WHERE content LIKE CONCAT(@marker, '%');
DELETE FROM resource WHERE title LIKE CONCAT(@marker, '%');
DELETE FROM `user` WHERE email LIKE 'stats.test.%@example.com';
DELETE FROM share WHERE channel LIKE CONCAT(@marker, '%');
DELETE FROM user_resource_state WHERE started_at >= '2099-01-01 00:00:00';

-- Resources (+4)
INSERT INTO resource (
    title,
    content,
    external_url,
    published_at,
    created_at,
    updated_at,
    image,
    video,
    resource_status,
    visibility_status,
    shares_count
) VALUES
    ('[[STATS_TEST]] Ressource A', 'Contenu test A', 'https://example.com/a', NOW(), NOW(), NOW(), NULL, NULL, 'Publiee', 'Public', 0),
    ('[[STATS_TEST]] Ressource B', 'Contenu test B', 'https://example.com/b', NOW(), NOW(), NOW(), NULL, NULL, 'Publiee', 'Public', 0),
    ('[[STATS_TEST]] Ressource C', 'Contenu test C', 'https://example.com/c', NOW(), NOW(), NOW(), NULL, NULL, 'Brouillon', 'Prive', 0),
    ('[[STATS_TEST]] Ressource D', 'Contenu test D', 'https://example.com/d', NOW(), NOW(), NOW(), NULL, NULL, 'Archivee', 'Prive', 0);

-- Comments (+4)
INSERT INTO comment (content, created_at, status, updated_at, resource_id)
SELECT '[[STATS_TEST]] Comment 1', NOW(), 'Publie', NULL, id
FROM resource WHERE title = '[[STATS_TEST]] Ressource A' LIMIT 1;

INSERT INTO comment (content, created_at, status, updated_at, resource_id)
SELECT '[[STATS_TEST]] Comment 2', NOW(), 'Publie', NULL, id
FROM resource WHERE title = '[[STATS_TEST]] Ressource A' LIMIT 1;

INSERT INTO comment (content, created_at, status, updated_at, resource_id)
SELECT '[[STATS_TEST]] Comment 3', NOW(), 'Publie', NULL, id
FROM resource WHERE title = '[[STATS_TEST]] Ressource B' LIMIT 1;

INSERT INTO comment (content, created_at, status, updated_at, resource_id)
SELECT '[[STATS_TEST]] Comment 4', NOW(), 'En attente', NULL, id
FROM resource WHERE title = '[[STATS_TEST]] Ressource C' LIMIT 1;

-- Shares (+5)
INSERT INTO share (channel, created_at) VALUES
    ('[[STATS_TEST]] whatsapp', NOW()),
    ('[[STATS_TEST]] email', NOW()),
    ('[[STATS_TEST]] slack', NOW()),
    ('[[STATS_TEST]] teams', NOW()),
    ('[[STATS_TEST]] linkedin', NOW());

-- Users (+3, with +2 active)
INSERT INTO `user` (
    email,
    roles,
    password,
    username,
    last_name,
    first_name,
    is_verified,
    is_active,
    created_at,
    updated_at
) VALUES
    ('stats.test.active1@example.com', '["ROLE_USER"]', 'test-password-hash', 'stats_active_1', 'Test', 'Active1', 1, 1, NOW(), NOW()),
    ('stats.test.active2@example.com', '["ROLE_USER"]', 'test-password-hash', 'stats_active_2', 'Test', 'Active2', 1, 1, NOW(), NOW()),
    ('stats.test.inactive1@example.com', '["ROLE_USER"]', 'test-password-hash', 'stats_inactive_1', 'Test', 'Inactive1', 1, 0, NOW(), NOW());

-- User resource states
-- exploited(true): 3 rows, favorite(true): 2 rows
INSERT INTO user_resource_state (
    is_favorite,
    is_exploited,
    is_saved_for_later,
    started_at,
    completed_at,
    last_interaction_at
) VALUES
    (1, 1, 0, '2099-01-01 10:00:00', '2099-01-02 10:00:00', '2099-01-02 10:00:00'),
    (1, 1, 0, '2099-01-01 11:00:00', '2099-01-03 10:00:00', '2099-01-03 10:00:00'),
    (0, 1, 0, '2099-01-01 12:00:00', NULL, '2099-01-04 10:00:00'),
    (0, 0, 1, '2099-01-01 13:00:00', NULL, '2099-01-05 10:00:00');

COMMIT;

-- Quick check of inserted markers
SELECT
    (SELECT COUNT(*) FROM resource WHERE title LIKE CONCAT(@marker, '%')) AS marker_resources,
    (SELECT COUNT(*) FROM comment WHERE content LIKE CONCAT(@marker, '%')) AS marker_comments,
    (SELECT COUNT(*) FROM share WHERE channel LIKE CONCAT(@marker, '%')) AS marker_shares,
    (SELECT COUNT(*) FROM `user` WHERE email LIKE 'stats.test.%@example.com') AS marker_users,
    (SELECT COUNT(*) FROM user_resource_state WHERE started_at >= '2099-01-01 00:00:00') AS marker_states;

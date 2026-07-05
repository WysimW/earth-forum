INSERT INTO univers (name, description, slug, created_at) VALUES
('DC Comics', 'Univers DC : Superman, Batman, Wonder Woman, etc.', 'dc', NOW()),
('Marvel', 'Univers Marvel : Spider-Man, Iron Man, Thor, X-Men, etc.', 'marvel', NOW());

INSERT INTO `user` (email, roles, password, pseudo, avatar, created_at, filter_non_participating_messages, can_create_faction, can_create_rp_forum, is_active) VALUES
('dupezthomas@gmail.com', '["ROLE_SUPER_ADMIN"]', '$2y$13$4qglVgTYzdmIiAIxuHk4YulMRk6X.FzYb7cbaib/UZt1G7K3kHK26', 'Thomas', NULL, NOW(), 0, 1, 1, 1);

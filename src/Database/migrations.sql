-- Some changes will need to be made to get this to work on Supabase

-- Users
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL,
    email         VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(80)  NULL,
    bio           TEXT         NULL,
    created_at    DATETIME     DEFAULT NOW(),
    updated_at    DATETIME     DEFAULT NOW() ON UPDATE NOW(),
    UNIQUE INDEX idx_username (username),
    UNIQUE INDEX idx_email    (email),
    UNIQUE INDEX idx_display_name (display_name),
    FULLTEXT  INDEX ft_display_name (display_name)
);

-- Snippets
CREATE TABLE IF NOT EXISTS snippets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(255) NOT NULL,
    description TEXT         NULL,
    code        LONGTEXT     NOT NULL,
    language    VARCHAR(50)  NOT NULL,
    visibility  ENUM('public','private') NOT NULL DEFAULT 'private',
    anonymous   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     DEFAULT NOW(),
    updated_at  DATETIME     DEFAULT NOW() ON UPDATE NOW(),
    INDEX       idx_language   (language),
    INDEX       idx_visibility (visibility),
    INDEX       idx_created_at (created_at),
    FULLTEXT INDEX ft_snippets (title, description, code),
    CONSTRAINT fk_snippets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tags
CREATE TABLE IF NOT EXISTS tags (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    name       VARCHAR(80) NOT NULL,
    created_at DATETIME    DEFAULT NOW(),
    INDEX     idx_tag_name (name),
    FULLTEXT  INDEX ft_tag_name (name),
    CONSTRAINT fk_tags_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Snippet Tags
CREATE TABLE IF NOT EXISTS snippet_tags (
    snippet_id INT UNSIGNED NOT NULL,
    tag_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (snippet_id, tag_id),
    CONSTRAINT fk_snippet_tags_snippet FOREIGN KEY (snippet_id) REFERENCES snippets(id) ON DELETE CASCADE,
    CONSTRAINT fk_snippet_tags_tag     FOREIGN KEY (tag_id)     REFERENCES tags(id)     ON DELETE CASCADE
);

-- Snippet Links
CREATE TABLE IF NOT EXISTS snippet_links (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    snippet_id        INT UNSIGNED  NOT NULL,
    linked_snippet_id INT UNSIGNED  NOT NULL,
    label             VARCHAR(100)  NULL,
    created_at        DATETIME      DEFAULT NOW(),
    CONSTRAINT fk_snippet_links_snippet        FOREIGN KEY (snippet_id)        REFERENCES snippets(id) ON DELETE CASCADE,
    CONSTRAINT fk_snippet_links_linked_snippet FOREIGN KEY (linked_snippet_id) REFERENCES snippets(id) ON DELETE CASCADE
);

-- Follows
CREATE TABLE IF NOT EXISTS follows (
    follower_id  INT UNSIGNED NOT NULL,
    following_id INT UNSIGNED NOT NULL,
    created_at   DATETIME     DEFAULT NOW(),
    PRIMARY KEY (follower_id, following_id),
    CONSTRAINT fk_follows_follower  FOREIGN KEY (follower_id)  REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_follows_following FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password Resets
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    token      VARCHAR(64)  NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     DEFAULT NOW(),
    INDEX        idx_pr_email (email),
    UNIQUE INDEX idx_pr_token (token)
);
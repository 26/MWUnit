CREATE TABLE /*_*/mwunit_content (
    content TEXT,
    article_id INT UNSIGNED NOT NULL,
    test_name  VARCHAR(255) NOT NULL,
    PRIMARY KEY (article_id, test_name),
    FOREIGN KEY (article_id, test_name) REFERENCES mwunit_tests(article_id, test_name)
);
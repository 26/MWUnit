CREATE TABLE /*_*/mwunit_tests (
    article_id INT UNSIGNED NOT NULL,
    test_group VARCHAR(255) NOT NULL,
    test_name  VARCHAR(255) NOT NULL,
    covers     VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (article_id, test_name)
);
CREATE TABLE /*_*/mwunit_attributes (
    attribute_name VARCHAR(255) NOT NULL,
    attribute_value VARCHAR(512),
    article_id INT UNSIGNED NOT NULL,
    test_name  VARCHAR(255) NOT NULL,
    PRIMARY KEY (attribute_name, article_id, test_name),
    FOREIGN KEY (article_id, test_name) REFERENCES mwunit_tests(article_id, test_name)
);
CREATE TABLE tx_gayataxonomy_domain_model_vocabulary
(
    title       varchar(255)  DEFAULT '' NOT NULL,
    slug        varchar(255)  DEFAULT '' NOT NULL,
    description varchar(2000) DEFAULT '' NOT NULL,

    KEY         idx_slug(slug)
);

CREATE TABLE tx_gayataxonomy_domain_model_term
(
    title       varchar(255)  DEFAULT '' NOT NULL,
    description varchar(2000) DEFAULT '' NOT NULL,
    vocabulary  int (11) DEFAULT '0' NOT NULL,
    parent      int (11) DEFAULT '0' NOT NULL,
    items       int (11) DEFAULT '0' NOT NULL,

    KEY         idx_vocabulary(vocabulary)
);

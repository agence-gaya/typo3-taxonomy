CREATE TABLE tx_taxonomy_domain_model_vocabulary
(
    title       varchar(255)  DEFAULT '' NOT NULL,
    slug        varchar(255)  DEFAULT '' NOT NULL,
    description varchar(2000) DEFAULT '' NOT NULL,

    KEY         idx_slug(slug)
);

CREATE TABLE tx_taxonomy_domain_model_term
(
    title       varchar(255)  DEFAULT '' NOT NULL,
    description varchar(2000) DEFAULT '' NOT NULL,
    vocabulary  int (11) DEFAULT '0' NOT NULL,
    parent      int (11) DEFAULT '0' NOT NULL,
    items       int (11) DEFAULT '0' NOT NULL,

    KEY         idx_vocabulary(vocabulary)
);

CREATE TABLE tx_taxonomy_domain_model_term_record_mm
(
    -- We have to require those fields explicitely, until the dynamic TCA requires it
    -- with the first call to TaxonomyTcaUtility::addTaxonomy*
    uid_local   int unsigned DEFAULT '0' NOT NULL,
    uid_foreign int unsigned DEFAULT '0' NOT NULL,
    tablenames  varchar(64) DEFAULT '' NOT NULL,
    fieldname   varchar(64) DEFAULT '' NOT NULL,

    PRIMARY KEY (uid_local, uid_foreign, tablenames, fieldname)
);

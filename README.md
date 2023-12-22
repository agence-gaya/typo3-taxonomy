# ext:taxonomy

Taxonomy extension for TYPO3 CMS

## Installation

composer require agence-gaya/typo3-taxonomy

## Main configuration

Create a global storage folder for your taxonomy in where you will create your vocabularies.

Terms can also be stored in this storage folder, or stored in any other storage folder of your installation.

```
Root
    Taxonomy <-- vocabularies and global terms are stored here
    Site A
        Taxonomy <-- terms of Site B are stored here
    Site B
        Taxonomy <-- terms of Site B are stored here
```

In the Site configuration module, you need to configure for each site the PID of each vocabulary.

**Important**

- Each time you add a new Site, you will need to set up those fields for this new site.
- Each time you will add a new vocabulary, you will need to edit and save each Site configuration.

## TCA configuration

Two static methods allow to add either a `selectSingle` or `selectTree` field to a table.

    \GAYA\Taxonomy\Utility\TaxonomyTcaUtility::addTaxonomySingle(
        'pages',
        'my_taxonomy_field',
        'my_vocabulary_slug',
        (string) \TYPO3\CMS\Core\Domain\Repository\PageRepository::DOKTYPE_DEFAULT,
        // by default, field is added in a new Taxonomy tab
        '',
        // you can override the TCA config
        ['required' => true]
    );

    \GAYA\Taxonomy\Utility\TaxonomyTcaUtility::addTaxonomyTree(
        'pages',
        'my_other_taxonomy_field',
        'my_vocabulary_slug',
        // if no typesList given, the field will be added to every types
        '',
        // use the usual position definition to add the field where you want
        'after:title'
    );

**Notes**

- You can add several field for the same vocabulary on the same table: just name the table's field differently.

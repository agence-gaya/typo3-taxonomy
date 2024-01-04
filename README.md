# ext:taxonomy

Taxonomy extension for TYPO3 CMS

## Installation

composer require gaya/typo3-taxonomy

## Main configuration

1. Create a global "Taxonomy" storage folder and one or more vocabularies.

   > Vocabularies are global to the TYPO3 instance, only developpers can manage them.
   > 
   > Terms can be stored in the global storage folder, also it is best practice to store them into a Site, it will facilitate the permission setup.

2. Create a "Taxonomy" folder in each site.

3. In the Site configuration module, configure for each site the PID of each vocabulary.

Example setup: 
```
Root
    Taxonomy <-- vocabularies and global terms are stored here
    Site A
        Taxonomy <-- terms of Site B are stored here
    Site B
        Taxonomy <-- terms of Site B are stored here
```

Now you can create vocabularies and terms.

**Important**

- Each time you add a new Site, you will need to set up the Taxonomy tab in the Site configration.
- Each time you will add a new vocabulary, you will need to setup the Taxonomy tab for each Site configuration.

## TCA configuration

You will want to associate objects, like pages or news, to vocabularies' terms.

You will need to add at least one field per desired vocabulary in the TCA of your object.

Two static methods allow to add either a `selectSingle` or `selectTree` field.

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

After the TCA being configured, run the database upgrade through the Install tool.

**Notes**

- You can add several field for the same vocabulary on the same table: just name the fields differently.

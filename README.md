# ext:taxonomy

Taxonomy extension for TYPO3 CMS

## Installation

```
composer require gaya/typo3-taxonomy
```

## Site configuration

   > Vocabularies are global to the TYPO3 instance, only admins can manage them.
   > 
   > Terms of a vocabulary can be stored in a common folder or stored inside a Site.

1. (Optional) Create a global "Taxonomy" storage folder, set the field "Contains plugin" to "Taxonomy", and create one or more vocabularies inside it.

2. Create a "Taxonomy" folder in each site, and set the field "Contains plugin" to "Taxonomy"

3. In the Site configuration module, configure for each site the PID of each vocabulary.

**Note**

Since TYPO3 v12, it is not allowed anymore to translate records outside a Site.
If you plan to store common term and to translate them,
you will need to create and configure a virtual site for those common records to be translated.

**Important**

- Each time you add a new Site, you will need to set up the Taxonomy tab in the Site configration.
- Each time you will add a new vocabulary, you will need to setup the Taxonomy tab for each Site configuration.

**Example setups**

Single site, multi-lingual:
```
Root
   Taxonomy <-- vocabularies are stored here
   Site A
      Taxonomy <-- terms are stored and translated here
```

Multi-site, multi-lingual:
```
Root
   Global virtual site
      Taxonomy <-- vocabularies and global terms are stored and translated here
   Site B
      Taxonomy <-- terms of Site B are stored and translated here
   Site C: will use global scope
```

## TCA configuration

You will want to associate objects, like pages or news, to vocabularies' terms.

You will need to add at least one field per desired vocabulary in the TCA of your object.

This is an example to create a 2 fields in the pages table.
The code snippet goes into a file in your sitepackage or theme extension in the folder `Configuration/TCA/Overrides/`.
The file can have any name but it is good practice to name it according to the database table it relates to.
In this case this would be `pages.php`.

```php
GeneralUtility::makeInstance(Registry::class)
    ->configureTaxonomyField(
        (new TaxonomyConfiguration('pages', 'my_taxonomy_field', 'my_vocabulary_name'))
            // you can override label, which is by default the vocabulary name
            ->setLabel('My Vocabulary')
            // you can set the field description
            ->setDescription('Choose a term to associate with this content')
    );

GeneralUtility::makeInstance(Registry::class)
    ->configureTaxonomyField(
        (new TaxonomyConfiguration('pages', 'my_other_taxonomy_field', 'my_other_vocabulary_name'))
            ->setLabel('My Other Vocabulary')
            ->setDescription('Choose a term to associate with this content')
            // by default, renderType is selectSingle
            ->setRenderTypeTree()
            // by default, field is optional
            ->setRequired()
            // if no types list given, the field will be added to every types
            ->setTypes([ PageRepository::DOKTYPE_DEFAULT ])
            // by default, field is added in a Taxonomy tab, but you can
            // use the usual position definition to add the field where you want
            ->setPosition('after:title')
    );
```

After the TCA being configured, don't forget run the database upgrade through the Install tool.

**Notes**

- You can add several field for the same vocabulary on the same table: just name the fields differently.

## Usage in frontend

### FLUIDTEMPLATE

Use the Taxonomy processor

```typoscript
page.10 = FLUIDTEMPLATE
page.10 {
   dataProcessing {
      10 = taxonomy
      10 {
          if.isTrue.field = my_taxonomy_field
          fieldName = my_taxonomy_field
          as = my_taxonomy
      }
   }
}
```

See `\GAYA\Taxonomy\DataProcessing\TaxonomyProcessor` for more details about available options.

## FAQ

### I don't see any Taxonomy tab in the Site configuration

At least one vocabulary must exists somewhere in the pagetree to make the Taxonomy tab available in the site configuration. 

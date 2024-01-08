<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Tca;

final class TaxonomyConfiguration
{
    protected const SELECT_SINGLE = 'selectSingle';
    protected const SELECT_TREE = 'selectTree';

    protected string $tableName;

    protected string $fieldName;

    protected string $vocabularyName;

    protected string $label = '';

    protected string $description = '';

    protected array $types = [];

    protected string $position = '';

    protected string $displayCond = '';

    protected string $onChange = '';

    protected string $renderType = self::SELECT_SINGLE;

    protected array $configurationOverride = [];

    public function __construct(
        string $tableName,
        string $fieldName,
        string $vocabularyName
    )
    {
        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
        $this->vocabularyName = $vocabularyName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getVocabularyName(): string
    {
        return $this->vocabularyName;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): TaxonomyConfiguration
    {
        $this->label = $label;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): TaxonomyConfiguration
    {
        $this->description = $description;

        return $this;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function setTypes(array $types): TaxonomyConfiguration
    {
        $this->types = $types;

        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): TaxonomyConfiguration
    {
        $this->position = $position;

        return $this;
    }

    public function getRenderType(): string
    {
        return $this->renderType;
    }

    public function setRenderTypeSelect(): TaxonomyConfiguration
    {
        $this->renderType = self::SELECT_SINGLE;

        return $this;
    }

    public function setRenderTypeTree(): TaxonomyConfiguration
    {
        $this->renderType = self::SELECT_TREE;

        return $this;
    }

    public function getConfigurationOverride(): array
    {
        return $this->configurationOverride;
    }

    public function setConfigurationOverride(array $configurationOverride): TaxonomyConfiguration
    {
        $this->configurationOverride = $configurationOverride;

        return $this;
    }

    public function getDisplayCond(): string
    {
        return $this->displayCond;
    }

    public function setDisplayCond(string $displayCond): TaxonomyConfiguration
    {
        $this->displayCond = $displayCond;

        return $this;
    }

    public function getOnChange(): string
    {
        return $this->onChange;
    }

    public function setOnChange(string $onChange): TaxonomyConfiguration
    {
        $this->onChange = $onChange;

        return $this;
    }

    public function setRequired(bool $required = true): TaxonomyConfiguration
    {
        $this->configurationOverride['required'] = $required;

        return $this;
    }
}

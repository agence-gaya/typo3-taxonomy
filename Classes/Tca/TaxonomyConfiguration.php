<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Tca;

final class TaxonomyConfiguration
{
    protected const string SELECT_SINGLE = 'selectSingle';

    protected const string SELECT_TREE = 'selectTree';

    private string $label = '';

    private string $description = '';

    private array $types = [];

    private string $position = '';

    private string $displayCond = '';

    private string $onChange = '';

    private string $renderType = self::SELECT_SINGLE;

    private array $configurationOverride = [];

    public function __construct(private readonly string $tableName, private readonly string $fieldName, private readonly string $vocabularyName) {}

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

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function setTypes(array $types): self
    {
        $this->types = $types;

        return $this;
    }

    public function getPosition(): string
    {
        return $this->position;
    }

    public function setPosition(string $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getRenderType(): string
    {
        return $this->renderType;
    }

    public function setRenderTypeSelect(): self
    {
        $this->renderType = self::SELECT_SINGLE;

        return $this;
    }

    public function setRenderTypeTree(): self
    {
        $this->renderType = self::SELECT_TREE;

        return $this;
    }

    public function getConfigurationOverride(): array
    {
        return $this->configurationOverride;
    }

    public function setConfigurationOverride(array $configurationOverride): self
    {
        $this->configurationOverride = $configurationOverride;

        return $this;
    }

    public function getDisplayCond(): string
    {
        return $this->displayCond;
    }

    public function setDisplayCond(string $displayCond): self
    {
        $this->displayCond = $displayCond;

        return $this;
    }

    public function getOnChange(): string
    {
        return $this->onChange;
    }

    public function setOnChange(string $onChange): self
    {
        $this->onChange = $onChange;

        return $this;
    }

    public function setRequired(bool $required = true): self
    {
        $this->configurationOverride['required'] = $required;

        return $this;
    }
}

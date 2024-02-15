<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\ViewHelpers;

use GAYA\Taxonomy\Domain\Repository\TermRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\Variables\ScopedVariableProvider;
use TYPO3Fluid\Fluid\Core\Variables\StandardVariableProvider;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class ForEachViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    protected $escapeOutput = false;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('tableName', 'string', 'The table name of the record for which the taxonomy is to be loaded', true);
        $this->registerArgument('fieldName', 'string', 'The field name of the record for which the taxonomy is to be loaded', true);
        $this->registerArgument('recUid', 'int', 'The uid of the record', true);
        $this->registerArgument('as', 'string', 'The name of the iteration variable', true);
        $this->registerArgument('key', 'string', 'Variable to assign array key to');
        $this->registerArgument('iteration', 'string', 'The name of the variable to store iteration information (index, cycle, total, isFirst, isLast, isEven, isOdd)');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $each = static::getTerms($arguments['tableName'], $arguments['fieldName'], $arguments['recUid']);
        if (empty($each)) {
            return '';
        }

        if (isset($arguments['iteration'])) {
            $iterationData = [
                'index' => 0,
                'cycle' => 1,
                'total' => count($each)
            ];
        }

        $globalVariableProvider = $renderingContext->getVariableProvider();
        $localVariableProvider = new StandardVariableProvider();
        $renderingContext->setVariableProvider(new ScopedVariableProvider($globalVariableProvider, $localVariableProvider));

        $output = '';
        foreach ($each as $singleElement) {
            $localVariableProvider->add($arguments['as'], $singleElement);
            if (isset($iterationData)) {
                $iterationData['isFirst'] = $iterationData['cycle'] === 1;
                $iterationData['isLast'] = $iterationData['cycle'] === $iterationData['total'];
                $iterationData['isEven'] = $iterationData['cycle'] % 2 === 0;
                $iterationData['isOdd'] = !$iterationData['isEven'];
                $localVariableProvider->add($arguments['iteration'], $iterationData);
                $iterationData['index']++;
                $iterationData['cycle']++;
            }
            $output .= $renderChildrenClosure();
        }

        $renderingContext->setVariableProvider($globalVariableProvider);

        return $output;
    }

    protected static function getTerms(string $tableName, string $fieldName, int $recUid): array
    {
        $termRepository = GeneralUtility::makeInstance(TermRepository::class);

        return $termRepository->findByRelation($tableName, $fieldName, $recUid);
    }
}

<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Controller;

use GAYA\Taxonomy\Domain\Repository\VocabularyRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsController]
final class VocabulariesController
{
    private const string MODULE_NAME = 'content_taxonomy_vocabularies';
    private const string MODULE_NAME_TERMS = 'content_taxonomy_terms';

    private ServerRequestInterface $request;
    private ModuleTemplate $view;

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly ComponentFactory $componentFactory,
        private readonly IconFactory $iconFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly FlashMessageService $flashMessageService,
        private readonly VocabularyRepository $vocabularyRepository,
    ) {}

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->view->setTitle('Taxonomy');

        /** @var Route $route */
        $route = $request->getAttribute('route');

        return match ($route->getOption('_identifier')) {
            self::MODULE_NAME . '.deleteVocabulary' => $this->deleteVocabularyAction(),
            default => $this->vocabulariesAction(),
        };
    }

    private function vocabulariesAction(): ResponseInterface
    {
        $canList = $this->canList($this->vocabularyRepository->getTableName());
        $canModify = $this->canModify($this->vocabularyRepository->getTableName(), 0);

        if ($canModify) {
            $this->addLinkButton(
                'Add a vocabulary',
                (string) $this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        $this->vocabularyRepository->getTableName() => [
                            0 => 'new',
                        ],
                    ],
                    'module' => self::MODULE_NAME,
                    'returnUrl' => (string) $this->buildUri(),
                ]),
                'actions-plus'
            );
        }

        $this->assignCommonVariables($canList, $canModify);
        $vocabularies = $canList ? $this->vocabularyRepository->getAllVocabulary() : [];
        foreach ($vocabularies as &$vocabulary) {
            $vocabulary['viewUrl'] = (string) $this->uriBuilder->buildUriFromRoute(self::MODULE_NAME_TERMS, [
                'module' => self::MODULE_NAME_TERMS,
                'vocabulary' => (int) $vocabulary['uid'],
            ]);
            $vocabulary['editUrl'] = (string) $this->uriBuilder->buildUriFromRoute('record_edit', [
                'edit' => [
                    $this->vocabularyRepository->getTableName() => [
                        (int) $vocabulary['uid'] => 'edit',
                    ],
                ],
                'module' => self::MODULE_NAME,
                'returnUrl' => (string) $this->buildUri(),
            ]);
            $vocabulary['deleteUrl'] = (string) $this->buildUri('deleteVocabulary', [
                'vocabulary' => (int) $vocabulary['uid'],
            ]);
        }
        unset($vocabulary);

        $this->view->assignMultiple([
            'vocabularies' => $vocabularies,
        ]);

        return $this->view->renderResponse('Backend/Vocabularies');
    }

    private function deleteVocabularyAction(): ResponseInterface
    {
        $uid = (int) ($this->request->getQueryParams()['vocabulary'] ?? 0);
        $vocabulary = $this->vocabularyRepository->findVocabulary($uid);

        if ($vocabulary === null || !$this->canModify($this->vocabularyRepository->getTableName(), 0)) {
            $this->addFlashMessage('You are not allowed to delete this vocabulary.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToVocabularies();
        }

        $this->vocabularyRepository->remove($uid);

        return $this->redirectToVocabularies();
    }

    private function assignCommonVariables(bool $canList, bool $canModify): void
    {
        $this->pageRenderer->loadJavaScriptModule('@gaya/taxonomy/Backend/taxonomy-module.js');
        $this->view->assignMultiple([
            'canList' => $canList,
            'canModify' => $canModify,
            'moduleName' => self::MODULE_NAME,
        ]);
    }

    private function addLinkButton(string $title, string $href, string $iconIdentifier): void
    {
        $button = $this->componentFactory->createLinkButton()
            ->setHref($href)
            ->setTitle($title)
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon($iconIdentifier, IconSize::SMALL));

        $this->view->addButtonToButtonBar($button, ButtonBar::BUTTON_POSITION_LEFT);
    }

    private function canList(string $table): bool
    {
        return $this->getBackendUser()->check('tables_select', $table);
    }

    private function canModify(string $table, int $pageId): bool
    {
        if (!$this->getBackendUser()->check('tables_modify', $table)) {
            return false;
        }
        if ($pageId <= 0) {
            return $this->getBackendUser()->isAdmin();
        }
        $page = BackendUtility::getRecord('pages', $pageId);
        if (!is_array($page) || !$this->getBackendUser()->isInWebMount($page)) {
            return false;
        }
        return (new Permission($this->getBackendUser()->calcPerms($page)))->editContentPermissionIsGranted();
    }

    private function redirectToVocabularies(): ResponseInterface
    {
        return new RedirectResponse((string) $this->buildUri(), 303);
    }

    private function addFlashMessage(string $message, ContextualFeedbackSeverity $severity): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity, true);
        $this->flashMessageService->getMessageQueueByIdentifier()->enqueue($flashMessage);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function buildUri($route = '', ?array $arguments = []): UriInterface
    {
        return $this->uriBuilder->buildUriFromRoute(self::MODULE_NAME . ($route !== '' ? '.' . $route : ''), array_merge([
            'module' => self::MODULE_NAME,
        ], $arguments));
    }
}

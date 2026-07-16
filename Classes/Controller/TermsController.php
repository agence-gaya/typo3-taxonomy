<?php

declare(strict_types=1);

namespace GAYA\Taxonomy\Controller;

use GAYA\Taxonomy\Domain\Repository\TermRepository;
use GAYA\Taxonomy\Domain\Repository\VocabularyRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\JsonResponse;
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
final class TermsController
{
    private const string MODULE_NAME = 'content_taxonomy_terms';
    private const string MODULE_NAME_VOCABULARIES = 'content_taxonomy_vocabularies';

    private ServerRequestInterface $request;
    private ModuleTemplate $view;
    private ?array $page = null;
    private ?array $vocabulary = null;

    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly UriBuilder $uriBuilder,
        private readonly ComponentFactory $componentFactory,
        private readonly IconFactory $iconFactory,
        private readonly PageRenderer $pageRenderer,
        private readonly FlashMessageService $flashMessageService,
        private readonly TermRepository $termRepository,
        private readonly VocabularyRepository $vocabularyRepository,
    ) {}

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->view->setTitle('Vocabulary');

        /** @var Route $route */
        $route = $request->getAttribute('route');

        $vocabularyUid = (int) ($this->request->getQueryParams()['vocabulary'] ?? 0);
        if ($vocabularyUid === 0) {
            return $this->redirectToVocabularies();
        }
        $this->vocabulary = $this->vocabularyRepository->findVocabulary($vocabularyUid);

        if ($this->vocabulary === null) {
            return $this->redirectToVocabularies();
        }

        $id = (int) ($request->getQueryParams()['id'] ?? '');
        if ($id > 0 && $this->canShowPage($id)) {
            $page = BackendUtility::getRecord('pages', $id);
            if ($page['module'] === 'taxonomy') {
                $this->page = $page;
            }
        }

        return match ($route->getOption('_identifier')) {
            self::MODULE_NAME . '.deleteTerm' => $this->deleteTermAction(),
            self::MODULE_NAME . '.sortTerms' => $this->sortTermsAction(),
            default => $this->termsAction(),
        };
    }

    private function termsAction(): ResponseInterface
    {
        $canList = false;
        $canModify = false;
        $terms = [];

        if ($this->page !== null) {
            $canList = $this->canList($this->termRepository->getTableName()) && $this->canShowPage($this->page['uid']);
            $canModify = $this->canModify($this->termRepository->getTableName(), $this->page['uid']);
        }

        $this->addLinkButton(
            'Back',
            (string) $this->uriBuilder->buildUriFromRoute(self::MODULE_NAME_VOCABULARIES),
            'actions-arrow-left'
        );

        if ($canModify) {
            $returnUrl = (string) $this->buildUri();
            $this->addLinkButton(
                'Add a term',
                (string) $this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        $this->termRepository->getTableName() => [
                            $this->page['uid'] => 'new',
                        ],
                    ],
                    'defVals' => [
                        $this->termRepository->getTableName() => [
                            'vocabulary' => $this->vocabulary['uid'],
                        ],
                    ],
                    'module' => self::MODULE_NAME,
                    'returnUrl' => $returnUrl,
                ]),
                'actions-plus'
            );
        }

        if ($canList) {
            $terms = $this->buildTermTree($this->termRepository->findTerms($this->vocabulary['uid'], $this->page['uid']));
            $returnUrl = (string) $this->buildUri();
            foreach ($terms as &$term) {
                $term['editUrl'] = (string) $this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        $this->termRepository->getTableName() => [
                            (int) $term['uid'] => 'edit',
                        ],
                    ],
                    'module' => self::MODULE_NAME,
                    'returnUrl' => $returnUrl,
                ]);
                $term['deleteUrl'] = (string) $this->buildUri('deleteTerm', [
                    'term' => (int) $term['uid'],
                ]);
                $term['indent'] = (int) $term['depth'] * 24;
            }
            unset($term);
        }

        $this->assignCommonVariables($canList, $canModify);
        $this->view->assignMultiple([
            'vocabulary' => $this->vocabulary,
            'page' => $this->page,
            'terms' => $terms,
            'sortUrl' => (string) $this->buildUri('sortTerms'),
        ]);

        return $this->view->renderResponse('Backend/Terms');
    }

    private function deleteTermAction(): ResponseInterface
    {
        $uid = (int) ($this->request->getQueryParams()['term'] ?? 0);
        $term = $this->termRepository->findTerm($uid);

        if ($term === null || !$this->canModify($this->termRepository->getTableName(), (int) $term['pid'])) {
            $this->addFlashMessage('You are not allowed to delete this term.', ContextualFeedbackSeverity::ERROR);
            return $this->redirectToTerms();
        }

        $this->termRepository->removeTerm($uid);

        return $this->redirectToTerms();
    }

    private function sortTermsAction(): ResponseInterface
    {
        if ($this->page === null) {
            $this->redirectToTerms();
        }

        $body = (array) ($this->request->getParsedBody() ?? []);
        $items = is_array($body['items'] ?? null) ? $body['items'] : [];

        if (!$this->canModify($this->termRepository->getTableName(), $this->page['uid'])) {
            return new JsonResponse(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $availableUids = array_flip(array_map(
            static fn(array $term): int => (int) $term['uid'],
            $this->termRepository->findTerms($this->vocabulary['uid'], $this->page['uid'])
        ));
        $updates = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $uid = (int) ($item['uid'] ?? 0);
            $parent = (int) ($item['parent'] ?? 0);
            if (!isset($availableUids[$uid]) || ($parent > 0 && !isset($availableUids[$parent])) || $uid === $parent) {
                continue;
            }
            $updates[$uid] = [
                'parent' => $parent,
                'sorting' => ($index + 1) * 256,
            ];
        }

        if (count($updates) !== count($availableUids)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid term tree.'], 400);
        }

        foreach ($updates as $uid => $fields) {
            $this->termRepository->updateTerm($uid, $fields);
        }

        return new JsonResponse(['success' => true]);
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

        $this->view->addButtonToButtonBar($button);
    }

    private function buildTermTree(array $terms): array
    {
        $children = [];
        foreach ($terms as $term) {
            $children[(int) ($term['parent'] ?? 0)][] = $term;
        }

        $tree = [];
        $appendChildren = function (int $parent, int $depth) use (&$appendChildren, &$children, &$tree): void {
            foreach ($children[$parent] ?? [] as $term) {
                $term['depth'] = $depth;
                $tree[] = $term;
                $appendChildren((int) $term['uid'], $depth + 1);
            }
        };
        $appendChildren(0, 0);

        return $tree;
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

    private function canShowPage(int $pageId): bool
    {
        if ($pageId <= 0) {
            return false;
        }
        $page = BackendUtility::getRecord('pages', $pageId);
        if (!is_array($page) || !$this->getBackendUser()->isInWebMount($page)) {
            return false;
        }
        return (new Permission($this->getBackendUser()->calcPerms($page)))->showPagePermissionIsGranted();
    }

    private function redirectToVocabularies(): ResponseInterface
    {
        return new RedirectResponse((string) $this->uriBuilder->buildUriFromRoute(self::MODULE_NAME_VOCABULARIES), 303);
    }

    private function redirectToTerms(): ResponseInterface
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
            'vocabulary' => $this->vocabulary['uid'],
            'id' => ($this->page !== null ? $this->page['uid'] : ''),
        ], $arguments));
    }
}

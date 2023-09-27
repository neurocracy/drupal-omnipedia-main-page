<?php

declare(strict_types=1);

namespace Drupal\omnipedia_main_page\EventSubscriber\Form;

use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\omnipedia_core\Entity\WikiNodeInfo;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeRevisionInterface;
use Drupal\omnipedia_main_page\Service\MainPageDefaultInterface;
use Drupal\omnipedia_main_page\Service\MainPageResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Alter the 'system_site_information_settings' form to add main page field.
 */
class SystemSiteInformationSettingsEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageDefaultInterface $mainPageDefault
   *   The Omnipedia default main page service.
   *
   * @param \Drupal\omnipedia_main_page\Service\MainPageResolverInterface $mainPageResolver
   *   The Omnipedia main page resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface $wikiNodeRevision
   *   The Omnipedia wiki node revision service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    protected readonly MainPageDefaultInterface   $mainPageDefault,
    protected readonly MainPageResolverInterface  $mainPageResolver,
    protected readonly WikiNodeResolverInterface  $wikiNodeResolver,
    protected readonly WikiNodeRevisionInterface  $wikiNodeRevision,
    protected $stringTranslation,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::PREFIX . 'form_' .
        'system_site_information_settings' .
      '.alter' => 'onFormAlter',
    ];
  }

  /**
   * Alter the 'system_site_information_settings' form.
   *
   * This hides the front page field and repurposes its <details> container for
   * specifying a wiki node by its title rather than a path or route.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   The event object.
   */
  public function onFormAlter(FormIdAlterEvent $event): void {

    /** @var array */
    $form = &$event->getForm();

    // Prevent rendering the Drupal core front page field by denying access.
    $form['front_page']['site_frontpage']['#access'] = false;

    $form['front_page']['#title'] = $this->t('Main page');

    // Unfortunately, there doesn't seem to be an alternative to rendering the
    // link here, as even a render array from Link::toRenderable() causes
    // warnings. It's preferable to create a link programmatically rather than
    // embedding an <a> element in the text, as the former can be altered via
    // Drupal's hooks while the latter cannot.
    /** @var string A link to the content overview admin page with the wiki node type filter pre-selected. */
    $contentOverviewLink = Link::createFromRoute(
      $this->t('an existing wiki page'),
      'system.admin_content',
      ['type' => WikiNodeInfo::TYPE],
    )->toString();

    /** @var \Drupal\node\NodeInterface|null */
    $mainPage = $this->mainPageResolver->get('default');

    $form['front_page']['main_page_title'] = [
      '#type'             => 'textfield',
      '#default_value'    => $mainPage->getTitle(),
      '#element_validate' => [[$this, 'validateMainPageTitleElement']],
      '#required'         => true,
      '#title'            => $this->t('Main page title'),
      '#description'      => $this->t(
        'The title of @contentOverviewLink to use as the main page.',
        ['@contentOverviewLink' => $contentOverviewLink],
      ),
      '#autocomplete_route_name' =>
        'omnipedia_core.wiki_node_title_autocomplete',
    ];

    // Prepend our submit handler to the #submit array so it's triggered before
    // the default one.
    \array_unshift($form['#submit'], [$this, 'submitMainPage']);

  }

  /**
   * Validate the main page title form element.
   *
   * This ensures that the main page title matches an existing wiki node.
   *
   * @param array &$element
   *   The element being validated.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   *
   * @param array &$form
   *   The whole form.
   */
  public function validateMainPageTitleElement(
    array &$element, FormStateInterface $formState, array &$form,
  ): void {

    if (empty($element['#value'])) {

      $formState->setErrorByName(
        'main_page_title', $this->t('The main page title cannot be empty.')
      );

      return;

    }

    if (empty($this->wikiNodeResolver->nodeOrTitleToNids(
      $element['#value'],
    ))) {

      $formState->setErrorByName('main_page_title', $this->t(
        'The main page title doesn\'t match any existing wiki page.',
      ));

      return;

    }

    if (\is_null($this->wikiNodeRevision->getWikiNodeRevision(
      $formState->getValue('main_page_title'),
      $formState->getValue('default_date'),
    ))) {

      $formState->setErrorByName('main_page_title', $this->t(
        'The main page title exists but that wiki page doesn\'t have a revision available for the specifed default date.',
      ));

      return;

    }

  }

  /**
   * Submit callback to update the default main page based on input.
   *
   * @param array &$form
   *   The whole form.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   */
  public function submitMainPage(
    array $form, FormStateInterface $formState,
  ): void {

    $node = $this->wikiNodeRevision->getWikiNodeRevision(
      $formState->getValue('main_page_title'),
      $formState->getValue('default_date'),
    );

    $this->mainPageDefault->set($node);

  }

}

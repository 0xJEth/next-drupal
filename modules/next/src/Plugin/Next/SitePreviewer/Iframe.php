<?php

namespace Drupal\next\Plugin\Next\SitePreviewer;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\next\Form\IframeSitePreviewerSwitcherForm;
use Drupal\next\Plugin\ConfigurableSitePreviewerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a site resolver plugin for entity reference fields.
 *
 * @SitePreviewer(
 *  id = "iframe",
 *  label = "Iframe",
 *  description = "Displays the site preview in an iframe."
 * )
 */
class Iframe extends ConfigurableSitePreviewerBase implements ContainerFactoryPluginInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Iframe constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, FormBuilderInterface $form_builder, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->request = $request;
    $this->formBuilder = $form_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('form_builder'),
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['width'] = [
      '#title' => $this->t('Width'),
      '#size' => 10,
      '#type' => 'textfield',
      '#description' => $this->t('Provide a width for the iframe. Example: <em>100%</em> or <em>500px</em>.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['width'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['width'] = $form_state->getValue('width');
  }

  /**
   * {@inheritdoc}
   */
  public function render(EntityInterface $entity, array $sites) {
    $build = [];
    $site_id = $this->request->query->get('site');
    $site = $site_id ? $sites[$site_id] : reset($sites);

    if (count($sites) > 1) {
      $build['form'] = $this->formBuilder->getForm(IframeSitePreviewerSwitcherForm::class, $entity, $sites, $site->id());
    }

    $build['toolbar'] = [
      '#prefix' => '<div class="next-site-preview-toolbar">',
      '#suffix' => '</div>',
      'info' => [
        '#type' => 'inline_template',
        '#template' => '<p class="heading-f">{{title}} ({{ bundle }})</p>',
        '#context' => [
          'bundle' => $entity->bundle(),
          'title' => $entity->label(),
        ],
      ],
      'links' => [
        '#theme' => 'links',
        '#links' => [],
        '#attributes' => [
          'class' => ['operations', 'clearfix'],
        ],
      ],
    ];

    if ($entity instanceof EntityPublishedInterface) {
      $build['toolbar']['links']['#links']['status'] = [
        'title' => $entity->isPublished() ? $this->t('Published') : $this->t('Unpublished'),
        'attributes' => [
          'class' => [
            $entity->isPublished() ? 'published' : '',
          ],
        ]
      ];

      if ($live_url = $site->getLiveUrlForEntity($entity)) {
        $build['toolbar']['links']['#links']['live_link'] = [
          'title' => $this->t('View on @label', [
            '@label' => $site->label(),
          ]),
          'url' => $live_url,
          'attributes' => [
            'class' => [
              'button'
            ],
            'target' => '_blank',
            'rel' => 'nofollow',
          ],
        ];
      }
    }

    // Handle revisions.
    if ($entity instanceof RevisionableInterface && !$entity->isDefaultRevision()) {
      /** @var \Drupal\Core\Entity\RevisionableInterface $revision */
      $revision = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->loadRevision($entity->getRevisionId());

      $build['toolbar']['links']['#links']['status'] = [
        'title' => $this->t('Revision: @date', [
          '@date' => $this->dateFormatter->format($revision->revision_timestamp->value, 'short'),
        ]),
        'attributes' => [],
      ];
      unset($build['toolbar']['links']['#links']['live_link']);
    }

    $build['iframe'] = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#prefix' => '<div class="next-site-preview-container">',
      '#suffix' => '</div>',
      '#attributes' => [
        'src' => $site->getPreviewUrlForEntity($entity)->toString(),
        'frameborder' => 0,
        'scrolling' => FALSE,
        'allowtransparency' => TRUE,
        'width' => $this->configuration['width'],
        'class' => ['next-site-preview-iframe'],
      ],
      '#attached' => [
        'library' => [
          'next/site_preview.iframe',
        ],
      ],
    ];

    return $build;
  }

}

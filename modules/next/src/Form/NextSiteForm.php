<?php

namespace Drupal\next\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base form for next_site.
 */
class NextSiteForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var \Drupal\next\Entity\NextSiteInterface $entity */
    $entity = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Example: Blog or Marketing site.'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\next\Entity\NextSite::load',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base URL'),
      '#description' => $this->t('Enter the base URL for the Next.js site. Example: <em>https://example.com</em>.'),
      '#default_value' => $entity->getBaseUrl(),
      '#required' => TRUE,
    ];

    $form['preview'] = [
      '#title' => $this->t('Preview'),
      '#type' => 'fieldset',
    ];

    $form['preview']['preview_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview URL'),
      '#description' => $this->t('Enter the preview URL. Example: <em>https://example.com/api/preview</em>.'),
      '#default_value' => $entity->getPreviewUrl(),
    ];

    $form['preview']['preview_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview secret'),
      '#description' => $this->t('Enter a secret for the site preview. This is the same value used for <em>DRUPAL_PREVIEW_SECRET</em>.'),
      '#default_value' => $entity->getPreviewSecret(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\next\Entity\NextSiteInterface $next_site */
    $next_site = $this->entity;
    $status = $next_site->save();

    $this->messenger()->addStatus($this->t('Next.js site %label has been %action.', [
      '%label' => $next_site->label(),
      '%action' => $status === SAVED_NEW ? 'added' : 'updated',
    ]));

    $form_state->setRedirectUrl($next_site->toUrl('collection'));
  }

}

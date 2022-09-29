<?php

namespace Drupal\next\Plugin;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines an interface for the revalidator plugin.
 */
interface RevalidatorInterface {

  /**
   * Returns the ID of the plugin.
   *
   * @return string
   *   The plugin ID.
   */
  public function getId(): string;

  /**
   * Returns the label for the plugin.
   *
   * @return string
   *   The plugin label.
   */
  public function getLabel(): string;

  /**
   * Returns the description for the plugin.
   *
   * @return string
   *   The plugin description.
   */
  public function getDescription(): string;

  /**
   * Returns an array of paths to revalidate for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   An array of paths.
   */
  public function getPathsForEntity(EntityInterface $entity): array;

}

<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback;

use Drupal\tidy_feedback\Model\Item;

/**
 * @todo Add interface description.
 */
interface ItemRepositoryInterface {

  /**
   * @param array $criteria
   * @param array $orderBy
   * @return Item[]
   */
  public function findBy(array $criteria, array $orderBy): array;

  public function persist(Item $item, bool $flush = true): Item;
}

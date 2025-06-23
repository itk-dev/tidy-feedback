<?php

declare(strict_types=1);

namespace Drupal\tidy_feedback;

use Drupal\tidy_feedback\Model\Item;
use Symfony\Component\Yaml\Yaml;

/**
 * @todo Add class description.
 */
final class ItemRepository implements ItemRepositoryInterface {
  public function findBy(array $criteria, array $orderBy): array
  {
    $items = $this->loadItems();

// @tode Filter and sort

    return $items;
  }

  public function persist(Item $item, bool $flush = true): Item
  {
    $items = $this->loadItems();

    $id = new \ReflectionProperty($item, 'id');
    $id->setValue($item, (string)(count($items) + 1));

    $items[] = $item;
    $this->savedItems($items);

    return $item;
  }

  private function loadItems(): array
  {
    try {
      $contents = file_get_contents($this->getStoragePath()) ?: '[]';
      $items = json_decode($contents, true);
    } catch (\Exception $e) {}

    return $items ?: [];
  }

  private function savedItems(array $items): array
  {
    file_put_contents($this->getStoragePath(), json_encode($items, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_SLASHES));

    return $items;
  }

  private function getStoragePath(): string
  {
    return __FILE__.'.data.json';
  }
}

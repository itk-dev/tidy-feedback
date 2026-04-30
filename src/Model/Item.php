<?php

namespace ItkDev\TidyFeedback\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Feedback item entity storing user-submitted data as JSON.
 */
#[Entity]
class Item extends AbstractModel
{
    protected static string $type = 'item';

    #[Column(type: Types::JSON)]
    private array $data = [];

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data ?? [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Merge a single key-value pair into the data payload.
     */
    public function addData(string $key, mixed $value): static
    {
        $data = $this->getData();
        $data[$key] = $value;

        return $this->setData($data);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return [
            'data' => $this->data,
        ];
    }
}

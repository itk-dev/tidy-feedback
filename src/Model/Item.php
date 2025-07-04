<?php

namespace ItkDev\TidyFeedback\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

#[Entity]
class Item extends AbstractModel
{
    protected static string $type = 'item';

    #[Column(type: Types::STRING)]
    private string $subject;

    #[Column(type: Types::STRING, nullable: true)]
    private ?string $status = null;

    #[Column(type: Types::STRING, nullable: true)]
    private ?string $category = null;

    #[Column(type: Types::JSON)]
    private array $data = [];

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function getStatus(): ?ItemStatus
    {
        return $this->status ? ItemStatus::from($this->status) : null;
    }

    public function setStatus(ItemStatus $status): static
    {
        $this->status = $status->value;

        return $this;
    }

    public function getCategory(): ?ItemCategory
    {
        return $this->category ? ItemCategory::from($this->category) : null;
    }

    public function setCategory(ItemCategory $category): static
    {
        $this->category = $category->value;

        return $this;
    }

    public function getData(): array
    {
        return $this->data ?? [];
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function addData(string $key, mixed $value): static
    {
        $data = $this->getData();
        $data[$key] = $value;

        return $this->setData($data);
    }

    public function getAttributes(): array
    {
        return [
            'subject' => $this->subject,
            'data' => $this->data,
        ];
    }
}

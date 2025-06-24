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

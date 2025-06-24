<?php

namespace ItkDev\TidyFeedback\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\PrePersist;

#[MappedSuperclass]
#[HasLifecycleCallbacks]
abstract class AbstractModel implements \JsonSerializable
{
    #[Id, Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected string $id;

    #[Column(type: Types::DATETIME_IMMUTABLE)]
    protected \DateTimeInterface $createdAt;

    #[Column(type: Types::STRING, nullable: true)]
    protected ?string $createdBy = null;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @return string|null
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    // https://jsonapi.org/
    public function jsonSerialize(): array
    {
        return [
            'type' => static::$type,
            'id' => $this->id,
            'attributes' => [
                'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
                'created_by' => $this->createdBy,
            ] + $this->getAttributes(),
        ];
    }

    abstract protected function getAttributes(): array;

    #[PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable(timezone: new \DateTimeZone('UTC'));
    }
}

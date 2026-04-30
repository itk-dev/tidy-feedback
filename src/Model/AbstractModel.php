<?php

namespace ItkDev\TidyFeedback\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\PrePersist;

/**
 * Base Doctrine entity with common fields for all feedback models.
 *
 * Provides id, createdAt, createdBy and JSON:API-style serialisation.
 */
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

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?string $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Serialise into a JSON:API resource object.
     *
     * @see https://jsonapi.org/
     */
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

    /**
     * Return model-specific attributes for JSON serialisation.
     */
    abstract protected function getAttributes(): array;

    /**
     * Set createdAt timestamp before persisting.
     */
    #[PrePersist]
    public function prePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable(timezone: new \DateTimeZone('UTC'));
    }
}

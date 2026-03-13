<?php

declare(strict_types=1);

namespace ItkDev\TidyFeedback\Tests\Model;

use ItkDev\TidyFeedback\Model\Item;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Item::class)]
class ItemTest extends TestCase
{
    public function testGetDataReturnsEmptyArrayByDefault(): void
    {
        $item = new Item();
        $this->assertSame([], $item->getData());
    }

    public function testSetDataAndGetData(): void
    {
        $item = new Item();
        $data = ['description' => 'A bug', 'email' => 'user@example.com'];

        $result = $item->setData($data);

        $this->assertSame($data, $item->getData());
        $this->assertSame($item, $result, 'setData should return $this for fluent chaining');
    }

    public function testAddData(): void
    {
        $item = new Item();
        $item->setData(['existing' => 'value']);

        $result = $item->addData('new_key', 'new_value');

        $this->assertSame('value', $item->getData()['existing']);
        $this->assertSame('new_value', $item->getData()['new_key']);
        $this->assertSame($item, $result, 'addData should return $this for fluent chaining');
    }

    public function testAddDataOverwritesExistingKey(): void
    {
        $item = new Item();
        $item->setData(['key' => 'original']);

        $item->addData('key', 'updated');

        $this->assertSame('updated', $item->getData()['key']);
    }

    public function testGetAttributes(): void
    {
        $item = new Item();
        $data = ['description' => 'Test feedback'];
        $item->setData($data);

        $attributes = $item->getAttributes();

        $this->assertSame(['data' => $data], $attributes);
    }

    public function testSetCreatedByAndGetCreatedBy(): void
    {
        $item = new Item();

        $this->assertNull($item->getCreatedBy());

        $result = $item->setCreatedBy('user@example.com');

        $this->assertSame('user@example.com', $item->getCreatedBy());
        $this->assertSame($item, $result);
    }

    public function testSetCreatedByAcceptsNull(): void
    {
        $item = new Item();
        $item->setCreatedBy('user@example.com');
        $item->setCreatedBy(null);

        $this->assertNull($item->getCreatedBy());
    }

    public function testPrePersistSetsCreatedAt(): void
    {
        $item = new Item();
        $item->prePersist();

        $createdAt = $item->getCreatedAt();
        $this->assertInstanceOf(\DateTimeImmutable::class, $createdAt);
        $this->assertSame('UTC', $createdAt->getTimezone()->getName());
    }

    public function testJsonSerialize(): void
    {
        $item = new Item();
        $item->setCreatedBy('user@example.com');
        $item->setData(['description' => 'Test']);
        $item->prePersist();

        // Set the id property via reflection since it's normally auto-generated.
        $reflection = new \ReflectionProperty($item, 'id');
        $reflection->setValue($item, '42');

        $json = $item->jsonSerialize();

        $this->assertSame('item', $json['type']);
        $this->assertSame('42', $json['id']);
        $this->assertSame('user@example.com', $json['attributes']['created_by']);
        $this->assertSame(['description' => 'Test'], $json['attributes']['data']);
        $this->assertArrayHasKey('created_at', $json['attributes']);
    }
}

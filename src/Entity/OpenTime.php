<?php

namespace Prolyfix\OnlineCalendarBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Prolyfix\HolidayAndTime\Entity\TimeData;
use Prolyfix\OnlineCalendarBundle\Repository\OpenTimeRepository;
use Prolyfix\WeekplanningBundle\Entity\Room;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: OpenTimeRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['module_configuration_value:read']],
    denormalizationContext: ['groups' => ['module_configuration_value:write']],
)]
class OpenTime extends TimeData
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['module_configuration_value:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    #[Groups(['module_configuration_value:read', 'module_configuration_value:write'])]
    private ?string $weekday = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['module_configuration_value:read', 'module_configuration_value:write'])]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Groups(['module_configuration_value:read', 'module_configuration_value:write'])]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['module_configuration_value:read', 'module_configuration_value:write'])]
    private ?\DateTimeInterface $breakFrom = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    #[Groups(['module_configuration_value:read', 'module_configuration_value:write'])]
    private ?\DateTimeInterface $breakTo = null;

    #[ORM\ManyToOne(targetEntity: Room::class)]
    #[Groups(['module_configuration_value:read', 'module_configuration_value:write'])]
    private ?Room $room = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeekday(): ?string
    {
        return $this->weekday;
    }

    public function setWeekday(string $weekday): static
    {
        $this->weekday = $weekday;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getBreakFrom(): ?\DateTimeInterface
    {
        return $this->breakFrom;
    }

    public function setBreakFrom(?\DateTimeInterface $breakFrom): static
    {
        $this->breakFrom = $breakFrom;

        return $this;
    }

    public function getBreakTo(): ?\DateTimeInterface
    {
        return $this->breakTo;
    }

    public function setBreakTo(?\DateTimeInterface $breakTo): static
    {
        $this->breakTo = $breakTo;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): static
    {
        $this->room = $room;

        return $this;
    }

    public function __toString(): string
    {
        $room = $this->room ? $this->room->getName() : '';
        return sprintf('%s %s-%s %s-%s %s',
            (string)($this->weekday ?? ''),
            $this->startTime?->format('H:i') ?? '',
            $this->endTime?->format('H:i') ?? '',
            $this->breakFrom?->format('H:i') ?? '',
            $this->breakTo?->format('H:i') ?? '',
            $room
        );
    }
}

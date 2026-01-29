<?php

namespace Prolyfix\OnlineCalendarBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Prolyfix\HolidayAndTime\Entity\TimeData;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ApiResource(
	normalizationContext: ['groups' => ['module_configuration_value:read']],
	denormalizationContext: ['groups' => ['module_configuration_value:write']],
)]
class AppointmentCategory extends TimeData
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	#[Groups(['module_configuration_value:read'])]
	private ?int $id = null;

	#[ORM\Column(length: 255)]
	#[Groups(['module_configuration_value:read'])]
	private ?string $name = null;

	#[ORM\Column(type: Types::TEXT, nullable: true)]
	private ?string $description = null;

	#[ORM\Column]
	private int $durationMinutes = 0;

	#[ORM\Column(type: Types::TEXT, nullable: true)]
	private ?string $forBooking = null;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): ?string
	{
		return $this->name;
	}

	public function setName(?string $name): static
	{
		$this->name = $name;

		return $this;
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function setDescription(?string $description): static
	{
		$this->description = $description;

		return $this;
	}

	public function getDurationMinutes(): int
	{
		return $this->durationMinutes;
	}

	public function setDurationMinutes(int $durationMinutes): static
	{
		$this->durationMinutes = $durationMinutes;

		return $this;
	}

	public function getForBooking(): ?string
	{
		return $this->forBooking;
	}

	public function setForBooking(?string $forBooking): static
	{
		$this->forBooking = $forBooking;

		return $this;
	}

	public function __toString(): string
	{
		return (string)($this->name ?? '');
	}
}

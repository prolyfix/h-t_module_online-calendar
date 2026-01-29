<?php

namespace Prolyfix\OnlineCalendarBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Prolyfix\HolidayAndTime\Entity\TimeData;
use Prolyfix\CrmBundle\Entity\AppointmentInterface;
use Prolyfix\CrmBundle\Entity\AppointmentTrait;
use Prolyfix\PatientManagementBundle\Entity\Patient;
use Prolyfix\OnlineCalendarBundle\Entity\AppointmentType;
use Prolyfix\OnlineCalendarBundle\Repository\PatientAppointmentRepository;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PatientAppointmentRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['module_configuration_value:read']],
    denormalizationContext: ['groups' => ['module_configuration_value:write']],
)]
class PatientAppointment extends TimeData implements AppointmentInterface
{
    use AppointmentTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['module_configuration_value:read'])]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Patient::class)]
    #[Groups(['module_configuration_value:read'])]
    private ?Patient $patient = null;

    // Fallback info when patient is not matched
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $emailAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\ManyToOne(targetEntity: AppointmentCategory::class)]
    #[Groups(['module_configuration_value:read'])]
    private ?AppointmentCategory $appointmentType = null;

    public function getPatient(): ?Patient
    {
        return $this->patient;
    }

    public function setPatient(?Patient $patient): static
    {
        $this->patient = $patient;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): static
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAppointmentType(): ?AppointmentCategory
    {
        return $this->appointmentType;
    }

    public function setAppointmentType(?AppointmentCategory $appointmentType): static
    {
        $this->appointmentType = $appointmentType;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}

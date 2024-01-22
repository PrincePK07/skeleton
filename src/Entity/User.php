<?php

namespace App\Entity;

use Ramsey\Uuid\Uuid;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post;
use Doctrine\DBAL\Types\Types;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use App\Controller\AuthController;
use App\Controller\UserController;
use App\Repository\UserRepository;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource(
    routePrefix: '/auth',
    operations: [
        new Get(),
        new Put(),
        new Delete(),
        new Post(name: 'register', uriTemplate: '/resend_mail', controller: AuthController::class),
        new Post(name: 'api_refresh_token', uriTemplate: '/api/token/refresh'),
        new Post(name: 'resend_registration_mail', uriTemplate: '/register', controller: AuthController::class),
        new Get(name: 'verify_active_user', requirements: ['token' => '\d+'],
        uriTemplate: '/verify-active-user/{token}',
        controller: UserController::class),
        new GetCollection(),
    ],
)]
#[UniqueEntity('email')]
#[UniqueEntity('username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => 'ram'
        ]
    )]
    #[Assert\NotBlank(message: new TranslatableMessage('translate.validation.username_blank'))]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: new TranslatableMessage('translate.validation.username_min'),
        maxMessage: new TranslatableMessage('translate.validation.username_max'),
    )]
    private $username;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: new TranslatableMessage('translate.validation.email_blank'))]
    #[ApiProperty(
        openapiContext: [
            'type' => 'string',
            'example' => 'ram123@gmail.com'
        ]
    )]
    #[Assert\Email(
        message: new TranslatableMessage('translate.validation.email'),
    )]
    private ?string $email = null;

    #[ORM\Column]
    private ?array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: new TranslatableMessage('translate.validation.password_blank'))]
    #[Assert\Length(
        min: 6,
        max: 255,
        minMessage: new TranslatableMessage('translate.validation.password_min'),
        maxMessage: new TranslatableMessage('translate.validation.password_max'),
    )]
    private ?string $password = null;

    #[ORM\Column(type: 'boolean')]
    private $isActive;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AccessToken::class, orphanRemoval: true)]
    private Collection $accessTokens;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Contact::class, cascade: ['persist', 'remove'])]
    private ?Contact $contact = null;

    public function __construct($username)
    {
        $this->isActive = false;
        $this->username = $username;
        $this->uuid = Uuid::uuid4();
        $this->accessTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUsername()
    {
        return $this->username;
    }
    public function getSalt()
    {
        return null;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
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
    public function getIsActive(){
        return $this->isActive;
    }

    public function setIsActive(bool $value)
    {
        $this->isActive = $value;
        return $this;
    }

    /**
     * @return Collection<int, AccessToken>
     */
    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function addAccessToken(AccessToken $accessToken): static
    {
        if (!$this->accessTokens->contains($accessToken)) {
            $this->accessTokens->add($accessToken);
            $accessToken->setUser($this);
        }

        return $this;
    }

    public function removeAccessToken(AccessToken $accessToken): static
    {
        if ($this->accessTokens->removeElement($accessToken)) {
            // set the owning side to null (unless already changed)
            if ($accessToken->getUser() === $this) {
                $accessToken->setUser(null);
            }
        }

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): static
    {
        // set the owning side of the relation if necessary
        if ($contact->getUser() !== $this) {
            $contact->setUser($this);
        }

        $this->contact = $contact;

        return $this;
    }
}

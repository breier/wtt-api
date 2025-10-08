<?php

namespace App\Entity;

use App\Repository\VisitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Index(name: 'idx_visit_request_url_fp_hash', columns: ['request_url', 'fp_hash'])]
#[ORM\Entity(repositoryClass: VisitRepository::class)]
class Visit extends BaseEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(length: 255)]
    public string $request_url {
        set (string $request_url) {
            $this->request_url = substr($request_url, 0, 255);
        }
    }

    #[ORM\Column(length: 128)]
    public string $fp_hash {
        set (string $fp_hash) {
            $this->fp_hash = substr($fp_hash, 0, 128);
        }
    }

    #[ORM\Column(nullable: true)]
    public ?\DateTime $client_ts {
        set (\DateTime|string|int|null $client_ts) {
            if (is_numeric($client_ts)) {
                $client_ts = date(DATE_ATOM, (int) $client_ts);
            }

            $this->client_ts = $client_ts instanceof \DateTime ? $client_ts : new \DateTime($client_ts);
        }
    }

    #[ORM\Column]
    public \DateTimeImmutable $created_at {
        set (\DateTimeImmutable|string $created_at) {
            $this->created_at = $created_at instanceof \DateTimeImmutable ? $created_at : new \DateTimeImmutable($created_at);
        }
    }

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }
}

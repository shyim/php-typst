<?php

declare(strict_types=1);

namespace Typst;

use Typst\Exception\InvalidArgumentException;

final readonly class PdfOptions
{
    public ?string $identifier;
    public ?int $timestamp;
    public ?int $firstPage;
    public ?int $lastPage;
    public ?PdfVersion $version;
    public ?PdfValidator $validator;
    public bool $tagged;

    public function __construct(
        ?string $identifier = null,
        ?int $timestamp = null,
        ?int $first_page = null,
        ?int $last_page = null,
        ?PdfVersion $version = null,
        ?PdfValidator $validator = null,
        ?bool $tagged = null,
    ) {
        if ($first_page !== null && $first_page < 0) {
            throw new InvalidArgumentException("First page must be non-negative, got {$first_page}");
        }
        if ($last_page !== null && $last_page < 0) {
            throw new InvalidArgumentException("Last page must be non-negative, got {$last_page}");
        }
        if ($first_page !== null && $last_page !== null && $first_page > $last_page) {
            throw new InvalidArgumentException(
                "First page ({$first_page}) must not be greater than last page ({$last_page})",
            );
        }

        $this->identifier = $identifier;
        $this->timestamp = $timestamp;
        $this->firstPage = $first_page;
        $this->lastPage = $last_page;
        $this->version = $version;
        $this->validator = $validator;
        $this->tagged = $tagged ?? true;
    }

    public function withIdentifier(?string $identifier): self
    {
        return new self($identifier, $this->timestamp, $this->firstPage, $this->lastPage, $this->version, $this->validator, $this->tagged);
    }

    public function withTimestamp(?int $timestamp): self
    {
        return new self($this->identifier, $timestamp, $this->firstPage, $this->lastPage, $this->version, $this->validator, $this->tagged);
    }

    public function withFirstPage(?int $first_page): self
    {
        return new self($this->identifier, $this->timestamp, $first_page, $this->lastPage, $this->version, $this->validator, $this->tagged);
    }

    public function withLastPage(?int $last_page): self
    {
        return new self($this->identifier, $this->timestamp, $this->firstPage, $last_page, $this->version, $this->validator, $this->tagged);
    }

    public function withVersion(?PdfVersion $version): self
    {
        return new self($this->identifier, $this->timestamp, $this->firstPage, $this->lastPage, $version, $this->validator, $this->tagged);
    }

    public function withValidator(?PdfValidator $validator): self
    {
        return new self($this->identifier, $this->timestamp, $this->firstPage, $this->lastPage, $this->version, $validator, $this->tagged);
    }

    public function withTagged(bool $tagged): self
    {
        return new self($this->identifier, $this->timestamp, $this->firstPage, $this->lastPage, $this->version, $this->validator, $tagged);
    }
}

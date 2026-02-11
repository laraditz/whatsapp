<?php

namespace Laraditz\Whatsapp\Responses;

use Illuminate\Support\Collection;
use Laraditz\Whatsapp\Enums\TemplateStatus;

class TemplateResponse extends BaseResponse
{
    protected ?\Closure $nextPageResolver = null;

    public function templates(): Collection
    {
        return collect($this->get('data', []));
    }

    public function id(): ?string
    {
        return $this->get('id');
    }

    public function name(): ?string
    {
        return $this->get('name');
    }

    public function status(): ?TemplateStatus
    {
        $status = $this->get('status');

        return $status ? TemplateStatus::tryFrom($status) : null;
    }

    public function category(): ?string
    {
        return $this->get('category');
    }

    public function components(): array
    {
        return $this->get('components', []);
    }

    public function hasNextPage(): bool
    {
        return $this->get('paging.cursors.after') !== null
            && $this->get('paging.next') !== null;
    }

    public function nextPageCursor(): ?string
    {
        return $this->get('paging.cursors.after');
    }

    public function setNextPageResolver(\Closure $resolver): static
    {
        $this->nextPageResolver = $resolver;

        return $this;
    }

    public function nextPage(): ?static
    {
        if (! $this->hasNextPage() || ! $this->nextPageResolver) {
            return null;
        }

        return ($this->nextPageResolver)($this->nextPageCursor());
    }
}
